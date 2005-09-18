<?php

/**
 * event_edit.php
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * Functions to edit an event.
 *
 * @copyright &copy; 2002-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage calendar
 */

/** @ignore */
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
if (isset($event_year))  unset($event_year);
if (isset($event_month))  unset($event_month);
if (isset($event_day))  unset($event_day);
if (isset($event_hour))  unset($event_hour);
if (isset($event_minute))  unset($event_minute);
if (isset($event_length))  unset($event_length);
if (isset($event_priority))  unset($event_priority);

if (isset($_POST['updated'])) {
    $updated = $_POST['updated'];
}

if (isset($_POST['event_year']) && is_numeric($_POST['event_year'])) {
    $event_year = $_POST['event_year'];
}
if (isset($_POST['event_month']) && is_numeric($_POST['event_month'])) {
    $event_month = $_POST['event_month'];
}
if (isset($_POST['event_day']) && is_numeric($_POST['event_day'])) {
    $event_day = $_POST['event_day'];
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
if (isset($_POST['event_title'])) {
    $event_title = $_POST['event_title'];
}
if (isset($_POST['event_text'])) {
    $event_text = $_POST['event_text'];
}
if (isset($_POST['send'])) {
    $send = $_POST['send'];
}
if (isset($_POST['event_priority']) && is_numeric($_POST['event_priority'])) {
    $event_priority = $_POST['event_priority'];
}
if (isset($_POST['confirmed'])) {
    $confirmed = $_POST['confirmed'];
}

if (isset($_POST['year']) && is_numeric($_POST['year'])) {
    $year = $_POST['year'];
} elseif (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $year = $_GET['year'];
}
if (isset($_POST['month']) && is_numeric($_POST['month'])) {
    $month = $_POST['month'];
} elseif (isset($_GET['month']) && is_numeric($_GET['month'])) {
    $month = $_GET['month'];
}
if (isset($_POST['day']) && is_numeric($_POST['day'])) {
    $day = $_POST['day'];
} elseif (isset($_GET['day']) && is_numeric($_GET['day'])) {
    $day = $_GET['day'];
}
if (isset($_POST['hour']) && is_numeric($_POST['hour'])) {
    $hour = $_POST['hour'];
} elseif (isset($_GET['hour']) && is_numeric($_GET['hour'])) {
    $hour = $_GET['hour'];
}
if (isset($_POST['minute']) && is_numeric($_POST['minute'])) {
    $minute = $_POST['minute'];
}
elseif (isset($_GET['minute']) && is_numeric($_GET['minute'])) {
    $minute = $_GET['minute'];
}
/* got 'em */

// update event info
function update_event_form() {
    global $color, $editor_size, $year, $day, $month, $hour, $minute, $calendardata;

    $tmparray = $calendardata["$month$day$year"]["$hour$minute"];
    echo "\n<form name=\"eventupdate\" action=\"event_edit.php\" method=\"post\">\n".
         "      <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
         "      <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
         "      <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
         "      <input type=\"hidden\" name=\"hour\" value=\"$hour\" />\n".
         "      <input type=\"hidden\" name=\"minute\" value=\"$minute\" />\n".
         "      <input type=\"hidden\" name=\"updated\" value=\"yes\" />\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Date:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <select name=\"event_year\">\n";
    select_option_year($year);
    echo "      </select>\n" .
         "      &nbsp;&nbsp;\n" .
         "      <select name=\"event_month\">\n";
    select_option_month($month);
    echo "      </select>\n".
         "      &nbsp;&nbsp;\n".
         "      <select name=\"event_day\">\n";
    select_option_day($day);
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Time:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <select name=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </select>\n".
         "      &nbsp;:&nbsp;\n".
         "      <select name=\"event_minute\">\n";
    select_option_minute($minute);
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Length:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <select name=\"event_length\">\n";
    select_option_length($tmparray['length']);
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Priority:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <select name=\"event_priority\">\n";
    select_option_priority($tmparray['priority']);
    echo "      </select>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Title:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <input type=\"text\" name=\"event_title\" value=\"$tmparray[title]\" size=\"30\" maxlenght=\"50\" /><br />\n".
         "      </td></tr>\n".
         html_tag( 'td',
             "      <textarea name=\"event_text\" rows=\"5\" cols=\"$editor_size\" wrap=\"hard\">$tmparray[message]</textarea>\n" ,
         'left', $color[4], 'colspan="2"' ) .
         '</tr>' . html_tag( 'tr' ) .
         html_tag( 'td',
             '<input type="submit" name="send" value="' .
             _("Update Event") . "\" />\n" ,
         'left', $color[4], 'colspan="2"' ) .
         "</tr></form>\n";
}

// self explenatory
function confirm_update() {
    global $calself, $year, $month, $day, $hour, $minute, $calendardata, $color, $event_year, $event_month, $event_day, $event_hour, $event_minute, $event_length, $event_priority, $event_title, $event_text;

    $tmparray = $calendardata["$month$day$year"]["$hour$minute"];

    echo html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'th', _("Do you really want to change this event from:") . "<br />\n", '', $color[4], 'colspan="2"' ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Date:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $month.'/'.$day.'/'.$year , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Time:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $hour.':'.$minute , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Priority:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $tmparray['priority'] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Title:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $tmparray['title'] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Message:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $tmparray['message'] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("to:") . "<br />\n", '', $color[4], 'colspan="2"' ) ."\n"
                ) .

                html_tag( 'tr',
                    html_tag( 'td', _("Date:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $event_month.'/'.$event_day.'/'.$event_year , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Time:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $event_hour.':'.$event_minute , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Priority:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $event_priority , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Title:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $event_title , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Message:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $event_text , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        "    <form name=\"updateevent\" method=\"post\" action=\"$calself\">\n".
                        "       <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
                        "       <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
                        "       <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
                        "       <input type=\"hidden\" name=\"hour\" value=\"$hour\" />\n".
                        "       <input type=\"hidden\" name=\"minute\" value=\"$minute\" />\n".
                        "       <input type=\"hidden\" name=\"event_year\" value=\"$event_year\" />\n".
                        "       <input type=\"hidden\" name=\"event_month\" value=\"$event_month\" />\n".
                        "       <input type=\"hidden\" name=\"event_day\" value=\"$event_day\" />\n".
                        "       <input type=\"hidden\" name=\"event_hour\" value=\"$event_hour\" />\n".
                        "       <input type=\"hidden\" name=\"event_minute\" value=\"$event_minute\" />\n".
                        "       <input type=\"hidden\" name=\"event_priority\" value=\"$event_priority\" />\n".
                        "       <input type=\"hidden\" name=\"event_length\" value=\"$event_length\" />\n".
                        "       <input type=\"hidden\" name=\"event_title\" value=\"$event_title\" />\n".
                        "       <input type=\"hidden\" name=\"event_text\" value=\"$event_text\" />\n".
                        "       <input type=\"hidden\" name=\"updated\" value=\"yes\" />\n".
                        "       <input type=\"hidden\" name=\"confirmed\" value=\"yes\" />\n".
                        '       <input type="submit" value="' . _("Yes") . "\" />\n".
                        "    </form>\n" ,
                    'right', $color[4] ) ."\n" .
                    html_tag( 'td',
                        "    <form name=\"nodelevent\" method=\"post\" action=\"day.php\">\n".
                        "       <input type=\"hidden\" name=\"year\" value=\"$year\" />\n".
                        "       <input type=\"hidden\" name=\"month\" value=\"$month\" />\n".
                        "       <input type=\"hidden\" name=\"day\" value=\"$day\" />\n".
                        '       <input type="submit" value="' . _("No") . "\" />\n".
                        "    </form>\n" ,
                    'left', $color[4] ) ."\n"
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
if ($hour <= 0){
    $hour = '08';
}

$calself=basename($PHP_SELF);

displayPageHeader($color, 'None');
//load calendar menu
calendar_header();

echo html_tag( 'tr', '', '', $color[0] ) .
            html_tag( 'td', '', 'left' ) .
                html_tag( 'table', '', '', $color[0], 'width="100%" border="0" cellpadding="2" cellspacing="1"' ) .
                    html_tag( 'tr' ) .
                        html_tag( 'td',
                            date_intl( _("l, F j Y"), mktime(0, 0, 0, $month, $day, $year)) ,
                        'left', '', 'colspan="2"' );
if (!isset($updated)){
    //get changes to event
    readcalendardata();
    update_event_form();
} else {
    if (!isset($confirmed)){
        //confirm changes
        readcalendardata();
        // strip event text so it fits in one line
        $event_text=nl2br($event_text);
        $event_text=ereg_replace ("\n", '', $event_text);
        $event_text=ereg_replace ("\r", '', $event_text);
        confirm_update();
    } else {
        update_event("$month$day$year", "$hour$minute");
        echo html_tag( 'tr',
                   html_tag( 'td', _("Event updated!"), 'left' )
                ) . "\n";
        echo html_tag( 'tr',
                   html_tag( 'td',
                       "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day\">" .
                       _("Day View") ."</a>",
                   'left' )
                ) . "\n";

        $fixdate = date( 'mdY', mktime(0, 0, 0, $event_month, $event_day, $event_year));
        //if event has been moved to different year then act accordingly
        if ($year==$event_year){
            $calendardata["$fixdate"]["$event_hour$event_minute"] = array("length"=>"$event_length","priority"=>"$event_priority","title"=>"$event_title","message"=>"$event_text");
            writecalendardata();
        } else {
            writecalendardata();
            $year=$event_year;
            $calendardata = array();
            readcalendardata();
            $calendardata["$fixdate"]["$event_hour$event_minute"] = array("length"=>"$event_length","priority"=>"$event_priority","title"=>"$event_title","message"=>"$event_text");
            writecalendardata();
        }
    }
}

?>
</table></td></tr></table>
</body></html>