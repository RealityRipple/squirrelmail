<?php
   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");

   include("../src/load_prefs.php");

   function putSelectedMessagesIntoString($msg) {
      $j = 0;
      $i = 0;
      $firstLoop = true;
      
      // If they have selected nothing msg is size one still, but will
      // be an infinite loop because we never increment j. so check to
      // see if msg[0] is set or not to fix this.
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

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);

   // If the delete button was pressed, the moveButton variable will not be set.
   if (!$moveButton) {
      if (is_array($msg) == 1) {
         // Marks the selected messages ad 'Deleted'
         $j = 0;
         $i = 0;
      
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if ($msg[$i]) {
               sqimap_messages_delete($imapConnection, $msg[$i], $msg[$i], $mailbox);
               $j++;
            }
            $i++;
         }
         if ($auto_expunge) {
            sqimap_mailbox_expunge($imapConnection, $mailbox);
         }
         if ($auto_forward) {   
            header ("Location: right_main.php");
         } else {
            displayPageHeader($color, $mailbox);
            messages_deleted_message($mailbox, $sort, $startMessage, $color);
         }
      } else {
         displayPageHeader($color, $mailbox);
         error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
      }
   } else {    // Move messages
      // lets check to see if they selected any messages
      if (is_array($msg) == 1) {
         $j = 0;
         $i = 0;
 
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if ($msg[$i]) {
               /** check if they would like to move it to the trash folder or not */
               sqimap_messages_copy($imapConnection, $msg[$i], $msg[$i], $targetMailbox);
               sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Deleted");
               $j++;
            }
            $i++;
         }
         if ($auto_expunge == true)
            sqimap_mailbox_expunge($imapConnection, $mailbox);

         if ($auto_forward) {   
            header ("Location: right_main.php");
         } else {
            displayPageHeader($color, $mailbox);
            messages_moved_message($mailbox, $sort, $startMessage, $color);
         }
      } else {
         displayPageHeader($color, $mailbox);
         error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
      }
   }

   // Log out this session
   sqimap_logout($imapConnection);

?>
</BODY></HTML>
