<?php
/**
 * Bug Report plugin - setup script
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * Initialize the bug report plugin
 *
 * @return void
 *
 * @access private
 *
 */
function squirrelmail_plugin_init_bug_report() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['template_construct_page_header.tpl']['bug_report']
        = 'bug_report_button';
    $squirrelmail_plugin_hooks['optpage_loadhook_display']['bug_report']
        = 'bug_report_block';
}


/**
 * Show the button in the main bar
 *
 * @access private
 *
 */
function bug_report_button() {
    include_once(SM_PATH . 'plugins/bug_report/functions.php');
    return bug_report_button_do();
}

/**
 *
 * Register bug report option block
 *
 * @since 1.5.1
 *
 * @access private
 *
 */
function bug_report_block() {
    include_once(SM_PATH.'plugins/bug_report/functions.php');
    bug_report_block_do();
}

/**
 * Returns info about this plugin
 *
 */
function bug_report_info() { 
    return array(
        'english_name' => 'Bug Report',
        'authors' => array(
            'SquirrelMail Team' => array(),
        ),
        'version' => 'CORE',
        'required_sm_version' => 'CORE',
        'requires_configuration' => 0,
        'summary' => 'Helps with sending bug reports to the SquirrelMail Developers.  Collects a lot of useful information about your system.',
        'details' => 'When people stumble across a bug, which may happen in a work-in-progress, often times they would like to help out the software and get rid of the bug.  Sometimes, these people don\'t know much about the system and how it is set up -- they know enough to make the bug happen for them.  This bug report plugin is designed to gather all of the non-private information for the user automatically, so that the user doesn\'t need to know more than how to trigger the bug.',
    );
} 



/**
 * Returns version info about this plugin
 *
 */
function bug_report_version() {
    $info = bug_report_info();
    return $info['version'];
} 
