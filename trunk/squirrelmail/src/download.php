<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mime.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // $message contains all information about the message
   // including header and body
   $message = fetchMessage($imapConnection, $passed_id, $mailbox);

   $type0 = $message["ENTITIES"][$passed_ent_id]["TYPE0"];
   $type1 = $message["ENTITIES"][$passed_ent_id]["TYPE1"];
   $filename = $message["ENTITIES"][$passed_ent_id]["FILENAME"];
   $body = decodeBody($message["ENTITIES"][$passed_ent_id]["BODY"][0], $message["ENTITIES"][$passed_ent_id]["ENCODING"]);


   switch ($type0) {
      case "image":
         if (($type1 == "jpeg") || ($type1 == "jpg") || ($type1 == "gif") || ($type1 == "png")) {
            /** Add special instructions to view images inline here **/
            header("Content-type: $type0/$type1");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
         } else {
            header("Content-type: $type0/$type1");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            echo $body;
         }
         break;
      default:
         header("Content-type: $type0/$type1");
         header("Content-Disposition: attachment; filename=\"$filename\"");
         echo $body;
         break;
   }

   fputs($imapConnection, "1 logout\n");
?>