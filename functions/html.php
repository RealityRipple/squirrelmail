<?php

/**
 * imap.php
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

    function html_tag( $tag,
                       $align = '', 
                       $xtra = '' ) {
			 
	GLOBAL $languages, $language;
	
	$align = strtolower( $align );
	$dir   = strtolower( $dir );
	
	if ( isset( $languages[$language]['DIR']) ) {
	    $dir = $languages[$language]['DIR'];
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
	
	switch ( $align ) {
	case '':
	    $alg = '';
	    break;
	case 'right':
	    $alg = " ALIGN=\"$rgt\"";
	    break;
	default:
	    $alg = " ALIGN=\"$lft\"";
	}
	
        return( "<$tag DIR=\"$dir\"$alg $xtra>" );
    }


?>
