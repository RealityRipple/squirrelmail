<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   include("../src/load_prefs.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress, 0);
   $dm = findMailboxDelimeter($imapConnection);

   if (strpos($orig, $dm))
      $old_dir = substr($orig, 0, strrpos($orig, $dm));
   else
      $old_dir = "";

   if ($old_dir != "")
      $newone = "$old_dir$dm$new_name";
   else
      $newone = "$new_name";

   fputs ($imapConnection, ". RENAME \"$orig\" \"$newone\"\n");
   $data = imapReadData($imapConnection, ".", true, $a, $b);

/*   fputs ($imapConnection, ". RENAME \"$old_name\" \"$mailbox\"\n";

   selectMailbox($imapConnection, $orig, $numMessages);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted, $boxesRaw);

   $mailbox = "$subfolder.$new_name";
   $old_name = substr($orig, strrpos($orig, ".")+1, strlen($orig));
   $old_parent = substr($orig, 0, strrpos($orig, "."));

   for ($i = 0; $i < count($boxesUnformatted); $i++) {
      if (substr($boxesUnformatted[$i], 0, strlen($orig)) == $orig) {
         $after = substr($boxesUnformatted[$i], strlen($orig)+1, strlen($boxesUnformatted[$i]));
         selectMailbox($imapConnection, $boxesUnformatted[$i], $numMessages);
         if (strlen($after) > 0) {
            createFolder($imapConnection, "$mailbox.$after");
            if ($numMessages > 0)
               $success = copyMessages($imapConnection, 1, $numMessages, "$mailbox.$after");
            else
               $success = true;

            if ($success == true)
               removeFolder($imapConnection, "$boxesUnformatted[$i]");
         }
         else {
            createFolder($imapConnection, "$mailbox");
            if ($numMessages > 0)
               $success = copyMessages($imapConnection, 1, $numMessages, "$mailbox");
            else
               $success = true;

            if ($success == true)
               removeFolder($imapConnection, "$boxesUnformatted[$i]");
         }
      }
   }
*/
   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
   echo _("Return");
   echo "</A>";
   echo "</BODY></HTML>";
?>


