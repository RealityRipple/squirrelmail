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

   // mark them as deleted
   setMessageFlag($imapConnection, 1, $numMessages, "Deleted");
   expungeBox($imapConnection, $mailbox);

   // Log out this session
   fputs($imapConnection, "1 logout");

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">";
   displayPageHeader($mailbox);

   messages_deleted_message($mailbox, $sort, $startMessage);
?>
