<?
   include("../config/config.php");
   include("../functions/mailbox.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress, 0);
   selectMailbox($imapConnection, $mailbox, $numMessages, $imapServerAddress);

   displayPageHeader($color, $mailbox);

   deleteMessages($imapConnection, $message, $message, $numMessages, $trash_folder, $move_to_trash, $auto_expunge, $mailbox);
   messages_deleted_message($mailbox, $sort, $startMessage, $color);
?>
</BODY></HTML>
