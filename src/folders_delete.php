<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/array.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   getFolderList($imapConnection, $boxes);

   $dm = findMailboxDelimeter($imapConnection);
   /** lets see if we CAN move folders to the trash.. otherwise, just delete them **/
   for ($i = 0; $i < count($boxes[$i]["UNFORMATTED"]); $i++) {
      if ($boxes[$i]["UNFORMATTED"] == $trash_folder)
         $tmp_trash_folder = $boxes[$i]["RAW"];
   }

   $tmpflags = getMailboxFlags($tmp_trash_folder);
   $can_move_to_trash = true;
   for ($i = 0; $i < count($tmpflags); $i++) {
      if (strtolower($tmpflags[$i]) == "noinferiors")
         $can_move_to_trash = false;
   }

   /** Lets start removing the folders and messages **/
   if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
      /** Creates the subfolders under $trash_folder **/
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["UNFORMATTED"] == $mailbox) ||
             (substr($boxes[$i]["UNFORMATTED"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
            $folderWithoutINBOX = getFolderNameMinusINBOX($boxes[$i]["UNFORMATTED"], $dm);
            $flags = getMailboxFlags($boxes[$i]["RAW"]);
            for ($b = 0; $b < count($flags); $b++) {
               $type = $flags[$b];
            }
            createFolder($imapConnection, "$trash_folder" . $dm . "$folderWithoutINBOX", $type);
         }
      }
      for ($i = 0; $i < count($boxes); $i++) {
         if (($boxes[$i]["UNFORMATTED"] == $mailbox) ||
             (substr($boxes[$i]["UNFORMATTED"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
            selectMailbox($imapConnection, $boxes[$i]["UNFORMATTED"], $numMessages);
            $folder = getFolderNameMinusINBOX($boxes[$i]["UNFORMATTED"]);

            if ($numMessages > 0)
               $success = copyMessages($imapConnection, 1, $numMessages, "$trash_folder" . $dm . "$folder");
            else
               $success = true;

            if ($success == true)
               removeFolder($imapConnection, $boxes[$i]["UNFORMATTED"]);
         }
      }
   } else { /** if they do NOT wish to move messages to the trash (or cannot)**/
      fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
      $data = imapReadData($imapConnection , "1", false, $response, $message);
      while (substr($data[0], strpos($data[0], " ")+1, 4) == "LIST") {
         for ($i = 0; $i < count($boxes); $i++) {
            if (($boxes[$i]["UNFORMATTED"] == $mailbox) ||
                (substr($boxes[$i]["UNFORMATTED"], 0, strlen($mailbox . $dm)) == $mailbox . $dm)) {
               removeFolder($imapConnection, $boxes[$i]["UNFORMATTED"]);
            }
         }
         fputs($imapConnection, "1 LIST \"$mailbox\" *\n");
         $data = imapReadData($imapConnection , "1", false, $response, $message);
      }
   }

   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>Return</A>";
   echo "</BODY></HTML>";
?>


