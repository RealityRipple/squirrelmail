<?php

/*
 * db_prefs.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions for manipulating user preferences
 * stored in a database, accessed though the Pear DB layer.
 *
 * To use this instead of the regular prefs.php, create a
 * database as described below, and replace prefs.php
 * with this file.
 *
 * Database:
 * ---------
 *
 * The preferences table should have tree columns:
 *    username   char  \  primary
 *    prefkey    char  /  key
 *    prefval    blob
 *
 *   CREATE TABLE userprefs (user CHAR(32) NOT NULL DEFAULT '',
 *                           prefkey CHAR(64) NOT NULL DEFAULT '',
 *                           prefval BLOB NOT NULL DEFAULT '',
 *                           primary key (user,prefkey));
 *
 * Configuration of databasename, username and password is done
 * by changing $DSN below.
 *
 * $Id$
 */

require_once('DB.php');

global $prefs_are_cached, $prefs_cache;

if ( !session_is_registered('prefs_are_cached') ||
     !isset( $prefs_cache) ||
     !is_array( $prefs_cache) ||
     substr( phpversion(), 0, 3 ) == '4.1' ) {
    $prefs_are_cached = false;
    $prefs_cache = array();
}

function cachePrefValues($username) {
    global $prefs_are_cached, $prefs_cache;

    if ($prefs_are_cached) {
        return;
    }

    session_unregister('prefs_cache');
    session_unregister('prefs_are_cached');

    $db = new dbPrefs;
    if(isset($db->error)) {
        printf( _("Preference database error (%s). Exiting abnormally"),
              $db->error);
        exit;
    }

    $db->fillPrefsCache($username);
    if (isset($db->error)) {
        printf( _("Preference database error (%s). Exiting abnormally"),
              $db->error);
        exit;
    }

    $prefs_are_cached = true;

    session_register('prefs_cache');
    session_register('prefs_are_cached');
}

class dbPrefs {
    var $DSN   = 'mysql://user:passwd@host/database';
    var $table = 'userprefs';

    var $dbh   = NULL;
    var $error = NULL;

    var $default = Array('chosen_theme' => '../themes/default_theme.php',
                         'show_html_default' => '0');

    function open() {
        if(isset($this->dbh)) {
            return true;
        }
        $dbh = DB::connect($this->DSN, true);

        if(DB::isError($dbh) || DB::isWarning($dbh)) {
            $this->error = DB::errorMessage($dbh);
            return false;
        }

        $this->dbh = $dbh;
        return true;
    }

    function failQuery($res = NULL) {
        if($res == NULL) {
            printf(_("Preference database error (%s). Exiting abnormally"),
                  $this->error);
        } else {
            printf(_("Preference database error (%s). Exiting abnormally"),
                  DB::errorMessage($res));
        }
        exit;
    }


    function getKey($user, $key, $default = '') {
        global $prefs_cache;

        cachePrefValues($user);

        if (isset($prefs_cache[$key])) {
            return $prefs_cache[$key];
        } else {
            if (isset($this->default[$key])) {
                return $this->default[$key];
            } else {
                return $default;
            }
        }
    }

    function deleteKey($user, $key) {
        global $prefs_cache;

        $this->open();
        $query = sprintf("DELETE FROM %s WHERE user='%s' AND prefkey='%s'",
                         $this->table,
                         $this->dbh->quoteString($user),
                         $this->dbh->quoteString($key));

        $res = $this->dbh->simpleQuery($query);
        if(DB::isError($res)) {
            $this->failQuery($res);
        }

        unset($prefs_cache[$key]);

        if(substr($key, 0, 9) == 'highlight') {
            $this->renumberHighlightList($user);
        }

        return true;
    }

    function setKey($user, $key, $value) {
        $this->open();
        $query = sprintf("REPLACE INTO %s (user,prefkey,prefval) ".
                         "VALUES('%s','%s','%s')",
                         $this->table,
                         $this->dbh->quoteString($user),
                         $this->dbh->quoteString($key),
                         $this->dbh->quoteString($value));

        $res = $this->dbh->simpleQuery($query);
        if(DB::isError($res)) {
            $this->failQuery($res);
        }

        return true;
    }

    function fillPrefsCache($user) {
        global $prefs_cache;

        $this->open();

        $prefs_cache = array();
        $query = sprintf("SELECT prefkey, prefval FROM %s ".
                         "WHERE user = '%s'",
                         $this->table,
                         $this->dbh->quoteString($user));
        $res = $this->dbh->query($query);
        if (DB::isError($res)) {
            $this->failQuery($res);
        }

        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $prefs_cache[$row['prefkey']] = $row['prefval'];
        }
    }

    /*
     * When a highlight option is deleted the preferences module
     * must renumber the list.  This should be done somewhere else,
     * but it is not, so....
     */
    function renumberHighlightList($user) {
        $this->open();
        $query = sprintf("SELECT * FROM %s WHERE user='%s' ".
                         "AND prefkey LIKE 'highlight%%' ORDER BY prefkey",
                         $this->table,
                         $this->dbh->quoteString($user));

        $res = $this->dbh->query($query);
        if(DB::isError($res)) {
            $this->failQuery($res);
        }

        /* Store old data in array */
        $rows = Array();
        while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $rows[] = $row;
        }

        /* Renumber keys of old data */
        $hilinum = 0;
        for($i = 0; $i < count($rows) ; $i++) {
            $oldkey = $rows[$i]['prefkey'];
            $newkey = substr($oldkey, 0, 9) . $hilinum;
            $hilinum++;

            if($oldkey != $newkey) {
                $query = sprintf("UPDATE %s SET prefkey='%s' ".
                                 "WHERE user ='%s' AND prefkey='%s'",
                                 $this->table,
                                 $this->dbh->quoteString($newkey),
                                 $this->dbh->quoteString($user),
                                 $this->dbh->quoteString($oldkey));

                $res = $this->dbh->simpleQuery($query);
                if(DB::isError($res)) {
                    $this->failQuery($res);
                }
            }
        }

        return;
    }

} /* end class dbPrefs */


/* returns the value for the pref $string */
function getPref($data_dir, $username, $string, $default = '') {
    $db = new dbPrefs;
    if(isset($db->error)) {
        printf( _("Preference database error (%s). Exiting abnormally"),
              $db->error);
        exit;
    }

    return $db->getKey($username, $string, $default);
}

/* Remove the pref $string */
function removePref($data_dir, $username, $string) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    $db->deleteKey($username, $string);
    return;
}

/* sets the pref, $string, to $set_to */
function setPref($data_dir, $username, $string, $set_to) {
    global $prefs_cache;

    if (isset($prefs_cache[$string]) && ($prefs_cache[$string] == $value)) {
        return;
    }

    if ($set_to == '') {
        removePref($data_dir, $username, $string);
        return;
    }

    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    $db->setKey($username, $string, $set_to);
    $prefs_cache[$string] = $set_to;
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_BAIL, 1);
    assert ('$set_to == $prefs_cache[$string]');

    return;
}

/* This checks if the prefs are available */
function checkForPrefs($data_dir, $username) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }
}

/* Writes the Signature */
function setSig($data_dir, $username, $string) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    $db->setKey($username, '___signature___', $string);
    return;
}

/* Gets the signature */
function getSig($data_dir, $username) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    return $db->getKey($username, '___signature___');
}

/* Hashing functions */

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
     
    /* Set the value of our real data file. */
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
                echo sprintf(_("Error creating directory %s."), $real_hash_dir) . '<br>';
                echo _("Could not create hashed directory structure!") . "<br>\n";
                echo _("Please contact your system administrator and report this error.") . "<br>\n";
                exit;
            }
        }
    }

    /* And return that directory. */
    return ($real_hash_dir);
}

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
