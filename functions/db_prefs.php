<?php

/**
 * db_prefs.php
 *
 * This contains functions for manipulating user preferences
 * stored in a database, accessed through the Pear DB layer
 * or PDO, the latter taking precedence if available.
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
 * Three settings that control PDO behavior can be specified in
 * config/config_local.php if needed:
 *    boolean $disable_pdo SquirrelMail uses PDO by default to access the
 *                         user preferences and address book databases, but
 *                         setting this to TRUE will cause SquirrelMail to
 *                         fall back to using Pear DB instead.
 *    boolean $pdo_show_sql_errors When database errors are encountered,
 *                                 setting this to TRUE causes the actual
 *                                 database error to be displayed, otherwise
 *                                 generic errors are displayed, preventing
 *                                 internal database information from being
 *                                 exposed. This should be enabled only for
 *                                 debugging purposes.
 *    string $db_identifier_quote_char By default, SquirrelMail will quote
 *                                     table and field names in database
 *                                     queries with what it thinks is the
 *                                     appropriate quote character for the
 *                                     database type being used (backtick
 *                                     for MySQL (and thus MariaDB), double
 *                                     quotes for all others), but you can
 *                                     override the character used by
 *                                     putting it here, or tell SquirrelMail
 *                                     NOT to quote identifiers by setting
 *                                     this to "none"
 *
 * @copyright 1999-2022 The SquirrelMail Project Team
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
 * Needs either PDO or the DB functions
 * Don't display errors here. (no code execution in functions/*.php).
 * will handle error in dbPrefs class.
 */
global $use_pdo, $disable_pdo;
if (empty($disable_pdo) && class_exists('PDO'))
    $use_pdo = TRUE;
else
    $use_pdo = FALSE;

if (!$use_pdo)
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
     * Character used to quote database table
     * and field names
     * @var string
     */
    var $identifier_quote_char = '';

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
     * Constructor (PHP5 style, required in some future version of PHP)
     * initialize the default preferences array.
     *
     */
    function __construct() {
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
     * Constructor (PHP4 style, kept for compatibility reasons)
     * initialize the default preferences array.
     *
     */
    function dbPrefs() {
       self::__construct();
    }

    /**
     * initialize DB connection object
     *
     * @return boolean true, if object is initialized
     *
     */
    function open() {
        global $prefs_dsn, $prefs_table, $use_pdo, $db_identifier_quote_char;
        global $prefs_user_field, $prefs_key_field, $prefs_val_field;
        global $prefs_user_size, $prefs_key_size, $prefs_val_size;

        /* test if PDO or Pear DB classes are available and freak out if necessary */
        if (!$use_pdo && !class_exists('DB')) {
            // same error also in abook_database.php
            $error  = _("Could not find or include PHP PDO or PEAR database functions required for the database backend.") . "\n";
            $error .= sprintf(_("PDO should come preinstalled with PHP version 5.1 or higher. Otherwise, is PEAR installed, and is the include path set correctly to find %s?"), 'DB.php') . "\n";
            $error .= _("Please contact your system administrator and report this error.");
            return false;
        }

        if(isset($this->dbh)) {
            return true;
        }

        if (strpos($prefs_dsn, 'mysql') === 0) {
            $this->db_type = SMDB_MYSQL;
        } else if (strpos($prefs_dsn, 'pgsql') === 0) {
            $this->db_type = SMDB_PGSQL;
        }

        // figure out identifier quoting
        if (empty($db_identifier_quote_char)) {
            if ($this->db_type == SMDB_MYSQL)
                $this->identifier_quote_char = '`';
            else
                $this->identifier_quote_char = '"';
        } else if ($db_identifier_quote_char === 'none')
            $this->identifier_quote_char = '';
        else
            $this->identifier_quote_char = $db_identifier_quote_char;

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
        if (!empty($prefs_user_size)) {
            $this->user_size = (int) $prefs_user_size;
        }
        if (!empty($prefs_key_size)) {
            $this->key_size = (int) $prefs_key_size;
        }
        if (!empty($prefs_val_size)) {
            $this->val_size = (int) $prefs_val_size;
        }

        // connect, create database connection object
        //
        if ($use_pdo) {
            // parse and convert DSN to PDO style
            // Pear's full DSN syntax is one of the following:
            //    phptype(dbsyntax)://username:password@protocol+hostspec/database?option=value
            //    phptype(syntax)://user:pass@protocol(proto_opts)/database
            //
            // $matches will contain:
            // 1: database type
            // 2: username
            // 3: password
            // 4: hostname (and possible port number) OR protocol (and possible protocol options)
            // 5: database name (and possible options)
            // 6: port number (moved from match number 4)
            // 7: options (moved from match number 5)
            // 8: protocol (instead of hostname)
            // 9: protocol options (moved from match number 4/8)
//TODO: do we care about supporting cases where no password is given? (this is a legal DSN, but causes an error below)
            if (!preg_match('|^(.+)://(.+):(.+)@(.+)/(.+)$|i', $prefs_dsn, $matches)) {
                $this->error = _("Could not parse prefs DSN");
                return false;
            }
            $matches[6] = NULL;
            $matches[7] = NULL;
            $matches[8] = NULL;
            $matches[9] = NULL;
            if (preg_match('|^(.+):(\d+)$|', $matches[4], $host_port_matches)) {
                $matches[4] = $host_port_matches[1];
                $matches[6] = $host_port_matches[2];
            } 
            if (preg_match('|^(.+?)\((.+)\)$|', $matches[4], $protocol_matches)) {
                $matches[8] = $protocol_matches[1];
                $matches[9] = $protocol_matches[2];
                $matches[4] = NULL;
                $matches[6] = NULL;
            } 
//TODO: currently we just ignore options specified on the end of the DSN
            if (preg_match('|^(.+?)\?(.+)$|', $matches[5], $database_name_options_matches)) {
                $matches[5] = $database_name_options_matches[1];
                $matches[7] = $database_name_options_matches[2];
            } 
            if ($matches[8] === 'unix' && !empty($matches[9]))
                $pdo_prefs_dsn = $matches[1] . ':unix_socket=' . $matches[9] . ';dbname=' . $matches[5];
            else
                $pdo_prefs_dsn = $matches[1] . ':host=' . $matches[4] . (!empty($matches[6]) ? ';port=' . $matches[6] : '') . ';dbname=' . $matches[5];
            try {
                $dbh = new PDO($pdo_prefs_dsn, $matches[2], $matches[3]);
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                return false;
            }
        } else {
            $dbh = DB::connect($prefs_dsn, true);

            if(DB::isError($dbh)) {
                $this->error = DB::errorMessage($dbh);
                return false;
            }
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
        global $use_pdo;
        if($res == NULL) {
            printf(_("Preference database error (%s). Exiting abnormally"),
                  $this->error);
        } else {
            printf(_("Preference database error (%s). Exiting abnormally"),
                  ($use_pdo ? implode(' - ', $res->errorInfo()) : DB::errorMessage($res)));
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
        global $prefs_cache, $use_pdo, $pdo_show_sql_errors;

        if (!$this->open()) {
            return false;
        }
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('DELETE FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ' = ? AND ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ' = ?'))) {
                if ($pdo_show_sql_errors)
                    $this->error = implode(' - ', $this->dbh->errorInfo());
                else
                    $this->error = _("Could not prepare query");
                $this->failQuery();
            }
            if (!($res = $sth->execute(array($user, $key)))) {
                if ($pdo_show_sql_errors)
                    $this->error = implode(' - ', $sth->errorInfo());
                else
                    $this->error = _("Could not execute query");
                $this->failQuery();
            }
        } else {
            $query = sprintf("DELETE FROM %s%s%s WHERE %s%s%s='%s' AND %s%s%s='%s'",
                             $this->identifier_quote_char,
                             $this->table,
                             $this->identifier_quote_char,
                             $this->identifier_quote_char,
                             $this->user_field,
                             $this->identifier_quote_char,
                             $this->dbh->quoteString($user),
                             $this->identifier_quote_char,
                             $this->key_field,
                             $this->identifier_quote_char,
                             $this->dbh->quoteString($key));

            $res = $this->dbh->simpleQuery($query);
            if(DB::isError($res)) {
                $this->failQuery($res);
            }
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
        global $use_pdo, $pdo_show_sql_errors;
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
            if ($use_pdo) {
                if (!($sth = $this->dbh->prepare('REPLACE INTO ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' (' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->val_field . $this->identifier_quote_char . ') VALUES (?, ?, ?)'))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not prepare query");
                    $this->failQuery();
                }
                if (!($res = $sth->execute(array($user, $key, $value)))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $sth->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->failQuery();
                }
            } else {
                $query = sprintf("REPLACE INTO %s%s%s (%s%s%s, %s%s%s, %s%s%s) ".
                                 "VALUES('%s','%s','%s')",
                                 $this->identifier_quote_char,
                                 $this->table,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->user_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->key_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->val_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($user),
                                 $this->dbh->quoteString($key),
                                 $this->dbh->quoteString($value));

                $res = $this->dbh->simpleQuery($query);
                if(DB::isError($res)) {
                    $this->failQuery($res);
                }
            }
        } elseif ($this->db_type == SMDB_PGSQL) {
            if ($use_pdo) {
                if ($this->dbh->exec('BEGIN TRANSACTION') === FALSE) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->failQuery();
                }
                if (!($sth = $this->dbh->prepare('DELETE FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ' = ? AND ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ' = ?'))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not prepare query");
                    $this->failQuery();
                }
                if (!($res = $sth->execute(array($user, $key)))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $sth->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->dbh->exec('ROLLBACK TRANSACTION');
                    $this->failQuery();
                }
                if (!($sth = $this->dbh->prepare('INSERT INTO ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' (' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->val_field . $this->identifier_quote_char . ') VALUES (?, ?, ?)'))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not prepare query");
                    $this->failQuery();
                }
                if (!($res = $sth->execute(array($user, $key, $value)))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $sth->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->dbh->exec('ROLLBACK TRANSACTION');
                    $this->failQuery();
                }
                if ($this->dbh->exec('COMMIT TRANSACTION') === FALSE) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->failQuery();
                }
            } else {
                $this->dbh->simpleQuery("BEGIN TRANSACTION");
                $query = sprintf("DELETE FROM %s%s%s WHERE %s%s%s='%s' AND %s%s%s='%s'",
                                 $this->identifier_quote_char,
                                 $this->table,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->user_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($user),
                                 $this->identifier_quote_char,
                                 $this->key_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($key));
                $res = $this->dbh->simpleQuery($query);
                if (DB::isError($res)) {
                    $this->dbh->simpleQuery("ROLLBACK TRANSACTION");
                    $this->failQuery($res);
                }
                $query = sprintf("INSERT INTO %s%s%s (%s%s%s, %s%s%s, %s%s%s) VALUES ('%s', '%s', '%s')",
                                 $this->identifier_quote_char,
                                 $this->table,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->user_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->key_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->val_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($user),
                                 $this->dbh->quoteString($key),
                                 $this->dbh->quoteString($value));
                $res = $this->dbh->simpleQuery($query);
                if (DB::isError($res)) {
                    $this->dbh->simpleQuery("ROLLBACK TRANSACTION");
                    $this->failQuery($res);
                }
                $this->dbh->simpleQuery("COMMIT TRANSACTION");
            }
        } else {
            if ($use_pdo) {
                if (!($sth = $this->dbh->prepare('DELETE FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ' = ? AND ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ' = ?'))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not prepare query");
                    $this->failQuery();
                }
                if (!($res = $sth->execute(array($user, $key)))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $sth->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->failQuery();
                }
                if (!($sth = $this->dbh->prepare('INSERT INTO ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' (' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . $this->val_field . $this->identifier_quote_char . ') VALUES (?, ?, ?)'))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $this->dbh->errorInfo());
                    else
                        $this->error = _("Could not prepare query");
                    $this->failQuery();
                }
                if (!($res = $sth->execute(array($user, $key, $value)))) {
                    if ($pdo_show_sql_errors)
                        $this->error = implode(' - ', $sth->errorInfo());
                    else
                        $this->error = _("Could not execute query");
                    $this->failQuery();
                }
            } else {
                $query = sprintf("DELETE FROM %s%s%s WHERE %s%s%s='%s' AND %s%s%s='%s'",
                                 $this->identifier_quote_char,
                                 $this->table,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->user_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($user),
                                 $this->identifier_quote_char,
                                 $this->key_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($key));
                $res = $this->dbh->simpleQuery($query);
                if (DB::isError($res)) {
                    $this->failQuery($res);
                }
                $query = sprintf("INSERT INTO %s%s%s (%s%s%s, %s%s%s, %s%s%s) VALUES ('%s', '%s', '%s')",
                                 $this->identifier_quote_char,
                                 $this->table,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->user_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->key_field,
                                 $this->identifier_quote_char,
                                 $this->identifier_quote_char,
                                 $this->val_field,
                                 $this->identifier_quote_char,
                                 $this->dbh->quoteString($user),
                                 $this->dbh->quoteString($key),
                                 $this->dbh->quoteString($value));
                $res = $this->dbh->simpleQuery($query);
                if (DB::isError($res)) {
                    $this->failQuery($res);
                }
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
        global $prefs_cache, $use_pdo, $pdo_show_sql_errors;

        if (!$this->open()) {
            return;
        }

        $prefs_cache = array();
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('SELECT ' . $this->identifier_quote_char . $this->key_field . $this->identifier_quote_char . ' AS prefkey, ' . $this->identifier_quote_char . $this->val_field . $this->identifier_quote_char . ' AS prefval FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . $this->user_field . $this->identifier_quote_char . ' = ?'))) {
                if ($pdo_show_sql_errors)
                    $this->error = implode(' - ', $this->dbh->errorInfo());
                else
                    $this->error = _("Could not prepare query");
                $this->failQuery();
            }
            if (!($res = $sth->execute(array($user)))) {
                if ($pdo_show_sql_errors)
                    $this->error = implode(' - ', $sth->errorInfo());
                else
                    $this->error = _("Could not execute query");
                $this->failQuery();
            }

            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $prefs_cache[$row['prefkey']] = $row['prefval'];
            }
        } else {
            $query = sprintf("SELECT %s%s%s as prefkey, %s%s%s as prefval FROM %s%s%s ".
                             "WHERE %s%s%s = '%s'",
                             $this->identifier_quote_char,
                             $this->key_field,
                             $this->identifier_quote_char,
                             $this->identifier_quote_char,
                             $this->val_field,
                             $this->identifier_quote_char,
                             $this->identifier_quote_char,
                             $this->table,
                             $this->identifier_quote_char,
                             $this->identifier_quote_char,
                             $this->user_field,
                             $this->identifier_quote_char,
                             $this->dbh->quoteString($user));
            $res = $this->dbh->query($query);
            if (DB::isError($res)) {
                $this->failQuery($res);
            }

            while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                $prefs_cache[$row['prefkey']] = $row['prefval'];
            }
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
