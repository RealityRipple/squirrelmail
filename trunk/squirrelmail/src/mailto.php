<?php

/**
 * mailto.php -- mailto: url handler
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This checks to see if we're logged in.  If we are we open up a new
 * compose window for this email, otherwise we go to login.php
 * (the above functionality has been disabled, by default you are required to
 *  login first)
 *
 * Use the following url to use mailto:
 * http://<your server>/<squirrelmail base dir>/src/mailto.php?emailaddress="%1"
 * see ../contrib/squirrelmail.mailto.reg for a Windows Registry file
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/global.php');

header('Pragma: no-cache');
if(!sqgetGlobalVar('emailaddress', $emailaddress)) {
    return;
}

$mailto_pos = strpos(strtolower($emailaddress), 'mailto:');
if($mailto_pos !== false) {
    $emailaddress = substr($emailaddress, $mailto_pos+7);
    $_GET['emailaddress'] = $emailaddress;
}
if(($pos = strpos($emailaddress, '?')) !== false) {
    $a = substr($emailaddress, $pos+1);
    list($emailaddress, $a) = explode('?', $emailaddress, 2);
    $a = explode('=', $a, 2);
    $_GET['emailaddress'] = $emailaddress;
    $_GET[$a[0]] = $a[1];
}
$trtable = array('emailaddress' => 'send_to',
                 'cc'           => 'send_to_cc',
                 'bcc'          => 'send_to_bcc',
                 'body'         => 'body',
                 'subject'      => 'subject');
$url = '';
/* CC, BCC, etc could be any case, so we'll fix them here */
foreach($_GET as $k=>$g) {
    if($g != '') {
        $k = strtolower($k);
        $k = $trtable[$k];
        $url .= $k . '=' . urlencode($g) . '&';
    }
}
$url = substr($url, 0, -1);

sqsession_is_active();
/* Check to see if we're logged in */
/*
if (sqsession_is_registered('user_is_logged_in')) {
    $redirect = 'webmail.php?right_frame=compose.php?';
} else {
    $redirect = 'login.php?mailto=';
}
*/
$url = urlencode($url);
/* $redirect .= $url; */
$redirect = 'login.php?mailto=' . $url;
session_write_close();
header('Location: ' . $redirect);
?>
