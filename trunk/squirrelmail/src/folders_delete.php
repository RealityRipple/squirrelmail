<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted);

   /** Lets start removing the folders and messages **/
   if ($move_to_trash == true) { /** if they wish to move messages to the trash **/
      /** Creates the subfolders under $trash_folder **/
      for ($i = 0; $i < count($boxesUnformatted); $i++) {
         if (substr($boxesUnformatted[$i], 0, strlen($mailbox)) == $mailbox) {
            $folderWithoutINBOX = getFolderNameMinusINBOX($boxesUnformatted[$i]);
            createFolder($imapConnection, "$trash_folder.$folderWithoutINBOX");
         }
      }
      for ($i = 0; $i < count($boxesUnformatted); $i++) {
         if (substr($boxesUnformatted[$i], 0, strlen($mailbox)) == $mailbox) {
            selectMailbox($imapConnection, $boxesUnformatted[$i], $numMessages);
            $folder = getFolderNameMinusINBOX($boxesUnformatted[$i]);

            if ($numMessages > 0)
               $success = copyMessages($imapConnection, 1, $numMessages, "$trash_folder.$folder");
            else
               $success = true;

            if ($success == true)
               removeFolder($imapConnection, "$boxesUnformatted[$i]");
         }
      }
   } else { /** if they do NOT wish to move messages to the trash **/
      for ($i = 0; $i < count($boxesUnformatted); $i++) {
         if (substr($boxesUnformatted[$i], 0, strlen($mailbox)) == $mailbox) {
            removeFolder($imapConnection, "$boxesUnformatted[$i]");
         }
      }
   }

   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<BR><BR><A HREF=\"folders.php\">Return</A>";
?>


