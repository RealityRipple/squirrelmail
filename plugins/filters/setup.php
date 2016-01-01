<?php

/**
 * Message and Spam Filter Plugin - Setup
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage filters
 */

/**
 * Init plugin
 * @access private
 */
function squirrelmail_plugin_init_filters() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['left_main_before']['filters'] = 'start_filters_hook';
    $squirrelmail_plugin_hooks['right_main_after_header']['filters'] = 'start_filters_hook';
    $squirrelmail_plugin_hooks['optpage_register_block']['filters'] = 'filters_optpage_register_block_hook';
    $squirrelmail_plugin_hooks['special_mailbox']['filters'] = 'filters_special_mailbox';
    $squirrelmail_plugin_hooks['rename_or_delete_folder']['filters'] = 'update_for_folder_hook';
    $squirrelmail_plugin_hooks['template_construct_login_webmail.tpl']['filters'] = 'start_filters_hook';
    $squirrelmail_plugin_hooks['folder_status']['filters'] = 'filters_folder_status';
}

/**
 * Report spam folder as special mailbox
 * @param string $mb variable used by hook
 * @return string spam folder name
 * @access private
 */
function filters_special_mailbox( $mb ) {
    global $data_dir, $username;
    return( $mb == getPref($data_dir, $username, 'filters_spam_folder', 'na' ) );
}

/**
 * Called by hook to Register option blocks
 * @access private
 */
function filters_optpage_register_block_hook() {
    include_once(SM_PATH . 'plugins/filters/filters.php');
    filters_optpage_register_block ();
}

/**
 * Called by hook to Start Filtering
 * @param mixed $args optional variable passed by hook
 * @access private
 */
function start_filters_hook($args) {
    include_once(SM_PATH . 'plugins/filters/filters.php');
    start_filters ($args);
}

/**
 * Called by hook to Update filters when Folders Change
 * @access private
 */
function update_for_folder_hook($args) {
    include_once(SM_PATH . 'plugins/filters/filters.php');
    update_for_folder ($args);
}
