<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">
<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages, $imapServerAddress);

   if (strtolower($move_or_delete) == "delete selected messages") {
      // Marks the selected messages ad 'Deleted'
      $j = 0;
      $i = 0;
      while ($j < count($msg)) {
         if ($msg[$i]) {
            /** check if they would like to move it to the trash folder or not */
            if ($move_to_trash == true) {
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
      displayPageHeader($mailbox);
      messages_deleted_message($mailbox, $sort, $startMessage);
   } else {
      echo "Move";
   }

   // Log out this session
   fputs($imapConnection, "1 logout");

?>
</BODY></HTML>
