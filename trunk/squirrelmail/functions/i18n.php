<?php

/**
 * i18n.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains variuos functions that are needed to do
 * internationalization of SquirrelMail.
 *
 * Internally the output character set is used. Other characters are
 * encoded using Unicode entities according to HTML 4.0.
 *
 * $Id$
 */

/* Decodes a string to the internal encoding from the given charset */
function charset_decode ($charset, $string) {
    global $languages, $squirrelmail_language;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        $string = $languages[$squirrelmail_language]['XTRA_CODE']('decode', $string);
    }

    /* All HTML special characters are 7 bit and can be replaced first */
    $string = htmlspecialchars ($string);

    $charset = strtolower($charset);

    set_my_charset() ;

    if (ereg('iso-8859-([[:digit:]]+)', $charset, $res)) {
        if ($res[1] == '1') {
            $ret = charset_decode_iso_8859_1 ($string);
        } else if ($res[1] == '2') {
            $ret = charset_decode_iso_8859_2 ($string);
        } else if ($res[1] == '4') {
            $ret = charset_decode_iso_8859_4 ($string);
        } else if ($res[1] == '5') {
            $ret = charset_decode_iso_8859_5 ($string);
        } else if ($res[1] == '7') {
            $ret = charset_decode_iso_8859_7 ($string);
        } else if ($res[1] == '9') {
            $ret = charset_decode_iso_8859_9 ($string);
        } else if ($res[1] == '13') {
            $ret = charset_decode_iso_8859_13 ($string);
        } else if ($res[1] == '15') {
            $ret = charset_decode_iso_8859_15 ($string);
        } else {
            $ret = charset_decode_iso_8859_default ($string);
        }
    } else if ($charset == 'ns_4551-1') {
        $ret = charset_decode_ns_4551_1 ($string);
    } else if ($charset == 'koi8-r') {
        $ret = charset_decode_koi8r ($string);
    } else if ($charset == 'windows-1251') {
        $ret = charset_decode_windows_1251 ($string);
    } else if ($charset == 'windows-1253') {
	$ret = charset_decode_windows_1253 ($string);
    } else if ($charset == 'windows-1254') {
	$ret = charset_decode_windows_1254 ($string);
    } else if ($charset == 'windows-1257') {
        $ret = charset_decode_windows_1257 ($string);
    } else if ($charset == 'utf-8') {
	$ret = charset_decode_utf8 ($string);
    } else {
        $ret = $string;
    }
    return( $ret );
}

/*
 iso-8859-1 is the same as Latin 1 and is normally used
 in western europe.
 */
function charset_decode_iso_8859_1 ($string) {
    global $default_charset;

    if (strtolower($default_charset) <> 'iso-8859-1') {
        /* Only do the slow convert if there are 8-bit characters */
        if (ereg("[\200-\377]", $string)) {
            $string = str_replace("\201", '&#129;', $string);
            $string = str_replace("\202", '&#130;', $string);
            $string = str_replace("\203", '&#131;', $string);
            $string = str_replace("\204", '&#132;', $string);
            $string = str_replace("\205", '&#133;', $string);
            $string = str_replace("\206", '&#134;', $string);
            $string = str_replace("\207", '&#135;', $string);
            $string = str_replace("\210", '&#136;', $string);
            $string = str_replace("\211", '&#137;', $string);
            $string = str_replace("\212", '&#138;', $string);
            $string = str_replace("\213", '&#139;', $string);
            $string = str_replace("\214", '&#140;', $string);
            $string = str_replace("\215", '&#141;', $string);
            $string = str_replace("\216", '&#142;', $string);
            $string = str_replace("\217", '&#143;', $string);
            $string = str_replace("\220", '&#144;', $string);
            $string = str_replace("\221", '&#145;', $string);
            $string = str_replace("\222", '&#146;', $string);
            $string = str_replace("\223", '&#147;', $string);
            $string = str_replace("\224", '&#148;', $string);
            $string = str_replace("\225", '&#149;', $string);
            $string = str_replace("\226", '&#150;', $string);
            $string = str_replace("\227", '&#151;', $string);
            $string = str_replace("\230", '&#152;', $string);
            $string = str_replace("\231", '&#153;', $string);
            $string = str_replace("\232", '&#154;', $string);
            $string = str_replace("\233", '&#155;', $string);
            $string = str_replace("\234", '&#156;', $string);
            $string = str_replace("\235", '&#157;', $string);
            $string = str_replace("\236", '&#158;', $string);
            $string = str_replace("\237", '&#159;', $string);
            $string = str_replace("\240", '&#160;', $string);
            $string = str_replace("\241", '&#161;', $string);
            $string = str_replace("\242", '&#162;', $string);
            $string = str_replace("\243", '&#163;', $string);
            $string = str_replace("\244", '&#164;', $string);
            $string = str_replace("\245", '&#165;', $string);
            $string = str_replace("\246", '&#166;', $string);
            $string = str_replace("\247", '&#167;', $string);
            $string = str_replace("\250", '&#168;', $string);
            $string = str_replace("\251", '&#169;', $string);
            $string = str_replace("\252", '&#170;', $string);
            $string = str_replace("\253", '&#171;', $string);
            $string = str_replace("\254", '&#172;', $string);
            $string = str_replace("\255", '&#173;', $string);
            $string = str_replace("\256", '&#174;', $string);
            $string = str_replace("\257", '&#175;', $string);
            $string = str_replace("\260", '&#176;', $string);
            $string = str_replace("\261", '&#177;', $string);
            $string = str_replace("\262", '&#178;', $string);
            $string = str_replace("\263", '&#179;', $string);
            $string = str_replace("\264", '&#180;', $string);
            $string = str_replace("\265", '&#181;', $string);
            $string = str_replace("\266", '&#182;', $string);
            $string = str_replace("\267", '&#183;', $string);
            $string = str_replace("\270", '&#184;', $string);
            $string = str_replace("\271", '&#185;', $string);
            $string = str_replace("\272", '&#186;', $string);
            $string = str_replace("\273", '&#187;', $string);
            $string = str_replace("\274", '&#188;', $string);
            $string = str_replace("\275", '&#189;', $string);
            $string = str_replace("\276", '&#190;', $string);
            $string = str_replace("\277", '&#191;', $string);
            $string = str_replace("\300", '&#192;', $string);
            $string = str_replace("\301", '&#193;', $string);
            $string = str_replace("\302", '&#194;', $string);
            $string = str_replace("\303", '&#195;', $string);
            $string = str_replace("\304", '&#196;', $string);
            $string = str_replace("\305", '&#197;', $string);
            $string = str_replace("\306", '&#198;', $string);
            $string = str_replace("\307", '&#199;', $string);
            $string = str_replace("\310", '&#200;', $string);
            $string = str_replace("\311", '&#201;', $string);
            $string = str_replace("\312", '&#202;', $string);
            $string = str_replace("\313", '&#203;', $string);
            $string = str_replace("\314", '&#204;', $string);
            $string = str_replace("\315", '&#205;', $string);
            $string = str_replace("\316", '&#206;', $string);
            $string = str_replace("\317", '&#207;', $string);
            $string = str_replace("\320", '&#208;', $string);
            $string = str_replace("\321", '&#209;', $string);
            $string = str_replace("\322", '&#210;', $string);
            $string = str_replace("\323", '&#211;', $string);
            $string = str_replace("\324", '&#212;', $string);
            $string = str_replace("\325", '&#213;', $string);
            $string = str_replace("\326", '&#214;', $string);
            $string = str_replace("\327", '&#215;', $string);
            $string = str_replace("\330", '&#216;', $string);
            $string = str_replace("\331", '&#217;', $string);
            $string = str_replace("\332", '&#218;', $string);
            $string = str_replace("\333", '&#219;', $string);
            $string = str_replace("\334", '&#220;', $string);
            $string = str_replace("\335", '&#221;', $string);
            $string = str_replace("\336", '&#222;', $string);
            $string = str_replace("\337", '&#223;', $string);
            $string = str_replace("\340", '&#224;', $string);
            $string = str_replace("\341", '&#225;', $string);
            $string = str_replace("\342", '&#226;', $string);
            $string = str_replace("\343", '&#227;', $string);
            $string = str_replace("\344", '&#228;', $string);
            $string = str_replace("\345", '&#229;', $string);
            $string = str_replace("\346", '&#230;', $string);
            $string = str_replace("\347", '&#231;', $string);
            $string = str_replace("\350", '&#232;', $string);
            $string = str_replace("\351", '&#233;', $string);
            $string = str_replace("\352", '&#234;', $string);
            $string = str_replace("\353", '&#235;', $string);
            $string = str_replace("\354", '&#236;', $string);
            $string = str_replace("\355", '&#237;', $string);
            $string = str_replace("\356", '&#238;', $string);
            $string = str_replace("\357", '&#239;', $string);
            $string = str_replace("\360", '&#240;', $string);
            $string = str_replace("\361", '&#241;', $string);
            $string = str_replace("\362", '&#242;', $string);
            $string = str_replace("\363", '&#243;', $string);
            $string = str_replace("\364", '&#244;', $string);
            $string = str_replace("\365", '&#245;', $string);
            $string = str_replace("\366", '&#246;', $string);
            $string = str_replace("\367", '&#247;', $string);
            $string = str_replace("\370", '&#248;', $string);
            $string = str_replace("\371", '&#249;', $string);
            $string = str_replace("\372", '&#250;', $string);
            $string = str_replace("\373", '&#251;', $string);
            $string = str_replace("\374", '&#252;', $string);
            $string = str_replace("\375", '&#253;', $string);
            $string = str_replace("\376", '&#254;', $string);
            $string = str_replace("\377", '&#255;', $string);
        }
    }

    return ($string);
}

/* iso-8859-2 is used for some eastern European languages */
function charset_decode_iso_8859_2 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-2')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    /* NO-BREAK SPACE */
    $string = str_replace("\240", '&#160;', $string);
    /* LATIN CAPITAL LETTER A WITH OGONEK */
    $string = str_replace("\241", '&#260;', $string);
    /* BREVE */
    $string = str_replace("\242", '&#728;', $string);
    // LATIN CAPITAL LETTER L WITH STROKE
    $string = str_replace("\243", '&#321;', $string);
    // CURRENCY SIGN
    $string = str_replace("\244", '&#164;', $string);
    // LATIN CAPITAL LETTER L WITH CARON
    $string = str_replace("\245", '&#317;', $string);
    // LATIN CAPITAL LETTER S WITH ACUTE
    $string = str_replace("\246", '&#346;', $string);
    // SECTION SIGN
    $string = str_replace("\247", '&#167;', $string);
    // DIAERESIS
    $string = str_replace("\250", '&#168;', $string);
    // LATIN CAPITAL LETTER S WITH CARON
    $string = str_replace("\251", '&#352;', $string);
    // LATIN CAPITAL LETTER S WITH CEDILLA
    $string = str_replace("\252", '&#350;', $string);
    // LATIN CAPITAL LETTER T WITH CARON
    $string = str_replace("\253", '&#356;', $string);
    // LATIN CAPITAL LETTER Z WITH ACUTE
    $string = str_replace("\254", '&#377;', $string);
    // SOFT HYPHEN
    $string = str_replace("\255", '&#173;', $string);
    // LATIN CAPITAL LETTER Z WITH CARON
    $string = str_replace("\256", '&#381;', $string);
    // LATIN CAPITAL LETTER Z WITH DOT ABOVE
    $string = str_replace("\257", '&#379;', $string);
    // DEGREE SIGN
    $string = str_replace("\260", '&#176;', $string);
    // LATIN SMALL LETTER A WITH OGONEK
    $string = str_replace("\261", '&#261;', $string);
    // OGONEK
    $string = str_replace("\262", '&#731;', $string);
    // LATIN SMALL LETTER L WITH STROKE
    $string = str_replace("\263", '&#322;', $string);
    // ACUTE ACCENT
    $string = str_replace("\264", '&#180;', $string);
    // LATIN SMALL LETTER L WITH CARON
    $string = str_replace("\265", '&#318;', $string);
    // LATIN SMALL LETTER S WITH ACUTE
    $string = str_replace("\266", '&#347;', $string);
    // CARON
    $string = str_replace("\267", '&#711;', $string);
    // CEDILLA
    $string = str_replace("\270", '&#184;', $string);
    // LATIN SMALL LETTER S WITH CARON
    $string = str_replace("\271", '&#353;', $string);
    // LATIN SMALL LETTER S WITH CEDILLA
    $string = str_replace("\272", '&#351;', $string);
    // LATIN SMALL LETTER T WITH CARON
    $string = str_replace("\273", '&#357;', $string);
    // LATIN SMALL LETTER Z WITH ACUTE
    $string = str_replace("\274", '&#378;', $string);
    // DOUBLE ACUTE ACCENT
    $string = str_replace("\275", '&#733;', $string);
    // LATIN SMALL LETTER Z WITH CARON
    $string = str_replace("\276", '&#382;', $string);
    // LATIN SMALL LETTER Z WITH DOT ABOVE
    $string = str_replace("\277", '&#380;', $string);
    // LATIN CAPITAL LETTER R WITH ACUTE
    $string = str_replace("\300", '&#340;', $string);
    // LATIN CAPITAL LETTER A WITH ACUTE
    $string = str_replace("\301", '&#193;', $string);
    // LATIN CAPITAL LETTER A WITH CIRCUMFLEX
    $string = str_replace("\302", '&#194;', $string);
    // LATIN CAPITAL LETTER A WITH BREVE
    $string = str_replace("\303", '&#258;', $string);
    // LATIN CAPITAL LETTER A WITH DIAERESIS
    $string = str_replace("\304", '&#196;', $string);
    // LATIN CAPITAL LETTER L WITH ACUTE
    $string = str_replace("\305", '&#313;', $string);
    // LATIN CAPITAL LETTER C WITH ACUTE
    $string = str_replace("\306", '&#262;', $string);
    // LATIN CAPITAL LETTER C WITH CEDILLA
    $string = str_replace("\307", '&#199;', $string);
    // LATIN CAPITAL LETTER C WITH CARON
    $string = str_replace("\310", '&#268;', $string);
    // LATIN CAPITAL LETTER E WITH ACUTE
    $string = str_replace("\311", '&#201;', $string);
    // LATIN CAPITAL LETTER E WITH OGONEK
    $string = str_replace("\312", '&#280;', $string);
    // LATIN CAPITAL LETTER E WITH DIAERESIS
    $string = str_replace("\313", '&#203;', $string);
    // LATIN CAPITAL LETTER E WITH CARON
    $string = str_replace("\314", '&#282;', $string);
    // LATIN CAPITAL LETTER I WITH ACUTE
    $string = str_replace("\315", '&#205;', $string);
    // LATIN CAPITAL LETTER I WITH CIRCUMFLEX
    $string = str_replace("\316", '&#206;', $string);
    // LATIN CAPITAL LETTER D WITH CARON
    $string = str_replace("\317", '&#270;', $string);
    // LATIN CAPITAL LETTER D WITH STROKE
    $string = str_replace("\320", '&#272;', $string);
    // LATIN CAPITAL LETTER N WITH ACUTE
    $string = str_replace("\321", '&#323;', $string);
    // LATIN CAPITAL LETTER N WITH CARON
    $string = str_replace("\322", '&#327;', $string);
    // LATIN CAPITAL LETTER O WITH ACUTE
    $string = str_replace("\323", '&#211;', $string);
    // LATIN CAPITAL LETTER O WITH CIRCUMFLEX
    $string = str_replace("\324", '&#212;', $string);
    // LATIN CAPITAL LETTER O WITH DOUBLE ACUTE
    $string = str_replace("\325", '&#336;', $string);
    // LATIN CAPITAL LETTER O WITH DIAERESIS
    $string = str_replace("\326", '&#214;', $string);
    // MULTIPLICATION SIGN
    $string = str_replace("\327", '&#215;', $string);
    // LATIN CAPITAL LETTER R WITH CARON
    $string = str_replace("\330", '&#344;', $string);
    // LATIN CAPITAL LETTER U WITH RING ABOVE
    $string = str_replace("\331", '&#366;', $string);
    // LATIN CAPITAL LETTER U WITH ACUTE
    $string = str_replace("\332", '&#218;', $string);
    // LATIN CAPITAL LETTER U WITH DOUBLE ACUTE
    $string = str_replace("\333", '&#368;', $string);
    // LATIN CAPITAL LETTER U WITH DIAERESIS
    $string = str_replace("\334", '&#220;', $string);
    // LATIN CAPITAL LETTER Y WITH ACUTE
    $string = str_replace("\335", '&#221;', $string);
    // LATIN CAPITAL LETTER T WITH CEDILLA
    $string = str_replace("\336", '&#354;', $string);
    // LATIN SMALL LETTER SHARP S
    $string = str_replace("\337", '&#223;', $string);
    // LATIN SMALL LETTER R WITH ACUTE
    $string = str_replace("\340", '&#341;', $string);
    // LATIN SMALL LETTER A WITH ACUTE
    $string = str_replace("\341", '&#225;', $string);
    // LATIN SMALL LETTER A WITH CIRCUMFLEX
    $string = str_replace("\342", '&#226;', $string);
    // LATIN SMALL LETTER A WITH BREVE
    $string = str_replace("\343", '&#259;', $string);
    // LATIN SMALL LETTER A WITH DIAERESIS
    $string = str_replace("\344", '&#228;', $string);
    // LATIN SMALL LETTER L WITH ACUTE
    $string = str_replace("\345", '&#314;', $string);
    // LATIN SMALL LETTER C WITH ACUTE
    $string = str_replace("\346", '&#263;', $string);
    // LATIN SMALL LETTER C WITH CEDILLA
    $string = str_replace("\347", '&#231;', $string);
    // LATIN SMALL LETTER C WITH CARON
    $string = str_replace("\350", '&#269;', $string);
    // LATIN SMALL LETTER E WITH ACUTE
    $string = str_replace("\351", '&#233;', $string);
    // LATIN SMALL LETTER E WITH OGONEK
    $string = str_replace("\352", '&#281;', $string);
    // LATIN SMALL LETTER E WITH DIAERESIS
    $string = str_replace("\353", '&#235;', $string);
    // LATIN SMALL LETTER E WITH CARON
    $string = str_replace("\354", '&#283;', $string);
    // LATIN SMALL LETTER I WITH ACUTE
    $string = str_replace("\355", '&#237;', $string);
    // LATIN SMALL LETTER I WITH CIRCUMFLEX
    $string = str_replace("\356", '&#238;', $string);
    // LATIN SMALL LETTER D WITH CARON
    $string = str_replace("\357", '&#271;', $string);
    // LATIN SMALL LETTER D WITH STROKE
    $string = str_replace("\360", '&#273;', $string);
    // LATIN SMALL LETTER N WITH ACUTE
    $string = str_replace("\361", '&#324;', $string);
    // LATIN SMALL LETTER N WITH CARON
    $string = str_replace("\362", '&#328;', $string);
    // LATIN SMALL LETTER O WITH ACUTE
    $string = str_replace("\363", '&#243;', $string);
    // LATIN SMALL LETTER O WITH CIRCUMFLEX
    $string = str_replace("\364", '&#244;', $string);
    // LATIN SMALL LETTER O WITH DOUBLE ACUTE
    $string = str_replace("\365", '&#337;', $string);
    // LATIN SMALL LETTER O WITH DIAERESIS
    $string = str_replace("\366", '&#246;', $string);
    // DIVISION SIGN
    $string = str_replace("\367", '&#247;', $string);
    // LATIN SMALL LETTER R WITH CARON
    $string = str_replace("\370", '&#345;', $string);
    // LATIN SMALL LETTER U WITH RING ABOVE
    $string = str_replace("\371", '&#367;', $string);
    // LATIN SMALL LETTER U WITH ACUTE
    $string = str_replace("\372", '&#250;', $string);
    // LATIN SMALL LETTER U WITH DOUBLE ACUTE
    $string = str_replace("\373", '&#369;', $string);
    // LATIN SMALL LETTER U WITH DIAERESIS
    $string = str_replace("\374", '&#252;', $string);
    // LATIN SMALL LETTER Y WITH ACUTE
    $string = str_replace("\375", '&#253;', $string);
    // LATIN SMALL LETTER T WITH CEDILLA
    $string = str_replace("\376", '&#355;', $string);
    // DOT ABOVE
    $string = str_replace("\377", '&#729;', $string);

    return $string;
}

/* 
 ISO/IEC 8859-4:1998 Latin Alphabet No. 4
*/

function charset_decode_iso_8859_4 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-4')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    $string = str_replace ("\241", '&#260;', $string);
    $string = str_replace ("\242", '&#312;', $string);
    $string = str_replace ("\243", '&#342;', $string);
    $string = str_replace ("\245", '&#296;', $string);
    $string = str_replace ("\246", '&#315;', $string);
    $string = str_replace ("\251", '&#352;', $string);
    $string = str_replace ("\252", '&#274;', $string);
    $string = str_replace ("\253", '&#290;', $string);
    $string = str_replace ("\254", '&#358;', $string);
    $string = str_replace ("\256", '&#381;', $string);
    $string = str_replace ("\261", '&#261;', $string);
    $string = str_replace ("\262", '&#731;', $string);
    $string = str_replace ("\263", '&#343;', $string);
    $string = str_replace ("\265", '&#297;', $string);
    $string = str_replace ("\266", '&#316;', $string);
    $string = str_replace ("\267", '&#711;', $string);
    $string = str_replace ("\271", '&#353;', $string);
    $string = str_replace ("\272", '&#275;', $string);
    $string = str_replace ("\273", '&#291;', $string);
    $string = str_replace ("\274", '&#359;', $string);
    $string = str_replace ("\275", '&#330;', $string);
    $string = str_replace ("\276", '&#382;', $string);
    $string = str_replace ("\277", '&#331;', $string);
    $string = str_replace ("\300", '&#256;', $string);
    $string = str_replace ("\307", '&#302;', $string);
    $string = str_replace ("\310", '&#268;', $string);
    $string = str_replace ("\312", '&#280;', $string);
    $string = str_replace ("\314", '&#278;', $string);
    $string = str_replace ("\317", '&#298;', $string);
    $string = str_replace ("\320", '&#272;', $string);
    $string = str_replace ("\321", '&#325;', $string);
    $string = str_replace ("\322", '&#332;', $string);
    $string = str_replace ("\323", '&#310;', $string);
    $string = str_replace ("\331", '&#370;', $string);
    $string = str_replace ("\335", '&#360;', $string);
    $string = str_replace ("\336", '&#362;', $string);
    $string = str_replace ("\340", '&#257;', $string);
    $string = str_replace ("\347", '&#303;', $string);
    $string = str_replace ("\350", '&#269;', $string);
    $string = str_replace ("\352", '&#281;', $string);
    $string = str_replace ("\354", '&#279;', $string);
    $string = str_replace ("\357", '&#299;', $string);
    $string = str_replace ("\360", '&#273;', $string);
    $string = str_replace ("\361", '&#326;', $string);
    $string = str_replace ("\362", '&#333;', $string);
    $string = str_replace ("\363", '&#311;', $string);
    $string = str_replace ("\371", '&#371;', $string);
    $string = str_replace ("\375", '&#361;', $string);
    $string = str_replace ("\376", '&#363;', $string);
    $string = str_replace ("\377", '&#729;', $string);

    // rest of charset is the same as ISO-8859-1
    return (charset_decode_iso_8859_1($string));
}

/* iso-8859-7 is Greek. */
function charset_decode_iso_8859_7 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-7') {
        return $string;
    }

    /* Only do the slow convert if there are 8-bit characters */
    if (!ereg("[\200-\377]", $string)) {
        return $string;
    }

    /* Some diverse characters in the beginning */
    $string = str_replace("\240", '&#160;', $string);
    $string = str_replace("\241", '&#8216;', $string);
    $string = str_replace("\242", '&#8217;', $string);
    $string = str_replace("\243", '&#163;', $string);
    $string = str_replace("\246", '&#166;', $string);
    $string = str_replace("\247", '&#167;', $string);
    $string = str_replace("\250", '&#168;', $string);
    $string = str_replace("\251", '&#169;', $string);
    $string = str_replace("\253", '&#171;', $string);
    $string = str_replace("\254", '&#172;', $string);
    $string = str_replace("\255", '&#173;', $string);
    $string = str_replace("\257", '&#8213;', $string);
    $string = str_replace("\260", '&#176;', $string);
    $string = str_replace("\261", '&#177;', $string);
    $string = str_replace("\262", '&#178;', $string);
    $string = str_replace("\263", '&#179;', $string);

    /* Horizontal bar (parentheki pavla) */
    $string = str_replace ("\257", '&#8213;', $string);

    /*
     * ISO-8859-7 characters from 11/04 (0xB4) to 11/06 (0xB6)
     * These are Unicode 900-902
     */
    $string = preg_replace("/([\264-\266])/e","'&#' . (ord('\\1')+720);",$string);
    
    /* 11/07 (0xB7) Middle dot is the same in iso-8859-1 */
    $string = str_replace("\267", '&#183;', $string);

    /*
     * ISO-8859-7 characters from 11/08 (0xB8) to 11/10 (0xBA)
     * These are Unicode 900-902
     */
    $string = preg_replace("/([\270-\272])/e","'&#' . (ord('\\1')+720);",$string);

    /*
     * 11/11 (0xBB) Right angle quotation mark is the same as in
     * iso-8859-1
     */
    $string = str_replace("\273", '&#187;', $string);

    /* And now the rest of the charset */
    $string = preg_replace("/([\274-\376])/e","'&#'.(ord('\\1')+720);",$string);

    return $string;
}

/*
 ISOIEC 8859-9:1999 Latin Alphabet No. 5
*/
function charset_decode_iso_8859_9 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-9')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    // latin capital letter g with breve 208->286
    $string = str_replace("\320", '&#286;', $string);
    // latin capital letter i with dot above 221->304
    $string = str_replace("\335", '&#304;', $string);
    // latin capital letter s with cedilla 222->350
    $string = str_replace("\336", '&#350;', $string);
    // latin small letter g with breve 240->287
    $string = str_replace("\360", '&#287;', $string);
    // latin small letter dotless i 253->305
    $string = str_replace("\375", '&#305;', $string);
    // latin small letter s with cedilla 254->351
    $string = str_replace("\376", '&#351;', $string);

    // rest of charset is the same as ISO-8859-1
    return (charset_decode_iso_8859_1($string));
}


/*
 ISO/IEC 8859-13:1998 Latin Alphabet No. 7 (Baltic Rim) 
*/
function charset_decode_iso_8859_13 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'iso-8859-13')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    $string = str_replace ("\241", '&#8221;', $string);
    $string = str_replace ("\245", '&#8222;', $string);
    $string = str_replace ("\250", '&#216;', $string);
    $string = str_replace ("\252", '&#342;', $string);
    $string = str_replace ("\257", '&#198;', $string);
    $string = str_replace ("\264", '&#8220;', $string);
    $string = str_replace ("\270", '&#248;', $string);
    $string = str_replace ("\272", '&#343;', $string);
    $string = str_replace ("\277", '&#230;', $string);
    $string = str_replace ("\300", '&#260;', $string);
    $string = str_replace ("\301", '&#302;', $string);
    $string = str_replace ("\302", '&#256;', $string);
    $string = str_replace ("\303", '&#262;', $string);
    $string = str_replace ("\306", '&#280;', $string);
    $string = str_replace ("\307", '&#274;', $string);
    $string = str_replace ("\310", '&#268;', $string);
    $string = str_replace ("\312", '&#377;', $string);
    $string = str_replace ("\313", '&#278;', $string);
    $string = str_replace ("\314", '&#290;', $string);
    $string = str_replace ("\315", '&#310;', $string);
    $string = str_replace ("\316", '&#298;', $string);
    $string = str_replace ("\317", '&#315;', $string);
    $string = str_replace ("\320", '&#352;', $string);
    $string = str_replace ("\321", '&#323;', $string);
    $string = str_replace ("\322", '&#325;', $string);
    $string = str_replace ("\324", '&#332;', $string);
    $string = str_replace ("\330", '&#370;', $string);
    $string = str_replace ("\331", '&#321;', $string);
    $string = str_replace ("\332", '&#346;', $string);
    $string = str_replace ("\333", '&#362;', $string);
    $string = str_replace ("\335", '&#379;', $string);
    $string = str_replace ("\336", '&#381;', $string);
    $string = str_replace ("\340", '&#261;', $string);
    $string = str_replace ("\341", '&#303;', $string);
    $string = str_replace ("\342", '&#257;', $string);
    $string = str_replace ("\343", '&#263;', $string);
    $string = str_replace ("\346", '&#281;', $string);
    $string = str_replace ("\347", '&#275;', $string);
    $string = str_replace ("\350", '&#269;', $string);
    $string = str_replace ("\352", '&#378;', $string);
    $string = str_replace ("\353", '&#279;', $string);
    $string = str_replace ("\354", '&#291;', $string);
    $string = str_replace ("\355", '&#311;', $string);
    $string = str_replace ("\356", '&#299;', $string);
    $string = str_replace ("\357", '&#316;', $string);
    $string = str_replace ("\360", '&#353;', $string);
    $string = str_replace ("\361", '&#324;', $string);
    $string = str_replace ("\362", '&#326;', $string);
    $string = str_replace ("\364", '&#333;', $string);
    $string = str_replace ("\370", '&#371;', $string);
    $string = str_replace ("\371", '&#322;', $string);
    $string = str_replace ("\372", '&#347;', $string);
    $string = str_replace ("\373", '&#363;', $string);    
    $string = str_replace ("\375", '&#380;', $string);
    $string = str_replace ("\376", '&#382;', $string);
    $string = str_replace ("\377", '&#8217;', $string);

    // rest of charset is the same as ISO-8859-1
    return (charset_decode_iso_8859_1($string));
}

/*
 * iso-8859-15 is Latin 9 and has very much the same use as Latin 1
 * but has the Euro symbol and some characters needed for French.
 */
function charset_decode_iso_8859_15 ($string) {
    // Euro sign
    $string = str_replace ("\244", '&#8364;', $string);
    // Latin capital letter S with caron
    $string = str_replace ("\246", '&#352;', $string);
    // Latin small letter s with caron
    $string = str_replace ("\250", '&#353;', $string);
    // Latin capital letter Z with caron
    $string = str_replace ("\264", '&#381;', $string);
    // Latin small letter z with caron
    $string = str_replace ("\270", '&#382;', $string);
    // Latin capital ligature OE
    $string = str_replace ("\274", '&#338;', $string);
    // Latin small ligature oe
    $string = str_replace ("\275", '&#339;', $string);
    // Latin capital letter Y with diaeresis
    $string = str_replace ("\276", '&#376;', $string);

    return (charset_decode_iso_8859_1($string));
}

/* ISO-8859-5 is Cyrillic */
function charset_decode_iso_8859_5 ($string) {
    // Convert to KOI8-R, then return this decoded.
    $string = convert_cyr_string($string, 'i', 'k');
    return charset_decode_koi8r($string);
}

/* Remove all 8 bit characters from all other ISO-8859 character sets */
function charset_decode_iso_8859_default ($string) {
    return (strtr($string, "\240\241\242\243\244\245\246\247".
                    "\250\251\252\253\254\255\256\257".
                    "\260\261\262\263\264\265\266\267".
                    "\270\271\272\273\274\275\276\277".
                    "\300\301\302\303\304\305\306\307".
                    "\310\311\312\313\314\315\316\317".
                    "\320\321\322\323\324\325\326\327".
                    "\330\331\332\333\334\335\336\337".
                    "\340\341\342\343\344\345\346\347".
                    "\350\351\352\353\354\355\356\357".
                    "\360\361\362\363\364\365\366\367".
                    "\370\371\372\373\374\375\376\377",
                    "????????????????????????????????????????".
                    "????????????????????????????????????????".
                    "????????????????????????????????????????".
                    "????????"));

}

/*
 * This is the same as ISO-646-NO and is used by some
 * Microsoft programs when sending Norwegian characters
 */
function charset_decode_ns_4551_1 ($string) {
    /*
     * These characters are:
     * Latin capital letter AE
     * Latin capital letter O with stroke
     * Latin capital letter A with ring above
     * and the same as small letters
     */
    return strtr ($string, "[\\]{|}", "ÆØÅæøå");
}

/*
 * KOI8-R is used to encode Russian mail (Cyrrilic). Defined in RFC
 * 1489.
 */
function charset_decode_koi8r ($string) {
    global $default_charset;

    if ($default_charset == 'koi8-r') {
        return $string;
    }

    /*
     * Convert to Unicode HTML entities.
     * This code is rather ineffective.
     */
    $string = str_replace("\200", '&#9472;', $string);
    $string = str_replace("\201", '&#9474;', $string);
    $string = str_replace("\202", '&#9484;', $string);
    $string = str_replace("\203", '&#9488;', $string);
    $string = str_replace("\204", '&#9492;', $string);
    $string = str_replace("\205", '&#9496;', $string);
    $string = str_replace("\206", '&#9500;', $string);
    $string = str_replace("\207", '&#9508;', $string);
    $string = str_replace("\210", '&#9516;', $string);
    $string = str_replace("\211", '&#9524;', $string);
    $string = str_replace("\212", '&#9532;', $string);
    $string = str_replace("\213", '&#9600;', $string);
    $string = str_replace("\214", '&#9604;', $string);
    $string = str_replace("\215", '&#9608;', $string);
    $string = str_replace("\216", '&#9612;', $string);
    $string = str_replace("\217", '&#9616;', $string);
    $string = str_replace("\220", '&#9617;', $string);
    $string = str_replace("\221", '&#9618;', $string);
    $string = str_replace("\222", '&#9619;', $string);
    $string = str_replace("\223", '&#8992;', $string);
    $string = str_replace("\224", '&#9632;', $string);
    $string = str_replace("\225", '&#8729;', $string);
    $string = str_replace("\226", '&#8730;', $string);
    $string = str_replace("\227", '&#8776;', $string);
    $string = str_replace("\230", '&#8804;', $string);
    $string = str_replace("\231", '&#8805;', $string);
    $string = str_replace("\232", '&#160;', $string);
    $string = str_replace("\233", '&#8993;', $string);
    $string = str_replace("\234", '&#176;', $string);
    $string = str_replace("\235", '&#178;', $string);
    $string = str_replace("\236", '&#183;', $string);
    $string = str_replace("\237", '&#247;', $string);
    $string = str_replace("\240", '&#9552;', $string);
    $string = str_replace("\241", '&#9553;', $string);
    $string = str_replace("\242", '&#9554;', $string);
    $string = str_replace("\243", '&#1105;', $string);
    $string = str_replace("\244", '&#9555;', $string);
    $string = str_replace("\245", '&#9556;', $string);
    $string = str_replace("\246", '&#9557;', $string);
    $string = str_replace("\247", '&#9558;', $string);
    $string = str_replace("\250", '&#9559;', $string);
    $string = str_replace("\251", '&#9560;', $string);
    $string = str_replace("\252", '&#9561;', $string);
    $string = str_replace("\253", '&#9562;', $string);
    $string = str_replace("\254", '&#9563;', $string);
    $string = str_replace("\255", '&#9564;', $string);
    $string = str_replace("\256", '&#9565;', $string);
    $string = str_replace("\257", '&#9566;', $string);
    $string = str_replace("\260", '&#9567;', $string);
    $string = str_replace("\261", '&#9568;', $string);
    $string = str_replace("\262", '&#9569;', $string);
    $string = str_replace("\263", '&#1025;', $string);
    $string = str_replace("\264", '&#9570;', $string);
    $string = str_replace("\265", '&#9571;', $string);
    $string = str_replace("\266", '&#9572;', $string);
    $string = str_replace("\267", '&#9573;', $string);
    $string = str_replace("\270", '&#9574;', $string);
    $string = str_replace("\271", '&#9575;', $string);
    $string = str_replace("\272", '&#9576;', $string);
    $string = str_replace("\273", '&#9577;', $string);
    $string = str_replace("\274", '&#9578;', $string);
    $string = str_replace("\275", '&#9579;', $string);
    $string = str_replace("\276", '&#9580;', $string);
    $string = str_replace("\277", '&#169;', $string);
    $string = str_replace("\300", '&#1102;', $string);
    $string = str_replace("\301", '&#1072;', $string);
    $string = str_replace("\302", '&#1073;', $string);
    $string = str_replace("\303", '&#1094;', $string);
    $string = str_replace("\304", '&#1076;', $string);
    $string = str_replace("\305", '&#1077;', $string);
    $string = str_replace("\306", '&#1092;', $string);
    $string = str_replace("\307", '&#1075;', $string);
    $string = str_replace("\310", '&#1093;', $string);
    $string = str_replace("\311", '&#1080;', $string);
    $string = str_replace("\312", '&#1081;', $string);
    $string = str_replace("\313", '&#1082;', $string);
    $string = str_replace("\314", '&#1083;', $string);
    $string = str_replace("\315", '&#1084;', $string);
    $string = str_replace("\316", '&#1085;', $string);
    $string = str_replace("\317", '&#1086;', $string);
    $string = str_replace("\320", '&#1087;', $string);
    $string = str_replace("\321", '&#1103;', $string);
    $string = str_replace("\322", '&#1088;', $string);
    $string = str_replace("\323", '&#1089;', $string);
    $string = str_replace("\324", '&#1090;', $string);
    $string = str_replace("\325", '&#1091;', $string);
    $string = str_replace("\326", '&#1078;', $string);
    $string = str_replace("\327", '&#1074;', $string);
    $string = str_replace("\330", '&#1100;', $string);
    $string = str_replace("\331", '&#1099;', $string);
    $string = str_replace("\332", '&#1079;', $string);
    $string = str_replace("\333", '&#1096;', $string);
    $string = str_replace("\334", '&#1101;', $string);
    $string = str_replace("\335", '&#1097;', $string);
    $string = str_replace("\336", '&#1095;', $string);
    $string = str_replace("\337", '&#1098;', $string);
    $string = str_replace("\340", '&#1070;', $string);
    $string = str_replace("\341", '&#1040;', $string);
    $string = str_replace("\342", '&#1041;', $string);
    $string = str_replace("\343", '&#1062;', $string);
    $string = str_replace("\344", '&#1044;', $string);
    $string = str_replace("\345", '&#1045;', $string);
    $string = str_replace("\346", '&#1060;', $string);
    $string = str_replace("\347", '&#1043;', $string);
    $string = str_replace("\350", '&#1061;', $string);
    $string = str_replace("\351", '&#1048;', $string);
    $string = str_replace("\352", '&#1049;', $string);
    $string = str_replace("\353", '&#1050;', $string);
    $string = str_replace("\354", '&#1051;', $string);
    $string = str_replace("\355", '&#1052;', $string);
    $string = str_replace("\356", '&#1053;', $string);
    $string = str_replace("\357", '&#1054;', $string);
    $string = str_replace("\360", '&#1055;', $string);
    $string = str_replace("\361", '&#1071;', $string);
    $string = str_replace("\362", '&#1056;', $string);
    $string = str_replace("\363", '&#1057;', $string);
    $string = str_replace("\364", '&#1058;', $string);
    $string = str_replace("\365", '&#1059;', $string);
    $string = str_replace("\366", '&#1046;', $string);
    $string = str_replace("\367", '&#1042;', $string);
    $string = str_replace("\370", '&#1068;', $string);
    $string = str_replace("\371", '&#1067;', $string);
    $string = str_replace("\372", '&#1047;', $string);
    $string = str_replace("\373", '&#1064;', $string);
    $string = str_replace("\374", '&#1069;', $string);
    $string = str_replace("\375", '&#1065;', $string);
    $string = str_replace("\376", '&#1063;', $string);
    $string = str_replace("\377", '&#1066;', $string);

    return $string;
}

/*
 windows-1254 (Turks)
 */
function charset_decode_windows_1254 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'windows-1254')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    // Euro sign 128 -> 8364
    $string = str_replace("\200", '&#8364;', $string);
    // Single low-9 quotation mark 130 -> 8218
    $string = str_replace("\202", '&#8218;', $string);
    // latin small letter f with hook 131 -> 402
    $string = str_replace("\203", '&#402;', $string);
    // Double low-9 quotation mark 132 -> 8222
    $string = str_replace("\204", '&#8222;', $string);
    // horizontal ellipsis 133 -> 8230
    $string = str_replace("\205", '&#8230;', $string);
    // dagger 134 -> 8224
    $string = str_replace("\206", '&#8224;', $string);
    // double dagger 135 -> 8225
    $string = str_replace("\207", '&#8225;', $string);
    // modifier letter circumflex accent 136->710
    $string = str_replace("\210", '&#710;', $string);
    // per mille sign 137 -> 8240
    $string = str_replace("\211", '&#8240;', $string);
    // latin capital letter s with caron 138 -> 352
    $string = str_replace("\212", '&#352;', $string);
    // single left-pointing angle quotation mark 139 -> 8249
    $string = str_replace("\213", '&#8249;', $string);
    // latin capital ligature oe 140 -> 338
    $string = str_replace("\214", '&#338;', $string);
    // left single quotation mark 145 -> 8216
    $string = str_replace("\221", '&#8216;', $string);
    // right single quotation mark 146 -> 8217
    $string = str_replace("\222", '&#8217;', $string);
    // left double quotation mark 147 -> 8220
    $string = str_replace("\223", '&#8220;', $string);
    // right double quotation mark 148 -> 8221
    $string = str_replace("\224", '&#8221;', $string);
    // bullet 149 -> 8226
    $string = str_replace("\225", '&#8226;', $string);
    // en dash 150 -> 8211
    $string = str_replace("\226", '&#8211;', $string);
    // em dash 151 -> 8212
    $string = str_replace("\227", '&#8212;', $string);
    // small tilde 152 -> 732
    $string = str_replace("\230", '&#732;', $string);
    // trade mark sign 153 -> 8482
    $string = str_replace("\231", '&#8482;', $string);
    // latin small letter s with caron 154 -> 353
    $string = str_replace("\232", '&#353;', $string);
    // single right-pointing angle quotation mark 155 -> 8250
    $string = str_replace("\233", '&#8250;', $string);
    // latin small ligature oe 156 -> 339
    $string = str_replace("\234", '&#339;', $string);
    // latin capital letter y with diaresis 159->376
    $string = str_replace("\237", '&#376;', $string);
    // latin capital letter g with breve 208->286
    $string = str_replace("\320", '&#286;', $string);
    // latin capital letter i with dot above 221->304
    $string = str_replace("\335", '&#304;', $string);
    // latin capital letter s with cedilla 222->350
    $string = str_replace("\336", '&#350;', $string);
    // latin small letter g with breve 240->287
    $string = str_replace("\360", '&#287;', $string);
    // latin small letter dotless i 253->305
    $string = str_replace("\375", '&#305;', $string);
    // latin small letter s with cedilla 254->351
    $string = str_replace("\376", '&#351;', $string);

    // Rest of charset is like iso-8859-1
    return (charset_decode_iso_8859_1($string));
}

/*
 windows-1253 (Greek)
 */
function charset_decode_windows_1253 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'windows-1253')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    $string = str_replace("\200", '&#8364;', $string);
    $string = str_replace("\202", '&#8218;', $string);
    $string = str_replace("\203", '&#402;', $string);
    $string = str_replace("\204", '&#8222;', $string);
    $string = str_replace("\205", '&#8230;', $string);
    $string = str_replace("\206", '&#8224;', $string);
    $string = str_replace("\207", '&#8225;', $string);
    $string = str_replace("\211", '&#8240;', $string);
    $string = str_replace("\213", '&#8249;', $string);
    $string = str_replace("\221", '&#8216;', $string);
    $string = str_replace("\222", '&#8217;', $string);
    $string = str_replace("\223", '&#8220;', $string);
    $string = str_replace("\224", '&#8221;', $string);
    $string = str_replace("\225", '&#8226;', $string);
    $string = str_replace("\226", '&#8211;', $string);
    $string = str_replace("\227", '&#8212;', $string);
    $string = str_replace("\231", '&#8482;', $string);
    $string = str_replace("\233", '&#8250;', $string);
    $string = str_replace("\241", '&#901;', $string);
    $string = str_replace("\242", '&#902;', $string);
    $string = str_replace ("\257", '&#8213;', $string);
    $string = str_replace("\264", '&#900;', $string);
    $string = str_replace("\270", '&#904;', $string);
    $string = str_replace ("\271", '&#905;', $string);
    $string = str_replace ("\272", '&#906;', $string);
    $string = str_replace ("\274", '&#908;', $string);
    // cycle for 190-254 symbols
    $string = preg_replace("/([\274-\376])/e","'&#' . (ord('\\1')+720);",$string);

    // Rest of charset is like iso-8859-1
    return (charset_decode_iso_8859_1($string));
}


/*
 windows-1257 (BaltRim)
 */
function charset_decode_windows_1257 ($string) {
    global $default_charset;

    if (strtolower($default_charset) == 'windows-1257')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
        return $string;

    $string = str_replace("\200", '&#8364;', $string);
    $string = str_replace("\202", '&#8218;', $string);
    $string = str_replace("\204", '&#8222;', $string);
    $string = str_replace("\205", '&#8230;', $string);
    $string = str_replace("\206", '&#8224;', $string);
    $string = str_replace("\207", '&#8225;', $string);
    $string = str_replace("\211", '&#8240;', $string);
    $string = str_replace("\213", '&#8249;', $string);
    $string = str_replace("\215", '&#168;', $string);
    $string = str_replace("\216", '&#711;', $string);
    $string = str_replace("\217", '&#184;', $string);
    $string = str_replace("\221", '&#8216;', $string);
    $string = str_replace("\222", '&#8217;', $string);
    $string = str_replace("\223", '&#8220;', $string);
    $string = str_replace("\224", '&#8221;', $string);
    $string = str_replace("\225", '&#8226;', $string);
    $string = str_replace("\226", '&#8211;', $string);
    $string = str_replace("\227", '&#8212;', $string);
    $string = str_replace("\231", '&#8482;', $string);
    $string = str_replace("\233", '&#8250;', $string);
    $string = str_replace("\235", '&#175;', $string);
    $string = str_replace("\236", '&#731;', $string);
    $string = str_replace ("\250", '&#216;', $string);
    $string = str_replace ("\252", '&#342;', $string);
    $string = str_replace ("\257", '&#198;', $string);
    $string = str_replace ("\270", '&#248;', $string);
    $string = str_replace ("\272", '&#343;', $string);
    $string = str_replace ("\277", '&#230;', $string);
    $string = str_replace ("\300", '&#260;', $string);
    $string = str_replace ("\301", '&#302;', $string);
    $string = str_replace ("\302", '&#256;', $string);
    $string = str_replace ("\303", '&#262;', $string);
    $string = str_replace ("\306", '&#280;', $string);
    $string = str_replace ("\307", '&#274;', $string);
    $string = str_replace ("\310", '&#268;', $string);
    $string = str_replace ("\312", '&#377;', $string);
    $string = str_replace ("\313", '&#278;', $string);
    $string = str_replace ("\314", '&#290;', $string);
    $string = str_replace ("\315", '&#310;', $string);
    $string = str_replace ("\316", '&#298;', $string);
    $string = str_replace ("\317", '&#315;', $string);
    $string = str_replace ("\320", '&#352;', $string);
    $string = str_replace ("\321", '&#323;', $string);
    $string = str_replace ("\322", '&#325;', $string);
    $string = str_replace ("\324", '&#332;', $string);
    $string = str_replace ("\330", '&#370;', $string);
    $string = str_replace ("\331", '&#321;', $string);
    $string = str_replace ("\332", '&#340;', $string);
    $string = str_replace ("\333", '&#362;', $string);
    $string = str_replace ("\335", '&#379;', $string);
    $string = str_replace ("\336", '&#381;', $string);
    $string = str_replace ("\340", '&#261;', $string);
    $string = str_replace ("\341", '&#303;', $string);
    $string = str_replace ("\342", '&#257;', $string);
    $string = str_replace ("\343", '&#263;', $string);
    $string = str_replace ("\346", '&#281;', $string);
    $string = str_replace ("\347", '&#275;', $string);
    $string = str_replace ("\350", '&#269;', $string);
    $string = str_replace ("\352", '&#378;', $string);
    $string = str_replace ("\353", '&#279;', $string);
    $string = str_replace ("\354", '&#291;', $string);
    $string = str_replace ("\355", '&#311;', $string);
    $string = str_replace ("\356", '&#299;', $string);
    $string = str_replace ("\357", '&#316;', $string);
    $string = str_replace ("\360", '&#353;', $string);
    $string = str_replace ("\361", '&#324;', $string);
    $string = str_replace ("\362", '&#326;', $string);
    $string = str_replace ("\364", '&#333;', $string);
    $string = str_replace ("\370", '&#371;', $string);
    $string = str_replace ("\371", '&#322;', $string);
    $string = str_replace ("\372", '&#347;', $string);
    $string = str_replace ("\373", '&#363;', $string);    
    $string = str_replace ("\375", '&#380;', $string);
    $string = str_replace ("\376", '&#382;', $string);
    $string = str_replace ("\377", '&#729;', $string);

    // Rest of charset is like iso-8859-1
    return (charset_decode_iso_8859_1($string));
}

/* windows-1251 is Microsoft Cyrillic encoding */
function charset_decode_windows_1251 ($string) {
    // Convert to KOI8-R, then return this decoded.
    $string = convert_cyr_string($string, 'w', 'k');
    return charset_decode_koi8r($string);
}



/*
 * Set up the language to be output
 * if $do_search is true, then scan the browser information
 * for a possible language that we know
 */
function set_up_language($sm_language, $do_search = false) {

    static $SetupAlready = 0;
    global $use_gettext, $languages,
           $squirrelmail_language, $squirrelmail_default_language,
           $sm_notAlias;

    if ($SetupAlready) {
        return;
    }

    $SetupAlready = TRUE;

    if ($do_search && ! $sm_language && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $sm_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    }
    
    if (!$sm_language && isset($squirrelmail_default_language)) {
        $squirrelmail_language = $squirrelmail_default_language;
        $sm_language = $squirrelmail_default_language;
    }
    $sm_notAlias = $sm_language;
    while (isset($languages[$sm_notAlias]['ALIAS'])) {
        $sm_notAlias = $languages[$sm_notAlias]['ALIAS'];
    }

    if ( isset($sm_language) &&
         $use_gettext &&
         $sm_language != '' &&
         isset($languages[$sm_notAlias]['CHARSET']) ) {
        bindtextdomain( 'squirrelmail', SM_PATH . 'locale/' );
        textdomain( 'squirrelmail' );
        if ( !ini_get('safe_mode') &&
             getenv( 'LC_ALL' ) != $sm_notAlias ) {
            putenv( "LC_ALL=$sm_notAlias" );
            putenv( "LANG=$sm_notAlias" );
            putenv( "LANGUAGE=$sm_notAlias" );
        }
        setlocale(LC_ALL, $sm_notAlias);
        $squirrelmail_language = $sm_notAlias;
        if ($squirrelmail_language == 'ja_JP' && function_exists('mb_detect_encoding') ) {
            header ('Content-Type: text/html; charset=EUC-JP');
            if (!function_exists('mb_internal_encoding')) {
                echo _("You need to have php4 installed with the multibyte string function enabled (using configure option --with-mbstring).");
            }
            mb_internal_encoding('EUC-JP');
            mb_http_output('pass');
        } else {
        header( 'Content-Type: text/html; charset=' . $languages[$sm_notAlias]['CHARSET'] );
    }
}
}

function set_my_charset(){

    /*
     * There can be a $default_charset setting in the
     * config.php file, but the user may have a different language
     * selected for a user interface. This function checks the
     * language selected by the user and tags the outgoing messages
     * with the appropriate charset corresponding to the language
     * selection. This is "more right" (tm), than just stamping the
     * message blindly with the system-wide $default_charset.
     */
    global $data_dir, $username, $default_charset, $languages, $squirrelmail_default_language;

    $my_language = getPref($data_dir, $username, 'language');
    if (!$my_language) {
        $my_language = $squirrelmail_default_language ;
    }
    while (isset($languages[$my_language]['ALIAS'])) {
        $my_language = $languages[$my_language]['ALIAS'];
    }
    $my_charset = $languages[$my_language]['CHARSET'];
    if ($my_charset) {
        $default_charset = $my_charset;
    }
}

/* ------------------------------ main --------------------------- */

global $squirrelmail_language, $languages, $use_gettext;

if (! isset($squirrelmail_language)) {
    $squirrelmail_language = '';
}

/* This array specifies the available languages. */

// The glibc locale is ca_ES.

$languages['ca_ES']['NAME']    = 'Catalan';
$languages['ca_ES']['CHARSET'] = 'iso-8859-1';
$languages['ca']['ALIAS'] = 'ca_ES';

$languages['cs_CZ']['NAME']    = 'Czech';
$languages['cs_CZ']['CHARSET'] = 'iso-8859-2';
$languages['cs']['ALIAS']      = 'cs_CZ';

// Danish locale is da_DK.

$languages['da_DK']['NAME']    = 'Danish';
$languages['da_DK']['CHARSET'] = 'iso-8859-1';
$languages['da']['ALIAS'] = 'da_DK';

$languages['de_DE']['NAME']    = 'Deutsch';
$languages['de_DE']['CHARSET'] = 'iso-8859-1';
$languages['de']['ALIAS'] = 'de_DE';

// There is no en_EN! There is en_US, en_BR, en_AU, and so forth, 
// but who cares about !US, right? Right? :)

$languages['el_GR']['NAME']    = 'Greek';
$languages['el_GR']['CHARSET'] = 'iso-8859-7';
$languages['el']['ALIAS'] = 'el_GR';

$languages['en_US']['NAME']    = 'English';
$languages['en_US']['CHARSET'] = 'iso-8859-1';
$languages['en']['ALIAS'] = 'en_US';

$languages['es_ES']['NAME']    = 'Spanish';
$languages['es_ES']['CHARSET'] = 'iso-8859-1';
$languages['es']['ALIAS'] = 'es_ES';

$languages['et_EE']['NAME']    = 'Estonian';
$languages['et_EE']['CHARSET'] = 'iso-8859-15';
$languages['et']['ALIAS'] = 'et_EE';

$languages['fi_FI']['NAME']    = 'Finnish';
$languages['fi_FI']['CHARSET'] = 'iso-8859-1';
$languages['fi']['ALIAS'] = 'fi_FI';

$languages['fr_FR']['NAME']    = 'French';
$languages['fr_FR']['CHARSET'] = 'iso-8859-1';
$languages['fr']['ALIAS'] = 'fr_FR';

$languages['hr_HR']['NAME']    = 'Croatian';
$languages['hr_HR']['CHARSET'] = 'iso-8859-2';
$languages['hr']['ALIAS'] = 'hr_HR';

$languages['hu_HU']['NAME']    = 'Hungarian';
$languages['hu_HU']['CHARSET'] = 'iso-8859-2';
$languages['hu']['ALIAS'] = 'hu_HU';

$languages['id_ID']['NAME']    = 'Indonesian';
$languages['id_ID']['CHARSET'] = 'iso-8859-1';
$languages['id']['ALIAS'] = 'id_ID';

$languages['is_IS']['NAME']    = 'Icelandic';
$languages['is_IS']['CHARSET'] = 'iso-8859-1';
$languages['is']['ALIAS'] = 'is_IS';

$languages['it_IT']['NAME']    = 'Italian';
$languages['it_IT']['CHARSET'] = 'iso-8859-1';
$languages['it']['ALIAS'] = 'it_IT';

$languages['ja_JP']['NAME']    = 'Japanese';
$languages['ja_JP']['CHARSET'] = 'iso-2022-jp';
$languages['ja_JP']['XTRA_CODE'] = 'japanese_charset_xtra';
$languages['ja']['ALIAS'] = 'ja_JP';

$languages['ko_KR']['NAME']    = 'Korean';
$languages['ko_KR']['CHARSET'] = 'euc-KR';
$languages['ko_KR']['XTRA_CODE'] = 'korean_charset_xtra';
$languages['ko']['ALIAS'] = 'ko_KR';

$languages['nl_NL']['NAME']    = 'Dutch';
$languages['nl_NL']['CHARSET'] = 'iso-8859-1';
$languages['nl']['ALIAS'] = 'nl_NL';

$languages['no_NO']['NAME']    = 'Norwegian (Bokm&aring;l)';
$languages['no_NO']['CHARSET'] = 'iso-8859-1';
$languages['no']['ALIAS'] = 'no_NO';
$languages['nn_NO']['NAME']    = 'Norwegian (Nynorsk)';
$languages['nn_NO']['CHARSET'] = 'iso-8859-1';

$languages['pl_PL']['NAME']    = 'Polish';
$languages['pl_PL']['CHARSET'] = 'iso-8859-2';
$languages['pl']['ALIAS'] = 'pl_PL';

$languages['pt_PT']['NAME'] = 'Portuguese (Portugal)';
$languages['pt_PT']['CHARSET'] = 'iso-8859-1';
$languages['pt_BR']['NAME']    = 'Portuguese (Brazil)';
$languages['pt_BR']['CHARSET'] = 'iso-8859-1';
$languages['pt']['ALIAS'] = 'pt_PT';

$languages['ru_RU']['NAME']    = 'Russian';
$languages['ru_RU']['CHARSET'] = 'koi8-r';
$languages['ru']['ALIAS'] = 'ru_RU';

$languages['sr_YU']['NAME']    = 'Serbian';
$languages['sr_YU']['CHARSET'] = 'iso-8859-2';
$languages['sr']['ALIAS'] = 'sr_YU';

$languages['sv_SE']['NAME']    = 'Swedish';
$languages['sv_SE']['CHARSET'] = 'iso-8859-1';
$languages['sv']['ALIAS'] = 'sv_SE';

$languages['tr_TR']['NAME']    = 'Turkish';
$languages['tr_TR']['CHARSET'] = 'iso-8859-9';
$languages['tr']['ALIAS'] = 'tr_TR';

$languages['zh_TW']['NAME']    = 'Chinese Trad';
$languages['zh_TW']['CHARSET'] = 'big5';
$languages['tw']['ALIAS'] = 'zh_TW';

$languages['zh_CN']['NAME']    = 'Chinese Simp';
$languages['zh_CN']['CHARSET'] = 'gb2312';
$languages['cn']['ALIAS'] = 'zh_CN';

$languages['sk_SK']['NAME']     = 'Slovak';
$languages['sk_SK']['CHARSET']  = 'iso-8859-2';
$languages['sk']['ALIAS']       = 'sk_SK';

$languages['ro_RO']['NAME']    = 'Romanian';
$languages['ro_RO']['CHARSET'] = 'iso-8859-2';
$languages['ro']['ALIAS'] = 'ro_RO';

$languages['th_TH']['NAME']    = 'Thai';
$languages['th_TH']['CHARSET'] = 'tis-620';
$languages['th']['ALIAS'] = 'th_TH';

$languages['lt_LT']['NAME']    = 'Lithuanian';
$languages['lt_LT']['CHARSET'] = 'windows-1257';
$languages['lt']['ALIAS'] = 'lt_LT';

$languages['sl_SI']['NAME']    = 'Slovenian';
$languages['sl_SI']['CHARSET'] = 'iso-8859-2';
$languages['sl']['ALIAS'] = 'sl_SI';

$languages['bg_BG']['NAME']    = 'Bulgarian';
$languages['bg_BG']['CHARSET'] = 'windows-1251';
$languages['bg']['ALIAS'] = 'bg_BG';

$languages['uk_UA']['NAME']    = 'Ukrainian';
$languages['uk_UA']['CHARSET'] = 'koi8-u';
$languages['uk']['ALIAS'] = 'uk_UA';

// Right to left languages

$languages['ar']['NAME']    = 'Arabic';
$languages['ar']['CHARSET'] = 'windows-1256';
$languages['ar']['DIR']     = 'rtl';

$languages['he_IL']['NAME']    = 'Hebrew';
$languages['he_IL']['CHARSET'] = 'windows-1255';
$languages['he_IL']['DIR']     = 'rtl';
$languages['he']['ALIAS']      = 'he_IL';

$languages['vi_VN']['NAME']    = 'Vietnamese';
$languages['vi_VN']['CHARSET'] = 'utf-8';
$languages['vi']['ALIAS'] = 'vi_VN';

/* Detect whether gettext is installed. */
$gettext_flags = 0;
if (function_exists('_')) {
    $gettext_flags += 1;
}
if (function_exists('bindtextdomain')) {
    $gettext_flags += 2;
}
if (function_exists('textdomain')) {
    $gettext_flags += 4;
}

/* If gettext is fully loaded, cool */
if ($gettext_flags == 7) {
    $use_gettext = true;
}
/* If we can fake gettext, try that */
elseif ($gettext_flags == 0) {
    $use_gettext = true;
    include_once(SM_PATH . 'functions/gettext.php');
} else {
    /* Uh-ho.  A weird install */
    if (! $gettext_flags & 1) {
        function _($str) {
            return $str;
        }
    }
    if (! $gettext_flags & 2) {
        function bindtextdomain() {
            return;
        }
    }
    if (! $gettext_flags & 4) {
        function textdomain() {
            return;
        }
    }
}

function charset_decode_utf8 ($string) {
/*
    Every decoded character consists of n bytes. First byte is octal
    300-375, other bytes - always octals 200-277.

    \a\b characters are decoded to html code octdec(a-300)*64 + octdec(b-200)
    \a\b\c characters are decoded to html code octdec(a-340)*64*64 + octdec(b-200)*64 + octdec(c-200)
    
    decoding cycle is unfinished. please test and report problems to tokul@users.sourceforge.net
*/
    global $default_charset, $languages, $sm_notAlias;

    if (strtolower($default_charset) == 'utf-8')
        return $string;
    if (strtolower($languages[$sm_notAlias]['CHARSET']) == 'utf-8')
        return $string;

    /* Only do the slow convert if there are 8-bit characters */
    if (! ereg("[\200-\377]", $string))
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

/*
 * Japanese charset extra function
 *
 */
function japanese_charset_xtra() {
    $ret = func_get_arg(1);  /* default return value */
    if (function_exists('mb_detect_encoding')) {
        switch (func_get_arg(0)) { /* action */
        case 'decode':
            $detect_encoding = mb_detect_encoding($ret);
            if ($detect_encoding == 'JIS' ||
                $detect_encoding == 'EUC-JP' ||
                $detect_encoding == 'SJIS') {
                
                $ret = mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
            }
            break;
        case 'encode':
            $detect_encoding = mb_detect_encoding($ret);
            if ($detect_encoding == 'JIS' ||
                $detect_encoding == 'EUC-JP' ||
                $detect_encoding == 'SJIS') {
                
                $ret = mb_convert_encoding($ret, 'JIS', 'AUTO');
            }
            break;
        case 'strimwidth':
            $width = func_get_arg(2);
            $ret = mb_strimwidth($ret, 0, $width, '...'); 
            break;
        case 'encodeheader':
            $result = '';
            if (strlen($ret) > 0) {
                $tmpstr = mb_substr($ret, 0, 1);
                $prevcsize = strlen($tmpstr);
                for ($i = 1; $i < mb_strlen($ret); $i++) {
                    $tmp = mb_substr($ret, $i, 1);
                    if (strlen($tmp) == $prevcsize) {
                        $tmpstr .= $tmp;
                    } else {
                        if ($prevcsize == 1) {
                            $result .= $tmpstr;
                        } else {
                            $result .= mb_encode_mimeheader($tmpstr);
                        }
                        $tmpstr = $tmp;
                        $prevcsize = strlen($tmp);
                    }
                }
                if (strlen($tmpstr)) {
                    if (strlen(mb_substr($tmpstr, 0, 1)) == 1)
                        $result .= $tmpstr;
                    else
                        $result .= mb_encode_mimeheader($tmpstr);
                }
            }
            $ret = $result;
            //$ret = mb_encode_mimeheader($ret);
            break;
        case 'decodeheader':
            $ret = str_replace("\t", "", $ret);
            if (eregi('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=', $ret))
                $ret = mb_decode_mimeheader($ret);
            $ret = mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
            break;
        case 'downloadfilename':
            $useragent = func_get_arg(2);
            if (strstr($useragent, 'Windows') !== false ||
                strstr($useragent, 'Mac_') !== false) {
                $ret = mb_convert_encoding($ret, 'SJIS', 'AUTO');
            } else {
                $ret = mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
}
            break;
        }
    }
    return $ret;
}


/*
 * Korean charset extra function
 * Hangul(Korean Character) Attached File Name Fix.
 */
function korean_charset_xtra() {
    
    $ret = func_get_arg(1);  /* default return value */
    if (func_get_arg(0) == 'downloadfilename') { /* action */
        $ret = str_replace("\x0D\x0A", '', $ret);  /* Hanmail's CR/LF Clear */
        for ($i=0;$i<strlen($ret);$i++) {
            if ($ret[$i] >= "\xA1" && $ret[$i] <= "\xFE") {   /* 0xA1 - 0XFE are Valid */
                $i++;
                continue;
            } else if (($ret[$i] >= 'a' && $ret[$i] <= 'z') || /* From Original ereg_replace in download.php */
                       ($ret[$i] >= 'A' && $ret[$i] <= 'Z') ||
                       ($ret[$i] == '.') || ($ret[$i] == '-')) {
                continue;
            } else {
                $ret[$i] = '_';
            }
        }

    }

    return $ret;
}

?>
