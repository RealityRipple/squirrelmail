<?php

/**
 * html.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * The idea is to inlcude here some functions to make easier
 * the right to left implementation by "functionize" some
 * html outputs.
 *
 * $Id$
 */

    function html_tag( $tag,                // Tag to output
                       $val = '',           // Value between tags (if empty only start tag is issued)
                       $align = '',         // Alignment
                       $bgcolor = '',       // Back color
                       $xtra = '' ) {       // Extra options

        GLOBAL $languages, $squirrelmail_language;

        $align = strtolower( $align );
        $bgc = '';
        $tag = strtoupper( $tag );

        if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
        $dir = $languages[$squirrelmail_language]['DIR'];
        } else {
            $dir = 'ltr';
        }

        if ( $dir == 'ltr' ) {
            $rgt = 'right';
            $lft = 'left';
        } else {
            $rgt = 'left';
            $lft = 'right';
        }

        if ( $bgcolor <> '' ) {
            $bgc = " BGCOLOR=\"$bgcolor\""; 
        }

        switch ( $align ) {
            case '':
                $alg = '';
                break;
            case 'right':
                $alg = " ALIGN=\"$rgt\"";
                break;
            case 'left':
                $alg = " ALIGN=\"$lft\"";
                break;
            default:
                $alg = " ALIGN=\"$align\"";
                break;
        }

        $ret = "<$tag";

        if ( $dir <> 'ltr' ) {
            $ret .= " DIR=\"$dir\"";
        }
        $ret .= "$bgc$alg";

        if ( $xtra <> '' ) {
            $ret .= " $xtra";
        }
        $ret .= '>';

        if ( $val <> '' ) {
            $ret .= "$val</$tag>";
        }

        return( $ret );
    }

    /* handy function to set url vars */
    /* especially usefull when $url = $PHP_SELF */
    function set_url_var($url, $var, $val=0) {
        $k = '';
        $ret = '';
        $pat_a = array (
                       '/.+(\\&'.$var.')=(.*)\\&/AU',   /* in the middle */
                       '/.+\\?('.$var.')=(.*\\&).+/AU', /* at front, more follow */
                       '/.+(\\?'.$var.')=(.*)$/AU',     /* at front and only var */
                       '/.+(\\&'.$var.')=(.*)$/AU'      /* at the end */
                     );
        switch (true) {
            case (preg_match($pat_a[0],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[1],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[2],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            case (preg_match($pat_a[3],$url,$regs)):
                $k = $regs[1];
                $v = $regs[2];
                break;
            default:
                if ($val) {
                    if (strpos($url,'?')) {
                        $url .= "&amp;$var=$val";
                    } else {
                        $url .= "?$var=$val";
                    }
                }
                break;
        }

        if ($k) {
            if ($val) {
                $rpl = "$k=$val";
		$rpl = preg_replace('/&/','&amp;',$rpl);
            } else {
                $rpl = '';
            }
            $pat = "/$k=$v/";
            $url = preg_replace($pat,$rpl,$url);
        }
        return $url;
    }

    /* Temporary test function to proces template vars with formatting.
     * I use it for viewing the message_header (view_header.php) with
     * a sort of template.
     */
    function echo_template_var($var, $format_ar = array() ) {
        $frm_last = count($format_ar) -1;

        if (isset($format_ar[0])) echo $format_ar[0]; 
            $i = 1;

        switch (true) {
            case (is_string($var)):
                echo $var;
                break;
            case (is_array($var)):
                $frm_a = array_slice($format_ar,1,$frm_last-1);
                foreach ($var as $a_el) {
                    if (is_array($a_el)) {
                        echo_template_var($a_el,$frm_a);
                    } else {
                        echo $a_el;
                        if (isset($format_ar[$i])) {
                            echo $format_ar[$i];
                        }
                        $i++;
                    }
                }
                break;
            default:
                break;
        }
        if (isset($format_ar[$frm_last]) && $frm_last>$i ) {
            echo $format_ar[$frm_last];
        }
    }
?>
