<?php
/*
 * decode/iso8859-1.php
 * $Id$
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains iso-8859-1 decoding function that is needed to read
 * iso-8859-1 encoded mails in non-iso-8859-1 locale.
 * 
 */
function charset_decode_iso8859_1 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-1')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    /* there is no 0x80-0x9F letters in ISO8859-* */
    if ( ! ereg("[\241-\377]", $string) )
        return $string;

    $string = preg_replace("/([\201-\237])/e","'&#' . ord('\\1') . ';'",$string);

    /* I don't want to use 0xA0 (\240) in any ranges. RH73 may dislike it */
    $string = str_replace("\240", '&#160;', $string);

    $string = preg_replace("/([\241-\377])/e","'&#' . ord('\\1') . ';'",$string);
    return $string;
}

?>