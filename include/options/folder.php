<?php

/**
 * options_folder.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays all options relating to folders
 *
 * @version $Id$
 * @package squirrelmail
 */

/** SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/imap_general.php');

/* Define the group constants for the folder options page. */
define('SMOPT_GRP_SPCFOLDER', 0);
define('SMOPT_GRP_FOLDERLIST', 1);
define('SMOPT_GRP_FOLDERSELECT', 2);

/**
 * This function builds an array with all the information about
 * the options available to the user, and returns it. The options
 * are grouped by the groups in which they are displayed.
 * For each option, the following information is stored:
 * - name: the internal (variable) name
 * - caption: the description of the option in the UI
 * - type: one of SMOPT_TYPE_*
 * - refresh: one of SMOPT_REFRESH_*
 * - size: one of SMOPT_SIZE_*
 * - save: the name of a function to call when saving this option
 * @return array all option information
 */
function load_optpage_data_folder() {
    global $username, $key, $imapServerAddress, $imapPort;
    global $folder_prefix, $default_folder_prefix, $show_prefix_option;

    /* Get some imap data we need later. */
    $imapConnection =
        sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
    $boxes = sqimap_mailbox_list($imapConnection);

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_SPCFOLDER] = _("Special Folder Options");
    $optvals[SMOPT_GRP_SPCFOLDER] = array();

    if (!isset($folder_prefix)) { $folder_prefix = $default_folder_prefix; }
    if ($show_prefix_option) {
        $optvals[SMOPT_GRP_SPCFOLDER][] = array(
            'name'    => 'folder_prefix',
            'caption' => _("Folder Path"),
            'type'    => SMOPT_TYPE_STRING,
            'refresh' => SMOPT_REFRESH_FOLDERLIST,
            'size'    => SMOPT_SIZE_LARGE
        );
    }

    $trash_folder_values = array(SMPREF_NONE => '[ '._("Do not use Trash").' ]',
                                 'whatever'  => $boxes);
    $optvals[SMOPT_GRP_SPCFOLDER][] = array(
        'name'    => 'trash_folder',
        'caption' => _("Trash Folder"),
        'type'    => SMOPT_TYPE_FLDRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $trash_folder_values,
        'save'    => 'save_option_trash_folder'
    );

    $draft_folder_values = array(SMPREF_NONE => '[ '._("Do not use Drafts").' ]',
                                 'whatever'  => $boxes);
    $optvals[SMOPT_GRP_SPCFOLDER][] = array(
        'name'    => 'draft_folder',
        'caption' => _("Draft Folder"),
        'type'    => SMOPT_TYPE_FLDRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $draft_folder_values,
        'save'    => 'save_option_draft_folder'
    );

    $sent_folder_values = array(SMPREF_NONE => '[ '._("Do not use Sent").' ]',
                                'whatever'  => $boxes);
    $optvals[SMOPT_GRP_SPCFOLDER][] = array(
        'name'    => 'sent_folder',
        'caption' => _("Sent Folder"),
        'type'    => SMOPT_TYPE_FLDRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $sent_folder_values,
        'save'    => 'save_option_sent_folder'
    );

    $optvals[SMOPT_GRP_SPCFOLDER][] = array(
        'name'    => 'save_reply_with_orig',
        'caption' => _("Save Replies with Original Message"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_FOLDERLIST
    );

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_FOLDERLIST] = _("Folder List Options");
    $optvals[SMOPT_GRP_FOLDERLIST] = array();

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'location_of_bar',
        'caption' => _("Location of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_LOC_LEFT  => _("Left"),
                           SMPREF_LOC_RIGHT => _("Right"))
    );

    $left_size_values = array();
    for ($lsv = 100; $lsv <= 300; $lsv += 10) {
        $left_size_values[$lsv] = "$lsv " . _("pixels");
    }
    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'left_size',
        'caption' => _("Width of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $left_size_values
    );

    $left_refresh_values = array(SMPREF_NONE => _("Never"));
    foreach (array(30,60,120,180,300,600,1200) as $lr_val) {
        if ($lr_val < 60) {
            $left_refresh_values[$lr_val] = "$lr_val " . _("Seconds");
        } else {
            $left_refresh_values[$lr_val] = sprintf(ngettext("%d Minute","%d Minutes",($lr_val/60)),($lr_val/60));
        }
    }
    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'left_refresh',
        'caption' => _("Auto Refresh Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $left_refresh_values
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'unseen_notify',
        'caption' => _("Enable Unread Message Notification"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_UNSEEN_NONE  => _("No Notification"),
                           SMPREF_UNSEEN_INBOX => _("Only INBOX"),
                           SMPREF_UNSEEN_ALL   => _("All Folders"))
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'unseen_type',
        'caption' => _("Unread Message Notification Type"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_UNSEEN_ONLY  => _("Only Unseen"),
                           SMPREF_UNSEEN_TOTAL => _("Unseen and Total"))
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'collapse_folders',
        'caption' => _("Enable Collapsable Folders"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_FOLDERLIST
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'unseen_cum',
        'caption' => _("Enable Cumulative Unread Message Notification"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_FOLDERLIST
    );


    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'date_format',
        'caption' => _("Show Clock on Folders Panel"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array( '0' => _("International date and time"),
                            '1' => _("American date and time"),
                            '2' => _("European date and time"),
                            '3' => _("Show weekday and time"),
                            '4' => _("Show time with seconds"),
                            '5' => _("Show time"),
                            '6' => _("No Clock")),
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'search_memory',
        'caption' => _("Memory Search"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array( 0 => _("Disabled"),
                            1 => '1',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                            5 => '5',
                            6 => '6',
                            7 => '7',
                            8 => '8',
                            9 => '9')
    );

    $optvals[SMOPT_GRP_FOLDERLIST][] = array(
        'name'    => 'show_only_subscribed_folders',
        'caption' => _("Show only subscribed folders"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_FOLDERLIST
    );

    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_FOLDERSELECT] = _("Folder Selection Options");
    $optvals[SMOPT_GRP_FOLDERSELECT] = array();

    $delim = sqimap_get_delimiter($imapConnection);
    $optvals[SMOPT_GRP_FOLDERSELECT][] = array(
        'name'    => 'mailbox_select_style',
        'caption' => _("Selection List Style"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array( 0 => _("Long: ") . '"' . _("Folder") . $delim . _("Subfolder") . '"',
                            1 => _("Indented: ") .  '"&nbsp;&nbsp;&nbsp;&nbsp;' . _("Subfolder") . '"',
                            2 => _("Delimited: ") . '".&nbsp;' . _("Subfolder") . '"'),
        'htmlencoded' => true
    );

    /* Assemble all this together and return it as our result. */
    $result = array(
        'grps' => $optgrps,
        'vals' => $optvals
    );
    sqimap_logout($imapConnection);
    return ($result);
}

/******************************************************************/
/** Define any specialized save functions for this option page. ***/
/******************************************************************/

/**
 * Saves the trash folder option.
 */
function save_option_trash_folder($option) {
    global $data_dir, $username;

    /* Set move to trash on or off. */
    $trash_on = ($option->new_value == SMPREF_NONE ? SMPREF_OFF : SMPREF_ON);
    setPref($data_dir, $username, 'move_to_trash', $trash_on);

    /* Now just save the option as normal. */
    save_option($option);
}

/**
 * Saves the sent folder option.
 */
function save_option_sent_folder($option) {
    global $data_dir, $username;

    /* Set move to sent on or off. */
    $sent_on = ($option->new_value == SMPREF_NONE ? SMPREF_OFF : SMPREF_ON);
    setPref($data_dir, $username, 'move_to_sent', $sent_on);

    /* Now just save the option as normal. */
    save_option($option);
}

/**
 * Saves the draft folder option.
 */
function save_option_draft_folder($option) {
    global $data_dir, $username;

    /* Set move to draft on or off. */
    $draft_on = ($option->new_value == SMPREF_NONE ? SMPREF_OFF : SMPREF_ON);
    setPref($data_dir, $username, 'save_as_draft', $draft_on);

    /* Now just save the option as normal. */
    save_option($option);
}

?>
