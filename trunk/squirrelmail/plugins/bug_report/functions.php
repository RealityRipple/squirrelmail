<?php
/**
 * functions for bug_report plugin
 *
 * @copyright &copy; 2004-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */


/**
 * do not allow to call this file directly
 */
if ((isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == __FILE__) ||
     (isset($HTTP_SERVER_SERVER['SCRIPT_FILENAME']) && $HTTP_SERVER_SERVER['SCRIPT_FILENAME'] == __FILE__) ) {
    header("Location: ../../src/login.php");
    die();
}

/** Declare plugin configuration vars */
global $bug_report_admin_email, $bug_report_allow_users;

/** Load default config */
if (file_exists(SM_PATH . 'plugins/bug_report/config_default.php')) {
    include_once (SM_PATH . 'plugins/bug_report/config_default.php');
} else {
    // default config was removed.
    $bug_report_admin_email = '';
    $bug_report_allow_users = false;
}

/** Load site config */
if (file_exists(SM_PATH . 'config/bug_report_config.php')) {
    include_once (SM_PATH . 'config/bug_report_config.php');
} elseif (file_exists(SM_PATH . 'plugins/bug_report/config.php')) {
    include_once (SM_PATH . 'plugins/bug_report/config.php');
}

/**
 * Checks if user can use bug_report plugin
 * @return boolean
 * @since 1.5.1
 */
function bug_report_check_user() {
    global $username, $bug_report_allow_users, $bug_report_admin_email;

    if (file_exists(SM_PATH . 'plugins/bug_report/admins')) {
        $auths = file(SM_PATH . 'plugins/bug_report/admins');
        array_walk($auths, 'bug_report_array_trim');
        $auth = in_array($username, $auths);
    } else if (file_exists(SM_PATH . 'config/admins')) {
        $auths = file(SM_PATH . 'config/admins');
        array_walk($auths, 'bug_report_array_trim');
        $auth = in_array($username, $auths);
    } else if (($adm_id = fileowner(SM_PATH . 'config/config.php')) &&
               function_exists('posix_getpwuid')) {
        $adm = posix_getpwuid( $adm_id );
        $auth = ($username == $adm['name']);
    } else {
        $auth = false;
    }

    if (! empty($bug_report_admin_email) && $bug_report_allow_users) {
        $auth = true;
    }

    return ($auth);
}

/**
 * Removes whitespace from array values
 * @param string $value array value that has to be trimmed
 * @param string $key array key
 * @since 1.5.1
 * @todo code reuse. create generic sm function.
 * @access private
 */
function bug_report_array_trim(&$value,$key) {
    $value=trim($value);
}
