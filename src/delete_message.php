<?php

/**
 * delete_message.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Deletes a meesage from the IMAP server
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/display_messages.php');
require_once('../functions/imap.php');

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

sqimap_mailbox_select($imapConnection, $mailbox);

sqimap_messages_delete($imapConnection, $message, $message, $mailbox);
if ($auto_expunge) {
    sqimap_mailbox_expunge($imapConnection, $mailbox, true);
}
if (!isset($saved_draft)) {
    $saved_draft = '';
}

if (!isset($mail_sent)) {
    $mail_sent = '';
}

$location = get_location();

if (isset($where) && isset($what)) {
    header("Location: $location/search.php?where=" . urlencode($where) .
           '&what=' . urlencode($what) . '&mailbox=' . urlencode($mailbox));
} else {
    if (!empty($saved_draft) || !empty($mail_sent)) {
          header("Location: $location/compose.php?mail_sent=$mail_sent&saved_draft=$saved_draft");
    }
    else {
        header("Location: $location/right_main.php?sort=$sort&startMessage=$startMessage&mailbox=" .
               urlencode($mailbox));
    }
}

sqimap_logout($imapConnection);

?>
