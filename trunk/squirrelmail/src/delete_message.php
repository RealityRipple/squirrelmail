<HTML><BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages, $imapServerAddress);

   displayPageHeader($mailbox);

   deleteMessages($imapConnection, $message, $message, $numMessages, $trash_folder, $move_to_trash, $auto_expunge, $mailbox);
   messages_deleted_message($mailbox, $sort, $startMessage);
?>
</BODY></HTML>