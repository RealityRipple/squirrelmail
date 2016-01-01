<?php

/**
 * iso-8859-1 encoding functions
 *
 * takes a string of unicode entities and converts it to a iso-8859-1 encoded string
 * Unsupported characters are replaced with ?.
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage encode
 */

/**
 * Converts string to iso-8859-1
 * @param string $string text with numeric unicode entities
 * @return string iso-8859-1 encoded text
 */
function charset_encode_iso_8859_1 ($string) {
   // don't run encoding function, if there is no encoded characters
   if (! preg_match("'&#[0-9]+;'",$string) ) return $string;

    $string=preg_replace_callback("/&#([0-9]+);/",'unicodetoiso88591',$string);

    return $string;
}

/**
 * Return iso-8859-1 symbol when unicode character number is provided
 *
 * This function is used internally by charset_encode_iso_8859_1
 * function. It might be unavailable to other SquirrelMail functions.
 * Don't use it or make sure, that functions/encode/iso_8859_1.php is
 * included.
 *
 * @param array $matches array with first element a decimal unicode value
 * @return string iso-8859-1 character
 */
function unicodetoiso88591($matches) {
    $var = $matches[1];

    if ($var < 256) {
        $ret = chr ($var);
    } else {
        $ret='?';
    }
    return $ret;
}
