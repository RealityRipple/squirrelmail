<?php

/**
 * delete_message.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Deletes a meesage from the IMAP server
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');

/* get globals */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('message', $message, SQ_FORM);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

sqgetGlobalVar('bypass_trash', $bypass_trash, SQ_FORM);

global $data_dir;
/* end globals */

/* get $compose_new_win */
$compose_new_win=getPref($data_dir,$username,'compose_new_win',0);

/**
 * Script is used when draft is saved again or sent.
 * browser should be redirected to compose.php (compose_new_win=1)
 * or right_main.php
 * Problem: drafts folder listing is not refreshed when 
 * compose_new_win=1.
 */
if (sqGetGlobalVar('saved_draft', $tmp, SQ_GET)) {
    $saved_draft = urlencode($tmp);
}
if (sqGetGlobalVar('mail_sent', $tmp, SQ_GET)) {
    $mail_sent = urlencode($tmp);
}
/**
 * Script is used in search page.
 * browser should be redirected to search.php
 * Is it really used in search page?
 */
if (sqGetGlobalVar('where', $tmp, SQ_FORM)) {
    $where = urlencode($tmp);
}
if (sqGetGlobalVar('what', $tmp, SQ_FORM)) {
    $what = urlencode($tmp);
}
/**
 * FIXME: which part of code uses it?
 */
if (sqGetGlobalVar('sort', $tmp, SQ_FORM)) {
    $sort = (int) $tmp;
}
if (sqGetGlobalVar('startMessage', $tmp, SQ_FORM)) {
    $startMessage = (int) $tmp;
}

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

// FIXME: unchecked use of variables.
sqimap_mailbox_select($imapConnection, $mailbox);

// FIXME: unchecked use of variables.
sqimap_messages_delete($imapConnection, $message, $message, $mailbox,$bypass_trash);
if ($auto_expunge) {
    sqimap_mailbox_expunge($imapConnection, $mailbox, true);
}

$location = get_location();

/**
 * FIXME: rg=on problems with $saved_drafts, $sent_mail, $where and $what
 * FIXME: current version of squirrelmail contains only two 
 * delete_message.php calls. with saved_draft=yes (compose.php 
 * around line 360) and with mail_sent=yes (compose.php around line 434)
 */
if (isset($saved_draft)) {
    // process resumed and again saved draft
    if ($compose_new_win == '1') {
        header("Location: $location/compose.php?saved_draft=yes");
    } else {
        $draft_message = _("Draft Saved");
        header("Location: $location/right_main.php?mailbox=" . urlencode($draft_folder) .
               "&startMessage=1&note=".urlencode($draft_message));
    }
} elseif (isset($mail_sent)) {
    // process resumed and then sent draft
    if ($compose_new_win == '1') {
        header("Location: $location/compose.php?mail_sent=yes");
    } else {
        $draft_message = _("Your Message has been sent.");
        header("Location: $location/right_main.php?mailbox=" . urlencode($draft_folder) .
               "&startMessage=1&note=".urlencode($draft_message));
    }
} elseif (isset($where) && isset($what)) {
    // FIXME: I suspect that part of code is obsolete
    header("Location: $location/search.php?where=" . $where .
           '&what=' . $what . '&mailbox=' . urlencode($mailbox));
} else {
    header("Location: $location/right_main.php?startMessage=$startMessage&mailbox=" .
       urlencode($mailbox));
}
sqimap_logout($imapConnection);
?>