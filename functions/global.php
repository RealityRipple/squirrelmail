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
    $rg = ini_get('register_globals');
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 && empty($rg)) {
        global $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS["$name"] = $var;
    }
    else {
        session_register("$name");
    }
}
function sqsession_unregister ($name) {
    $rg = ini_get('register_globals');
    if ( (float)substr(PHP_VERSION,0,3) < 4.1 && empty($rg)) {
    global $HTTP_SESSION_VARS;
        unset($HTTP_SESSION_VARS["$name"]);
    }
    else {
        session_unregister("$name");
    }
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
?>
