<?php

/** 
 * strings.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the Squirrelmail code.
 *
 * $Id$
 */

/**
 * SquirrelMail version number -- DO NOT CHANGE
 */
global $version;
$version = '1.3.1 [CVS-DEVEL]';

/**
 * Wraps text at $wrap characters
 *
 * Has a problem with special HTML characters, so call this before
 * you do character translation.
 *
 * Specifically, &#039 comes up as 5 characters instead of 1.
 * This should not add newlines to the end of lines.
 */
function sqWordWrap(&$line, $wrap) {
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
 */
function sqUnWordWrap(&$body) {
    $lines = explode("\n", $body);
    $body = '';
    $PreviousSpaces = '';
    $cnt = count($lines);
    for ($i = 0; $i < $cnt; $i ++) {
        preg_match("/^([\t >]*)([^\t >].*)?$/", $lines[$i], $regs);
        $CurrentSpaces = $regs[1];
        if (isset($regs[2])) {
            $CurrentRest = $regs[2];
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
 * Returns an array of email addresses.
 * Be cautious of "user@host.com"
 */
function parseAddrs($text) {
    if (trim($text) == '')
        return array();
    $text = str_replace(' ', '', $text);
    $text = ereg_replace('"[^"]*"', '', $text);
    $text = ereg_replace('\\([^\\)]*\\)', '', $text);
    $text = str_replace(',', ';', $text);
    $array = explode(';', $text);
    for ($i = 0; $i < count ($array); $i++) {
        $array[$i] = eregi_replace ('^.*[<]', '', $array[$i]);
        $array[$i] = eregi_replace ('[>].*$', '', $array[$i]);
    }
    return $array;
}

/**
 * Returns a line of comma separated email addresses from an array.
 */
function getLineOfAddrs($array) {
    if (is_array($array)) {
        $to_line = implode(', ', $array);
        $to_line = ereg_replace(', (, )+', ', ', $to_line);
        $to_line = trim(ereg_replace('^, ', '', $to_line));
        if( substr( $to_line, -1 ) == ',' )
            $to_line = substr( $to_line, 0, -1 );
    } else {
        $to_line = '';
    }
    
    return( $to_line );
}

function php_self () {
    global $PHP_SELF, $HTTP_SERVER_VARS;
    
    if (isset($HTTP_SERVER_VARS['REQUEST_URI']) && !empty($HTTP_SERVER_VARS['REQUEST_URI']) ) {
        return $HTTP_SERVER_VARS['REQUEST_URI'];
    }

    if (isset($PHP_SELF) && !empty($PHP_SELF)) {
        return $PHP_SELF;
    } else if (isset($HTTP_SERVER_VARS['PHP_SELF']) &&
               !empty($HTTP_SERVER_VARS['PHP_SELF'])) {
        return $HTTP_SERVER_VARS['PHP_SELF'];
    } else {
        return '';
    }
}


/**
 * This determines the location to forward to relative to your server.
 * If this doesnt work correctly for you (although it should), you can
 * remove all this code except the last two lines, and change the header()
 * function to look something like this, customized to the location of
 * SquirrelMail on your server:
 *
 *   http://www.myhost.com/squirrelmail/src/login.php
 */
function get_location () {
    
    global $PHP_SELF, $SERVER_NAME, $HTTP_HOST, $SERVER_PORT,
        $HTTP_SERVER_VARS, $imap_server_type;
    
    /* Get the path, handle virtual directories */
    $path = substr(php_self(), 0, strrpos(php_self(), '/'));
    
    /* Check if this is a HTTPS or regular HTTP request. */
    $proto = 'http://';
    
    /*
     * If you have 'SSLOptions +StdEnvVars' in your apache config
     *     OR if you have HTTPS in your HTTP_SERVER_VARS
     *     OR if you are on port 443
     */
    $getEnvVar = getenv('HTTPS');
    if ((isset($getEnvVar) && !strcasecmp($getEnvVar, 'on')) ||
        (isset($HTTP_SERVER_VARS['HTTPS'])) ||
        (isset($HTTP_SERVER_VARS['SERVER_PORT']) &&
         $HTTP_SERVER_VARS['SERVER_PORT'] == 443)) {
        $proto = 'https://';
    }
    
    /* Get the hostname from the Host header or server config. */
    $host = '';
    if (isset($HTTP_HOST) && !empty($HTTP_HOST)) {
        $host = $HTTP_HOST;
    } else if (isset($SERVER_NAME) && !empty($SERVER_NAME)) {
        $host = $SERVER_NAME;
    } else if (isset($HTTP_SERVER_VARS['SERVER_NAME']) &&
               !empty($HTTP_SERVER_VARS['SERVER_NAME'])) {
        $host = $HTTP_SERVER_VARS['SERVER_NAME'];
    }

    
    $port = '';
    if (! strstr($host, ':')) {
        if (isset($SERVER_PORT)) {
            if (($SERVER_PORT != 80 && $proto == 'http://')
                || ($SERVER_PORT != 443 && $proto == 'https://')) {
                $port = sprintf(':%d', $SERVER_PORT);
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
    return ($host ? $proto . $host . $port . $path : $path);
}


/**
 * These functions are used to encrypt the passowrd before it is
 * stored in a cookie.
 */
function OneTimePadEncrypt ($string, $epad) {
    $pad = base64_decode($epad);
    $encrypted = '';
    for ($i = 0; $i < strlen ($string); $i++) {
        $encrypted .= chr (ord($string[$i]) ^ ord($pad[$i]));
    }
    
    return base64_encode($encrypted);
}

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
 * Randomize the mt_rand() function.  Toss this in strings or integers
 * and it will seed the generator appropriately. With strings, it is
 * better to get them long. Use md5() to lengthen smaller strings.
 */
function sq_mt_seed($Val) {
    /* if mt_getrandmax() does not return a 2^n - 1 number,
       this might not work well.  This uses $Max as a bitmask. */
    $Max = mt_getrandmax();
    
    if (! is_int($Val)) {
        if (function_exists('crc32')) {
            $Val = crc32($Val);
        } else {
            $Str = $Val;
            $Pos = 0;
            $Val = 0;
            $Mask = $Max / 2;
            $HighBit = $Max ^ $Mask;
            while ($Pos < strlen($Str)) {
                if ($Val & $HighBit) {
                    $Val = (($Val & $Mask) << 1) + 1;
                } else {
                    $Val = ($Val & $Mask) << 1;
                }
                $Val ^= $Str[$Pos];
                $Pos ++;
            }
        }
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
 * This function initializes the random number generator fairly well.
 * It also only initializes it once, so you don't accidentally get
 * the same 'random' numbers twice in one session.
 */
function sq_mt_randomize() {
    global $REMOTE_PORT, $REMOTE_ADDR, $UNIQUE_ID;
    static $randomized;
    
    if ($randomized) {
        return;
    }
    
    /* Global. */
    sq_mt_seed((int)((double) microtime() * 1000000));
    sq_mt_seed(md5($REMOTE_PORT . $REMOTE_ADDR . getmypid()));
    
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
    
    /* Apache-specific */
    sq_mt_seed(md5($UNIQUE_ID));
    
    $randomized = 1;
}

function OneTimePadCreate ($length=100) {
    sq_mt_randomize();
    
    $pad = '';
    for ($i = 0; $i < $length; $i++) {
        $pad .= chr(mt_rand(0,255));
    }
    
    return base64_encode($pad);
}

/**
 * Check if we have a required PHP-version. Return TRUE if we do,
 * or FALSE if we don't.
 *
 *     To check for 4.0.1, use sqCheckPHPVersion(4,0,1)
 *     To check for 4.0b3, use sqCheckPHPVersion(4,0,-3)
 *
 * Does not handle betas like 4.0.1b1 or development versions
 */
function sqCheckPHPVersion($major, $minor, $release) {
    
    $ver = phpversion();
    eregi('^([0-9]+)\\.([0-9]+)(.*)', $ver, $regs);
    
    /* Parse the version string. */
    $vmajor  = strval($regs[1]);
    $vminor  = strval($regs[2]);
    $vrel    = $regs[3];
    if($vrel[0] == '.') {
        $vrel = strval(substr($vrel, 1));
    }
    if($vrel[0] == 'b' || $vrel[0] == 'B') {
        $vrel = - strval(substr($vrel, 1));
    }
    if($vrel[0] == 'r' || $vrel[0] == 'R') {
        $vrel = - strval(substr($vrel, 2))/10;
    }
    
    /* Compare major version. */
    if ($vmajor < $major) { return false; }
    if ($vmajor > $major) { return true; }
    
    /* Major is the same. Compare minor. */
    if ($vminor < $minor) { return false; }
    if ($vminor > $minor) { return true; }
    
    /* Major and minor is the same as the required one. Compare release */
    if ($vrel >= 0 && $release >= 0) {       /* Neither are beta */
        if($vrel < $release) return false;
    } else if($vrel >= 0 && $release < 0) {  /* This is not beta, required is beta */
        return true;
    } else if($vrel < 0 && $release >= 0){   /* This is beta, require not beta */
        return false;
    } else {                                 /* Both are beta */
        if($vrel > $release) return false;
    }
    
    return true;
}

/**
 *  Returns a string showing the size of the message/attachment.
 */
function show_readable_size($bytes) {
    $bytes /= 1024;
    $type = 'k';
    
    if ($bytes / 1024 > 1) {
        $bytes /= 1024;
        $type = 'm';
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
 * Flags:
 *   1 = add lowercase a-z to $chars
 *   2 = add uppercase A-Z to $chars
 *   4 = add numbers 0-9 to $chars
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

function quoteIMAP($str) {
    return ereg_replace('(["\\])', '\\\\1', $str);
}

/**
 * Trims every element in the array
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
 * Removes slashes from every element in the array
 */
function RemoveSlashes(&$array) {
    foreach ($array as $k => $v) {
        global $$k;
        if (is_array($$k)) {
            foreach ($$k as $k2 => $v2) {
                $newArray[stripslashes($k2)] = stripslashes($v2);
            }
            $$k = $newArray;
        } else {
            $$k = stripslashes($v);
        }
        
        /* Re-assign back to the array. */
        $array[$k] = $$k;
    }
}

$PHP_SELF = php_self();

?>
