<?
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");

   include("../src/load_prefs.php");

   echo "<HTML>";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, "None");  
   
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);
   $boxes = sqimap_mailbox_list ($imapConnection);
   $dm = sqimap_get_delimiter($imapConnection);

   /** lets see if we CAN move folders to the trash.. otherwise, just delete them **/
   for ($i = 0; $i < count($boxes[$i]["unformatted"]); $i++) {
      if ($boxes[$i]["unformatted"] == $trash_folder) {
         $can_move_to_trash = true;
         for ($i = 0; $i < count($tmpflags); $i++) {
            if (strtolower($tmpflags[$i]) == "noinferiors")
               $can_move_to_trash = false;
         }
      }
   }

   /** Lets start removing the folders and messages **/
   if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
      /** Creates the subfolders under $trash_folder **/
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["unformatted"] == $mailbox) ||
             (substr($boxes[$i]["unformatted"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
            $folderWithoutINBOX = getFolderNameMinusINBOX($boxes[$i]["unformatted"], $dm);
            $flags = getMailboxFlags($imapConnection, $boxes[$i]["raw"]);
            for ($b = 0; $b < count($flags); $b++) {
               $type = $flags[$b];
            }
            createFolder($imapConnection, "$trash_folder" . $dm . "$folderWithoutINBOX", $type);
         }
      }
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["unformatted"] == $mailbox) ||
             (substr($boxes[$i]["unformatted"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
            sqimap_mailbox_create($imapConnection, $boxes[$i]["unformatted"], $numMessages);
            $folder = $boxes[$i]["unformatted"];

            if ($numMessages > 0)
               $success = sqimap_messages_copy($imapConnection, 1, $folder);
            else
               $success = true;

            if ($success == true)
               sqimap_mailbox_delete($imapConnection, $boxes[$i]["unformatted"]);
            if ($auto_expunge)
               sqimap_mailbox_expunge($imapConnection, $mailbox);
         }
      }
   } else { /** if they do NOT wish to move messages to the trash (or cannot)**/
      fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
      $data = sqimap_read_data($imapConnection, "1", false, $response, $message);
      while (substr($data[0], strpos($data[0], " ")+1, 4) == "LIST") {
         for ($i = 0; $i < count($boxes); $i++) {
            if (($boxes[$i]["unformatted"] == $mailbox) ||
                (substr($boxes[$i]["unformatted"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
               sqimap_mailbox_delete($imapConnection, $boxes[$i]["unformatted"], $dm);
            }
         }
         if ($auto_expunge)
            sqimap_mailbox_expunge($imapConnection, $mailbox);
         fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
         $data = sqimap_read_data($imapConnection , "1", false, $response, $message);
      }
   }

   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<FONT FACE=\"Arial,Helvetica\">";
   echo "<BR><BR><BR><CENTER><B>";
   echo _("Folder Deleted!");
   echo "</B><BR><BR>";
   echo _("The folder has been successfully deleted.");
   echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Click here");
   echo "</A> ";
   echo _("to continue.");
   echo "</CENTER></FONT>"; 
   
   echo "</BODY></HTML>";
?>
