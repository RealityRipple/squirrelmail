<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted, $boxesRaw);

   $mailbox = $trash_folder;
   fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
   $data = imapReadData($imapConnection , "1", false, $response, $message);
   while (substr($data[0], strpos($data[0], " ")+1, 4) == "LIST") {
      for ($i = 0; $i < count($boxesUnformatted); $i++) {
         if (($boxesUnformatted[$i] == $mailbox) ||
             (substr($boxesUnformatted[$i], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
            removeFolder($imapConnection, "$boxesUnformatted[$i]");
         }
      }
      fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
      $data = imapReadData($imapConnection , "1", false, $response, $message);
   }

   createFolder($imapConnection, "$trash_folder", "");

   selectMailbox($imapConnection, $trash_folder, $numMessages);
   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, $mailbox);
   messages_deleted_message($trash_folder, $sort, $startMessage, $color);
   fputs($imapConnection, "1 logout");
?>
