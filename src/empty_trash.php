<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);

   if ($mailbox != $trash_folder) {
      echo "ERROR -- I'm not in the trash folder!<BR>";
      exit;
   }


   /** GET FOLDER ARRAY OF TRASH SUBFOLDERS **/
   fputs($imapConnection, "1 list \"\" *\n");
   $str = imapReadData($imapConnection);

   for ($i = 0;$i < count($str); $i++) {
      $box = Chop($str[$i]);
      $mailbox = findMailboxName($box);

      if (strpos($box, $trash_folder) > 0) {
         $folders[$i] = $mailbox;
      } else {
         $folders[$i] = "NOPE";
      }
   }

   $trash = getFolderNameMinusINBOX($trash_folder);
   $trash = "user.$username.$trash";
   // mark them as deleted
   setMessageFlag($imapConnection, 1, $numMessages, "Deleted");
   expungeBox($imapConnection, $mailbox);

   // remove subfolders
   for ($i = 0; $i < count($folders); $i++) {
      if ($folders[$i] != "NONE") {
         $trash = getFolderNameMinusINBOX($folders[$i]);
         $trash = "user.$username.$trash";
         echo "$trash<BR>";
         removeFolder($imapConnection, $trash);
      }
   }

   createFolder($imapConnection, "user.$username.$trash_folder");

   // Log out this session
   fputs($imapConnection, "1 logout");

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">";
   displayPageHeader($mailbox);

   messages_deleted_message($mailbox, $sort, $startMessage);
?>
