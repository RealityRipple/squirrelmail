<?php

/**
 * event_delete.php
 *
 * Copyright (c) 2002-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * Functions to delete a event. 
 *
 * $Id$
 */
define('SM_PATH','../../');

/* Calender plugin required files. */
require_once(SM_PATH . 'plugins/calendar/calendar_data.php');
require_once(SM_PATH . 'plugins/calendar/functions.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/html.php');
/* get globals */
if (isset($_GET['month'])) {
    $month = $_GET['month'];
}
elseif (isset($_POST['month'])) {
    $month = $_POST['month'];
}
if (isset($_GET['year'])) {
    $year = $_GET['year'];
}
elseif (isset($_POST['year'])) {
    $year = $_POST['year'];
}
if (isset($_GET['day'])) {
    $day = $_GET['day'];
}
elseif (isset($_POST['day'])) {
    $day = $_POST['day'];
}
if (isset($_GET['dyear'])) {
    $dyear = $_GET['dyear'];
}
elseif (isset($_POST['dyear'])) {
    $dyear = $_POST['dyear'];
}
if (isset($_GET['dmonth'])) {
    $dmonth = $_GET['dmonth'];
}
elseif (isset($_POST['dmonth'])) {
    $dmonth = $_POST['dmonth'];
}
if (isset($_GET['dday'])) {
    $dday = $_GET['dday'];
}
elseif (isset($_POST['dday'])) {
    $dday = $_POST['dday'];
}
if (isset($_GET['dhour'])) {
    $dhour = $_GET['dhour'];
}
elseif (isset($_POST['dhour'])) {
    $dhour = $_POST['dhour'];
}
if (isset($_GET['dminute'])) {
    $dminute = $_GET['dminute'];
}
elseif (isset($_POST['dminute'])) {
    $dminute = $_POST['dminute'];
}
if (isset($_POST['confirmed'])) {
    $confirmed = $_POST['confirmed'];
}
/* got 'em */

function confirm_deletion()
{
    global $calself, $dyear, $dmonth, $dday, $dhour, $dminute, $calendardata, $color, $year, $month, $day;

    $tmparray = $calendardata["$dmonth$dday$dyear"]["$dhour$dminute"];

    echo html_tag( 'table',
               html_tag( 'tr',
                   html_tag( 'th', _("Do you really want to delete this event?") . '<br>', '', $color[4], 'colspan="2"' )
               ) .
               html_tag( 'tr',
                   html_tag( 'td', _("Date:"), 'right', $color[4] ) .
                   html_tag( 'td', $dmonth.'/'.$dday.'/'.$dyear, 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td', _("Time:"), 'right', $color[4] ) .
                   html_tag( 'td', $dhour.':'.$dminute, 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td', _("Title:"), 'right', $color[4] ) .
                   html_tag( 'td', $tmparray[title], 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td', _("Message:"), 'right', $color[4] ) .
                   html_tag( 'td', $tmparray[message], 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td',
                       "    <FORM NAME=\"delevent\" METHOD=POST ACTION=\"$calself\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"dyear\" VALUE=\"$dyear\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"dmonth\" VALUE=\"$dmonth\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"dday\" VALUE=\"$dday\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"year\" VALUE=\"$year\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"month\" VALUE=\"$month\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"day\" VALUE=\"$day\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"dhour\" VALUE=\"$dhour\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"dminute\" VALUE=\"$dminute\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"confirmed\" VALUE=\"yes\">\n".
                       '       <INPUT TYPE=SUBMIT VALUE="' . _("Yes") . "\">\n".
                       "    </FORM>\n" ,
                   'right', $color[4] ) .
                   html_tag( 'td',
                       "    <FORM NAME=\"nodelevent\" METHOD=POST ACTION=\"day.php\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"year\" VALUE=\"$year\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"month\" VALUE=\"$month\">\n".
                       "       <INPUT TYPE=HIDDEN NAME=\"day\" VALUE=\"$day\">\n".
                       '       <INPUT TYPE=SUBMIT VALUE="' . _("No") . "\">\n".
                       "    </FORM>\n" ,
                   'left', $color[4] )
               ) ,
           '', $color[0], 'border="0" cellpadding="2" cellspacing="1"' );
}

if ($month <= 0){
    $month = date( 'm' );
}
if ($year <= 0){
    $year = date( 'Y' );
}
if ($day <= 0){
    $day = date( 'd' );
}

$calself=basename($PHP_SELF);

displayPageHeader($color, 'None');
//load calendar menu
calendar_header();

echo html_tag( 'tr', '', '', $color[0] ) .
           html_tag( 'td' ) .
               html_tag( 'table', '', '', $color[0], 'width="100%" border="0" cellpadding="2" cellspacing="1"' ) .
                   html_tag( 'tr' ) .
                       html_tag( 'td', '', 'left' ) .
     date_intl( _("l, F j Y"), mktime(0, 0, 0, $month, $day, $year));
if (isset($dyear) && isset($dmonth) && isset($dday) && isset($dhour) && isset($dminute)){
    if (isset($confirmed)){
        delete_event("$dmonth$dday$dyear", "$dhour$dminute");
        echo '<br><br>' . _("Event deleted!") . "<br>\n";
        echo "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day\">" .
          _("Day View") . "</a>\n";
    } else {
        readcalendardata();
        confirm_deletion();
    }
} else {
    echo '<br>' . _("Nothing to delete!");
}

?>
</table></td></tr></table>
</body></html>
