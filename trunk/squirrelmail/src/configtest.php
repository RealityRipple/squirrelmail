<?php

/**
 * SquirrelMail configtest script
 *
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage config
 */

/************************************************************
 * NOTE: you do not need to change this script!             *
 * If it throws errors you need to adjust your config.      *
 ************************************************************/

/** This is the configtest page */
define('PAGE_NAME', 'configtest');

// This script could really use some restructuring as it has grown quite rapidly
// but is not very 'clean'. Feel free to get some structure into this thing.

// force verbose error reporting and turn on display of errors, but not before
// getting their original values
$php_display_errors_original_value = ini_get('display_errors');
$php_error_reporting_original_value = ini_get('error_reporting');
error_reporting(E_ALL);
ini_set('display_errors',1);

/** Blockcopy from init.php. Cleans globals. */
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
 * Displays error messages and warnings
 * @param string $str message
 * @param boolean $fatal fatal error or only warning
 */
function do_err($str, $fatal = TRUE) {
    global $IND, $warnings;
    $level = $fatal ? 'FATAL ERROR:' : 'WARNING:';
    echo '<p>'.$IND.'<font color="red"><b>' . $level . '</b></font> ' .$str. "</p>\n";
    if($fatal) {
        echo '</body></html>';
        exit;
    } else {
        $warnings++;
    }
}

ob_implicit_flush();
/** @ignore */
define('SM_PATH', '../');
/** load minimal function set */
require(SM_PATH . 'include/constants.php');
require(SM_PATH . 'functions/global.php');
require(SM_PATH . 'functions/strings.php');
require(SM_PATH . 'functions/files.php');
$SQM_INTERNAL_VERSION = explode('.', SM_VERSION, 3);
$SQM_INTERNAL_VERSION[2] = intval($SQM_INTERNAL_VERSION[2]);

/** set default value in order to block remote access */
$allow_remote_configtest=false;

/** Load all configuration files before output begins */

/* load default configuration */
require(SM_PATH . 'config/config_default.php');
/* reset arrays in default configuration */
$ldap_server = array();
$plugins = array();
$fontsets = array();
$theme = array();
$theme[0]['PATH'] = SM_PATH . 'themes/default_theme.php';
$theme[0]['NAME'] = 'Default';
$aTemplateSet = array();
$aTemplateSet[0]['ID'] = 'default';
$aTemplateSet[0]['NAME'] = 'Default';
/* load site configuration */
if (file_exists(SM_PATH . 'config/config.php')) {
    require(SM_PATH . 'config/config.php');
}
/* load local configuration overrides */
if (file_exists(SM_PATH . 'config/config_local.php')) {
    require(SM_PATH . 'config/config_local.php');
}

sqGetGlobalVar('REMOTE_ADDR',$client_ip,SQ_SERVER);
sqGetGlobalVar('SERVER_ADDR',$server_ip,SQ_SERVER);

/**
 * Include Compatibility plugin if available.
 */
if (!$disable_plugins && file_exists(SM_PATH . 'plugins/compatibility/functions.php'))
    include_once(SM_PATH . 'plugins/compatibility/functions.php');

/** Load plugins */
global $disable_plugins;
$squirrelmail_plugin_hooks = array();
if (!$disable_plugins && file_exists(SM_PATH . 'config/plugin_hooks.php')) {
    require(SM_PATH . 'config/plugin_hooks.php');
}

/** Warning counter */
$warnings = 0;

/** indent */
$IND = str_repeat('&nbsp;',4);

/**
 * get_location starts session and must be run before output is started.
 */
$test_location = get_location();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
  <meta name="robots" content="noindex,nofollow">
  <title>SquirrelMail configtest</title>
</head>
<body>
<h1>SquirrelMail configtest</h1>

<p>This script will try to check some aspects of your SquirrelMail configuration
and point you to errors whereever it can find them. You need to go run <tt>conf.pl</tt>
in the <tt>config/</tt> directory first before you run this script.</p>

<?php

$included = array_map('basename', get_included_files() );
if(!in_array('config.php', $included)) {
    if(!file_exists(SM_PATH . 'config/config.php')) {
        do_err('Config file '.SM_PATH . 'config/config.php does not exist!<br />'.
                'You need to run <tt>conf.pl</tt> first.');
    }
    do_err('Could not read '.SM_PATH.'config/config.php! Check file permissions.');
}
if(!in_array('strings.php', $included)) {
    do_err('Could not include '.SM_PATH.'functions/strings.php!<br />'.
            'Check permissions on that file.');
}

/* Block remote use of script */
if (! $allow_remote_configtest) {

    if ((! isset($client_ip) || $client_ip!='127.0.0.1') &&
            (! isset($client_ip) || ! isset($server_ip) || $client_ip!=$server_ip)) {
        do_err('Enable "Allow remote configtest" option in squirrelmail configuration in order to use this script.');
    }
}

echo "<p><table>\n<tr><td>SquirrelMail version:</td><td><b>" . SM_VERSION . "</b></td></tr>\n" .
    '<tr><td>Config file version:</td><td><b>' . $config_version . "</b></td></tr>\n" .
    '<tr><td>Config file last modified:</td><td><b>' .
    date ('d F Y H:i:s', filemtime(SM_PATH . 'config/config.php')) .
    "</b></td></tr>\n</table>\n</p>\n\n";

/* check $config_version */
if ($config_version!='1.5.0') {
    do_err('Configuration file version does not match required version. Please update your configuration file.');
}


/* checking PHP specs */

echo "Checking PHP configuration...<br />\n";

if(!check_php_version(4,1,0)) {
    do_err('Insufficient PHP version: '. PHP_VERSION . '! Minimum required: 4.1.0');
}

echo $IND . 'PHP version ' . PHP_VERSION . ' OK. (You have: ' . phpversion() . ". Minimum: 4.1.0)<br />\n";

// try to determine information about the user and group the web server is running as
//
$webOwnerID = 'N/A';
$webOwnerInfo = array('name' => 'N/A');
if (function_exists('posix_getuid'))
    $webOwnerID = posix_getuid();
if ($webOwnerID === FALSE)
    $webOwnerID = 'N/A';
if ($webOwnerID !== 'N/A' && function_exists('posix_getpwuid'))
    $webOwnerInfo = posix_getpwuid($webOwnerID);
if (!$webOwnerInfo)
    $webOwnerInfo = array('name' => 'N/A');
$webGroupID = 'N/A';
$webGroupInfo = array('name' => 'N/A');
if (function_exists('posix_getgid'))
    $webGroupID = posix_getgid();
if ($webGroupID === FALSE)
    $webGroupID = 'N/A';
if ($webGroupID !== 'N/A' && function_exists('posix_getgrgid'))
    $webGroupInfo = posix_getgrgid($webGroupID);
if (!$webGroupInfo)
    $webGroupInfo = array('name' => 'N/A');

echo $IND . 'Running as ' . $webOwnerInfo['name'] . '(' . $webOwnerID
          . ') / ' . $webGroupInfo['name'] . '(' . $webGroupID . ")<br />\n";

echo $IND . 'display_errors: ' . $php_display_errors_original_value . " (overridden with 1 for this page only)<br />\n";

echo $IND . 'error_reporting: ' . $php_error_reporting_original_value . " (overridden with " . E_ALL . " for this page only)<br />\n";

$safe_mode = ini_get('safe_mode');
if ($safe_mode) {
    //FIXME: should show that safe_mode is off when it is (this only shows the safe_mode setting when it's on) (also might be generally helpful to show things like open_basedir, too or even add phpinfo() output or a link to another script that has phpinfo()
    echo $IND . 'safe_mode: ' . $safe_mode;
    if (empty($prefs_dsn) || empty($addrbook_dsn))
        echo ' (<font color="red">double check data and attachment directory ownership, etc!</font>)';
    if (!empty($addrbook_dsn) || !empty($prefs_dsn) || !empty($addrbook_global_dsn))
        echo ' (<font color="red">does PHP have access to database interface?</font>)';
    echo "<br />\n";
    $safe_mode_exec_dir = ini_get('safe_mode_exec_dir');
    echo $IND . 'safe_mode_exec_dir: ' . $safe_mode_exec_dir . "<br />\n";
}

/* register_globals check: test for boolean false and any string that is not equal to 'off' */

if ((bool) ini_get('register_globals') &&
    strtolower(ini_get('register_globals'))!='off') {
    do_err('You have register_globals turned on. This is not an error, but it CAN be a security hazard. Consider turning register_globals off.', false);
}


/* variables_order check */

// FIXME(?): Hmm, how do we distinguish between when an ini setting is not available (ini_set() returns empty string) and when the administrator set the value to an empty string? The latter is sure to be highly rare, so for now, just assume that empty value means the setting isn't even available (could also check PHP version when this setting was implemented) although, we'll also warn the user if it is empty, with a non-fatal error
$variables_order = strtoupper(ini_get('variables_order'));
if (empty($variables_order))
    do_err('Your variables_order setting seems to be empty. Make sure it is undefined in any PHP ini files, .htaccess files, etc. and not specifically set to an empty value or SquirrelMail may not function correctly', false);
else if (strpos($variables_order, 'G') === FALSE
 || strpos($variables_order, 'P') === FALSE
 || strpos($variables_order, 'C') === FALSE
 || strpos($variables_order, 'S') === FALSE) {
    do_err('Your variables_order setting is insufficient for SquirrelMail to function. It needs at least "GPCS", but you have it set to "' . sm_encode_html_special_chars($variables_order) . '"', true);
} else {
    echo $IND . "variables_order OK: $variables_order.<br />\n";
}


/* gpc_order check (removed from PHP as of v5.0) */

if (!check_php_version(5)) {
    // FIXME(?): Hmm, how do we distinguish between when an ini setting is not available (ini_set() returns empty string) and when the administrator set the value to an empty string? The latter is sure to be highly rare, so for now, just assume that empty value means the setting isn't even available (could also check PHP version when this setting was implemented) although, we'll also warn the user if it is empty, with a non-fatal error
    $gpc_order = strtoupper(ini_get('gpc_order'));
    if (empty($gpc_order))
        do_err('Your gpc_order setting seems to be empty. Make sure it is undefined in any PHP ini files, .htaccess files, etc. and not specifically set to an empty value or SquirrelMail may not function correctly', false);
    else if (strpos($gpc_order, 'G') === FALSE
     || strpos($gpc_order, 'P') === FALSE
     || strpos($gpc_order, 'C') === FALSE) {
        do_err('Your gpc_order setting is insufficient for SquirrelMail to function. It needs to be set to "GPC", but you have it set to "' . sm_encode_html_special_chars($gpc_order) . '"', true);
    } else {
        echo $IND . "gpc_order OK: $gpc_order.<br />\n";
    }
}


/* check PHP extensions */

$php_exts = array('session','pcre');
$diff = array_diff($php_exts, get_loaded_extensions());
if(count($diff)) {
    do_err('Required PHP extensions missing: '.implode(', ',$diff) );
}

echo $IND . "PHP extensions OK. Dynamic loading is ";

if (!(bool)ini_get('enable_dl') || (bool)ini_get('safe_mode')) {
    echo "disabled.<br />\n";
} else {
    echo "enabled.<br />\n";
}


/* dangerous php settings */
/**
 * mbstring.func_overload allows to replace original string and regexp functions
 * with their equivalents from php mbstring extension. It causes problems when
 * scripts analyze 8bit strings byte after byte or use 8bit strings in regexp tests.
 * Setting can be controlled in php.ini (php 4.2.0), webserver config (php 4.2.0)
 * and .htaccess files (php 4.3.5).
 */
if (function_exists('mb_internal_encoding') &&
    check_php_version(4,2,0) &&
    (int)ini_get('mbstring.func_overload')!=0) {
    $mb_error='You have enabled mbstring overloading.'
        .' It can cause problems with SquirrelMail scripts that rely on single byte string functions.';
    do_err($mb_error);
}

/**
 * Do not use SquirrelMail with magic_quotes_* on.
 */
if ( (function_exists('get_magic_quotes_runtime') &&  @get_magic_quotes_runtime()) ||
     (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) ||
    ( (bool) ini_get('magic_quotes_sybase') && ini_get('magic_quotes_sybase') != 'off' )
    ) {
    $magic_quotes_warning='You have enabled any one of <tt>magic_quotes_runtime</tt>, '
        .'<tt>magic_quotes_gpc</tt> or <tt>magic_quotes_sybase</tt> in your PHP '
        .'configuration. We recommend all those settings to be off. SquirrelMail '
        .'may work with them on, but when experiencing stray backslashes in your mail '
        .'or other strange behaviour, it may be advisable to turn them off.';
    do_err($magic_quotes_warning,false);
}

if (ini_get('short_open_tag') == 0) {
    $short_open_tag_warning = 'You have configured PHP not to allow short tags '
        . '(<tt>short_open_tag=off</tt>). This shouldn\'t be a problem with '
        . 'SquirrelMail or any plugin coded coded according to the '
        . 'SquirrelMail Coding Guidelines, but if you experience problems with '
        . 'PHP code being displayed in some of the pages and changing setting '
        . 'to "on" solves the problem, please file a bug report against the '
        . 'failing plugin. The correct contact information is most likely '
        . 'to be found in the plugin documentation.';
    do_err($short_open_tag_warning, false);
}


/* check who the web server is running as if possible */

if ($process_info = get_process_owner_info()) {
    echo $IND . 'Web server is running as user: ' . $process_info['name'] . ' (' . $process_info['uid'] . ")<br />\n";
    //echo $IND . 'Web server is running as effective user: ' . $process_info['ename'] . ' (' . $process_info['euid'] . ")<br />\n";
    echo $IND . 'Web server is running as group: ' . $process_info['group'] . ' (' . $process_info['gid'] . ")<br />\n";
    //echo $IND . 'Web server is running as effective group: ' . $process_info['egroup'] . ' (' . $process_info['egid'] . ")<br />\n";
}


/* checking paths */

echo "Checking paths...<br />\n";

if(!file_exists($data_dir)) {
    // data_dir is not that important in db_setups.
    if (!empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) does not exist!\n";
        echo $IND .'<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) does not exist!");
    }
}
// don't check if errors
if(!isset($data_dir_error) && !is_dir($data_dir)) {
    if (!empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) is not a directory!\n";
        echo $IND . '<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) is not a directory!");
    }
}
// datadir should be executable - but no clean way to test on that
if(!isset($data_dir_error) && !sq_is_writable($data_dir)) {
    if (!empty($prefs_dsn)) {
        $data_dir_error = "Data dir ($data_dir) is not writable!\n";
        echo $IND . '<font color="red"><b>ERROR:</b></font> ' . $data_dir_error;
    } else {
        do_err("Data dir ($data_dir) is not writable!");
    }
}

if (isset($data_dir_error)) {
    echo " Some plugins might need access to data directory.<br />\n";
} else {
    // todo_ornot: actually write something and read it back.
    echo $IND . "Data dir OK.<br />\n";
}

if($data_dir == $attachment_dir) {
    echo $IND . "Attachment dir is the same as data dir.<br />\n";
    if (isset($data_dir_error)) {
        do_err($data_dir_error);
    }
} else {
    if(!file_exists($attachment_dir)) {
        do_err("Attachment dir ($attachment_dir) does not exist!");
    }
    if (!is_dir($attachment_dir)) {
        do_err("Attachment dir ($attachment_dir) is not a directory!");
    }
    if (!sq_is_writable($attachment_dir)) {
        do_err("I cannot write to attachment dir ($attachment_dir)!");
    }
    echo $IND . "Attachment dir OK.<br />\n";
}


echo "Checking plugins...<br />\n";

/* check plugins and themes */
//FIXME: check requirements given in plugin _info() function, such as required PHP extensions, Pear packages, other plugins, SM version, etc see development docs for list of returned info from that function
//FIXME: update this list with most recent contents of the Obsolete category - I think it has changed recently
$bad_plugins = array(
        'attachment_common',      // Integrated into SquirrelMail 1.2 core
        'auto_prune_sent',        // Obsolete: See Proon Automatic Folder Pruning plugin
        'compose_new_window',     // Integrated into SquirrelMail 1.4 core
        'delete_move_next',       // Integrated into SquirrelMail 1.5 core
        'disk_quota',             // Obsolete: See Check Quota plugin
        'email_priority',         // Integrated into SquirrelMail 1.2 core
        'emoticons',              // Obsolete: See HTML Mail plugin
        'focus_change',           // Integrated into SquirrelMail 1.2 core
        'folder_settings',        // Integrated into SquirrelMail 1.5.1 core
        'global_sql_addressbook', // Integrated into SquirrelMail 1.4 core
        'hancock',                // Not Working: See Random Signature Taglines plugin
        'msg_flags',              // Integrated into SquirrelMail 1.5.1 core
        'message_source',         // Added to SquirrelMail 1.4 Core Plugins (message_details)
        'motd',                   // Integrated into SquirrelMail 1.2 core
        'paginator',              // Integrated into SquirrelMail 1.2 core
        'printer_friendly',       // Integrated into SquirrelMail 1.2 core
        'procfilter',             // Obsolete: See Server Side Filter plugin
        'redhat_php_cgi_fix',     // Integrated into SquirrelMail 1.1.1 core
        'send_to_semicolon',      // Integrated into SquirrelMail 1.4.1 core
        'spamassassin',           // Not working beyond SquirrelMail 1.2.7: See Spamassassin SpamFilter (Frontend) v2 plugin
        'sqcalendar',             // Added to SquirrelMail 1.2 Core Plugins (calendar)
        'sqclock',                // Integrated into SquirrelMail 1.2 core
        'sql_squirrel_logger',    // Obsolete: See Squirrel Logger plugin
        'tmda',                   // Obsolete: See TMDA Tools plugin
        'vacation',               // Obsolete: See Vacation Local plugin
        'view_as_html',           // Integrated into SquirrelMail 1.5.1 core
        'xmailer'                 // Integrated into SquirrelMail 1.2 core
        );

if (isset($plugins[0])) {
    foreach($plugins as $plugin) {
        if(!file_exists(SM_PATH .'plugins/'.$plugin)) {
            do_err('You have enabled the <i>'.$plugin.'</i> plugin, but I cannot find it.', FALSE);
        } elseif (!is_readable(SM_PATH .'plugins/'.$plugin.'/setup.php')) {
            do_err('You have enabled the <i>'.$plugin.'</i> plugin, but I cannot locate or read its setup.php file.', FALSE);
        } elseif (in_array($plugin, $bad_plugins)) {
            do_err('You have enabled the <i>'.$plugin.'</i> plugin, which causes problems with this version of SquirrelMail. Please check the ReleaseNotes or other documentation for more information.', false);
        }
    }


    // load plugin functions
    include_once(SM_PATH . 'functions/plugin.php');

    // turn on output buffering in order to prevent output of new lines
    ob_start();
    foreach ($plugins as $name) {
        use_plugin($name);

        // get output and remove whitespace
        $output = trim(ob_get_contents());

        // if plugin outputs more than newlines and spacing, stop script execution.
        if (!empty($output)) {
            $plugin_load_error = 'Some output was produced when plugin <i>' . $name . '</i> was loaded.  Usually this means there is an error in the plugin\'s setup or configuration file.  The output was: '.sm_encode_html_special_chars($output);
            do_err($plugin_load_error);
        }
    }
    ob_end_clean();


    /**
     * Check the contents of the static plugin hooks array file against
     * the plugin setup file, which may have changed in an upgrade, etc.
     * This helps remind admins to re-run the configuration utility when
     * a plugin has been changed or upgraded.
     */
    $static_squirrelmail_plugin_hooks = $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks = array();
    foreach ($plugins as $name) {
        $function = "squirrelmail_plugin_init_$name";
        if (function_exists($function)) {
            $function();

            // now iterate through each hook and make sure the
            // plugin is registered on the correct ones in the
            // static plugin configuration file
            //
            foreach ($squirrelmail_plugin_hooks as $hook_name => $hooked_plugins)
                foreach ($hooked_plugins as $hooked_plugin => $hooked_function)
                    if ($hooked_plugin == $name
                     && (empty($static_squirrelmail_plugin_hooks[$hook_name][$hooked_plugin])
                      || $static_squirrelmail_plugin_hooks[$hook_name][$hooked_plugin] != $hooked_function))
                        do_err('The plugin <i>' . $name . '</i> is supposed to be registered on the <i>' . $hook_name . '</i> hook, but it is not.  You need to re-run the configuration utility and re-save your configuration file.', FALSE);
        }
    }
    $squirrelmail_plugin_hooks = $static_squirrelmail_plugin_hooks;


    /**
     * Print plugin versions
     */
    echo $IND . "Plugin versions...<br />\n";
    foreach ($plugins as $name) {
        $plugin_version = get_plugin_version($name);
        $english_name = get_plugin_requirement($name, 'english_name');
        echo $IND . $IND . (empty($english_name) ? $name . ' ' : $english_name . ' (' . $name . ') ') . (empty($plugin_version) ? '??' : $plugin_version) . "<br />\n";

        // check if this plugin has any other plugin
        // dependencies and if they are satisfied
        //
        $failed_dependencies = check_plugin_dependencies($name);
        if ($failed_dependencies === SQ_INCOMPATIBLE) {
            do_err($name . ' is NOT COMPATIBLE with this version of SquirrelMail', FALSE);
        }
        else if (is_array($failed_dependencies)) {
            $missing_plugins = '';
            $incompatible_plugins = '';
            foreach ($failed_dependencies as $depend_name => $depend_requirements) {
                if ($depend_requirements['version'] == SQ_INCOMPATIBLE)
                    $incompatible_plugins .= ', ' . $depend_name;
                else
                    $missing_plugins .= ', ' . $depend_name . ' (version ' . $depend_requirements['version'] . ', ' . ($depend_requirements['activate'] ? 'must be activated' : 'need not be activated') . ')';
            }
            $error_string = (!empty($incompatible_plugins) ? $name . ' cannot be activated at the same time as the following plugins: ' . trim($incompatible_plugins, ', ') : '')
                          . (!empty($missing_plugins) ? (!empty($incompatible_plugins) ? '.  ' . $name . ' is also ' : $name . ' is ') . 'missing some dependencies: ' . trim($missing_plugins, ', ') : '');
            do_err($error_string, FALSE);
        }

    }


    /**
     * This hook was added in 1.5.2 and 1.4.10. Each plugins should print an error
     * message and return TRUE if there are any errors in its setup/configuration.
     */
    $plugin_err = boolean_hook_function('configtest', $null, 1);
    if($plugin_err) {
        do_err('Some plugin tests failed.');
    } else {
        echo $IND . "Plugins OK.<br />\n";
    }
} else {
    echo $IND . "Plugins are not enabled in config.<br />\n";
}
foreach($theme as $thm) {
    if(!file_exists($thm['PATH'])) {
        do_err('You have enabled the <i>'.$thm['NAME'].'</i> theme but I cannot find it ('.$thm['PATH'].').', FALSE);
    } elseif(!is_readable($thm['PATH'])) {
        do_err('You have enabled the <i>'.$thm['NAME'].'</i> theme but I cannot read it ('.$thm['PATH'].').', FALSE);
    }
}

echo $IND . "Themes OK.<br />\n";

if ( $squirrelmail_default_language != 'en_US' ) {
    $loc_path = SM_PATH .'locale/'.$squirrelmail_default_language.'/LC_MESSAGES/squirrelmail.mo';
    if( ! file_exists( $loc_path ) ) {
        do_err('You have set <i>' . $squirrelmail_default_language .
                '</i> as your default language, but I cannot find this translation (should be '.
                'in <tt>' . $loc_path . '</tt>). Please note that you have to download translations '.
                'separately from the main SquirrelMail package.', FALSE);
    } elseif ( ! is_readable( $loc_path ) ) {
        do_err('You have set <i>' . $squirrelmail_default_language .
                '</i> as your default language, but I cannot read this translation (file '.
                'in <tt>' . $loc_path . '</tt> unreadable).', FALSE);
    } else {
        echo $IND . "Default language OK.<br />\n";
    }
} else {
    echo $IND . "Default language OK.<br />\n";
}

echo $IND . "Base URL detected as: <tt>" . sm_encode_html_special_chars($test_location) .
    "</tt> (location base " . (empty($config_location_base) ? 'autodetected' : 'set to <tt>' .
    sm_encode_html_special_chars($config_location_base)."</tt>") . ")<br />\n";

/* check minimal requirements for other security options */

/* imaps or ssmtp */
if($use_smtp_tls == 1 || $use_imap_tls == 1) {
    if(!check_php_version(4,3,0)) {
        do_err('You need at least PHP 4.3.0 for SMTP/IMAP TLS!');
    }
    if(!extension_loaded('openssl')) {
        do_err('You need the openssl PHP extension to use SMTP/IMAP TLS!');
    }
}
/* starttls extensions */
if($use_smtp_tls === 2 || $use_imap_tls === 2) {
    if (! function_exists('stream_socket_enable_crypto')) {
        do_err('If you want to use STARTTLS extension, you need stream_socket_enable_crypto() function from PHP 5.1.0 and newer.');
    }
}
/* digest-md5 */
if ($smtp_auth_mech=='digest-md5' || $imap_auth_mech =='digest-md5') {
    if (!extension_loaded('xml')) {
        do_err('You need the PHP XML extension to use Digest-MD5 authentication!');
    }
}

/* check outgoing mail */

echo "Checking outgoing mail service....<br />\n";

if($useSendmail) {
    // is_executable also checks for existance, but we want to be as precise as possible with the errors
    if(!file_exists($sendmail_path)) {
        do_err("Location of sendmail program incorrect ($sendmail_path)!");
    }
    if(!is_executable($sendmail_path)) {
        do_err("I cannot execute the sendmail program ($sendmail_path)!");
    }

    echo $IND . "sendmail OK<br />\n";
} else {
    // NB: Using "ssl://" ensures the highest possible TLS version
    // will be negotiated with the server (whereas "tls://" only
    // uses TLS version 1.0)
    $stream = fsockopen( ($use_smtp_tls==1?'ssl://':'').$smtpServerAddress, $smtpPort,
            $errorNumber, $errorString);
    if(!$stream) {
        do_err("Error connecting to SMTP server \"$smtpServerAddress:$smtpPort\".".
                "Server error: ($errorNumber) ".sm_encode_html_special_chars($errorString));
    }

    // check for SMTP code; should be 2xx to allow us access
    $smtpline = fgets($stream, 1024);
    if(((int) $smtpline{0}) > 3) {
        do_err("Error connecting to SMTP server. Server error: ".
                sm_encode_html_special_chars($smtpline));
    }

    /* smtp starttls checks */
    if ($use_smtp_tls===2) {
        // if something breaks, script should close smtp connection on exit.


        // format EHLO argument correctly if needed
        //
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $client_ip))
            $helohost = '[' . $client_ip . ']';
        else // some day might add IPv6 here
            $helohost = $client_ip;


        // say helo
        fwrite($stream,"EHLO $helohost\r\n");

        $ehlo=array();
        $ehlo_error = false;
        while ($line=fgets($stream, 1024)){
            if (preg_match("/^250(-|\s)(\S*)\s+(\S.*)/",$line,$match)||
                    preg_match("/^250(-|\s)(\S*)\s+/",$line,$match)) {
                if (!isset($match[3])) {
                    // simple one word extension
                    $ehlo[strtoupper($match[2])]='';
                } else {
                    // ehlo-keyword + ehlo-param
                    $ehlo[strtoupper($match[2])]=trim($match[3]);
                }
                if ($match[1]==' ') {
                    $ret = $line;
                    break;
                }
            } else {
                //
                $ehlo_error = true;
                $ehlo[]=$line;
                break;
            }
        }
        if ($ehlo_error) {
            do_err('SMTP EHLO failed. You need ESMTP support for SMTP STARTTLS');
        } elseif (!array_key_exists('STARTTLS',$ehlo)) {
            do_err('STARTTLS support is not declared by SMTP server.');
        }

        fwrite($stream,"STARTTLS\r\n");
        $starttls_response=fgets($stream, 1024);
        if ($starttls_response[0]!=2) {
            $starttls_cmd_err = 'SMTP STARTTLS failed. Server replied: '
                .sm_encode_html_special_chars($starttls_response);
            do_err($starttls_cmd_err);
        } elseif(! stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            do_err('Failed to enable encryption on SMTP STARTTLS connection.');
        } else {
            echo $IND . "SMTP STARTTLS extension looks OK.<br />\n";
        }
        // According to RFC we should second ehlo call here.
    }

    fputs($stream, 'QUIT');
    fclose($stream);
    echo $IND . 'SMTP server OK (<tt><small>'.
            trim(sm_encode_html_special_chars($smtpline))."</small></tt>)<br />\n";

    /* POP before SMTP */
    if($pop_before_smtp) {
        if (empty($pop_before_smtp_host)) $pop_before_smtp_host = $smtpServerAddress;
        $stream = fsockopen($pop_before_smtp_host, 110, $err_no, $err_str);
        if (!$stream) {
            do_err("Error connecting to POP Server ($pop_before_smtp_host:110) "
                . $err_no . ' : ' . sm_encode_html_special_chars($err_str));
        }

        $tmp = fgets($stream, 1024);
        if (substr($tmp, 0, 3) != '+OK') {
            do_err("Error connecting to POP Server ($pop_before_smtp_host:110)"
                . ' '.sm_encode_html_special_chars($tmp));
        }
        fputs($stream, 'QUIT');
        fclose($stream);
        echo $IND . "POP-before-SMTP OK.<br />\n";
    }
}

/**
 * Check the IMAP server
 */
echo "Checking IMAP service....<br />\n";

/** Can we open a connection? */
// NB: Using "ssl://" ensures the highest possible TLS version
// will be negotiated with the server (whereas "tls://" only
// uses TLS version 1.0)
$stream = fsockopen( ($use_imap_tls==1?'ssl://':'').$imapServerAddress, $imapPort,
        $errorNumber, $errorString);
if(!$stream) {
    do_err("Error connecting to IMAP server \"$imapServerAddress:$imapPort\".".
            "Server error: ($errorNumber) ".
            sm_encode_html_special_chars($errorString));
}

/** Is the first response 'OK'? */
$imapline = fgets($stream, 1024);
if(substr($imapline, 0,4) != '* OK') {
    do_err('Error connecting to IMAP server. Server error: '.
            sm_encode_html_special_chars($imapline));
}

echo $IND . 'IMAP server ready (<tt><small>'.
    sm_encode_html_special_chars(trim($imapline))."</small></tt>)<br />\n";

/** Check capabilities */
fputs($stream, "A001 CAPABILITY\r\n");
$capline = '';
while ($line=fgets($stream, 1024)){
    if (preg_match("/A001.*/",$line)) {
        break;
    } else {
        $capline.=$line;
    }
}

/* don't display capabilities before STARTTLS */
if ($use_imap_tls===2 && stristr($capline, 'STARTTLS') === false) {
    do_err('Your server doesn\'t support STARTTLS.');
} elseif($use_imap_tls===2) {
    /* try starting starttls */
    fwrite($stream,"A002 STARTTLS\r\n");
    $starttls_line=fgets($stream, 1024);
    if (! preg_match("/^A002 OK.*/i",$starttls_line)) {
        $imap_starttls_err = 'IMAP STARTTLS failed. Server replied: '
            .sm_encode_html_special_chars($starttls_line);
        do_err($imap_starttls_err);
    } elseif (! stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        do_err('Failed to enable encryption on IMAP connection.');
    } else {
        echo $IND . "IMAP STARTTLS extension looks OK.<br />\n";
    }

    // get new capability line
    fwrite($stream,"A003 CAPABILITY\r\n");
    $capline='';
    while ($line=fgets($stream, 1024)){
        if (preg_match("/A003.*/",$line)) {
            break;
        } else {
            $capline.=$line;
        }
    }
}

echo $IND . 'Capabilities: <tt>'.sm_encode_html_special_chars($capline)."</tt><br />\n";

if($imap_auth_mech == 'login' && stristr($capline, 'LOGINDISABLED') !== FALSE) {
    do_err('Your server doesn\'t allow plaintext logins. '.
            'Try enabling another authentication mechanism like CRAM-MD5, DIGEST-MD5 or TLS-encryption '.
            'in the SquirrelMail configuration.', FALSE);
}

if (stristr($capline, 'XMAGICTRASH') !== false) {
    $magic_trash = 'It looks like IMAP_MOVE_EXPUNGE_TO_TRASH option is turned on '
        .'in your Courier IMAP configuration. Courier does not provide tools that '
        .'allow to detect folder used for Trash or commands are not documented. '
        .'SquirrelMail can\'t detect special trash folder. SquirrelMail manages '
        .'all message deletion or move operations internally and '
        .'IMAP_MOVE_EXPUNGE_TO_TRASH option can cause errors in message and '
        .'folder management operations. Please turn off IMAP_MOVE_EXPUNGE_TO_TRASH '
        .'option in Courier imapd configuration.';
    do_err($magic_trash,false);
}

/* add warning about IMAP delivery */
if (stristr($capline, 'XCOURIEROUTBOX') !== false) {
    $courier_outbox = 'OUTBOX setting is enabled in your Courier imapd '
        .'configuration. SquirrelMail uses standard SMTP protocol or sendmail '
        .'binary to send emails. Courier IMAP delivery method is not supported'
        .' and can create duplicate email messages.';
    do_err($courier_outbox,false);
}

/** OK, close connection */
fputs($stream, "A004 LOGOUT\r\n");
fclose($stream);

echo "Checking internationalization (i18n) settings...<br />\n";
echo "$IND gettext - ";
if (function_exists('gettext')) {
    echo 'Gettext functions are available.'
        .' On some systems you must have appropriate system locales compiled.'
        ."<br />\n";

    /* optional setlocale() tests. Should work only on glibc systems. */
    if (sqgetGlobalVar('testlocales',$testlocales,SQ_GET)) {
        include_once(SM_PATH . 'include/languages.php');
        echo $IND . $IND . 'Testing translations:<br>';
        foreach ($languages as $lang_code => $lang_data) {
            /* don't test aliases */
            if (isset($lang_data['NAME'])) {
                /* locale can be $lang_code or $lang_data['LOCALE'] */
                if (isset($lang_data['LOCALE'])) {
                    $setlocale = $lang_data['LOCALE'];
                } else {
                    $setlocale = $lang_code;
                }
                /* prepare information about tested locales */
                if (is_array($setlocale)) {
                    $display_locale = implode(', ',$setlocale);
                    $locale_count = count($setlocale);
                } else {
                    $display_locale = $setlocale;
                    $locale_count = 1;
                }
                $tested_locales_msg = 'Tested '.sm_encode_html_special_chars($display_locale).' '
                    .($locale_count>1 ? 'locales':'locale'). '.';

                echo $IND . $IND .$IND . $lang_data['NAME'].' (' .$lang_code. ') - ';
                $retlocale = sq_setlocale(LC_ALL,$setlocale);
                if (is_bool($retlocale)) {
                    echo '<font color="red">unsupported</font>. ';
                    echo $tested_locales_msg;
                } else {
                    echo 'supported. '
                        .$tested_locales_msg
                        .' setlocale() returned "'.sm_encode_html_special_chars($retlocale).'"';
                }
                echo "<br />\n";
            }
        }
        echo $IND . $IND . '<a href="configtest.php">Don\'t test translations</a>';
    } else {
        echo $IND . $IND . '<a href="configtest.php?testlocales=1">Test translations</a>. '
            .'This test is not accurate and might work only on some systems.'
            ."\n";
    }
    echo "<br />\n";
    /* end of translation tests */
} else {
    echo 'Gettext functions are unavailable.'
        .' SquirrelMail will use slower internal gettext functions.'
        ."<br />\n";
}
echo "$IND mbstring - ";
if (function_exists('mb_detect_encoding')) {
    echo "Mbstring functions are available.<br />\n";
} else {
    echo 'Mbstring functions are unavailable.'
        ." Japanese translation won't work.<br />\n";
}
echo "$IND recode - ";
if (function_exists('recode')) {
    echo "Recode functions are available.<br />\n";
} elseif (isset($use_php_recode) && $use_php_recode) {
    echo "Recode functions are unavailable.<br />\n";
    do_err('Your configuration requires recode support, but recode support is missing.');
} else {
    echo "Recode functions are unavailable.<br />\n";
}
echo "$IND iconv - ";
if (function_exists('iconv')) {
    echo "Iconv functions are available.<br />\n";
} elseif (isset($use_php_iconv) && $use_php_iconv) {
    echo "Iconv functions are unavailable.<br />\n";
    do_err('Your configuration requires iconv support, but iconv support is missing.');
} else {
    echo "Iconv functions are unavailable.<br />\n";
}
// same test as in include/init.php + date_default_timezone_set check
echo "$IND timezone - ";
if ( (!ini_get('safe_mode')) || function_exists('date_default_timezone_set') ||
        !strcmp(ini_get('safe_mode_allowed_env_vars'),'') ||
        preg_match('/^([\w_]+,)*TZ/', ini_get('safe_mode_allowed_env_vars')) ) {
    echo "Webmail users can change their time zone settings. \n";
} else {
    echo "Webmail users can't change their time zone settings. \n";
}
if (isset($_ENV['TZ'])) {
    echo 'Default time zone is '.sm_encode_html_special_chars($_ENV['TZ']);
} else {
    echo 'Current time zone is '.date('T');
}
echo ".<br />\n";

// Pear DB tests
echo "Checking database functions...<br />\n";
if($addrbook_dsn || $prefs_dsn || $addrbook_global_dsn) {
    @include_once('DB.php');
    if (class_exists('DB')) {
        echo "$IND PHP Pear DB support is present.<br />\n";
        $db_functions=array(
                'dbase' => 'dbase_open',
                'fbsql' => 'fbsql_connect',
                'interbase' => 'ibase_connect',
                'informix' => 'ifx_connect',
                'msql' => 'msql_connect',
                'mssql' => 'mssql_connect',
                'mysql' => 'mysql_connect',
                'mysqli' => 'mysqli_connect',
                'oci8' => 'ocilogon',
                'odbc' => 'odbc_connect',
                'pgsql' => 'pg_connect',
                'sqlite' => 'sqlite_open',
                'sybase' => 'sybase_connect'
                );

        $dsns = array();
        if($prefs_dsn) {
            $dsns['preferences'] = $prefs_dsn;
        }
        if($addrbook_dsn) {
            $dsns['addressbook'] = $addrbook_dsn;
        }
        if($addrbook_global_dsn) {
            $dsns['global addressbook'] = $addrbook_global_dsn;
        }

        foreach($dsns as $type => $dsn) {
            $aDsn = explode(':', $dsn);
            $dbtype = array_shift($aDsn);

            if(isset($db_functions[$dbtype]) && function_exists($db_functions[$dbtype])) {
                echo "$IND$dbtype database support present.<br />\n";
            } elseif(!(bool)ini_get('enable_dl') || (bool)ini_get('safe_mode')) {
                do_err($dbtype.' database support not present!');
            } else {
                // Non-fatal error
                do_err($dbtype.' database support not present or not configured!
                    Trying to dynamically load '.$dbtype.' extension.
                    Please note that it is advisable to not rely on dynamic loading of extensions.', FALSE);
            }


            // now, test this interface:

            $dbh = DB::connect($dsn, true);
            if (DB::isError($dbh)) {
                do_err('Database error: '. sm_encode_html_special_chars(DB::errorMessage($dbh)) .
                        ' in ' .$type .' DSN.');
            }
            $dbh->disconnect();
            echo "$IND$type database connect successful.<br />\n";
        }
    } else {
        $db_error='Required PHP PEAR DB support is not available.'
            .' Is PEAR installed and is the include path set correctly to find <tt>DB.php</tt>?'
            .' The include path is now: "<tt>' . ini_get('include_path') . '</tt>".';
        do_err($db_error);
    }
} else {
    echo $IND."not using database functionality.<br />\n";
}

// LDAP DB tests
echo "Checking LDAP functions...<br />\n";
if( empty($ldap_server) ) {
    echo $IND."not using LDAP functionality.<br />\n";
} else {
    if ( !function_exists('ldap_connect') ) {
        do_err('Required LDAP support is not available.');
    } else {
        echo "$IND LDAP support present.<br />\n";
        foreach ( $ldap_server as $param ) {

            $linkid = @ldap_connect($param['host'], (empty($param['port']) ? 389 : $param['port']) );

            if ( $linkid ) {
                echo "$IND LDAP connect to ".$param['host']." successful: ".$linkid."<br />\n";

                if ( !empty($param['protocol']) &&
                        !ldap_set_option($linkid, LDAP_OPT_PROTOCOL_VERSION, $param['protocol']) ) {
                    do_err('Unable to set LDAP protocol');
                }

                if ( empty($param['binddn']) ) {
                    $bind = @ldap_bind($linkid);
                } else {
                    $bind = @ldap_bind($linkid, $param['binddn'], $param['bindpw']);
                }

                if ( $bind ) {
                    echo "$IND LDAP Bind Successful <br />";
                } else {
                    do_err('Unable to Bind to LDAP Server');
                }

                @ldap_close($linkid);
            } else {
                do_err('Connection to LDAP failed');
            }
        }
    }
}

echo '<hr width="75%" align="center">';
echo '<h2 align="center">Summary</h2>';
$footer = '<hr width="75%" align="center">';
if ($warnings) {
    echo '<p>No fatal errors were found, but there was at least 1 warning. Please check the flagged issue(s) carefully, as correcting them may prevent erratic, undefined, or incorrect behavior (or flat out breakage).</p>';
    echo $footer;
} else {
    print <<< EOF
<p>Congratulations, your SquirrelMail setup looks fine to me!</p>

<p><a href="login.php">Login now</a></p>

</body>
</html>
EOF;
    echo $footer;
}
