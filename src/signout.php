<?php

/**
 * signout.php -- cleans up session and logs the user out
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Cleans up after the user. Resets cookies and terminates session.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/prefs.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');

/* Erase any lingering attachments */
if (isset($attachments) && is_array($attachments) 
    && sizeof($attachments)){
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    foreach ($attachments as $info) {
        $attached_file = "$hashed_attachment_dir/$info[localfilename]";
        if (file_exists($attached_file)) {
            unlink($attached_file);
        }
    }
}

if (!isset($frame_top)) {
    $frame_top = '_top';
}

/* If a user hits reload on the last page, $base_uri isn't set
 * because it was deleted with the session. */
if (! sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
    require_once(SM_PATH . 'functions/display_messages.php');
    $base_uri = sqm_baseuri();
}

do_hook('logout');

sqsession_destroy();

if ($signout_page) {
    header('Status: 303 See Other');
    header("Location: $signout_page");
    exit; /* we send no content if we're redirecting. */
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
    if ($theme_css != '') {
?>
   <link rel="stylesheet" type="text/css" href="<?php echo $theme_css ?>" />
<?php
    }
?>
   <title><?php echo $org_title ?> - Signout</title>
</head>
<body text="<?php echo $color[8] ?>" bgcolor="<?php echo $color[4] ?>" 
link="<?php echo $color[7] ?>" vlink="<?php echo $color[7] ?>"
alink="<?php echo $color[7] ?>">
<br><br>
<?php
$plugin_message = concat_hook_function('logout_above_text');
echo
html_tag( 'table',
    html_tag( 'tr',
         html_tag( 'th', _("Sign Out"), 'center' ) ,
    '', $color[0], 'width="100%"' ) .
    $plugin_message .
    html_tag( 'tr',
         html_tag( 'td', _("You have been successfully signed out.") .
             '<br><a href="login.php" target="' . $frame_top . '">' .
             _("Click here to log back in.") . '</a><br>' ,
         'center' ) ,
    '', $color[4], 'width="100%"' ) .
    html_tag( 'tr',
         html_tag( 'td', '<br>', 'center' ) ,
    '', $color[0], 'width="100%"' ) ,
'center', $color[4], 'width="50%" cols="1" cellpadding="2" cellspacing="0" border="0"' )
?>
</body>
</html>
