<?php

/**
 * options_compose.php
 *
 * Displays all options concerning composing of new messages
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** Define the group constants for this options page. */
define('SMOPT_GRP_COMPOSE', 0);
define('SMOPT_GRP_COMPOSE_REPLY', 1);

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
function load_optpage_data_compose() {

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the General Compose Options into the array ***/
    $optgrps[SMOPT_GRP_COMPOSE] = _("General Message Composition");
    $optvals[SMOPT_GRP_COMPOSE] = array();

    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'editor_size',
        'caption' => _("Width of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'editor_height',
        'caption' => _("Height of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'location_of_buttons',
        'caption' => _("Location of Buttons when Composing"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_LOC_TOP     => _("Before headers"),
                           SMPREF_LOC_BETWEEN => _("Between headers and message body"),
                           SMPREF_LOC_BOTTOM  => _("After message body"))
    );


    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'use_javascript_addr_book',
        'caption' => _("Address Book Display Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('1' => _("Pop-up window"),
                           '0' => _("In-page"))
    );


    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'addrsrch_fullname',
        'caption' => _("Format of Addresses Added From Address Book"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('noprefix' => _("No prefix/Address only"),
                           'nickname' => _("Nickname and address"),
                           'fullname' => _("Full name and address"))
    );


    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'compose_new_win',
        'caption' => _("Compose Messages in New Window"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL
    );

    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'compose_width',
        'caption' => _("Width of Compose Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_ALL,
        'size'    => SMOPT_SIZE_TINY
    );

    $optvals[SMOPT_GRP_COMPOSE][] = array(
        'name'    => 'compose_height',
        'caption' => _("Height of Compose Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_ALL,
        'size'    => SMOPT_SIZE_TINY
    );


    /*** Load the General Options into the array ***/
    $optgrps[SMOPT_GRP_COMPOSE_REPLY] = _("Replying and Forwarding Messages");
    $optvals[SMOPT_GRP_COMPOSE_REPLY] = array();

    $optvals[SMOPT_GRP_COMPOSE_REPLY][] = array(
        'name'    => 'include_self_reply_all',
        'caption' => _("Include Me in CC when I Reply All"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_COMPOSE_REPLY][] = array(
        'name'    => 'sig_first',
        'caption' => _("Prepend Signature before Reply/Forward Text"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_COMPOSE_REPLY][] = array(
        'name'    => 'body_quote',
        'caption' => _("Prefix for Original Message when Replying"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_TINY,
        'save'    => 'save_option_reply_prefix'
    );

    $optvals[SMOPT_GRP_COMPOSE_REPLY][] = array(
        'name'    => 'reply_focus',
        'caption' => _("Cursor Position when Replying"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array('' => _("To: field"),
                           'focus' => _("Focus in body"),
                           'select' => _("Select body"),
                           'none' => _("No focus"))
    );

    $optvals[SMOPT_GRP_COMPOSE_REPLY][] = array(
        'name'    => 'strip_sigs',
        'caption' => _("Strip signature when replying"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
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

/**
 * This function saves the reply prefix (body_quote) character(s)
 * @param object $option
 */
function save_option_reply_prefix($option) {

    // save as "NONE" if it was blanked out
    //
    if (empty($option->new_value)) $option->new_value = 'NONE';


    // Save the option like normal.
    //
    save_option($option);

}
