<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $orig, $numMessages);
   getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted);

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

   /** Log out this session **/
   fputs($imapConnection, "1 logout");

   echo "<BR><BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>Return</A>";
?>


