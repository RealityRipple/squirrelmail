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
require_once(SM_PATH . 'functions/display_messages.php');

/* globals */
$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];

$orig = $_POST['orig'];
$old_name = $_POST['old_name'];
$new_name = $_POST['new_name'];

/* end globals */

$new_name = trim($new_name);

if (substr_count($new_name, '"') || substr_count($new_name, "\\") ||
    substr_count($new_name, $delimiter) || ($new_name == '')) {
    displayPageHeader($color, 'None');

    plain_error_message(_("Illegal folder name.  Please select a different name.").
        '<BR><A HREF="../src/folders.php">'._("Click here to go back").'</A>.', $color);

    exit;
}

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

    // Renaming a folder doesn't rename the folder but leaves you unsubscribed
    //    at least on Cyrus IMAP servers.
    if (isset($isfolder)) {
        $newone = $newone.$delimiter;
        $orig = $orig.$delimiter;
    }
    sqimap_mailbox_rename( $imapConnection, $orig, $newone );

    // Log out this session 
    sqimap_logout($imapConnection);

}

header ('Location: ' . get_location() . '/folders.php?success=rename');

?>
