<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");

   $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
   if (!$imapConnection) {
      echo "Error connecting to IMAP Server.<br>";
      echo "$errorNumber : $errorString<br>";
      exit;
   }
   $serverInfo = fgets($imapConnection, 256);

   // login
   fputs($imapConnection, "1 login $username $key\n");
   $read = fgets($imapConnection, 1024);

   if (strpos($read, "NO")) {
      error_username_password_incorrect();
      exit;
   }

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