<?php

/**
 * setup.php -- Sent Subfolders Setup File
 *
 * This is a standard SquirrelMail 1.2 API for plugins.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage sent_subfolders
 */

/**
 * Adds plugin to SquirrelMail's hooks
 */
function squirrelmail_plugin_init_sent_subfolders() {
    /* Standard initialization API. */
    global $squirrelmail_plugin_hooks;

    /* The hooks to make the sent subfolders display correctly. */
    $squirrelmail_plugin_hooks['check_handleAsSent_result']['sent_subfolders']
        = 'sent_subfolders_check_handleAsSent';

    /* The hooks to automatically update sent subfolders. */
// hook isn't in 1.5.x; isn't absolutely necessary to run on the folder list anyway
//    $squirrelmail_plugin_hooks['left_main_before']['sent_subfolders']
//        = 'sent_subfolders_update_sentfolder';
    $squirrelmail_plugin_hooks['compose_send']['sent_subfolders']
        = 'sent_subfolders_update_sentfolder';

    /* The hooks to handle sent subfolders options. */
    $squirrelmail_plugin_hooks['optpage_loadhook_folder']['sent_subfolders']
        = 'sent_subfolders_optpage_loadhook_folders';

    /* mark base sent folder as special mailbox */
    $squirrelmail_plugin_hooks['special_mailbox']['sent_subfolders']
        = 'sent_subfolders_special_mailbox';
}

function sent_subfolders_check_handleAsSent($mailbox) {
    include_once(SM_PATH . 'plugins/sent_subfolders/functions.php');
    sent_subfolders_check_handleAsSent_do($mailbox);
}

/**
 * Adds sent_subfolders options in folder preferences
 */
function sent_subfolders_optpage_loadhook_folders() {
    include_once(SM_PATH . 'plugins/sent_subfolders/functions.php');
    sent_subfolders_optpage_loadhook_folders_do();
}

/**
 * Update sent_subfolders settings
 *
 * function updates default sent folder value and
 * creates required imap folders
 */
function sent_subfolders_update_sentfolder() {
    include_once(SM_PATH . 'plugins/sent_subfolders/functions.php');
    sent_subfolders_update_sentfolder_do();
}

/**
 * detects if mailbox is part of sent_subfolders
 *
 * @param string $mb imap folder name
 * @return boolean 1 - is part of sent_subfolders, 0 - is not part of sent_subfolders
 */
function sent_subfolders_special_mailbox($mb) {
    include_once(SM_PATH . 'plugins/sent_subfolders/functions.php');
    return sent_subfolders_special_mailbox_do($mb);
}
