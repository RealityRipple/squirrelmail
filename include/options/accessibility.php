<?php

/**
 * options_accessibility.php
 *
 * Displays all options concerning accessibility features in SquirrelMail.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** Define the group constants for this options page. */
define('SMOPT_GRP_ACCESSKEYS_READ_MESSAGE', 0);
define('SMOPT_GRP_ACCESSKEYS_COMPOSE', 1);

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

    /*** Load the Access Key Options for the Read Message page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE] = _("Access Keys For Read Message Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_reply',
        'caption' => _("Reply"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_reply_all',
        'caption' => _("Reply All"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_forward',
        'caption' => _("Forward"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_as_attach',
        'caption' => _("As Attachment"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_delete',
        'caption' => _("Delete"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_bypass_trash',
        'caption' => _("Bypass Trash"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_move_to',
        'caption' => _("Move To"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_move',
        'caption' => _("Move"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_READ_MESSAGE][] = array(
        'name'    => 'accesskey_read_msg_copy',
        'caption' => _("Copy"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );


    /*** Load the Access Key Options for the Compose page into the array ***/
    $optgrps[SMOPT_GRP_ACCESSKEYS_COMPOSE] = _("Access Keys For Compose Screen");
    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE] = array();

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_to',
        'caption' => _("To"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_cc',
        'caption' => _("Cc"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_bcc',
        'caption' => _("Bcc"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_subject',
        'caption' => _("Subject"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_priority',
        'caption' => _("Priority"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_on_read',
        'caption' => _("On Read"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_on_delivery',
        'caption' => _("On Delivery"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_signature',
        'caption' => _("Signature"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_addresses',
        'caption' => _("Addresses"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_save_draft',
        'caption' => _("Save Draft"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_send',
        'caption' => _("Send"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_body',
        'caption' => _("Body"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_attach_browse',
        'caption' => _("Browse"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_attach',
        'caption' => _("Attach"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'posvals' => $my_a_to_z,
    );

    $optvals[SMOPT_GRP_ACCESSKEYS_COMPOSE][] = array(
        'name'    => 'accesskey_compose_delete_attach',
        'caption' => _("Delete Selected Attachments"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
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

