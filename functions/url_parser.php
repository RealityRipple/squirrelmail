<?php

/**
 * url_parser.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the Squirrelmail code.
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Undocumented - complain, then patch.
 */
function replaceBlock (&$in, $replace, $start, $end) {
    $begin = substr($in,0,$start);
    $end   = substr($in,$end,strlen($in)-$end);
    $in    = $begin.$replace.$end;
}

/* Having this defined in just one spot could help when changes need
 * to be made to the pattern
 * Make sure that the expression is evaluated case insensitively
 *
 * RFC2822 (and RFC822) defines the left side of an email address as (roughly):
 *  1*atext *("." 1*atext)
 * where atext is: a-zA-Z0-9!#$%&'*+-/=?^_`{|}~
 *
 * Here's pretty sophisticated IP matching:
 * $IPMatch = '(2[0-5][0-9]|1?[0-9]{1,2})';
 * $IPMatch = '\[?' . $IPMatch . '(\.' . $IPMatch . '){3}\]?';
 */
/* Here's enough: */
global $IP_RegExp_Match, $Host_RegExp_Match, $Email_RegExp_Match;
$IP_RegExp_Match = '\\[?[0-9]{1,3}(\\.[0-9]{1,3}){3}\\]?';
$Host_RegExp_Match = '(' . $IP_RegExp_Match .
    '|[0-9a-z]([-.]?[0-9a-z])*\\.[a-z][a-z]+)';
$atext = '([a-z0-9!#$&%*+/=?^_`{|}~-]|&amp;)';
$dot_atom = $atext . '+(\.' . $atext . '+)*';
$Email_RegExp_Match = $dot_atom . '(%' . $Host_RegExp_Match . ')?@' .
                      $Host_RegExp_Match;

/**
 * Parses a body and converts all found email addresses to clickable links.
 *
 * @param string body the body to process, by ref
 * @return int the number of unique addresses found
 */
function parseEmail (&$body) {
    global $Email_RegExp_Match;
    $sbody     = $body;
    $addresses = array();

    /* Find all the email addresses in the body */
    while(eregi($Email_RegExp_Match, $sbody, $regs)) {
        $addresses[$regs[0]] = strtr($regs[0], array('&amp;' => '&'));
        $start = strpos($sbody, $regs[0]) + strlen($regs[0]);
        $sbody = substr($sbody, $start);
    }

    /* Replace each email address with a compose URL */
    foreach ($addresses as $text => $email) {
        $comp_uri = makeComposeLink('src/compose.php?send_to='.urlencode($email), $text);
        $body = str_replace($text, $comp_uri, $body);
    }

    /* Return number of unique addresses found */
    return count($addresses);
}


/* We don't want to re-initialize this stuff for every line.  Save work
 * and just do it once here.
 */
global $url_parser_url_tokens;
$url_parser_url_tokens = array(
    'http://',
    'https://',
    'ftp://',
    'telnet:',  // Special case -- doesn't need the slashes
    'mailto:',  // Special case -- doesn't use the slashes
    'gopher://',
    'news://');

global $url_parser_poss_ends;
$url_parser_poss_ends = array(' ', "\n", "\r", '<', '>', ".\r", ".\n",
    '.&nbsp;', '&nbsp;', ')', '(', '&quot;', '&lt;', '&gt;', '.<',
    ']', '[', '{', '}', "\240", ', ', '. ', ",\n", ",\r");


/**
 * rfc 2368 (mailto URL) preg_match() regexp
 * @link http://www.ietf.org/rfc/rfc2368.txt
 * @global string MailTo_PReg_Match the encapsulated regexp for preg_match()
 */
global $MailTo_PReg_Match;
$Mailto_Email_RegExp = '[0-9a-z%]([-_.+%]?[0-9a-z])*(%' . $Host_RegExp_Match . ')?@' . $Host_RegExp_Match;
$MailTo_PReg_Match = '/((?:' . $Mailto_Email_RegExp . ')*)((?:\?(?:to|cc|bcc|subject|body)=[^\s\?&=,()]+)?(?:&amp;(?:to|cc|bcc|subject|body)=[^\s\?&=,()]+)*)/i';

/**
 * Parses a body and converts all found URLs to clickable links.
 *
 * @param string body the body to process, by ref
 * @return void
 */
function parseUrl (&$body) {
    global $url_parser_poss_ends, $url_parser_url_tokens;
    $start      = 0;
    $blength    = strlen($body);

    while ($start < $blength) {
        $target_token = '';
        $target_pos = $blength;

        /* Find the first token to replace */
        foreach ($url_parser_url_tokens as $the_token) {
            $pos = strpos(strtolower($body), $the_token, $start);
            if (is_int($pos) && $pos < $target_pos) {
                $target_pos   = $pos;
                $target_token = $the_token;
            }
        }

        /* Look for email addresses between $start and $target_pos */
        $check_str = substr($body, $start, $target_pos-$start);

        if (parseEmail($check_str)) {
            replaceBlock($body, $check_str, $start, $target_pos);
            $blength    = strlen($body);
            $target_pos = strlen($check_str) + $start;
        }

        /* If there was a token to replace, replace it */
        if ($target_token == 'mailto:') {    // rfc 2368 (mailto URL)
            $target_pos += 7;    //skip mailto:
            $end = $blength;

            $mailto = substr($body, $target_pos, $end-$target_pos);

            global $MailTo_PReg_Match;
            if ((preg_match($MailTo_PReg_Match, $mailto, $regs)) && ($regs[0] != '')) {
                //sm_print_r($regs);
                $mailto_before = $target_token . $regs[0];
                $mailto_params = $regs[10];
                if ($regs[1]) {    //if there is an email addr before '?', we need to merge it with the params
                    $to = 'to=' . $regs[1];
                    if (strpos($mailto_params, 'to=') > -1)    //already a 'to='
                        $mailto_params = str_replace('to=', $to . '%2C%20', $mailto_params);
                    else {
                        if ($mailto_params)    //already some params, append to them
                            $mailto_params .= '&amp;' . $to;
                        else
                            $mailto_params .= '?' . $to;
                    }
                }
                $url_str = str_replace(array('to=', 'cc=', 'bcc='), array('send_to=', 'send_to_cc=', 'send_to_bcc='), $mailto_params);
                $comp_uri = makeComposeLink('src/compose.php' . $url_str, $mailto_before);
                replaceBlock($body, $comp_uri, $target_pos - 7, $target_pos + strlen($regs[0]));
                $target_pos += strlen($comp_uri) - 7;
            }
        }
        else
        if ($target_token != '') {
            /* Find the end of the URL */
            $end = $blength;
            foreach ($url_parser_poss_ends as $val) {
                $enda = strpos($body, $val, $target_pos);
                if (is_int($enda) && $enda < $end) {
                    $end = $enda;
                }
            }

            /* Extract URL */
            $url = substr($body, $target_pos, $end-$target_pos);

            /* Needed since lines are not passed with \n or \r */
            while ( ereg("[,\.]$", $url) ) {
                $url = substr( $url, 0, -1 );
                $end--;
            }

            /* Replace URL with HyperLinked Url, requires 1 char in link */
            if ($url != '' && $url != $target_token) {
                $url_str = "<a href=\"$url\" target=\"_blank\">$url</a>";
                replaceBlock($body,$url_str,$target_pos,$end);
                $target_pos += strlen($url_str);
            }
            else {
                // Not quite a valid link, skip ahead to next chance
                $target_pos += strlen($target_token);
            }
        }

        /* Move forward */
        $start   = $target_pos;
        $blength = strlen($body);
    }
}

/**
 * Parses a string and returns the first e-mail address found.
 *
 * @param string string the string to process
 * @return string the first e-mail address found
 */
function getEmail($string) {
    global $Email_RegExp_Match;
    $addresses = array();

    /* Find all the email addresses in the body */
    while (eregi($Email_RegExp_Match, $string, $regs)) {
        $addresses[$regs[0]] = strtr($regs[0], array('&amp;' => '&'));
        $start = strpos($string, $regs[0]) + strlen($regs[0]);
        $string = substr($string, $start);
    }

    /* Return the first address, or an empty string if no address was found */
    $addresses = array_values($addresses);
    return (array_key_exists(0, $addresses) ? $addresses[0] : '');
}

?>