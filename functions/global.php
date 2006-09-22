<?php

/**
 * global.php
 *
 * This includes code to update < 4.1.0 globals to the newer format
 * It also has some session register functions that work across various
 * php versions.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
 * Merges two variables into a single array
 *
 * Similar to PHP array_merge function, but provides same
 * functionality as array_merge without losing array values
 * with same key names.  If the values under identical array
 * keys are both strings and $concat_strings is TRUE, those
 * values are concatenated together, otherwise they are placed
 * in a sub-array and are merged (recursively) in the same manner.  
 *
 * If either of the elements being merged is not an array,
 * it will simply be added to the returned array.
 * 
 * If both values are strings and $concat_strings is TRUE,
 * a concatenated string is returned instead of an array.
 * 
 * @param mixed   $a              First element to be merged
 * @param mixed   $b              Second element to be merged
 * @param boolean $concat_strings Whether or not string values 
 *                                should be concatenated instead 
 *                                of added to different array 
 *                                keys (default TRUE)
 *
 * @return array The merged $a and $b in one array
 *
 * @since 1.5.2
 *
 */
function sq_array_merge($a, $b, $concat_strings=true) {

    $ret = array();

    if (is_array($a)) {
        $ret = $a;
    } else {
        if (is_string($a) && is_string($b) && $concat_strings) {
            return $a . $b;
        }
        $ret[] = $a;
    }


    if (is_array($b)) {
        foreach ($b as $key => $value) {
            if (isset($ret[$key])) {
                $ret[$key] = sq_array_merge($ret[$key], $value, $concat_strings);
            } else {
                $ret[$key] = $value;
            }
        }
    } else {
        $ret[] = $b;
    }

    return $ret;

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
 * @param int typecast force variable to be cast to given type (please
 *                     use SQ_TYPE_XXX constants or set to FALSE (default)
 *                     to leave variable type unmolested)
 * @return bool whether variable is found.
 */
function sqgetGlobalVar($name, &$value, $search = SQ_INORDER, $default = NULL, $typecast = false) {

    $result = false;

    switch ($search) {
        /* we want the default case to be first here,
           so that if a valid value isn't specified,
           all three arrays will be searched. */
      default:
      case SQ_INORDER: // check session, post, get
      case SQ_SESSION:
        if( isset($_SESSION[$name]) ) {
            $value = $_SESSION[$name];
            $result = TRUE;
            break;
        } elseif ( $search == SQ_SESSION ) {
            break;
        }
      case SQ_FORM:   // check post, get
      case SQ_POST:
        if( isset($_POST[$name]) ) {
            $value = $_POST[$name];
            $result = TRUE;
            break;
        } elseif ( $search == SQ_POST ) {
          break;
        }
      case SQ_GET:
        if ( isset($_GET[$name]) ) {
            $value = $_GET[$name];
            $result = TRUE;
            break;
        }
        /* NO IF HERE. FOR SQ_INORDER CASE, EXIT after GET */
        break;
      case SQ_COOKIE:
        if ( isset($_COOKIE[$name]) ) {
            $value = $_COOKIE[$name];
            $result = TRUE;
            break;
        }
        break;
      case SQ_SERVER:
        if ( isset($_SERVER[$name]) ) {
            $value = $_SERVER[$name];
            $result = TRUE;
            break;
        }
        break;
    }
    if ($result && $typecast) {
        switch ($typecast) {
            case SQ_TYPE_INT: $value = (int) $value; break;
            case SQ_TYPE_STRING: $value = (string) $value; break;
            case SQ_TYPE_BOOL: $value = (bool) $value; break;
            default: break;
        }
    } else if (!$result && !is_null($default)) {
        $value = $default;
    }
    return $result;
}

/**
 * Deletes an existing session, more advanced than the standard PHP
 * session_destroy(), it explicitly deletes the cookies and global vars.
 *
 * WARNING: Older PHP versions have some issues with session management.
 * See http://bugs.php.net/11643 (warning, spammed bug tracker) and 
 * http://bugs.php.net/13834. SID constant is not destroyed in PHP 4.1.2,
 * 4.2.3 and maybe other versions. If you restart session after session 
 * is destroyed, affected PHP versions produce PHP notice. Bug should 
 * be fixed only in 4.3.0
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
    global $base_uri;

    session_start();
    $session_id = session_id();

    // session_starts sets the sessionid cookie buth without the httponly var
    // setting the cookie again sets the httponly cookie attribute

    // disable, @see sqsetcookie and php 5.1.2
    // sqsetcookie(session_name(),session_id(),false,$base_uri);
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
function sqsetcookie($sName,$sValue,$iExpire=false,$sPath="",$sDomain="",$bSecure=false,$bHttpOnly=true,$bFlush=false) {
    static $sCookieCache;
    if (!isset($sCache)) {
        $sCache = '';
    }
    /**
     * We have to send all cookies with one header call otherwise we loose cookies.
     * In order to achieve that the sqsetcookieflush function calls this function with $bFlush = true.
     * If that happens we send the cookie header.
     */
    if ($bFlush) {
        // header($sCookieCache);
        return;
    }
    if (!$sName) return;

    // php 5.1.2 and 4.4.2 do not allow to send multiple headers at once.
    // Because that's the only way to get this thing working we fallback to
    // setcookie until we solved this
    if ($iExpire===false) $iExpire = 0;
    setcookie($sName, $sValue, $iExpire, $sPath);
    return;

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
    // TODO: IE for Mac (5.2) thinks that semicolon is part of cookie domain
    if ($bSecure) {
        $sHeader .= "; Secure";
    }
    if ($bHttpOnly) {
        $sHeader .= "; HttpOnly";
    }
    // $sHeader .= "; Version=1";
    $sCookieCache .= $sHeader ."\r\n";
    //header($sHeader."\r\n");
}

/**
 * Send the cookie header
 *
 * Cookies set with sqsetcookie will bet set after a sqsetcookieflush call.
 * @return void
 */
function sqsetcookieflush() {
    sqsetcookie('','','','','','','',true);
}

/**
 * session_regenerate_id replacement for PHP < 4.3.2
 *
 * This code is borrowed from Gallery, session.php version 1.53.2.1
 */
if (!function_exists('session_regenerate_id')) {
    function make_seed() {
        list($usec, $sec) = explode(' ', microtime());
        return (float)$sec + ((float)$usec * 100000);
    }

    function php_combined_lcg() {
        mt_srand(make_seed());
        $tv = gettimeofday();
        $lcg['s1'] = $tv['sec'] ^ (~$tv['usec']);
        $lcg['s2'] = mt_rand();
        $q = (int) ($lcg['s1'] / 53668);
        $lcg['s1'] = (int) (40014 * ($lcg['s1'] - 53668 * $q) - 12211 * $q);
        if ($lcg['s1'] < 0) {
            $lcg['s1'] += 2147483563;
        }
        $q = (int) ($lcg['s2'] / 52774);
        $lcg['s2'] = (int) (40692 * ($lcg['s2'] - 52774 * $q) - 3791 * $q);
        if ($lcg['s2'] < 0) {
            $lcg['s2'] += 2147483399;
        }
        $z = (int) ($lcg['s1'] - $lcg['s2']);
        if ($z < 1) {
            $z += 2147483562;
        }
        return $z * 4.656613e-10;
    }

    function session_regenerate_id() {
        global $base_uri;
        $tv = gettimeofday();
        sqgetGlobalVar('REMOTE_ADDR',$remote_addr,SQ_SERVER);
        $buf = sprintf("%.15s%ld%ld%0.8f", $remote_addr, $tv['sec'], $tv['usec'], php_combined_lcg() * 10);
        session_id(md5($buf));
        if (ini_get('session.use_cookies')) {
            // at a later stage we use sqsetcookie. At this point just do
            // what session_regenerate_id would do
            setcookie(session_name(), session_id(), NULL, $base_uri);
        }
        return TRUE;
    }
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
