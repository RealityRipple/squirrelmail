<?php

/**
 * globals.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This includes code to update < 4.1.0 globals to the newer format 
 * It also has some session register functions that work across various
 * php versions. 
 *
 * $Id$
 */

/* If magic_quotes_runtime is on, SquirrelMail breaks in new and creative ways.
 * Force magic_quotes_runtime off.
 * chilts@birdbrained.org - I put it here in the hopes that all SM code includes this.
 * If there's a better place, please let me know.
 */
ini_set('magic_quotes_runtime','0');

/* convert old-style superglobals to current method
 * this is executed if you are running PHP 4.0.x.
 * it is run via a require_once directive in validate.php 
 * and redirect.php. Patch submitted by Ray Black.
 */ 

if ( !check_php_version(4,1) ) {
  global $_COOKIE, $_ENV, $_FILES, $_GET, $_POST, $_SERVER, $_SESSION;
  global $HTTP_COOKIE_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $HTTP_GET_VARS,
         $HTTP_POST_VARS, $HTTP_SERVER_VARS, $HTTP_SESSION_VARS;
  $_COOKIE  =& $HTTP_COOKIE_VARS;
  $_ENV     =& $HTTP_ENV_VARS;
  $_FILES   =& $HTTP_POST_FILES;
  $_GET     =& $HTTP_GET_VARS;
  $_POST    =& $HTTP_POST_VARS;
  $_SERVER  =& $HTTP_SERVER_VARS;
  $_SESSION =& $HTTP_SESSION_VARS;
}

/* if running with magic_quotes_gpc then strip the slashes
   from POST and GET global arrays */

if (get_magic_quotes_gpc()) {
    sqstripslashes($_GET);
    sqstripslashes($_POST);
}

/* strip any tags added to the url from PHP_SELF.
   This fixes hand crafted url XXS expoits for any
   page that uses PHP_SELF as the FORM action */

$_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

/* returns true if current php version is at mimimum a.b.c */
function check_php_version ($a = '0', $b = '0', $c = '0')             
{
    global $SQ_PHP_VERSION;

    if(!isset($SQ_PHP_VERSION))
        $SQ_PHP_VERSION = substr( str_pad( preg_replace('/\D/','', PHP_VERSION), 3, '0'), 0, 3);

    return $SQ_PHP_VERSION >= ($a.$b.$c);
}

/* recursively strip slashes from the values of an array */
function sqstripslashes(&$array) {
    if(count($array) > 0) {
        foreach ($array as $index=>$value) {
            if (is_array($array[$index])) {
                sqstripslashes($array[$index]);
            }
            else {
                $array[$index] = stripslashes($value);
            }
        }
    }
}

function sqsession_register ($var, $name) {

    sqsession_is_active();

    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS[$name] = $var;
    }
    else {
        $_SESSION["$name"] = $var; 
    }
    session_register("$name");
}

function sqsession_unregister ($name) {

    sqsession_is_active();

    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        unset($HTTP_SESSION_VARS[$name]);
    }
    else {
        unset($_SESSION[$name]);
    }
    session_unregister("$name");
}

function sqsession_is_registered ($name) {
    $test_name = &$name;
    $result = false;
    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        if (isset($HTTP_SESSION_VARS[$test_name])) {
            $result = true;
        }
    }
    else {
        if (isset($_SESSION[$test_name])) {
            $result = true;
        }
    }
    return $result;
}


/**
 *  Search for the var $name in $_SESSION, $_POST, $_GET
 *  (in that order) and register it as a global var.
 */
function sqextractGlobalVar ($name) {
    if ( !check_php_version(4,1) ) {
        global $_SESSION, $_GET, $_POST;
    }
    global  $$name;
    if( isset($_SESSION[$name]) ) {
        $$name = $_SESSION[$name];
    }
    if( isset($_POST[$name]) ) {
        $$name = $_POST[$name];
    }
    else if ( isset($_GET[$name]) ) {
        $$name =  $_GET[$name];
    }
}

function sqsession_destroy() {

    /*
     * php.net says we can kill the cookie by setting just the name:
     * http://www.php.net/manual/en/function.setcookie.php
     * maybe this will help fix the session merging again.
     *
     * Changed the theory on this to kill the cookies first starting
     * a new session will provide a new session for all instances of
     * the browser, we don't want that, as that is what is causing the
     * merging of sessions.
     */

    global $base_uri;
    $cookie_params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 5, $cookie_params['path'],
        $cookie_params['domain']);
    setcookie('username','',time() - 5,$base_uri);
    setcookie('key','',time() - 5,$base_uri);

    $sessid = session_id();
    if (!empty( $sessid )) {
        if ( !check_php_version(4,1) ) {
            global $HTTP_SESSION_VARS;
            $HTTP_SESSION_VARS = array();
        } else {
            $_SESSION = array();
        }
        @session_destroy;
    }

}

/*
 * Function to verify a session has been started.  If it hasn't
 * start a session up.  php.net doesn't tell you that $_SESSION
 * (even though autoglobal), is not created unless a session is
 * started, unlike $_POST, $_GET and such
 */

function sqsession_is_active() {

    $sessid = session_id();
    if ( empty( $sessid ) ) {
        session_start();
    }
}


?>
