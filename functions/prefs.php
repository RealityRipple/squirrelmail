<?php

/**
 * prefs.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions for manipulating user preferences
 *
 * $Id$
 * @package squirrelmail
 */

/** Include global.php */
require_once(SM_PATH . 'functions/global.php');

sqgetGlobalVar('prefs_cache', $prefs_cache, SQ_SESSION );
sqgetGlobalVar('prefs_are_cached', $prefs_are_cached, SQ_SESSION );

$rg = ini_get('register_globals');

/* if php version >= 4.1 OR (4.0 AND $rg = off) */
if ( !sqsession_is_registered('prefs_are_cached') ||
     !isset( $prefs_cache) ||
     !is_array( $prefs_cache) ||
     check_php_version(4,1) ||
     empty($rg)
   ) {
    $prefs_are_cached = false;
    $prefs_cache = array();
}

if (isset($prefs_backend) && file_exists(SM_PATH . $prefs_backend)) {
    require_once(SM_PATH . $prefs_backend);
} elseif (isset($prefs_dsn) && !empty($prefs_dsn)) {
    require_once(SM_PATH . 'functions/db_prefs.php');
} else {
    require_once(SM_PATH . 'functions/file_prefs.php');
}

/* Hashing functions */

/**
 * Given a username and datafilename, this will return the path to the
 * hashed location of that datafile.
 *
 * @param string username the username of the current user
 * @param string dir the squirrelmail datadir
 * @param string datafile the name of the file to open
 * @param bool hash_seach default true
 * @return string the hashed location of datafile
 */
function getHashedFile($username, $dir, $datafile, $hash_search = true) {
    global $dir_hash_level;

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
 * @param string dir the squirrelmail datadir
 * @param string hash_dirs default ''
 * @return the path to the hash dir for username
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
            if (!@mkdir($real_hash_dir, 0770)) {
                echo sprintf(_("Error creating directory %s."), $real_hash_dir) . '<br>' .
                     _("Could not create hashed directory structure!") . "<br>\n" .
                     _("Please contact your system administrator and report this error.") . "<br>\n";
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
 * @param string username the username to calculate the hash dir for
 * @return array a list of hash dirs for this username
 */
function computeHashDirs($username) {
    /* Compute the hash for this user and extract the hash directories. */
    $hash = base_convert(crc32($username), 10, 16);
    $hash_dirs = array();
    for ($h = 0; $h < 4; ++ $h) {
        $hash_dirs[] = substr($hash, $h, 1);
    }

    /* Return our array of hash directories. */
    return ($hash_dirs);
}

?>
