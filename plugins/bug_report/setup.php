<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a standard SquirrelMail 1.2 API for plugins.
 *
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/* This button fills out a form with your setup information already
   gathered -- all you have to do is type. */


/**
 * Initialize the bug report plugin
 * @return void
 * @access private
 */
function squirrelmail_plugin_init_bug_report() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['menuline']['bug_report'] = 'bug_report_button';
    $squirrelmail_plugin_hooks['options_display_inside']['bug_report'] = 'bug_report_options';
    $squirrelmail_plugin_hooks['options_display_save']['bug_report'] = 'bug_report_save';
    $squirrelmail_plugin_hooks['loading_prefs']['bug_report'] = 'bug_report_load';
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
 * Saves bug report options
 * @access private
 */
function bug_report_save() {
    global $username,$data_dir;

    if( sqgetGlobalVar('bug_report_bug_report_visible', $vis, SQ_POST) ) {
        setPref($data_dir, $username, 'bug_report_visible', '1');
    } else {
        setPref($data_dir, $username, 'bug_report_visible', '');
    }
}

/**
 * Loads bug report options
 * @access private
 */
function bug_report_load() {
    global $username, $data_dir;
    global $bug_report_visible;

    $bug_report_visible = getPref($data_dir, $username, 'bug_report_visible');
}

/**
 * Adds bug report options to display page
 * @access private
 */
function bug_report_options() {
    global $bug_report_visible;

    echo '<tr>' . html_tag('td',_("Bug Reports:"),'right','','style="white-space: nowrap;"') . "\n" .
         '<td><input name="bug_report_bug_report_visible" type="checkbox"';
    if ($bug_report_visible) {
        echo ' checked="checked"';
    }
    echo ' /> ' . _("Show button in toolbar") . "</td></tr>\n";
}

?>
