<?php

/**
 * event_create.php
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * functions to create a event for calendar.
 *
 * $Id$
 */

require_once('calendar_data.php');
require_once('functions.php');
chdir('..');
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/html.php');

//main form to gather event info
function show_event_form() {
    global $color, $editor_size, $year, $day, $month, $hour;

    echo "\n<FORM name=eventscreate action=\"event_create.php\" METHOD=POST >\n".
         "      <INPUT TYPE=hidden NAME=\"year\" VALUE=\"$year\">\n".
         "      <INPUT TYPE=hidden NAME=\"month\" VALUE=\"$month\">\n".
         "      <INPUT TYPE=hidden NAME=\"day\" VALUE=\"$day\">\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Start time:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <SELECT NAME=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </SELECT>\n" .
         "      &nbsp;:&nbsp;\n" .
         "      <SELECT NAME=\"event_minute\">\n";
    select_option_minute("00");
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Length:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <SELECT NAME=\"event_length\">\n";
    select_option_length("0");
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Priority:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <SELECT NAME=\"event_priority\">\n";
    select_option_priority("0");
    echo "      </SELECT>\n".
         "      </td></tr>\n".
         html_tag( 'tr' ) .
         html_tag( 'td', _("Title:"), 'right', $color[4] ) . "\n" .
         html_tag( 'td', '', 'left', $color[4] ) . "\n" .
         "      <INPUT TYPE=text NAME=\"event_title\" VALUE=\"\" SIZE=30 MAXLENGTH=50><BR>\n".
         "      </td></tr>\n".
         html_tag( 'tr',
             html_tag( 'td',
                 "<TEXTAREA NAME=\"event_text\" ROWS=5 COLS=\"$editor_size\" WRAP=HARD></TEXTAREA>" ,
             'left', $color[4], 'colspan="2"' )
         ) ."\n" .
         html_tag( 'tr',
             html_tag( 'td',
                 "<INPUT TYPE=SUBMIT NAME=send VALUE=\"" .
                 _("Set Event") . "\">" ,
             'left', $color[4], 'colspan="2"' )
         ) ."\n";
    echo "</FORM>\n";
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
                    html_tag( 'th', _("Event Has been added!") . "<br>\n", '', $color[4], 'colspan="2"' )
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
                    html_tag( 'td', $event_title, 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td', _("Message:"), 'right', $color[4] ) . "\n" .
                    html_tag( 'td', $event_text, 'left', $color[4] ) . "\n"
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        "<a href=\"day.php?year=$year&month=$month&day=$day\">" . _("Day View") . "</a>\n" ,
                    'left', $color[4], 'colspan="2"' ) . "\n"
                ) ,
            '', $color[0], 'width="100%" border="0" cellpadding="2" cellspacing="1"' ) ."\n";
}

?>
</table></td></tr></table>
</body></html>
