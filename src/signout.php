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

do_hook('logout');

sqsession_destroy();

if ($signout_page) {
    header('Status: 303 See Other');
    header("Location: $signout_page");
    exit; /* we send no content if we're redirecting. */
}

/* internal gettext functions will fail, if language is not set */
set_up_language($squirrelmail_language, true, true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<?php
    if ($theme_css != '') {
?>
   <link rel="stylesheet" type="text/css" href="<?php echo $theme_css; ?>" />
<?php
    }
?>
   <meta name="robots" content="noindex,nofollow">
   <title><?php echo $org_title . ' - ' . _("Signout"); ?></title>
</head>
<body text="<?php echo $color[8]; ?>" bgcolor="<?php echo $color[4]; ?>"
link="<?php echo $color[7]; ?>" vlink="<?php echo $color[7]; ?>"
alink="<?php echo $color[7]; ?>">
<br /><br />
<?php
$plugin_message = concat_hook_function('logout_above_text');
echo
html_tag( 'table',
    html_tag( 'tr',
         html_tag( 'th', _("Sign Out"), 'center' ) ,
    '', $color[0] ) .
    $plugin_message .
    html_tag( 'tr',
         html_tag( 'td', _("You have been successfully signed out.") .
             '<br /><a href="login.php" target="' . $frame_top . '">' .
             _("Click here to log back in.") . '</a><br />' ,
         'center' ) ,
    '', $color[4] ) .
    html_tag( 'tr',
         html_tag( 'td', '<br />', 'center' ) ,
    '', $color[0] ) ,
'center', $color[4], 'width="50%" cellpadding="2" cellspacing="0" border="0"' );

/* After a reload of signout.php, $oTemplate might not exist anymore.
 * Recover, so that we don't get all kinds of errors in that situation. */
if ( !isset($oTemplate) || !is_object($oTemplate) ) {
    require_once(SM_PATH . 'class/template/template.class.php');
    $aTemplateSet = ( !isset($aTemplateSet) ? array() : $aTemplateSet );
    $templateset_default = ( !isset($templateset_default) ? 0 : $templateset_default );

    $sTplDir = ( !isset($aTemplateSet[$templateset_default]['PATH']) ?
             SM_PATH . 'templates/default/' :
             $aTemplateSet[$templateset_default]['PATH'] );
    $oTemplate = new Template($sTplDir);
}

$oTemplate->display('footer.tpl');

?>
