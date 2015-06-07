<?php

/**
 * strings.php
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the SquirrelMail code.
 *
 * @copyright 1999-2015 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Appends citation markers to the string.
 * Also appends a trailing space.
 *
 * @author Justus Pendleton
 * @param string $str The string to append to
 * @param int $citeLevel the number of markers to append
 * @return null
 * @since 1.5.1
 */
function sqMakeCite (&$str, $citeLevel) {
    for ($i = 0; $i < $citeLevel; $i++) {
        $str .= '>';
    }
    if ($citeLevel != 0) {
        $str .= ' ';
    }
}

/**
 * Create a newline in the string, adding citation
 * markers to the newline as necessary.
 *
 * @author Justus Pendleton
 * @param string $str the string to make a newline in
 * @param int $citeLevel the citation level the newline is at
 * @param int $column starting column of the newline
 * @return null
 * @since 1.5.1
 */
function sqMakeNewLine (&$str, $citeLevel, &$column) {
    $str .= "\n";
    $column = 0;
    if ($citeLevel > 0) {
        sqMakeCite ($str, $citeLevel);
        $column = $citeLevel + 1;
    } else {
        $column = 0;
    }
}

/**
 * Checks for spaces in strings - only used if PHP doesn't have native ctype support
 *
 * You might be able to rewrite the function by adding short evaluation form.
 *
 * possible problems:
 *  - iso-2022-xx charsets  - hex 20 might be part of other symbol. I might
 * be wrong. 0x20 is not used in iso-2022-jp. I haven't checked iso-2022-kr
 * and iso-2022-cn mappings.
 *
 *  - no-break space (&nbsp;) - it is 8bit symbol, that depends on charset.
 * there are at least three different charset groups that have nbsp in
 * different places.
 *
 * I don't see any charset/nbsp options in php ctype either.
 *
 * @param string $string tested string
 * @return bool true when only whitespace symbols are present in test string
 * @since 1.5.1
 */
function sm_ctype_space($string) {
    if ( preg_match('/^[\x09-\x0D]|^\x20/', $string) || $string=='') {
        return true;
    } else {
        return false;
    }
}

/**
 * Wraps text at $wrap characters.  While sqWordWrap takes
 * a single line of text and wraps it, this function works
 * on the entire corpus at once, this allows it to be a little
 * bit smarter and when and how to wrap.
 *
 * @author Justus Pendleton
 * @param string $body the entire body of text
 * @param int $wrap the maximum line length
 * @return string the wrapped text
 * @since 1.5.1
 */
function &sqBodyWrap (&$body, $wrap) {
    //check for ctype support, and fake it if it doesn't exist
    if (!function_exists('ctype_space')) {
        function ctype_space ($string) {
            return sm_ctype_space($string);
        }
    }

    // the newly wrapped text
    $outString = '';
    // current column since the last newline in the outstring
    $outStringCol = 0;
    $length = sq_strlen($body);
    // where we are in the original string
    $pos = 0;
    // the number of >>> citation markers we are currently at
    $citeLevel = 0;

    // the main loop, whenever we start a newline of input text
    // we start from here
    while ($pos < $length) {
       // we're at the beginning of a line, get the new cite level
       $newCiteLevel = 0;

       while (($pos < $length) && (sq_substr($body,$pos,1) == '>')) {
           $newCiteLevel++;
           $pos++;

           // skip over any spaces interleaved among the cite markers
           while (($pos < $length) && (sq_substr($body,$pos,1) == ' ')) {

               $pos++;

           }
           if ($pos >= $length) {
               break;
           }
       }

       // special case: if this is a blank line then maintain it
       // (i.e. try to preserve original paragraph breaks)
       // unless they occur at the very beginning of the text
       if ((sq_substr($body,$pos,1) == "\n" ) && (sq_strlen($outString) != 0)) {
           $outStringLast = $outString{sq_strlen($outString) - 1};
           if ($outStringLast != "\n") {
               $outString .= "\n";
           }
           sqMakeCite ($outString, $newCiteLevel);
           $outString .= "\n";
           $pos++;
           $outStringCol = 0;
           continue;
       }

       // if the cite level has changed, then start a new line
       // with the new cite level.
       if (($citeLevel != $newCiteLevel) && ($pos > ($newCiteLevel + 1)) && ($outStringCol != 0)) {
           sqMakeNewLine ($outString, 0, $outStringCol);
       }

       $citeLevel = $newCiteLevel;

       // prepend the quote level if necessary
       if ($outStringCol == 0) {
           sqMakeCite ($outString, $citeLevel);
           // if we added a citation then move the column
           // out by citelevel + 1 (the cite markers + the space)
           $outStringCol = $citeLevel + ($citeLevel ? 1 : 0);
       } else if ($outStringCol > $citeLevel) {
           // not a cite and we're not at the beginning of a line
           // in the output.  add a space to separate the new text
           // from previous text.
           $outString .= ' ';
           $outStringCol++;
       }

       // find the next newline -- we don't want to go further than that
       $nextNewline = sq_strpos ($body, "\n", $pos);
       if ($nextNewline === FALSE) {
           $nextNewline = $length;
       }

       // Don't wrap unquoted lines at all.  For now the textarea
       // will work fine for this.  Maybe revisit this later though
       // (for completeness more than anything else, I think)
       if ($citeLevel == 0) {
           $outString .= sq_substr ($body, $pos, ($nextNewline - $pos));
           $outStringCol = $nextNewline - $pos;
           if ($nextNewline != $length) {
               sqMakeNewLine ($outString, 0, $outStringCol);
           }
           $pos = $nextNewline + 1;
           continue;
       }
       /**
        * Set this to false to stop appending short strings to previous lines
        */
       $smartwrap = true;
       // inner loop, (obviously) handles wrapping up to
       // the next newline
       while ($pos < $nextNewline) {
           // skip over initial spaces
           while (($pos < $nextNewline) && (ctype_space (sq_substr($body,$pos,1)))) {
               $pos++;
           }
           // if this is a short line then just append it and continue outer loop
           if (($outStringCol + $nextNewline - $pos) <= ($wrap - $citeLevel - 1) ) {
               // if this is the final line in the input string then include
               // any trailing newlines
               //      echo substr($body,$pos,$wrap). "<br />";
               if (($nextNewline + 1 == $length) && (sq_substr($body,$nextNewline,1) == "\n")) {
                   $nextNewline++;
               }

               // trim trailing spaces
               $lastRealChar = $nextNewline;
               while (($lastRealChar > $pos && $lastRealChar < $length) && (ctype_space (sq_substr($body,$lastRealChar,1)))) {
                   $lastRealChar--;
               }
               // decide if appending the short string is what we want
               if (($nextNewline < $length && sq_substr($body,$nextNewline,1) == "\n") &&
                     isset($lastRealChar)) {
                   $mypos = $pos;
                   //check the first word:
                   while (($mypos < $length) && (sq_substr($body,$mypos,1) == '>')) {
                       $mypos++;
                       // skip over any spaces interleaved among the cite markers
                       while (($mypos < $length) && (sq_substr($body,$mypos,1) == ' ')) {
                           $mypos++;
                       }
                   }
/*
                     $ldnspacecnt = 0;
                     if ($mypos == $nextNewline+1) {
                        while (($mypos < $length) && ($body{$mypos} == ' ')) {
                         $ldnspacecnt++;
                        }
                     }
*/

                   $firstword = sq_substr($body,$mypos,sq_strpos($body,' ',$mypos) - $mypos);
                   //if ($dowrap || $ldnspacecnt > 1 || ($firstword && (
                   if (!$smartwrap || $firstword && (
                                        $firstword{0} == '-' ||
                                        $firstword{0} == '+' ||
                                        $firstword{0} == '*' ||
                                        sq_substr($firstword,0,1) == sq_strtoupper(sq_substr($firstword,0,1)) ||
                                        strpos($firstword,':'))) {
                        $outString .= sq_substr($body,$pos,($lastRealChar - $pos+1));
                        $outStringCol += ($lastRealChar - $pos);
                        sqMakeNewLine($outString,$citeLevel,$outStringCol);
                        $nextNewline++;
                        $pos = $nextNewline;
                        $outStringCol--;
                        continue;
                   }

               }

               $outString .= sq_substr ($body, $pos, ($lastRealChar - $pos + 1));
               $outStringCol += ($lastRealChar - $pos);
               $pos = $nextNewline + 1;
               continue;
           }

           $eol = $pos + $wrap - $citeLevel - $outStringCol;
           // eol is the tentative end of line.
           // look backwards for there for a whitespace to break at.
           // if it's already less than our current position then
           // our current line is already too long, break immediately
           // and restart outer loop
           if ($eol <= $pos) {
               sqMakeNewLine ($outString, $citeLevel, $outStringCol);
               continue;
           }

           // start looking backwards for whitespace to break at.
           $breakPoint = $eol;
           while (($breakPoint > $pos) && (! ctype_space (sq_substr($body,$breakPoint,1)))) {
               $breakPoint--;
           }

           // if we didn't find a breakpoint by looking backward then we
           // need to figure out what to do about that
           if ($breakPoint == $pos) {
               // if we are not at the beginning then end this line
               // and start a new loop
               if ($outStringCol > ($citeLevel + 1)) {
                   sqMakeNewLine ($outString, $citeLevel, $outStringCol);
                   continue;
               } else {
                   // just hard break here.  most likely we are breaking
                   // a really long URL.  could also try searching
                   // forward for a break point, which is what Mozilla
                   // does.  don't bother for now.
                   $breakPoint = $eol;
               }
           }

           // special case: maybe we should have wrapped last
           // time.  if the first breakpoint here makes the
           // current line too long and there is already text on
           // the current line, break and loop again if at
           // beginning of current line, don't force break
           $SLOP = 6;
           if ((($outStringCol + ($breakPoint - $pos)) > ($wrap + $SLOP)) && ($outStringCol > ($citeLevel + 1))) {
               sqMakeNewLine ($outString, $citeLevel, $outStringCol);
               continue;
           }

           // skip newlines or whitespace at the beginning of the string
           $substring = sq_substr ($body, $pos, ($breakPoint - $pos));
           $substring = rtrim ($substring); // do rtrim and ctype_space have the same ideas about whitespace?
           $outString .= $substring;
           $outStringCol += sq_strlen ($substring);
           // advance past the whitespace which caused the wrap
           $pos = $breakPoint;
           while (($pos < $length) && (ctype_space (sq_substr($body,$pos,1)))) {
               $pos++;
           }
           if ($pos < $length) {
               sqMakeNewLine ($outString, $citeLevel, $outStringCol);
           }
       }
    }

    return $outString;
}

/**
 * Wraps text at $wrap characters
 *
 * Has a problem with special HTML characters, so call this before
 * you do character translation.
 *
 * Specifically, &amp;#039; comes up as 5 characters instead of 1.
 * This should not add newlines to the end of lines.
 *
 * @param string $line the line of text to wrap, by ref
 * @param int $wrap the maximum line lenth
 * @param string $charset name of charset used in $line string. Available since v.1.5.1.
 * @return void
 * @since 1.0
 */
function sqWordWrap(&$line, $wrap, $charset='') {
    global $languages, $squirrelmail_language;

    // Use custom wrapping function, if translation provides it
    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_wordwrap')) {
        if (mb_detect_encoding($line) != 'ASCII') {
            $line = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_wordwrap', $line, $wrap);
            return;
        }
    }

    preg_match('/^([\t >]*)([^\t >].*)?$/', $line, $regs);
    $beginning_spaces = $regs[1];
    if (isset($regs[2])) {
        $words = explode(' ', $regs[2]);
    } else {
        $words = array();
    }

    $i = 0;
    $line = $beginning_spaces;

    while ($i < count($words)) {
        /* Force one word to be on a line (minimum) */
        $line .= $words[$i];
        $line_len = strlen($beginning_spaces) + sq_strlen($words[$i],$charset) + 2;
        if (isset($words[$i + 1]))
            $line_len += sq_strlen($words[$i + 1],$charset);
        $i ++;

        /* Add more words (as long as they fit) */
        while ($line_len < $wrap && $i < count($words)) {
            $line .= ' ' . $words[$i];
            $i++;
            if (isset($words[$i]))
                $line_len += sq_strlen($words[$i],$charset) + 1;
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
 * @param string $body the text to un-wordwrap
 * @return void
 * @since 1.0
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
 * @since 1.0
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
 * get_location
 *
 * Determines the location to forward to, relative to your server.
 * This is used in HTTP Location: redirects.
 *
 * If set, it uses $config_location_base as the first part of the URL,
 * specifically, the protocol, hostname and port parts. The path is
 * always autodetected.
 *
 * @return string the base url for this SquirrelMail installation
 * @since 1.0
 */
function get_location () {

    global $imap_server_type, $config_location_base,
           $is_secure_connection, $sq_ignore_http_x_forwarded_headers;

    /* Get the path, handle virtual directories */
    if(strpos(php_self(), '?')) {
        $path = substr(php_self(), 0, strpos(php_self(), '?'));
    } else {
        $path = php_self();
    }
    $path = substr($path, 0, strrpos($path, '/'));

    // proto+host+port are already set in config:
    if ( !empty($config_location_base) ) {
        return $config_location_base . $path ;
    }
    // we computed it before, get it from the session:
    if ( sqgetGlobalVar('sq_base_url', $full_url, SQ_SESSION) ) {
      return $full_url . $path;
    }
    // else: autodetect

    /* Check if this is a HTTPS or regular HTTP request. */
    $proto = 'http://';
    if ($is_secure_connection)
        $proto = 'https://';

    /* Get the hostname from the Host header or server config. */
    if ($sq_ignore_http_x_forwarded_headers
     || !sqgetGlobalVar('HTTP_X_FORWARDED_HOST', $host, SQ_SERVER)
     || empty($host)) {
        if ( !sqgetGlobalVar('HTTP_HOST', $host, SQ_SERVER) || empty($host) ) {
            if ( !sqgetGlobalVar('SERVER_NAME', $host, SQ_SERVER) || empty($host) ) {
                $host = '';
            }
        }
    }

    $port = '';
    if (! strstr($host, ':')) {
        // Note: HTTP_X_FORWARDED_PROTO could be sent from the client and
        //       therefore possibly spoofed/hackable.  Thus, SquirrelMail
        //       ignores such headers by default.  The administrator
        //       can tell SM to use such header values by setting
        //       $sq_ignore_http_x_forwarded_headers to boolean FALSE
        //       in config/config.php or by using config/conf.pl.
        global $sq_ignore_http_x_forwarded_headers;
        if ($sq_ignore_http_x_forwarded_headers
         || !sqgetGlobalVar('HTTP_X_FORWARDED_PROTO', $forwarded_proto, SQ_SERVER))
            $forwarded_proto = '';
        if (sqgetGlobalVar('SERVER_PORT', $server_port, SQ_SERVER)) {
            if (($server_port != 80 && $proto == 'http://') ||
                ($server_port != 443 && $proto == 'https://' &&
                 strcasecmp($forwarded_proto, 'https') !== 0)) {
                $port = sprintf(':%d', $server_port);
            }
        }
    }

    /* this is a workaround for the weird macosx caching that
     * causes Apache to return 16080 as the port number, which causes
     * SM to bail */

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
 * Get Message List URI
 *
 * @param string $mailbox      Current mailbox name (unencoded/raw)
 * @param string $startMessage The mailbox page offset
 * @param string $what         Any current search parameters (OPTIONAL; 
 *                             default empty string)
 *
 * @return string The message list URI
 *
 * @since 1.5.2
 *
 */
function get_message_list_uri($mailbox, $startMessage, $what='') {

    global $base_uri;

    $urlMailbox = urlencode($mailbox);

    $list_xtra = "?where=read_body.php&what=$what&mailbox=" . $urlMailbox.
                 "&startMessage=$startMessage";

    return $base_uri .'src/right_main.php'. $list_xtra;
}


/**
 * Encrypts password
 *
 * These functions are used to encrypt the password before it is
 * stored in a cookie. The encryption key is generated by
 * OneTimePadCreate();
 *
 * @param string $string the (password)string to encrypt
 * @param string $epad the encryption key
 * @return string the base64-encoded encrypted password
 * @since 1.0
 */
function OneTimePadEncrypt ($string, $epad) {
    $pad = base64_decode($epad);

    if (strlen($pad)>0) {
        // make sure that pad is longer than string
        while (strlen($string)>strlen($pad)) {
            $pad.=$pad;
        }
    } else {
        // FIXME: what should we do when $epad is not base64 encoded or empty.
    }

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
 * @param string $string the string to decrypt
 * @param string $epad the encryption key from the session
 * @return string the decrypted password
 * @since 1.0
 */
function OneTimePadDecrypt ($string, $epad) {
    $pad = base64_decode($epad);

    if (strlen($pad)>0) {
        // make sure that pad is longer than string
        while (strlen($string)>strlen($pad)) {
            $pad.=$pad;
        }
    } else {
        // FIXME: what should we do when $epad is not base64 encoded or empty.
    }

    $encrypted = base64_decode ($string);
    $decrypted = '';
    for ($i = 0; $i < strlen ($encrypted); $i++) {
        $decrypted .= chr (ord($encrypted[$i]) ^ ord($pad[$i]));
    }

    return $decrypted;
}

/**
 * Creates encryption key
 *
 * Creates an encryption key for encrypting the password stored in the cookie.
 * The encryption key itself is stored in the session.
 *
 * Pad must be longer or equal to encoded string length in 1.4.4/1.5.0 and older.
 * @param int $length optional, length of the string to generate
 * @return string the encryption key
 * @since 1.0
 */
function OneTimePadCreate ($length=100) {
    $pad = '';
    for ($i = 0; $i < $length; $i++) {
        $pad .= chr(mt_rand(0,255));
    }

    return base64_encode($pad);
}

/**
  * Returns a string showing a byte size figure in
  * a more easily digested (readable) format
  *
  * @param int $bytes the size in bytes
  *
  * @return string The size in human readable format
  *
  * @since 1.0
  *
  */
function show_readable_size($bytes) {
    $bytes /= 1024;
    $type = _("KiB");

    if ($bytes / 1024 > 1) {
        $bytes /= 1024;
        $type = _("MiB");
    }

    if ($bytes < 10) {
        $bytes *= 10;
        settype($bytes, 'integer');
        $bytes /= 10;
    } else {
        settype($bytes, 'integer');
    }

    global $nbsp;
    return $bytes . $nbsp . $type;
}

/**
 * Generates a random string from the character set you pass in
 *
 * @param int $size the length of the string to generate
 * @param string $chars a string containing the characters to use
 * @param int $flags a flag to add a specific set to the characters to use:
 *     Flags:
 *       1 = add lowercase a-z to $chars
 *       2 = add uppercase A-Z to $chars
 *       4 = add numbers 0-9 to $chars
 * @return string the random string
 * @since 1.0
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
 * @param string $str the string to escape
 * @return string the escaped string
 * @since 1.0.3
 */
function quoteimap($str) {
    return str_replace(array('\\', '"'), array('\\\\', '\\"'), $str);
}

/**
 * Create compose link
 *
 * Returns a link to the compose-page, taking in consideration
 * the compose_in_new and javascript settings.
 *
 * @param string $url       The URL to the compose page
 * @param string $text      The link text, default "Compose"
 * @param string $target    URL target, if any (since 1.4.3)
 * @param string $accesskey The access key to be used, if any
 *
 * @return string a link to the compose page
 *
 * @since 1.4.2
 */
function makeComposeLink($url, $text = null, $target='', $accesskey='NONE') {
    global $compose_new_win, $compose_width, 
           $compose_height, $oTemplate;

    if(!$text) {
        $text = _("Compose");
    }

    // if not using "compose in new window", make
    // regular link and be done with it
    if($compose_new_win != '1') {
        return makeInternalLink($url, $text, $target, $accesskey);
    }

    // build the compose in new window link...


    // if javascript is on, use onclick event to handle it
    if(checkForJavascript()) {
        sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);
        $compuri = SM_BASE_URI.$url;

        return create_hyperlink('javascript:void(0)', $text, '',
                                "comp_in_new('$compuri','$compose_width','$compose_height')",
                                '', '', '',
                                ($accesskey == 'NONE'
                                ? array()
                                : array('accesskey' => $accesskey)));
    }

    // otherwise, just open new window using regular HTML
    return makeInternalLink($url, $text, '_blank', $accesskey);
}

/**
 * version of fwrite which checks for failure
 * @param resource $fp
 * @param string $string
 * @return number of written bytes. false on failure
 * @since 1.4.3
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
 *              <ul>
 *                <li>ENT_COMPAT - (default) encode double quotes</li>
 *                <li>ENT_NOQUOTES -  don't encode double or single quotes</li>
 *                <li>ENT_QUOTES - encode double and single quotes</li>
 *              </ul>
 * @param string $charset charset used for encoding. default to us-ascii, 'auto' uses $default_charset global value.
 * @return array html translation array
 * @since 1.5.1
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
 * Minimal php requirement - v.4.0.5.
 *
 * Function is designed for people that want to use full power of htmlentities() in
 * i18n environment.
 *
 * @param string $string string that has to be sanitized
 * @param integer $quote_style quote encoding style. Possible values (without quotes):
 *              <ul>
 *                <li>ENT_COMPAT - (default) encode double quotes</li>
 *                <li>ENT_NOQUOTES - don't encode double or single quotes</li>
 *                <li>ENT_QUOTES - encode double and single quotes</li>
 *              </ul>
 * @param string $charset charset used for encoding. defaults to 'us-ascii', 'auto' uses $default_charset global value.
 * @return string sanitized string
 * @since 1.5.1
 */
function sq_htmlentities($string,$quote_style=ENT_COMPAT,$charset='us-ascii') {
  // get translation table
  $sq_html_ent_table=sq_get_html_translation_table(HTML_ENTITIES,$quote_style,$charset);
  // convert characters
  return str_replace(array_keys($sq_html_ent_table),array_values($sq_html_ent_table),$string);
}

/**
 * Tests if string contains 8bit symbols.
 *
 * If charset is not set, function defaults to default_charset.
 * $default_charset global must be set correctly if $charset is
 * not used.
 * @param string $string tested string
 * @param string $charset charset used in a string
 * @return bool true if 8bit symbols are detected
 * @since 1.5.1 and 1.4.4
 */
function sq_is8bit($string,$charset='') {
    global $default_charset;

    if ($charset=='') $charset=$default_charset;

    /**
     * Don't use \240 in ranges. Sometimes RH 7.2 doesn't like it.
     * Don't use \200-\237 for iso-8859-x charsets. This range
     * stores control symbols in those charsets.
     * Use preg_match instead of ereg in order to avoid problems
     * with mbstring overloading
     */
    if (preg_match("/^iso-8859/i",$charset)) {
        $needle='/\240|[\241-\377]/';
    } else {
        $needle='/[\200-\237]|\240|[\241-\377]/';
    }
    return preg_match("$needle",$string);
}

/**
 * Replacement of mb_list_encodings function
 *
 * This function provides replacement for function that is available only
 * in php 5.x. Function does not test all mbstring encodings. Only the ones
 * that might be used in SM translations.
 *
 * Supported strings are stored in session in order to reduce number of
 * mb_internal_encoding function calls.
 *
 * If you want to test all mbstring encodings - fill $list_of_encodings
 * array.
 * @return array list of encodings supported by php mbstring extension
 * @since 1.5.1 and 1.4.6
 */
function sq_mb_list_encodings() {
    if (! function_exists('mb_internal_encoding'))
        return array();

    // php 5+ function
    if (function_exists('mb_list_encodings')) {
        $ret = mb_list_encodings();
        array_walk($ret,'sq_lowercase_array_vals');
        return $ret;
    }

    // don't try to test encodings, if they are already stored in session
    if (sqgetGlobalVar('mb_supported_encodings',$mb_supported_encodings,SQ_SESSION))
        return $mb_supported_encodings;

    // save original encoding
    $orig_encoding=mb_internal_encoding();

    $list_of_encoding=array(
        'pass',
        'auto',
        'ascii',
        'jis',
        'utf-8',
        'sjis',
        'euc-jp',
        'iso-8859-1',
        'iso-8859-2',
        'iso-8859-7',
        'iso-8859-9',
        'iso-8859-15',
        'koi8-r',
        'koi8-u',
        'big5',
        'gb2312',
        'gb18030',
        'windows-1251',
        'windows-1255',
        'windows-1256',
        'tis-620',
        'iso-2022-jp',
        'euc-cn',
        'euc-kr',
        'euc-tw',
        'uhc',
        'utf7-imap');

    $supported_encodings=array();

    foreach ($list_of_encoding as $encoding) {
        // try setting encodings. suppress warning messages
        if (@mb_internal_encoding($encoding))
            $supported_encodings[]=$encoding;
    }

    // restore original encoding
    mb_internal_encoding($orig_encoding);

    // register list in session
    sqsession_register($supported_encodings,'mb_supported_encodings');

    return $supported_encodings;
}

/**
 * Callback function used to lowercase array values.
 * @param string $val array value
 * @param mixed $key array key
 * @since 1.5.1 and 1.4.6
 */
function sq_lowercase_array_vals(&$val,$key) {
    $val = strtolower($val);
}


/**
 * Function returns number of characters in string.
 *
 * Returned number might be different from number of bytes in string,
 * if $charset is multibyte charset. Detection depends on mbstring
 * functions. If mbstring does not support tested multibyte charset,
 * vanilla string length function is used.
 * @param string $str string
 * @param string $charset charset
 * @since 1.5.1 and 1.4.6
 * @return integer number of characters in string
 */
function sq_strlen($str, $charset=null){
    // default option
    if (is_null($charset)) return strlen($str);

    // lowercase charset name
    $charset=strtolower($charset);

    // use automatic charset detection, if function call asks for it
    if ($charset=='auto') {
        global $default_charset, $squirrelmail_language;
        set_my_charset();
        $charset=$default_charset;
        if ($squirrelmail_language=='ja_JP') $charset='euc-jp';
    }

    // Use mbstring only with listed charsets
    $aList_of_mb_charsets=array('utf-8','big5','gb2312','gb18030','euc-jp','euc-cn','euc-tw','euc-kr');

    // calculate string length according to charset
    if (in_array($charset,$aList_of_mb_charsets) && in_array($charset,sq_mb_list_encodings())) {
        $real_length = mb_strlen($str,$charset);
    } else {
        // own strlen detection code is removed because missing strpos,
        // strtoupper and substr implementations break string wrapping.
        $real_length=strlen($str);
    }
    return $real_length;
}

/**
 * string padding with multibyte support
 *
 * @link http://www.php.net/str_pad
 * @param string $string original string
 * @param integer $width padded string width
 * @param string $pad padding symbols
 * @param integer $padtype padding type
 *  (internal php defines, see str_pad() description)
 * @param string $charset charset used in original string
 * @return string padded string
 */
function sq_str_pad($string, $width, $pad, $padtype, $charset='') {

    $charset = strtolower($charset);
    $padded_string = '';

    switch ($charset) {
    case 'utf-8':
    case 'big5':
    case 'gb2312':
    case 'euc-kr':
        /*
         * all multibyte charsets try to increase width value by
         * adding difference between number of bytes and real length
         */
        $width = $width - sq_strlen($string,$charset) + strlen($string);
    default:
        $padded_string=str_pad($string,$width,$pad,$padtype);
    }
    return $padded_string;
}

/**
 * Wrapper that is used to switch between vanilla and multibyte substr
 * functions.
 * @param string $string
 * @param integer $start
 * @param integer $length
 * @param string $charset
 * @return string
 * @since 1.5.1
 * @link http://www.php.net/substr
 * @link http://www.php.net/mb_substr
 */
function sq_substr($string,$start,$length=NULL,$charset='auto') {

    // if $length is NULL, use the full string length...
    // we have to do this to mimick the use of substr()
    // where $length is not given
    //
    if (is_null($length))
        $length = sq_strlen($length);


    // use automatic charset detection, if function call asks for it
    static $charset_auto, $bUse_mb;

    if ($charset=='auto') {
        if (!isset($charset_auto)) {
            global $default_charset, $squirrelmail_language;
            set_my_charset();
            $charset=$default_charset;
            if ($squirrelmail_language=='ja_JP') $charset='euc-jp';
            $charset_auto = $charset;
        } else {
            $charset = $charset_auto;
        }
    }
    $charset = strtolower($charset);

    // in_array call is expensive => do it once and use a static var for
    // storing the results
    if (!isset($bUse_mb)) {
        if (in_array($charset,sq_mb_list_encodings())) {
            $bUse_mb = true;
        } else {
            $bUse_mb = false;
        }
    }

    if ($bUse_mb) {
        return mb_substr($string,$start,$length,$charset);
    }
    // TODO: add mbstring independent code

    // use vanilla string functions as last option
    return substr($string,$start,$length);
}

/**
  * This is a replacement for PHP's substr_replace() that is
  * multibyte-aware.
  *
  * @param string $string      The string to operate upon
  * @param string $replacement The string to be inserted
  * @param int    $start       The offset at which to begin substring replacement
  * @param int    $length      The number of characters after $start to remove
  *                            NOTE that if you need to specify a charset but
  *                            want to achieve normal substr_replace() behavior
  *                            where $length is not specified, use NULL (OPTIONAL;
  *                            default from $start to end of string)
  * @param string $charset     The charset of the given string.  A value of NULL
  *                            here will force the use of PHP's standard substr().
  *                            (OPTIONAL; default is "auto", which indicates that
  *                            the user's current charset should be used).
  *
  * @return string The manipulated string
  *
  * Of course, you can use more advanced (e.g., negative) values
  * for $start and $length as needed - see the PHP manual for more
  * information:  http://www.php.net/manual/function.substr-replace.php
  *
  */
function sq_substr_replace($string, $replacement, $start, $length=NULL,
                           $charset='auto')
{

   // NULL charset?  Just use substr_replace()
   //
   if (is_null($charset))
      return is_null($length) ? substr_replace($string, $replacement, $start)
                              : substr_replace($string, $replacement, $start, $length);


   // use current character set?
   //
   if ($charset == 'auto')
   {
//FIXME: is there any reason why this cannot be a global flag used by all string wrapper functions?
      static $auto_charset;
      if (!isset($auto_charset))
      {
         global $default_charset;
//FIXME - do we need this?
global $squirrelmail_language;
         set_my_charset();
         $auto_charset = $default_charset;
//FIXME - do we need this?
if ($squirrelmail_language == 'ja_JP') $auto_charset = 'euc-jp';
      }
      $charset = $auto_charset;
   }


   // standardize character set name
   //
   $charset = strtolower($charset);


/* ===== FIXME: this list is not used in 1.5.x, but if we need it, unless this differs between all our string function wrappers, we should store this info in the session
   // only use mbstring with the following character sets
   //
   $sq_substr_replace_mb_charsets = array(
      'utf-8',
      'big5',
      'gb2312',
      'gb18030',
      'euc-jp',
      'euc-cn',
      'euc-tw',
      'euc-kr'
   );


   // now we can use our own implementation using
   // mb_substr() and mb_strlen() if needed
   //
   if (in_array($charset, $sq_substr_replace_mb_charsets)
    && in_array($charset, sq_mb_list_encodings()))
===== */
//FIXME: is there any reason why this cannot be a global array used by all string wrapper functions?
   if (in_array($charset, sq_mb_list_encodings()))
   {

      $string_length = mb_strlen($string, $charset);

      if ($start < 0)
         $start = max(0, $string_length + $start);

      else if ($start > $string_length)
         $start = $string_length;

      if ($length < 0)
         $length = max(0, $string_length - $start + $length);

      else if (is_null($length) || $length > $string_length)
         $length = $string_length;

      if ($start + $length > $string_length)
         $length = $string_length - $start;

      return mb_substr($string, 0, $start, $charset)
           . $replacement
           . mb_substr($string,
                       $start + $length,
                       $string_length, // FIXME: I can't see why this is needed:  - $start - $length,
                       $charset);

   }


   // else use normal substr_replace()
   //
   return is_null($length) ? substr_replace($string, $replacement, $start)
                           : substr_replace($string, $replacement, $start, $length);

}

/**
 * Wrapper that is used to switch between vanilla and multibyte strpos
 * functions.
 * @param string $haystack
 * @param mixed $needle
 * @param integer $offset
 * @param string $charset
 * @return string
 * @since 1.5.1
 * @link http://www.php.net/strpos
 * @link http://www.php.net/mb_strpos
 */
function sq_strpos($haystack,$needle,$offset,$charset='auto') {
    // use automatic charset detection, if function call asks for it
    static $charset_auto, $bUse_mb;

    if ($charset=='auto') {
        if (!isset($charset_auto)) {
            global $default_charset, $squirrelmail_language;
            set_my_charset();
            $charset=$default_charset;
            if ($squirrelmail_language=='ja_JP') $charset='euc-jp';
            $charset_auto = $charset;
        } else {
            $charset = $charset_auto;
        }
    }
    $charset = strtolower($charset);

    // in_array call is expensive => do it once and use a static var for
    // storing the results
    if (!isset($bUse_mb)) {
        if (in_array($charset,sq_mb_list_encodings())) {
            $bUse_mb = true;
        } else {
            $bUse_mb = false;
        }
    }
    if ($bUse_mb) {
        return mb_strpos($haystack,$needle,$offset,$charset);
    }
    // TODO: add mbstring independent code

    // use vanilla string functions as last option
    return strpos($haystack,$needle,$offset);
}

/**
 * Wrapper that is used to switch between vanilla and multibyte strtoupper
 * functions.
 * @param string $string
 * @param string $charset
 * @return string
 * @since 1.5.1
 * @link http://www.php.net/strtoupper
 * @link http://www.php.net/mb_strtoupper
 */
function sq_strtoupper($string,$charset='auto') {
    // use automatic charset detection, if function call asks for it
    static $charset_auto, $bUse_mb;

    if ($charset=='auto') {
        if (!isset($charset_auto)) {
            global $default_charset, $squirrelmail_language;
            set_my_charset();
            $charset=$default_charset;
            if ($squirrelmail_language=='ja_JP') $charset='euc-jp';
            $charset_auto = $charset;
        } else {
            $charset = $charset_auto;
        }
    }
    $charset = strtolower($charset);

    // in_array call is expensive => do it once and use a static var for
    // storing the results
    if (!isset($bUse_mb)) {
        if (function_exists('mb_strtoupper') &&
            in_array($charset,sq_mb_list_encodings())) {
            $bUse_mb = true;
        } else {
            $bUse_mb = false;
        }
    }

    if ($bUse_mb) {
        return mb_strtoupper($string,$charset);
    }
    // TODO: add mbstring independent code

    // use vanilla string functions as last option
    return strtoupper($string);
}

/**
 * Counts 8bit bytes in string
 * @param string $string tested string
 * @return integer number of 8bit bytes
 */
function sq_count8bit($string) {
    $count=0;
    for ($i=0; $i<strlen($string); $i++) {
        if (ord($string[$i]) > 127) $count++;
    }
    return $count;
}

/**
 * Callback function to trim whitespace from a value, to be used in array_walk
 * @param string $value value to trim
 * @since 1.5.2 and 1.4.7
 */
function sq_trim_value ( &$value ) {
    $value = trim($value);
}

/**
  * Truncates the given string so that it has at
  * most $max_chars characters.  NOTE that a "character"
  * may be a multibyte character, or (optionally), an
  * HTML entity , so this function is different than
  * using substr() or mb_substr().
  *
  * NOTE that if $elipses is given and used, the returned
  *      number of characters will be $max_chars PLUS the
  *      length of $elipses
  *
  * @param string  $string    The string to truncate
  * @param int     $max_chars The maximum allowable characters
  * @param string  $elipses   A string that will be added to
  *                           the end of the truncated string
  *                           (ONLY if it is truncated) (OPTIONAL;
  *                           default not used)
  * @param boolean $html_entities_as_chars Whether or not to keep
  *                                        HTML entities together
  *                                        (OPTIONAL; default ignore
  *                                        HTML entities)
  *
  * @return string The truncated string
  *
  * @since 1.4.20 and 1.5.2 (replaced truncateWithEntities())
  *
  */
function sm_truncate_string($string, $max_chars, $elipses='',
                            $html_entities_as_chars=FALSE)
{

   // if the length of the string is less than
   // the allowable number of characters, just
   // return it as is (even if it contains any
   // HTML entities, that would just make the
   // actual length even smaller)
   //
   $actual_strlen = sq_strlen($string, 'auto');
   if ($max_chars <= 0 || $actual_strlen <= $max_chars)
      return $string;


   // if needed, count the number of HTML entities in
   // the string up to the maximum character limit,
   // pushing that limit up for each entity found
   //
   $adjusted_max_chars = $max_chars;
   if ($html_entities_as_chars)
   {

      // $loop_count is needed to prevent an endless loop
      // which is caused by buggy mbstring versions that
      // return 0 (zero) instead of FALSE in some rare
      // cases.  Thanks, PHP.
      // see: http://bugs.php.net/bug.php?id=52731
      // also: tracker $3053349
      //
      $loop_count = 0;
      $entity_pos = $entity_end_pos = -1;
      while ($entity_end_pos + 1 < $actual_strlen
          && ($entity_pos = sq_strpos($string, '&', $entity_end_pos + 1)) !== FALSE
          && ($entity_end_pos = sq_strpos($string, ';', $entity_pos)) !== FALSE
          && $entity_pos <= $adjusted_max_chars
          && $loop_count++ < $max_chars)
      {
         $adjusted_max_chars += $entity_end_pos - $entity_pos;
      }


      // this isn't necessary because sq_substr() would figure this
      // out anyway, but we can avoid a sq_substr() call and we
      // know that we don't have to add an elipses (this is now
      // an accurate comparison, since $adjusted_max_chars, like
      // $actual_strlen, does not take into account HTML entities)
      //
      if ($actual_strlen <= $adjusted_max_chars)
         return $string;

   }


   // get the truncated string
   //
   $truncated_string = sq_substr($string, 0, $adjusted_max_chars);


   // return with added elipses
   //
   return $truncated_string . $elipses;

}

/**
  * Gathers the list of secuirty tokens currently
  * stored in the user's preferences and optionally
  * purges old ones from the list.
  *
  * @param boolean $purge_old Indicates if old tokens
  *                           should be purged from the
  *                           list ("old" is 2 days or
  *                           older unless the administrator
  *                           overrides that value using
  *                           $max_token_age_days in
  *                           config/config_local.php)
  *                           (OPTIONAL; default is to always
  *                           purge old tokens)
  *
  * @return array The list of tokens
  *
  * @since 1.4.19 and 1.5.2
  *
  */
function sm_get_user_security_tokens($purge_old=TRUE)
{

   global $data_dir, $username, $max_token_age_days;

   $tokens = getPref($data_dir, $username, 'security_tokens', '');
   if (($tokens = unserialize($tokens)) === FALSE || !is_array($tokens))
      $tokens = array();

   // purge old tokens if necessary
   //
   if ($purge_old)
   {
      if (empty($max_token_age_days)) $max_token_age_days = 2;
      $now = time();
      $discard_token_date = $now - ($max_token_age_days * 86400);
      $cleaned_tokens = array();
      foreach ($tokens as $token => $timestamp)
         if ($timestamp >= $discard_token_date)
            $cleaned_tokens[$token] = $timestamp;
      $tokens = $cleaned_tokens;
   }

   return $tokens;

}

/**
  * Generates a security token that is then stored in
  * the user's preferences with a timestamp for later
  * verification/use.
  *
  * NOTE: The administrator can force SquirrelMail to generate
  * a new token every time one is requested (which may increase
  * obscurity through token randomness at the cost of some
  * performance) by adding the following to
  * config/config_local.php:   $do_not_use_single_token = TRUE;
  * Otherwise, only one token will be generated per user which
  * will change only after it expires or is used outside of the
  * validity period specified when calling sm_validate_security_token()
  *
  * WARNING: If the administrator has turned the token system
  *          off by setting $disable_security_tokens to TRUE in
  *          config/config.php or the configuration tool, this
  *          function will not store tokens in the user
  *          preferences (but it will still generate and return
  *          a random string).
  *
  * @param boolean $force_generate_new When TRUE, a new token will
  *                                    always be created even if current
  *                                    configuration dictates otherwise
  *                                    (OPTIONAL; default FALSE)
  *
  * @return string A security token
  *
  * @since 1.4.19 and 1.5.2
  *
  */
function sm_generate_security_token($force_generate_new=FALSE)
{

   global $data_dir, $username, $disable_security_tokens, $do_not_use_single_token;
   $max_generation_tries = 1000;

   $tokens = sm_get_user_security_tokens();

   if (!$force_generate_new && !$do_not_use_single_token && !empty($tokens))
      return key($tokens);

   $new_token = GenerateRandomString(12, '', 7);
   $count = 0;
   while (isset($tokens[$new_token]))
   {
      $new_token = GenerateRandomString(12, '', 7);
      if (++$count > $max_generation_tries)
      {
         logout_error(_("Fatal token generation error; please contact your system administrator or the SquirrelMail Team"));
         exit;
      }
   }

   // is the token system enabled?  CAREFUL!
   //
   if (!$disable_security_tokens)
   {
      $tokens[$new_token] = time();
      setPref($data_dir, $username, 'security_tokens', serialize($tokens));
   }

   return $new_token;

}

/**
  * Validates a given security token and optionally remove it
  * from the user's preferences if it was valid.  If the token
  * is too old but otherwise valid, it will still be rejected.
  *
  * "Too old" is 2 days or older unless the administrator
  * overrides that value using $max_token_age_days in
  * config/config_local.php
  *
  * WARNING: If the administrator has turned the token system
  *          off by setting $disable_security_tokens to TRUE in
  *          config/config.php or the configuration tool, this
  *          function will always return TRUE.
  *
  * @param string  $token           The token to validate
  * @param int     $validity_period The number of seconds tokens are valid
  *                                 for (set to zero to remove valid tokens
  *                                 after only one use; set to -1 to allow
  *                                 indefinite re-use (but still subject to
  *                                 $max_token_age_days - see elsewhere);
  *                                 use 3600 to allow tokens to be reused for
  *                                 an hour) (OPTIONAL; default is to only
  *                                 allow tokens to be used once)
  *                                 NOTE this is unrelated to $max_token_age_days
  *                                 or rather is an additional time constraint on
  *                                 tokens that allows them to be re-used (or not)
  *                                 within a more narrow timeframe
  * @param boolean $show_error      Indicates that if the token is not
  *                                 valid, this function should display
  *                                 a generic error, log the user out
  *                                 and exit - this function will never
  *                                 return in that case.
  *                                 (OPTIONAL; default FALSE)
  *
  * @return boolean TRUE if the token validated; FALSE otherwise
  *
  * @since 1.4.19 and 1.5.2
  *
  */
function sm_validate_security_token($token, $validity_period=0, $show_error=FALSE)
{

   global $data_dir, $username, $max_token_age_days,
          $disable_security_tokens;

   // bypass token validation?  CAREFUL!
   //
   if ($disable_security_tokens) return TRUE;

   // don't purge old tokens here because we already
   // do it when generating tokens
   //
   $tokens = sm_get_user_security_tokens(FALSE);

   // token not found?
   //
   if (empty($tokens[$token]))
   {
      if (!$show_error) return FALSE;
      logout_error(_("This page request could not be verified and appears to have expired."));
      exit;
   }

   $now = time();
   $timestamp = $tokens[$token];

   // whether valid or not, we want to remove it from
   // user prefs if it's old enough (unless requested to
   // bypass this (in which case $validity_period is -1))
   //
   if ($validity_period >= 0
    && $timestamp < $now - $validity_period)
   {
      unset($tokens[$token]);
      setPref($data_dir, $username, 'security_tokens', serialize($tokens));
   }

   // reject tokens that are too old
   //
   if (empty($max_token_age_days)) $max_token_age_days = 2;
   $old_token_date = $now - ($max_token_age_days * 86400);
   if ($timestamp < $old_token_date)
   {
      if (!$show_error) return FALSE;
      logout_error(_("The current page request appears to have originated from an untrusted source."));
      exit;
   }

   // token OK!
   //
   return TRUE;

}

/**
  * Wrapper for PHP's htmlspecialchars() that
  * attempts to add the correct character encoding
  *
  * @param string $string The string to be converted
  * @param int $flags A bitmask that controls the behavior of htmlspecialchars()
  *                   (See http://php.net/manual/function.htmlspecialchars.php )
  *                   (OPTIONAL; default ENT_COMPAT, ENT_COMPAT | ENT_SUBSTITUTE for PHP >=5.4)
  * @param string $encoding The character encoding to use in the conversion
  *                         (OPTIONAL; default automatic detection)
  * @param boolean $double_encode Whether or not to convert entities that are
  *                               already in the string (only supported in
  *                               PHP 5.2.3+) (OPTIONAL; default TRUE)
  *
  * @return string The converted text
  *
  */
function sm_encode_html_special_chars($string, $flags=ENT_COMPAT,
                                      $encoding=NULL, $double_encode=TRUE)
{
   if (!$encoding)
   {
      global $default_charset;
      if ($default_charset == 'iso-2022-jp')
         $default_charset = 'EUC-JP';
      $encoding = $default_charset;
   }

   if (check_php_version(5, 2, 3)) {
      // Replace invalid characters with a symbol instead of returning
      // empty string for the entire to be encoded string.
      if (check_php_version(5, 4, 0) && $flags == ENT_COMPAT) {
         $flags = $flags | ENT_SUBSTITUTE;
      }
      return htmlspecialchars($string, $flags, $encoding, $double_encode);
   }

   return htmlspecialchars($string, $flags, $encoding);
}

