<?php
/*
 *  functions.php
 *
 *  Copyright (c) 2001 Michal Szczotka <michal@tuxy.org>
 *  Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  miscelenous functions.
 *
 * $Id$
 */


function calendar_header() {
    //Add Second layer ofCalendar links to upper menu
    global $color,$year,$day,$month;

    echo html_tag( 'table', '', '', $color[0], 'border="0" width="100%" cellspacing="0" cellpadding="2"' ) .
         html_tag( 'tr' ) .
         html_tag( 'td', '', 'left', '', 'width="100%"' );

    displayInternalLink("plugins/calendar/calendar.php?year=$year&month=$month",_("Month View"),"right");
    echo "&nbsp;&nbsp\n";
    displayInternalLink("plugins/calendar/day.php?year=$year&month=$month&day=$day",_("Day View"),"right");
    echo "&nbsp;&nbsp\n";
    // displayInternalLink("plugins/calendar/event_create.php?year=$year&month=$month&day=$day",_("Add Event"),"right");
    // echo "&nbsp;&nbsp\n";
    echo '</td></tr>';

}

function select_option_length($selected) {

    $eventlength = array(
        "0" => _("0 min."),
        "15" => _("15 min."),
        "30" => _("35 min."),
        "45" => _("45 min."),
        "60" => _("1 hr."),
        "90" => _("1.5 hr."),
        "120" => _("2 hr."),
        "150" => _("2.5 hr."),
        "180" => _("3 hr."),
        "210" => _("3.5 hr."),
        "240" => _("4 hr."),
        "300" => _("5 hr."),
        "360" => _("6 hr.")
    );

    while( $bar = each($eventlength)) {
        if($selected==$bar[key]){
                echo "        <OPTION VALUE=\"".$bar[key]."\" SELECTED>".$bar[value]."</OPTION>\n";
        } else {
                echo "        <OPTION VALUE=\"".$bar[key]."\">".$bar[value]."</OPTION>\n";
        }
    }
}

function select_option_minute($selected) {
    $eventminute = array(
    "00"=>"00",
    "05"=>"05",
    "10"=>"10",
    "15"=>"15",
    "20"=>"20",
    "25"=>"25",
    "30"=>"30",
    "35"=>"35",
    "40"=>"40",
    "45"=>"45",
    "50"=>"50",
    "55"=>"55"
    );

    while ( $bar = each($eventminute)) {
        if ($selected==$bar[key]){
                echo "        <OPTION VALUE=\"".$bar[key]."\" SELECTED>".$bar[value]."</OPTION>\n";
        } else {
                echo "        <OPTION VALUE=\"".$bar[key]."\">".$bar[value]."</OPTION>\n";
        }
    }
}

function select_option_hour($selected) {

    for ($i=0;$i<24;$i++){
        ($i<10)? $ih = "0" . $i : $ih = $i;
        if ($ih==$selected){
            echo "            <OPTION VALUE=\"$ih\" SELECTED>$i</OPTION>\n";
        } else {
            echo "            <OPTION VALUE=\"$ih\">$i</OPTION>\n";
        }
    }
}

function select_option_priority($selected) {
    $eventpriority = array(
        "0" => _("Normal"),
        "1" => _("High"),
    );

    while( $bar = each($eventpriority)) {
        if($selected==$bar[key]){
                echo "        <OPTION VALUE=\"".$bar[key]."\" SELECTED>".$bar[value]."</OPTION>\n";
        } else {
                echo "        <OPTION VALUE=\"".$bar[key]."\">".$bar[value]."</OPTION>\n";
        }
    }
}

function select_option_year($selected) {

    for ($i=1902;$i<2038;$i++){
        if ($i==$selected){
            echo "            <OPTION VALUE=\"$i\" SELECTED>$i</OPTION>\n";
        } else {
            echo "            <OPTION VALUE=\"$i\">$i</OPTION>\n";
        }
    }
}

function select_option_month($selected) {

    for ($i=1;$i<13;$i++){
        $im=date('m',mktime(0,0,0,$i,1,1));
        $is = substr( _( date('F',mktime(0,0,0,$i,1,1)) ), 0, 3 );
        if ($im==$selected){
            echo "            <OPTION VALUE=\"$im\" SELECTED>$is</OPTION>\n";
        } else {
            echo "            <OPTION VALUE=\"$im\">$is</OPTION>\n";
        }
    }
}

function select_option_day($selected) {

    for ($i=1;$i<32;$i++){
        ($i<10)? $ih="0".$i : $ih=$i;
        if ($i==$selected){
            echo "            <OPTION VALUE=\"$ih\" SELECTED>$i</OPTION>\n";
        } else {
            echo "            <OPTION VALUE=\"$ih\">$i</OPTION>\n";
        }
    }
}

?>