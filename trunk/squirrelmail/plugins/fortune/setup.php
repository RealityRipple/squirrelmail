<?php

/**
 * Fortune plugin setup script
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * Init plugin
 * @access private
 */
function squirrelmail_plugin_init_fortune() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['template_construct_message_list.tpl']['fortune'] = 'fortune';
    $squirrelmail_plugin_hooks['loading_prefs']['fortune'] = 'fortune_load';
    $squirrelmail_plugin_hooks['optpage_loadhook_display']['fortune'] = 'fortune_options';
}

/**
 * Call fortune display function
 * @access private
 */
function fortune() {
    include_once(SM_PATH . 'plugins/fortune/functions.php');
    return fortune_function();
}

/**
 * Call fortune option display function
 * @access private
 */
function fortune_options() {
    include_once(SM_PATH . 'plugins/fortune/functions.php');
    fortune_function_options();
}

/**
 * Call fortune prefs load function
 * @access private
 */
function fortune_load() {
    include_once(SM_PATH . 'plugins/fortune/functions.php');
    fortune_function_load();
}
