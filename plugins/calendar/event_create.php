<?php
/*
 *  event_create.php
 *
 *  Copyright (c) 2001 Michal Szczotka <michal@tuxy.org>
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  functions to create a event for calendar.
 *
 * $Id$
 */

require_once('calendar_data.php');
require_once('functions.php');
chdir('..');
require_once('../src/validate.php');
require_once('../functions/strings.php');
require_once('../functions/date.php');
require_once('../config/config.php');
require_once('../functions/page_header.php');
require_once('../src/load_prefs.php');

//main form to gather event info
function show_event_form() {
    global $color, $editor_size, $year, $day, $month, $hour;

    echo "\n<FORM name=eventscreate action=\"event_create.php\" METHOD=POST >\n".
         "      <INPUT TYPE=hidden NAME=\"year\" VALUE=\"$year\">\n".
         "      <INPUT TYPE=hidden NAME=\"month\" VALUE=\"$month\">\n".
         "      <INPUT TYPE=hidden NAME=\"day\" VALUE=\"$day\">\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Start time:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </SELECT>\n" .
         "      &nbsp;:&nbsp;\n" .
         "      <SELECT NAME=\"event_minute\">\n";
    select_option_minute("00");
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Length:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_length\">\n";
    select_option_length("0");
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Priority:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_priority\">\n";
    select_option_priority("0");
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Title:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <INPUT TYPE=text NAME=\"event_title\" VALUE=\"\" SIZE=30 MAXLENGTH=50><BR>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=LEFT COLSPAN=2>\n".
         "      <TEXTAREA NAME=\"event_text\" ROWS=5 COLS=\"$editor_size\" WRAP=HARD></TEXTAREA>\n".
         "      </TD></TR>\n".
         "      <TR><TD ALIGN=LEFT BGCOLOR=\"$color[4]\" COLSPAN=2><INPUT TYPE=SUBMIT NAME=send VALUE=\"" .
         _("Set Event") . "\"></TD></TR>\n";
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


echo "<TR BGCOLOR=\"$color[0]\"><TD>" .
     "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">" .
     '<tr><td COLSPAN=2>' .
     date_intl( 'l, F d Y', mktime(0, 0, 0, $month, $day, $year)) .
     '</td></tr>';
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
    echo "  <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">\n".
         "    <TR><TH COLSPAN=2 BGCOLOR=\"$color[4]\">\n".
         _("Event Has been added!") . "<br>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Date:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$month/$day/$year</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Time:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_hour:$event_minute</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Title:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_title</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Message:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_text</TD></TR>\n".
         "    <TR><TD COLSPAN=2 BGCOLOR=\"$color[4]\">\n".
         "<A HREF=\"day.php?year=$year&month=$month&day=$day\">" . _("Day View") . "</A>\n".
         "    </TD></TR>\n".
         "  </TABLE>\n";
}

?>
</table></td></tr></table>
</body></html>