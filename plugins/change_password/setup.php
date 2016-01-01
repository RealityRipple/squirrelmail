<?php

/**
 * setup.php - Generic Change Password plugin
 *
 * This plugin aims to provide a general framework for all password
 * changing methods that currently have their own plugins.
 *
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Plugin init function
 */
function squirrelmail_plugin_init_change_password() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['optpage_register_block']['change_password'] = 'change_password_optpage';
    $squirrelmail_plugin_hooks['optpage_set_loadinfo']['change_password'] = 'change_password_loadinfo';
}

/**
 * Add plugin option block
 */
function change_password_optpage() {
    global $optpage_blocks;

    $optpage_blocks[] = array(
        'name' => _("Change Password"),
        'url' => '../plugins/change_password/options.php',
        'desc' => _("Use this to change your email password."),
        'js' => FALSE
    );
}

/**
 * Displays information after "Successfully Saved Options:"
 * @since 1.5.1
 */
function change_password_loadinfo() {
    global $optpage, $optpage_name;
    if ($optpage=='change_password') {
        // i18n: is displayed after "Successfully Saved Options:"
        $optpage_name=_("User's Password");
    }
}

/**
 * Return version information
 * @return string version number
 */
function change_password_version() {
    return '0.2';
}
