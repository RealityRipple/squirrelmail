<?php

/*
 * Generic Change Password plugin
 *
 * This plugin aims to provide a general framework for all password
 * changing methods that currently have their own plugins.
 *
 * $Id $
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
    return '0.1';
}
