<?php

/**
 * setup.php - Generic Change Password plugin
 *
 * Copyright (c) 2003-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This plugin aims to provide a general framework for all password
 * changing methods that currently have their own plugins.
 *
 * $Id$
 * @package plugins
 * @subpackage change_password
 */

function squirrelmail_plugin_init_change_password() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['optpage_register_block']['change_password'] = 'change_password_optpage';
}

function change_password_optpage() {
    global $optpage_blocks;

    $optpage_blocks[] = array(
        'name' => _("Change Password"),
        'url' => '../plugins/change_password/options.php',
        'desc' => _("Use this to change your email password."),
        'js' => FALSE
    );
}

function change_password_version() {
    return '0.2';
}
