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

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');

$key = $_COOKIE['key'];
$username = $_SESSION['username'];
$onetimepad = $_SESSION['onetimepad'];

$message = $_GET['message'];
$mailbox = $_GET['mailbox'];

if (isset($_GET['saved_draft'])) {
    $saved_draft = $_GET['saved_draft'];
}
if (isset($_GET['mail_sent'])) {
    $mail_sent = $_GET['mail_sent'];
}
$sort = $_GET['sort'];
$startMessage = $_GET['startMessage'];

if(isset($_GET['where'])) {
    $where = $_GET['where'];
}
if(isset($_GET['what'])) {
    $what = $_GET['what'];
}

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
