<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mime.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   function viewText($color, $body, $id, $entid, $mailbox) {
      echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
      displayPageHeader($color, "None");

      echo "<BR><TABLE WIDTH=90% BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">";
      echo "<B><CENTER>Viewing a plain text attachment</CENTER></B>";
      echo "</TD></TR><TR><TD BGCOLOR=\"$color[4]\">";
      $urlmailbox = urlencode($mailbox);
      echo "<FONT FACE=\"Arial, Helvetica\"><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$entid&mailbox=$urlmailbox\">Download this as a file</A><BR><BR></FONT><TT>";
      echo nl2br($body);
      echo "</TT></TD></TR></TABLE>";
   }

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // $message contains all information about the message
   // including header and body
   $message = fetchMessage($imapConnection, $passed_id, $mailbox);

   $type0 = $message["ENTITIES"][$passed_ent_id]["TYPE0"];
   $type1 = $message["ENTITIES"][$passed_ent_id]["TYPE1"];
   $filename = $message["ENTITIES"][$passed_ent_id]["FILENAME"];

   if (strlen($filename) < 1) {
      $filename = "message" . time();
   }

   if ($absolute_dl == "true") {
      switch($type0) {
         case "text":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            if ($type1 != "html")
               echo nl2br($body);
            break;
         default:
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   } else {
      switch ($type0) {
         case "text":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox);
            break;
         case "message":
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox);
            break;
         default:
            $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);
            header("Content-type: $type0/$type1");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
            break;
      }
   }

   fputs($imapConnection, "1 logout\n");
?>
