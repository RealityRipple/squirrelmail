<?php

/**
 * url_parser.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the Squirrelmail code.
 *
 * $Id$
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
 * Here's pretty sophisticated IP matching:
 * $IPMatch = '(2[0-5][0-9]|1?[0-9]{1,2})';
 * $IPMatch = '\[?' . $IPMatch . '(\.' . $IPMatch . '){3}\]?';
 */
/* Here's enough: */
global $IP_RegExp_Match, $Host_RegExp_Match, $Email_RegExp_Match;
$IP_RegExp_Match = '\\[?[0-9]{1,3}(\\.[0-9]{1,3}){3}\\]?';
$Host_RegExp_Match = '(' . $IP_RegExp_Match .
    '|[0-9a-z]([-.]?[0-9a-z])*\\.[a-z][a-z]+)';
$Email_RegExp_Match = '[0-9a-z]([-_.+]?[0-9a-z])*(%' . $Host_RegExp_Match .
    ')?@' . $Host_RegExp_Match;

function parseEmail (&$body) {
    global $color, $Email_RegExp_Match, $compose_new_win;
    $Size = strlen($body);

    /*
     * This is here in case we ever decide to use highlighting of searched
     * text.  this does it for email addresses
     *
     * if ($what && ($where == "BODY" || $where == "TEXT")) {
     *    eregi ($Email_RegExp_Match, $body, $regs);
     *    $oldaddr = $regs[0];
     *    if ($oldaddr) {
     *       $newaddr = eregi_replace ($what, "<b><font color=\"$color[2]\">$what</font></font></b>", $oldaddr);
     *       $body = str_replace ($oldaddr, "<a href=\"../src/compose.php?send_to=$oldaddr\">$newaddr</a>", $body);
     *    }
     * } else {
     *    $body = eregi_replace ($Email_RegExp_Match, "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
     * }
     */

    if( eregi($Email_RegExp_Match, $body, $regs) ) {
        if ($compose_new_win == '1') {
            $body = str_replace($regs[0],  '<a href="../src/compose.php?send_to='.urlencode($regs[0]).'" target="compose_window" onClick="comp_in_new()">'.$regs[0].'</a>', $body);
        }
        else {
            $body = str_replace($regs[0],  '<a href="../src/compose.php?send_to='.
            urlencode($regs[0]).'">'.$regs[0].'</a>', $body);
        }
    }

    /* If there are any changes, it'll just get bigger. */
    if ($Size != strlen($body)) {
        return 1;
    }
    return 0;
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
    'gopher://',
    'news://');

global $url_parser_poss_ends;
$url_parser_poss_ends = array(' ', "\n", "\r", '<', '>', ".\r", ".\n", 
    '.&nbsp;', '&nbsp;', ')', '(', '&quot;', '&lt;', '&gt;', '.<', 
    ']', '[', '{', '}', "\240", ', ', '. ', ",\n", ",\r");


function parseUrl (&$body) {
    global $url_parser_poss_ends, $url_parser_url_tokens;;
    $start = 0;
    $target_pos = strlen($body);
      
    while ($start != $target_pos) {
        $target_token = '';
        
        /* Find the first token to replace */
        foreach ($url_parser_url_tokens as $the_token) {
            $pos = strpos(strtolower($body), $the_token, $start);
            if (is_int($pos) && $pos < $target_pos) {
                $target_pos = $pos;
                $target_token = $the_token;
            }
        }
        
        /* Look for email addresses between $start and $target_pos */
        $check_str = substr($body, $start, $target_pos);
       
        if (parseEmail($check_str)) {
            replaceBlock($body, $check_str, $start, $target_pos);
            $target_pos = strlen($check_str) + $start;
        }

        /* If there was a token to replace, replace it */
        if ($target_token != '') {
            /* Find the end of the URL */
            $end=strlen($body); 
            foreach ($url_parser_poss_ends as $key => $val) {
                $enda = strpos($body,$val,$target_pos);
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
        $start = $target_pos;
        $target_pos = strlen($body);
    }
} 
?>
