<?php

/**
 * global.php
 *
 * This includes code to update < 4.1.0 globals to the newer format
 * It also has some session register functions that work across various
 * php versions.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 */
define('SQ_INORDER',0);
define('SQ_GET',1);
define('SQ_POST',2);
define('SQ_SESSION',3);
define('SQ_COOKIE',4);
define('SQ_SERVER',5);
define('SQ_FORM',6);

/**
 * returns true if current php version is at mimimum a.b.c
 *
 * Called: check_php_version(4,1)
 * @param int a major version number
 * @param int b minor version number
 * @param int c release number
 * @return bool
 */
function check_php_version ($a = '0', $b = '0', $c = '0')
{
    return version_compare ( PHP_VERSION, "$a.$b.$c", 'ge' );
}

/**
 * returns true if the current internal SM version is at minimum a.b.c
 * These are plain integer comparisons, as our internal version is
 * constructed by us, as an array of 3 ints.
 *
 * Called: check_sm_version(1,3,3)
 * @param int a major version number
 * @param int b minor version number
 * @param int c release number
 * @return bool
 */
function check_sm_version($a = 0, $b = 0, $c = 0)
{
    global $SQM_INTERNAL_VERSION;
    if ( !isset($SQM_INTERNAL_VERSION) ||
         $SQM_INTERNAL_VERSION[0] < $a ||
         ( $SQM_INTERNAL_VERSION[0] == $a &&
           $SQM_INTERNAL_VERSION[1] < $b) ||
         ( $SQM_INTERNAL_VERSION[0] == $a &&
           $SQM_INTERNAL_VERSION[1] == $b &&
           $SQM_INTERNAL_VERSION[2] < $c ) ) {
        return FALSE;
    }
    return TRUE;
}


/**
 * Recursively strip slashes from the values of an array.
 * @param array array the array to strip, passed by reference
 * @return void
 */
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

/**
 * Add a variable to the session.
 * @param mixed $var the variable to register
 * @param string $name the name to refer to this variable
 * @return void
 */
function sqsession_register ($var, $name) {

    sqsession_is_active();

    $_SESSION["$name"] = $var;

    session_register("$name");
}

/**
 * Delete a variable from the session.
 * @param string $name the name of the var to delete
 * @return void
 */
function sqsession_unregister ($name) {

    sqsession_is_active();

    unset($_SESSION[$name]);

    session_unregister("$name");
}

/**
 * Checks to see if a variable has already been registered
 * in the session.
 * @param string $name the name of the var to check
 * @return bool whether the var has been registered
 */
function sqsession_is_registered ($name) {
    $test_name = &$name;
    $result = false;

    if (isset($_SESSION[$test_name])) {
        $result = true;
    }

    return $result;
}

/**
 * Search for the var $name in $_SESSION, $_POST, $_GET, $_COOKIE, or $_SERVER
 * and set it in provided var.
 *
 * If $search is not provided, or if it is SQ_INORDER, it will search $_SESSION,
 * then $_POST, then $_GET. If $search is SQ_FORM it will search $_POST and
 * $_GET.  Otherwise, use one of the defined constants to look for a var in one
 * place specifically.
 *
 * Note: $search is an int value equal to one of the constants defined above.
 *
 * Example:
 * sqgetGlobalVar('username',$username,SQ_SESSION);
 * // No quotes around last param, it's a constant - not a string!
 *
 * @param string name the name of the var to search
 * @param mixed value the variable to return
 * @param int search constant defining where to look
 * @return bool whether variable is found.
 */
function sqgetGlobalVar($name, &$value, $search = SQ_INORDER) {

    /* NOTE: DO NOT enclose the constants in the switch
       statement with quotes. They are constant values,
       enclosing them in quotes will cause them to evaluate
       as strings. */
    switch ($search) {
        /* we want the default case to be first here,
           so that if a valid value isn't specified,
           all three arrays will be searched. */
      default:
      case SQ_INORDER: // check session, post, get
      case SQ_SESSION:
        if( isset($_SESSION[$name]) ) {
            $value = $_SESSION[$name];
            return TRUE;
        } elseif ( $search == SQ_SESSION ) {
            break;
        }
      case SQ_FORM:   // check post, get
      case SQ_POST:
        if( isset($_POST[$name]) ) {
            $value = $_POST[$name];
            return TRUE;
        } elseif ( $search == SQ_POST ) {
          break;
        }
      case SQ_GET:
        if ( isset($_GET[$name]) ) {
            $value = $_GET[$name];
            return TRUE;
        }
        /* NO IF HERE. FOR SQ_INORDER CASE, EXIT after GET */
        break;
      case SQ_COOKIE:
        if ( isset($_COOKIE[$name]) ) {
            $value = $_COOKIE[$name];
            return TRUE;
        }
        break;
      case SQ_SERVER:
        if ( isset($_SERVER[$name]) ) {
            $value = $_SERVER[$name];
            return TRUE;
        }
        break;
    }
    /* Nothing found, return FALSE */
    return FALSE;
}

/**
 * Deletes an existing session, more advanced than the standard PHP
 * session_destroy(), it explicitly deletes the cookies and global vars.
 */
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

    if (isset($_COOKIE[session_name()])) sqsetcookie(session_name(), '', 0, $base_uri);
    if (isset($_COOKIE['username'])) sqsetcookie('username','',0,$base_uri);
    if (isset($_COOKIE['key'])) sqsetcookie('key','',0,$base_uri);

    $sessid = session_id();
    if (!empty( $sessid )) {
        $_SESSION = array();
        @session_destroy();
    }

}

/**
 * Function to verify a session has been started.  If it hasn't
 * start a session up.  php.net doesn't tell you that $_SESSION
 * (even though autoglobal), is not created unless a session is
 * started, unlike $_POST, $_GET and such
 */
function sqsession_is_active() {
    $sessid = session_id();
    if ( empty( $sessid ) ) {
        sqsession_start();
    }
}

/**
 * Function to start the session and store the cookie with the session_id as
 * HttpOnly cookie which means that the cookie isn't accessible by javascript
 * (IE6 only)
 */
function sqsession_start() {
    global $PHP_SELF;

    $dirs = array('|src/.*|', '|plugins/.*|', '|functions/.*|');
    $repl = array('', '', '');
    $base_uri = preg_replace($dirs, $repl, $PHP_SELF);


    session_start();
    $sessid = session_id();
    // session_starts sets the sessionid cookie buth without the httponly var
    // setting the cookie again sets the httponly cookie attribute
    sqsetcookie(session_name(),$sessid,false,$base_uri);
}


/**
 * Set a cookie
 * @param string  $sName     The name of the cookie.
 * @param string  $sValue    The value of the cookie.
 * @param int     $iExpire   The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
 * @param string  $sPath     The path on the server in which the cookie will be available on.
 * @param string  $sDomain   The domain that the cookie is available.
 * @param boolean $bSecure   Indicates that the cookie should only be transmitted over a secure HTTPS connection.
 * @param boolean $bHttpOnly Disallow JS to access the cookie (IE6 only)
 * @return void
 */
function sqsetcookie($sName,$sValue,$iExpire=false,$sPath="",$sDomain="",$bSecure=false,$bHttpOnly=true) {
    $sHeader = "Set-Cookie: $sName=$sValue";
    if ($sPath) {
        $sHeader .= "; path=$sPath";
    }
    if ($iExpire !== false) {
        $sHeader .= "; Max-Age=$iExpire";
        // php uses Expire header, also add the expire header
        $sHeader .= "; expires=". gmdate('D, d-M-Y H:i:s T',$iExpire);
    }
    if ($sDomain) {
        $sHeader .= "; Domain=$sDomain";
    }
    if ($bSecure) {
        $sHeader .= "; Secure";
    }
    if ($bHttpOnly) {
        $sHeader .= "; HttpOnly";
    }
    // $sHeader .= "; Version=1";

    header($sHeader);
}

/**
 * php_self
 *
 * Creates an URL for the page calling this function, using either the PHP global
 * REQUEST_URI, or the PHP global PHP_SELF with QUERY_STRING added. Before 1.5.1
 * function was stored in function/strings.php.
 *
 * @return string the complete url for this page
 * @since 1.2.3
 */
function php_self () {
    if ( sqgetGlobalVar('REQUEST_URI', $req_uri, SQ_SERVER) && !empty($req_uri) ) {
      return $req_uri;
    }

    if ( sqgetGlobalVar('PHP_SELF', $php_self, SQ_SERVER) && !empty($php_self) ) {

      // need to add query string to end of PHP_SELF to match REQUEST_URI
      //
      if ( sqgetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER) && !empty($query_string) ) {
         $php_self .= '?' . $query_string;
      }

      return $php_self;
    }

    return '';
}

/** set the name of the session cookie */
if(isset($session_name) && $session_name) {
    ini_set('session.name' , $session_name);
} else {
    ini_set('session.name' , 'SQMSESSID');
}

/**
 * If magic_quotes_runtime is on, SquirrelMail breaks in new and creative ways.
 * Force magic_quotes_runtime off.
 * tassium@squirrelmail.org - I put it here in the hopes that all SM code includes this.
 * If there's a better place, please let me know.
 */
ini_set('magic_quotes_runtime','0');

/* Since we decided all IMAP servers must implement the UID command as defined in
 * the IMAP RFC, we force $uid_support to be on.
 */

global $uid_support;
$uid_support = true;

/* if running with magic_quotes_gpc then strip the slashes
   from POST and GET global arrays */
if (get_magic_quotes_gpc()) {
    sqstripslashes($_GET);
    sqstripslashes($_POST);
}

/**
 * If register_globals are on, unregister globals.
 * Code requires PHP 4.1.0 or newer.
 */
if ((bool) @ini_get('register_globals')) {
    /**
     * Remove all globals from $_GET, $_POST, and $_COOKIE.
     */
    foreach ($_REQUEST as $key => $value) {
        unset($GLOBALS[$key]);
    }
    /**
     * Remove globalized $_FILES variables
     * Before 4.3.0 $_FILES are included in $_REQUEST.
     * Unglobalize them in separate call in order to remove dependency 
     * on PHP version.
     */
    foreach ($_FILES as $key => $value) {
        unset($GLOBALS[$key]);
        // there are three undocumented $_FILES globals.
        unset($GLOBALS[$key.'_type']);
        unset($GLOBALS[$key.'_name']);
        unset($GLOBALS[$key.'_size']);
    }
    /**
     * Remove globalized environment variables.
     */
    foreach ($_ENV as $key => $value) {
        unset($GLOBALS[$key]);
    }
    /**
     * Remove globalized server variables.
     */
    foreach ($_SERVER as $key => $value) {
        unset($GLOBALS[$key]);
    }
}

/* strip any tags added to the url from PHP_SELF.
   This fixes hand crafted url XXS expoits for any
   page that uses PHP_SELF as the FORM action */
$_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

$PHP_SELF = php_self();

sqsession_is_active();

/** 
 * Remove globalized session data in rg=on setups
 */
if ((bool) @ini_get('register_globals')) {
    foreach ($_SESSION as $key => $value) {
        unset($GLOBALS[$key]);
    }
}
?>
