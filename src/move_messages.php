<?php
   /**
    **  move_messages.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Enables message moving between folders on the IMAP server.
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');

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

   // expunge-on-demand if user isn't using move_to_trash or auto_expunge
   if(isset($expungeButton)) {
     sqimap_mailbox_expunge($imapConnection, $mailbox, true);
     $location = get_location();
     if ($where && $what)
       header ("Location: $location/search.php?mailbox=".urlencode($mailbox)."&what=".urlencode($what)."&where=".urlencode($where));
     else   
       header ("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=". urlencode($mailbox));
   }
   // undelete messages if user isn't using move_to_trash or auto_expunge
   elseif(isset($undeleteButton)) {
      if (is_array($msg) == 1) {
         // Removes \Deleted flag from selected messages
         $j = 0;
         $i = 0;
      
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if ($msg[$i]) {
              sqimap_messages_remove_flag ($imapConnection, $msg[$i], $msg[$i], "Deleted");
               $j++;
            }
            $i++;
         }
         $location = get_location();

         if ($where && $what)
            header ("Location: $location/search.php?mailbox=".urlencode($mailbox)."&what=".urlencode($what)."&where=".urlencode($where));
         else   
            header ("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=". urlencode($mailbox));
      } else {
         displayPageHeader($color, $mailbox);
         error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
      }
   }
   // If the delete button was pressed, the moveButton variable will not be set.
   elseif (!isset($moveButton)) {
      if (is_array($msg) == 1) {
         // Marks the selected messages as 'Deleted'
         $j = 0;
         $i = 0;
      
         // If they have selected nothing msg is size one still, but will be an infinite
         //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
         while ($j < count($msg)) {
            if (isset($msg[$i])) {
               if (isset($markRead)) {
                  sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Seen");
               } else if (isset($markRead)) {
                   sqimap_messages_remove_flag($imapConnection, $msg[$i], $msg[$i], "Seen");
               } else {
                  sqimap_messages_delete($imapConnection, $msg[$i], $msg[$i], $mailbox);
               }
               $j++;
            }
            $i++;
         }
         if ($auto_expunge) {
            sqimap_mailbox_expunge($imapConnection, $mailbox, true);
         }
         $location = get_location();
         if (isset($where) && isset($what))
            header ("Location: $location/search.php?mailbox=".urlencode($mailbox)."&what=".urlencode($what)."&where=".urlencode($where));
         else   
            header ("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=". urlencode($mailbox));
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
            if (isset($msg[$i])) {
               /** check if they would like to move it to the trash folder or not */
               sqimap_messages_copy($imapConnection, $msg[$i], $msg[$i], $targetMailbox);
               sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Deleted");
               $j++;
            }
            $i++;
         }
         if ($auto_expunge == true)
            sqimap_mailbox_expunge($imapConnection, $mailbox, true);

         $location = get_location();
         if (isset($where) && isset($what))
            header ("Location: $location/search.php?mailbox=".urlencode($mailbox)."&what=".urlencode($what)."&where=".urlencode($where));
         else   
            header ("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=". urlencode($mailbox));
      } else {
         displayPageHeader($color, $mailbox);
         error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
      }
   }

   // Log out this session
   sqimap_logout($imapConnection);

?>
</BODY></HTML>
