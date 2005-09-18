<?php

/**
 * event_delete.php
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * Functions to delete a event.
 *
 * @copyright &copy; 2002-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage calendar
 */

/**
 * @ignore
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
if (isset($_GET['month']) && is_numeric($_GET['month'])) {
    $month = $_GET['month'];
}
elseif (isset($_POST['month']) && is_numeric($_GET['month'])) {
    $month = $_POST['month'];
}
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $year = $_GET['year'];
}
elseif (isset($_POST['year']) && is_numeric($_POST['year'])) {
    $year = $_POST['year'];
}
if (isset($_GET['day']) && is_numeric($_GET['day'])) {
    $day = $_GET['day'];
}
elseif (isset($_POST['day']) && is_numeric($_POST['day'])) {
    $day = $_POST['day'];
}
if (isset($_GET['dyear']) && is_numeric($_GET['dyear'])) {
    $dyear = $_GET['dyear'];
}
elseif (isset($_POST['dyear']) && is_numeric($_POST['dyear'])) {
    $dyear = $_POST['dyear'];
}
if (isset($_GET['dmonth']) && is_numeric($_GET['dmonth'])) {
    $dmonth = $_GET['dmonth'];
}
elseif (isset($_POST['dmonth']) && is_numeric($_POST['dmonth'])) {
    $dmonth = $_POST['dmonth'];
}
if (isset($_GET['dday']) && is_numeric($_GET['dday'])) {
    $dday = $_GET['dday'];
}
elseif (isset($_POST['dday']) && is_numeric($_POST['dday'])) {
    $dday = $_POST['dday'];
}
if (isset($_GET['dhour']) && is_numeric($_GET['dhour'])) {
    $dhour = $_GET['dhour'];
}
elseif (isset($_POST['dhour']) && is_numeric($_POST['dhour'])) {
    $dhour = $_POST['dhour'];
}
if (isset($_GET['dminute']) && is_numeric($_GET['dminute'])) {
    $dminute = $_GET['dminute'];
}
elseif (isset($_POST['dminute']) && is_numeric($_POST['dminute'])) {
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
                   html_tag( 'th', _("Do you really want to delete this event?") . '<br />', '', $color[4], 'colspan="2"' )
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
                   html_tag( 'td', $tmparray['title'], 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td', _("Message:"), 'right', $color[4] ) .
                   html_tag( 'td', $tmparray['message'], 'left', $color[4] )
               ) .
               html_tag( 'tr',
                   html_tag( 'td',
                       "    <form name=\"delevent\" method=\"post\" action=\"$calself\">\n".
                       "       <input type=\"hidden\" name=\"dyear\" value=\"$dyear\" />\n".
                       "       <input type=\"hidden\" name=\"dmonth\" value=\"$dmonth\" />\n".
                       "       <input type=\"hidden\" name=\"dday\" value=\"$dday\" />\n".
                       "       <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
                       "       <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
                       "       <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
                       "       <input type=\"hidden\" name=\"dhour\" value=\"$dhour\" />\n".
                       "       <input type=\"hidden\" name=\"dminute\" value=\"$dminute\" />\n".
                       "       <input type=\"hidden\" name=\"confirmed\" value=\"yes\" />\n".
                       '       <input type="submit" value="' . _("Yes") . "\" />\n".
                       "    </form>\n" ,
                   'right', $color[4] ) .
                   html_tag( 'td',
                       "    <form name=\"nodelevent\" method=\"post\" action=\"day.php\">\n".
                       "       <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
                       "       <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
                       "       <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
                       '       <input type="submit" value="' . _("No") . "\" />\n".
                       "    </form>\n" ,
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
        echo '<br /><br />' . _("Event deleted!") . "<br />\n";
        echo "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day\">" .
          _("Day View") . "</a>\n";
    } else {
        readcalendardata();
        confirm_deletion();
    }
} else {
    echo '<br />' . _("Nothing to delete!");
}

?>
</table></td></tr></table>
</body></html>