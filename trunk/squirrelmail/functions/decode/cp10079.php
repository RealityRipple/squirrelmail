<?php
/**
 * decode/cp10079.php
 * $Id$
 *
 * Copyright (c) 2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains cp10079 (MacIcelandic) decoding function that 
 * is needed to read cp10079 encoded mails in non-cp10079 locale.
 * 
 * Original data taken from:
 *  ftp://ftp.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/MAC/ICELAND.TXT
 *
 *  Name:     cp10079_MacIcelandic to Unicode table
 *  Unicode version: 2.0
 *  Table version: 2.00
 *  Table format:  Format A
 *  Date:          04/24/96
 *  Authors:       Lori Brownell <loribr@microsoft.com>
 *                 K.D. Chang    <a-kchang@microsoft.com>
 * @package squirrelmail
 * @subpackage decode
 */

/**
 * Decode a cp10079 (MacIcelandic) string
 * @param string $string Encoded string
 * @return string $string Decoded string
 */
function charset_decode_cp10079 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'x-mac-icelandic')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string) )
        return $string;

    $cp10079 = array(
	"\0x80" => '&#196;',
	"\0x81" => '&#197;',
	"\0x82" => '&#199;',
	"\0x83" => '&#201;',
	"\0x84" => '&#209;',
	"\0x85" => '&#214;',
	"\0x86" => '&#220;',
	"\0x87" => '&#225;',
	"\0x88" => '&#224;',
	"\0x89" => '&#226;',
	"\0x8A" => '&#228;',
	"\0x8B" => '&#227;',
	"\0x8C" => '&#229;',
	"\0x8D" => '&#231;',
	"\0x8E" => '&#233;',
	"\0x8F" => '&#232;',
	"\0x90" => '&#234;',
	"\0x91" => '&#235;',
	"\0x92" => '&#237;',
	"\0x93" => '&#236;',
	"\0x94" => '&#238;',
	"\0x95" => '&#239;',
	"\0x96" => '&#241;',
	"\0x97" => '&#243;',
	"\0x98" => '&#242;',
	"\0x99" => '&#244;',
	"\0x9A" => '&#246;',
	"\0x9B" => '&#245;',
	"\0x9C" => '&#250;',
	"\0x9D" => '&#249;',
	"\0x9E" => '&#251;',
	"\0x9F" => '&#252;',
	"\0xA0" => '&#221;',
	"\0xA1" => '&#176;',
	"\0xA2" => '&#162;',
	"\0xA3" => '&#163;',
	"\0xA4" => '&#167;',
	"\0xA5" => '&#8226;',
	"\0xA6" => '&#182;',
	"\0xA7" => '&#223;',
	"\0xA8" => '&#174;',
	"\0xA9" => '&#169;',
	"\0xAA" => '&#8482;',
	"\0xAB" => '&#180;',
	"\0xAC" => '&#168;',
	"\0xAD" => '&#8800;',
	"\0xAE" => '&#198;',
	"\0xAF" => '&#216;',
	"\0xB0" => '&#8734;',
	"\0xB1" => '&#177;',
	"\0xB2" => '&#8804;',
	"\0xB3" => '&#8805;',
	"\0xB4" => '&#165;',
	"\0xB5" => '&#181;',
	"\0xB6" => '&#8706;',
	"\0xB7" => '&#8721;',
	"\0xB8" => '&#8719;',
	"\0xB9" => '&#960;',
	"\0xBA" => '&#8747;',
	"\0xBB" => '&#170;',
	"\0xBC" => '&#186;',
	"\0xBD" => '&#8486;',
	"\0xBE" => '&#230;',
	"\0xBF" => '&#248;',
	"\0xC0" => '&#191;',
	"\0xC1" => '&#161;',
	"\0xC2" => '&#172;',
	"\0xC3" => '&#8730;',
	"\0xC4" => '&#402;',
	"\0xC5" => '&#8776;',
	"\0xC6" => '&#8710;',
	"\0xC7" => '&#171;',
	"\0xC8" => '&#187;',
	"\0xC9" => '&#8230;',
	"\0xCA" => '&#160;',
	"\0xCB" => '&#192;',
	"\0xCC" => '&#195;',
	"\0xCD" => '&#213;',
	"\0xCE" => '&#338;',
	"\0xCF" => '&#339;',
	"\0xD0" => '&#8211;',
	"\0xD1" => '&#8212;',
	"\0xD2" => '&#8220;',
	"\0xD3" => '&#8221;',
	"\0xD4" => '&#8216;',
	"\0xD5" => '&#8217;',
	"\0xD6" => '&#247;',
	"\0xD7" => '&#9674;',
	"\0xD8" => '&#255;',
	"\0xD9" => '&#376;',
	"\0xDA" => '&#8260;',
	"\0xDB" => '&#164;',
	"\0xDC" => '&#208;',
	"\0xDD" => '&#240;',
	"\0xDE" => '&#222;',
	"\0xDF" => '&#254;',
	"\0xE0" => '&#253;',
	"\0xE1" => '&#183;',
	"\0xE2" => '&#8218;',
	"\0xE3" => '&#8222;',
	"\0xE4" => '&#8240;',
	"\0xE5" => '&#194;',
	"\0xE6" => '&#202;',
	"\0xE7" => '&#193;',
	"\0xE8" => '&#203;',
	"\0xE9" => '&#200;',
	"\0xEA" => '&#205;',
	"\0xEB" => '&#206;',
	"\0xEC" => '&#207;',
	"\0xED" => '&#204;',
	"\0xEE" => '&#211;',
	"\0xEF" => '&#212;',
	"\0xF0" => '&#65535;',
	"\0xF1" => '&#210;',
	"\0xF2" => '&#218;',
	"\0xF3" => '&#219;',
	"\0xF4" => '&#217;',
	"\0xF5" => '&#305;',
	"\0xF6" => '&#710;',
	"\0xF7" => '&#732;',
	"\0xF8" => '&#175;',
	"\0xF9" => '&#728;',
	"\0xFA" => '&#729;',
	"\0xFB" => '&#730;',
	"\0xFC" => '&#184;',
	"\0xFD" => '&#733;',
	"\0xFE" => '&#731;',
	"\0xFF" => '&#711;'
   );

    $string = str_replace(array_keys($cp10079), array_values($cp10079), $string);

    return $string;
}
?>
