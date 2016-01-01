<?php

/**
 * global.php
 *
 * This includes code to update < 4.1.0 globals to the newer format
 * It also has some session register functions that work across various
 * php versions.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * These constants are used in the function sqgetGlobalVar(). See
 * sqgetGlobalVar() for a description of what they mean.
 *
 * @since 1.4.0
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
 * Squelch error output to screen (only) for the given function.
 * If the SquirrelMail debug mode SM_DEBUG_MODE_ADVANCED is not 
 * enabled, error output will not go to the log, either.
 *
 * This provides an alternative to the @ error-suppression
 * operator where errors will not be shown in the interface
 * but will show up in the server log file (assuming the
 * administrator has configured PHP logging).
 *
 * @since 1.4.12 and 1.5.2
 *
 * @param string $function The function to be executed
 * @param array  $args     The arguments to be passed to the function
 *                         (OPTIONAL; default no arguments)
 *                         NOTE: The caller must take extra action if
 *                               the function being called is supposed
 *                               to use any of the parameters by
 *                               reference.  In the following example,
 *                               $x is passed by reference and $y is
 *                               passed by value to the "my_func"
 *                               function.
 * sq_call_function_suppress_errors('my_func', array(&$x, $y));
 *
 * @return mixed The return value, if any, of the function being
 *               executed will be returned.
 *
 */
function sq_call_function_suppress_errors($function, $args=array()) {
   global $sm_debug_mode;

   $display_errors = ini_get('display_errors');
   ini_set('display_errors', '0');

   // if advanced debug mode isn't enabled, don't log the error, either
   //
   if (!($sm_debug_mode & SM_DEBUG_MODE_ADVANCED))
      $error_reporting = error_reporting(0);

   $ret = call_user_func_array($function, $args);

   if (!($sm_debug_mode & SM_DEBUG_MODE_ADVANCED))
      error_reporting($error_reporting);

   ini_set('display_errors', $display_errors);
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

    $_SESSION[$name] = $var;
}

/**
 * Delete a variable from the session.
 * @param string $name the name of the var to delete
 * @return void
 */
function sqsession_unregister ($name) {

    sqsession_is_active();

    unset($_SESSION[$name]);

    // starts throwing warnings in PHP 5.3.0 and is
    // removed in PHP 6 and is redundant anyway
    //session_unregister("$name");
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
  * Retrieves a form variable, from a set of possible similarly named
  * form variables, based on finding a different, single field.  This
  * is intended to allow more than one same-named inputs in a single
  * <form>, where the submit button that is clicked tells us which
  * input we should retrieve.  An example is if we have:
  *     <select name="startMessage_1">
  *     <select name="startMessage_2">
  *     <input type="submit" name="form_submit_1" />
  *     <input type="submit" name="form_submit_2" />
  * and we want to know which one of the select inputs should be
  * returned as $startMessage (without the suffix!), this function
  * decides by looking for either "form_submit_1" or "form_submit_2"
  * (both should not appear).  In this example, $name should be
  * "startMessage" and $indicator_field should be "form_submit".
  *
  * NOTE that form widgets must be named with the suffix "_1", "_2", "_3"
  *      and so on, or this function will not work.
  *
  * If more than one of the indicator fields is found, the first one
  * (numerically) will win.
  *
  * If an indicator field is found without a matching input ($name)
  * field, FALSE is returned.
  *
  * If no indicator fields are found, a field of $name *without* any
  * suffix is searched for (but only if $fallback_no_suffix is TRUE),
  * and if not found, FALSE is ultimately returned.
  *
  * It should also be possible to use the same string for both
  * $name and $indicator_field to look for the first possible
  * widget with a suffix that can be found (and possibly fallback
  * to a widget without a suffix).
  *
  * @param string name the name of the var to search
  * @param mixed value the variable to return
  * @param string indicator_field the name of the field upon which to base
  *                               our decision upon (see above)
  * @param int search constant defining where to look
  * @param bool fallback_no_suffix whether or not to look for $name with
  *                                no suffix when nothing else is found
  * @param mixed default the value to assign to $value when nothing is found
  * @param int typecast force variable to be cast to given type (please
  *                     use SQ_TYPE_XXX constants or set to FALSE (default)
  *                     to leave variable type unmolested)
  *
  * @return bool whether variable is found.
  */
function sqGetGlobalVarMultiple($name, &$value, $indicator_field,
                                $search = SQ_INORDER,
                                $fallback_no_suffix=TRUE, $default=NULL,
                                $typecast=FALSE) {

    // Set arbitrary max limit -- should be much lower except on the
    // search results page, if there are many (50 or more?) mailboxes
    // shown, this may not be high enough.  Is there some way we should
    // automate this value?
    //
    $max_form_search = 100;

    for ($i = 1; $i <= $max_form_search; $i++) {
        if (sqGetGlobalVar($indicator_field . '_' . $i, $temp, $search)) {
            return sqGetGlobalVar($name . '_' . $i, $value, $search, $default, $typecast);
        }
    }


    // no indicator field found; just try without suffix if allowed
    //
    if ($fallback_no_suffix) {
        return sqGetGlobalVar($name, $value, $search, $default, $typecast);
    }


    // no dice, set default and return FALSE
    //
    if (!is_null($default)) {
        $value = $default;
    }
    return FALSE;

}


/**
 * Search for the variable $name in one or more of the global variables
 * $_SESSION, $_POST, $_GET, $_COOKIE, and $_SERVER, and set the value of it in
 * the variable $vaule.
 *
 * $search must be one of the defined constants below. The default is
 * SQ_INORDER. Both SQ_INORDER and SQ_FORM stops on the first match.
 *
 * SQ_INORDER searches $_SESSION, then $_POST, and then $_GET.
 * SQ_FORM searches $_POST and then $_GET.
 * SQ_COOKIE searches $_COOKIE only.
 * SQ_GET searches $_GET only.
 * SQ_POST searches $_POST only.
 * SQ_SERVER searches $_SERVER only.
 * SQ_SESSION searches $_SESSION only.
 *
 * Example:
 * sqgetGlobalVar('username', $username, SQ_SESSION);
 * // No quotes around the last parameter, it's a constant - not a string!
 *
 * @param string name the name of the var to search
 * @param mixed value the variable to return
 * @param int search constant defining where to look
 * @param mixed default the value to assign to $value when nothing is found
 * @param int typecast force variable to be cast to given type (please
 *                     use SQ_TYPE_XXX constants or set to FALSE (default)
 *                     to leave variable type unmolested)
 *
 * @return bool whether variable is found.
 */
function sqgetGlobalVar($name, &$value, $search = SQ_INORDER, $default = NULL, $typecast = FALSE) {
    // The return value defaults to FALSE, i.e. the variable wasn't found.
    $result = FALSE;

    // Search the global variables to find a match.
    switch ($search) {
        default:
            // The default needs to be first here so SQ_INORDER will be used if
            // $search isn't a valid constant.
        case SQ_INORDER:
            // Search $_SESSION, then $_POST, and then $_GET. Stop on the first
            // match.
        case SQ_SESSION:
            if (isset($_SESSION[$name])) {
                // If a match is found, set the specified variable to the found
                // value, indicate a match, and stop the search.
                $value = $_SESSION[$name];
                $result = TRUE;
                break;
            } elseif ($search == SQ_SESSION) {
                // Only stop the search if SQ_SESSION is set. SQ_INORDER will
                // continue with the next clause.
                break;
            }
        case SQ_FORM:
            // Search $_POST and then $_GET. Stop on the first match.
        case SQ_POST:
            if (isset($_POST[$name])) {
                // If a match is found, set the specified variable to the found
                // value, indicate a match, and stop the search.
                $value = $_POST[$name];
                $result = TRUE;
                break;
            } elseif ($search == SQ_POST) {
                // Only stop the search if SQ_POST is set. SQ_INORDER and
                // SQ_FORM will continue with the next clause.
                break;
            }
        case SQ_GET:
            if (isset($_GET[$name])) {
                // If a match is found, set the specified variable to the found
                // value, indicate a match, and stop the search.
                $value = $_GET[$name];
                $result = TRUE;
                break;
            }
            // Stop the search regardless of if SQ_INORDER, SQ_FORM, or SQ_GET
            // is set. All three of them ends here.
            break;
        case SQ_COOKIE:
            if (isset($_COOKIE[$name])) {
                // If a match is found, set the specified variable to the found
                // value, indicate a match, and stop the search.
                $value = $_COOKIE[$name];
                $result = TRUE;
                break;
            }
            // Stop the search.
            break;
        case SQ_SERVER:
            if (isset($_SERVER[$name])) {
                // If a match is found, set the specified variable to the found
                // value, indicate a match, and stop the search.
                $value = $_SERVER[$name];
                $result = TRUE;
                break;
            }
            // Stop the search.
            break;
    }

    if ($result && $typecast) {
        // Only typecast if it's requested and a match is found. The default is
        // not to typecast, which will happen if a valid constant isn't
        // specified.
        switch ($typecast) {
            case SQ_TYPE_INT:
                // Typecast the value and stop.
                $value = (int) $value;
                break;
            case SQ_TYPE_STRING:
                // Typecast the value and stop.
                $value = (string) $value;
                break;
            case SQ_TYPE_BOOL:
                // Typecast the value and stop.
                $value = (bool) $value;
                break;
            case SQ_TYPE_BIGINT:
                // Typecast the value and stop.
                $value = (preg_match('/^[0-9]+$/', $value) ? $value : '0');
                break;
            default:
                // The default is to do nothing.
                break;
        }
    } else if (!$result && !is_null($default)) {
        // If no match is found and a default value is specified, set it.
        $value = $default;
    }

    // Return if a match was found or not.
    return $result;
}

/**
 * Get an immutable copy of a configuration variable if SquirrelMail
 * is in "secured configuration" mode.  This guarantees the caller 
 * gets a copy of the requested value as it is set in the main 
 * application configuration (including config_local overrides), and 
 * not what it might be after possibly having been modified by some 
 * other code (usually a plugin overriding configuration values for 
 * one reason or another).
 *
 * WARNING: Please use this function as little as possible, because 
 * every time it is called, it forcibly reloads the main configuration
 * file(s).
 *
 * Caller beware that this function will do nothing if SquirrelMail
 * is not in "secured configuration" mode per the $secured_config 
 * setting.
 *
 * @param string $var_name The name of the desired variable
 *
 * @return mixed The desired value
 *
 * @since 1.5.2
 *
 */
function get_secured_config_value($var_name) {

    static $return_values = array();

    // if we can avoid it, return values that have 
    // already been retrieved (so we don't have to
    // include the config file yet again)
    //
    if (isset($return_values[$var_name])) {
        return $return_values[$var_name];
    }


    // load site configuration
    //
    require(SM_PATH . 'config/config.php');

    // load local configuration overrides
    //
    if (file_exists(SM_PATH . 'config/config_local.php')) {
        require(SM_PATH . 'config/config_local.php');
    }

    // if SM isn't in "secured configuration" mode,
    // just return the desired value from the global scope
    // 
    if (!$secured_config) {
        global $$var_name;
        $return_values[$var_name] = $$var_name;
        return $$var_name;
    }

    // else we return what we got from the config file
    //
    $return_values[$var_name] = $$var_name;
    return $$var_name;

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

    global $base_uri, $_COOKIE, $_SESSION;

    if (isset($_COOKIE[session_name()]) && session_name()) {
        sqsetcookie(session_name(), $_COOKIE[session_name()], 1, $base_uri);

        /*
         * Make sure to kill /src and /src/ cookies, just in case there are
         * some left-over or malicious ones set in user's browser.
         * NB: Note that an attacker could try to plant a cookie for one
         *     of the /plugins/* directories.  Such cookies can block
         *     access to certain plugin pages, but they do not influence
         *     or fixate the $base_uri cookie, so we don't worry about
         *     trying to delete all of them here.
         */
        sqsetcookie(session_name(), $_COOKIE[session_name()], 1, $base_uri . 'src');
        sqsetcookie(session_name(), $_COOKIE[session_name()], 1, $base_uri . 'src/');
    }

    if (isset($_COOKIE['key']) && $_COOKIE['key']) sqsetcookie('key','SQMTRASH',1,$base_uri);

    /* Make sure new session id is generated on subsequent session_start() */
    unset($_COOKIE[session_name()]);
    unset($_GET[session_name()]);
    unset($_POST[session_name()]);

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
 * Update: (see #1685031) the session ID is left over after the
 * session is closed in some PHP setups; this function just becomes
 * a passthru to sqsession_start(), but leaving old code in for
 * edification.
 */
function sqsession_is_active() {
    //$sessid = session_id();
    //if ( empty( $sessid ) ) {
        sqsession_start();
    //}
}

/**
 * Function to start the session and store the cookie with the session_id as
 * HttpOnly cookie which means that the cookie isn't accessible by javascript
 * (IE6 only)
 * Note that as sqsession_is_active() no longer discriminates as to when 
 * it calls this function, session_start() has to have E_NOTICE suppression
 * (thus the @ sign).
 */
function sqsession_start() {
    global $base_uri;

    sq_call_function_suppress_errors('session_start');
    // was: @session_start();
    $session_id = session_id();

    // session_starts sets the sessionid cookie but without the httponly var
    // setting the cookie again sets the httponly cookie attribute
    //
    // need to check if headers have been sent, since sqsession_is_active()
    // has become just a passthru to this function, so the sqsetcookie()
    // below is called every time, even after headers have already been sent
    //
    if (!headers_sent())
       sqsetcookie(session_name(),$session_id,false,$base_uri);
}



/**
 * Set a cookie
 *
 * @param string  $sName     The name of the cookie.
 * @param string  $sValue    The value of the cookie.
 * @param int     $iExpire   The time the cookie expires. This is a Unix 
 *                           timestamp so is in number of seconds since 
 *                           the epoch.
 * @param string  $sPath     The path on the server in which the cookie 
 *                           will be available on.
 * @param string  $sDomain   The domain that the cookie is available.
 * @param boolean $bSecure   Indicates that the cookie should only be 
 *                           transmitted over a secure HTTPS connection.
 * @param boolean $bHttpOnly Disallow JS to access the cookie (IE6 only)
 * @param boolean $bReplace  Replace previous cookies with same name?
 *
 * @return void
 *
 * @since 1.4.16 and 1.5.1
 *
 */
function sqsetcookie($sName, $sValue='deleted', $iExpire=0, $sPath="", $sDomain="",
                     $bSecure=false, $bHttpOnly=true, $bReplace=false) {
 
    // if we have a secure connection then limit the cookies to https only.
    global $is_secure_connection;
    if ($sName && $is_secure_connection)
        $bSecure = true;

    // admin config can override the restriction of secure-only cookies
    global $only_secure_cookies;
    if (!$only_secure_cookies)
        $bSecure = false;

    if (false && check_php_version(5,2)) {
       // php 5 supports the httponly attribute in setcookie, but because setcookie seems a bit
       // broken we use the header function for php 5.2 as well. We might change that later.
       //setcookie($sName,$sValue,(int) $iExpire,$sPath,$sDomain,$bSecure,$bHttpOnly);
    } else {
        if (!empty($sDomain)) {
            // Fix the domain to accept domains with and without 'www.'.
            if (strtolower(substr($sDomain, 0, 4)) == 'www.')  $sDomain = substr($sDomain, 4);
            $sDomain = '.' . $sDomain;

            // Remove port information.
            $Port = strpos($sDomain, ':');
            if ($Port !== false)  $sDomain = substr($sDomain, 0, $Port);
        }
        if (!$sValue) $sValue = 'deleted';
        header('Set-Cookie: ' . rawurlencode($sName) . '=' . rawurlencode($sValue)
                            . (empty($iExpire) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $iExpire) . ' GMT')
                            . (empty($sPath) ? '' : '; path=' . $sPath)
                            . (empty($sDomain) ? '' : '; domain=' . $sDomain)
                            . (!$bSecure ? '' : '; secure')
                            . (!$bHttpOnly ? '' : '; HttpOnly'), $bReplace);
    }
}


/**
 * session_regenerate_id replacement for PHP < 4.3.2
 *
 * This code is borrowed from Gallery, session.php version 1.53.2.1
FIXME: I saw this code on php.net (in the manual); that's where it comes from originally, but I don't think we need it - it's just redundant to all the hard work we already did seeding the random number generator IMO.  I think we can just call to GenerateRandomString() and dump the rest.
 */
if (!function_exists('session_regenerate_id')) {

    function php_combined_lcg() {
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
            sqsetcookie(session_name(), session_id(), 0, $base_uri);
        }
        return TRUE;
    }
}


/**
 * php_self
 *
 * Attempts to determine the path and filename and any arguments
 * for the currently executing script.  This is usually found in
 * $_SERVER['REQUEST_URI'], but some environments may differ, so 
 * this function tries to standardize this value.
 *
 * Note that before SquirrelMail version 1.5.1, this function was
 * stored in function/strings.php.
 *
 * @since 1.2.3
 * @return string The path, filename and any arguments for the
 *                current script
 */
function php_self() {

    $request_uri = '';

    // first try $_SERVER['PHP_SELF'], which seems most reliable
    // (albeit it usually won't include the query string)
    //
    $request_uri = ''; 
    if (!sqgetGlobalVar('PHP_SELF', $request_uri, SQ_SERVER)
     || empty($request_uri)) { 

        // well, then let's try $_SERVER['REQUEST_URI']
        //
        $request_uri = '';
        if (!sqgetGlobalVar('REQUEST_URI', $request_uri, SQ_SERVER)
         || empty($request_uri)) { 

            // TODO: anyone have any other ideas?  maybe $_SERVER['SCRIPT_NAME']???
            //
            return '';
        }

    }

    // we may or may not have any query arguments, depending on 
    // which environment variable was used above, and the PHP
    // version, etc., so let's check for it now
    //
    $query_string = '';
    if (strpos($request_uri, '?') === FALSE
     && sqgetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER)
     && !empty($query_string)) {

        $request_uri .= '?' . $query_string;
    }   

    return $request_uri;

}


/**
 * Print variable
 *
 * sm_print_r($some_variable, [$some_other_variable [, ...]]);
 *
 * Debugging function - does the same as print_r, but makes sure special
 * characters are converted to htmlentities first.  This will allow
 * values like <some@email.address> to be displayed.
 * The output is wrapped in <<pre>> and <</pre>> tags.
 * Since 1.4.2 accepts unlimited number of arguments.
 * @since 1.4.1
 * @return void
 */
function sm_print_r() {
    ob_start();  // Buffer output
    foreach(func_get_args() as $var) {
        print_r($var);
        echo "\n";
        // php has get_class_methods function that can print class methods
        if (is_object($var)) {
            // get class methods if $var is object
            $aMethods=get_class_methods(get_class($var));
            // make sure that $aMethods is array and array is not empty
            if (is_array($aMethods) && $aMethods!=array()) {
                echo "Object methods:\n";
                foreach($aMethods as $method) {
                    echo '* ' . $method . "\n";
                }
            }
            echo "\n";
        }
    }
    $buffer = ob_get_contents(); // Grab the print_r output
    ob_end_clean();  // Silently discard the output & stop buffering
    print '<div align="left"><pre>';
    print htmlentities($buffer);
    print '</pre></div>';
}


/**
  * Sanitize a value using sm_encode_html_special_chars() or similar, but also
  * recursively run sm_encode_html_special_chars() (or similar) on array keys
  * and values.
  *
  * If $value is not a string or an array with strings in it,
  * the value is returned as is.
  *
  * @param mixed $value       The value to be sanitized.
  * @param mixed $quote_style Either boolean or an integer.  If it
  *                           is an integer, it must be the PHP
  *                           constant indicating if/how to escape
  *                           quotes: ENT_QUOTES, ENT_COMPAT, or
  *                           ENT_NOQUOTES.  If it is a boolean value,
  *                           it must be TRUE and thus indicates
  *                           that the only sanitizing to be done
  *                           herein is to replace single and double
  *                           quotes with &#039; and &quot;, no other
  *                           changes are made to $value.  If it is
  *                           boolean and FALSE, behavior reverts
  *                           to same as if the value was ENT_QUOTES
  *                           (OPTIONAL; default is ENT_QUOTES).
  *
  * @return mixed The sanitized value.
  *
  * @since 1.5.2
  *
  **/
function sq_htmlspecialchars($value, $quote_style=ENT_QUOTES) {

    if ($quote_style === FALSE) $quote_style = ENT_QUOTES;

    // array?  go recursive...
    //
    if (is_array($value)) {
        $return_array = array();
        foreach ($value as $key => $val) {
            $return_array[sq_htmlspecialchars($key, $quote_style)]
                = sq_htmlspecialchars($val, $quote_style);
        }
        return $return_array;

    // sanitize strings only
    //
    } else if (is_string($value)) {
        if ($quote_style === TRUE)
            return str_replace(array('\'', '"'), array('&#039;', '&quot;'), $value);
        else
            return sm_encode_html_special_chars($value, $quote_style);
    }

    // anything else gets returned with no changes
    //
    return $value;

}


/**
 * Detect whether or not we have a SSL secured (HTTPS) connection
 * connection to the browser
 *
 * It is thought to be so if you have 'SSLOptions +StdEnvVars'
 * in your Apache configuration,
 *     OR if you have HTTPS set to a non-empty value (except "off")
 *        in your HTTP_SERVER_VARS,
 *     OR if you have HTTP_X_FORWARDED_PROTO=https in your HTTP_SERVER_VARS,
 *     OR if you are on port 443.
 *
 * Note: HTTP_X_FORWARDED_PROTO could be sent from the client and
 *       therefore possibly spoofed/hackable.  Thus, SquirrelMail
 *       ignores such headers by default.  The administrator
 *       can tell SM to use such header values by setting
 *       $sq_ignore_http_x_forwarded_headers to boolean FALSE
 *       in config/config.php or by using config/conf.pl.
 *
 * Note: It is possible to run SSL on a port other than 443, and
 *       if that is the case, the administrator should set
 *       $sq_https_port in config/config.php or by using config/conf.pl.
 *
 * @return boolean TRUE if the current connection is SSL-encrypted;
 *                 FALSE otherwise.
 *
 * @since 1.4.17 and 1.5.2
 *
 */
function is_ssl_secured_connection()
{
    global $sq_ignore_http_x_forwarded_headers, $sq_https_port;
    $https_env_var = getenv('HTTPS');
    if ($sq_ignore_http_x_forwarded_headers
     || !sqgetGlobalVar('HTTP_X_FORWARDED_PROTO', $forwarded_proto, SQ_SERVER))
        $forwarded_proto = '';
    if (empty($sq_https_port)) // won't work with port 0 (zero)
       $sq_https_port = 443;
    if ((isset($https_env_var) && strcasecmp($https_env_var, 'on') === 0)
     || (sqgetGlobalVar('HTTPS', $https, SQ_SERVER) && !empty($https)
      && strcasecmp($https, 'off') !== 0)
     || (strcasecmp($forwarded_proto, 'https') === 0)
     || (sqgetGlobalVar('SERVER_PORT', $server_port, SQ_SERVER)
      && $server_port == $sq_https_port))
        return TRUE;
    return FALSE;
}


/**
 * Endeavor to detect what user and group PHP is currently
 * running as.  Probably only works in non-Windows environments.
 *
 * @return mixed Boolean FALSE is returned if something went wrong,
 *               otherwise an array is returned with the following
 *               elements:
 *                  uid    The current process' UID (integer)
 *                  euid   The current process' effective UID (integer)
 *                  gid    The current process' GID (integer)
 *                  egid   The current process' effective GID (integer)
 *                  name   The current process' name/handle (string)
 *                  ename  The current process' effective name/handle (string)
 *                  group  The current process' group name (string)
 *                  egroup The current process' effective group name (string)
 *               Note that some of these elements may have empty
 *               values, especially if they could not be determined.
 *
 * @since 1.5.2
 *
 */
function get_process_owner_info()
{
    if (!function_exists('posix_getuid'))
        return FALSE;

    $process_info['uid'] = posix_getuid();
    $process_info['euid'] = posix_geteuid();
    $process_info['gid'] = posix_getgid();
    $process_info['egid'] = posix_getegid();

    $user_info = posix_getpwuid($process_info['uid']);
    $euser_info = posix_getpwuid($process_info['euid']);
    $group_info = posix_getgrgid($process_info['gid']);
    $egroup_info = posix_getgrgid($process_info['egid']);

    $process_info['name'] = $user_info['name'];
    $process_info['ename'] = $euser_info['name'];
    $process_info['group'] = $user_info['name'];
    $process_info['egroup'] = $euser_info['name'];
    
    return $process_info;
}


