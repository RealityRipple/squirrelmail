<?php

/**
 * setup.php
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */

/**
 * Initialize the listcommands plugin
 */
function squirrelmail_plugin_init_listcommands () {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['template_construct_read_headers.tpl']['listcommands'] = 'plugin_listcommands_menu';
    $squirrelmail_plugin_hooks['optpage_register_block']['listcommands'] = 'plugin_listcommands_optpage_register_block';

}

/**
 * Main function added to read_body_header
 */
function plugin_listcommands_menu() {
    include_once(SM_PATH . 'plugins/listcommands/functions.php');
    return plugin_listcommands_menu_do();
}


/**
  * Show mailing list management option section on options page
  */
function plugin_listcommands_optpage_register_block() {
    include_once(SM_PATH . 'plugins/listcommands/functions.php');
    plugin_listcommands_optpage_register_block_do();
}
