<?php
/**
 * SquirrelMail internationalization functions
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains variuos functions that are needed to do
 * internationalization of SquirrelMail.
 *
 * Internally the output character set is used. Other characters are
 * encoded using Unicode entities according to HTML 4.0.
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 */

/** Everything uses global.php... */
require_once(SM_PATH . 'functions/global.php');

/**
 * Gettext bindtextdomain wrapper.
 *
 * Wrapper solves differences between php versions in order to provide
 * ngettext support. Should be used if translation uses ngettext
 * functions.
 * @since 1.5.1
 * @param string $domain gettext domain name
 * @param string $dir directory that contains all translations
 * @return string path to translation directory
 */
function sq_bindtextdomain($domain,$dir) {
    global $l10n, $gettext_flags, $sm_notAlias;

    if ($gettext_flags==7) {
        // gettext extension without ngettext
        if (substr($dir, -1) != '/') $dir .= '/';
        $mofile=$dir . $sm_notAlias . '/LC_MESSAGES/' . $domain . '.mo';
        $input = new FileReader($mofile);
        $l10n[$domain] = new gettext_reader($input);
    }

    $dir=bindtextdomain($domain,$dir);

    return $dir;
}

/**
 * Gettext textdomain wrapper.
 * Makes sure that gettext_domain global is modified.
 * @since 1.5.1
 * @param string $name gettext domain name
 * @return string gettext domain name
 */
function sq_textdomain($domain) {
    global $gettext_domain;
    $gettext_domain=textdomain($domain);
    return $gettext_domain;
}

/**
 * php setlocale function wrapper
 *
 * From php 4.3.0 it is possible to use arrays in order to set locale.
 * php gettext extension works only when locale is set. This wrapper
 * function allows to use more than one locale name.
 *
 * @param int $category locale category name. Use php named constants
 *     (LC_ALL, LC_COLLATE, LC_CTYPE, LC_MONETARY, LC_NUMERIC, LC_TIME)
 * @param mixed $locale option contains array with possible locales or string with one locale
 * @return string name of set locale or false, if all locales fail.
 * @since 1.5.1 and 1.4.5
 * @see http://www.php.net/setlocale
 */
function sq_setlocale($category,$locale) {
    // string with only one locale
    if (is_string($locale))
        return setlocale($category,$locale);

    if (! check_php_version(4,3)) {
        $ret=false;
        $index=0;
        while ( ! $ret && $index<count($locale)) {
            $ret=setlocale($category,$locale[$index]);
            $index++;
        }
    } else {
        // php 4.3.0 or better, use entire array
        $ret=setlocale($category,$locale);
    }
    return $ret;
}

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
 * @param boolean $force_decode converts string to html without $charset!=$default_charset check.
 * Argument is available since 1.5.1 and 1.4.5.
 * @return string decoded string
 */
function charset_decode ($charset, $string, $force_decode=false) {
    global $languages, $squirrelmail_language, $default_charset;
    global $use_php_recode, $use_php_iconv, $aggressive_decoding;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode')) {
        $string = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode', $string);
    }

    $charset = strtolower($charset);

    set_my_charset();

    // Variables that allow to use functions without function_exist() calls
    if (! isset($use_php_recode) || $use_php_recode=="" ) {
        $use_php_recode=false; }
    if (! isset($use_php_iconv) || $use_php_iconv=="" ) {
        $use_php_iconv=false; }

    // Don't do conversion if charset is the same.
    if ( ! $force_decode && $charset == strtolower($default_charset) )
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
    if (! isset($aggressive_decoding) || $aggressive_decoding=="" ) {
        $aggressive_decoding=false; }

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
 * Converts html string to given charset
 * @since 1.5.1 and 1.4.4
 * @param string $string
 * @param string $charset
 * @param boolean $htmlencode keep htmlspecialchars encoding
 * @param string
 */
function charset_encode($string,$charset,$htmlencode=true) {
    global $default_charset;

    $encode=fixcharset($charset);
    $encodefile=SM_PATH . 'functions/encode/' . $encode . '.php';
    if (file_exists($encodefile)) {
        include_once($encodefile);
        $ret = call_user_func('charset_encode_'.$encode, $string);
    } else {
        include_once(SM_PATH . 'functions/encode/us_ascii.php');
        $ret = charset_encode_us_ascii($string);
    }

    /**
     * Undo html special chars, some places (like compose form) have
     * own sanitizing functions and don't need html symbols.
     * Undo chars only after encoding in order to prevent conversion of
     * html entities in plain text emails.
     */
    if (! $htmlencode ) {
        $ret = str_replace(array('&amp;','&gt;','&lt;','&quot;'),array('&','>','<','"'),$ret);
    }
    return( $ret );
}

/**
 * Combined decoding and encoding functions
 *
 * If conversion is done to charset different that utf-8, unsupported symbols
 * will be replaced with question marks.
 * @since 1.5.1 and 1.4.4
 * @param string $in_charset initial charset
 * @param string $string string that has to be converted
 * @param string $out_charset final charset
 * @param boolean $htmlencode keep htmlspecialchars encoding
 * @return string converted string
 */
function charset_convert($in_charset,$string,$out_charset,$htmlencode=true) {
    $string=charset_decode($in_charset,$string,true);
    $string=charset_encode($string,$out_charset,$htmlencode);
    return $string;
}

/**
 * Makes charset name suitable for decoding cycles
 *
 * @since 1.5.0 and 1.4.4
 * @param string $charset Name of charset
 * @return string $charset Adjusted name of charset
 */
function fixcharset($charset) {
    /* remove minus and characters that might be used in paths from charset
     * name in order to be able to use it in function names and include calls.
     */
    $charset=preg_replace("/[-:.\/\\\]/",'_',$charset);

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
           $squirrelmail_language, $squirrelmail_default_language, $default_charset,
           $sm_notAlias, $username, $data_dir;

    if ($SetupAlready) {
        return;
    }

    $SetupAlready = TRUE;
    sqgetGlobalVar('HTTP_ACCEPT_LANGUAGE',  $accept_lang, SQ_SERVER);

    /**
     * If function is asked to detect preferred language
     *  OR squirrelmail default language is set to empty string
     *    AND
     * squirrelmail language ($sm_language) is empty string
     * (not set in user's prefs and no cookie with language info)
     *    AND
     * browser provides list of preferred languages
     *  THEN
     * get preferred language from HTTP_ACCEPT_LANGUAGE header
     */
    if (($do_search || empty($squirrelmail_default_language)) &&
        ! $sm_language &&
        isset($accept_lang)) {
        // TODO: use more than one language, if first language is not available
        // FIXME: function assumes that string contains two or more characters.
        // FIXME: some languages use 5 chars
        $sm_language = substr($accept_lang, 0, 2);
    }

    /**
     * If language preference is not set OR script asks to use default language
     *  AND
     * default squirrelmail language is not set to empty string
     *  THEN
     * use default squirrelmail language value from configuration.
     */
    if ((!$sm_language||$default) &&
        ! empty($squirrelmail_default_language)) {
        $squirrelmail_language = $squirrelmail_default_language;
        $sm_language = $squirrelmail_default_language;
    }

    /** provide failsafe language when detection fails */
    if (! $sm_language) $sm_language='en_US';

    $sm_notAlias = $sm_language;

    // Catching removed translation
    // System reverts to English translation if user prefs contain translation
    // that is not available in $languages array
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
        sq_bindtextdomain( 'squirrelmail', SM_PATH . 'locale/' );
        sq_textdomain( 'squirrelmail' );

        // set codeset in order to avoid gettext charset conversions
        if (function_exists('bind_textdomain_codeset')) {
            // Japanese translation uses different internal charset
            if ($sm_notAlias == 'ja_JP') {
                bind_textdomain_codeset ('squirrelmail', 'EUC-JP');
            } else {
                bind_textdomain_codeset ('squirrelmail', $languages[$sm_notAlias]['CHARSET'] );
            }
        }

        // Use LOCALE key, if it is set.
        if (isset($languages[$sm_notAlias]['LOCALE'])){
            $longlocale=$languages[$sm_notAlias]['LOCALE'];
        } else {
            $longlocale=$sm_notAlias;
        }

        // try setting locale
        $retlocale=sq_setlocale(LC_ALL, $longlocale);

        // check if locale is set and assign that locale to $longlocale
        // in order to use it in putenv calls.
        if (! is_bool($retlocale)) {
            $longlocale=$retlocale;
        } elseif (is_array($longlocale)) {
            // setting of all locales failed.
            // we need string instead of array used in LOCALE key.
            $longlocale=$sm_notAlias;
        }

        if ( !((bool)ini_get('safe_mode')) &&
             getenv( 'LC_ALL' ) != $longlocale ) {
            putenv( "LC_ALL=$longlocale" );
            putenv( "LANG=$longlocale" );
            putenv( "LANGUAGE=$longlocale" );
            putenv( "LC_NUMERIC=C" );
            if ($sm_notAlias=='tr_TR') putenv( "LC_CTYPE=C" );
        }
        // Workaround for plugins that use numbers with floating point
        // It might be removed if plugins use correct decimal delimiters
        // according to locale settings.
        setlocale(LC_NUMERIC, 'C');
        // Workaround for specific Turkish strtolower/strtoupper rules.
        // Many functions expect English conversion rules.
        if ($sm_notAlias=='tr_TR') setlocale(LC_CTYPE,'C');

        // Set text direction/alignment variables
        // These don't appear to be used... are they safe to remove?
        if (isset($languages[$sm_notAlias]['DIR']) &&
            $languages[$sm_notAlias]['DIR'] == 'rtl') {
            /**
             * Text direction
             * @global string $text_direction
             */
            $text_direction='rtl';
            /**
             * Left alignment
             * @global string $left_align
             */
            $left_align='right';
            /**
             * Right alignment
             * @global string $right_align
             */
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
        } elseif ($squirrelmail_language == 'en_US') {
            header( 'Content-Type: text/html; charset=' . $default_charset );
        } else {
            header( 'Content-Type: text/html; charset=' . $languages[$sm_notAlias]['CHARSET'] );
        }
        /**
         * mbstring.func_overload fix (#929644).
         *
         * php mbstring extension can replace standard string functions with their multibyte
         * equivalents. See http://www.php.net/ref.mbstring#mbstring.overload. This feature 
         * was added in php v.4.2.0
         *
         * Some SquirrelMail functions work with 8bit strings in bytes. If interface is forced
         * to use mbstring functions and mbstring internal encoding is set to multibyte charset,
         * interface can't trust regular string functions. Due to mbstring overloading design
         * limits php scripts can't control this setting.
         *
         * This hack should fix some issues related to 8bit strings in passwords. Correct fix is
         * to disable mbstring overloading. Japanese translation uses different internal encoding.
         */
        if ($squirrelmail_language != 'ja_JP' &&
            function_exists('mb_internal_encoding') &&
            check_php_version(4,2,0) &&
            (int)ini_get('mbstring.func_overload')!=0) {
            mb_internal_encoding('pass');
        }
    }
    return 0;
}

/**
 * Sets default_charset variable according to the one that is used by user's translations.
 *
 * Function changes global $default_charset variable in order to be sure, that it
 * contains charset used by user's translation. Sanity of $squirrelmail_language
 * and $default_charset combination is also tested.
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
    global $data_dir, $username, $default_charset, $languages, $squirrelmail_language;

    $my_language = getPref($data_dir, $username, 'language');
    if (!$my_language) {
        $my_language = $squirrelmail_language ;
    }
    // Catch removed translation
    if (!isset($languages[$my_language])) {
        $my_language="en_US";
    }
    while (isset($languages[$my_language]['ALIAS'])) {
        $my_language = $languages[$my_language]['ALIAS'];
    }
    $my_charset = $languages[$my_language]['CHARSET'];
    if ($my_language!='en_US') {
        $default_charset = $my_charset;
    }
}

/**************************
 * Japanese extra functions
 **************************/

/**
 * Japanese decoding function
 *
 * converts string to euc-jp, if string uses JIS, EUC-JP, ShiftJIS or UTF-8
 * charset. Needs mbstring support in php.
 * @param string $ret text, that has to be converted
 * @return string converted string
 * @since 1.5.1
 */
function japanese_xtra_decode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $detect_encoding = @mb_detect_encoding($ret);
        if ($detect_encoding == 'JIS' ||
            $detect_encoding == 'EUC-JP' ||
            $detect_encoding == 'SJIS' ||
            $detect_encoding == 'UTF-8') {

            $ret = mb_convert_kana(mb_convert_encoding($ret, 'EUC-JP', 'AUTO'), "KV");
        }
    }
    return $ret;
}

/**
 * Japanese encoding function
 *
 * converts string to jis, if string uses JIS, EUC-JP, ShiftJIS or UTF-8
 * charset. Needs mbstring support in php.
 * @param string $ret text, that has to be converted
 * @return string converted text
 * @since 1.5.1
 */
function japanese_xtra_encode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $detect_encoding = @mb_detect_encoding($ret);
        if ($detect_encoding == 'JIS' ||
            $detect_encoding == 'EUC-JP' ||
            $detect_encoding == 'SJIS' ||
            $detect_encoding == 'UTF-8') {

            $ret = mb_convert_encoding(mb_convert_kana($ret, "KV"), 'JIS', 'AUTO');
        }
    }
    return $ret;
}

/**
 * Japanese header encoding function
 *
 * creates base64 encoded header in iso-2022-jp charset
 * @param string $ret text, that has to be converted
 * @return string mime base64 encoded string
 * @since 1.5.1
 */
function japanese_xtra_encodeheader($ret) {
    if (function_exists('mb_detect_encoding')) {
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
    }
    return $ret;
}

/**
 * Japanese header decoding function
 *
 * return human readable string from mime header. string is returned in euc-jp
 * charset.
 * @param string $ret header string
 * @return string decoded header string
 * @since 1.5.1
 */
function japanese_xtra_decodeheader($ret) {
    if (function_exists('mb_detect_encoding')) {
        $ret = str_replace("\t", "", $ret);
        if (eregi('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=', $ret))
            $ret = @mb_decode_mimeheader($ret);
        $ret = @mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
    }
    return $ret;
}

/**
 * Japanese downloaded filename processing function
 *
 * Returns shift-jis or euc-jp encoded file name
 * @param string $ret string
 * @param string $useragent browser
 * @return string converted string
 * @since 1.5.1
 */
function japanese_xtra_downloadfilename($ret,$useragent) {
    if (function_exists('mb_detect_encoding')) {
        if (strstr($useragent, 'Windows') !== false ||
            strstr($useragent, 'Mac_') !== false) {
            $ret = mb_convert_encoding($ret, 'SJIS', 'AUTO');
        } else {
            $ret = mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
        }
    }
    return $ret;
}

/**
 * Japanese wordwrap function
 *
 * wraps text at set number of symbols
 * @param string $ret text
 * @param integer $wrap number of symbols per line
 * @return string wrapped text
 * @since 1.5.1
 */
function japanese_xtra_wordwrap($ret,$wrap) {
    if (function_exists('mb_detect_encoding')) {
        $no_begin = "\x21\x25\x29\x2c\x2e\x3a\x3b\x3f\x5d\x7d\xa1\xf1\xa1\xeb\xa1" .
            "\xc7\xa1\xc9\xa2\xf3\xa1\xec\xa1\xed\xa1\xee\xa1\xa2\xa1\xa3\xa1\xb9" .
            "\xa1\xd3\xa1\xd5\xa1\xd7\xa1\xd9\xa1\xdb\xa1\xcd\xa4\xa1\xa4\xa3\xa4" .
            "\xa5\xa4\xa7\xa4\xa9\xa4\xc3\xa4\xe3\xa4\xe5\xa4\xe7\xa4\xee\xa1\xab" .
            "\xa1\xac\xa1\xb5\xa1\xb6\xa5\xa1\xa5\xa3\xa5\xa5\xa5\xa7\xa5\xa9\xa5" .
            "\xc3\xa5\xe3\xa5\xe5\xa5\xe7\xa5\xee\xa5\xf5\xa5\xf6\xa1\xa6\xa1\xbc" .
            "\xa1\xb3\xa1\xb4\xa1\xaa\xa1\xf3\xa1\xcb\xa1\xa4\xa1\xa5\xa1\xa7\xa1" .
            "\xa8\xa1\xa9\xa1\xcf\xa1\xd1";
        // This don't appear to be used... is it safe to remove?
        $no_end = "\x5c\x24\x28\x5b\x7b\xa1\xf2\x5c\xa1\xc6\xa1\xc8\xa1\xd2\xa1" .
            "\xd4\xa1\xd6\xa1\xd8\xa1\xda\xa1\xcc\xa1\xf0\xa1\xca\xa1\xce\xa1\xd0\xa1\xef";

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
    }
    return $ret;
}

/**
 * Japanese imap folder name encoding function
 *
 * converts folder name from euc-jp to utf7-imap
 * @param string $ret folder name
 * @return string converted folder name
 * @since 1.5.1
 */
function japanese_xtra_utf7_imap_encode($ret){
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_convert_encoding($ret, 'UTF7-IMAP', 'EUC-JP');
    }
    return $ret;
}

/**
 * Japanese imap folder name decoding function
 *
 * converts folder name from utf7-imap to euc-jp.
 * @param string $ret folder name in utf7-imap
 * @return string converted folder name
 * @since 1.5.1
 */
function japanese_xtra_utf7_imap_decode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_convert_encoding($ret, 'EUC-JP', 'UTF7-IMAP');
    }
    return $ret;
}

/**
 * Japanese string trimming function
 *
 * trims string to defined number of symbols
 * @param string $ret string
 * @param integer $width number of symbols
 * @return string trimmed string
 * @since 1.5.1
 */
function japanese_xtra_strimwidth($ret,$width) {
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_strimwidth($ret, 0, $width, '...');
    }
    return $ret;
}

/********************************
 * Korean charset extra functions
 ********************************/

/**
 * Korean downloaded filename processing functions
 *
 * @param string default return value
 * @return string
 * @since 1.5.1
 */
function korean_xtra_downloadfilename($ret) {
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
  global $languages, $sm_notAlias, $default_charset, $lossy_encoding;

    if (isset($lossy_encoding) && $lossy_encoding )
        return true;

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


/* ------------------------------ main --------------------------- */

global $squirrelmail_language, $languages, $use_gettext;

if (! sqgetGlobalVar('squirrelmail_language',$squirrelmail_language,SQ_COOKIE)) {
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
 *  LOCALE    - Full locale name (in xx_XX.charset format). It can use array with several locale names since 1.4.5 and 1.5.1
 *  DIR       - Text direction. Used to define Right-to-Left languages. Possible values 'rtl' or 'ltr'. If undefined - defaults to 'ltr'
 *  XTRA_CODE - translation uses special functions. See doc/i18n.txt
 *
 * Each 'language' definition requires NAME+CHARSET or ALIAS variables.
 *
 * @todo TODO: make language loading modular (similar to plugins, with locale/xx_XX/setup.php files)
 * @name $languages
 * @global array $languages
 */
$languages['bg_BG']['NAME']    = 'Bulgarian';
$languages['bg_BG']['ALTNAME'] = '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;';
$languages['bg_BG']['CHARSET'] = 'windows-1251';
$languages['bg_BG']['LOCALE']  = 'bg_BG.CP1251';
$languages['bg']['ALIAS'] = 'bg_BG';

$languages['bn_IN']['NAME']    = 'Bengali';
$languages['bn_IN']['CHARSET'] = 'utf-8';
$languages['bn_IN']['LOCALE']  = 'bn_IN.UTF-8';
$languages['bn_BD']['ALIAS'] = 'bn_IN';
$languages['bn']['ALIAS'] = 'bn_IN';

$languages['ca_ES']['NAME']    = 'Catalan';
$languages['ca_ES']['CHARSET'] = 'iso-8859-1';
$languages['ca_ES']['LOCALE']  = array('ca_ES.ISO8859-1','ca_ES.ISO-8859-1','ca_ES');
$languages['ca']['ALIAS'] = 'ca_ES';

$languages['cs_CZ']['NAME']    = 'Czech';
$languages['cs_CZ']['ALTNAME'] = '&#268;e&scaron;tina';
$languages['cs_CZ']['CHARSET'] = 'iso-8859-2';
$languages['cs_CZ']['LOCALE']  = array('cs_CZ.ISO8859-2','cs_CZ.ISO-8859-2','cs_CZ');
$languages['cs']['ALIAS']      = 'cs_CZ';

$languages['cy_GB']['NAME']    = 'Welsh';
$languages['cy_GB']['ALTNAME'] = 'Cymraeg';
$languages['cy_GB']['CHARSET'] = 'iso-8859-1';
$languages['cy_GB']['LOCALE']  = array('cy_GB.ISO8859-1','cy_GB.ISO-8859-1','cy_GB');
$languages['cy']['ALIAS'] = 'cy_GB';

// Danish locale is da_DK.
$languages['da_DK']['NAME']    = 'Danish';
$languages['da_DK']['ALTNAME'] = 'Dansk';
$languages['da_DK']['CHARSET'] = 'iso-8859-1';
$languages['da_DK']['LOCALE']  = array('da_DK.ISO8859-1','da_DK.ISO-8859-1','da_DK');
$languages['da']['ALIAS'] = 'da_DK';

$languages['de_DE']['NAME']    = 'German';
$languages['de_DE']['ALTNAME'] = 'Deutsch';
$languages['de_DE']['CHARSET'] = 'iso-8859-1';
$languages['de_DE']['LOCALE']  = array('de_DE.ISO8859-1','de_DE.ISO-8859-1','de_DE');
$languages['de']['ALIAS'] = 'de_DE';

$languages['el_GR']['NAME']    = 'Greek';
$languages['el_GR']['ALTNAME'] = '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;';
$languages['el_GR']['CHARSET'] = 'iso-8859-7';
$languages['el_GR']['LOCALE']  = array('el_GR.ISO8859-7','el_GR.ISO-8859-7','el_GR');
$languages['el']['ALIAS'] = 'el_GR';

$languages['en_GB']['NAME']    = 'British';
$languages['en_GB']['CHARSET'] = 'iso-8859-15';
$languages['en_GB']['LOCALE']  = array('en_GB.ISO8859-15','en_GB.ISO-8859-15','en_GB');

$languages['en_US']['NAME']    = 'English';
$languages['en_US']['CHARSET'] = 'iso-8859-1';
$languages['en_US']['LOCALE']  = 'en_US.ISO8859-1';
$languages['en']['ALIAS'] = 'en_US';

$languages['es_ES']['NAME']    = 'Spanish';
$languages['es_ES']['ALTNAME'] = 'Espa&ntilde;ol';
$languages['es_ES']['CHARSET'] = 'iso-8859-1';
$languages['es_ES']['LOCALE']  = array('es_ES.ISO8859-1','es_ES.ISO-8859-1','es_ES');
$languages['es']['ALIAS'] = 'es_ES';

$languages['et_EE']['NAME']    = 'Estonian';
$languages['et_EE']['CHARSET'] = 'iso-8859-15';
$languages['et_EE']['LOCALE']  = array('et_EE.ISO8859-15','et_EE.ISO-8859-15','et_EE');
$languages['et']['ALIAS'] = 'et_EE';

$languages['eu_ES']['NAME']    = 'Basque';
$languages['eu_ES']['CHARSET'] = 'iso-8859-1';
$languages['eu_ES']['LOCALE']  = array('eu_ES.ISO8859-1','eu_ES.ISO-8859-1','eu_ES');
$languages['eu']['ALIAS'] = 'eu_ES';

$languages['fo_FO']['NAME']    = 'Faroese';
$languages['fo_FO']['CHARSET'] = 'iso-8859-1';
$languages['fo_FO']['LOCALE']  = array('fo_FO.ISO8859-1','fo_FO.ISO-8859-1','fo_FO');
$languages['fo']['ALIAS'] = 'fo_FO';

$languages['fi_FI']['NAME']    = 'Finnish';
$languages['fi_FI']['ALTNAME'] = 'Suomi';
$languages['fi_FI']['CHARSET'] = 'iso-8859-1';
$languages['fi_FI']['LOCALE']  = array('fi_FI.ISO8859-1','fi_FI.ISO-8859-1','fi_FI');
$languages['fi']['ALIAS'] = 'fi_FI';

$languages['fr_FR']['NAME']    = 'French';
$languages['fr_FR']['ALTNAME'] = 'Fran&#231;ais';
$languages['fr_FR']['CHARSET'] = 'iso-8859-1';
$languages['fr_FR']['LOCALE']  = array('fr_FR.ISO8859-1','fr_FR.ISO-8859-1','fr_FR');
$languages['fr']['ALIAS'] = 'fr_FR';

$languages['hr_HR']['NAME']    = 'Croatian';
$languages['hr_HR']['CHARSET'] = 'iso-8859-2';
$languages['hr_HR']['LOCALE']  = array('hr_HR.ISO8859-2','hr_HR.ISO-8859-2','hr_HR');
$languages['hr']['ALIAS'] = 'hr_HR';

$languages['hu_HU']['NAME']    = 'Hungarian';
$languages['hu_HU']['ALTNAME'] = 'Magyar';
$languages['hu_HU']['CHARSET'] = 'iso-8859-2';
$languages['hu_HU']['LOCALE']  = array('hu_HU.ISO8859-2','hu_HU.ISO-8859-2','hu_HU');
$languages['hu']['ALIAS'] = 'hu_HU';

$languages['id_ID']['NAME']    = 'Indonesian';
$languages['id_ID']['ALTNAME'] = 'Bahasa Indonesia';
$languages['id_ID']['CHARSET'] = 'iso-8859-1';
$languages['id_ID']['LOCALE']  = array('id_ID.ISO8859-1','id_ID.ISO-8859-1','id_ID');
$languages['id']['ALIAS'] = 'id_ID';

$languages['is_IS']['NAME']    = 'Icelandic';
$languages['is_IS']['ALTNAME'] = '&Iacute;slenska';
$languages['is_IS']['CHARSET'] = 'iso-8859-1';
$languages['is_IS']['LOCALE']  = array('is_IS.ISO8859-1','is_IS.ISO-8859-1','is_IS');
$languages['is']['ALIAS'] = 'is_IS';

$languages['it_IT']['NAME']    = 'Italian';
$languages['it_IT']['CHARSET'] = 'iso-8859-1';
$languages['it_IT']['LOCALE']  = array('it_IT.ISO8859-1','it_IT.ISO-8859-1','it_IT');
$languages['it']['ALIAS'] = 'it_IT';

$languages['ja_JP']['NAME']    = 'Japanese';
$languages['ja_JP']['ALTNAME'] = '&#26085;&#26412;&#35486;';
$languages['ja_JP']['CHARSET'] = 'iso-2022-jp';
$languages['ja_JP']['LOCALE'] = 'ja_JP.EUC-JP';
$languages['ja_JP']['XTRA_CODE'] = 'japanese_xtra';
$languages['ja']['ALIAS'] = 'ja_JP';

$languages['ko_KR']['NAME']    = 'Korean';
$languages['ko_KR']['CHARSET'] = 'euc-KR';
$languages['ko_KR']['LOCALE']  = 'ko_KR.EUC-KR';
$languages['ko_KR']['XTRA_CODE'] = 'korean_xtra';
$languages['ko']['ALIAS'] = 'ko_KR';

$languages['lt_LT']['NAME']    = 'Lithuanian';
$languages['lt_LT']['ALTNAME'] = 'Lietuvi&#371;';
$languages['lt_LT']['CHARSET'] = 'utf-8';
$languages['lt_LT']['LOCALE'] = 'lt_LT.UTF-8';
$languages['lt']['ALIAS'] = 'lt_LT';

$languages['nl_NL']['NAME']    = 'Dutch';
$languages['nl_NL']['ALTNAME'] = 'Nederlands';
$languages['nl_NL']['CHARSET'] = 'iso-8859-1';
$languages['nl_NL']['LOCALE']  = array('nl_NL.ISO8859-1','nl_NL.ISO-8859-1','nl_NL');
$languages['nl']['ALIAS'] = 'nl_NL';

$languages['ms_MY']['NAME']    = 'Malay';
$languages['ms_MY']['ALTNAME'] = 'Bahasa Melayu';
$languages['ms_MY']['CHARSET'] = 'iso-8859-1';
$languages['ms_MY']['LOCALE']  = array('ms_MY.ISO8859-1','ms_MY.ISO-8859-1','ms_MY');
$languages['my']['ALIAS'] = 'ms_MY';

$languages['nb_NO']['NAME']    = 'Norwegian (Bokm&aring;l)';
$languages['nb_NO']['ALTNAME'] = 'Norsk (Bokm&aring;l)';
$languages['nb_NO']['CHARSET'] = 'iso-8859-1';
$languages['nb_NO']['LOCALE']  = array('nb_NO.ISO8859-1','nb_NO.ISO-8859-1','nb_NO');
$languages['nb']['ALIAS'] = 'nb_NO';

$languages['nn_NO']['NAME']    = 'Norwegian (Nynorsk)';
$languages['nn_NO']['ALTNAME'] = 'Norsk (Nynorsk)';
$languages['nn_NO']['CHARSET'] = 'iso-8859-1';
$languages['nn_NO']['LOCALE']  = array('nn_NO.ISO8859-1','nn_NO.ISO-8859-1','nn_NO');

$languages['pl_PL']['NAME']    = 'Polish';
$languages['pl_PL']['ALTNAME'] = 'Polski';
$languages['pl_PL']['CHARSET'] = 'iso-8859-2';
$languages['pl_PL']['LOCALE']  = array('pl_PL.ISO8859-2','pl_PL.ISO-8859-2','pl_PL');
$languages['pl']['ALIAS'] = 'pl_PL';

$languages['pt_PT']['NAME'] = 'Portuguese (Portugal)';
$languages['pt_PT']['CHARSET'] = 'iso-8859-1';
$languages['pt_PT']['LOCALE']  = array('pt_PT.ISO8859-1','pt_PT.ISO-8859-1','pt_PT');
$languages['pt']['ALIAS'] = 'pt_PT';

$languages['pt_BR']['NAME']    = 'Portuguese (Brazil)';
$languages['pt_BR']['ALTNAME'] = 'Portugu&ecirc;s do Brasil';
$languages['pt_BR']['CHARSET'] = 'iso-8859-1';
$languages['pt_BR']['LOCALE']  = array('pt_BR.ISO8859-1','pt_BR.ISO-8859-1','pt_BR');

$languages['ro_RO']['NAME']    = 'Romanian';
$languages['ro_RO']['ALTNAME'] = 'Rom&acirc;n&#259;';
$languages['ro_RO']['CHARSET'] = 'iso-8859-2';
$languages['ro_RO']['LOCALE']  = array('ro_RO.ISO8859-2','ro_RO.ISO-8859-2','ro_RO');
$languages['ro']['ALIAS'] = 'ro_RO';

$languages['ru_RU']['NAME']    = 'Russian';
$languages['ru_RU']['ALTNAME'] = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;';
$languages['ru_RU']['CHARSET'] = 'utf-8';
$languages['ru_RU']['LOCALE']  = 'ru_RU.UTF-8';
$languages['ru']['ALIAS'] = 'ru_RU';

$languages['sk_SK']['NAME']    = 'Slovak';
$languages['sk_SK']['CHARSET'] = 'iso-8859-2';
$languages['sk_SK']['LOCALE']  = array('sk_SK.ISO8859-2','sk_SK.ISO-8859-2','sk_SK');
$languages['sk']['ALIAS'] = 'sk_SK';

$languages['sl_SI']['NAME']    = 'Slovenian';
$languages['sl_SI']['ALTNAME'] = 'Sloven&scaron;&#269;ina';
$languages['sl_SI']['CHARSET'] = 'iso-8859-2';
$languages['sl_SI']['LOCALE']  = array('sl_SI.ISO8859-2','sl_SI.ISO-8859-2','sl_SI');
$languages['sl']['ALIAS'] = 'sl_SI';

$languages['sr_YU']['NAME']    = 'Serbian';
$languages['sr_YU']['ALTNAME'] = 'Srpski';
$languages['sr_YU']['CHARSET'] = 'iso-8859-2';
$languages['sr_YU']['LOCALE']  = array('sr_YU.ISO8859-2','sr_YU.ISO-8859-2','sr_YU');
$languages['sr']['ALIAS'] = 'sr_YU';

$languages['sv_SE']['NAME']    = 'Swedish';
$languages['sv_SE']['ALTNAME'] = 'Svenska';
$languages['sv_SE']['CHARSET'] = 'iso-8859-1';
$languages['sv_SE']['LOCALE']  = array('sv_SE.ISO8859-1','sv_SE.ISO-8859-1','sv_SE');
$languages['sv']['ALIAS'] = 'sv_SE';

$languages['th_TH']['NAME']    = 'Thai';
$languages['th_TH']['CHARSET'] = 'tis-620';
$languages['th_TH']['LOCALE']  = 'th_TH.TIS-620';
$languages['th']['ALIAS'] = 'th_TH';

$languages['tl_PH']['NAME']    = 'Tagalog';
$languages['tl_PH']['CHARSET'] = 'iso-8859-1';
$languages['tl_PH']['LOCALE']  = array('tl_PH.ISO8859-1','tl_PH.ISO-8859-1','tl_PH');
$languages['tl']['ALIAS'] = 'tl_PH';

$languages['tr_TR']['NAME']    = 'Turkish';
$languages['tr_TR']['CHARSET'] = 'iso-8859-9';
$languages['tr_TR']['LOCALE']  = array('tr_TR.ISO8859-9','tr_TR.ISO-8859-9','tr_TR');
$languages['tr']['ALIAS'] = 'tr_TR';

$languages['zh_TW']['NAME']    = 'Chinese Trad';
$languages['zh_TW']['CHARSET'] = 'big5';
$languages['zh_TW']['LOCALE']  = 'zh_TW.BIG5';
$languages['tw']['ALIAS'] = 'zh_TW';

$languages['zh_CN']['NAME']    = 'Chinese Simp';
$languages['zh_CN']['CHARSET'] = 'gb2312';
$languages['zh_CN']['LOCALE']  = 'zh_CN.GB2312';
$languages['cn']['ALIAS'] = 'zh_CN';

$languages['uk_UA']['NAME']    = 'Ukrainian';
$languages['uk_UA']['CHARSET'] = 'koi8-u';
$languages['uk_UA']['LOCALE']  = 'uk_UA.KOI8-U';
$languages['uk']['ALIAS'] = 'uk_UA';

$languages['ru_UA']['NAME']    = 'Russian (Ukrainian)';
$languages['ru_UA']['CHARSET'] = 'koi8-r';
$languages['ru_UA']['LOCALE']  = 'ru_UA.KOI8-R';

/*
$languages['vi_VN']['NAME']    = 'Vietnamese';
$languages['vi_VN']['CHARSET'] = 'utf-8';
$languages['vi']['ALIAS'] = 'vi_VN';
*/

// Right to left languages
$languages['ar']['NAME']    = 'Arabic';
$languages['ar']['CHARSET'] = 'windows-1256';
$languages['ar']['DIR']     = 'rtl';

$languages['fa_IR']['NAME']    = 'Farsi';
$languages['fa_IR']['CHARSET'] = 'utf-8';
$languages['fa_IR']['DIR']     = 'rtl';
$languages['fa_IR']['LOCALE']  = 'fa_IR.UTF-8';
$languages['fa']['ALIAS']      = 'fa_IR';

$languages['he_IL']['NAME']    = 'Hebrew';
$languages['he_IL']['CHARSET'] = 'windows-1255';
$languages['he_IL']['LOCALE']  = 'he_IL.CP1255';
$languages['he_IL']['DIR']     = 'rtl';
$languages['he']['ALIAS']      = 'he_IL';

$languages['ug']['NAME']    = 'Uighur';
$languages['ug']['CHARSET'] = 'utf-8';
$languages['ug']['DIR']     = 'rtl';

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
if (function_exists('ngettext')) {
    $gettext_flags += 8;
}

/* If gettext is fully loaded, cool */
if ($gettext_flags == 15) {
    $use_gettext = true;
}

/* If ngettext support is missing, load it */
elseif ($gettext_flags == 7) {
    $use_gettext = true;
    // load internal ngettext functions
    include_once(SM_PATH . 'class/l10n.class.php');
    include_once(SM_PATH . 'functions/ngettext.php');
}

/* If we can fake gettext, try that */
elseif ($gettext_flags == 0) {
    $use_gettext = true;
    include_once(SM_PATH . 'functions/gettext.php');
} else {
    /* Uh-ho.  A weird install */
    if (! $gettext_flags & 1) {
      /**
       * Function is used as replacement in broken installs
       * @ignore
       */
        function _($str) {
            return $str;
        }
    }
    if (! $gettext_flags & 2) {
      /**
       * Function is used as replacement in broken installs
       * @ignore
       */
        function bindtextdomain() {
            return;
        }
    }
    if (! $gettext_flags & 4) {
      /**
       * Function is used as replacemet in broken installs
       * @ignore
       */
        function textdomain() {
            return;
        }
    }
    if (! $gettext_flags & 8) {
        /**
         * Function is used as replacemet in broken installs
         * @ignore
         */
        function ngettext($str,$str2,$number) {
            if ($number>1) {
                return $str2;
            } else {
                return $str;
            }
        }
    }
}
?>