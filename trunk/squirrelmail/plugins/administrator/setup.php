<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Administrator plugin. Allows remote administration.  Philippe Mingo
 *
 * $Id$
 */

require_once(SM_PATH . 'plugins/administrator/auth.php');

function squirrelmail_plugin_init_administrator() {
    global $squirrelmail_plugin_hooks, $username;

    if ( adm_check_user() ) {        
        $squirrelmail_plugin_hooks['optpage_register_block']['administrator'] =
                                  'squirrelmail_administrator_optpage_register_block';
    }
}

function squirrelmail_administrator_optpage_register_block() {
    global $optpage_blocks;
    global $AllowSpamFilters;

    $optpage_blocks[] = array(
        'name' => _("Administration"),
        'url'  => '../plugins/administrator/options.php',
        'desc' => _("This module allows administrators to manage SquirrelMail main configuration remotely."),
        'js'   => false
    );
}
?>
