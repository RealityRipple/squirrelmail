<?php

/**
 * folders_rename_do.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Does the actual renaming of files on the IMAP server.
 * Called from the folders.php
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');

global $delimiter, $base_uri;

$new_name = trim($new_name);

if ($old_name <> $new_name) {

    $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

    if (strpos($orig, $delimiter)) {
        $old_dir = substr($orig, 0, strrpos($orig, $delimiter));
    } else {
        $old_dir = '';
    }

    if ($old_dir != '') {
        $newone = $old_dir . $delimiter . $new_name;
    } else {
        $newone = $new_name;
    }

    // Renaming a folder doesn't renames the folder but leaves you unsubscribed
    //    at least on Cyrus IMAP servers.
    if (isset($isfolder)) {
        $newone = $newone.$delimiter;
        $orig = $orig.$delimiter;
    }
    sqimap_mailbox_rename( $imapConnection, $orig, $newone );

    // Log out this session 
    sqimap_logout($imapConnection);

}
header ('Location: ' . $base_uri . 'src/folders.php');
?>
