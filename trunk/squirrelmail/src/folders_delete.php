<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);

   $folder = getFolderNameMinusINBOX($mailbox);
   $trash = getFolderNameMinusINBOX($trash_folder);

   /** check if they would like to move it to the trash folder or not */
   if ($move_to_trash == true) {
      createFolder($imapConnection, "user.$username.$trash.$folder");
      echo "CREATING FOLDER:  user.$username.$trash.$folder<BR>";
      if ($numMessages > 0)
         $success = copyMessages($imapConnection, 1, $numMessages, $trash_folder);
      else
         $success = true;

      if ($success == true)
         removeFolder($imapConnection, "user.$username.$folder");
   } else {
      removeFolder($imapConnection, "user.$username.$folder");
   }

   // Log out this session
   fputs($imapConnection, "1 logout");

   echo "<BR><BR><A HREF=\"folders.php\">Return</A>";
?>


