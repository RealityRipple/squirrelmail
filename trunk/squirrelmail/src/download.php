<?php
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

   $type0 = $message["ENTITIES"][$passed_ent_id]["TYPE0"];
   $type1 = $message["ENTITIES"][$passed_ent_id]["TYPE1"];
   $filename = $message["ENTITIES"][$passed_ent_id]["FILENAME"];

   if (strlen($filename) < 1) {
      $filename = "untitled$passed_ent_id";
   }

   if ($absolute_dl == "true") {
      switch($type0) {
         case "text":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo trim($body);
            break;
         default:
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   } else {
      switch ($type0) {
         case "text":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            break;
         case "message":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
            break;
         default:
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1; name=\"$filename\"");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   }

   sqimap_logout($imapConnection);
?>
