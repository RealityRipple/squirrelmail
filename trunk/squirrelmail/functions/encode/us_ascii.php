<?php

/**
 * us_ascii encoding functions
 *
 * takes a string of unicode entities and converts it to a us-ascii encoded string
 * Unsupported characters are replaced with ?.
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage encode
 */

/**
 * Converts string to us-ascii
 * @param string $string text with numeric unicode entities
 * @return string us-ascii encoded text
 */
function charset_encode_us_ascii ($string) {
   // don't run encoding function, if there is no encoded characters
   if (! preg_match("'&#[0-9]+;'",$string) ) return $string;

    $string=preg_replace_callback("/&#([0-9]+);/",'unicodetousascii',$string);

    return $string;
}

/**
 * Return us-ascii symbol when unicode character number is provided
 *
 * This function is used internally by charset_encode_us_ascii
 * function. It might be unavailable to other SquirrelMail functions.
 * Don't use it or make sure, that functions/encode/us_ascii.php is
 * included.
 *
 * @param array $matches array with first element a decimal unicode value
 * @return string us-ascii character
 */
function unicodetousascii($matches) {
    $var = $matches[1];

    if ($var < 128) {
        $ret = chr ($var);
    } else {
        $ret='?';
    }
    return $ret;
}
