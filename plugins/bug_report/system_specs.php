<?php
/**
 * This script gathers system specification details for use with bug reporting
 * and anyone else who needs it.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/** @ignore */
if (!defined('SM_PATH')) define('SM_PATH','../../');

/**
 * load required libraries
 */
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/imap.php');
global $body, $username;


/**
 * converts array to string
 *
 * @param array $array array that has to be displayed
 * @return string
 * @access private
 */
function Show_Array($array) {
    $str = '';
    foreach ($array as $key => $value) {
        if ($key != 0 || $value != '') {
        $str .= "    * $key = $value\n";
        }
    }
    if ($str == '') {
        return "    * Nothing listed\n";
    }
    return $str;
}

/**
 * converts plugin's array to string and adds version numbers
 * @return string preformated text with installed plugin's information
 * @access private
 */
function br_show_plugins() {
    global $plugins;
    $str = '';
    if (is_array($plugins) && $plugins!=array()) {
        foreach ($plugins as $key => $value) {
            if ($key != 0 || $value != '') {
                $str .= "    * $key = $value";
                // add plugin version
                if (function_exists($value . '_version')) {
                    $str.= ' ' . call_user_func($value . '_version');
                }
                $str.="\n";
            }
        }
        // compatibility plugin can be used without need to enable it in sm config
        if (file_exists(SM_PATH . 'plugins/compatibility/setup.php')
            && ! in_array('compatibility',$plugins)) {
            $str.= '    * compatibility';
            include_once(SM_PATH . 'plugins/compatibility/setup.php');
            if (function_exists('compatibility_version')) {
                $str.= ' ' . call_user_func('compatibility_version');
            }
            $str.="\n";
        }
    }
    if ($str == '') {
        return "    * Nothing listed\n";
    }
    return $str;
}

$browscap = ini_get('browscap');
if(!empty($browscap)) {
    $browser = get_browser();
}

sqgetGlobalVar('HTTP_USER_AGENT', $HTTP_USER_AGENT, SQ_SERVER);
if ( ! sqgetGlobalVar('HTTP_USER_AGENT', $HTTP_USER_AGENT, SQ_SERVER) )
    $HTTP_USER_AGENT="Browser information is not available.";

$body_top = "My browser information:\n" .
            '  '.$HTTP_USER_AGENT . "\n" ;
            if(isset($browser)) {
                $body_top .= "  get_browser() information (List)\n" .
                Show_Array((array) $browser);
            }
            $body_top .= "\nMy web server information:\n" .
            "  PHP Version " . phpversion() . "\n" .
            "  PHP Extensions (List)\n" .
            Show_Array(get_loaded_extensions()) .
            "\nSquirrelMail-specific information:\n" .
            "  Version:  $version\n" .
            "  Plugins (List)\n" .
            br_show_plugins();
if (isset($ldap_server) && $ldap_server[0] && ! extension_loaded('ldap')) {
    $warning = 1;
    $warnings['ldap'] = "LDAP server defined in SquirrelMail config, " .
        "but the module is not loaded in PHP";
    $corrections['ldap'][] = "Reconfigure PHP with the option '--with-ldap'";
    $corrections['ldap'][] = "Then recompile PHP and reinstall";
    $corrections['ldap'][] = "-- OR --";
    $corrections['ldap'][] = "Reconfigure SquirrelMail to not use LDAP";
}

$body = "\nMy IMAP server information:\n" .
            "  Server type:  $imap_server_type\n";

$imapServerAddress = sqimap_get_user_server($imapServerAddress, $username);
$imap_stream = sqimap_create_stream($imapServerAddress, $imapPort, $use_imap_tls);
if ($imap_stream) {
    $body.= '  Capabilities: ';
    if ($imap_capabilities = sqimap_capability($imap_stream)) {
        foreach ($imap_capabilities as $capability => $value) {
            $body.= $capability . (is_bool($value) ? ' ' : "=$value ");
        }
    }
    $body.="\n";
    sqimap_logout($imap_stream);
} else {
    $body .= "  Unable to connect to IMAP server to get information.\n";
    $warning = 1;
    $warnings['imap'] = "Unable to connect to IMAP server";
    $corrections['imap'][] = "Make sure you specified the correct mail server";
    $corrections['imap'][] = "Make sure the mail server is running IMAP, not POP";
    $corrections['imap'][] = "Make sure the server responds to port $imapPort";
}
$warning_html = '';
$warning_num = 0;
if (isset($warning) && $warning) {
    foreach ($warnings as $key => $value) {
        if ($warning_num == 0) {
            $body_top .= "WARNINGS WERE REPORTED WITH YOUR SETUP:\n";
            $body_top = "WARNINGS WERE REPORTED WITH YOUR SETUP -- SEE BELOW\n\n$body_top";
            $warning_html = "<h1>Warnings were reported with your setup:</h1>\n<dl>\n";
        }
        $warning_num ++;
        $warning_html .= "<dt><b>$value</b></dt>\n";
        $body_top .= "\n$value\n";
        foreach ($corrections[$key] as $corr_val) {
            $body_top .= "  * $corr_val\n";
            $warning_html .= "<dd>* $corr_val</dd>\n";
        }
    }
    $warning_html .= "</dl>\n<p>$warning_num warning(s) reported.</p>\n<hr />\n";
    $body_top .= "\n$warning_num warning(s) reported.\n";
    $body_top .= "----------------------------------------------\n";
}

$body = htmlspecialchars($body_top . $body);

?>