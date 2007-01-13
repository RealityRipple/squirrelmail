<?php

/**
 * setup.php
 *
 * Address Take -- steals addresses from incoming email messages. Searches
 * the To, Cc, From and Reply-To headers, also searches the body of the
 * message.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage abook_take
 */


/**
 * Initialize the plugin
 */
function squirrelmail_plugin_init_abook_take()
{
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['read_body_bottom']['abook_take'] = 'abook_take_read_body_bottom';
    $squirrelmail_plugin_hooks['loading_prefs']['abook_take']    = 'abook_take_loading_prefs';
    $squirrelmail_plugin_hooks['options_display_inside']['abook_take'] = 'abook_take_options_display_inside';
    $squirrelmail_plugin_hooks['options_display_save']['abook_take']   = 'abook_take_options_display_save';
}

function abook_take_read_body_bottom() {
    include_once(SM_PATH . 'plugins/abook_take/functions.php');

    abook_take_read();
}

function abook_take_loading_prefs() {
    include_once(SM_PATH . 'plugins/abook_take/functions.php');

    abook_take_pref();
}

function abook_take_options_display_inside() {
    include_once(SM_PATH . 'plugins/abook_take/functions.php');

    abook_take_options();
}

function abook_take_options_display_save() {
    include_once(SM_PATH . 'plugins/abook_take/functions.php');

    abook_take_save();
}
