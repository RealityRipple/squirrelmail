<?php

/**
 * options_personal.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays all options relating to personal information
 *
 * $Id$
 */

require_once('../functions/imap.php');
require_once('../functions/array.php');
 
/* Define the group constants for the personal options page. */
define('SMOPT_GRP_CONTACT', 0);
define('SMOPT_GRP_REPLY', 1);
define('SMOPT_GRP_SIG', 2);

/* Define the optpage load function for the personal options page. */
function load_optpage_data_personal() {
    global $data_dir, $username;
    global $full_name, $reply_to, $email_address;

    /* Set the values of some global variables. */
    $full_name = getPref($data_dir, $username, 'full_name');
    $reply_to = getPref($data_dir, $username, 'reply_to');
    $email_address  = getPref($data_dir, $username, 'email_address'); 

    /* Build a simple array into which we will build options. */
    $optgrps = array();
    $optvals = array();

    /******************************************************/
    /* LOAD EACH GROUP OF OPTIONS INTO THE OPTIONS ARRAY. */
    /******************************************************/

    /*** Load the Contact Information Options into the array ***/
    $optgrps[SMOPT_GRP_CONTACT] = _("Name and Address Options");
    $optvals[SMOPT_GRP_CONTACT] = array();

    /* Build a simple array into which we will build options. */
    $optvals = array();

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'full_name',
        'caption' => _("Full Name"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'email_address',
        'caption' => _("Email Address"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'reply_to',
        'caption' => _("Reply To"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_HUGE
    );

    $identities_link_value = '<A HREF="options_identities.php">'
                           . _("Edit Advanced Identities")
                           . '</A> '
                           . _("(discards changes made on this form so far)");
    $optvals[SMOPT_GRP_CONTACT][] = array(
        'name'    => 'identities_link',
        'caption' => _("Multiple Identities"),
        'type'    => SMOPT_TYPE_COMMENT,
        'refresh' => SMOPT_REFRESH_NONE,
        'comment' =>  $identities_link_value
    );

    /*** Load the Reply Citation Options into the array ***/
    $optgrps[SMOPT_GRP_REPLY] = _("Reply Citation Options");
    $optvals[SMOPT_GRP_REPLY] = array();

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_style',
        'caption' => _("Reply Citation Style"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_NONE    => _("No Citation"),
                           'author_said'  => _("AUTHOR Said"),
                           'quote_who'    => _("Quote Who XML"),
                           'user-defined' => _("User-Defined"))
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_start',
        'caption' => _("User-Defined Citation Start"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM
    );

    $optvals[SMOPT_GRP_REPLY][] = array(
        'name'    => 'reply_citation_end',
        'caption' => _("User-Defined Citation End"),
        'type'    => SMOPT_TYPE_STRING,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM
    );

    /*** Load the Signature Options into the array ***/
    $optgrps[SMOPT_GRP_SIG] = _("Signature Options");
    $optvals[SMOPT_GRP_SIG] = array();

    $optvals[SMOPT_GRP_SIG][] = array(
        'name'    => 'use_signature',
        'caption' => _("Use Signature"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_SIG][] = array(
        'name'    => 'prefix_sig',
        'caption' => _("Prefix Signature with '-- ' Line"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[SMOPT_GRP_SIG][] = array(
        'name'    => 'signature_abs',
        'caption' => _("Signature"),
        'type'    => SMOPT_TYPE_TEXTAREA,
        'refresh' => SMOPT_REFRESH_NONE,
        'size'    => SMOPT_SIZE_MEDIUM,
        'save'    => 'save_option_signature'
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
/******************************************************************/

function save_option_signature($option) {
    global $data_dir, $username;
    setSig($data_dir, $username, $option->new_value);
}

?>
