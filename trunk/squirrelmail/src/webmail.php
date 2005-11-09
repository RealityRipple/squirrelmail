<?php

/**
 * webmail.php -- Displays the main frameset
 *
 * This file generates the main frameset. The files that are
 * shown can be given as parameters. If the user is not logged in
 * this file will verify username and password.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/prefs.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/auth.php');
require_once(SM_PATH . 'functions/global.php');

if (!function_exists('sqm_baseuri')){
    require_once(SM_PATH . 'functions/display_messages.php');
}
$base_uri = sqm_baseuri();

sqsession_is_active();

sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

if (sqgetGlobalVar('sort', $sort)) {
    $sort = (int) $sort;
}

if (sqgetGlobalVar('startMessage', $startMessage)) {
    $startMessage = (int) $startMessage;
}

if (!sqgetGlobalVar('mailbox', $mailbox)) {
    $mailbox = 'INBOX';
}

sqgetGlobalVar('right_frame', $right_frame, SQ_GET);

if ( isset($_SESSION['session_expired_post']) ) {
    sqsession_unregister('session_expired_post');
}
if(!sqgetGlobalVar('mailto', $mailto)) {
    $mailto = '';
}

is_logged_in();

do_hook('webmail_top');

/**
 * We'll need this to later have a noframes version
 *
 * Check if the user has a language preference, but no cookie.
 * Send him a cookie with his language preference, if there is
 * such discrepancy.
 */
$my_language = getPref($data_dir, $username, 'language');
if ($my_language != $squirrelmail_language) {
    sqsetcookie('squirrelmail_language', $my_language, time()+2592000, $base_uri);
}

$err=set_up_language(getPref($data_dir, $username, 'language'));

$output = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\">\n".
          "<html><head>\n" .
          "<meta name=\"robots\" content=\"noindex,nofollow\">\n" .
          "<title>$org_title</title>\n".
          "</head>";

// Japanese translation used without mbstring support
if ($err==2) {
    echo $output.
         "<body>\n".
         "<p>You need to have PHP installed with the multibyte string function \n".
         "enabled (using configure option --enable-mbstring).</p>\n".
         "<p>System assumed that you accidently switched to Japanese translation \n".
         "and reverted your language preference to English.</p>\n".
         "<p>Please refresh this page in order to use webmail.</p>\n".
         "</body></html>";
    return;
}

$left_size = getPref($data_dir, $username, 'left_size');
$location_of_bar = getPref($data_dir, $username, 'location_of_bar');

if (isset($languages[$squirrelmail_language]['DIR']) &&
    strtolower($languages[$squirrelmail_language]['DIR']) == 'rtl') {
    $temp_location_of_bar = 'right';
} else {
    $temp_location_of_bar = 'left';
}

if ($location_of_bar == '') {
    $location_of_bar = $temp_location_of_bar;
}
$temp_location_of_bar = '';

if ($left_size == "") {
    if (isset($default_left_size)) {
         $left_size = $default_left_size;
    }
    else {
        $left_size = 200;
    }
}

if ($location_of_bar == 'right') {
    $output .= "<frameset cols=\"*, $left_size\" id=\"fs1\">\n";
}
else {
    $output .= "<frameset cols=\"$left_size, *\" id=\"fs1\">\n";
}

/*
 * There are three ways to call webmail.php
 * 1.  webmail.php
 *      - This just loads the default entry screen.
 * 2.  webmail.php?right_frame=right_main.php&sort=X&startMessage=X&mailbox=XXXX
 *      - This loads the frames starting at the given values.
 * 3.  webmail.php?right_frame=folders.php
 *      - Loads the frames with the Folder options in the right frame.
 *
 * This was done to create a pure HTML way of refreshing the folder list since
 * we would like to use as little Javascript as possible.
 *
 * The test for // should catch any attempt to include off-site webpages into
 * our frameset.
 */

if (empty($right_frame) || (strpos(urldecode($right_frame), '//') !== false)) {
    $right_frame = '';
}

if ($right_frame == 'right_main.php') {
    $urlMailbox = urlencode($mailbox);
    $right_frame_url = "right_main.php?mailbox=$urlMailbox"
                       . (!empty($sort)?"&amp;sort=$sort":'')
                       . (!empty($startMessage)?"&amp;startMessage=$startMessage":'');
} elseif ($right_frame == 'options.php') {
    $right_frame_url = 'options.php';
} elseif ($right_frame == 'folders.php') {
    $right_frame_url = 'folders.php';
} elseif ($right_frame == 'compose.php') {
    $right_frame_url = 'compose.php?' . $mailto;
} else if ($right_frame == '') {
    $right_frame_url = 'right_main.php';
} else {
    $right_frame_url =  htmlspecialchars($right_frame);
}

$left_frame  = '<frame src="left_main.php" name="left" frameborder="1" title="'.
               _("Folder List") ."\" />\n";
$right_frame = '<frame src="'.$right_frame_url.'" name="right" frameborder="1" title="'.
               _("Message List") ."\" />\n";

if ($location_of_bar == 'right') {
    $output .= $right_frame . $left_frame;
}
else {
    $output .= $left_frame . $right_frame;
}
$ret = concat_hook_function('webmail_bottom', $output);
if($ret != '') {
    $output = $ret;
}
echo $output;

?>
</frameset>
</html>
