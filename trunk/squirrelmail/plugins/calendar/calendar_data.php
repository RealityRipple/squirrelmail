<?php

/**
 * calendar_data.php
 *
 * Copyright (c) 2002-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * functions to operate on calendar data files.
 *
 * $Id$
 */

// this is array that contains all events
// it is three dimensional array with fallowing structure
// $calendardata[date][time] = array(length,priority,title,message);
$calendardata = array();

//read events into array
//data is | delimited, just like addresbook
//files are structured like this:
//date|time|length|priority|title|message);
//files are divide by year for performance increase
function readcalendardata() {
    global $calendardata, $username, $data_dir, $year;

    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");

    if (file_exists($filename)){
        $fp = fopen ($filename,'r');

        if ($fp){
            while ($fdata = fgetcsv ($fp, 4096, '|')) {
                $calendardata[$fdata[0]][$fdata[1]] = array( 'length' => $fdata[2],
                                                            'priority' => $fdata[3],
                                                            'title' => htmlentities($fdata[4],ENT_NOQUOTES),
                                                            'message' => htmlentities($fdata[5],ENT_NOQUOTES),
                                                            'reminder' => $fdata[6] );
            }
            fclose ($fp);
        }
    }
}

//makes events persistant
function writecalendardata() {
    global $calendardata, $username, $data_dir, $year;

    $filetmp = getHashedFile($username, $data_dir, "$username.$year.cal.tmp");
    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");
    $fp = fopen ($filetmp,"w");
    if ($fp) {
        while ( $calfoo = each ($calendardata)) {
            while ( $calbar = each ($calfoo['value'])) {
                $calfoobar = $calendardata[$calfoo['key']][$calbar['key']];
                $calstr = "$calfoo[key]|$calbar[key]|$calfoobar[length]|$calfoobar[priority]|$calfoobar[title]|$calfoobar[message]|$calfoobar[reminder]\n";
                fwrite($fp, $calstr, 4096);
            }

        }
        fclose ($fp);
        rename($filetmp,$filename);
    }
}

//deletes event from file
function delete_event($date, $time) {
    global $calendardata, $username, $data_dir, $year;

    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");
    $fp = fopen ($filename,'r');
    if ($fp){
        while ($fdata = fgetcsv ($fp, 4096, "|")) {
            if (($fdata[0]==$date) && ($fdata[1]==$time)){
            // do nothing
            } else {
                $calendardata[$fdata[0]][$fdata[1]] = array( 'length' => $fdata[2],
                                                             'priority' => $fdata[3],
                                                             'title' => $fdata[4],
                                                             'message' => $fdata[5],
                                                             'reminder' => $fdata[6] );
            }
        }
        fclose ($fp);
    }
    writecalendardata();

}

// same as delete but not saves calendar
// saving is done inside event_edit.php
function update_event($date, $time) {
    global $calendardata, $username, $data_dir, $year;

    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");
    $fp = fopen ($filename,'r');
    if ($fp){
        while ($fdata = fgetcsv ($fp, 4096, '|')) {
            if (($fdata[0]==$date) && ($fdata[1]==$time)){
            // do nothing
            } else {
                $calendardata[$fdata[0]][$fdata[1]] = array( 'length' => $fdata[2],
                                                             'priority' => $fdata[3],
                                                             'title' => $fdata[4],
                                                             'message' => $fdata[5],
                                                             'reminder' => $fdata[6] );
            }
        }
        fclose ($fp);
    }
}


?>
