<?php
   /**
    **  download.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Handles attachment downloads to the users computer.
    **  Also allows displaying of attachments when possible.
    **
    **  $Id$
    **/

   define('download_php', true);  // Used for preferences

   include('../src/validate.php');
   include("../functions/strings.php");
   include("../config/config.php");
   include("../functions/imap.php");
   include("../functions/mime.php");
   include("../functions/date.php");
   include("../functions/i18n.php");
   include("../src/load_prefs.php");

   header("Pragma: ");
   header("Cache-Control: cache");

   function viewText($color, $body, $id, $entid, $mailbox, $type1, $wrap_at) {
      global $where, $what, $charset;
      global $startMessage;
      
      displayPageHeader($color, "None");

      echo "<BR><TABLE WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">";
      echo "<B><CENTER>";
      echo _("Viewing a text attachment") . " - ";
      if ($where && $what) {
         // from a search
         echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$id&where=".urlencode($where)."&what=".urlencode($what)."\">". _("View message") . "</a>";
      } else {   
         echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$id&startMessage=$startMessage&show_more=0\">". _("View message") . "</a>";
      }   

      $urlmailbox = urlencode($mailbox);
      echo "</b></td><tr><tr><td><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$entid&mailbox=$urlmailbox\">";
      echo _("Download this as a file");
      echo "</A></CENTER><BR>";
      echo "</CENTER></B>";
      echo "</TD></TR></TABLE>";

      echo "<TABLE WIDTH=98% BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">";
      echo "<TR><TD BGCOLOR=\"$color[4]\"><TT>";

      if ($type1 != "html")
         translateText($body, $wrap_at, $charset);
      
      echo $body;

      echo "</TT></TD></TR></TABLE>";
   }

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);

   // $message contains all information about the message
   // including header and body
   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
   $top_header = $message->header;

   // lets redefine message as this particular entity that we wish to display.
   // it should hold only the header for this entity.  We need to fetch the body
   // yet before we can display anything.
   $message = getEntity($message, $passed_ent_id);

   $header = $message->header;

   $charset = $header->charset;
   $type0 = $header->type0;
   $type1 = $header->type1;
   if (isset($override_type0))
       $type0 = $override_type0;
   if (isset($override_type1))
       $type1 = $override_type1;
   $filename = decodeHeader($header->filename);
   if (!$filename) {
      $filename = decodeHeader($header->name);
   }

   if (strlen($filename) < 1) {
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
   
   if (isset($absolute_dl) && $absolute_dl == "true") {
      switch($type0) {
         case "text":
            $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
            $body = decodeBody($body, $header->encoding);
            set_up_language(getPref($data_dir, $username, "language"));
            header("Content-Disposition: inline; filename=\"$filename\"");
            header("Content-Type: application/download; name=\"$filename\"");
            if ($type1 == "plain" && isset($showHeaders)) {
               echo _("Subject") . ": " . decodeHeader($top_header->subject) . "\n";
               echo "   " . _("From") . ": " . decodeHeader($top_header->from) . "\n";
               echo "     " . _("To") . ": " . decodeHeader(getLineOfAddrs($top_header->to)) . "\n";
               echo "   " . _("Date") . ": " . getLongDateString($top_header->date) . "\n\n";
            }
	    elseif ($type1 == "html" && isset($showHeaders)) {
	       echo '<table><tr><th align=right>' . _("Subject");
	       echo ':</th><td>' . decodeHeader($top_header->subject);
	       echo "</td></tr>\n<tr><th align=right>" . _("From");
	       echo ':</th><td>' . decodeHeader($top_header->from);
	       echo "</td></tr>\n<tr><th align=right>" . _("To");
	       echo ':</th><td>' . decodeHeader(getLineOfAddrs($top_header->to));
	       echo "</td></tr>\n<tr><th align=right>" . _("Date");
	       echo ':</th><td>' . getLongDateString($top_header->date);
	       echo "</td></tr>\n</table>\n<hr>\n";
	    }
            echo trim($body);
            break;
         default:
            header("Content-Disposition: inline; filename=\"$filename\"");
            header("Content-Type: application/download; name=\"$filename\"");
            mime_print_body_lines ($imapConnection, $passed_id, $passed_ent_id, $header->encoding);
            break;
      }
   } else {
      switch ($type0) {
         case "text":
            if ($type1 == "plain" || $type1 == "html") {
                $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
                $body = decodeBody($body, $header->encoding);
                include("../functions/page_header.php");
                viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            } else {
                $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
                $body = decodeBody($body, $header->encoding);
                header("Content-Type: $type0/$type1; name=\"$filename\"");
                header("Content-Disposition: inline; filename=\"$filename\"");
                echo $body;
            }
            break;
         case "message":
            $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
            $body = decodeBody($body, $header->encoding);
            include("../functions/page_header.php");
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            break;
         default:
            header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-Disposition: inline; filename=\"$filename\"");
            mime_print_body_lines ($imapConnection, $passed_id, $passed_ent_id, $header->encoding);
            break;
      }
   }    
    
   sqimap_logout($imapConnection);
?>
