<?php

/**
 * prefs.php
 *
 * This contains functions for filebased user prefs locations
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */



/* Hashing functions */

/**
 * Given a username and datafilename, this will return the path to the
 * hashed location of that datafile.
 *
 * @param string username the username of the current user
 * @param string dir the SquirrelMail datadir
 * @param string datafile the name of the file to open
 * @param bool hash_seach default true
 * @return string the hashed location of datafile
 * @since 1.2.0
 */
function getHashedFile($username, $dir, $datafile, $hash_search = true) {

    /* Remove trailing slash from $dir if found */
    if (substr($dir, -1) == '/') {
        $dir = substr($dir, 0, strlen($dir) - 1);
    }

    /* Compute the hash for this user and extract the hash directories. */
    $hash_dirs = computeHashDirs($username);

    /* First, get and make sure the full hash directory exists. */
    $real_hash_dir = getHashedDir($username, $dir, $hash_dirs);

    /* Set the value of our real data file, after we've removed unwanted characters. */
    $datafile = str_replace('/', '_', $datafile);
    $result = "$real_hash_dir/$datafile";

    /* Check for this file in the real hash directory. */
    if ($hash_search && !@file_exists($result)) {
        /* First check the base directory, the most common location. */
        if (@file_exists("$dir/$datafile")) {
            rename("$dir/$datafile", $result);

        /* Then check the full range of possible hash directories. */
        } else {
            $check_hash_dir = $dir;
            for ($h = 0; $h < 4; ++$h) {
                $check_hash_dir .= '/' . $hash_dirs[$h];
                if (@is_readable("$check_hash_dir/$datafile")) {
                    rename("$check_hash_dir/$datafile", $result);
                    break;
                }
            }
        }
    }

    /* Return the full hashed datafile path. */
    return ($result);
}

/**
 * Helper function for getHashedFile, given a username returns the hashed
 * dir for that username.
 *
 * @param string username the username of the current user
 * @param string dir the SquirrelMail datadir
 * @param string hash_dirs default ''
 * @return the path to the hash dir for username
 * @since 1.2.0
 */
function getHashedDir($username, $dir, $hash_dirs = '') {
    global $dir_hash_level;

    /* Remove trailing slash from $dir if found */
    if (substr($dir, -1) == '/') {
        $dir = substr($dir, 0, strlen($dir) - 1);
    }

    /* If necessary, populate the hash dir variable. */
    if ($hash_dirs == '') {
        $hash_dirs = computeHashDirs($username);
    }

    /* Make sure the full hash directory exists. */
    $real_hash_dir = $dir;
    for ($h = 0; $h < $dir_hash_level; ++$h) {
        $real_hash_dir .= '/' . $hash_dirs[$h];
        if (!@is_dir($real_hash_dir)) {
//FIXME: When safe_mode is turned on, the error suppression below makes debugging safe_mode UID/GID restrictions tricky... for now, I will add a check in configtest
            if (!@mkdir($real_hash_dir, 0770)) {
                error_box ( sprintf(_("Error creating directory %s."), $real_hash_dir) . "\n" .
                     _("Could not create hashed directory structure!") . "\n" .
                     _("Please contact your system administrator and report this error.") );
                exit;
            }
        }
    }

    /* And return that directory. */
    return ($real_hash_dir);
}

/**
 * Helper function for getHashDir which does the actual hash calculation.
 *
 * Uses a crc32 algorithm by default, but if you put
 * the following in config/config_local.php, you can
 * force md5 instead:
 *    $hash_dirs_use_md5 = TRUE;
 *
 * You may also specify that if usernames are in full
 * email address format, the domain part (beginning
 * with "@") be stripped before calculating the crc
 * or md5.  Do that by putting the following in
 * config/config_local.php:
 *    $hash_dirs_strip_domain = TRUE;
 *
 * @param string username the username to calculate the hash dir for
 *
 * @return array a list of hash dirs for this username
 *
 * @since 1.2.0
 *
 */
function computeHashDirs($username) {

    global $hash_dirs_use_md5, $hash_dirs_strip_domain;
    static $hash_dirs = array();


    // strip domain from username
    if ($hash_dirs_strip_domain)
        $user = substr($username, 0, strpos($username, '@'));
    else
        $user = $username;


    // have we already calculated it?
    if (!empty($hash_dirs[$user]))
        return $hash_dirs[$user];


    if ($hash_dirs_use_md5) {

        $hash = md5($user);
        //$hash = md5bin($user);

    } else {

        /* Compute the hash for this user and extract the hash directories.  */
        /* Note that the crc32() function result will be different on 32 and */
        /* 64 bit systems, thus the hack below.                              */
        $crc = crc32($user);
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }
        $hash = base_convert($crc, 10, 16);
    }


    $my_hash_dirs = array();
    for ($h = 0; $h < 4; ++ $h) {
        $my_hash_dirs[] = substr($hash, $h, 1);
    }

    // Return our array of hash directories
    $hash_dirs[$user] = $my_hash_dirs;
    return ($my_hash_dirs);
}

