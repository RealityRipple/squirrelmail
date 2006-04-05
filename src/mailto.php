<?php

/**
 * mailto.php -- mailto: url handler
 *
 * This checks to see if we're logged in.  If we are we open up a new
 * compose window for this email, otherwise we go to login.php
 * (the above functionality has been disabled, by default you are required to
 *  login first)
 *
 * Use the following url to use mailto:
 * http://<your server>/<squirrelmail base dir>/src/mailto.php?emailaddress=%1
 * see ../contrib/squirrelmail.mailto.reg for a Windows Registry file
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

// reduce the included files in int.php
$bLogin = true;

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* Force users to login each time? */
$force_login  = true;
/* Open only the compose window, meaningless if $force_login is true */
$compose_only = false;

header('Pragma: no-cache');

$trtable = array('cc'           => 'send_to_cc',
                 'bcc'          => 'send_to_bcc',
                 'body'         => 'body',
                 'subject'      => 'subject');
$url = '';

if(sqgetGlobalVar('emailaddress', $emailaddress)) {
    $emailaddress = trim($emailaddress);
    if(stristr($emailaddress, 'mailto:')) {
        $emailaddress = substr($emailaddress, 7);
    }
    if(strpos($emailaddress, '?') !== false) {
        list($emailaddress, $a) = explode('?', $emailaddress, 2);
        if(strlen(trim($a)) > 0) {
            $a = explode('=', $a, 2);
            $url .= $trtable[strtolower($a[0])] . '=' . urlencode($a[1]) . '&';
        }
    }
    $url = 'send_to=' . urlencode($emailaddress) . '&' . $url;

    /* CC, BCC, etc could be any case, so we'll fix them here */
    foreach($_GET as $k=>$g) {
        $k = strtolower($k);
        if(isset($trtable[$k])) {
            $k = $trtable[$k];
            $url .= $k . '=' . urlencode($g) . '&';
        }
    }
    $url = substr($url, 0, -1);
}

if($force_login == false && sqsession_is_registered('user_is_logged_in')) {
    if($compose_only == true) {
        $redirect = 'compose.php?' . $url;
    } else {
        $redirect = 'webmail.php?right_frame=compose.php?' . urlencode($url);
    }
} else {
    $redirect = 'login.php?mailto=' . urlencode($url);
}

session_write_close();
header('Location: ' . get_location() . '/' . $redirect);

?>