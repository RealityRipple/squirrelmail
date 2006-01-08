<?php

/**
 * calendar_data.php
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * functions to operate on calendar data files.
 *
 * @copyright &copy; 2002-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage calendar
 */

/** this is array that contains all events
 *  it is three dimensional array with fallowing structure
 *  $calendardata[date][time] = array(length,priority,title,message); */
$calendardata = array();

/**
 * Reads multilined calendar data
 * 
 * Plugin stores multiline texts converted to single line with PHP nl2br().
 * Function undoes nl2br() conversion and sanitizes data with htmlspecialchars().
 * @param string $string calendar string
 * @return string calendar string converted to multiline text
 * @since 1.5.1
 */
function calendar_readmultiline($string) {
    // replace html line breaks with ASCII line feeds
    $string = str_replace(array('<br />','<br>'),array("\n","\n"),$string);
    // FIXME: don't sanitize data. Storage backend should not care about html data safety
    $string = htmlspecialchars($string,ENT_NOQUOTES);
    return $string;
}

/**
 * Callback function used to sanitize calendar data before saving it to file
 * @param string $sValue array value 
 * @param string $sKey array key
 * @since 1.5.1
 */
function calendar_encodedata(&$sValue, $sKey) {
    // add html line breaks and remove original ASCII line feeds and carriage returns
    $sValue = str_replace(array("\n","\r"),array('',''),nl2br($sValue));
}

/**
 * read events into array
 *
 * data is | delimited, just like addressbook
 * files are structured like this:
 * date|time|length|priority|title|message
 * files are divided by year for performance increase */
function readcalendardata() {
    global $calendardata, $username, $data_dir, $year;

    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");

    if (file_exists($filename)){
        $fp = fopen ($filename,'r');

        if ($fp){
            while ($fdata = fgetcsv ($fp, 4096, '|')) {
                $calendardata[$fdata[0]][$fdata[1]] = array( 'length' => $fdata[2],
                                                            'priority' => $fdata[3],
                                                            'title' => htmlspecialchars($fdata[4],ENT_NOQUOTES),
                                                            'message' => calendar_readmultiline($fdata[5]),
                                                            'reminder' => $fdata[6] );
            }
            fclose ($fp);
            // this is to sort the events within a day on starttime
            $new_calendardata = array();
            foreach($calendardata as $day => $data) {
                ksort($data, SORT_NUMERIC);
                $new_calendardata[$day] = $data;
            }
            $calendardata = $new_calendardata;
        }
    }
}

//makes events persistant
function writecalendardata() {
    global $calendardata, $username, $data_dir, $year, $color;

    $filetmp = getHashedFile($username, $data_dir, "$username.$year.cal.tmp");
    $filename = getHashedFile($username, $data_dir, "$username.$year.cal");
    $fp = fopen ($filetmp,"w");
    if ($fp) {
        while ( $calfoo = each ($calendardata)) {
            while ( $calbar = each ($calfoo['value'])) {
                $calfoobar = $calendardata[$calfoo['key']][$calbar['key']];
                array_walk($calfoobar,'calendar_encodedata');
                $calstr = "$calfoo[key]|$calbar[key]|$calfoobar[length]|$calfoobar[priority]|$calfoobar[title]|$calfoobar[message]|$calfoobar[reminder]\n";
                if(sq_fwrite($fp, $calstr, 4096) === FALSE) {
                        error_box(_("Could not write calendar file %s", "$username.$year.cal.tmp"), $color);
                }
            }

        }
        fclose ($fp);
        @unlink($filename);
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