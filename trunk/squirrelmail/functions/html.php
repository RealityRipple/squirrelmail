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
    	$dir   = strtolower( $dir );
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

?>