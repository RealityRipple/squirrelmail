<?php
   /**
    **  download.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Handles attachment downloads to the users computer.
    **  Also allows displaying of attachments when possible.
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($mime_php))
      include("../functions/mime.php");
   if (!isset($date_php))
      include("../functions/date.php");

   include("../src/load_prefs.php");

   function viewText($color, $body, $id, $entid, $mailbox, $type1, $wrap_at) {
      displayPageHeader($color, "None");

      echo "<BR><TABLE WIDTH=90% BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">";
      echo "<B><CENTER>";
      echo _("Viewing a plain text attachment");
      echo "</CENTER></B>";
      echo "</TD></TR><TR><TD BGCOLOR=\"$color[4]\">";
      $urlmailbox = urlencode($mailbox);
      echo "<CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$entid&mailbox=$urlmailbox\">";
      echo _("Download this as a file");
      echo "</A></CENTER><BR><BR><TT>";
      if ($type1 == "html")
         echo $body;
      else
         echo translateText($body, $wrap_at);

      echo "</TT></TD></TR></TABLE>";
   }

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);

   // $message contains all information about the message
   // including header and body
   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

   // lets redefine message as this particular entity that we wish to display.
   // it should hold only the header for this entity.  We need to fetch the body
   // yet before we can display anything.
   $message = getEntity($message, $passed_ent_id);

   $header = $message->header;
   $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);

   $type0 = $header->type0;
   $type1 = $header->type1;
   $filename = $header->filename;

   if (strlen($filename) < 1) {
      # set some standard suffixes to the filenames if the filename isn't known

      if ($type1 == "plain" && $type0 == "text")                  $suffix = "txt";
      else if ($type1 == "richtext" && $type0 == "text")          $suffix = "rtf";
      else if ($type1 == "postscript" && $type0 == "application") $suffix = "ps";
      else if ($type1 == "message" && $type0 == "rfc822")         $suffix = "msg";
      else $suffix = $type1;

      $filename = "untitled$passed_ent_id.$suffix";
   }

   // Note:
   //    The following sections display the attachment in different
   //    ways depending on how they choose.  The first way will download
   //    under any circumstance.  This sets the Content-type to be
   //    applicatin/octet-stream, which should be interpreted by the
   //    browser as "download me".
   //      The second method (view) is used for images or other formats
   //    that should be able to be handled by the browser.  It will
   //    most likely display the attachment inline inside the browser.
   //      And finally, the third one will be used by default.  If it
   //    is displayable (text or html), it will load them up in a text
   //    viewer (built in to squirrelmail).  Otherwise, it sets the
   //    content-type as application/octet-stream

   if ($absolute_dl == "true") {
      switch($type0) {
         case "text":
            $body = decodeBody($body, $header->encoding);
            #header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-type: application/octet-stream; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo trim($body);
            break;
         default:
            $body = decodeBody($body, $header->encoding);
            header("Content-type: application/octet-stream; name=\"$filename\"");
            #header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   } else if ($view == "true") {
      $body = decodeBody ($body, $header->encoding);
      header("Content-type: $type0/$type1; name=\"$filename\"");
      header("Content-disposition: attachment; filename=\"$filename\"");
      echo $body;
   } else {
      switch ($type0) {
         case "text":
            $body = decodeBody($body, $header->encoding);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            break;
         case "message":
            $body = decodeBody($body, $header->encoding);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            break;
         default:
            $body = decodeBody($body, $header->encoding);
            header("Content-type: applicatin/octet-stream; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   }

   sqimap_logout($imapConnection);
?>
