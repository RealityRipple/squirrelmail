<?php

/**
 * date.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Takes a date and parses it into a usable format.  The form that a
 * date SHOULD arrive in is:
 *       <Tue,> 29 Jun 1999 09:52:11 -0500 (EDT)
 * (as specified in RFC 822) -- 'Tue' is optional
 *
 * $Id$
 */

require_once( '../functions/constants.php' );

// corrects a time stamp to be the local time
function getGMTSeconds($stamp, $gmt) {
    global $invert_time;
    if (($gmt == 'Pacific') || ($gmt == 'PST')) {
        $gmt = '-0800'; 
    } else if (($gmt == 'EDT')) {
        $gmt = '-0400';
    } else if (($gmt == 'Eastern') || ($gmt == 'EST') || ($gmt == 'CDT')) {
        $gmt = '-0500';
    } else if (($gmt == 'Central') || ($gmt == 'CST') || ($gmt == 'MDT')) {
        $gmt = '-0600';
    } else if (($gmt == 'Mountain') || ($gmt == 'MST') || ($gmt == 'PDT')) {
        $gmt = '-0700';
    } else if ($gmt == 'BST') {
        $gmt = '+0100';
    } else if ($gmt == 'EET') {
        $gmt = '+0200';
    } else if ($gmt == 'GMT') {
        $gmt = '+0000';
    } else if ($gmt == 'HKT') {
        $gmt = '+0800';
    } else if ($gmt == 'IST') {
        $gmt = '+0200';
    } else if ($gmt == 'JST') {
        $gmt = '+0900';
    } else if ($gmt == 'KST') {
        $gmt = "+0900";
    } else if ($gmt == 'MET') {
        $gmt = '+0100';
    } else if ($gmt == 'MET DST' || $gmt == 'METDST') {
        $gmt = '+0200';
    }
    
    if (substr($gmt, 0, 1) == '-') {
        $neg = true;
        $gmt = substr($gmt, 1, strlen($gmt));
    } else if (substr($gmt, 0, 1) == '+') {
        $neg = false;
        $gmt = substr($gmt, 1, strlen($gmt));
    } else {
        $neg = false;
    }
     
    $difference = substr($gmt, 2, 2);
    $gmt = substr($gmt, 0, 2);
    $gmt = ($gmt + ($difference / 60)) * 3600;
    if ($neg == true) {
        $gmt = "-$gmt";
    } else {
        $gmt = "+$gmt";
    }
    
    /** now find what the server is at **/
    $current = date('Z', time());
    if ($invert_time) {
        $current = - $current;
    }
    $stamp = (int)$stamp - (int)$gmt + (int)$current;
    
    return $stamp;
}

/**
  Switch system has been intentionaly choosed for the
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
    
    if ( $hour_format == SMPREF_TIME_12HR ) {
        $date_format = _("D, F j, Y g:i a");
    } else {
        $date_format = _("D, F j, Y G:i");
    }
    
    return( date_intl( $date_format, $stamp ) );

}

function getDateString( $stamp ) {

    global $invert_time, $hour_format;
    
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
     *    Since the day of week is optional, this check is needed.
     *
     *    The old code used eregi('mon|tue|wed|thu|fri|sat|sun',
     *    $dateParts[0], $tmp) to find if the first element was the
     *    day of week or day of month. This is an expensive call
     *    (processing time) to have inside a loop. Doing it this way
     *    saves quite a bit of time for large mailboxes.
     *
     *    It is also quicker to call explode only once rather than
     *    the 3 times it was getting called by calling the functions
     *    getHour, getMinute, and getSecond.
     */

    if (! isset($dateParts[1])) {
        $dateParts[1] = '';
    }
    if (! isset($dateParts[2])) {
        $dateParts[2] = '';
    }
    if (! isset($dateParts[3])) {
        $dateParts[3] = '';
    }
    if (! isset($dateParts[4])) {
        $dateParts[4] = '';
    }
    if (! isset($dateParts[5])) {
        $dateParts[5] = '';
    }
    if (intval(trim($dateParts[0])) > 0) {
        $string = $dateParts[0] . ' ' . $dateParts[1] . ' ' .
                  $dateParts[2] . ' ' . $dateParts[3];
        return getGMTSeconds(strtotime($string), $dateParts[4]);
    }
    $string = $dateParts[0] . ' ' . $dateParts[1] . ' ' .
            $dateParts[2] . ' ' . $dateParts[3] . ' ' . $dateParts[4];
    if (isset($dateParts[5])) {
        return getGMTSeconds(strtotime($string), $dateParts[5]);
    } else {
        return getGMTSeconds(strtotime($string), '');
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
