<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a standard SquirrelMail 1.2 API for plugins.
 *
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * Initialize the bug report plugin
 * @return void
 * @access private
 */
function squirrelmail_plugin_init_bug_report() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['menuline']['bug_report'] = 'bug_report_button';
    $squirrelmail_plugin_hooks['loading_prefs']['bug_report'] = 'bug_report_load';
    $squirrelmail_plugin_hooks['optpage_loadhook_display']['bug_report'] = 'bug_report_block';
}


/**
 * Show the button in the main bar
 * @access private
 */
function bug_report_button() {
    global $bug_report_visible;

    if (! $bug_report_visible) {
        return;
    }

    displayInternalLink('plugins/bug_report/bug_report.php', _("Bug"), '');
    echo "&nbsp;&nbsp;\n";
}

/**
 * Loads bug report options
 * @access private
 */
function bug_report_load() {
    global $username, $data_dir;
    global $bug_report_visible;

    $bug_report_visible = (bool) getPref($data_dir, $username, 'bug_report_visible',false);
}

/**
 * Register bug report option block
 * @since 1.5.1
 * @access private
 */
function bug_report_block() {
    global $optpage_data;
    $optpage_data['grps']['bug_report'] = _("Bug Reports");
    $optionValues = array();
    // FIXME: option needs refresh in SMOPT_REFRESH_RIGHT 
    // (menulink is processed before options are saved/loaded)
    $optionValues[] = array(
        'name'    => 'bug_report_visible',
        'caption' => _("Show button in toolbar"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_ALL,
        'initial_value' => false
        );
    $optpage_data['vals']['bug_report'] = $optionValues;
}

?>