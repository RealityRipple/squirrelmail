<?php
/**
 * decode/utf-8.php
 * $Id$
 *
 * Copyright (c) 2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains utf-8 decoding function that is needed to read
 * utf-8 encoded mails in non-utf-8 locale.
 *
 * Every decoded character consists of n bytes. First byte is octal
 * 300-375, other bytes - always octals 200-277.
 *
 * \a\b characters are decoded to html code octdec(a-300)*64 + octdec(b-200)
 * \a\b\c characters are decoded to html code octdec(a-340)*64*64 + octdec(b-200)*64 + octdec(c-200)
 *
 * decoding cycle is unfinished. please test and report problems to tokul@users.sourceforge.net
 * 
 * @package squirrelmail
 * @subpackage decode
 */

/**
 * Decode utf-8 strings
 * @param string $string Encoded string
 * @return string Decoded string
 */
function charset_decode_utf_8 ($string) {
  global $default_charset;

    if (strtolower($default_charset) == 'utf-8')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
        return $string;

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
    "'&#'.((ord('\\1')-224)*4096+(ord('\\2')-128)*64+(ord('\\3')-128)).';'",
    $string);

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
    "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
    $string);

    return $string;
}

?>
