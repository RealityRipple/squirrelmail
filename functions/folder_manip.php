<?php

/**
 * folder_manip.php
 *
 * Functions for IMAP folder manipulation:
 * (un)subscribe, create, rename, delete.
 *
 * @author Thijs Kinkhorst <kink at squirrelmail.org>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @see folders.php
 */


/**
 * Helper function for the functions below; checks if the user entered
 * folder name is valid according to the IMAP standard. If not, it
 * bails out with an error and cleanly terminates the IMAP connection.
 */
function folders_checkname($imapConnection, $folder_name, $delimiter)
{
    if (substr_count($folder_name, '"') || substr_count($folder_name, "\\") ||
        substr_count($folder_name, $delimiter) || ($folder_name == '')) {

        global $color, $oTemplate;
        error_box(_("Illegal folder name.") . "<br />\n" .
                sprintf(_("The name may not contain any of the following: %s"), '<tt>" \\ '.$delimiter.'</tt>')
                . "<br />\n" .
                _("Please select a different name.").
                '<br /><a href="folders.php">'.
                _("Click here to go back") . '</a>.');

        sqimap_logout($imapConnection);
        $oTemplate->display('footer.tpl');
        exit;
    }
}

/**
 * Called from folders.php to create a new folder.
 * @param stream $imapConnection imap connection resource
 * @param string $delimiter delimiter
 * @param string $folder_name new folder name
 * @param string $subfolder folder that stores new folder
 * @param string $contain_subs if not empty, creates folder that can store subfolders
 * @since 1.5.1
 */
function folders_create ($imapConnection, $delimiter, $folder_name, $subfolder, $contain_subs)
{
    folders_checkname($imapConnection, $folder_name, $delimiter);

    global $folder_prefix;

    $folder_name = imap_utf7_encode_local($folder_name);

    if ( ! empty($contain_subs) ) {
        $folder_type = 'noselect';
    } else {
        $folder_type = '';
    }

    if ($folder_prefix && (substr($folder_prefix, -1) != $delimiter)) {
        $folder_prefix = $folder_prefix . $delimiter;
    }
    if ($folder_prefix && (substr($subfolder, 0, strlen($folder_prefix)) != $folder_prefix)) {
        $subfolder_orig = $subfolder;
        $subfolder = $folder_prefix . $subfolder;
    } else {
        $subfolder_orig = $subfolder;
    }

    if (trim($subfolder_orig) == '') {
        sqimap_mailbox_create ($imapConnection, $folder_prefix.$folder_name, $folder_type);
    } else {
        sqimap_mailbox_create ($imapConnection, $subfolder.$delimiter.$folder_name, $folder_type);
    }

    return;
}

/**
 * Called from folders.php, given a folder name, ask the user what this
 * folder should be renamed to.
 */
function folders_rename_getname ($imapConnection, $delimiter, $old) {
    global $color, $default_folder_prefix, $oTemplate;

    if ( $old == '' ) {
        plain_error_message(_("You have not selected a folder to rename. Please do so.").
            '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
        sqimap_logout($imapConnection);
        $oTemplate->display('footer.tpl');
        exit;
    }

    if (substr($old, strlen($old) - strlen($delimiter)) == $delimiter) {
        $isfolder = TRUE;
        $old = substr($old, 0, strlen($old) - 1);
    } else {
        $isfolder = FALSE;
    }

    $old = imap_utf7_decode_local($old);

    if (strpos($old, $delimiter)) {
        $old_name = substr($old, strrpos($old, $delimiter)+1);
        // hide default prefix (INBOX., mail/ or other)
        $quoted_prefix=preg_quote($default_folder_prefix,'/');
        $prefix_length=(preg_match("/^$quoted_prefix/",$old) ? strlen($default_folder_prefix) : 0);
        if ($prefix_length>strrpos($old, $delimiter)) {
            $old_parent = '';
        } else {
            $old_parent = substr($old, $prefix_length, (strrpos($old, $delimiter)-$prefix_length))
                . ' ' . $delimiter;
        }
    } else {
        $old_name = $old;
        $old_parent = '';
    }
    
    sqimap_logout($imapConnection);

    $oTemplate->assign('dialog_type', 'rename');
    $oTemplate->assign('parent_folder', sm_encode_html_special_chars($old_parent));
    $oTemplate->assign('current_full_name', sm_encode_html_special_chars($old));
    $oTemplate->assign('current_folder_name', sm_encode_html_special_chars($old_name));
    $oTemplate->assign('is_folder', $isfolder);
    
    $oTemplate->display('folder_manip_dialog.tpl');
    $oTemplate->display('footer.tpl');

    exit;
}

/**
 * Given an old and new folder name, renames the folder.
 */
function folders_rename_do($imapConnection, $delimiter, $orig, $old_name, $new_name)
{
    folders_checkname($imapConnection, $new_name, $delimiter);

    $orig = imap_utf7_encode_local($orig);
    $old_name = imap_utf7_encode_local($old_name);
    $new_name = imap_utf7_encode_local($new_name);

    if ($old_name != $new_name) {

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

    }

    return;
}

/**
 * Presents a confirmation dialog to the user asking whether they're
 * sure they want to delete this folder.
 */
function folders_delete_ask ($imapConnection, $folder_name)
{
    global $color, $default_folder_prefix, $oTemplate;

    if ($folder_name == '') {
        plain_error_message(_("You have not selected a folder to delete. Please do so.").
            '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
        sqimap_logout($imapConnection);
        $oTemplate->display('footer.tpl');
        exit;
    }

    // hide default folder prefix (INBOX., mail/ or other)
    $visible_folder_name = imap_utf7_decode_local($folder_name);
    $quoted_prefix = preg_quote($default_folder_prefix,'/');
    $prefix_length = (preg_match("/^$quoted_prefix/",$visible_folder_name) ? strlen($default_folder_prefix) : 0);
    $visible_folder_name = substr($visible_folder_name,$prefix_length);

    sqimap_logout($imapConnection);

    $oTemplate->assign('dialog_type', 'delete');
    $oTemplate->assign('folder_name', sm_encode_html_special_chars($folder_name));
    $oTemplate->assign('visible_folder_name', sm_encode_html_special_chars($visible_folder_name));
    
    $oTemplate->display('folder_manip_dialog.tpl');
    $oTemplate->display('footer.tpl');

    exit;
}

/**
 * Given a folder, moves it to trash (and all subfolders of it too).
 */
function folders_delete_do ($imapConnection, $delimiter, $folder_name)
{
    include(SM_PATH . 'functions/tree.php');

    $boxes = sqimap_mailbox_list ($imapConnection);

    global $delete_folder, $imap_server_type, $trash_folder, $move_to_trash;

    if (substr($folder_name, -1) == $delimiter) {
        $folder_name_no_dm = substr($folder_name, 0, strlen($folder_name) - 1);
    } else {
        $folder_name_no_dm = $folder_name;
    }

    /** lets see if we CAN move folders to the trash.. otherwise,
        ** just delete them **/
    if ($delete_folder || preg_match('/^' . preg_quote($trash_folder, '/') . '.+/i', $folder_name) ) {
        $can_move_to_trash = FALSE;
    } else {
    /* Otherwise, check if trash folder exits and support sub-folders */
        foreach($boxes as $box) {
            if ($box['unformatted'] == $trash_folder) {
                $can_move_to_trash = !in_array('noinferiors', $box['flags']);
            }
        }
    }

    /** First create the top node in the tree **/
    foreach($boxes as $box) {
        if (($box['unformatted-dm'] == $folder_name) && (strlen($box['unformatted-dm']) == strlen($folder_name))) {
            $foldersTree[0]['value'] = $folder_name;
            $foldersTree[0]['doIHaveChildren'] = false;
            continue;
        }
    }

    /* Now create the nodes for subfolders of the parent folder
       You can tell that it is a subfolder by tacking the mailbox delimiter
       on the end of the $folder_name string, and compare to that.  */
    foreach($boxes as $box) {
        if (substr($box['unformatted'], 0, strlen($folder_name_no_dm . $delimiter)) == ($folder_name_no_dm . $delimiter)) {
            addChildNodeToTree($box['unformatted'], $box['unformatted-dm'], $foldersTree);
        }
    }

    /** Lets start removing the folders and messages **/
    if (($move_to_trash == true) && ($can_move_to_trash == true)) { /** if they wish to move messages to the trash **/
        walkTreeInPostOrderCreatingFoldersUnderTrash(0, $imapConnection, $foldersTree, $folder_name);
        walkTreeInPreOrderDeleteFolders(0, $imapConnection, $foldersTree);
    } else { /** if they do NOT wish to move messages to the trash (or cannot)**/
        walkTreeInPreOrderDeleteFolders(0, $imapConnection, $foldersTree);
    }

    return;
}

/**
 * Given an array of folder_names, subscribes to each of them.
 */
function folders_subscribe($imapConnection, $folder_names)
{
    global $color, $oTemplate;

    if (count($folder_names) == 0 || $folder_names[0] == '') {
        plain_error_message(_("You have not selected a folder to subscribe. Please do so.").
            '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
        sqimap_logout($imapConnection);
        $oTemplate->display('footer.tpl');
        exit;
    }

    global $no_list_for_subscribe, $imap_server_type;

    if($no_list_for_subscribe && $imap_server_type == 'cyrus') {
        /* Cyrus, atleast, does not typically allow subscription to
         * nonexistent folders (this is an optional part of IMAP),
         * lets catch it here and report back cleanly. */
        if(!sqimap_mailbox_exists($imapConnection, $folder_names[0])) {
            plain_error_message(_("Subscription Unsuccessful - Folder does not exist.").
                '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
            sqimap_logout($imapConnection);
            exit;

        }
    }
    foreach ( $folder_names as $folder_name ) {
        sqimap_subscribe ($imapConnection, $folder_name);
    }

    return;
}

/**
 * Given a list of folder names, unsubscribes from each of them.
 */
function folders_unsubscribe($imapConnection, $folder_names)
{
    global $color, $oTemplate;

    if (count($folder_names) == 0 || $folder_names[0] == '') {
        plain_error_message(_("You have not selected a folder to unsubscribe. Please do so.").
            '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
        sqimap_logout($imapConnection);
        $oTemplate->display('footer.tpl');
        exit;
    }

    foreach ( $folder_names as $folder_name ) {
        sqimap_unsubscribe ($imapConnection, $folder_name);
    }

    return;
}
