<?php

/**
 * globals.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This includes code to update < 4.1.0 globals to the newer format 
 * It also has two session register functions that work across various
 * php versions. 
 *
 * $Id $
 */


/* convert old-style superglobals to current method
 * this is executed if you are running PHP 4.0.x.
 * it is run via a require_once directive in validate.php 
 * and redirect.php. Patch submitted by Ray Black.
 */ 

if ( (float)substr(PHP_VERSION,0,3) < 4.1 ) {
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

/* if running with register_globals = 0 and 
   magic_quotes_gpc then strip the slashes
   from POST and GET global arrays */

if (get_magic_quotes_gpc()) {
    if (ini_get('register_globals') == 0) {
        sqstripslashes($_GET);
        sqstripslashes($_POST);
    }
}

/* strip any tags added to the url from PHP_SELF.
   This fixes hand crafted url XXS expoits for any
   page that uses PHP_SELF as the FORM action */

strip_tags($_SERVER['PHP_SELF']);

function sqstripslashes(&$array) {
    foreach ($array as $index=>$value) {
        if (is_array($array["$index"])) {
            sqstripslashes($array["$index"]);
        }
        else {
            $array["$index"] = stripslashes($value);
        }
    }
}

function sqsession_register ($var, $name) {
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 ) {
        global $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS["$name"] = $var;
    }
    else {
        $_SESSION["$name"] = $var; 
    }
}
function sqsession_unregister ($name) {
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 ) {
        global $HTTP_SESSION_VARS;
        unset($HTTP_SESSION_VARS["$name"]);
    }
    else {
        unset($_SESSION["$name"]);
    }
}
function sqsession_is_registered ($name) {
    $test_name = &$name;
    $result = false;
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 ) {
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
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 ) {
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
	global $base_uri;

	if ( (float)substr(PHP_VERSION , 0 , 3) < 4.1) {
		global $HTTP_SESSION_VARS;
		$HTTP_SESSION_VARS = array();
	}
	else {		
		$_SESSION = array();
	}
	
	/*
	 * now reset cookies to 5 seconds ago to delete from browser
	 */
	
	@session_destroy();
	$cookie_params = session_get_cookie_params();	
	setcookie(session_name(), '', time() - 5, $cookie_params['path'], 
			  $cookie_params['domain']);
	setcookie('username', '', time() - 5, $base_uri);
	setcookie('key', '', time() - 5 , $base_uri);
	
}

?>
