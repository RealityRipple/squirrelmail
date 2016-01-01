<?php
/**
 * This script gathers system specification details for use with bug reporting
 * and anyone else who needs it.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */


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
        foreach ($plugins as $key => $plugin_name) {

            // note that some plugins may not have been loaded up by now
            // so we do that here to make sure...  also turn on output
            // buffering so they don't screw up our output with spacing
            // or newlines
            //
            ob_start();
            use_plugin($plugin_name);
            ob_end_clean();

            if ($key != 0 || $plugin_name != '') {
                $english_name = get_plugin_requirement($plugin_name, 'english_name');
                $str .= "    * $key = " . (!empty($english_name) ? $english_name . " ($plugin_name) " : "$plugin_name ") . get_plugin_version($plugin_name, TRUE) . "\n";
            }
        }
        // compatibility plugin can be used without needing to enable it in sm config
        if (file_exists(SM_PATH . 'plugins/compatibility/setup.php')
            && ! in_array('compatibility',$plugins)) {
            $str.= '    * Compatibility (compatibility) ' . get_plugin_version('compatibility', TRUE) . "\n";
        }
    }
    if ($str == '') {
        return "    * Nothing listed\n";
    }
    return $str;
}


/**
 * Retrieve long text string containing semi-formatted (simple text
 * with newlines and spaces for indentation) SquirrelMail system
 * specs
 *
 * @return array A three-element array, the first element containing
 *               the string of system specs, the second one containing 
 *               a list of any warnings that may have occurred, keyed
 *               by a warning "type" (which is used to key the corrections
 *               array next), and the third element of which is a list
 *               of sub-arrays keyed by warning "type": the sub-arrays
 *               are lists of correction messages associated with the
 *               warnings.  The second and third return elements may
 *               be empty arrays if no warnings were found.
 *
 * @since 1.5.2
 *
 */
function get_system_specs() {
//FIXME: configtest and this plugin should be using the same code to generate the basic SM system specifications and setup detection

    global $imapServerAddress, $username, $imapPort, $imap_server_type,
           $use_imap_tls, $ldap_server;

    // load required libraries
    //
    include_once(SM_PATH . 'functions/imap_general.php');

    $browscap = ini_get('browscap');
    if(!empty($browscap)) {
        $browser = get_browser();
    }

    $warnings = array();
    $corrections = array();

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
            "  Version:  " . SM_VERSION . "\n" .
            "  Plugins (List)\n" .
            br_show_plugins() . "\n";
    if (!empty($ldap_server[0]) && $ldap_server[0] && ! extension_loaded('ldap')) {
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
            	if (is_array($value)) {
            		foreach($value as $val) {
            			$body .= $capability . "=$val ";
            		}
            	} else {
                	$body.= $capability . (is_bool($value) ? ' ' : "=$value ");
            	}
            }
        }
        $body.="\n";
        sqimap_logout($imap_stream);
    } else {
        $body .= "  Unable to connect to IMAP server to get information.\n";
        $warnings['imap'] = "Unable to connect to IMAP server";
        $corrections['imap'][] = "Make sure you specified the correct mail server";
        $corrections['imap'][] = "Make sure the mail server is running IMAP, not POP";
        $corrections['imap'][] = "Make sure the server responds to port $imapPort";
    }
    $warning_num = 0;
    if (!empty($warnings)) {
        foreach ($warnings as $key => $value) {
            if ($warning_num == 0) {
                $body_top .= "WARNINGS WERE REPORTED WITH YOUR SETUP:\n";
                $body_top = "WARNINGS WERE REPORTED WITH YOUR SETUP -- SEE BELOW\n\n$body_top";
            }
            $warning_num ++;
            $body_top .= "\n$value\n";
            foreach ($corrections[$key] as $corr_val) {
                $body_top .= "  * $corr_val\n";
            }
        }
        $body_top .= "\n$warning_num warning(s) reported.\n";
        $body_top .= "----------------------------------------------\n";
    }

    $body = $body_top . $body;

    return array($body, $warnings, $corrections);

}


