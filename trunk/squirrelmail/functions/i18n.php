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
 * @package squirrelmail
 */

/** Everything uses global.php... */
require_once(SM_PATH . 'functions/global.php');

/**
 * Converts string from given charset to charset, that can be displayed by user translation.
 *
 * Function by default returns html encoded strings, if translation uses different encoding.
 * If Japanese translation is used - function returns string converted to euc-jp
 * If iconv or recode functions are enabled and translation uses utf-8 - function returns utf-8 encoded string.
 * If $charset is not supported - function returns unconverted string.
 * 
 * sanitizing of html tags is also done by this function.
 *
 * @param string $charset
 * @param string $string Text to be decoded
 * @return string decoded string
 */
function charset_decode ($charset, $string) {
    global $languages, $squirrelmail_language, $default_charset;
    global $use_php_recode, $use_php_iconv, $agresive_decoding;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        $string = $languages[$squirrelmail_language]['XTRA_CODE']('decode', $string);
    }

    $charset = strtolower($charset);

    set_my_charset();

    // Variables that allow to use functions without function_exist() calls
    if (! isset($use_php_recode) || $use_php_recode=="" ) {
	     $use_php_recode=false; }
    if (! isset($use_php_iconv) || $use_php_iconv=="" ) {
         $use_php_iconv=false; }

    // Don't do conversion if charset is the same.
    if ( $charset == strtolower($default_charset) )
          return htmlspecialchars($string);

    // catch iso-8859-8-i thing
    if ( $charset == "iso-8859-8-i" )
              $charset = "iso-8859-8";

    /*
     * Recode converts html special characters automatically if you use 
     * 'charset..html' decoding. There is no documented way to put -d option 
     * into php recode function call.
     */
    if ( $use_php_recode ) {
      if ( $default_charset == "utf-8" ) {
	// other charsets can be converted to utf-8 without loss.
	// and output string is smaller
	$string = recode_string($charset . "..utf-8",$string);
	return htmlspecialchars($string);
      } else {
	$string = recode_string($charset . "..html",$string);
	// recode does not convert single quote, htmlspecialchars does.
	$string = str_replace("'", '&#039;', $string);
	return $string;
      }
    }

    // iconv functions does not have html target and can be used only with utf-8
    if ( $use_php_iconv && $default_charset=='utf-8') {
      $string = iconv($charset,$default_charset,$string);
      return htmlspecialchars($string);
    }

    // If we don't use recode and iconv, we'll do it old way.

    /* All HTML special characters are 7 bit and can be replaced first */
    
    $string = htmlspecialchars ($string);

    /* controls cpu and memory intensive decoding cycles */
    if (! isset($agresive_decoding) || $agresive_decoding=="" ) {
         $agresive_decoding=false; }

    $decode=fixcharset($charset);
    $decodefile=SM_PATH . 'functions/decode/' . $decode . '.php';
    if (file_exists($decodefile)) {
	include_once($decodefile);
	$ret = call_user_func('charset_decode_'.$decode, $string);
    } else {
	$ret = $string;
    }
    return( $ret );
}
		                                                                                                                           
/**
 * Makes charset name suitable for decoding cycles
 *
 * @param string $charset Name of charset
 * @return string $charset Adjusted name of charset
 */
function fixcharset($charset) {
    // minus removed from function names
    $charset=str_replace('-','_',$charset);
    
    // windows-125x and cp125x charsets
    $charset=str_replace('windows_','cp',$charset);

    // ibm > cp
    $charset=str_replace('ibm','cp',$charset);

    // iso-8859-8-i -> iso-8859-8
    // use same cycle until I'll find differences
    $charset=str_replace('iso_8859_8_i','iso_8859_8',$charset);

    return $charset;
}

/**
 * 8bit cleanup functions.
 *
 * Replaces all 8 bit characters from ISO-8859 character sets with '?'
 * Legacy function used for unsupported ISO-8859 charsets
 * 
 * @param string $string string that has to be cleaned
 * @return string cleaned string
 */
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

/**
 * ns_4551_1 decoding function
 *
 * This is the same as ISO-646-NO and is used by some
 * Microsoft programs when sending Norwegian characters
 * 
 * @param string $string
 * @return string 
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


/**
 * Set up the language to be output
 * if $do_search is true, then scan the browser information
 * for a possible language that we know
 *
 * Function sets system locale environment (LC_ALL, LANG, LANGUAGE), 
 * gettext translation bindings and html header information.
 *
 * Function returns error codes, if there is some fatal error.
 *  0 = no error, 
 *  1 = mbstring support is not present, 
 *  2 = mbstring support is not present, user's translation reverted to en_US.
 *
 * @param string $sm_language translation used by user's interface
 * @param bool $do_search use browser's preferred language detection functions. Defaults to false.
 * @param bool $default set $sm_language to $squirrelmail_default_language if language detection fails or language is not set. Defaults to false.
 * @return int function execution error codes. 
 */
function set_up_language($sm_language, $do_search = false, $default = false) {

    static $SetupAlready = 0;
    global $use_gettext, $languages,
           $squirrelmail_language, $squirrelmail_default_language,
           $sm_notAlias, $username, $data_dir;

    if ($SetupAlready) {
        return;
    }

    $SetupAlready = TRUE;
    sqgetGlobalVar('HTTP_ACCEPT_LANGUAGE',  $accept_lang, SQ_SERVER);

    if ($do_search && ! $sm_language && isset($accept_lang)) {
        $sm_language = substr($accept_lang, 0, 2);
    }
    
    if ((!$sm_language||$default) && isset($squirrelmail_default_language)) {
        $squirrelmail_language = $squirrelmail_default_language;
        $sm_language = $squirrelmail_default_language;
    }
    $sm_notAlias = $sm_language;
 
    // Catching removed translation
    // System reverts to English translation if user prefs contain translation
    // that is not available in $languages array (admin removed directory
    // with that translation)
    if (!isset($languages[$sm_notAlias])) {
      $sm_notAlias="en_US";
    }

    while (isset($languages[$sm_notAlias]['ALIAS'])) {
        $sm_notAlias = $languages[$sm_notAlias]['ALIAS'];
    }

    if ( isset($sm_language) &&
         $use_gettext &&
         $sm_language != '' &&
         isset($languages[$sm_notAlias]['CHARSET']) ) {
        bindtextdomain( 'squirrelmail', SM_PATH . 'locale/' );
        textdomain( 'squirrelmail' );
	if (function_exists('bind_textdomain_codeset')) {
            if ($sm_notAlias == 'ja_JP') {
        	bind_textdomain_codeset ("squirrelmail", 'EUC-JP');
            } else {
	        bind_textdomain_codeset ("squirrelmail", $languages[$sm_notAlias]['CHARSET'] );
	    }
	}
	if (isset($languages[$sm_notAlias]['LOCALE'])){
	  $longlocale=$languages[$sm_notAlias]['LOCALE'];
	} else {
	  $longlocale=$sm_notAlias;
	}
        if ( !ini_get('safe_mode') &&
             getenv( 'LC_ALL' ) != $longlocale ) {
            putenv( "LC_ALL=$longlocale" );
            putenv( "LANG=$longlocale" );
            putenv( "LANGUAGE=$longlocale" );
        }
	setlocale(LC_ALL, $longlocale);

	// Set text direction/alignment variables
	if (isset($languages[$sm_notAlias]['DIR']) && 
		$languages[$sm_notAlias]['DIR'] == 'rtl') {
	    $text_direction='rtl';
	    $left_align='right';
	    $right_align='left';
	} else {
	    $text_direction='ltr';
	    $left_align='left';
	    $right_align='right';
	}

	$squirrelmail_language = $sm_notAlias;
        if ($squirrelmail_language == 'ja_JP') {
            header ('Content-Type: text/html; charset=EUC-JP');
            if (!function_exists('mb_internal_encoding')) {
		// Error messages can't be displayed here
		$error = 1;
		// Revert to English if possible.
		if (function_exists('setPref')  && $username!='' && $data_dir!="") {
		    setPref($data_dir, $username, 'language', "en_US");
    		    $error = 2;		
		}
		// stop further execution in order not to get php errors on mb_internal_encoding().
		return $error;
            }
            if (function_exists('mb_language')) {
                mb_language('Japanese');
            }
            mb_internal_encoding('EUC-JP');
            mb_http_output('pass');
        } else {
        header( 'Content-Type: text/html; charset=' . $languages[$sm_notAlias]['CHARSET'] );
    }
}
    return 0;
}

/**
 * Sets default_charset variable according to the one that is used by user's translations.
 *
 * Function changes global $default_charset variable in order to be sure, that it
 * contains charset used by user's translation. Sanity of $squirrelmail_default_language
 * and $default_charset combination provided in SquirrelMail config is also tested.
 *
 * There can be a $default_charset setting in the
 * config.php file, but the user may have a different language
 * selected for a user interface. This function checks the
 * language selected by the user and tags the outgoing messages
 * with the appropriate charset corresponding to the language
 * selection. This is "more right" (tm), than just stamping the
 * message blindly with the system-wide $default_charset.
 */
function set_my_charset(){
    global $data_dir, $username, $default_charset, $languages, $squirrelmail_default_language;

    $my_language = getPref($data_dir, $username, 'language');
    if (!$my_language) {
        $my_language = $squirrelmail_default_language ;
    }
    // Catch removed translation
    if (!isset($languages[$my_language])) {
      $my_language="en_US";
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

/**
 * Array specifies the available translations.
 *
 * Structure of array:
 * $languages['language']['variable'] = 'value'
 * 
 * Possible 'variable' names:
 *  NAME      - Translation name in English
 *  CHARSET   - Encoding used by translation
 *  ALIAS     - used when 'language' is only short name and 'value' should provide long language name
 *  ALTNAME   - Native translation name. Any 8bit symbols must be html encoded.
 *  LOCALE    - Full locale name (in xx_XX.charset format)
 *  DIR       - Text direction. Used to define Right-to-Left languages. Possible values 'rtl' or 'ltr'. If undefined - defaults to 'ltr'
 *  XTRA_CODE - translation uses special functions. 'value' provides name of that extra function
 * 
 * Each 'language' definition requires NAME+CHARSET or ALIAS variables.
 *
 * @name $languages
 * @global $languages
 */
$languages['bg_BG']['NAME']    = 'Bulgarian';
$languages['bg_BG']['ALTNAME'] = '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;';
$languages['bg_BG']['CHARSET'] = 'windows-1251';
$languages['bg']['ALIAS'] = 'bg_BG';

$languages['ca_ES']['NAME']    = 'Catalan';
$languages['ca_ES']['CHARSET'] = 'iso-8859-1';
$languages['ca']['ALIAS'] = 'ca_ES';

$languages['cs_CZ']['NAME']    = 'Czech';
$languages['cs_CZ']['ALTNAME'] = '&#268;e&scaron;tina';
$languages['cs_CZ']['CHARSET'] = 'iso-8859-2';
$languages['cs']['ALIAS']      = 'cs_CZ';

$languages['cy_GB']['NAME']    = 'Welsh';
$languages['cy_GB']['ALTNAME'] = 'Cymraeg';
$languages['cy_GB']['CHARSET'] = 'iso-8859-1';
$languages['cy']['ALIAS'] = 'cy_GB';

// Danish locale is da_DK.
$languages['da_DK']['NAME']    = 'Danish';
$languages['da_DK']['ALTNAME'] = 'Dansk';
$languages['da_DK']['CHARSET'] = 'iso-8859-1';
$languages['da']['ALIAS'] = 'da_DK';

$languages['de_DE']['NAME']    = 'German';
$languages['de_DE']['ALTNAME']    = 'Deutsch';
$languages['de_DE']['CHARSET'] = 'iso-8859-1';
$languages['de']['ALIAS'] = 'de_DE';

$languages['el_GR']['NAME']    = 'Greek';
$languages['el_GR']['ALTNAME'] = '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;';
$languages['el_GR']['CHARSET'] = 'iso-8859-7';
$languages['el']['ALIAS'] = 'el_GR';

// There is no en_EN! There is en_US, en_BR, en_AU, and so forth, 
// but who cares about !US, right? Right? :)
$languages['en_US']['NAME']    = 'English';
$languages['en_US']['CHARSET'] = 'iso-8859-1';
$languages['en']['ALIAS'] = 'en_US';

$languages['es_ES']['NAME']    = 'Spanish';
$languages['es_ES']['ALTNAME'] = 'Espa&ntilde;ol';
$languages['es_ES']['CHARSET'] = 'iso-8859-1';
$languages['es']['ALIAS'] = 'es_ES';

$languages['et_EE']['NAME']    = 'Estonian';
$languages['et_EE']['CHARSET'] = 'iso-8859-15';
$languages['et']['ALIAS'] = 'et_EE';

$languages['fo_FO']['NAME']    = 'Faroese';
$languages['fo_FO']['CHARSET'] = 'iso-8859-1';
$languages['fo']['ALIAS'] = 'fo_FO';

$languages['fi_FI']['NAME']    = 'Finnish';
$languages['fi_FI']['ALTNAME'] = 'Suomi';
$languages['fi_FI']['CHARSET'] = 'iso-8859-1';
$languages['fi']['ALIAS'] = 'fi_FI';

$languages['fr_FR']['NAME']    = 'French';
$languages['fr_FR']['ALTNAME'] = 'Fran&#231;ais';
$languages['fr_FR']['CHARSET'] = 'iso-8859-1';
$languages['fr']['ALIAS'] = 'fr_FR';

$languages['hr_HR']['NAME']    = 'Croatian';
$languages['hr_HR']['CHARSET'] = 'iso-8859-2';
$languages['hr']['ALIAS'] = 'hr_HR';

$languages['hu_HU']['NAME']    = 'Hungarian';
$languages['hu_HU']['ALTNAME'] = 'Magyar';
$languages['hu_HU']['CHARSET'] = 'iso-8859-2';
$languages['hu']['ALIAS'] = 'hu_HU';

$languages['id_ID']['NAME']    = 'Indonesian';
$languages['id_ID']['ALTNAME'] = 'Bahasa Indonesia';
$languages['id_ID']['CHARSET'] = 'iso-8859-1';
$languages['id']['ALIAS'] = 'id_ID';

$languages['is_IS']['NAME']    = 'Icelandic';
$languages['is_IS']['ALTNAME'] = '&Iacute;slenska';
$languages['is_IS']['CHARSET'] = 'iso-8859-1';
$languages['is']['ALIAS'] = 'is_IS';

$languages['it_IT']['NAME']    = 'Italian';
$languages['it_IT']['CHARSET'] = 'iso-8859-1';
$languages['it']['ALIAS'] = 'it_IT';

$languages['ja_JP']['NAME']    = 'Japanese';
$languages['ja_JP']['ALTNAME'] = '&#26085;&#26412;&#35486;';
$languages['ja_JP']['CHARSET'] = 'iso-2022-jp';
$languages['ja_JP']['LOCALE'] = 'ja_JP.EUC-JP';
$languages['ja_JP']['XTRA_CODE'] = 'japanese_charset_xtra';
$languages['ja']['ALIAS'] = 'ja_JP';

$languages['ko_KR']['NAME']    = 'Korean';
$languages['ko_KR']['CHARSET'] = 'euc-KR';
$languages['ko_KR']['XTRA_CODE'] = 'korean_charset_xtra';
$languages['ko']['ALIAS'] = 'ko_KR';

$languages['lt_LT']['NAME']    = 'Lithuanian';
$languages['lt_LT']['ALTNAME'] = 'Lietuvi&#371;';
$languages['lt_LT']['CHARSET'] = 'utf-8';
$languages['lt_LT']['LOCALE'] = 'lt_LT.UTF-8';
$languages['lt']['ALIAS'] = 'lt_LT';

$languages['nl_NL']['NAME']    = 'Dutch';
$languages['nl_NL']['ALTNAME'] = 'Nederlands';
$languages['nl_NL']['CHARSET'] = 'iso-8859-1';
$languages['nl']['ALIAS'] = 'nl_NL';

$languages['ms_MY']['NAME']    = 'Malay';
$languages['ms_MY']['ALTNAME'] = 'Bahasa Melayu';
$languages['ms_MY']['CHARSET'] = 'iso-8859-1';
$languages['my']['ALIAS'] = 'ms_MY';

$languages['no_NO']['NAME']    = 'Norwegian (Bokm&aring;l)';
$languages['no_NO']['ALTNAME'] = 'Norsk (Bokm&aring;l)';
$languages['no_NO']['CHARSET'] = 'iso-8859-1';
$languages['no']['ALIAS'] = 'no_NO';

$languages['nn_NO']['NAME']    = 'Norwegian (Nynorsk)';
$languages['nn_NO']['ALTNAME'] = 'Norsk (Nynorsk)';
$languages['nn_NO']['CHARSET'] = 'iso-8859-1';

$languages['pl_PL']['NAME']    = 'Polish';
$languages['pl_PL']['ALTNAME'] = 'Polski';
$languages['pl_PL']['CHARSET'] = 'iso-8859-2';
$languages['pl']['ALIAS'] = 'pl_PL';

$languages['pt_PT']['NAME'] = 'Portuguese (Portugal)';
$languages['pt_PT']['CHARSET'] = 'iso-8859-1';
$languages['pt']['ALIAS'] = 'pt_PT';

$languages['pt_BR']['NAME']    = 'Portuguese (Brazil)';
$languages['pt_BR']['ALTNAME'] = 'Portugu&ecirc;s do Brasil';
$languages['pt_BR']['CHARSET'] = 'iso-8859-1';

$languages['ro_RO']['NAME']    = 'Romanian';
$languages['ro_RO']['ALTNAME'] = 'Rom&acirc;n&#259;';
$languages['ro_RO']['CHARSET'] = 'iso-8859-2';
$languages['ro']['ALIAS'] = 'ro_RO';

$languages['ru_RU']['NAME']    = 'Russian';
$languages['ru_RU']['ALTNAME'] = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;';
$languages['ru_RU']['CHARSET'] = 'utf-8';
$languages['ru_RU']['LOCALE'] = 'ru_RU.UTF-8';
$languages['ru']['ALIAS'] = 'ru_RU';

$languages['sk_SK']['NAME']     = 'Slovak';
$languages['sk_SK']['CHARSET']  = 'iso-8859-2';
$languages['sk']['ALIAS']       = 'sk_SK';

$languages['sl_SI']['NAME']    = 'Slovenian';
$languages['sl_SI']['ALTNAME'] = 'Sloven&scaron;&#269;ina';
$languages['sl_SI']['CHARSET'] = 'iso-8859-2';
$languages['sl']['ALIAS'] = 'sl_SI';

$languages['sr_YU']['NAME']    = 'Serbian';
$languages['sr_YU']['ALTNAME'] = 'Srpski';
$languages['sr_YU']['CHARSET'] = 'iso-8859-2';
$languages['sr']['ALIAS'] = 'sr_YU';

$languages['sv_SE']['NAME']    = 'Swedish';
$languages['sv_SE']['ALTNAME'] = 'Svenska';
$languages['sv_SE']['CHARSET'] = 'iso-8859-1';
$languages['sv']['ALIAS'] = 'sv_SE';

$languages['th_TH']['NAME']    = 'Thai';
$languages['th_TH']['CHARSET'] = 'tis-620';
$languages['th']['ALIAS'] = 'th_TH';

$languages['tr_TR']['NAME']    = 'Turkish';
$languages['tr_TR']['CHARSET'] = 'iso-8859-9';
$languages['tr']['ALIAS'] = 'tr_TR';

$languages['zh_TW']['NAME']    = 'Chinese Trad';
$languages['zh_TW']['CHARSET'] = 'big5';
$languages['tw']['ALIAS'] = 'zh_TW';

$languages['zh_CN']['NAME']    = 'Chinese Simp';
$languages['zh_CN']['CHARSET'] = 'gb2312';
$languages['cn']['ALIAS'] = 'zh_CN';

/*
$languages['uk_UA']['NAME']    = 'Ukrainian';
$languages['uk_UA']['CHARSET'] = 'koi8-u';
$languages['uk']['ALIAS'] = 'uk_UA';
*/
/*
if ( file_exists( SM_PATH . 'locale/vi_VN') ) {
    $languages['vi_VN']['NAME']    = 'Vietnamese';
    $languages['vi_VN']['CHARSET'] = 'utf-8';
    $languages['vi']['ALIAS'] = 'vi_VN';
}
*/

// Right to left languages
$languages['ar']['NAME']    = 'Arabic';
$languages['ar']['CHARSET'] = 'windows-1256';
$languages['ar']['DIR']     = 'rtl';

$languages['he_IL']['NAME']    = 'Hebrew';
$languages['he_IL']['CHARSET'] = 'windows-1255';
$languages['he_IL']['DIR']     = 'rtl';
$languages['he']['ALIAS']      = 'he_IL';

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


/**
 * Japanese charset extra function
 *
 * Action performed by function is defined by first argument.
 * Default return value is defined by second argument.
 * Use of third argument depends on action.
 *
 * @param string action performed by this function. 
 *    possible values:
 * 	decode - convert returned string to euc-jp. third argument unused
 *	encode - convert returned string to jis. third argument unused
 *	strimwidth - third argument=$width. trims string to $width symbols.
 *	encodeheader - create base64 encoded header in iso-2022-jp. third argument unused
 *	decodeheader - return human readable string from mime header. string is returned in euc-jp. third argument unused
 *	downloadfilename - third argument $useragent. Arguments provide browser info. Returns shift-jis or euc-jp encoded file name
 *	wordwrap - third argument=$wrap. wraps text at $wrap symbols
 *	utf7-imap_encode - returns string converted from euc-jp to utf7-imap. third argument unused
 *	utf7-imap_decode - returns string converted from utf7-imap to euc-jp. third argument unused
 * @param string default return value
 */
function japanese_charset_xtra() {
    $ret = func_get_arg(1);  /* default return value */
    if (function_exists('mb_detect_encoding')) {
        switch (func_get_arg(0)) { /* action */
        case 'decode':
            $detect_encoding = @mb_detect_encoding($ret);
            if ($detect_encoding == 'JIS' ||
                $detect_encoding == 'EUC-JP' ||
                $detect_encoding == 'SJIS' ||
                $detect_encoding == 'UTF-8') {
                
                $ret = mb_convert_kana(mb_convert_encoding($ret, 'EUC-JP', 'AUTO'), "KV");
            }
            break;
        case 'encode':
            $detect_encoding = @mb_detect_encoding($ret);
            if ($detect_encoding == 'JIS' ||
                $detect_encoding == 'EUC-JP' ||
                $detect_encoding == 'SJIS' ||
                $detect_encoding == 'UTF-8') {
                
                $ret = mb_convert_encoding(mb_convert_kana($ret, "KV"), 'JIS', 'AUTO');
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
                            $result .= str_replace(' ', '', 
                                                   mb_encode_mimeheader($tmpstr,'iso-2022-jp','B',''));
                        }
                        $tmpstr = $tmp;
                        $prevcsize = strlen($tmp);
                    }
                }
                if (strlen($tmpstr)) {
                    if (strlen(mb_substr($tmpstr, 0, 1)) == 1)
                        $result .= $tmpstr;
                    else
                        $result .= str_replace(' ', '',
                                               mb_encode_mimeheader($tmpstr,'iso-2022-jp','B',''));
                }
            }
            $ret = $result;
            break;
        case 'decodeheader':
            $ret = str_replace("\t", "", $ret);
            if (eregi('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=', $ret))
                $ret = @mb_decode_mimeheader($ret);
            $ret = @mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
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
        case 'wordwrap':
            $no_begin = "\x21\x25\x29\x2c\x2e\x3a\x3b\x3f\x5d\x7d\xa1\xf1\xa1\xeb\xa1" .
                "\xc7\xa1\xc9\xa2\xf3\xa1\xec\xa1\xed\xa1\xee\xa1\xa2\xa1\xa3\xa1\xb9" .
                "\xa1\xd3\xa1\xd5\xa1\xd7\xa1\xd9\xa1\xdb\xa1\xcd\xa4\xa1\xa4\xa3\xa4" .
                "\xa5\xa4\xa7\xa4\xa9\xa4\xc3\xa4\xe3\xa4\xe5\xa4\xe7\xa4\xee\xa1\xab" .
                "\xa1\xac\xa1\xb5\xa1\xb6\xa5\xa1\xa5\xa3\xa5\xa5\xa5\xa7\xa5\xa9\xa5" .
                "\xc3\xa5\xe3\xa5\xe5\xa5\xe7\xa5\xee\xa5\xf5\xa5\xf6\xa1\xa6\xa1\xbc" .
                "\xa1\xb3\xa1\xb4\xa1\xaa\xa1\xf3\xa1\xcb\xa1\xa4\xa1\xa5\xa1\xa7\xa1" .
                "\xa8\xa1\xa9\xa1\xcf\xa1\xd1";
            $no_end = "\x5c\x24\x28\x5b\x7b\xa1\xf2\x5c\xa1\xc6\xa1\xc8\xa1\xd2\xa1" .
                "\xd4\xa1\xd6\xa1\xd8\xa1\xda\xa1\xcc\xa1\xf0\xa1\xca\xa1\xce\xa1\xd0\xa1\xef";
            $wrap = func_get_arg(2);
            
            if (strlen($ret) >= $wrap && 
                substr($ret, 0, 1) != '>' &&
                strpos($ret, 'http://') === FALSE &&
                strpos($ret, 'https://') === FALSE &&
                strpos($ret, 'ftp://') === FALSE) {
                
                $ret = mb_convert_kana($ret, "KV");

                $line_new = '';
                $ptr = 0;
                
                while ($ptr < strlen($ret) - 1) {
                    $l = mb_strcut($ret, $ptr, $wrap);
                    $ptr += strlen($l);
                    $tmp = $l;
                    
                    $l = mb_strcut($ret, $ptr, 2);
                    while (strlen($l) != 0 && mb_strpos($no_begin, $l) !== FALSE ) {
                        $tmp .= $l;
                        $ptr += strlen($l);
                        $l = mb_strcut($ret, $ptr, 1);
                    }
                    $line_new .= $tmp;
                    if ($ptr < strlen($ret) - 1)
                        $line_new .= "\n";
                }
                $ret = $line_new;
            }
            break;
        case 'utf7-imap_encode':
            $ret = mb_convert_encoding($ret, 'UTF7-IMAP', 'EUC-JP');
            break;
        case 'utf7-imap_decode':
            $ret = mb_convert_encoding($ret, 'EUC-JP', 'UTF7-IMAP');
            break;
        }
    }
    return $ret;
}


/**
 * Korean charset extra functions
 *
 * Action performed by function is defined by first argument.
 * Default return value is defined by second argument.
 *
 * @param string action performed by this function. 
 *    possible values:
 *	downloadfilename - Hangul(Korean Character) Attached File Name Fix.
 * @param string default return value
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

/**
 * Replaces non-braking spaces inserted by some browsers with regular space
 * 
 * This function can be used to replace non-braking space symbols 
 * that are inserted in forms by some browsers instead of normal 
 * space symbol.
 *
 * @param string $string Text that needs to be cleaned
 * @param string $charset Charset used in text
 * @return string Cleaned text
 */
function cleanup_nbsp($string,$charset) {

  // reduce number of case statements
  if (stristr('iso-8859-',substr($charset,0,9))){
    $output_charset="iso-8859-x";
  }
  if (stristr('windows-125',substr($charset,0,11))){
    $output_charset="cp125x";
  }
  if (stristr('koi8',substr($charset,0,4))){
    $output_charset="koi8-x";
  }
  if (! isset($output_charset)){
    $output_charset=strtolower($charset);
  }

// where is non-braking space symbol
switch($output_charset):
 case "iso-8859-x":
 case "cp125x":
 case "iso-2022-jp":
  $nbsp="\xA0";
  break;
 case "koi8-x":
   $nbsp="\x9A";
   break;
 case "utf-8":
   $nbsp="\xC2\xA0";
   break;
 default:
   // don't change string if charset is unmatched
   return $string;
endswitch;

// return space instead of non-braking space. 
 return str_replace($nbsp,' ',$string);
}

/**
 * Function informs if it is safe to convert given charset to the one that is used by user.
 *
 * It is safe to use conversion only if user uses utf-8 encoding and when 
 * converted charset is similar to the one that is used by user.
 *
 * @param string $input_charset Charset of text that needs to be converted
 * @return bool is it possible to convert to user's charset
 */
function is_conversion_safe($input_charset) {
  global $languages, $sm_notAlias, $default_charset;

 // convert to lower case
 $input_charset = strtolower($input_charset);

 // Is user's locale Unicode based ?
 if ( $default_charset == "utf-8" ) {
   return true;
 }

 // Charsets that are similar
switch ($default_charset):
case "windows-1251":
      if ( $input_charset == "iso-8859-5" || 
	   $input_charset == "koi8-r" ||
	   $input_charset == "koi8-u" ) {
        return true;
     } else {
        return false;
     }
case "windows-1257":
  if ( $input_charset == "iso-8859-13" || 
	 $input_charset == "iso-8859-4" ) {
    return true;
  } else {
    return false;
  }
case "iso-8859-4":
  if ( $input_charset == "iso-8859-13" || 
	 $input_charset == "windows-1257" ) {
     return true;
  } else {
     return false;
  }
case "iso-8859-5":
  if ( $input_charset == "windows-1251" || 
	 $input_charset == "koi8-r" || 
	 $input_charset == "koi8-u" ) {
     return true;
  } else {
     return false;
  }
case "iso-8859-13":
  if ( $input_charset == "iso-8859-4" ||
       $input_charset == "windows-1257" ) {
     return true;
  } else {
     return false;
  }
case "koi8-r":
  if ( $input_charset == "windows-1251" ||
	 $input_charset == "iso-8859-5" || 
	 $input_charset == "koi8-u" ) {
     return true;
  } else {
     return false;
  }
case "koi8-u":
  if ( $input_charset == "windows-1251" ||
	$input_charset == "iso-8859-5" ||
	$input_charset == "koi8-r" ) {
     return true;
  } else {
     return false;
  }
default:
   return false;
endswitch;
}
?>