<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   function putSelectedMessagesIntoString($msg) {
      $j = 0;
      $i = 0;
      $firstLoop = true;
      
      // If they have selected nothing msg is size one still, but will be an infinite
      //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
      while (($j < count($msg)) && ($msg[0])) {
         if ($msg[$i]) {
            if ($firstLoop != true)
               $selectedMessages .= "&";
            else
               $firstLoop = false;

            $selectedMessages .= "selMsg[$j]=$msg[$i]";
            
            $j++;
         }
         $i++;
      }
   }

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages, $imapServerAddress);

   // If the delete button was pressed, the moveButton variable will not be set.
   if (!$moveButton) {
      displayPageHeader($color, $mailbox);
      if (is_array($msg) == 1) {
         // Marks the selected messages ad 'Deleted'
         $j = 0;
         $i = 0;
      
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if ($msg[$i]) {
               deleteMessages($imapConnection, $msg[$i], $msg[$i], $numMessages, $trash_folder, $move_to_trash, $auto_expunge, $mailbox);
               $j++;
            }
            $i++;
         }
         messages_deleted_message($mailbox, $sort, $startMessage, $color);
      } else {
         error_message("No messages were selected.", $mailbox, $sort, $startMessage, $color);
      }
   } else {    // Move messages
      displayPageHeader($color, $mailbox);
      // lets check to see if they selected any messages
      if (is_array($msg) == 1) {
         $j = 0;
         $i = 0;
 
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if ($msg[$i]) {
               /** check if they would like to move it to the trash folder or not */
               $success = copyMessages($imapConnection, $msg[$i], $msg[$i], $targetMailbox);
               if ($success == true)
                  setMessageFlag($imapConnection, $msg[$i], $msg[$i], "Deleted");
               $j++;
            }
            $i++;
         }
         if ($auto_expunge == true)
            expungeBox($imapConnection, $mailbox, $numMessages);

         messages_moved_message($mailbox, $sort, $startMessage, $color);
      } else {
         error_message("No messages were selected.", $mailbox, $sort, $startMessage, $color);
      }
   }

   // Log out this session
   fputs($imapConnection, "1 logout");

?>
</BODY></HTML>
