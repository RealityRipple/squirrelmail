<?php

/**
 * event_create.php
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * functions to create a event for calendar.
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

// undo rg = on effects
if (isset($month)) unset($month);
if (isset($year))  unset($year);
if (isset($day))  unset($day);
if (isset($hour))  unset($hour);
if (isset($minute))  unset($minute);
if (isset($event_hour))  unset($event_hour);
if (isset($event_minute))  unset($event_minute);
if (isset($event_length))  unset($event_length);
if (isset($event_priority))  unset($event_priority);


if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $year = $_GET['year'];
}
elseif (isset($_POST['year']) && is_numeric($_POST['year'])) {
    $year = $_POST['year'];
}
if (isset($_GET['month']) && is_numeric($_GET['month'])) {
    $month = $_GET['month'];
}
elseif (isset($_POST['month']) && is_numeric($_POST['month'])) {
    $month = $_POST['month'];
}
if (isset($_GET['day']) && is_numeric($_GET['day'])) {
    $day = $_GET['day'];
}
elseif (isset($_POST['day']) && is_numeric($_POST['day'])) {
    $day = $_POST['day'];
}

if (isset($_POST['hour']) && is_numeric($_POST['hour'])) {
    $hour = $_POST['hour'];
}
elseif (isset($_GET['hour']) && is_numeric($_GET['hour'])) {
    $hour = $_GET['hour'];
}
if (isset($_POST['event_hour']) && is_numeric($_POST['event_hour'])) {
    $event_hour = $_POST['event_hour'];
}
if (isset($_POST['event_minute']) && is_numeric($_POST['event_minute'])) {
    $event_minute = $_POST['event_minute'];
}
if (isset($_POST['event_length']) && is_numeric($_POST['event_length'])) {
    $event_length = $_POST['event_length'];
}
if (isset($_POST['event_priority']) && is_numeric($_POST['event_priority'])) {
    $event_priority = $_POST['event_priority'];
}
if (isset($_POST['event_title'])) {
    $event_title = $_POST['event_title'];
}
if (isset($_POST['event_text'])) {
    $event_text = $_POST['event_text'];
}
if (isset($_POST['send'])) {
    $send = $_POST['send'];
}
/* got 'em */

//main form to gather event info
function show_event_form() {
    global $color, $editor_size, $year, $day, $month, $hour;

    echo "\n<form name=\"eventscreate\" action=\"event_create.php\" method=\"post\">\n".
         "      <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
         "      <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
         "      <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Start time:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <select name=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </select>\n" .
         "      &nbsp;:&nbsp;\n" .
         "      <select name=\"event_minute\">\n";
    select_option_minute("00");
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Length:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <select name=\"event_length\">\n";
    select_option_length("0");
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Priority:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <select name=\"event_priority\">\n";
    select_option_priority("0");
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Title:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <input type=\"text\" name=\"event_title\" value=\"\" size=\"30\" maxlength=\"50\" /><br />\n".
         "      </td></tr>\n".
         html_tag( 'tr',
             html_tag( 'td',
                 "<textarea name=\"event_text\" rows=\"5\" cols=\"$editor_size\" wrap=\"hard\"></textarea>" ,
             'left', $color[4], 'colspan="2"' )
         ) ."\n" .
         html_tag( 'tr',
             html_tag( 'td',
                 '<input type="submit" name="send" value="' .
                 _("Set Event") . '" />' ,
             'left', $color[4], 'colspan="2"' )
         ) ."\n";
    echo "</form>\n";
}


if ( !isset($month) || $month <= 0){
    $month = date( 'm' );
}
if ( !isset($year) || $year <= 0){
    $year = date( 'Y' );
}
if (!isset($day) || $day <= 0){
    $day = date( 'd' );
}
if (!isset($hour) || $hour <= 0){
    $hour = '08';
}

$calself=basename($PHP_SELF);


displayPageHeader($color, 'None');
//load calendar menu
calendar_header();

echo html_tag( 'tr', '', '', $color[0] ) .
           html_tag( 'td', '', 'left' ) .
               html_tag( 'table', '', '', $color[0], 'width="100%" border="0" cellpadding="2" cellspacing="1"' ) .
                   html_tag( 'tr',
                       html_tag( 'td', date_intl( _("l, F j Y"), mktime(0, 0, 0, $month, $day, $year)), 'left', '', 'colspan="2"' )
                   );
//if form has not been filled in
if(!isset($event_text)){
    show_event_form();
} else {
    readcalendardata();
    //make sure that event text is fittting in one line
    $event_text=nl2br($event_text);
    $event_text=ereg_replace ("\n", "", $event_text);
    $event_text=ereg_replace ("\r", "", $event_text);
    $calendardata["$month$day$year"]["$event_hour$event_minute"] =
    array( 'length' => $event_length,
           'priority' => $event_priority,
           'title' => $event_title,
           'message' => $event_text,
           'reminder' => '' );
    //save
    writecalendardata();
    echo html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'th', _("Event Has been added!") . "<br />\n", '', $color[4], 'colspan="2"' )
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Date:"), 'right', $color[4] ) . "\n" .
                    html_tag( 'td', $month .'/'.$day.'/'.$year, 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Time:"), 'right', $color[4] ) . "\n" .
                    html_tag( 'td', $event_hour.':'.$event_minute, 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Title:"), 'right', $color[4] ) . "\n" .
                    html_tag( 'td', htmlspecialchars($event_title,ENT_NOQUOTES), 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Message:"), 'right', $color[4] ) . "\n" .
                    html_tag( 'td', htmlspecialchars($event_text,ENT_NOQUOTES), 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day\">" . _("Day View") . "</a>\n" ,
                    'left', $color[4], 'colspan="2"' ) . "\n"
                ) ,
            '', $color[0], 'width="100%" border="0" cellpadding="2" cellspacing="1"' ) ."\n";
}

?>
</table></td></tr></table>
</body></html>