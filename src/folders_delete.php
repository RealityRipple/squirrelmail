<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // Marks the selected messages ad 'Deleted'
   $j = 0;
   $i = 0;

   while ($j < count($msg)) {
      if ($msg[$i]) {
         /** check if they would like to move it to the trash folder or not */
         if ($move_to_trash == true) {
            createFolder($imapConnection, "user.$username.$folder");
            $success = copyMessages($imapConnection, $msg[$i], $msg[$i], $trash_folder);
            if ($success == true)
               setMessageFlag($imapConnection, $msg[$i], $msg[$i], "Deleted");
         } else {
            setMessageFlag($imapConnection, $msg[$i], "Deleted");
         }
         $j++;
      }
      $i++;
   }

   if ($auto_expunge == true)
      expungeBox($imapConnection, $mailbox, $numMessages);

   // Log out this session
   fputs($imapConnection, "1 logout");

   echo "<BR><BR><A HREF=\"folders.php\">Return</A>";
?>


