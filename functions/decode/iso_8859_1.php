<?php
/**
 * decode/iso8859-1.php
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains iso-8859-1 decoding function that is needed to read
 * iso-8859-1 encoded mails in non-iso-8859-1 locale.
 *
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

    $string = preg_replace("/([\201-\237])/e","'&#' . ord('\\1') . ';'",$string);

    /* I don't want to use 0xA0 (\240) in any ranges. RH73 may dislike it */
    $string = str_replace("\240", '&#160;', $string);

    $string = preg_replace("/([\241-\377])/e","'&#' . ord('\\1') . ';'",$string);
    return $string;
}

?>