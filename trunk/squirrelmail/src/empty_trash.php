<?php

/**
 * empty_trash.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handles deleting messages from the trash folder without
 * deleting subfolders.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/array.php');
require_once(SM_PATH . 'functions/tree.php');

$imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

sqimap_mailbox_list($imap_stream);

$mailbox = $trash_folder;
$boxes = sqimap_mailbox_list($imap_stream);
global $delimiter;

/*
 * According to RFC2060, a DELETE command should NOT remove inferiors (sub folders)
 *    so lets go through the list of subfolders and remove them before removing the
 *    parent.
 */

/** First create the top node in the tree **/
for ($i = 0;$i < count($boxes);$i++) {
    if (($boxes[$i]["unformatted"] == $mailbox) && (strlen($boxes[$i]["unformatted"]) == strlen($mailbox))) {
        $foldersTree[0]["value"] = $mailbox;
        $foldersTree[0]["doIHaveChildren"] = false;
        continue;
    }
}
/*
 * Now create the nodes for subfolders of the parent folder
 * You can tell that it is a subfolder by tacking the mailbox delimiter
 *    on the end of the $mailbox string, and compare to that.
 */
$j = 0;
for ($i = 0;$i < count($boxes);$i++) {
    if (substr($boxes[$i]["unformatted"], 0, strlen($mailbox . $delimiter)) == ($mailbox . $delimiter)) {
        addChildNodeToTree($boxes[$i]["unformatted"], $boxes[$i]["unformatted-dm"], $foldersTree);
    }
}

// now lets go through the tree and delete the folders
walkTreeInPreOrderEmptyTrash(0, $imap_stream, $foldersTree);

$location = get_location();
header ("Location: $location/left_main.php");

sqimap_logout($imap_stream);
?>
