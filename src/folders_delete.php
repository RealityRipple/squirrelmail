<?php

/**
 * folders_delete.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Deletes folders from the IMAP server. 
 * Called from the folders.php
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/tree.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/html.php');

/*
 *  Incoming values:
 *     $mailbox - selected mailbox from the form
 */

/* globals */
$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];

$mailbox = $_POST['mailbox'];

/* end globals */

if ($mailbox == '') {
    displayPageHeader($color, 'None');

    plain_error_message(_("You have not selected a folder to delete. Please do so.").
	'<BR><A HREF="../src/folders.php">'._("Click here to go back").'</A>.', $color);
    exit;
}

if (isset($_POST['backingout'])) {
    $location = get_location();
    header ("Location: $location/folders.php");
    exit;
}

if(!isset($_POST['confirmed'])) {
    displayPageHeader($color, 'None');

    echo '<br>' .
        html_tag( 'table', '', 'center', '', 'width="95%" border="0"' ) .
        html_tag( 'tr',
            html_tag( 'td', '<b>' . _("Delete Folder") . '</b>', 'center', $color[0] )
        ) .
        html_tag( 'tr' ) .
        html_tag( 'td', '', 'center', $color[4] ) .
        sprintf(_("Are you sure you want to delete %s?"), $mailbox).
        '<FORM ACTION="folders_delete.php" METHOD="POST"><p>'.

        '<INPUT TYPE=HIDDEN NAME="mailbox" VALUE="'.$mailbox."\">\n" .
        '<INPUT TYPE=SUBMIT NAME="confirmed" VALUE="'._("Yes")."\">\n".
        '<INPUT TYPE=SUBMIT NAME="backingout" VALUE="'._("No")."\">\n".
        '</p></FORM><BR></td></tr></table>';

    exit;
}

$imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

$boxes = sqimap_mailbox_list ($imap_stream);
$numboxes = count($boxes);

global $delete_folder;

if (substr($mailbox, -1) == $delimiter)
    $mailbox_no_dm = substr($mailbox, 0, strlen($mailbox) - 1);
else
    $mailbox_no_dm = $mailbox;

/** lets see if we CAN move folders to the trash.. otherwise,
    ** just delete them **/

/* Courier IMAP doesn't like subfolders of Trash
 * If global options say we can't move it into Trash
 * If it's already a subfolder of trash, we'll have to delete it */
if (strtolower($imap_server_type) == 'courier' || 
    (isset($delete_folder) && $delete_folder) ||
    eregi('^'.$trash_folder.'.+', $mailbox) )
{
    $can_move_to_trash = FALSE;
}

/* Otherwise, check if trash folder exits and support sub-folders */
else {
    for ($i = 0; $i < $numboxes; $i++) {
        if ($boxes[$i]['unformatted'] == $trash_folder) {
            $can_move_to_trash = !in_array('noinferiors', $boxes[$i]['flags']);
        }
    }
}

/** First create the top node in the tree **/
for ($i = 0; $i < $numboxes; $i++) {
    if (($boxes[$i]['unformatted-dm'] == $mailbox) && (strlen($boxes[$i]['unformatted-dm']) == strlen($mailbox))) {
        $foldersTree[0]['value'] = $mailbox;
        $foldersTree[0]['doIHaveChildren'] = false;
        continue;
    }
}

/* Now create the nodes for subfolders of the parent folder
   You can tell that it is a subfolder by tacking the mailbox delimiter
   on the end of the $mailbox string, and compare to that.  */
for ($i = 0; $i < $numboxes; $i++) {
    if (substr($boxes[$i]['unformatted'], 0, strlen($mailbox_no_dm . $delimiter)) == ($mailbox_no_dm . $delimiter)) {
        addChildNodeToTree($boxes[$i]["unformatted"], $boxes[$i]['unformatted-dm'], $foldersTree);
    }
}

/** Lets start removing the folders and messages **/
if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
    walkTreeInPostOrderCreatingFoldersUnderTrash(0, $imap_stream, $foldersTree, $mailbox);
    walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
} else { /** if they do NOT wish to move messages to the trash (or cannot)**/
    walkTreeInPreOrderDeleteFolders(0, $imap_stream, $foldersTree);
}

/** Log out this session **/
sqimap_logout($imap_stream);

$location = get_location();
header ("Location: $location/folders.php?success=delete");

?>
