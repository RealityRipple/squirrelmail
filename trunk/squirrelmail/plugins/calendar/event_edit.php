<?php

/**
 * event_edit.php
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * Functions to edit an event.
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

// update event info
function show_event_form() {
    global $color, $editor_size, $year, $day, $month, $hour, $minute, $calendardata;

    $tmparray = $calendardata["$month$day$year"]["$hour$minute"];
    echo "\n<FORM name=eventupdate action=\"event_edit.php\" METHOD=POST >\n".
         "      <INPUT TYPE=hidden NAME=\"year\" VALUE=\"$year\">\n".
         "      <INPUT TYPE=hidden NAME=\"month\" VALUE=\"$month\">\n".
         "      <INPUT TYPE=hidden NAME=\"day\" VALUE=\"$day\">\n".
         "      <INPUT TYPE=hidden NAME=\"hour\" VALUE=\"$hour\">\n".
         "      <INPUT TYPE=hidden NAME=\"minute\" VALUE=\"$minute\">\n".
         "      <INPUT TYPE=hidden NAME=\"updated\" VALUE=\"yes\">\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Date:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <SELECT NAME=\"event_year\">\n";
    select_option_year($year);
    echo "      </SELECT>\n" .
         "      &nbsp;&nbsp;\n" .
         "      <SELECT NAME=\"event_month\">\n";
    select_option_month($month);
    echo "      </SELECT>\n".
         "      &nbsp;&nbsp;\n".
         "      <SELECT NAME=\"event_day\">\n";
    select_option_day($day);
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Time:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <SELECT NAME=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </SELECT>\n".
         "      &nbsp;:&nbsp;\n".
         "      <SELECT NAME=\"event_minute\">\n";
    select_option_minute($minute);
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Length:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <SELECT NAME=\"event_length\">\n";
    select_option_length($tmparray[length]);
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Priority:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <SELECT NAME=\"event_priority\">\n";
    select_option_priority($tmparray[priority]);
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Title:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) .
         "      <INPUT TYPE=text NAME=\"event_title\" VALUE=\"$tmparray[title]\" SIZE=30 MAXLENGTH=50><BR>\n".
         "      </td></tr>\n".
         html_tag( 'td',
             "      <TEXTAREA NAME=\"event_text\" ROWS=5 COLS=\"$editor_size\" WRAP=HARD>$tmparray[message]</TEXTAREA>\n" ,
         'left', $color[4], 'colspan="2"' ) .
         '</tr>' . html_tag( 'tr' ) .
         html_tag( 'td',
             "<INPUT TYPE=SUBMIT NAME=send VALUE=\"" .
             _("Update Event") . "\">\n" ,
         'left', $color[4], 'colspan="2"' ) .
         "</tr></FORM>\n";
}

// self explenatory
function confirm_update() {
    global $calself, $year, $month, $day, $hour, $minute, $calendardata, $color, $event_year, $event_month, $event_day, $event_hour, $event_minute, $event_length, $event_priority, $event_title, $event_text;

    $tmparray = $calendardata["$month$day$year"]["$hour$minute"];

    echo html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'th', _("Do you really want to change this event from:") . "<br>\n", '', $color[4], 'colspan="2"' ) ."\n"
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
                    html_tag( 'td', $tmparray[priority] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Title:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $tmparray[title] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Message:") , 'right', $color[4] ) ."\n" .
                    html_tag( 'td', $tmparray[message] , 'left', $color[4] ) ."\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("to:") . "<br>\n", '', $color[4], 'colspan="2"' ) ."\n"
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
                        "    <FORM NAME=\"updateevent\" METHOD=POST ACTION=\"$calself\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"year\" VALUE=\"$year\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"month\" VALUE=\"$month\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"day\" VALUE=\"$day\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"hour\" VALUE=\"$hour\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"minute\" VALUE=\"$minute\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_year\" VALUE=\"$event_year\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_month\" VALUE=\"$event_month\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_day\" VALUE=\"$event_day\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_hour\" VALUE=\"$event_hour\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_minute\" VALUE=\"$event_minute\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_priority\" VALUE=\"$event_priority\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_length\" VALUE=\"$event_length\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_title\" VALUE=\"$event_title\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"event_text\" VALUE=\"$event_text\">\n".
                        "       <INPUT TYPE=hidden NAME=\"updated\" VALUE=\"yes\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"confirmed\" VALUE=\"yes\">\n".
                        '       <INPUT TYPE=SUBMIT VALUE="' . _("Yes") . "\">\n".
                        "    </FORM>\n" ,
                    'right', $color[4] ) ."\n" .
                    html_tag( 'td',
                        "    <FORM NAME=\"nodelevent\" METHOD=POST ACTION=\"day.php\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"year\" VALUE=\"$year\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"month\" VALUE=\"$month\">\n".
                        "       <INPUT TYPE=HIDDEN NAME=\"day\" VALUE=\"$day\">\n".
                        '       <INPUT TYPE=SUBMIT VALUE="' . _("No") . "\">\n".
                        "    </FORM>\n" ,
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
    show_event_form();
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
                       "<a href=\"day.php?year=$year&month=$month&day=$day\">" . 
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
