<?php

/**
 * folders.php
 *
 * Handles all interaction between the user and the other folder
 * scripts which do most of the work. Also handles the Special
 * Folders.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/folder_manip.php');
require_once(SM_PATH . 'functions/forms.php');

displayPageHeader($color, 'None');

/* get globals we may need */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('smaction', $action, SQ_POST);

/* end of get globals */

$imapConnection = sqimap_login ($username, false, $imapServerAddress, $imapPort, 0);

/* switch to the right function based on what the user selected */
if ( sqgetGlobalVar('smaction', $action, SQ_POST) ) {

    switch ($action)
    {
        case 'create':
            sqgetGlobalVar('folder_name',  $folder_name,  SQ_POST);
            sqgetGlobalVar('subfolder',    $subfolder,    SQ_POST);
            sqgetGlobalVar('contain_subs', $contain_subs, SQ_POST);
            folders_create($imapConnection, $delimiter, $folder_name, $subfolder, $contain_subs);
            $td_str =  _("Created folder successfully.");
            break;
        case 'rename':
            if ( sqgetGlobalVar('cancelbutton', $dummy, SQ_POST) ) {
                break;
            }
            if ( ! sqgetGlobalVar('new_name', $new_name, SQ_POST) ) {
                sqgetGlobalVar('old_name',    $old_name, SQ_POST);
                folders_rename_getname($imapConnection, $delimiter, $old_name);
            } else {
                sqgetGlobalVar('orig',        $orig,     SQ_POST);
                sqgetGlobalVar('old_name',    $old_name, SQ_POST);
                folders_rename_do($imapConnection, $delimiter, $orig, $old_name, $new_name);
                $td_str =  _("Renamed successfully.");
            }
            break;
        case 'delete':
            if ( sqgetGlobalVar('cancelbutton', $dummy, SQ_POST) ) {
                break;
            }
            sqgetGlobalVar('folder_name',  $folder_name,  SQ_POST);
            if ( sqgetGlobalVar('confirmed', $dummy, SQ_POST) ) {
                folders_delete_do($imapConnection, $delimiter, $folder_name);
                $td_str =  _("Deleted folder successfully.");
            } else {
                folders_delete_ask($imapConnection, $folder_name);
            }
            break;
        case 'subscribe':
            sqgetGlobalVar('folder_names',  $folder_names,  SQ_POST);
            folders_subscribe($imapConnection, $folder_names);
            $td_str =  _("Subscribed successfully.");
            break;
        case 'unsubscribe':
            sqgetGlobalVar('folder_names',  $folder_names,  SQ_POST);
            folders_unsubscribe($imapConnection, $folder_names);
            $td_str =  _("Unsubscribed successfully.");
            break;
        default:
            // TODO: add hook for plugin action processing.
            $td_str = '';
            break;
    }

}

$boxes = sqimap_mailbox_list($imapConnection,true);

/** CREATING FOLDERS **/

$show_selected = array();
$skip_folders = array();
$server_type = strtolower($imap_server_type);

// Special handling for courier
if ( $server_type == 'courier' ) {
    if ( $default_folder_prefix == 'INBOX.' ) {
        // We don't need INBOX, since it is top folder
        array_push($skip_folders, 'INBOX');
    }
} elseif ( $server_type == 'bincimap' ) {
    if ( $default_folder_prefix == 'INBOX/' ) {
        // We don't need INBOX, since it is top folder
        array_push($skip_folders, 'INBOX');
    }
}

if ( $default_sub_of_inbox == false ) {
    $mbx_option_list = '<option selected="selected" value="">[ '._("None")." ]</option>\n";
} else {
    $mbx_option_list = '<option value="">[ '._("None")." ]</option>\n";
    $show_selected = array('inbox');
}

// Call sqimap_mailbox_option_list, using existing connection to IMAP server,
// the arrays of folders to include or skip (assembled above),
// use 'noinferiors' as a mailbox filter to leave out folders that can not contain other folders.
// use the long format to show subfolders in an intelligible way if parent is missing (special folder)
$mbx_option_list .= sqimap_mailbox_option_list($imapConnection, $show_selected, $skip_folders, $boxes, 'noinferiors', true);


/** count special folders **/
foreach ($boxes as $index => $aBoxData) {
    if (isSpecialMailbox($aBoxData['unformatted'],false) &&
        ! in_array($aBoxData['unformatted'],$skip_folders)) {
        $skip_folders[] = $aBoxData['unformatted'];
    }
}

/**
 * Retrieve list of folders when special folders are excluded. Special folders
 * should be unavailable in rename/delete/unsubscribe. Theoretically user can
 * modify form and perform these operations with special folders, but if user
 * manages to delete/rename/unsubscribe special folder by hacking form...
 *
 * If script or program depends on special folder, they should not assume that
 * folder is available.
 *
 * $filtered_folders contains empty string or html formated option list.
 */
$rendel_folder_list = sqimap_mailbox_option_list($imapConnection, 0, $skip_folders, $boxes, NULL, true);


$subbox_option_list = '';

if ($show_only_subscribed_folders && !$no_list_for_subscribe) {
    // FIXME: fix subscription options when top folder is not subscribed and sub folder is subscribed
    // TODO: use checkboxes instead of select options.

    /** SUBSCRIBE TO FOLDERS **/
    $boxes_all = sqimap_mailbox_list_all ($imapConnection);

    // here we filter out all boxes we're already subscribed to,
    // so we keep only the unsubscribed ones.
    foreach ($boxes_all as $box_a) {

	$use_folder = true;
	foreach ( $boxes as $box ) {
	    if ($box_a['unformatted'] == $box['unformatted'] ||
		$box_a['unformatted-dm'] == $folder_prefix ) {
		$use_folder = false;
	    }
	}

	if ($use_folder) {
	    $box_enc  = htmlspecialchars($box_a['unformatted-dm']);
	    $box_disp = htmlspecialchars(imap_utf7_decode_local($box_a['unformatted-disp']));
	    $subbox_option_list .= '<option value="' . $box_enc . '">'.$box_disp."</option>\n";
	}
    }
}

sqimap_logout($imapConnection);

$oTemplate->assign('td_str', @$td_str);
$oTemplate->assign('color', $color);
$oTemplate->assign('mbx_option_list', $mbx_option_list);
$oTemplate->assign('show_contain_subfolders_option', $show_contain_subfolders_option);
$oTemplate->assign('show_only_subscribed_folders', $show_only_subscribed_folders);
$oTemplate->assign('rendel_folder_list', $rendel_folder_list);
$oTemplate->assign('subbox_option_list', $subbox_option_list);
$oTemplate->assign('no_list_for_subscribe', $no_list_for_subscribe);

$oTemplate->display('folder_manip.tpl');

$oTemplate->display('footer.tpl');

