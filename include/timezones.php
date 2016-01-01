<?php

/**
 * SquirrelMail Time zone functions
 *
 * Function load time zone array selected in SquirrelMail 
 * configuration.
 * 
 * Time zone array must consist of key name that matches key in
 * standard time zone array and 'NAME' and 'TZ' subkeys. 'NAME'
 * key should store translatable key name. 'TZ' key should store
 * time zone name that will be used in TZ environment variable.
 * Both subkeys are optional. If they are not present, time zone
 * key name is used.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage timezones
 */

/**
 * Returns time zone array set in SquirrelMail configuration
 * @return array time zone array
 * @since 1.5.1
 */
function sq_get_tz_array() {
    global $time_zone_type;

    // variable is not set or empty
    if (! isset($time_zone_type) || empty($time_zone_type)) {
        $time_zone_type = 0;
    } else {
        // make sure that it is integer
        $time_zone_type = (int) $time_zone_type;
    }

    /**
     * TODO: which one is better (global + include_once) or (include) 
     */
    switch ($time_zone_type) {
    case '3':
    case '2':
        // custom time zone set
        $aTimeZones = array();
        if (file_exists(SM_PATH . 'config/timezones.php')) {
            include(SM_PATH . 'config/timezones.php');
        }
        $aRet = $aTimeZones;
        break;
    case '1':
    case '0':
    default:
        // standard (default) time zone set
        include(SM_PATH . 'include/timezones/standard.php');
        $aRet = $aTimeZones;
    }
    // sort array
    ksort($aRet);
    return $aRet;
}

/**
 * @param string time zone string
 * @return string time zone name used for TZ env 
 * (false, if timezone does not exists and server's TZ should be used)
 * @since 1.5.1
 */
function sq_get_tz_key($sTZ) {
    $aTZs=sq_get_tz_array();

    // get real time zone from link
    if (isset($aTZs[$sTZ]['LINK'])) {
        $sTZ = $aTZs[$sTZ]['LINK'];
    }

    if (isset($aTZs[$sTZ])) {
        if (isset($aTZs[$sTZ]['TZ'])) {
            // get time zone
            return $aTZs[$sTZ]['TZ'];
        } else {
            // array does not have TZ entry. bad thing
            return $sTZ;
        }
    } else {
        return false;
    }
}
