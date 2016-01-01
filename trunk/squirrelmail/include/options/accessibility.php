<?php

/**
 * options_accessibility.php
 *
 * Displays all options concerning accessibility features in SquirrelMail.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** Define the group constants for this options page. */
define('SMOPT_GRP_ACCESSKEYS_MENUBAR', 0);
define('SMOPT_GRP_ACCESSKEYS_MAILBOX', 1);
define('SMOPT_GRP_ACCESSKEYS_READ_MESSAGE', 2);
define('SMOPT_GRP_ACCESSKEYS_COMPOSE', 3);
define('SMOPT_GRP_ACCESSKEYS_FOLDER_LIST', 4);
define('SMOPT_GRP_ACCESSKEYS_OPTIONS', 5);

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
function load_optpage_data_accessibility() {

    global $a_to_z;
    $my_a_to_z = array_merge(array('NONE' => _("Not used")), $a_to_z);

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the Access Key Options for the Menubar into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_MENUBAR] = _("Access Keys For Top Menu (All Screens)");
    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_compose',
        'caption' => _("Compose"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_addresses',
        'caption' => _("Addresses"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_folders',
        'caption' => _("Folders"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_options',
        'caption' => _("Options"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_search',
        'caption' => _("Search"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_help',
        'caption' => _("Help"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MENUBAR][] = array(
        'name'    => 'accesskey_menubar_signout',
        'caption' => _("Sign Out"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the Mailbox page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_MAILBOX] = _("Access Keys For Message List Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_previous',
        'caption' => _("Previous"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_next',
        'caption' => _("Next"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_all_paginate',
        'caption' => _("Show All/Paginate"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_thread',
        'caption' => _("Thread View/Unthreaded View"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_flag',
        'caption' => _("Flag"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_unflag',
        'caption' => _("Unflag"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_read',
        'caption' => _("Read"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_unread',
        'caption' => _("Unread"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_forward',
        'caption' => _("Forward"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_delete',
        'caption' => _("Delete"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_expunge',
        'caption' => _("Expunge"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_undelete',
        'caption' => _("Undelete"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_bypass_trash',
        'caption' => _("Bypass Trash"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_move_to',
        'caption' => _("Move To"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_move',
        'caption' => _("Move"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_copy',
        'caption' => _("Copy"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_MAILBOX][] = array(
        'name'    => 'accesskey_mailbox_toggle_selected',
        'caption' => _("Toggle Selected"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the Read Message page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE] = _("Access Keys For Read Message Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_reply',
        'caption' => _("Reply"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_reply_all',
        'caption' => _("Reply All"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_forward',
        'caption' => _("Forward"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_as_attach',
        'caption' => _("As Attachment"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_delete',
        'caption' => _("Delete"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_bypass_trash',
        'caption' => _("Bypass Trash"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_move_to',
        'caption' => _("Move To"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_move',
        'caption' => _("Move"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_copy',
        'caption' => _("Copy"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the Compose page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_COMPOSE] = _("Access Keys For Compose Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_identity',
        'caption' => _("From"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_to',
        'caption' => _("To"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_cc',
        'caption' => _("Cc"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_bcc',
        'caption' => _("Bcc"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_subject',
        'caption' => _("Subject"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_priority',
        'caption' => _("Priority"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_on_read',
        'caption' => _("On Read"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_on_delivery',
        'caption' => _("On Delivery"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_signature',
        'caption' => _("Signature"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_addresses',
        'caption' => _("Addresses"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_save_draft',
        'caption' => _("Save Draft"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_send',
        'caption' => _("Send"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_body',
        'caption' => _("Body"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_attach_browse',
        'caption' => _("Browse"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_attach',
        'caption' => _("Attach"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_delete_attach',
        'caption' => _("Delete Selected Attachments"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the Folder List page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_FOLDER_LIST] = _("Access Keys For Folder List Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_FOLDER_LIST] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_FOLDER_LIST][] = array(
        'name'    => 'accesskey_folders_refresh',
        'caption' => _("Refresh/Check Mail"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_FOLDER_LIST][] = array(
        'name'    => 'accesskey_folders_purge_trash',
        'caption' => _("Purge Trash"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_FOLDER_LIST][] = array(
        'name'    => 'accesskey_folders_inbox',
        'caption' => _("INBOX"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the main Options page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_OPTIONS] = _("Access Keys For Options Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_personal',
        'caption' => _("Personal Information"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_display',
        'caption' => _("Display Preferences"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_highlighting',
        'caption' => _("Message Highlighting"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_folders',
        'caption' => _("Folder Preferences"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_index_order',
        'caption' => _("Index Order"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_compose',
        'caption' => _("Compose Preferences"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_OPTIONS][] = array(
        'name'    => 'accesskey_options_accessibility',
        'caption' => _("Accessibility Preferences"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => $my_a_to_z,
    );


    /* Assemble all this together and return it as our result. */
    $result = array(
        'grps' => $optgrps,
        'vals' => $optvals
    );
    return ($result);
}

/******************************************************************/
/** Define any specialized save functions for this option page. ***/
/**                                                             ***/
/** You must add every function that is set in save parameter   ***/
/******************************************************************/

