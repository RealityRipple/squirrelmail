<?php

/**
* validate.php
*
* Copyright (c) 1999-2002 The SquirrelMail Project Team
* Licensed under the GNU GPL. For full terms see the file COPYING.
*
* $Id$
*/

/* include the mime class before the session start ! otherwise we can't store
 * messages with a session_register.
 *
 * From http://www.php.net/manual/en/language.oop.serialization.php:
 *   In case this isn't clear:
 *   In 4.2 and below: 
 *      session.auto_start and session objects are mutually exclusive.
 *
 * We need to load the classes before the session is started, 
 * except that the session could be started automatically 
 * via session.auto_start. So, we'll close the session, 
 * then load the classes, and reopen the session which should 
 * make everything happy.  
 *
 * ** Note this means that for the 1.3.2 release, we should probably
 * recommend that people set session.auto_start=0 to avoid this altogether.
 */
session_write_close();

/* SquirrelMail required files. */
require_once(SM_PATH . 'class/mime.class.php');

session_start();

require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/auth.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/global.php');

is_logged_in();

/**
* Auto-detection
*
* if $send (the form button's name) contains "\n" as the first char
* and the script is compose.php, then trim everything. Otherwise, we
* don't have to worry.
*
* This is for a RedHat package bug and a Konqueror (pre 2.1.1?) bug
*/
global $send, $PHP_SELF;
if (isset($send)
    && (substr($send, 0, 1) == "\n")
    && (substr($PHP_SELF, -12) == '/compose.php')) {
    if ($REQUEST_METHOD == 'POST') {
        global $HTTP_POST_VARS;
        TrimArray($HTTP_POST_VARS);
    } else {
        global $HTTP_GET_VARS;
        TrimArray($HTTP_GET_VARS);
    }
}

/**
* Everyone needs stuff from config, and config needs stuff from
* strings.php, so include them both here. Actually, strings is
* included at the top now as the string array functions have
* been moved into it.
*
* Include them down here instead of at the top so that all config
* variables overwrite any passed in variables (for security).
*/

/**
 * Reset the $theme() array in case a value was passed via a cookie.
 * This is until theming is rewritten.
 */
global $theme;
unset($theme);
$theme=array();

require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/prefs.php');

/* Set up the language (i18n.php was included by auth.php). */
global $username, $data_dir;
set_up_language(getPref($data_dir, $username, 'language'));

$timeZone = getPref($data_dir, $username, 'timezone');
if ( $timeZone != SMPREF_NONE && ($timeZone != "") 
    && !ini_get("safe_mode")) {
    putenv("TZ=".$timeZone);
}
?>
