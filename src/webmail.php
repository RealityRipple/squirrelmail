<?php

/**
 * webmail.php -- Displays the main frameset
 *
 * This file generates the main frameset. The files that are
 * shown can be given as parameters. If the user is not logged in
 * this file will verify username and password.
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

do_hook('webmail_top');

$output = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n".
          "  \"http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd\">\n".
          "<html><head>\n" .
          "<meta name=\"robots\" content=\"noindex,nofollow\">\n" .
          "<title>$org_title</title>\n".
          "</head>";

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

if ( strpos($right_frame,'?') ) {
    $right_frame_file = substr($right_frame,0,strpos($right_frame,'?'));
} else {
    $right_frame_file = $right_frame;
}

switch($right_frame) {
    case 'right_main.php':
        $right_frame_url = "right_main.php?mailbox=".urlencode($mailbox)
                       . (!empty($sort)?"&amp;sort=$sort":'')
                       . (!empty($startMessage)?"&amp;startMessage=$startMessage":'');
        break;
    case 'options.php':
        $right_frame_url = 'options.php';
        break;
    case 'folders.php':
        $right_frame_url = 'folders.php';
        break;
    case 'compose.php':
        $right_frame_url = 'compose.php?' . $mailto;
        break;
    case '':
        $right_frame_url = 'right_main.php';
        break;
    default:
        $right_frame_url =  urlencode($right_frame);
        break;
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

echo $output . '</frameset>';

$oTemplate->display('footer.tpl');
