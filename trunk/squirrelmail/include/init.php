<?php

/**
 * init.php -- initialisation file
 *
 * File should be loaded in every file in src/ or plugins that occupate an entire frame
 *
 * @copyright &copy; 2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */





/**
 * SquirrelMail internal version number -- DO NOT CHANGE
 * $sm_internal_version = array (release, major, minor)
 */

$SQM_INTERNAL_VERSION = array(1,5,2);

/**
 * This is a development version so in order to track programmer mistakes we
 * set the error reporting to E_ALL
 */

error_reporting(E_ALL);


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


/**
 * calculate SM_PATH and calculate the base_uri
 * assumptions made: init.php is only called from plugins or from the src dir.
 * files in the plugin directory may not be part of a subdirectory called "src"
 *
 */
if (isset($_SERVER['SCRIPT_NAME'])) {
    $a = explode('/',$_SERVER['SCRIPT_NAME']);
} elseif (isset($HTTP_SERVER_VARS['SCRIPT_NAME'])) {
    $a = explode('/',$_SERVER['SCRIPT_NAME']);
}
$sSM_PATH = '';
for($i = count($a) -2;$i > -1; --$i) {
    $sSM_PATH .= '../';
    if ($a[$i] === 'src' || $a[$i] === 'plugins') {
        break;
    }
}

$base_uri = implode('/',array_slice($a,0,$i)). '/';

define('SM_PATH',$sSM_PATH);
define('SM_BASE_URI', $base_uri);
/**
 * global var $bInit is used to check if initialisation took place.
 * At this moment it's a workarounf for the include of addrbook_search_html
 * inside compose.php. If we found a better way then remove this. Do only use
 * this var if you know for sure a page can be called stand alone and be included
 * in another file.
 */
$bInit = true;

require(SM_PATH . 'functions/global.php');
require(SM_PATH . 'config/config.php');
require(SM_PATH . 'functions/plugin.php');
require(SM_PATH . 'include/constants.php');
require(SM_PATH . 'include/languages.php');

/**
 * If magic_quotes_runtime is on, SquirrelMail breaks in new and creative ways.
 * Force magic_quotes_runtime off.
 * tassium@squirrelmail.org - I put it here in the hopes that all SM code includes this.
 * If there's a better place, please let me know.
 */
ini_set('magic_quotes_runtime','0');


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

$PHP_SELF = php_self();

/**
 * Initialize the session
 */

/** set the name of the session cookie */
if (!isset($session_name) || !$session_name) {
    $session_name = 'SQMSESSID';
}

/**
 * if session.auto_start is On then close the session
 */ 
$session_autostart_name = session_name();
if ((isset($session_autostart_name) || $session_autostart_name == '') && 
     $session_autostart_name !== $session_name) {
    $cookiepath = ini_get('session.cookie_path');
    $cookiedomain = ini_get('session.cookie_domain');
    // reset the cookie
    setcookie($session_autostart_name,'',time() - 604800,$cookiepath,$cookiedomain);
    @session_destroy();
    session_write_close();
}    

/**
 * includes from classes stored in the session
 */
require(SM_PATH . 'class/mime.class.php');

ini_set('session.name' , $session_name);
session_set_cookie_params (0, $base_uri);
sqsession_is_active();

/**
 * Remove globalized session data in rg=on setups
 */
if ((bool) @ini_get('register_globals')) {
    foreach ($_SESSION as $key => $value) {
        unset($GLOBALS[$key]);
    }
}

sqsession_register($base_uri, SM_BASE_URI);

/**
 * SquirrelMail version number -- DO NOT CHANGE
 */
$version = '1.5.2 [CVS]';

/**
 * Retrieve the language cookie
 */
if (! sqgetGlobalVar('squirrelmail_language',$squirrelmail_language,SQ_COOKIE)) {
    $squirrelmail_language = '';
}


/**
 * @var $sInitlocation From where do we include.
 */
if (!isset($sInitLocation)) {
    $sInitLocation=NULL;
}

/**
 * MAIN PLUGIN LOADING CODE HERE
 */

/**
 * Include Compatibility plugin if available.
 */
if (file_exists(SM_PATH . 'plugins/compatibility/functions.php'))
    include_once(SM_PATH . 'plugins/compatibility/functions.php');
$squirrelmail_plugin_hooks = array();

/* On init, register all plugins configured for use. */
if (isset($plugins) && is_array($plugins)) {
    // turn on output buffering in order to prevent output of new lines
    ob_start();
    foreach ($plugins as $name) {
        use_plugin($name);
    }
    // get output and remove whitespace
    $output = trim(ob_get_contents());
    ob_end_clean();
    // if plugins output more than newlines and spacing, stop script execution.
    if (!empty($output)) {
        die($output);
    }
}


switch ($sInitLocation) {
    case 'style': session_write_close(); sqsetcookieflush(); break;
    case 'redirect':
        $prefs_backend = do_hook_function('prefs_backend');
        if (isset($prefs_backend) && !empty($prefs_backend) && file_exists(SM_PATH . $prefs_backend)) {
            require(SM_PATH . $prefs_backend);
        } elseif (isset($prefs_dsn) && !empty($prefs_dsn)) {
            require(SM_PATH . 'functions/db_prefs.php');
        } else {
            require(SM_PATH . 'functions/prefs.php');
            require(SM_PATH . 'functions/file_prefs.php');
        }
        //nobreak;
    case 'login':
        require(SM_PATH . 'functions/display_messages.php' );
        require(SM_PATH . 'functions/page_header.php');
        require(SM_PATH . 'functions/html.php');
        break;
    default:
        require(SM_PATH . 'functions/display_messages.php' );
        require(SM_PATH . 'functions/page_header.php');
        require(SM_PATH . 'functions/html.php');
        require(SM_PATH . 'functions/strings.php');


        /**
         * Check if we are logged in
         */
        require(SM_PATH . 'functions/auth.php');

        if ( !sqsession_is_registered('user_is_logged_in') ) {
            //  First we store some information in the new session to prevent
            //  information-loss.
            //
            $session_expired_post = $_POST;
            $session_expired_location = $PHP_SELF;
            if (!sqsession_is_registered('session_expired_post')) {
                sqsession_register($session_expired_post,'session_expired_post');
            }
            if (!sqsession_is_registered('session_expired_location')) {
                sqsession_register($session_expired_location,'session_expired_location');
            }
            // signout page will deal with users who aren't logged
            // in on its own; don't show error here
            //
            if (strpos($PHP_SELF, 'signout.php') !== FALSE) {
            return;
            }

            set_up_language($squirrelmail_language, true);
            logout_error( _("You must be logged in to access this page.") );
            exit;
        }

        sqgetGlobalVar('username',$username,SQ_SESSION);

        /**
         * Setting the prefs backend
         */
        sqgetGlobalVar('prefs_cache', $prefs_cache, SQ_SESSION );
        sqgetGlobalVar('prefs_are_cached', $prefs_are_cached, SQ_SESSION );

        if ( !sqsession_is_registered('prefs_are_cached') ||
            !isset( $prefs_cache) ||
            !is_array( $prefs_cache)) {
            $prefs_are_cached = false;
            $prefs_cache = false; //array();
        }

        $prefs_backend = do_hook_function('prefs_backend');
        if (isset($prefs_backend) && !empty($prefs_backend) && file_exists(SM_PATH . $prefs_backend)) {
            require(SM_PATH . $prefs_backend);
        } elseif (isset($prefs_dsn) && !empty($prefs_dsn)) {
            require(SM_PATH . 'functions/db_prefs.php');
        } else {
            require(SM_PATH . 'functions/prefs.php');
            require(SM_PATH . 'functions/file_prefs.php');
        }

        /**
         * initializing user settings
         */
        require(SM_PATH . 'include/load_prefs.php');


// i do not understand the frames language cookie story
        /**
         * We'll need this to later have a noframes version
         *
         * Check if the user has a language preference, but no cookie.
         * Send him a cookie with his language preference, if there is
         * such discrepancy.
         */
         $my_language = getPref($data_dir, $username, 'language');
         if ($my_language != $squirrelmail_language) {
             sqsetcookie('squirrelmail_language', $my_language, time()+2592000, $base_uri);
         }
// /dont understand

        /**
         * Set up the language.
         */
        $err=set_up_language(getPref($data_dir, $username, 'language'));
        /* this is the last cookie we set so flush it. */
        sqsetcookieflush();

        // Japanese translation used without mbstring support
        if ($err==2) {
            $sError =
                "<p>You need to have PHP installed with the multibyte string function \n".
                "enabled (using configure option --enable-mbstring).</p>\n".
                "<p>System assumed that you accidently switched to Japanese translation \n".
                "and reverted your language preference to English.</p>\n".
                "<p>Please refresh this page in order to use webmail.</p>\n";
            error_box($sError);
        }

        $timeZone = getPref($data_dir, $username, 'timezone');

        /* Check to see if we are allowed to set the TZ environment variable.
         * We are able to do this if ...
         *   safe_mode is disabled OR
         *   safe_mode_allowed_env_vars is empty (you are allowed to set any) OR
         *   safe_mode_allowed_env_vars contains TZ
         */
        $tzChangeAllowed = (!ini_get('safe_mode')) ||
                            !strcmp(ini_get('safe_mode_allowed_env_vars'),'') ||
                            preg_match('/^([\w_]+,)*TZ/', ini_get('safe_mode_allowed_env_vars'));

        if ( $timeZone != SMPREF_NONE && ($timeZone != "")
            && $tzChangeAllowed ) {

            // get time zone key, if strict or custom strict timezones are used
            if (isset($time_zone_type) &&
                ($time_zone_type == 1 || $time_zone_type == 3)) {
                /* load time zone functions */
                require(SM_PATH . 'include/timezones.php');
                $realTimeZone = sq_get_tz_key($timeZone);
            } else {
                $realTimeZone = $timeZone;
            }

            // set time zone
            if ($realTimeZone) {
                putenv("TZ=".$realTimeZone);
            }
        }
        break;
}

/**
 * Initialize the template object
 */
require(SM_PATH . 'class/template/template.class.php');
/*
 * $sTplDir is not initialized when a user is not logged in, so we will use
 * the config file defaults here.  If the neccesary variables are net set,
 * force a default value.
 */
$aTemplateSet = ( !isset($aTemplateSet) ? array() : $aTemplateSet );
$templateset_default = ( !isset($templateset_default) ? 0 : $templateset_default );

$sTplDir = ( !isset($aTemplateSet[$templateset_default]['PATH']) ?
             SM_PATH . 'templates/default/' :
             $aTemplateSet[$templateset_default]['PATH'] );
$oTemplate = new Template($sTplDir);

/**
 * Initialize our custom error handler object
 */
require(SM_PATH . 'class/error.class.php');
$oErrorHandler = new ErrorHandler($oTemplate,'error_message.tpl');

/**
 * Activate custom error handling
 */
if (version_compare(PHP_VERSION, "4.3.0", ">=")) {
    $oldErrorHandler = set_error_handler(array($oErrorHandler, 'SquirrelMailErrorhandler'));
} else {
    $oldErrorHandler = set_error_handler('SquirrelMailErrorhandler');
}

/**
 * Javascript support detection function
 * @param boolean $reset recheck javascript support if set to true.
 * @return integer SMPREF_JS_ON or SMPREF_JS_OFF ({@see functions/constants.php})
 * @since 1.5.1
 */

function checkForJavascript($reset = FALSE) {
  global $data_dir, $username, $javascript_on, $javascript_setting;

  if ( !$reset && sqGetGlobalVar('javascript_on', $javascript_on, SQ_SESSION) )
    return $javascript_on;

  if ( $reset || !isset($javascript_setting) )
    $javascript_setting = getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);

  if ( !sqGetGlobalVar('new_js_autodetect_results', $js_autodetect_results) &&
       !sqGetGlobalVar('js_autodetect_results', $js_autodetect_results) )
    $js_autodetect_results = SMPREF_JS_OFF;

  if ( $javascript_setting == SMPREF_JS_AUTODETECT )
    $javascript_on = $js_autodetect_results;
  else
    $javascript_on = $javascript_setting;

  sqsession_register($javascript_on, 'javascript_on');
  return $javascript_on;
}

function sqm_baseuri() {
    global $base_uri;
    return $base_uri;
}