<?php

/**
 * folders_create.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Creates folders on the IMAP server.
 * Called from folders.php
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/display_messages.php');

/* get globals we may need */

$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];
$folder_name = $_POST['folder_name'];
$subfolder = $_POST['subfolder'];
if (isset($_POST['contain_subs'])) {
    $contain_subs = $_POST['contain_subs'];
}

/* end of get globals */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

$folder_name = trim($folder_name);

if (strpos($folder_name, "\"") || strpos($folder_name, "\\") ||
    strpos($folder_name, "'") || strpos($folder_name, "$delimiter") ||
    ($folder_name == '')) {
    displayPageHeader($color, 'None');
    echo "<html><body bgcolor=$color[4]>";
    plain_error_message(_("Illegal folder name.  Please select a different name.")."<BR><A HREF=\"../src/folders.php\">"._("Click here to go back")."</A>.", $color);
    sqimap_logout($imapConnection);
    exit;
}

if (isset($contain_subs) && $contain_subs ) {
    $folder_name = "$folder_name$delimiter";
}

if ($folder_prefix && (substr($folder_prefix, -1) != $delimiter)) {
    $folder_prefix = $folder_prefix . $delimiter;
}
if ($folder_prefix && (substr($subfolder, 0, strlen($folder_prefix)) != $folder_prefix)){
    $subfolder_orig = $subfolder;
    $subfolder = $folder_prefix . $subfolder;
} else {
    $subfolder_orig = $subfolder;
}

if (trim($subfolder_orig) == '') {
    sqimap_mailbox_create ($imapConnection, $folder_prefix.$folder_name, '');
} else {
    sqimap_mailbox_create ($imapConnection, $subfolder.$delimiter.$folder_name, '');
}

$location = get_location();
header ("Location: $location/folders.php?success=create");
sqimap_logout($imapConnection);
?>
