<?php

/**
 * delete_message.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Deletes a meesage from the IMAP server
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');

/* get globals */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('message', $message, SQ_GET);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
/* end globals */

if (isset($_GET['saved_draft'])) {
    $saved_draft = urlencode($_GET['saved_draft']);
}
if (isset($_GET['mail_sent'])) {
    $mail_sent = urlencode($_GET['mail_sent']);
}
if (isset($_GET['sort'])) {
	$sort = (int) $_GET['sort'];
}

if (isset($_GET['startMessage'])) {
	$startMessage = (int) $_GET['startMessage'];
}

if(isset($_GET['where'])) {
    $where = urlencode($_GET['where']);
}
if(isset($_GET['what'])) {
    $what = urlencode($_GET['what']);
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
    header("Location: $location/search.php?where=" . $where .
           '&what=' . $what . '&mailbox=' . urlencode($mailbox));
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
