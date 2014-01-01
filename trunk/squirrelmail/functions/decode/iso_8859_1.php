<?php

/**
 * decode/iso8859-1.php
 *
 * This file contains iso-8859-1 decoding function that is needed to read
 * iso-8859-1 encoded mails in non-iso-8859-1 locale.
 *
 * @copyright 2003-2014 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage decode
 */

/**
 * Decode iso8859-1 string
 * @param string $string Encoded string
 * @return string $string Decoded string
 */
function charset_decode_iso_8859_1 ($string) {
    // don't do decoding when there are no 8bit symbols
    if (! sq_is8bit($string,'iso-8859-1'))
        return $string;

    $string = preg_replace_callback("/([\201-\237])/",
    create_function ('$matches', 'return \'&#\' . ord($matches[1]) . \';\';'),
    $string);

    /* I don't want to use 0xA0 (\240) in any ranges. RH73 may dislike it */
    $string = str_replace("\240", '&#160;', $string);

    $string = preg_replace_callback("/([\241-\377])/",
    create_function ('$matches', 'return \'&#\' . ord($matches[1]) . \';\';'),
    $string);
    return $string;
}
