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

function squirrelmail_plugin_init_administrator() {
    global $squirrelmail_plugin_hooks, $username;

    if ( $adm_id = fileowner('../config/config.php') ) {
        $adm = posix_getpwuid( $adm_id );
        if ( $username == $adm['name'] ) {
            $squirrelmail_plugin_hooks['optpage_register_block']['administrator'] =
                                      'squirrelmail_plugin_optpage_register_block';
        }
    }
}

function squirrelmail_plugin_optpage_register_block() {
    global $optpage_blocks;
    global $AllowSpamFilters;

    $optpage_blocks[] = array(
        'name' => _("Administration"),
        'url'  => '../plugins/administrator/options.php',
        'desc' => _("This module allows administrators to run SquirrelMail configuration remotely."),
        'js'   => false
    );
}
?>