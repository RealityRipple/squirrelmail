<?php
/*
 *  event_edit.php
 *
 *  Copyright (c) 2001 Michal Szczotka <michal@tuxy.org>
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Functions to edit an event.
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
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Date:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
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
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Time:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_hour\">\n";
    select_option_hour($hour);
    echo "      </SELECT>\n".
         "      &nbsp;:&nbsp;\n".
         "      <SELECT NAME=\"event_minute\">\n";
    select_option_minute($minute);
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Length:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_length\">\n";
    select_option_length($tmparray[length]);
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Priority:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <SELECT NAME=\"event_priority\">\n";
    select_option_priority($tmparray[priority]);
    echo "      </SELECT>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" . _("Title:") . "</TD>\n".
         "      <TD BGCOLOR=\"$color[4]\" ALIGN=LEFT>\n".
         "      <INPUT TYPE=text NAME=\"event_title\" VALUE=\"$tmparray[title]\" SIZE=30 MAXLENGTH=50><BR>\n".
         "      </TD></TR>\n".
         "      <TR><TD BGCOLOR=\"$color[4]\" ALIGN=LEFT COLSPAN=2>\n".
         "      <TEXTAREA NAME=\"event_text\" ROWS=5 COLS=\"$editor_size\" WRAP=HARD>$tmparray[message]</TEXTAREA>\n".
         "      </TD></TR>\n".
         "      <TR><TD ALIGN=LEFT BGCOLOR=\"$color[4]\" COLSPAN=2><INPUT TYPE=SUBMIT NAME=send VALUE=\"" .
         _("Update Event") . "\"></TD></TR>\n".
         "</FORM>\n";
}

// self explenatory
function confirm_update() {
    global $calself, $year, $month, $day, $hour, $minute, $calendardata, $color, $event_year, $event_month, $event_day, $event_hour, $event_minute, $event_length, $event_priority, $event_title, $event_text;

    $tmparray = $calendardata["$month$day$year"]["$hour$minute"];

    echo "  <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">\n".
         "    <TR><TH COLSPAN=2 BGCOLOR=\"$color[4]\">\n".
         _("Do you really want to change this event from:") . "<br>\n".
         "    </TH></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Date:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$month/$day/$year</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Time:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$hour:$minute</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Priority:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$tmparray[priority]</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Title:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$tmparray[title]</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Message:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$tmparray[message]</TD></TR>\n".
         "    <TR><TH COLSPAN=2 BGCOLOR=\"$color[4]\">\n".
         _("to:") . "<br>\n".
         "    </TH></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Date:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_month/$event_day/$event_year</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . ("Time:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_hour:$event_minute</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Priority:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_priority</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Title:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_title</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">" . _("Message:") . "</TD>\n".
         "    <TD ALIGN=LEFT BGCOLOR=\"$color[4]\">$event_text</TD></TR>\n".
         "    <TR><TD ALIGN=RIGHT BGCOLOR=\"$color[4]\">\n".
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
         "    </FORM>\n".
         "    </TD><TD ALIGN=LEFT BGCOLOR=\"$color[4]\">\n".
         "    <FORM NAME=\"nodelevent\" METHOD=POST ACTION=\"day.php\">\n".
         "       <INPUT TYPE=HIDDEN NAME=\"year\" VALUE=\"$year\">\n".
         "       <INPUT TYPE=HIDDEN NAME=\"month\" VALUE=\"$month\">\n".
         "       <INPUT TYPE=HIDDEN NAME=\"day\" VALUE=\"$day\">\n".
         '       <INPUT TYPE=SUBMIT VALUE="' . _("No") . "\">\n".
         "    </FORM>\n".
         "    </TD></TR>\n".
         "  </TABLE>\n";

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

echo "<TR BGCOLOR=\"$color[0]\"><TD>" .
     "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">" .
     '<tr><td COLSPAN=2>' .
     date_intl( 'l, F d Y', mktime(0, 0, 0, $month, $day, $year)) .
     '</td></tr>';
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
        echo "<tr><td>" . _("Event updated!") . "</td></tr>\n";
        echo "<tr><td><A HREF=\"day.php?year=$year&month=$month&day=$day\">" . 
        _("Day View") ."</A></td></tr>\n";
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
