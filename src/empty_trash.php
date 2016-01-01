<?php

/**
 * empty_trash.php
 *
 * Handles deleting messages from the trash folder without
 * deleting subfolders.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the empty_trash page */
define('PAGE_NAME', 'empty_trash');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

require(SM_PATH . 'functions/imap_general.php');
require(SM_PATH . 'functions/imap_messages.php');
require(SM_PATH . 'functions/tree.php');

/* get those globals */

sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);

/* finished globals */

// first do a security check
sqgetGlobalVar('smtoken', $submitted_token, SQ_GET, '');
sm_validate_security_token($submitted_token, -1, TRUE);

global $imap_stream_options; // in case not defined in config
$imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);

$mailbox = $trash_folder;
$boxes = sqimap_mailbox_list($imap_stream);

/*
 * According to RFC2060, a DELETE command should NOT remove inferiors (sub folders)
 *    so lets go through the list of subfolders and remove them before removing the
 *    parent.
 */

/** First create the top node in the tree **/
$numboxes = count($boxes);
for ($i = 0; $i < $numboxes; $i++) {
    if (($boxes[$i]['unformatted'] == $mailbox) && (strlen($boxes[$i]['unformatted']) == strlen($mailbox))) {
        $foldersTree[0]['value'] = $mailbox;
        $foldersTree[0]['doIHaveChildren'] = false;
        continue;
    }
}
/*
 * Now create the nodes for subfolders of the parent folder
 * You can tell that it is a subfolder by tacking the mailbox delimiter
 *    on the end of the $mailbox string, and compare to that.
 */
$j = 0;
for ($i = 0; $i < $numboxes; $i++) {
    if (substr($boxes[$i]['unformatted'], 0, strlen($mailbox . $delimiter)) == ($mailbox . $delimiter)) {
        addChildNodeToTree($boxes[$i]['unformatted'], $boxes[$i]['unformatted-dm'], $foldersTree);
    }
}

// now lets go through the tree and delete the folders
walkTreeInPreOrderEmptyTrash(0, $imap_stream, $foldersTree);
// update mailbox cache
$mailboxes=sqimap_get_mailboxes($imap_stream,true,$show_only_subscribed_folders);
sqimap_logout($imap_stream);

// close session properly before redirecting
session_write_close();

$location = get_location();
header ("Location: $location/left_main.php");

