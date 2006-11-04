<?php

/**
 * signout.php -- cleans up session and logs the user out
 *
 *  Cleans up after the user. Resets cookies and terminates session.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* Erase any lingering attachments */
sqgetGlobalVar('compose_messages',  $compose_messages,  SQ_SESSION);

if (!empty($compose_message) && is_array($compose_messages)) {
    foreach($compose_messages as $composeMessage) {
        $composeMessage->purgeAttachments();
    }
}

if (!isset($frame_top)) {
    $frame_top = '_top';
}

/* If a user hits reload on the last page, $base_uri isn't set
 * because it was deleted with the session. */
if (! sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
    $base_uri = sqm_baseuri();
}

$login_uri = 'login.php';

$hook_results = do_hook('logout', $login_uri);
if (!empty($hook_results[1])) $login_uri = $hook_results[1];

sqsession_destroy();

if ($signout_page) {
    // Status 303 header is disabled. PHP fastcgi bug. See 1.91 changelog.
    //header('Status: 303 See Other');
    header("Location: $signout_page");
    exit; /* we send no content if we're redirecting. */
}

/* After a reload of signout.php, $oTemplate might not exist anymore.
 * Recover, so that we don't get all kinds of errors in that situation. */
if ( !isset($oTemplate) || !is_object($oTemplate) ) {
    require_once(SM_PATH . 'class/template/Template.class.php');
    $sTemplateID = Template::get_default_template_set();
    $icon_theme_path = !$use_icons ? NULL : Template::calculate_template_images_directory($sTemplateID);
    $oTemplate = Template::construct_template($sTemplateID);

    // We want some variables to always be available to the template
    $always_include = array('sTemplateID', 'icon_theme_path', 'javascript_on');
    foreach ($always_include as $var) {
        $oTemplate->assign($var, (isset($$var) ? $$var : NULL));
    }
}

// The error handler object is probably also not initialized on a refresh
$oErrorHandler = new ErrorHandler($oTemplate,'error_message.tpl');

/* internal gettext functions will fail, if language is not set */
set_up_language($squirrelmail_language, true, true);

displayHtmlHeader($org_title . ' - ' . _("Signout"));

$oTemplate->assign('frame_top', $frame_top);
$oTemplate->assign('login_uri', $login_uri);

$oTemplate->display('signout.tpl');

$oTemplate->display('footer.tpl');

