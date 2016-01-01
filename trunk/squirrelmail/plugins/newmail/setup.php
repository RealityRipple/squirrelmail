<?php

/**
 * newmail.php
 *
 * Copyright (c) 2000 by Michael Huttinger
 *
 * Quite a hack -- but my first attempt at a plugin.  We were
 * looking for a way to play a sound when there was unseen
 * messages to look at.  Nice for users who keep the squirrel
 * mail window up for long periods of time and want to know
 * when mail arrives.
 *
 * Basically, I hacked much of left_main.php into a plugin that
 * goes through each mail folder and increments a flag if
 * there are unseen messages.  If the final count of unseen
 * folders is > 0, then we play a sound (using the HTML at the
 * far end of this script).
 *
 * This was tested with IE5.0 - but I hear Netscape works well,
 * too (with a plugin).
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */


/**
 * Init newmail plugin
 */
function squirrelmail_plugin_init_newmail() {

    global $squirrelmail_plugin_hooks;
    $totalNewArr=array();
    global $totalNewArr;

    $squirrelmail_plugin_hooks['folder_status']['newmail']
        = 'newmail_folder_status';
    $squirrelmail_plugin_hooks['template_construct_left_main.tpl']['newmail']
        = 'newmail_plugin';
    $squirrelmail_plugin_hooks['optpage_register_block']['newmail']
        = 'newmail_optpage_register_block';
    $squirrelmail_plugin_hooks['options_save']['newmail']
        = 'newmail_sav';
    $squirrelmail_plugin_hooks['loading_prefs']['newmail']
        = 'newmail_pref';
    $squirrelmail_plugin_hooks['optpage_set_loadinfo']['newmail']
        = 'newmail_set_loadinfo';

}


/**
 * Register newmail option block
 */
function newmail_optpage_register_block() {
    include_once(SM_PATH . 'plugins/newmail/functions.php');
    newmail_optpage_register_block_function();
}


/**
 * Save newmail plugin settings
 */
function newmail_sav() {
    include_once(SM_PATH . 'plugins/newmail/functions.php');
    newmail_sav_function();
}


/**
 * Load newmail plugin settings
 */
function newmail_pref() {
    include_once(SM_PATH . 'plugins/newmail/functions.php');
    newmail_pref_function();
}


/**
 * Set loadinfo data
 *
 * Used by option page when saving settings.
 */
function newmail_set_loadinfo() {
    include_once(SM_PATH . 'plugins/newmail/functions.php');
    newmail_set_loadinfo_function();
}


/**
 * Insert needed data in left_main
 */
function newmail_plugin() {
    include_once(SM_PATH . 'plugins/newmail/functions.php');
    return newmail_plugin_function();
}


/**
 * Returns info about this plugin
 *
 */
function newmail_info() {
    return array(
        'english_name' => 'New Mail',
        'authors' => array(
            'SquirrelMail Team' => array(),
        ),
        'version' => 'CORE',
        'required_sm_version' => 'CORE',
        'requires_configuration' => 0,
        'summary' => 'This plugin is used to notify the user when a new mail arrives.',
        'details' => 'This plugin is used to notify the user when a new mail arrives.  This is accomplished by playing a sound through the browser or spawning a popup window whenever the user has unseen messages.',
    );
}



/**
 * Returns version info about this plugin
 *
 */
function newmail_version() {
    $info = newmail_info();
    return $info['version'];
}


