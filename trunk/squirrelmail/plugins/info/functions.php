<?php
/**
 * functions for info plugin
 *
 * Here are two functions for the info plugin
 * The first gets the CAPABILITY response from your IMAP server.
 * The second runs the passed IMAP test and returns the results 
 * The third prints the results of the IMAP command
 * to options.php.
 *
 * @copyright (c) 1999-2004 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Jason Munro jason@stdbev.com
 * @version $Id$
 * @package plugins
 * @subpackage info
 */

/**
 * Get the IMAP capabilities
 *
 * @param mixed $imap_stream
 * @return array
 * @access private
 */
function get_caps($imap_stream) {
    return sqimap_run_command_list($imap_stream, 'CAPABILITY',false, $responses, $message,false);
}

/**
 * Run an IMAP test and return the results
 *
 * @param mixed $imap_stream
 * @param string $string imap command
 * @return array Response from the IMAP server
 * @access private
 */
function imap_test($imap_stream, $string) {
    global $default_charset;
    print "<tr><td>".htmlspecialchars($string)."</td></tr>";
    $response = sqimap_run_command_list($imap_stream, trim($string),false, $responses, $message,false);
    array_push($response, $responses . ' ' .$message);
    return $response;
}

/**
 * Print the IMAP response to options.php
 *
 * @param array $response results of imap command
 * @access private
 */
function print_response($response) {
    foreach($response as $index=>$value) {
        if (is_array($value)) {
            print_response($value);
        }
        else {
            print htmlspecialchars($value)."<br />\n";
        }
    }
}

/**
 * Check if plugin is enabled
 * @param string $plugin_name plugin name
 * @return boolean
 */
function is_plugin_enabled($plugin_name) {
  global $plugins;
  if ( in_array($plugin_name,$plugins) ) {
    return true;
  } else {
    return false;
  }
}
?>