<?PHP

/**
 * functions for info plugin
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Here are two functions for the info plugin
 * The first gets the CAPABILITY response from your IMAP server.
 * The second runs the passed IMAP test and returns the results 
 * The third prints the results of the IMAP command
 * to options.php.
 * by: Jason Munro jason@stdbev.com
 *
 * $Id$ 
 * @package plugins
 * @subpackage info
 */

/**
 * Get the IMAP capabilities
 * @return array
 */
function get_caps($imap_stream) {
    return sqimap_run_command_list($imap_stream, 'CAPABILITY',false, $responses, $message,false);
}

/**
 * Run an IMAP test and return the results
 * @return array Response from the IMAP server
 */
function imap_test($imap_stream, $string) {
    global $default_charset;
    print "<TR><TD>".$string."</TD></TR>";
    $response = sqimap_run_command_list($imap_stream, trim($string),false, $responses, $message,false);
    array_push($response, $responses . ' ' .$message);
    return $response;
}

/**
 * Print the IMAP response to options.php
 */
function print_response($response) {
    foreach($response as $index=>$value) {
        if (is_array($value)) {
            print_response($value);
        }
        else {
            $value = preg_replace("/</", "&lt;", $value);
            $value = preg_replace("/>/", "&gt;", $value);
            print $value."<BR>\n";
        }
    }
}
                                                                                        
?>
