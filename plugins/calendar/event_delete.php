<?php
/*
 *  event_delete.php
 *
 *  Copyright (c) 2001 Michal Szczotka <michal@tuxy.org>
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Functions to delete a event. 
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
require_once('../functions/html.php');

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
        echo "<a href=\"day.php?year=$year&month=$month&day=$day\">" .
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
