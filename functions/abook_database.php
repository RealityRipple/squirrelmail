<?php

/**
 * abook_database.php
 *
 * Supported database schema
 * <pre>
 *  owner varchar(128) NOT NULL
 *  nickname varchar(16) NOT NULL
 *  firstname varchar(128) 
 *  lastname varchar(128)
 *  email varchar(128) NOT NULL
 *  label varchar(255)
 *  PRIMARY KEY (owner,nickname)
 * </pre>
 *
 * @copyright 1999-2021 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Needs either PDO or the DB functions
 * Don't display errors here. Error will be set in class constructor function.
 */
global $use_pdo, $disable_pdo;
if (empty($disable_pdo) && class_exists('PDO'))
    $use_pdo = TRUE;
else
    $use_pdo = FALSE;

if (!$use_pdo)
    @include_once('DB.php');

/**
 * Address book in a database backend
 *
 * Backend for personal/shared address book stored in a database,
 * accessed using the DB-classes in PEAR or PDO, the latter taking
 * precedence if available..
 *
 * IMPORTANT:  If PDO is not available (it should be installed by
 *             default since PHP 5.1), then the PEAR modules must
 *             be in the include path for this class to work.
 *
 * An array with the following elements must be passed to
 * the class constructor (elements marked ? are optional):
 * <pre>
 *   dsn       => database DNS (see PEAR for syntax, but more or
 *                less it is:  mysql://user:pass@hostname/dbname)
 *   table     => table to store addresses in (must exist)
 *   owner     => current user (owner of address data)
 * ? name      => name of address book
 * ? writeable => set writeable flag (true/false)
 * ? listing   => enable/disable listing
 * </pre>
 * The table used should have the following columns:
 * owner, nickname, firstname, lastname, email, label
 * The pair (owner,nickname) should be unique (primary key).
 *
 *  NOTE. This class should not be used directly. Use the
 *        "AddressBook" class instead.
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
 *    string $pdo_identifier_quote_char By default, SquirrelMail will quote
 *                                      table and field names in database
 *                                      queries with what it thinks is the
 *                                      appropriate quote character for the
 *                                      database type being used (backtick
 *                                      for MySQL (and thus MariaDB), double
 *                                      quotes for all others), but you can
 *                                      override the character used by
 *                                      putting it here, or tell SquirrelMail
 *                                      NOT to quote identifiers by setting
 *                                      this to "none"
 *
 * @package squirrelmail
 * @subpackage addressbook
 */
class abook_database extends addressbook_backend {
    /**
     * Backend type
     * @var string
     */
    var $btype = 'local';
    /**
     * Backend name
     * @var string
     */
    var $bname = 'database';

    /**
     * Data Source Name (connection description)
     * @var string
     */
    var $dsn       = '';

    /**
     * Character used to quote database table
     * and field names
     * @var string
     */
    var $identifier_quote_char = '';

    /**
     * Table that stores addresses
     * @var string
     */
    var $table     = '';
    /**
     * Owner name
     *
     * Limits list of database entries visible to end user
     * @var string
     */
    var $owner     = '';
    /**
     * Database Handle
     * @var resource
     */
    var $dbh       = false;
    /**
     * Enable/disable writing into address book
     * @var bool
     */
    var $writeable = true;
    /**
     * Enable/disable address book listing
     * @var bool
     */
    var $listing = true;

    /* ========================== Private ======================= */

    /**
     * Constructor (PHP5 style, required in some future version of PHP)
     * @param array $param address book backend options
     */
    function __construct($param) {
        $this->sname = _("Personal Address Book");

        /* test if PDO or Pear DB classes are available and freak out if necessary */
        global $use_pdo;
        if (!$use_pdo && !class_exists('DB')) {
            // same error also in db_prefs.php
            $error  = _("Could not find or include PHP PDO or PEAR database functions required for the database backend.") . "\n";
            $error .= sprintf(_("PDO should come preinstalled with PHP version 5.1 or higher. Otherwise, is PEAR installed, and is the include path set correctly to find %s?"), 'DB.php') . "\n";
            $error .= _("Please contact your system administrator and report this error.");
            return $this->set_error($error);
        }

        if (is_array($param)) {
            if (empty($param['dsn']) ||
                empty($param['table']) ||
                empty($param['owner'])) {
                return $this->set_error('Invalid parameters');
            }

            $this->dsn   = $param['dsn'];
            $this->table = $param['table'];
            $this->owner = $param['owner'];

            if (!empty($param['name'])) {
               $this->sname = $param['name'];
            }

            if (isset($param['writeable'])) {
               $this->writeable = $param['writeable'];
            }

            if (isset($param['listing'])) {
               $this->listing = $param['listing'];
            }

            // figure out identifier quoting (only used for PDO, though we could change that)
            global $pdo_identifier_quote_char;
            if (empty($pdo_identifier_quote_char)) {
                if (strpos($this->dsn, 'mysql') === 0)
                    $this->identifier_quote_char = '`';
                else
                    $this->identifier_quote_char = '"';
            } else if ($pdo_identifier_quote_char === 'none')
                $this->identifier_quote_char = '';
            else
                $this->identifier_quote_char = $pdo_identifier_quote_char;


            $this->open(true);
        }
        else {
            return $this->set_error('Invalid argument to constructor');
        }
    }

    /**
     * Constructor (PHP4 style, kept for compatibility reasons)
     * @param array $param address book backend options
     */
    function abook_database($param) {
        return self::__construct($param);
    }

    /**
     * Open the database.
     * @param bool $new new connection if it is true
     * @return bool
     */
    function open($new = false) {
        global $use_pdo;
        $this->error = '';

        /* Return true is file is open and $new is unset */
        if ($this->dbh && !$new) {
            return true;
        }

        /* Close old file, if any */
        if ($this->dbh) {
            $this->close();
        }

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
            if (!preg_match('|^(.+)://(.+):(.+)@(.+)/(.+)$|i', $this->dsn, $matches)) {
                return $this->set_error(_("Could not parse prefs DSN"));
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
                return $this->set_error(sprintf(_("Database error: %s"), $e->getMessage()));
            }

            $dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        } else {
            $dbh = DB::connect($this->dsn, true);

            if (DB::isError($dbh)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($dbh)));
            }

            /**
             * field names are lowercased.
             * We use unquoted identifiers and they use upper case in Oracle
             */
            $dbh->setOption('portability', DB_PORTABILITY_LOWERCASE);
        }

        $this->dbh = $dbh;
        return true;
    }

    /**
     * Close the file and forget the filehandle
     */
    function close() {
        global $use_pdo;
        if ($use_pdo) {
            $this->dbh = NULL;
        } else {
            $this->dbh->disconnect();
            $this->dbh = false;
        }
    }

    /**
     * Determine internal database field name given one of
     * the SquirrelMail SM_ABOOK_FIELD_* constants
     *
     * @param integer $field The SM_ABOOK_FIELD_* contant to look up
     *
     * @return string The desired field name, or the string "ERROR"
     *                if the $field is not understood (the caller
     *                is responsible for handing errors)
     *
     */
    function get_field_name($field) {
        switch ($field) {
            case SM_ABOOK_FIELD_NICKNAME:
                return 'nickname';
            case SM_ABOOK_FIELD_FIRSTNAME:
                return 'firstname';
            case SM_ABOOK_FIELD_LASTNAME:
                return 'lastname';
            case SM_ABOOK_FIELD_EMAIL:
                return 'email';
            case SM_ABOOK_FIELD_LABEL:
                return 'label';
            default:
                return 'ERROR';
        }
    }

    /* ========================== Public ======================== */

    /**
     * Search the database
     *
     * Backend supports only * and ? wildcards. Complex eregs are not supported.
     * Search is case insensitive.
     * @param string $expr search expression
     * @return array search results. boolean false on error
     */
    function search($expr) {
        $ret = array();
        if(!$this->open()) {
            return false;
        }

        /* To be replaced by advanded search expression parsing */
        if (is_array($expr)) {
            return;
        }

        // don't allow wide search when listing is disabled.
        if ($expr=='*' && ! $this->listing)
            return array();

        /* lowercase expression in order to make it case insensitive */
        $expr = strtolower($expr);

        /* escape SQL wildcards */
        $expr = str_replace('_', '\\_', $expr);
        $expr = str_replace('%', '\\%', $expr);

        /* Convert wildcards to SQL syntax  */
        $expr = str_replace('?', '_', $expr);
        $expr = str_replace('*', '%', $expr);

        $expr = "%$expr%";

        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('SELECT * FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ' = ? AND (LOWER(' . $this->identifier_quote_char . 'firstname' . $this->identifier_quote_char . ') LIKE ? ESCAPE ? OR LOWER(' . $this->identifier_quote_char . 'lastname' . $this->identifier_quote_char . ') LIKE ? ESCAPE ? OR LOWER(' . $this->identifier_quote_char . 'email' . $this->identifier_quote_char . ') LIKE ? ESCAPE ? OR LOWER(' . $this->identifier_quote_char . 'nickname' . $this->identifier_quote_char . ') LIKE ? ESCAPE ?)'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            if (!($res = $sth->execute(array($this->owner, $expr, '\\', $expr, '\\', $expr, '\\', $expr, '\\')))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }

            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                array_push($ret, array('nickname'  => $row['nickname'],
                                       'name'      => $this->fullname($row['firstname'], $row['lastname']),
                                       'firstname' => $row['firstname'],
                                       'lastname'  => $row['lastname'],
                                       'email'     => $row['email'],
                                       'label'     => $row['label'],
                                       'backend'   => $this->bnum,
                                       'source'    => &$this->sname));
            }

        } else {
            $expr = $this->dbh->quoteString($expr);

            /* create escape expression */
            $escape = 'ESCAPE \'' . $this->dbh->quoteString('\\') . '\'';

            $query = sprintf("SELECT * FROM %s WHERE owner='%s' AND " .
                             "(LOWER(firstname) LIKE '%s' %s " .
                             "OR LOWER(lastname) LIKE '%s' %s " .
                             "OR LOWER(email) LIKE '%s' %s " .
                             "OR LOWER(nickname) LIKE '%s' %s)",
                             $this->table, $this->owner, $expr, $escape, $expr, $escape,
                                                         $expr, $escape, $expr, $escape);
            $res = $this->dbh->query($query);

            if (DB::isError($res)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($res)));
            }

            while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                array_push($ret, array('nickname'  => $row['nickname'],
                                       'name'      => $this->fullname($row['firstname'], $row['lastname']),
                                       'firstname' => $row['firstname'],
                                       'lastname'  => $row['lastname'],
                                       'email'     => $row['email'],
                                       'label'     => $row['label'],
                                       'backend'   => $this->bnum,
                                       'source'    => &$this->sname));
            }
        }
        return $ret;
    }

    /**
     * Lookup an address by the indicated field.
     *
     * @param string  $value The value to look up
     * @param integer $field The field to look in, should be one
     *                       of the SM_ABOOK_FIELD_* constants
     *                       defined in include/constants.php
     *                       (OPTIONAL; defaults to nickname field)
     *                       NOTE: uniqueness is only guaranteed
     *                       when the nickname field is used here;
     *                       otherwise, the first matching address
     *                       is returned.
     *
     * @return array Array with lookup results when the value
     *               was found, an empty array if the value was
     *               not found.
     *
     */
    function lookup($value, $field=SM_ABOOK_FIELD_NICKNAME) {
        if (empty($value)) {
            return array();
        }

        $value = strtolower($value);

        if (!$this->open()) {
            return false;
        }

        $db_field = $this->get_field_name($field);
        if ($db_field == 'ERROR') {
            return $this->set_error(sprintf(_("Unknown field name: %s"), $field));
        }

        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('SELECT * FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ' = ? AND LOWER(' . $this->identifier_quote_char . $db_field . $this->identifier_quote_char . ') = ?'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            if (!($res = $sth->execute(array($this->owner, $value)))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }

            if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                return array('nickname'  => $row['nickname'],
                             'name'      => $this->fullname($row['firstname'], $row['lastname']),
                             'firstname' => $row['firstname'],
                             'lastname'  => $row['lastname'],
                             'email'     => $row['email'],
                             'label'     => $row['label'],
                             'backend'   => $this->bnum,
                             'source'    => &$this->sname);
            }

        } else {
            $query = sprintf("SELECT * FROM %s WHERE owner = '%s' AND LOWER(%s) = '%s'",
                             $this->table, $this->owner, $db_field, 
                             $this->dbh->quoteString($value));

            $res = $this->dbh->query($query);

            if (DB::isError($res)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($res)));
            }

            if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                return array('nickname'  => $row['nickname'],
                             'name'      => $this->fullname($row['firstname'], $row['lastname']),
                             'firstname' => $row['firstname'],
                             'lastname'  => $row['lastname'],
                             'email'     => $row['email'],
                             'label'     => $row['label'],
                             'backend'   => $this->bnum,
                             'source'    => &$this->sname);
            }
        }

        return array();
    }

    /**
     * List all addresses
     * @return array search results
     */
    function list_addr() {
        $ret = array();
        if (!$this->open()) {
            return false;
        }

        if(isset($this->listing) && !$this->listing) {
            return array();
        }


        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('SELECT * FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ' = ?'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            if (!($res = $sth->execute(array($this->owner)))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }

            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                array_push($ret, array('nickname'  => $row['nickname'],
                                       'name'      => $this->fullname($row['firstname'], $row['lastname']),
                                       'firstname' => $row['firstname'],
                                       'lastname'  => $row['lastname'],
                                       'email'     => $row['email'],
                                       'label'     => $row['label'],
                                       'backend'   => $this->bnum,
                                       'source'    => &$this->sname));
            }
        } else {
            $query = sprintf("SELECT * FROM %s WHERE owner='%s'",
                             $this->table, $this->owner);

            $res = $this->dbh->query($query);

            if (DB::isError($res)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($res)));
            }

            while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                array_push($ret, array('nickname'  => $row['nickname'],
                                       'name'      => $this->fullname($row['firstname'], $row['lastname']),
                                       'firstname' => $row['firstname'],
                                       'lastname'  => $row['lastname'],
                                       'email'     => $row['email'],
                                       'label'     => $row['label'],
                                       'backend'   => $this->bnum,
                                       'source'    => &$this->sname));
            }
        }

        return $ret;
    }

    /**
     * Add address
     * @param array $userdata added data
     * @return bool
     */
    function add($userdata) {
        if (!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

        // NB: if you want to check for some unwanted characters
        //     or other problems, do so here like this:
        // TODO: Should pull all validation code out into a separate function
        //if (strpos($userdata['nickname'], ' ')) {
        //    return $this->set_error(_("Nickname contains illegal characters"));
        //}

        /* See if user exist already */
        $ret = $this->lookup($userdata['nickname']);
        if (!empty($ret)) {
            return $this->set_error(sprintf(_("User \"%s\" already exists"), $ret['nickname']));
        }

        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('INSERT INTO ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' (' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . 'nickname' . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . 'firstname' . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . 'lastname' . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . 'email' . $this->identifier_quote_char . ', ' . $this->identifier_quote_char . 'label' . $this->identifier_quote_char . ') VALUES (?, ?, ?, ?, ?, ?)'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            if (!($res = $sth->execute(array($this->owner, $userdata['nickname'], $userdata['firstname'], (!empty($userdata['lastname']) ? $userdata['lastname'] : ''), $userdata['email'], (!empty($userdata['label']) ? $userdata['label'] : ''))))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }
        } else {
            /* Create query */
            $query = sprintf("INSERT INTO %s (owner, nickname, firstname, " .
                             "lastname, email, label) VALUES('%s','%s','%s'," .
                             "'%s','%s','%s')",
                             $this->table, $this->owner,
                             $this->dbh->quoteString($userdata['nickname']),
                             $this->dbh->quoteString($userdata['firstname']),
                             $this->dbh->quoteString((!empty($userdata['lastname'])?$userdata['lastname']:'')),
                             $this->dbh->quoteString($userdata['email']),
                             $this->dbh->quoteString((!empty($userdata['label'])?$userdata['label']:'')) );

            /* Do the insert */
            $r = $this->dbh->simpleQuery($query);

            /* Check for errors */
            if (DB::isError($r)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($r)));
            }
        }

         return true;
    }

    /**
     * Deletes address book entries
     * @param array $alias aliases that have to be deleted. numerical 
     *  array with nickname values
     * @return bool
     */
    function remove($alias) {
        if (!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            $sepstr = '';
            $where_clause = '';
            $where_clause_args = array();
            foreach ($alias as $nickname) {
                $where_clause .= $sepstr . $this->identifier_quote_char . 'nickname' . $this->identifier_quote_char . ' = ?';
                $where_clause_args[] = $nickname;
                $sepstr = ' OR ';
            }
            if (!($sth = $this->dbh->prepare('DELETE FROM ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' WHERE ' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ' = ? AND (' . $where_clause . ')'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            array_unshift($where_clause_args, $this->owner);
            if (!($res = $sth->execute($where_clause_args))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }
        } else {
            /* Create query */
            $query = sprintf("DELETE FROM %s WHERE owner='%s' AND (",
                             $this->table, $this->owner);

            $sepstr = '';
            foreach ($alias as $nickname) {
                $query .= sprintf("%s nickname='%s' ", $sepstr,
                                  $this->dbh->quoteString($nickname));
                $sepstr = 'OR';
            }
            $query .= ')';

            /* Delete entry */
            $r = $this->dbh->simpleQuery($query);

            /* Check for errors */
            if (DB::isError($r)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($r)));
            }
        }

        return true;
    }

    /**
     * Modify address
     * @param string $alias modified alias
     * @param array $userdata new data
     * @return bool
     */
    function modify($alias, $userdata) {
        if (!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

        // NB: if you want to check for some unwanted characters
        //     or other problems, do so here like this:
        // TODO: Should pull all validation code out into a separate function
        //if (strpos($userdata['nickname'], ' ')) {
        //    return $this->set_error(_("Nickname contains illegal characters"));
        //}

         /* See if user exist */
        $ret = $this->lookup($alias);
        if (empty($ret)) {
            return $this->set_error(sprintf(_("User \"%s\" does not exist"),$alias));
        }

        /* make sure that new nickname is not used */
        if (strtolower($alias) != strtolower($userdata['nickname'])) {
            /* same check as in add() */
            $ret = $this->lookup($userdata['nickname']);
            if (!empty($ret)) {
                $error = sprintf(_("User '%s' already exist."), $ret['nickname']);
                return $this->set_error($error);
            }
        }

        global $use_pdo, $pdo_show_sql_errors;
        if ($use_pdo) {
            if (!($sth = $this->dbh->prepare('UPDATE ' . $this->identifier_quote_char . $this->table . $this->identifier_quote_char . ' SET ' . $this->identifier_quote_char . 'nickname' . $this->identifier_quote_char . ' = ?, ' . $this->identifier_quote_char . 'firstname' . $this->identifier_quote_char . ' = ?, ' . $this->identifier_quote_char . 'lastname' . $this->identifier_quote_char . ' = ?, ' . $this->identifier_quote_char . 'email' . $this->identifier_quote_char . ' = ?, ' . $this->identifier_quote_char . 'label' . $this->identifier_quote_char . ' = ? WHERE ' . $this->identifier_quote_char . 'owner' . $this->identifier_quote_char . ' = ? AND ' . $this->identifier_quote_char . 'nickname' . $this->identifier_quote_char . ' = ?'))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $this->dbh->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not prepare query")));
            }
            if (!($res = $sth->execute(array($userdata['nickname'], $userdata['firstname'], (!empty($userdata['lastname']) ? $userdata['lastname'] : ''), $userdata['email'], (!empty($userdata['label']) ? $userdata['label'] : ''), $this->owner, $alias)))) {
                if ($pdo_show_sql_errors)
                    return $this->set_error(sprintf(_("Database error: %s"), implode(' - ', $sth->errorInfo())));
                else
                    return $this->set_error(sprintf(_("Database error: %s"), _("Could not execute query")));
            }
        } else {
            /* Create query */
            $query = sprintf("UPDATE %s SET nickname='%s', firstname='%s', ".
                             "lastname='%s', email='%s', label='%s' ".
                             "WHERE owner='%s' AND nickname='%s'",
                             $this->table,
                             $this->dbh->quoteString($userdata['nickname']),
                             $this->dbh->quoteString($userdata['firstname']),
                             $this->dbh->quoteString((!empty($userdata['lastname'])?$userdata['lastname']:'')),
                             $this->dbh->quoteString($userdata['email']),
                             $this->dbh->quoteString((!empty($userdata['label'])?$userdata['label']:'')),
                             $this->owner,
                             $this->dbh->quoteString($alias) );

            /* Do the insert */
            $r = $this->dbh->simpleQuery($query);

            /* Check for errors */
            if (DB::isError($r)) {
                return $this->set_error(sprintf(_("Database error: %s"),
                                                DB::errorMessage($r)));
            }
        }

        return true;
    }
} /* End of class abook_database */

// vim: et ts=4
