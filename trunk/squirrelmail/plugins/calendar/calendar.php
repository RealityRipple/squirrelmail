<?php
/*
 *
 *  calendar.php
 *
 *  Copyright (c) 2001 Michal Szczotka <michal@tuxy.org>
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Displays the main calendar page (month view).
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

//display upper part of month calendar view
function startcalendar() {
    global $year, $month, $day, $color;

    $prev_date = mktime(0, 0, 0, $month - 1, 1, $year);
    $act_date  = mktime(0, 0, 0, $month, 1, $year);
    $next_date = mktime(0, 0, 0, $month + 1, 1, $year);
    $prev_month = date( 'm', $prev_date );
    $next_month = date( 'm', $next_date);
    $prev_year = date( 'Y', $prev_date);
    $next_year = date( 'Y', $next_date );
    $self = 'calendar.php';

    echo "<TR BGCOLOR=\"$color[0]\"><TD>" .
         "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">" .
         '<tr>'.
         "<th><a href=\"$self?year=".($year-1)."&month=$month\">&lt;&lt;&nbsp;".($year-1)."</a></th>\n".
         "<th><a href=\"$self?year=$prev_year&month=$prev_month\">&lt;&nbsp;" .
         date_intl( 'M', $prev_date). "</a></th>\n".
         "<th bgcolor=$color[0] colspan=3>" .
         date_intl( 'F Y', $act_date ) . "</th>\n" .
         "<th><a href=\"$self?year=$next_year&month=$next_month\">" .
         date_intl( 'M', $next_date) . "&nbsp;&gt;</a></th>".
         "<th><a href=\"$self?year=".($year+1)."&month=$month\">".($year+1)."&nbsp;&gt;&gt;</a></th>".
         '</tr><tr>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Sunday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Monday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Tuesday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Wednesday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Thursday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Friday") . '</th>'.
         "<th WIDTH=\"14%\" bgcolor=$color[5] width=90>" . _("Saturday") . '</th>'.
        '</tr>';

}

//main logic for month view of calendar
function drawmonthview() {
    global $year, $month, $day, $color, $calendardata, $todayis;

    $aday = 1 - date('w', mktime(0, 0, 0, $month, 1, $year));
    $days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
    while ($aday <= $days_in_month) {
        echo '<tr>';
        for ($j=1; $j<=7; $j++) {
            $cdate="$month";
            ($aday<10)?$cdate=$cdate."0$aday":$cdate=$cdate."$aday";
            $cdate=$cdate."$year";
            if ( $aday <= $days_in_month && $aday > 0){
                echo "<TD BGCOLOR=\"$color[4]\" height=50 valign=top>\n" .
                     "<div align=right>";
                echo(($cdate==$todayis) ? "<font size=-1 color=$color[1]>[ " . _("TODAY") . " ] " : "<font size=-1>");
                echo "<a href=day.php?year=$year&month=$month&day=";
                echo(($aday<10) ? "0" : "");
                echo "$aday>$aday</a></font></div>";
            } else {
                echo "<TD BGCOLOR=\"$color[0]\">\n".
                     "&nbsp;";
            }
            if (isset($calendardata[$cdate])){
                $i=0;
                while ($calfoo = each($calendardata[$cdate])) {
                    $calbar = $calendardata[$cdate][$calfoo['key']];
                    echo ($calbar['priority']==1) ? "<FONT COLOR=\"$color[1]\">$calbar[title]</FONT><br>\n" : "$calbar[title]<br>\n";
                    $i=$i+1;
                    if($i==2){
                        break;
                    }
                }
            }
            echo "\n</TD>\n";
            $aday++;
        }
        echo '</tr>';
    }
}

//end of monthly view and form to jump to any month and year
function endcalendar() {
    global $year, $month, $day, $color;

    echo "          <TR><TD COLSPAN=7>\n".
         "          <FORM NAME=caljump ACTION=\"calendar.php\" METHOD=POST>\n".
         "          <SELECT NAME=\"year\">\n";
    select_option_year($year);
    echo "          </SELECT>\n".
         "          <SELECT NAME=\"month\">\n";
    select_option_month($month);
    echo "          </SELECT>\n".
         '         <INPUT TYPE=SUBMIT VALUE="' . _("Go") . "\">\n".
         "          </FORM>\n".
         "          </TD></TR>\n".
         "</TABLE></TD></TR></TABLE>\n";
}


if( !isset( $month ) || $month <= 0){
    $month = date( 'm' );
}
if( !isset($year) || $year <= 0){
    $year = date( 'Y' );
}
if( !isset($day) || $day <= 0){
    $day = date( 'd' );
}

$todayis = date( 'mdY' );
$calself=basename($PHP_SELF);

displayPageHeader($color, 'None');
calendar_header();
readcalendardata();
startcalendar();
drawmonthview();
endcalendar();

?>
</body></html>