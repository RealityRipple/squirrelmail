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

   // mark them as deleted
   setMessageFlag($imapConnection, 1, $numMessages, "Deleted");
   expungeBox($imapConnection, $mailbox);

   // remove subfolders
   for ($i = 0; $i < count($folders); $i++) {
      if (($folders[$i] == "NOPE") || ($folders[$i] == "$trash_folder")) {
      } else {
         $thisfolder = getFolderNameMinusINBOX($folders[$i]);
         $thisfolder = "user.$username.$thisfolder";
         removeFolder($imapConnection, $thisfolder);
      }
   }

   selectMailbox($imapConnection, $trash_folder, $numMessages);
   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, $mailbox);
   messages_deleted_message($trash_folder, $sort, $startMessage);
   fputs($imapConnection, "1 logout");
?>
