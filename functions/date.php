<?php

/**
 * date.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Takes a date and parses it into a usable format.  The form that a
 * date SHOULD arrive in is:
 *       <Tue,> 29 Jun 1999 09:52:11 -0500 (EDT)
 * (as specified in RFC 822) -- 'Tue' is optional
 *
 * $Id$
 */

require_once(SM_PATH . 'functions/constants.php');

/* corrects a time stamp to be the local time */
function getGMTSeconds($stamp, $tzc) {
    /* date couldn't be parsed */
    if ($stamp == -1) {
        return -1;
    }
    /* timezone correction, expressed as `shhmm' */
    switch($tzc)
    {
        case 'Pacific':
        case 'PST':
            $tzc = '-0800';
            break;
        case 'Mountain':   
        case 'MST':   
        case 'PDT':   
            $tzc = '-0700';     
            break;
        case 'Central':
        case 'CST':
        case 'MDT':   
            $tzc = '-0600';     
            break;
        case 'Eastern':
        case 'EST':
        case 'CDT':   
            $tzc = '-0500';     
            break;
        case 'EDT':
            $tzc = '-0400';
            break;
        case 'GMT':   
            $tzc = '+0000';     
            break;
        case 'BST':
        case 'MET':
        case 'CET':
            $tzc = '+0100';
            break;
        case 'EET':
        case 'IST':   
        case 'MET DST':   
        case 'METDST':   
            $tzc = '+0200';     
            break;
        case 'HKT':   
            $tzc = '+0800';     
            break;
        case 'JST':   
        case 'KST':
            $tzc = '+0900';     
            break;
    }
    $neg = false;
    if (substr($tzc, 0, 1) == '-') {
        $neg = true;
    } else if (substr($tzc, 0, 1) != '+') {
        $tzc = '+'.$tzc;
    }
    $hh = substr($tzc,1,2);
    $mm = substr($tzc,3,2);
    $iTzc = ($hh * 60 + $mm) * 60;
    if ($neg) $iTzc = -1 * (int) $iTzc; 
    /* stamp in gmt */
    $stamp -= $iTzc;
    /** now find what the server is at **/
    $current = date('Z', time());
    /* stamp in local timezone */
    $stamp += $current;
    
    return $stamp;
}

/**
  Switch system has been intentionaly chosen for the
  internationalization of month and day names. The reason
  is to make sure that _("") strings will go into the
  main po.
**/

function getDayName( $day_number ) {

    switch( $day_number ) {
    case 0:
        $ret = _("Sunday");
        break;
    case 1:
        $ret = _("Monday");
        break;
    case 2:
        $ret = _("Tuesday");
        break;
    case 3:
        $ret = _("Wednesday");
        break;
    case 4:
        $ret = _("Thursday");
        break;
    case 5:
        $ret = _("Friday");
        break;
    case 6:
        $ret = _("Saturday");
        break;
    default:
        $ret = '';
    }
    return( $ret );
}

function getMonthName( $month_number ) {
    switch( $month_number ) {
     case '01':
        $ret = _("January");
        break;
     case '02':
        $ret = _("February");
        break;
     case '03':
        $ret = _("March");
        break;
     case '04':
        $ret = _("April");
        break;
     case '05':
        $ret = _("May");
        break;
     case '06':
        $ret = _("June");
        break;
     case '07':
        $ret = _("July");
        break;
     case '08':
        $ret = _("August");
        break;
     case '09':
        $ret = _("September");
        break;
     case '10':
        $ret = _("October");
        break;
     case '11':
        $ret = _("November");
        break;
     case '12':
        $ret = _("December");
        break;
     default:
        $ret = '';
    }
    return( $ret );
}

function date_intl( $date_format, $stamp ) {

    $ret = str_replace( 'D', '$1', $date_format );
    $ret = str_replace( 'F', '$2', $ret );
    $ret = str_replace( 'l', '$4', $ret );
    $ret = str_replace( 'M', '$5', $ret );
    $ret = date( '$3'. $ret . '$3', $stamp ); // Workaround for a PHP 4.0.4 problem
    $ret = str_replace( '$1', substr( getDayName( date( 'w', $stamp ) ), 0, 3 ), $ret );
    $ret = str_replace( '$5', substr( getMonthName( date( 'm', $stamp ) ), 0, 3 ), $ret );    
    $ret = str_replace( '$2', getMonthName( date( 'm', $stamp ) ), $ret );
    $ret = str_replace( '$4', getDayName( date( 'w', $stamp ) ), $ret );
    $ret = str_replace( '$3', '', $ret );
    
    return( $ret );
}

function getLongDateString( $stamp ) {

    global $hour_format;
    
    if ($stamp == -1) {
        return '';
    }

    if ( $hour_format == SMPREF_TIME_12HR ) {
        $date_format = _("D, F j, Y g:i a");
    } else {
        $date_format = _("D, F j, Y G:i");
    }
    
    return( date_intl( $date_format, $stamp ) );

}

function getDateString( $stamp ) {

    global $invert_time, $hour_format;

    if ( $stamp == -1 ) {
       return '';
    }
    
    $now = time();
    
    $dateZ = date('Z', $now );
    if ($invert_time) {
        $dateZ = - $dateZ;
    }
    $midnight = $now - ($now % 86400) - $dateZ;
    
    if ($midnight < $stamp) {
        /* Today */
        if ( $hour_format == SMPREF_TIME_12HR ) {
            $date_format = _("g:i a");
        } else {
            $date_format = _("G:i");
        }
    } else if ($midnight - 518400 < $stamp) {
        /* This week */
        if ( $hour_format == SMPREF_TIME_12HR ) {
            $date_format = _("D, g:i a");
        } else {
            $date_format = _("D, G:i");
        }
    } else {
        /* before this week */
        $date_format = _("M j, Y");
    }
    
    return( date_intl( $date_format, $stamp ) );
}

function getTimeStamp($dateParts) {
    /** $dateParts[0] == <day of week>   Mon, Tue, Wed
    ** $dateParts[1] == <day of month>  23
    ** $dateParts[2] == <month>         Jan, Feb, Mar
    ** $dateParts[3] == <year>          1999
    ** $dateParts[4] == <time>          18:54:23 (HH:MM:SS)
    ** $dateParts[5] == <from GMT>      +0100
    ** $dateParts[6] == <zone>          (EDT)
    **
    ** NOTE:  In RFC 822, it states that <day of week> is optional.
    **        In that case, dateParts[0] would be the <day of month>
    **        and everything would be bumped up one.
    **/

    /* 
     * Simply check to see if the first element in the dateParts
     * array is an integer or not.
     * Since the day of week is optional, this check is needed.
     */
     if (count($dateParts) <2) {
        return 0;
     }

    /* remove day of week */
    if (!is_numeric(trim($dateParts[0]))) {
        $dataParts = array_shift($dateParts);
    }
    /* calculate timestamp separated from the zone and obs-zone */
    $stamp = strtotime(implode (' ', array_splice ($dateParts,0,4)));
    if (!isset($dateParts[0])) {
        $dateParts[0] = '+0000';
    }

    if (!preg_match('/^[+-]{1}[0-9]{4}$/',$dateParts[0])) {
        /* zone in obs-zone format */
        if (preg_match('/\((.+)\)/',$dateParts[0],$regs)) {
            $obs_zone = $regs[1];
        } else {
            $obs_zone = $dateParts[0];
        }
        return getGMTSeconds($stamp, $obs_zone);
    } else {
        return getGMTSeconds($stamp, $dateParts[0]);
    }
}

/* I use this function for profiling. Should never be called in
   actual versions of squirrelmail released to public. */
/*
   function getmicrotime() {
      $mtime = microtime();
      $mtime = explode(' ',$mtime);
      $mtime = $mtime[1] + $mtime[0];
      return ($mtime);
   }
*/
?>
