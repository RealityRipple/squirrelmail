<?php

/**
 * SquirrelMail time zone library - time zone validation script.
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage timezones
 */

/** @ignore */
define('SM_PATH','../../');

/** Send http header */
header('Content-Type: text/plain');

/** Information about script */
echo "--------------------------------------------\n"
    ." SquirrelMail time zone library test script\n"
    ."--------------------------------------------\n";

/** load SM config */
unset($time_zone_type);
if (file_exists(SM_PATH.'config/config.php')) {
    include(SM_PATH.'config/config.php');
} else {
    echo "SquirrelMail configuration file is missing.\n";
    exit();
}

/**
 * Script does not test, if standard time zone libraries are missing.
 * If they are missing or corrupted - php can fail, scream and show 
 * finger or other parts of interpreter.
 */

/** load original reference */
include(SM_PATH.'include/timezones/standard_orig.php');

/** move timezones to different array */
$aTimeZonesOrig = $aTimeZones;
unset($aTimeZones);

if (! isset($time_zone_type) || $time_zone_type == 0 || $time_zone_type == 1) {
    /** load new time zone library */
    include(SM_PATH.'include/timezones/standard.php');
} elseif ($time_zone_type == 2 || $time_zone_type == 3) {
    /** load custom time zone library */
    $aTimeZones=array();
    if (file_exists(SM_PATH . 'config/timezones.php')) {
        include(SM_PATH.'config/timezones.php');
    } else {
        echo "ERROR: config/timezones.php is missing.\n";
        exit();
    }
} else {
    echo "ERROR: invalid value in time_zone_type configuration.\n";
    exit();
}

if (! isset($aTimeZones) || ! is_array($aTimeZones) || empty($aTimeZones)) {
    echo "ERROR: timezones array is missing or empty.\n";
    exit();
}

$error = false;

/** test backward compatibility */
echo "Testing backward compatibility:\n"
    ."  Failed time zones:\n";
foreach ($aTimeZonesOrig as $TzKey => $TzData) {
    if (! isset($aTimeZones[$TzKey])) {
        echo '    '.$TzKey."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none. Looking good.\n";
} else {
    // error is not fatal, but test should fail only with limited custom time zone sets
}

echo "\n";

/** test forward compatibility */
$error = false;
echo "Testing forward compatibility:\n"
    ."  New time zones:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (! isset($aTimeZonesOrig[$TzKey])) {
        echo '    '.$TzKey."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    no new time zones.\n";
} else {
    // error is not fatal. test should show new time zones, that are not 
    // present in timezones.cfg
}

echo "\n";

/** test links */
$error = false;
echo "Testing time zone links:\n"
    ."  Failed time zone links:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (isset($TzData['LINK']) && ! isset($aTimeZones[$TzData['LINK']]['TZ'])) {
        echo '    '.$TzKey.' = '.$TzData['LINK']."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none. Looking good.\n";
} else {
    // error is fatal. 'LINK' should always reffer to existing 'TZ' entries
}

echo "\n";

/** Test TZ subkeys */
$error = false;
echo "Testing time zone TZ subkeys:\n"
    ."  Failed time zone TZ subkeys:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (! isset($TzData['LINK']) && ! isset($TzData['TZ'])) {
        echo '    '.$TzKey."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none. Looking good.\n";
} else {
    // LINK or TZ are required for strict time zones. Interface won't break, but
    // any error means inconsistency in time zone array.
}

echo "\n";

/** Test NAME subkeys */
$error = false;
echo "Testing time zone NAME subkeys:\n"
    ."  Time zones without NAME subkeys:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (isset($TzData['TZ']) && ! isset($TzData['NAME'])) {
        echo '    '.$TzKey."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none.\n";
} else {
    // error is not fatal. It would be nice if all geographic locations
    // use some human readable name
}

echo "\n";

/** Test NAME subkeys */
$error = false;
echo "  Time zones with empty NAME subkeys:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (isset($TzData['NAME']) && empty($TzData['NAME'])) {
        echo '    '.$TzKey."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none. Looking good\n";
} else {
    // error is fatal. NAME should not be empty string.
}

echo "\n";

/** Test TZ subkeys with UCT/UTC/GMT offsets */
$error = false;
echo "Testing TZ subkeys with UCT/UTC/GMT offsets:\n"
    ."  Time zones UCT/UTC/GMT offsets:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (isset($TzData['TZ']) && preg_match("/^(UCT)|(UTC)|(GMT).+/i",$TzData['TZ'])) {
        echo '    '.$TzKey.' = '.$TzData['TZ']."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none.\n";
} else {
    // I think error is fatal for UCT with offsets. date('T',time()) is corrupted.
}

echo "\n";

/** Test TZ subkeys with custom TZ values and no offsets */
$error = false;
echo "Testing TZ subkeys with custom TZ values and no offsets:\n"
    ."  Time zones with custom TZ values and no offsets:\n";
foreach ($aTimeZones as $TzKey => $TzData) {
    if (isset($TzData['TZ']) && 
        ! preg_match("/^((UCT)|(UTC)|(GMT).+)|(GMT)$/i",$TzData['TZ']) &&
        preg_match("/^[a-z]+$/i",$TzData['TZ'])) {
        echo '    '.$TzKey.' = '.$TzData['TZ']."\n";
        $error = true;
    }
}
if (! $error) {
    echo "    none.\n";
} else {
    // I think error is fatal. Time zone formating requires time zone name and offset from GMT.
}

echo "\n";

echo "Done!\n";
