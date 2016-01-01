<?php

/**
 * db_prefs.php
 *
 * This contains functions for manipulating user preferences
 * stored in a database, accessed though the Pear DB layer.
 *
 * Database:
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
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 * @since 1.1.3
 */

/** @ignore */
if (!defined('SM_PATH')) define('SM_PATH','../');

/** Unknown database */
define('SMDB_UNKNOWN', 0);
/** MySQL */
define('SMDB_MYSQL', 1);
/** PostgreSQL */
define('SMDB_PGSQL', 2);

/**
 * don't display errors (no code execution in functions/*.php).
 * will handle error in dbPrefs class.
 */
@include_once('DB.php');

global $prefs_are_cached, $prefs_cache;

/**
 * @ignore
 */
function cachePrefValues($username) {
    global $prefs_are_cached, $prefs_cache;

    sqgetGlobalVar('prefs_are_cached', $prefs_are_cached, SQ_SESSION );
    if ($prefs_are_cached) {
        sqgetGlobalVar('prefs_cache', $prefs_cache, SQ_SESSION );
        return;
    }

    sqsession_unregister('prefs_cache');
    sqsession_unregister('prefs_are_cached');

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

    sqsession_register($prefs_cache, 'prefs_cache');
    sqsession_register($prefs_are_cached, 'prefs_are_cached');
}

/**
 * Class used to handle connections to prefs database and operations with preferences
 *
 * @package squirrelmail
 * @subpackage prefs
 * @since 1.1.3
 *
 */
class dbPrefs {
    /**
     * Table used to store preferences
     * @var string
     */
    var $table = 'userprefs';

    /**
     * Field used to store owner of preference
     * @var string
     */
    var $user_field = 'user';

    /**
     * Field used to store preference name
     * @var string
     */
    var $key_field = 'prefkey';

    /**
     * Field used to store preference value
     * @var string
     */
    var $val_field = 'prefval';

    /**
     * Database connection object
     * @var object
     */
    var $dbh   = NULL;

    /**
     * Error messages
     * @var string
     */
    var $error = NULL;

    /**
     * Database type (SMDB_* constants)
     * Is used in setKey().
     * @var integer
     */
    var $db_type = SMDB_UNKNOWN;

    /**
     * Default preferences
     * @var array
     */
    var $default = Array('theme_default' => 0,
                         'include_self_reply_all' => '0',
                         'do_not_reply_to_self' => '1',
                         'show_html_default' => '0');

    /**
     * Preference owner field size
     * @var integer
     * @since 1.5.1
     */
    var $user_size = 128;

    /**
     * Preference key field size
     * @var integer
     * @since 1.5.1
     */
    var $key_size = 64;

    /**
     * Preference value field size
     * @var integer
     * @since 1.5.1
     */
    var $val_size = 65536;



    /**
     * initialize the default preferences array.
     *
     */
    function dbPrefs() {
        // Try and read the default preferences file.
        $default_pref = SM_PATH . 'config/default_pref';
        if (@file_exists($default_pref)) {
            if ($file = @fopen($default_pref, 'r')) {
                while (!feof($file)) {
                    $pref = fgets($file, 1024);
                    $i = strpos($pref, '=');
                    if ($i > 0) {
                        $this->default[trim(substr($pref, 0, $i))] = trim(substr($pref, $i + 1));
                    }
                }
                fclose($file);
            }
        }
    }

    /**
     * initialize DB connection object
     *
     * @return boolean true, if object is initialized
     *
     */
    function open() {
        global $prefs_dsn, $prefs_table;
        global $prefs_user_field, $prefs_key_field, $prefs_val_field;
        global $prefs_user_size, $prefs_key_size, $prefs_val_size;

        /* test if Pear DB class is available and freak out if it is not */
        if (! class_exists('DB')) {
            // same error also in abook_database.php
            $this->error  = _("Could not include PEAR database functions required for the database backend.") . "\n";
            $this->error .= sprintf(_("Is PEAR installed, and is the include path set correctly to find %s?"),
                              'DB.php') . "\n";
            $this->error .= _("Please contact your system administrator and report this error.");
            return false;
        }

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

        // the default user field is "user", which in PostgreSQL
        // is an identifier and causes errors if not escaped
        //
        if ($this->db_type == SMDB_PGSQL) {
           $this->user_field = '"' . $this->user_field . '"';
        }

        if (!empty($prefs_key_field)) {
            $this->key_field = $prefs_key_field;
        }
        if (!empty($prefs_val_field)) {
            $this->val_field = $prefs_val_field;
        }
        if (!empty($prefs_user_size)) {
            $this->user_size = (int) $prefs_user_size;
        }
        if (!empty($prefs_key_size)) {
            $this->key_size = (int) $prefs_key_size;
        }
        if (!empty($prefs_val_size)) {
            $this->val_size = (int) $prefs_val_size;
        }
        $dbh = DB::connect($prefs_dsn, true);

        if(DB::isError($dbh)) {
            $this->error = DB::errorMessage($dbh);
            return false;
        }

        $this->dbh = $dbh;
        return true;
    }

    /**
     * Function used to handle database connection errors
     *
     * @param object PEAR Error object
     *
     */
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

    /**
     * Get user's prefs setting
     *
     * @param string $user user name
     * @param string $key preference name
     * @param mixed $default (since 1.2.5) default value
     *
     * @return mixed preference value
     *
     */
    function getKey($user, $key, $default = '') {
        global $prefs_cache;

        $temp = array(&$user, &$key);
        $result = do_hook('get_pref_override', $temp);
        if (is_null($result)) {
            cachePrefValues($user);

            if (isset($prefs_cache[$key])) {
                $result = $prefs_cache[$key];
            } else {
//FIXME: is there a justification for having two prefs hooks so close?  who uses them?
                $temp = array(&$user, &$key);
                $result = do_hook('get_pref', $temp);
                if (is_null($result)) {
                    if (isset($this->default[$key])) {
                        $result = $this->default[$key];
                    } else {
                        $result = $default;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Delete user's prefs setting
     *
     * @param string $user user name
     * @param string $key  preference name
     *
     * @return boolean
     *
     */
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

        return true;
    }

    /**
     * Set user's preference
     *
     * @param string $user  user name
     * @param string $key   preference name
     * @param mixed  $value preference value
     *
     * @return boolean
     *
     */
    function setKey($user, $key, $value) {
        if (!$this->open()) {
            return false;
        }

        /**
         * Check if username fits into db field
         */
        if (strlen($user) > $this->user_size) {
            $this->error = "Oversized username value."
                ." Your preferences can't be saved."
                ." See the administrator's manual or contact your system administrator.";

            /**
             * Debugging function. Can be used to log all issues that trigger
             * oversized field errors. Function should be enabled in all three
             * strlen checks. See http://www.php.net/error-log
             */
            // error_log($user.'|'.$key.'|'.$value."\n",3,'/tmp/oversized_log');

            // error is fatal
            $this->failQuery(null);
        }
        /**
         * Check if preference key fits into db field
         */
        if (strlen($key) > $this->key_size) {
            $err_msg = "Oversized user's preference key."
                ." Some preferences were not saved."
                ." See the administrator's manual or contact your system administrator.";
            // error is not fatal. Only some preference is not saved.
            trigger_error($err_msg,E_USER_WARNING);
            return false;
        }
        /**
         * Check if preference value fits into db field
         */
        if (strlen($value) > $this->val_size) {
            $err_msg = "Oversized user's preference value."
                ." Some preferences were not saved."
                ." See the administrator's manual or contact your system administrator.";
            // error is not fatal. Only some preference is not saved.
            trigger_error($err_msg,E_USER_WARNING);
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

    /**
     * Fill preference cache array
     *
     * @param string $user user name
     *
     * @since 1.2.3
     *
     */
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

} /* end class dbPrefs */


/**
 * Returns the value for the requested preference
 * @ignore
 */
function getPref($data_dir, $username, $pref_name, $default = '') {
    $db = new dbPrefs;
    if(isset($db->error)) {
        printf( _("Preference database error (%s). Exiting abnormally"),
              $db->error);
        exit;
    }

    return $db->getKey($username, $pref_name, $default);
}

/**
 * Remove the desired preference setting ($pref_name)
 * @ignore
 */
function removePref($data_dir, $username, $pref_name) {
    global $prefs_cache;
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    $db->deleteKey($username, $pref_name);

    if (isset($prefs_cache[$pref_name])) {
        unset($prefs_cache[$pref_name]);
    }

    sqsession_register($prefs_cache , 'prefs_cache');
    return;
}

/**
 * Sets the desired preference setting ($pref_name) to whatever is in $value
 * @ignore
 */
function setPref($data_dir, $username, $pref_name, $value) {
    global $prefs_cache;

    if (isset($prefs_cache[$pref_name]) && ($prefs_cache[$pref_name] == $value)) {
        return;
    }

    if ($value === '') {
        removePref($data_dir, $username, $pref_name);
        return;
    }

    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }

    $db->setKey($username, $pref_name, $value);
    $prefs_cache[$pref_name] = $value;
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_BAIL, 1);
    assert ('$value == $prefs_cache[$pref_name]');
    sqsession_register($prefs_cache , 'prefs_cache');
    return;
}

/**
 * This checks if the prefs are available
 * @ignore
 */
function checkForPrefs($data_dir, $username) {
    $db = new dbPrefs;
    if(isset($db->error)) {
        $db->failQuery();
    }
}

/**
 * Writes the Signature
 * @ignore
 */
function setSig($data_dir, $username, $number, $value) {
    if ($number == "g") {
        $key = '___signature___';
    } else {
        $key = sprintf('___sig%s___', $number);
    }
    setPref($data_dir, $username, $key, $value);
    return;
}

/**
 * Gets the signature
 * @ignore
 */
function getSig($data_dir, $username, $number) {
    if ($number == "g") {
        $key = '___signature___';
    } else {
        $key = sprintf('___sig%d___', $number);
    }
    return getPref($data_dir, $username, $key);
}
