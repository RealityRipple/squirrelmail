<?php
/**
 * functions for bug_report plugin
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */


/**
 * Initializes the Bug Report plugin
 *
 * @return boolean FALSE if the plugin is not correctly configured
 *         or an error in its setup is found; TRUE otherwise
 *
 * @since 1.5.2
 *
 */
function bug_report_init() {

    // Declare plugin configuration vars
    //
    global $bug_report_admin_email, $bug_report_allow_users;

    // Load default config
    //
    if (file_exists(SM_PATH . 'plugins/bug_report/config_default.php')) {
        include_once (SM_PATH . 'plugins/bug_report/config_default.php');
    } else {
        // default config was removed.
        $bug_report_admin_email = '';
        $bug_report_allow_users = false;
    }

    // Load site config
    //
    if (file_exists(SM_PATH . 'config/bug_report_config.php')) {
        include_once (SM_PATH . 'config/bug_report_config.php');
    } elseif (file_exists(SM_PATH . 'plugins/bug_report/config.php')) {
        include_once (SM_PATH . 'plugins/bug_report/config.php');
    }

}


/**
 * Checks if user can use bug_report plugin
 *
 * @return boolean
 *
 * @since 1.5.1
 *
 */
function bug_report_check_user() {
    global $username, $bug_report_allow_users, $bug_report_admin_email;

    bug_report_init();

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
 *
 * @param string $value array value that has to be trimmed
 * @param string $key array key
 *
 * @since 1.5.1
 *
 * @todo code reuse. create generic sm function.
 *
 * @access private
 *
 */
function bug_report_array_trim(&$value,$key) {
    $value = trim($value);
}


/**
 * Show the button in the main bar
 *
 * @access private
 *
 */
function bug_report_button_do() {
    global $username, $data_dir;
    $bug_report_visible = getPref($data_dir, $username, 'bug_report_visible', FALSE);

    if (! $bug_report_visible || ! bug_report_check_user()) {
        return;
    }

    global $oTemplate, $nbsp;
    $output = makeInternalLink('plugins/bug_report/bug_report.php', _("Bug"), '')
            . $nbsp . $nbsp;
    return array('menuline' => $output);
}


/**
 * Register bug report option block
 *
 * @since 1.5.1
 *
 * @access private
 *
 */
function bug_report_block_do() {
    if (bug_report_check_user()) {
        global $username, $data_dir, $optpage_data, $bug_report_visible;
        $bug_report_visible = getPref($data_dir, $username, 'bug_report_visible', FALSE);
        $optpage_data['grps']['bug_report'] = _("Bug Reports");
        $optionValues = array();
// FIXME: option needs refresh in SMOPT_REFRESH_RIGHT (menulinks are built before options are saved/loaded)
        $optionValues[] = array(
            'name'    => 'bug_report_visible',
            'caption' => _("Show button in toolbar"),
            'type'    => SMOPT_TYPE_BOOLEAN,
            'refresh' => SMOPT_REFRESH_ALL,
            'initial_value' => false
            );
        $optpage_data['vals']['bug_report'] = $optionValues;
    }
}


