<?php

/**
 * strings.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the Squirrelmail code.
 *
 * $Id$
 * @package squirrelmail
 */

/**
 * SquirrelMail version number -- DO NOT CHANGE
 */
global $version;
$version = '1.5.1 [CVS]';

/**
 * SquirrelMail internal version number -- DO NOT CHANGE
 * $sm_internal_version = array (release, major, minor)
 */
global $SQM_INTERNAL_VERSION;
$SQM_INTERNAL_VERSION = array(1,5,1);

/**
 * There can be a circular issue with includes, where the $version string is
 * referenced by the include of global.php, etc. before it's defined.
 * For that reason, bring in global.php AFTER we define the version strings.
 */
require_once(SM_PATH . 'functions/global.php');

/**
 * Wraps text at $wrap characters
 *
 * Has a problem with special HTML characters, so call this before
 * you do character translation.
 *
 * Specifically, &#039 comes up as 5 characters instead of 1.
 * This should not add newlines to the end of lines.
 *
 * @param string line the line of text to wrap, by ref
 * @param int wrap the maximum line lenth
 * @return void
 */
function sqWordWrap(&$line, $wrap) {
    global $languages, $squirrelmail_language;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        if (mb_detect_encoding($line) != 'ASCII') {
            $line = $languages[$squirrelmail_language]['XTRA_CODE']('wordwrap', $line, $wrap);
            return;
        }
    }

    ereg("^([\t >]*)([^\t >].*)?$", $line, $regs);
    $beginning_spaces = $regs[1];
    if (isset($regs[2])) {
        $words = explode(' ', $regs[2]);
    } else {
        $words = '';
    }

    $i = 0;
    $line = $beginning_spaces;

    while ($i < count($words)) {
        /* Force one word to be on a line (minimum) */
        $line .= $words[$i];
        $line_len = strlen($beginning_spaces) + strlen($words[$i]) + 2;
        if (isset($words[$i + 1]))
            $line_len += strlen($words[$i + 1]);
        $i ++;

        /* Add more words (as long as they fit) */
        while ($line_len < $wrap && $i < count($words)) {
            $line .= ' ' . $words[$i];
            $i++;
            if (isset($words[$i]))
                $line_len += strlen($words[$i]) + 1;
            else
                $line_len += 1;
        }

        /* Skip spaces if they are the first thing on a continued line */
        while (!isset($words[$i]) && $i < count($words)) {
            $i ++;
        }

        /* Go to the next line if we have more to process */
        if ($i < count($words)) {
            $line .= "\n";
        }
    }
}

/**
 * Does the opposite of sqWordWrap()
 * @param string body the text to un-wordwrap
 * @return void
 */
function sqUnWordWrap(&$body) {
    global $squirrelmail_language;

    if ($squirrelmail_language == 'ja_JP') {
        return;
    }

    $lines = explode("\n", $body);
    $body = '';
    $PreviousSpaces = '';
    $cnt = count($lines);
    for ($i = 0; $i < $cnt; $i ++) {
        preg_match("/^([\t >]*)([^\t >].*)?$/", $lines[$i], $regs);
        $CurrentSpaces = $regs[1];
        if (isset($regs[2])) {
            $CurrentRest = $regs[2];
        } else {
            $CurrentRest = '';
        }

        if ($i == 0) {
            $PreviousSpaces = $CurrentSpaces;
            $body = $lines[$i];
        } else if (($PreviousSpaces == $CurrentSpaces) /* Do the beginnings match */
                   && (strlen($lines[$i - 1]) > 65)    /* Over 65 characters long */
                   && strlen($CurrentRest)) {          /* and there's a line to continue with */
            $body .= ' ' . $CurrentRest;
        } else {
            $body .= "\n" . $lines[$i];
            $PreviousSpaces = $CurrentSpaces;
        }
    }
    $body .= "\n";
}

/**
 * If $haystack is a full mailbox name and $needle is the mailbox
 * separator character, returns the last part of the mailbox name.
 *
 * @param string haystack full mailbox name to search
 * @param string needle the mailbox separator character
 * @return string the last part of the mailbox name
 */
function readShortMailboxName($haystack, $needle) {

    if ($needle == '') {
        $elem = $haystack;
    } else {
        $parts = explode($needle, $haystack);
        $elem = array_pop($parts);
        while ($elem == '' && count($parts)) {
            $elem = array_pop($parts);
        }
    }
    return( $elem );
}

/**
 * php_self
 *
 * Creates an URL for the page calling this function, using either the PHP global
 * REQUEST_URI, or the PHP global PHP_SELF with QUERY_STRING added.
 *
 * @return string the complete url for this page
 */
function php_self () {
    if ( sqgetGlobalVar('REQUEST_URI', $req_uri, SQ_SERVER) && !empty($req_uri) ) {
      return $req_uri;
    }

    if ( sqgetGlobalVar('PHP_SELF', $php_self, SQ_SERVER) && !empty($php_self) ) {

      // need to add query string to end of PHP_SELF to match REQUEST_URI
      //
      if ( sqgetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER) && !empty($query_string) ) {
         $php_self .= '?' . $query_string;
      }

      return $php_self;
    }

    return '';
}


/**
 * get_location
 *
 * Determines the location to forward to, relative to your server.
 * This is used in HTTP Location: redirects.
 * If this doesnt work correctly for you (although it should), you can
 * remove all this code except the last two lines, and have it return
 * the right URL for your site, something like:
 *
 *   http://www.example.com/squirrelmail/
 *
 * @return string the base url for this SquirrelMail installation
 */
function get_location () {

    global $imap_server_type;

    /* Get the path, handle virtual directories */
    if(strpos(php_self(), '?')) {
        $path = substr(php_self(), 0, strpos(php_self(), '?'));
    } else {
        $path = php_self();
    }
    $path = substr($path, 0, strrpos($path, '/'));
    if ( sqgetGlobalVar('sq_base_url', $full_url, SQ_SESSION) ) {
      return $full_url . $path;
    }

    /* Check if this is a HTTPS or regular HTTP request. */
    $proto = 'http://';

    /*
     * If you have 'SSLOptions +StdEnvVars' in your apache config
     *     OR if you have HTTPS=on in your HTTP_SERVER_VARS
     *     OR if you are on port 443
     */
    $getEnvVar = getenv('HTTPS');
    if ((isset($getEnvVar) && !strcasecmp($getEnvVar, 'on')) ||
        (sqgetGlobalVar('HTTPS', $https_on, SQ_SERVER) && !strcasecmp($https_on, 'on')) ||
        (sqgetGlobalVar('SERVER_PORT', $server_port, SQ_SERVER) &&  $server_port == 443)) {
        $proto = 'https://';
    }

    /* Get the hostname from the Host header or server config. */
    if ( !sqgetGlobalVar('HTTP_HOST', $host, SQ_SERVER) || empty($host) ) {
      if ( !sqgetGlobalVar('SERVER_NAME', $host, SQ_SERVER) || empty($host) ) {
        $host = '';
      }
    }

    $port = '';
    if (! strstr($host, ':')) {
        if (sqgetGlobalVar('SERVER_PORT', $server_port, SQ_SERVER)) {
            if (($server_port != 80 && $proto == 'http://') ||
                ($server_port != 443 && $proto == 'https://')) {
                $port = sprintf(':%d', $server_port);
            }
        }
    }

   /* this is a workaround for the weird macosx caching that
      causes Apache to return 16080 as the port number, which causes
      SM to bail */

   if ($imap_server_type == 'macosx' && $port == ':16080') {
        $port = '';
   }

   /* Fallback is to omit the server name and use a relative */
   /* URI, although this is not RFC 2616 compliant.          */
   $full_url = ($host ? $proto . $host . $port : '');
   sqsession_register($full_url, 'sq_base_url');
   return $full_url . $path;
}


/**
 * Encrypts password
 *
 * These functions are used to encrypt the password before it is
 * stored in a cookie. The encryption key is generated by
 * OneTimePadCreate();
 *
 * @param string string the (password)string to encrypt
 * @param string epad the encryption key
 * @return string the base64-encoded encrypted password
 */
function OneTimePadEncrypt ($string, $epad) {
    $pad = base64_decode($epad);
    $encrypted = '';
    for ($i = 0; $i < strlen ($string); $i++) {
        $encrypted .= chr (ord($string[$i]) ^ ord($pad[$i]));
    }

    return base64_encode($encrypted);
}

/**
 * Decrypts a password from the cookie
 *
 * Decrypts a password from the cookie, encrypted by OneTimePadEncrypt.
 * This uses the encryption key that is stored in the session.
 *
 * @param string string the string to decrypt
 * @param string epad the encryption key from the session
 * @return string the decrypted password
 */
function OneTimePadDecrypt ($string, $epad) {
    $pad = base64_decode($epad);
    $encrypted = base64_decode ($string);
    $decrypted = '';
    for ($i = 0; $i < strlen ($encrypted); $i++) {
        $decrypted .= chr (ord($encrypted[$i]) ^ ord($pad[$i]));
    }

    return $decrypted;
}


/**
 * Randomizes the mt_rand() function.
 *
 * Toss this in strings or integers and it will seed the generator 
 * appropriately. With strings, it is better to get them long. 
 * Use md5() to lengthen smaller strings.
 *
 * @param mixed val a value to seed the random number generator
 * @return void
 */
function sq_mt_seed($Val) {
    /* if mt_getrandmax() does not return a 2^n - 1 number,
       this might not work well.  This uses $Max as a bitmask. */
    $Max = mt_getrandmax();

    if (! is_int($Val)) {
            $Val = crc32($Val);
    }

    if ($Val < 0) {
        $Val *= -1;
    }

    if ($Val = 0) {
        return;
    }

    mt_srand(($Val ^ mt_rand(0, $Max)) & $Max);
}


/**
 * Init random number generator
 *
 * This function initializes the random number generator fairly well.
 * It also only initializes it once, so you don't accidentally get
 * the same 'random' numbers twice in one session.
 *
 * @return void
 */
function sq_mt_randomize() {
    static $randomized;

    if ($randomized) {
        return;
    }

    /* Global. */
    sqgetGlobalVar('REMOTE_PORT', $remote_port, SQ_SERVER);
    sqgetGlobalVar('REMOTE_ADDR', $remote_addr, SQ_SERVER);
    sq_mt_seed((int)((double) microtime() * 1000000));
    sq_mt_seed(md5($remote_port . $remote_addr . getmypid()));

    /* getrusage */
    if (function_exists('getrusage')) {
        /* Avoid warnings with Win32 */
        $dat = @getrusage();
        if (isset($dat) && is_array($dat)) {
            $Str = '';
            foreach ($dat as $k => $v)
                {
                    $Str .= $k . $v;
                }
            sq_mt_seed(md5($Str));
        }
    }

    if(sqgetGlobalVar('UNIQUE_ID', $unique_id, SQ_SERVER)) {
        sq_mt_seed(md5($unique_id));
    }

    $randomized = 1;
}

/**
 * Creates encryption key
 *
 * Creates an encryption key for encrypting the password stored in the cookie.
 * The encryption key itself is stored in the session.
 *
 * @param int length optional, length of the string to generate
 * @return string the encryption key
 */
function OneTimePadCreate ($length=100) {
    sq_mt_randomize();

    $pad = '';
    for ($i = 0; $i < $length; $i++) {
        $pad .= chr(mt_rand(0,255));
    }

    return base64_encode($pad);
}

/**
 * Returns a string showing the size of the message/attachment.
 *
 * @param int bytes the filesize in bytes
 * @return string the filesize in human readable format
 */
function show_readable_size($bytes) {
    $bytes /= 1024;
    $type = 'k';

    if ($bytes / 1024 > 1) {
        $bytes /= 1024;
        $type = 'M';
    }

    if ($bytes < 10) {
        $bytes *= 10;
        settype($bytes, 'integer');
        $bytes /= 10;
    } else {
        settype($bytes, 'integer');
    }

    return $bytes . '<small>&nbsp;' . $type . '</small>';
}

/**
 * Generates a random string from the caracter set you pass in
 *
 * @param int size the size of the string to generate
 * @param string chars a string containing the characters to use
 * @param int flags a flag to add a specific set to the characters to use:
 *     Flags:
 *       1 = add lowercase a-z to $chars
 *       2 = add uppercase A-Z to $chars
 *       4 = add numbers 0-9 to $chars
 * @return string the random string
 */
function GenerateRandomString($size, $chars, $flags = 0) {
    if ($flags & 0x1) {
        $chars .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if ($flags & 0x2) {
        $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if ($flags & 0x4) {
        $chars .= '0123456789';
    }

    if (($size < 1) || (strlen($chars) < 1)) {
        return '';
    }

    sq_mt_randomize(); /* Initialize the random number generator */

    $String = '';
    $j = strlen( $chars ) - 1;
    while (strlen($String) < $size) {
        $String .= $chars{mt_rand(0, $j)};
    }

    return $String;
}

/**
 * Escapes special characters for use in IMAP commands.
 *
 * @param string the string to escape
 * @return string the escaped string
 */
function quoteimap($str) {
    return preg_replace("/([\"\\\\])/", "\\\\$1", $str);
}

/**
 * Trims array
 *
 * Trims every element in the array, ie. remove the first char of each element
 * @param array array the array to trim
 */
function TrimArray(&$array) {
    foreach ($array as $k => $v) {
        global $$k;
        if (is_array($$k)) {
            foreach ($$k as $k2 => $v2) {
                $$k[$k2] = substr($v2, 1);
            }
        } else {
            $$k = substr($v, 1);
        }

        /* Re-assign back to array. */
        $array[$k] = $$k;
    }
}

/**
 * Create compose link
 *
 * Returns a link to the compose-page, taking in consideration
 * the compose_in_new and javascript settings.
 * @param string url the URL to the compose page
 * @param string text the link text, default "Compose"
 * @return string a link to the compose page
 */
function makeComposeLink($url, $text = null, $target='')
{
    global $compose_new_win,$javascript_on;

    if(!$text) {
        $text = _("Compose");
    }


    // if not using "compose in new window", make 
    // regular link and be done with it
    if($compose_new_win != '1') {
        return makeInternalLink($url, $text, $target);
    }


    // build the compose in new window link...  


    // if javascript is on, use onClick event to handle it
    if($javascript_on) {
        sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);
        return '<a href="javascript:void(0)" onclick="comp_in_new(\''.$base_uri.$url.'\')">'. $text.'</a>';
    }


    // otherwise, just open new window using regular HTML
    return makeInternalLink($url, $text, '_blank');

}

/**
 * Print variable
 *
 * sm_print_r($some_variable, [$some_other_variable [, ...]]);
 *
 * Debugging function - does the same as print_r, but makes sure special
 * characters are converted to htmlentities first.  This will allow
 * values like <some@email.address> to be displayed.
 * The output is wrapped in <<pre>> and <</pre>> tags.
 *
 * @return void
 */
function sm_print_r() {
    ob_start();  // Buffer output
    foreach(func_get_args() as $var) {
        print_r($var);
        echo "\n";
    }
    $buffer = ob_get_contents(); // Grab the print_r output
    ob_end_clean();  // Silently discard the output & stop buffering
    print '<pre>';
    print htmlentities($buffer);
    print '</pre>';
}

/**
 * version of fwrite which checks for failure
 */
function sq_fwrite($fp, $string) {
	// write to file
	$count = @fwrite($fp,$string);
	// the number of bytes written should be the length of the string
	if($count != strlen($string)) {
		return FALSE;
	}

	return $count;
}

/**
 * sq_get_html_translation_table
 *
 * Returns the translation table used by sq_htmlentities()
 *
 * @param integer $table html translation table. Possible values (without quotes):
 *             <ul>
 *                <li>HTML_ENTITIES - full html entities table defined by charset</li>
 *                <li>HTML_SPECIALCHARS - html special characters table</li>
 *             </ul>
 * @param integer $quote_style quote encoding style. Possible values (without quotes):
 *		<ul>
 *                <li>ENT_COMPAT - (default) encode double quotes</li>
 *                <li>ENT_NOQUOTES -  don't encode double or single quotes</li>
 *                <li>ENT_QUOTES - encode double and single quotes</li>
 *		</ul>
 * @param string $charset charset used for encoding. default to us-ascii, 'auto' uses $default_charset global value.
 * @return array html translation array
 */
function sq_get_html_translation_table($table,$quote_style=ENT_COMPAT,$charset='us-ascii') {
  global $default_charset;

  if ($table == HTML_SPECIALCHARS) $charset='us-ascii';

  // Start array with ampersand
  $sq_html_ent_table = array( "&" => '&amp;' );

  // < and >
  $sq_html_ent_table = array_merge($sq_html_ent_table,
			array("<" => '&lt;',
			      ">" => '&gt;')
			);
  // double quotes
  if ($quote_style == ENT_COMPAT)
     $sq_html_ent_table = array_merge($sq_html_ent_table,
			    array("\"" => '&quot;')
			    );

  // double and single quotes
  if ($quote_style == ENT_QUOTES)
     $sq_html_ent_table = array_merge($sq_html_ent_table,
			    array("\"" => '&quot;',
			      "'" => '&#39;')
			    );

  if ($charset=='auto') $charset=$default_charset;

  // add entities that depend on charset
  switch($charset){
  case 'iso-8859-1':
    include_once(SM_PATH . 'functions/htmlentities/iso-8859-1.php');
    break;
  case 'utf-8':
    include_once(SM_PATH . 'functions/htmlentities/utf-8.php');
    break;
  case 'us-ascii':
  default:
    break;
  }
  // return table
  return $sq_html_ent_table;
}

/**
 * sq_htmlentities
 *
 * Convert all applicable characters to HTML entities.
 * Minimal php requirement - v.4.0.5
 *
 * @param string $string string that has to be sanitized
 * @param integer $quote_style quote encoding style. Possible values (without quotes):
 *		<ul>
 *                <li>ENT_COMPAT - (default) encode double quotes</li>
 *                <li>ENT_NOQUOTES - don't encode double or single quotes</li>
 *                <li>ENT_QUOTES - encode double and single quotes</li>
 *		</ul>
 * @param string $charset charset used for encoding. defaults to 'us-ascii', 'auto' uses $default_charset global value.
 * @return string sanitized string
 */
function sq_htmlentities($string,$quote_style=ENT_COMPAT,$charset='us-ascii') {
  // get translation table
  $sq_html_ent_table=sq_get_html_translation_table(HTML_ENTITIES,$quote_style,$charset);
  // convert characters
  return str_replace(array_keys($sq_html_ent_table),array_values($sq_html_ent_table),$string);
}

$PHP_SELF = php_self();
?>