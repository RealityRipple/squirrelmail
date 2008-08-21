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
 * This is a development version so in order to track programmer mistakes we
 * set the error reporting to E_ALL
FIXME: disabling this for now, because we now have $sm_debug_mode, but the problem with that is that we don't know what it will be until we have loaded the config file, a good 175 lines below after several important files have been included, etc.  For now, we'll trust that developers have turned on E_ALL in php.ini anyway, but this can be uncommented if not.
 */
//error_reporting(E_ALL);



/**
 * Make sure we have a page name
 *
 */
if ( !defined('PAGE_NAME') ) define('PAGE_NAME', NULL);


/**
 * If register_globals are on, unregister globals.
 * Second test covers boolean set as string (php_value register_globals off).
 */
if ((bool) ini_get('register_globals') &&
    strtolower(ini_get('register_globals'))!='off') {
    /**
     * Remove all globals that are not reserved by PHP
     * 'value' and 'key' are used by foreach. Don't unset them inside foreach.
     */
    foreach ($GLOBALS as $key => $value) {
        switch($key) {
        case 'HTTP_POST_VARS':
        case '_POST':
        case 'HTTP_GET_VARS':
        case '_GET':
        case 'HTTP_COOKIE_VARS':
        case '_COOKIE':
        case 'HTTP_SERVER_VARS':
        case '_SERVER':
        case 'HTTP_ENV_VARS':
        case '_ENV':
        case 'HTTP_POST_FILES':
        case '_FILES':
        case '_REQUEST':
        case 'HTTP_SESSION_VARS':
        case '_SESSION':
        case 'GLOBALS':
        case 'key':
        case 'value':
            break;
        default:
            unset($GLOBALS[$key]);
        }
    }
    // Unset variables used in foreach
    unset($GLOBALS['key']);
    unset($GLOBALS['value']);
}

/**
 * Used as a dummy value, e.g., for passing as an empty
 * hook argument (where the value is passed by reference,
 * and therefore NULL itself is not acceptable).
 */
global $null;
$null = NULL;

/**
 * [#1518885] session.use_cookies = off breaks SquirrelMail
 *
 * When session cookies are not used, all http redirects, meta refreshes,
 * src/download.php and javascript URLs are broken. Setting must be set
 * before session is started.
 */
if (!(bool)ini_get('session.use_cookies') ||
    ini_get('session.use_cookies') == 'off') {
    ini_set('session.use_cookies','1');
}

/**
 * Initialize seed of random number generator.
 * We use a number of things to randomize input: current time in ms,
 * info about the remote client, info about the current process, the
 * randomness of uniqid and stat of the current file.
 *
 * We seed this here only once per init, not only to save cycles
 * but also to make the result of mt_rand more random (it now also
 * depends on the number of times mt_rand was called before in this
 * execution.
 */
$seed = microtime() . $_SERVER['REMOTE_PORT'] . $_SERVER['REMOTE_ADDR'] . getmypid();

if (function_exists('getrusage')) {
    /* Avoid warnings with Win32 */
    $dat = @getrusage();
    if (isset($dat) && is_array($dat)) { $seed .= implode('', $dat); }
}

if(!empty($_SERVER['UNIQUE_ID'])) {
    $seed .= $_SERVER['UNIQUE_ID'];
}

$seed .= uniqid(mt_rand(),TRUE);
$seed .= implode( '', stat( __FILE__) );

/** PHP 4.2 and up don't require seeding, but their used seed algorithm
 *  is of questionable quality, so we keep doing it ourselves. */
mt_srand(hexdec(md5($seed)));

/**
 * calculate SM_PATH and calculate the base_uri
 * assumptions made: init.php is only called from plugins or from the src dir.
 * files in the plugin directory may not be part of a subdirectory called "src"
 *
 */
if (isset($_SERVER['SCRIPT_NAME'])) {
    $a = explode('/', $_SERVER['SCRIPT_NAME']);
} elseif (isset($HTTP_SERVER_VARS['SCRIPT_NAME'])) {
    $a = explode('/', $HTTP_SERVER_VARS['SCRIPT_NAME']);
} else {
    $error = 'Unable to detect script environment. Please test your PHP '
           . 'settings and send your PHP core configuration, $_SERVER and '
           . '$HTTP_SERVER_VARS contents to the SquirrelMail developers.';
    die($error);
}
$sSM_PATH = '';
for($i = count($a) -2; $i > -1; --$i) {
    $sSM_PATH .= '../';
    if ($a[$i] === 'src' || $a[$i] === 'plugins') {
        break;
    }
}

$base_uri = implode('/', array_slice($a, 0, $i)). '/';

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

/**
 * This theme as a failsafe if no themes were found, or if we error
 * out before anything could be initialised.
 */
$color = array();
$color[0]  = '#DCDCDC';  /* light gray    TitleBar               */
$color[1]  = '#800000';  /* red                                  */
$color[2]  = '#CC0000';  /* light red     Warning/Error Messages */
$color[3]  = '#A0B8C8';  /* green-blue    Left Bar Background    */
$color[4]  = '#FFFFFF';  /* white         Normal Background      */
$color[5]  = '#FFFFCC';  /* light yellow  Table Headers          */
$color[6]  = '#000000';  /* black         Text on left bar       */
$color[7]  = '#0000CC';  /* blue          Links                  */
$color[8]  = '#000000';  /* black         Normal text            */
$color[9]  = '#ABABAB';  /* mid-gray      Darker version of #0   */
$color[10] = '#666666';  /* dark gray     Darker version of #9   */
$color[11] = '#770000';  /* dark red      Special Folders color  */
$color[12] = '#EDEDED';
$color[13] = '#800000';  /* (dark red)    Color for quoted text -- > 1 quote */
$color[14] = '#ff0000';  /* (red)         Color for quoted text -- >> 2 or more */
$color[15] = '#002266';  /* (dark blue)   Unselectable folders */
$color[16] = '#ff9933';  /* (orange)      Highlight color */

require(SM_PATH . 'include/constants.php');
require(SM_PATH . 'functions/global.php');
require(SM_PATH . 'functions/strings.php');
require(SM_PATH . 'functions/arrays.php');

/* load default configuration */
require(SM_PATH . 'config/config_default.php');
/* reset arrays in default configuration */
$ldap_server = array();
$plugins = array();
$fontsets = array();
$aTemplateSet = array();
$aTemplateSet[0]['ID'] = 'default';
$aTemplateSet[0]['NAME'] = 'Default';

/* load site configuration */
require(SM_PATH . 'config/config.php');
/* load local configuration overrides */
if (file_exists(SM_PATH . 'config/config_local.php')) {
    require(SM_PATH . 'config/config_local.php');
}


/**
 * Set PHP error reporting level based on the SquirrelMail debug mode
 */
$error_level = 0;
if ($sm_debug_mode & SM_DEBUG_MODE_SIMPLE)
    $error_level |= E_ERROR;
if ($sm_debug_mode & SM_DEBUG_MODE_MODERATE
 || $sm_debug_mode & SM_DEBUG_MODE_ADVANCED)
    $error_level |= E_ALL;
if ($sm_debug_mode & SM_DEBUG_MODE_STRICT)
    $error_level |= E_STRICT;
error_reporting($error_level);


require(SM_PATH . 'functions/plugin.php');
require(SM_PATH . 'include/languages.php');
require(SM_PATH . 'class/template/Template.class.php');
require(SM_PATH . 'class/error.class.php');

/**
 * If magic_quotes_runtime is on, SquirrelMail breaks in new and creative ways.
 * Force magic_quotes_runtime off.
 * tassium@squirrelmail.org - I put it here in the hopes that all SM code includes this.
 * If there's a better place, please let me know.
 */
ini_set('magic_quotes_runtime','0');


/* if running with magic_quotes_gpc then strip the slashes
   from POST and GET global arrays */
if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
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
 * When session.auto_start is On we want to destroy/close the session
 */
$sSessionAutostartName = session_name();
$sCookiePath = null;
if (isset($sSessionAutostartName) && $sSessionAutostartName !== $session_name) {
    $sCookiePath = ini_get('session.cookie_path');
    $sCookieDomain = ini_get('session.cookie_domain');
    // reset the cookie
    setcookie($sSessionAutostartName,'',time() - 604800,$sCookiePath,$sCookieDomain);
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
 * When on login page, have to reset the user session, making
 * sure to save session restore data first
 */
if (PAGE_NAME == 'login') {
    if (!sqGetGlobalVar('session_expired_post', $sep, SQ_SESSION))
        $sep = '';
    if (!sqGetGlobalVar('session_expired_location', $sel, SQ_SESSION))
        $sel = '';
    sqsession_destroy();
    session_write_close();

    /**
     * in some rare instances, the session seems to stick
     * around even after destroying it (!!), so if it does,
     * we'll manually flatten the $_SESSION data
     */
    if (!empty($_SESSION))
        $_SESSION = array();

    /**
     * Allow administrators to define custom session handlers
     * for SquirrelMail without needing to change anything in
     * php.ini (application-level).
     *
     * In config_local.php, admin needs to put:
     *
     *     $custom_session_handlers = array(
     *         'my_open_handler',
     *         'my_close_handler',
     *         'my_read_handler',
     *         'my_write_handler',
     *         'my_destroy_handler',
     *         'my_gc_handler',
     *     );
     *     session_module_name('user');
     *     session_set_save_handler(
     *         $custom_session_handlers[0],
     *         $custom_session_handlers[1],
     *         $custom_session_handlers[2],
     *         $custom_session_handlers[3],
     *         $custom_session_handlers[4],
     *         $custom_session_handlers[5]
     *     );
     *
     * We need to replicate that code once here because PHP has
     * long had a bug that resets the session handler mechanism
     * when the session data is also destroyed.  Because of this
     * bug, even administrators who define custom session handlers
     * via a PHP pre-load defined in php.ini (auto_prepend_file)
     * will still need to define the $custom_session_handlers array
     * in config_local.php.
     */
    global $custom_session_handlers;
    if (!empty($custom_session_handlers)) {
        $open    = $custom_session_handlers[0];
        $close   = $custom_session_handlers[1];
        $read    = $custom_session_handlers[2];
        $write   = $custom_session_handlers[3];
        $destroy = $custom_session_handlers[4];
        $gc      = $custom_session_handlers[5];
        session_module_name('user');
        session_set_save_handler($open, $close, $read, $write, $destroy, $gc);
    }

    sqsession_is_active();
    session_regenerate_id();

    // put session restore data back into session if necessary
    if (!empty($sel)) {
        sqsession_register($sel, 'session_expired_location');
        if (!empty($sep))
            sqsession_register($sep, 'session_expired_post');
    }
}

/**
 * SquirrelMail internal version number -- DO NOT CHANGE
 * $sm_internal_version = array (release, major, minor)
 */
$SQM_INTERNAL_VERSION = explode('.', SM_VERSION, 3);
$SQM_INTERNAL_VERSION[2] = intval($SQM_INTERNAL_VERSION[2]);


/* load prefs system; even when user not logged in, should be OK to do this here */
require(SM_PATH . 'functions/prefs.php');


/* if plugins are disabled only for one user and
 * the current user is NOT that user, turn them
 * back on
 */
sqgetGlobalVar('username',$username,SQ_SESSION);
if ($disable_plugins && !empty($disable_plugins_user)
 && $username != $disable_plugins_user) {
    $disable_plugins = false;
}


/* remove all plugins if they are disabled */
if ($disable_plugins) {
   $plugins = array();
}


/**
 * Include Compatibility plugin if available.
 */
if (!$disable_plugins && file_exists(SM_PATH . 'plugins/compatibility/functions.php'))
    include_once(SM_PATH . 'plugins/compatibility/functions.php');


/**
 * MAIN PLUGIN LOADING CODE HERE
 * On init, we no longer need to load all plugin setup files.
 * Now, we load the statically generated hook registrations here
 * and let the hook calls include only the plugins needed.
 */
$squirrelmail_plugin_hooks = array();
if (!$disable_plugins && file_exists(SM_PATH . 'config/plugin_hooks.php')) {
//FIXME: if we keep the plugin hooks array static like this, it seems like we should also keep the template files list in a static file too (when a new user session is started or the template set is changed, the code will dynamically iterate through the directory heirarchy of the template directory and catalog all the template files therein (and store the "catalog" in PHP session) -- instead, we could do that once at config-time and keep that static so SM can just include the file just like the line below)
    require(SM_PATH . 'config/plugin_hooks.php');
}


/**
 * Plugin authors note that the "config_override" hook used to be
 * executed here, but please adapt your plugin to use this "prefs_backend" 
 * hook instead, making sure that it does NOT return anything, since
 * doing so will interfere with proper prefs system functionality.
 * Of course, otherwise, this hook may be used to do any configuration
 * overrides as needed, as well as set up a custom preferences backend.
 */
$prefs_backend = do_hook('prefs_backend', $null);
if (isset($prefs_backend) && !empty($prefs_backend) && file_exists(SM_PATH . $prefs_backend)) {
    require(SM_PATH . $prefs_backend);
} elseif (isset($prefs_dsn) && !empty($prefs_dsn)) {
    require(SM_PATH . 'functions/db_prefs.php');
} else {
    require(SM_PATH . 'functions/file_prefs.php');
}



/**
 * DISABLED.
 * Remove globalized session data in rg=on setups
 *
 * Code can be utilized when session is started, but data is not loaded.
 * We have already loaded configuration and other important vars. Can't
 * clean session globals here, beside, the cleanout of globals at the
 * top of this file will have removed anything this code would find anyway.
if ((bool) @ini_get('register_globals') &&
    strtolower(ini_get('register_globals'))!='off') {
    foreach ($_SESSION as $key => $value) {
        unset($GLOBALS[$key]);
    }
}
*/

sqsession_register(SM_BASE_URI,'base_uri');

/**
 * Retrieve the language cookie
 */
if (! sqgetGlobalVar('squirrelmail_language',$squirrelmail_language,SQ_COOKIE)) {
    $squirrelmail_language = '';
}


/**
 * Do something special for some pages. This is based on the PAGE_NAME constant
 * set at the top of every page.
 */
switch (PAGE_NAME) {
    case 'style':

        // need to get the right template set up
        //
        sqGetGlobalVar('templateid', $templateid, SQ_GET);

        // sanitize just in case...
        //
        $templateid = preg_replace('/(\.\.\/){1,}/', '', $templateid);

        // make sure given template actually is available
        //
        $found_templateset = false;
        for ($i = 0; $i < count($aTemplateSet); ++$i) {
            if ($aTemplateSet[$i]['ID'] == $templateid) {
                $found_templateset = true;
                break;
            }
        }

// FIXME: do we need/want to check here for actual (physical) presence of template sets?
        // selected template not available, fall back to default template
        //
        if (!$found_templateset) {
            $sTemplateID = Template::get_default_template_set();
        } else {
            $sTemplateID = $templateid;
        }

        session_write_close();
        break;

    case 'mailto':
        // nothing to do
        break;

    case 'redirect':
        require(SM_PATH . 'functions/auth.php');
        //nobreak;

    case 'login':
        require(SM_PATH . 'functions/display_messages.php' );
        require(SM_PATH . 'functions/page_header.php');
        require(SM_PATH . 'functions/html.php');

        // reset template file cache
        //
        $sTemplateID = Template::get_default_template_set();
        Template::cache_template_file_hierarchy(TRUE);

        /**
         * Make sure icon variables are setup for the login page.
         */
        $icon_theme = $icon_themes[$icon_theme_def]['PATH'];
        /*
         * NOTE: The $icon_theme_path var should contain the path to the icon
         *       theme to use.  If the admin has disabled icons, or the user has
         *       set the icon theme to "None," no icons will be used.
         */
        $icon_theme_path = (!$use_icons || $icon_theme=='none') ? NULL : ($icon_theme == 'template' ? SM_PATH . Template::calculate_template_images_directory($sTemplateID) : $icon_theme);

        /**
         * cleanup old cookies with a cookie path the same as the standard php.ini
         * cookie path. All previous SquirrelMail version used the standard php.ini
         * cookie path for storing the session name. That behaviour changed.
         */
        if ($sCookiePath !== SM_BASE_URI) {
            /**
             * do not delete the standard sessions with session.name is i.e. PHPSESSID
             * because they probably belong to other php apps
             */
            if (ini_get('session.name') !== $sSessionAutostartName) {
                //  This does not work. Sometimes the cookie with SQSESSID=deleted and path /
                // is picked up in webmail.php => login will fail
                //sqsetcookie(ini_get('session.name'),'',0,$sCookiePath);
            }
        }
        break;
    default:
        require(SM_PATH . 'functions/display_messages.php' );
        require(SM_PATH . 'functions/page_header.php');
        require(SM_PATH . 'functions/html.php');


        /**
         * Check if we are logged in
         */
        require(SM_PATH . 'functions/auth.php');

        if ( !sqsession_is_registered('user_is_logged_in') ) {

            // use $message to indicate what logout text the user
            // will see... if 0, typical "You must be logged in"
            // if 1, information that the user session was saved
            // and will be resumed after (re)login
            //
            $message = 0;

            //  First we store some information in the new session to prevent
            //  information-loss.
            //
            $session_expired_post = $_POST;
            $session_expired_location = PAGE_NAME;
            if (!sqsession_is_registered('session_expired_post')) {
                sqsession_register($session_expired_post,'session_expired_post');
            }
            if (!sqsession_is_registered('session_expired_location')) {
                sqsession_register($session_expired_location,'session_expired_location');
                if ($session_expired_location == 'compose')
                    $message = 1;
            }
            // signout page will deal with users who aren't logged
            // in on its own; don't show error here
            //
            if ( PAGE_NAME == 'signout' ) {
                return;
            }

            /**
             * Initialize the template object (logout_error uses it)
             */
            /*
             * $sTemplateID is not initialized when a user is not logged in, so we
             * will use the config file defaults here.  If the neccesary variables
             * are net set, force a default value.
             */
            $sTemplateID = Template::get_default_template_set();
            $oTemplate = Template::construct_template($sTemplateID);

            set_up_language($squirrelmail_language, true);
            if (!$message)
                logout_error( _("You must be logged in to access this page.") );
            else
                logout_error( _("Your session has expired, but will be resumed after logging in again.") );
            exit;
        }

        sqgetGlobalVar('authz',$authz,SQ_SESSION);

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

        // Japanese translation used without mbstring support
        if ($err==2) {
            $sError = "<p>Your administrator needs to have PHP installed with the multibyte string extension enabled (using configure option --enable-mbstring).</p>\n"
                    . "<p>This system has assumed that you accidently switched to Japanese and has reverted your language preference to English.</p>\n"
                    . "<p>Please refresh this page in order to continue using your webmail.</p>\n";
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

        /**
         * php 5.1.0 added time zone functions. Set time zone with them in order
         * to prevent E_STRICT notices and allow time zone modifications in safe_mode.
         */
        if (function_exists('date_default_timezone_set')) {
            if ($timeZone != SMPREF_NONE && $timeZone != "") {
                date_default_timezone_set($timeZone);
            } else {
                // interface runs on server's time zone. Remove php E_STRICT complains
                $default_timezone = @date_default_timezone_get();
                date_default_timezone_set($default_timezone);
            }
        }
        break;
}

/*
 * $sTemplateID is not initialized when a user is not logged in, so we
 * will use the config file defaults here.  If the neccesary variables
 * are not set, force a default value.
 *
 * If the user is logged in, $sTemplateID will be set in load_prefs.php,
 * so we shouldn't change it here.
 */
if (!isset($sTemplateID)) {
    $sTemplateID = Template::get_default_template_set();
    $icon_theme_path = !$use_icons ? NULL : Template::calculate_template_images_directory($sTemplateID);
}

// template object may have already been constructed in load_prefs.php
//
if (empty($oTemplate)) {
    $oTemplate = Template::construct_template($sTemplateID);
}

// We want some variables to always be available to the template
//
$oTemplate->assign('javascript_on', 
    (sqGetGlobalVar('user_is_logged_in', $user_is_logged_in, SQ_SESSION)
     ?  checkForJavascript() : 0));
$oTemplate->assign('base_uri', sqm_baseuri());
$always_include = array('sTemplateID', 'icon_theme_path');
foreach ($always_include as $var) {
    $oTemplate->assign($var, (isset($$var) ? $$var : NULL));
}

// A few output elements are used often, so just get them once here
//
$nbsp = $oTemplate->fetch('non_breaking_space.tpl');
$br = $oTemplate->fetch('line_break.tpl');

/**
 * Initialize our custom error handler object
 */
$oErrorHandler = new ErrorHandler($oTemplate,'error_message.tpl');

/**
 * Activate custom error handling
 */
if (version_compare(PHP_VERSION, "4.3.0", ">=")) {
    $oldErrorHandler = set_error_handler(array($oErrorHandler, 'SquirrelMailErrorhandler'));
} else {
    $oldErrorHandler = set_error_handler('SquirrelMailErrorhandler');
}


// ============================================================================
// ================= End of Live Code, Beginning of Functions ================= 
// ============================================================================


/**
 * Javascript support detection function
 * @param boolean $reset recheck javascript support if set to true.
 * @return integer SMPREF_JS_ON or SMPREF_JS_OFF ({@see include/constants.php})
 * @since 1.5.1
 */
function checkForJavascript($reset = FALSE) {
  global $data_dir, $username, $javascript_on, $javascript_setting;

  if ( !$reset && sqGetGlobalVar('javascript_on', $javascript_on, SQ_SESSION) )
    return $javascript_on;

  $user_is_logged_in = FALSE;
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
