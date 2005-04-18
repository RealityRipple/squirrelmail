<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */

/**
 * Initialize the listcommands plugin
 */
function squirrelmail_plugin_init_listcommands () {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['read_body_header']['listcommands'] = 'plugin_listcommands_menu';
}

/**
 * Main function added to read_body_header
 */
function plugin_listcommands_menu() {
    include_once(SM_PATH . 'plugins/listcommands/functions.php');
    plugin_listcommands_menu_do();
}

?>