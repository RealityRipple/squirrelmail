<?php

/**
 * mail_fetch/setup.php
 *
 * Setup of the mailfetch plugin.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../../');

/**
 * Initialize the plugin
 */
function squirrelmail_plugin_init_mail_fetch() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['menuline']['mail_fetch'] = 'mail_fetch_link';
    $squirrelmail_plugin_hooks['loading_prefs']['mail_fetch'] = 'mail_fetch_load_pref';
    $squirrelmail_plugin_hooks['login_verified']['mail_fetch'] = 'mail_fetch_setnew';
    $squirrelmail_plugin_hooks['left_main_before']['mail_fetch'] = 'mail_fetch_login';
    $squirrelmail_plugin_hooks['optpage_register_block']['mail_fetch'] = 'mailfetch_optpage_register_block';
    $squirrelmail_plugin_hooks['rename_or_delete_folder']['mail_fetch'] = 'mail_fetch_folderact';
}

/**
 * display link in menu line
 * @private
 */
function mail_fetch_link() {
    displayInternalLink('plugins/mail_fetch/fetch.php', _("Fetch"), '');
    echo '&nbsp;&nbsp;';
}

/**
 * load preferences
 * @private
 */
function mail_fetch_load_pref() {
    include_once(SM_PATH . 'plugins/mail_fetch/functions.php');
    mail_fetch_load_pref_function();
}

/**
 * Fetch pop3 mails on login.
 * @private
 */
function mail_fetch_login() {
    include_once (SM_PATH . 'plugins/mail_fetch/functions.php');
    mail_fetch_login_function();
}

/**
 * Adds preference that is used to detect new logins
 * @private
 */
function mail_fetch_setnew() {
    include_once (SM_PATH . 'plugins/mail_fetch/functions.php');
    mail_fetch_setnew_function();
}

/**
 * Add plugin option block
 * @private
 */
function mailfetch_optpage_register_block() {
    include_once (SM_PATH . 'plugins/mail_fetch/functions.php');
    mailfetch_optpage_register_block_function();
}

/**
 * Update mail_fetch settings when folders are renamed or deleted.
 * @since 1.5.1 and 1.4.5
 * @private
 */
function mail_fetch_folderact($args) {
    include_once (SM_PATH . 'plugins/mail_fetch/functions.php');
    mail_fetch_folderact_function($args);
}
?>