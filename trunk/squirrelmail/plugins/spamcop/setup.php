<?php

/**
 * setup.php -- SpamCop plugin - setup script
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage spamcop
 */

/**
 * Initialize the plugin
 * @access private
 */
function squirrelmail_plugin_init_spamcop() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['optpage_register_block']['spamcop'] =
        'spamcop_options';
    $squirrelmail_plugin_hooks['loading_prefs']['spamcop'] =
        'spamcop_load';
    $squirrelmail_plugin_hooks['read_body_header_right']['spamcop'] =
        'spamcop_show_link';
    $squirrelmail_plugin_hooks['compose_send']['spamcop'] =
        'spamcop_while_sending';
}

/**
 * Loads spamcop settings and validates some of values (make '' into 'default', etc.)
 * @access private
 */
function spamcop_load() {
    include_once(SM_PATH . 'plugins/spamcop/functions.php');
    spamcop_load_function();
}


/**
 * Shows spamcop link on the read-a-message screen
 * @access private
 */
function spamcop_show_link(&$links) {
    include_once(SM_PATH . 'plugins/spamcop/functions.php');
    spamcop_show_link_function($links);
}

/**
 * Show spamcop options block
 * @access private
 */
function spamcop_options() {
    include_once(SM_PATH . 'plugins/spamcop/functions.php');
    spamcop_options_function();
}


/**
 * Process messages submitted by email
 * @access private
 */
function spamcop_while_sending() {
    include_once(SM_PATH . 'plugins/spamcop/functions.php');
    spamcop_while_sending_function();
}
