<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/display_messages.php");
   include("../functions/imap.php");

   include("../src/load_prefs.php");

   echo "<HTML><BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);

   displayPageHeader($color, $mailbox);

   sqimap_message_delete($imapConnection, $message, $message, $mailbox);
   messages_deleted_message($mailbox, $sort, $startMessage, $color);
?>
</BODY></HTML>
