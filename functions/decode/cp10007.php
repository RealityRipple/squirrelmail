<?php
/*
 * decode/cp10007.php
 * $Id$
 *
 * Copyright (c) 2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains cp10007 (MacCyrillic) decoding function that 
 * is needed to read cp10007 encoded mails in non-cp10007 locale.
 * 
 * Original data taken from:
 *  ftp://ftp.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/MAC/CYRILLIC.TXT
 *
 *   Name:     cp10007_MacCyrillic to Unicode table
 *   Unicode version: 2.0
 *   Table version: 2.00
 *   Table format:  Format A
 *   Date:          04/24/96
 *   Authors:       Lori Brownell <loribr@microsoft.com>
 *          K.D. Chang    <a-kchang@microsoft.com>
 *
 */

function charset_decode_cp10007 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'x-mac-cyrillic')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string) )
        return $string;

    $cp10007 = array(
	"\x80" => '&#1040;',
	"\x81" => '&#1041;',
	"\x82" => '&#1042;',
	"\x83" => '&#1043;',
	"\x84" => '&#1044;',
	"\x85" => '&#1045;',
	"\x86" => '&#1046;',
	"\x87" => '&#1047;',
	"\x88" => '&#1048;',
	"\x89" => '&#1049;',
	"\x8A" => '&#1050;',
	"\x8B" => '&#1051;',
	"\x8C" => '&#1052;',
	"\x8D" => '&#1053;',
	"\x8E" => '&#1054;',
	"\x8F" => '&#1055;',
	"\x90" => '&#1056;',
	"\x91" => '&#1057;',
	"\x92" => '&#1058;',
	"\x93" => '&#1059;',
	"\x94" => '&#1060;',
	"\x95" => '&#1061;',
	"\x96" => '&#1062;',
	"\x97" => '&#1063;',
	"\x98" => '&#1064;',
	"\x99" => '&#1065;',
	"\x9A" => '&#1066;',
	"\x9B" => '&#1067;',
	"\x9C" => '&#1068;',
	"\x9D" => '&#1069;',
	"\x9E" => '&#1070;',
	"\x9F" => '&#1071;',
	"\xA0" => '&#8224;',
	"\xA1" => '&#176;',
	"\xA2" => '&#162;',
	"\xA3" => '&#163;',
	"\xA4" => '&#167;',
	"\xA5" => '&#8226;',
	"\xA6" => '&#182;',
	"\xA7" => '&#1030;',
	"\xA8" => '&#174;',
	"\xA9" => '&#169;',
	"\xAA" => '&#8482;',
	"\xAB" => '&#1026;',
	"\xAC" => '&#1106;',
	"\xAD" => '&#8800;',
	"\xAE" => '&#1027;',
	"\xAF" => '&#1107;',
	"\xB0" => '&#8734;',
	"\xB1" => '&#177;',
	"\xB2" => '&#8804;',
	"\xB3" => '&#8805;',
	"\xB4" => '&#1110;',
	"\xB5" => '&#181;',
	"\xB6" => '&#8706;',
	"\xB7" => '&#1032;',
	"\xB8" => '&#1028;',
	"\xB9" => '&#1108;',
	"\xBA" => '&#1031;',
	"\xBB" => '&#1111;',
	"\xBC" => '&#1033;',
	"\xBD" => '&#1113;',
	"\xBE" => '&#1034;',
	"\xBF" => '&#1114;',
	"\xC0" => '&#1112;',
	"\xC1" => '&#1029;',
	"\xC2" => '&#172;',
	"\xC3" => '&#8730;',
	"\xC4" => '&#402;',
	"\xC5" => '&#8776;',
	"\xC6" => '&#8710;',
	"\xC7" => '&#171;',
	"\xC8" => '&#187;',
	"\xC9" => '&#8230;',
	"\xCA" => '&#160;',
	"\xCB" => '&#1035;',
	"\xCC" => '&#1115;',
	"\xCD" => '&#1036;',
	"\xCE" => '&#1116;',
	"\xCF" => '&#1109;',
	"\xD0" => '&#8211;',
	"\xD1" => '&#8212;',
	"\xD2" => '&#8220;',
	"\xD3" => '&#8221;',
	"\xD4" => '&#8216;',
	"\xD5" => '&#8217;',
	"\xD6" => '&#247;',
	"\xD7" => '&#8222;',
	"\xD8" => '&#1038;',
	"\xD9" => '&#1118;',
	"\xDA" => '&#1039;',
	"\xDB" => '&#1119;',
	"\xDC" => '&#8470;',
	"\xDD" => '&#1025;',
	"\xDE" => '&#1105;',
	"\xDF" => '&#1103;',
	"\xE0" => '&#1072;',
	"\xE1" => '&#1073;',
	"\xE2" => '&#1074;',
	"\xE3" => '&#1075;',
	"\xE4" => '&#1076;',
	"\xE5" => '&#1077;',
	"\xE6" => '&#1078;',
	"\xE7" => '&#1079;',
	"\xE8" => '&#1080;',
	"\xE9" => '&#1081;',
	"\xEA" => '&#1082;',
	"\xEB" => '&#1083;',
	"\xEC" => '&#1084;',
	"\xED" => '&#1085;',
	"\xEE" => '&#1086;',
	"\xEF" => '&#1087;',
	"\xF0" => '&#1088;',
	"\xF1" => '&#1089;',
	"\xF2" => '&#1090;',
	"\xF3" => '&#1091;',
	"\xF4" => '&#1092;',
	"\xF5" => '&#1093;',
	"\xF6" => '&#1094;',
	"\xF7" => '&#1095;',
	"\xF8" => '&#1096;',
	"\xF9" => '&#1097;',
	"\xFA" => '&#1098;',
	"\xFB" => '&#1099;',
	"\xFC" => '&#1100;',
	"\xFD" => '&#1101;',
	"\xFE" => '&#1102;',
	"\xFF" => '&#164;'
    );

    $string = str_replace(array_keys($cp10007), array_values($cp10007), $string);

    return $string;
}
?>