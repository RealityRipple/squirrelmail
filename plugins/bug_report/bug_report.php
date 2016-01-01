<?php
/**
 * bug_report.php
 *
 * This generates the bug report data, gives information about where
 * it will be sent to and what people will do with it, and provides
 * a button to show the bug report mail message in order to actually
 * send it.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */


// This is the bug_report options page
//
define('PAGE_NAME', 'bug_report_options');


// Include the SquirrelMail initialization file.
//
require('../../include/init.php');


// load plugin functions
//
require_once(SM_PATH . 'plugins/bug_report/functions.php');


displayPageHeader($color);


// error out when bug_report plugin is disabled 
// or is called by the wrong user
//
if (! is_plugin_enabled('bug_report') || ! bug_report_check_user()) {
    error_box(_("Plugin is disabled."));
    $oTemplate->display('footer.tpl');
    exit();
}


// get system specs
//
require_once(SM_PATH . 'plugins/bug_report/system_specs.php');
list($body, $warnings, $corrections) = get_system_specs();

$body_top = "I am subscribed to the this mailing list.\n" .
            " (applies when you are sending email to SquirrelMail mailing list)\n".
            "  [ ]  True - No need to CC me when replying\n" .
            "  [ ]  False - Please CC me when replying\n" .
            "\n" .
            "This bug occurs when I ...\n" .
            "  ... view a particular message\n" .
            "  ... use a specific plugin/function\n" .
            "  ... try to do/view/use ....\n" .
            "\n\n\n" .
            "The description of the bug:\n\n\n" .
            "I can reproduce the bug by:\n\n\n" .
            "(Optional) I got bored and found the bug occurs in:\n\n\n" .
            "(Optional) I got really bored and here's a fix:\n\n\n" .
            "----------------------------------------------\n\n";

$body = $body_top . $body;

global $oTemplate, $bug_report_admin_email;
if (!empty($bug_report_admin_email)) {
    $oTemplate->assign('admin_email', $bug_report_admin_email);
}
$oTemplate->assign('message_body', $body);
$oTemplate->assign('title_bg_color', $color[0]);
$oTemplate->assign('warning_messages', $warnings);
$oTemplate->assign('correction_messages', $corrections);
$oTemplate->assign('warning_count', sizeof($warnings));
$oTemplate->assign('version', SM_VERSION);
$oTemplate->display('plugins/bug_report/usage.tpl');
$oTemplate->display('footer.tpl');

