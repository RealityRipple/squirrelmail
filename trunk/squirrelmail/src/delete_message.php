<?php
   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_message_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");

   include("../src/load_prefs.php");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   sqimap_mailbox_select($imapConnection, $mailbox);

   displayPageHeader($color, $mailbox);

   sqimap_messages_delete($imapConnection, $message, $message, $mailbox);
   if ($auto_expunge)
      sqimap_mailbox_expunge($imapConnection, $mailbox);

   messages_deleted_message($mailbox, $sort, $startMessage, $color);
?>
</BODY></HTML>
