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
 * The preferences table should have three columns:
 *    user       char  \  primary
 *    prefkey    char  /  key
 *    prefval    blob
 *
 *   CREATE TABLE userprefs (user CHAR(128) NOT NULL DEFAULT '',
 *                           prefkey CHAR(64) NOT NULL DEFAULT '',
 *                           prefval BLOB NOT NULL DEFAULT '',
 *                           primary key (user,prefkey));
 *
 * Configuration of databasename, username and password is done
 * by using conf.pl or the administrator plugin
 *
 * $Id$
 */

define('SMDB_UNKNOWN', 0);
define('SMDB_MYSQL', 1);
define('SMDB_PGSQL', 2);

include_once('DB.php');
require_once('../config/config.php');

global $prefs_are_cached, $prefs_cache;

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
    var $table = 'userprefs';
    var $user_field = 'user';
    var $key_field = 'prefkey';
    var $val_field = 'prefval';

    var $dbh   = NULL;
    var $error = NULL;
    var $db_type = SMDB_UNKNOWN;

    var $default = Array('chosen_theme' => '../themes/default_theme.php',
                         'show_html_default' => '0');

    function open() {
        global $prefs_dsn, $prefs_table;
        global $prefs_user_field, $prefs_key_field, $prefs_val_field;

        if(isset($this->dbh)) {
            return true;
        }

        if (preg_match('/^mysql/', $prefs_dsn)) {
            $this->db_type = SMDB_MYSQL;
        } elseif (preg_match('/^pgsql/', $prefs_dsn)) {
            $this->db_type = SMDB_PGSQL;
        }

        if (!empty($prefs_table)) {
            $this->table = $prefs_table;
        }
        if (!empty($prefs_user_field)) {
            $this->user_field = $prefs_user_field;
        }
        if (!empty($prefs_key_field)) {
            $this->key_field = $prefs_key_field;
        }
        if (!empty($prefs_val_field)) {
            $this->val_field = $prefs_val_field;
        }
        $dbh = DB::connect($prefs_dsn, true);

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

        if (!$this->open()) {
            return false;
        }
        $query = sprintf("DELETE FROM %s WHERE %s='%s' AND %s='%s'",
                         $this->table,
                         $this->user_field,
                         $this->dbh->quoteString($user),
                         $this->key_field,
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
        if (!$this->open()) {
            return false;
        }
        if ($this->db_type == SMDB_MYSQL) {
            $query = sprintf("REPLACE INTO %s (%s, %s, %s) ".
                             "VALUES('%s','%s','%s')",
                             $this->table,
                             $this->user_field,
                             $this->key_field,
                             $this->val_field,
                             $this->dbh->quoteString($user),
                             $this->dbh->quoteString($key),
                             $this->dbh->quoteString($value));

            $res = $this->dbh->simpleQuery($query);
            if(DB::isError($res)) {
                $this->failQuery($res);
            }
        } elseif ($this->db_type == SMDB_PGSQL) {
            $this->dbh->simpleQuery("BEGIN TRANSACTION");
            $query = sprintf("DELETE FROM %s WHERE %s='%s' AND %s='%s'",
                             $this->table,
                             $this->user_field,
                             $this->dbh->quoteString($user),
                             $this->key_field,
                             $this->dbh->quoteString($key));
            $res = $this->dbh->simpleQuery($query);
            if (DB::isError($res)) {
                $this->dbh->simpleQuery("ROLLBACK TRANSACTION");
                $this->failQuery($res);
            }
            $query = sprintf("INSERT INTO %s (%s, %s, %s) VALUES ('%s', '%s', '%s')",
                             $this->table,
                             $this->user_field,
                             $this->key_field,
                             $this->val_field,
                             $this->dbh->quoteString($user),
                             $this->dbh->quoteString($key),
                             $this->dbh->quoteString($value));
            $res = $this->dbh->simpleQuery($query);
            if (DB::isError($res)) {
                $this->dbh->simpleQuery("ROLLBACK TRANSACTION");
                $this->failQuery($res);
            }
            $this->dbh->simpleQuery("COMMIT TRANSACTION");
        } else {
            $query = sprintf("DELETE FROM %s WHERE %s='%s' AND %s='%s'",
                             $this->table,
                             $this->user_field,
                             $this->dbh->quoteString($user),
                             $this->key_field,
                             $this->dbh->quoteString($key));
            $res = $this->dbh->simpleQuery($query);
            if (DB::isError($res)) {
                $this->failQuery($res);
            }
            $query = sprintf("INSERT INTO %s (%s, %s, %s) VALUES ('%s', '%s', '%s')",
                             $this->table,
                             $this->user_field,
                             $this->key_field,
                             $this->val_field,
                             $this->dbh->quoteString($user),
                             $this->dbh->quoteString($key),
                             $this->dbh->quoteString($value));
            $res = $this->dbh->simpleQuery($query);
            if (DB::isError($res)) {
                $this->failQuery($res);
            }
        }

        return true;
    }

    function fillPrefsCache($user) {
        global $prefs_cache;

        if (!$this->open()) {
            return;
        }

        $prefs_cache = array();
        $query = sprintf("SELECT %s as prefkey, %s as prefval FROM %s ".
                         "WHERE %s = '%s'",
                         $this->key_field,
                         $this->val_field,
                         $this->table,
                         $this->user_field,
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
        if (!$this->open()) {
            return;
        }
        $query = sprintf("SELECT %s, %s as prefkey, %s as prefval FROM %s WHERE %s='%s' ".
                         "AND %s LIKE 'highlight%%' ORDER BY %s",
                         $this->user_field,
                         $this->key_field,
                         $this->val_field,
                         $this->table,
                         $this->user_field,
                         $this->dbh->quoteString($user),
                         $this->key_field,
                         $this->key_field);

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
                $query = sprintf("UPDATE %s SET %s='%s' ".
                                 "WHERE %s ='%s' AND %s='%s'",
                                 $this->table,
                                 $this->key_field,
                                 $this->dbh->quoteString($newkey),
                                 $this->user_field,
                                 $this->dbh->quoteString($user),
                                 $this->key_field,
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

    if (isset($prefs_cache[$string]) && ($prefs_cache[$string] == $set_to)) {
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
function setSig($data_dir, $username, $number, $string) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    if ($number == "g") {
        $key = '___signature___';
    } else {
        $key = sprintf('___sig%s___', $number);
    }
    $db->setKey($username, $key, $string);
    return;
}

/* Gets the signature */
function getSig($data_dir, $username, $number) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    if ($number == "g") {
        $key = '___signature___';
    } else {
        $key = sprintf('___sig%d___', $number);
    }
    return $db->getKey($username, $key);
}

?>
