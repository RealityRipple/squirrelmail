<?php

/**
 * abook_database.php
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Needs the DB functions
 * Don't display errors here. Error will be set in class constructor function.
 */
@include_once('DB.php');

/**
 * Address book in a database backend
 *
 * Backend for personal/shared address book stored in a database,
 * accessed using the DB-classes in PEAR.
 *
 * IMPORTANT:  The PEAR modules must be in the include path
 * for this class to work.
 *
 * An array with the following elements must be passed to
 * the class constructor (elements marked ? are optional):
 * <pre>
 *   dsn       => database DNS (see PEAR for syntax)
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
     * Constructor
     * @param array $param address book backend options
     */
    function abook_database($param) {
        $this->sname = _("Personal address book");

        /* test if Pear DB class is available and freak out if it is not */
        if (! class_exists('DB')) {
            // same error also in db_prefs.php
            $error  = _("Could not include PEAR database functions required for the database backend.") . "<br />\n";
            $error .= sprintf(_("Is PEAR installed, and is the include path set correctly to find %s?"),
                              '<tt>DB.php</tt>') . "<br />\n";
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

            $this->open(true);
        }
        else {
            return $this->set_error('Invalid argument to constructor');
        }
    }


    /**
     * Open the database.
     * @param bool $new new connection if it is true
     * @return bool
     */
    function open($new = false) {
        $this->error = '';

        /* Return true is file is open and $new is unset */
        if ($this->dbh && !$new) {
            return true;
        }

        /* Close old file, if any */
        if ($this->dbh) {
            $this->close();
        }

        $dbh = DB::connect($this->dsn, true);

        if (DB::isError($dbh)) {
            return $this->set_error(sprintf(_("Database error: %s"),
                                            DB::errorMessage($dbh)));
        }

        $this->dbh = $dbh;
        return true;
    }

    /**
     * Close the file and forget the filehandle
     */
    function close() {
        $this->dbh->disconnect();
        $this->dbh = false;
    }

    /* ========================== Public ======================== */

    /**
     * Search the database
     * @param string $expr search expression
     * @return array search results
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

        /* Make regexp from glob'ed expression  */
        $expr = str_replace('?', '_', $expr);
        $expr = str_replace('*', '%', $expr);
        $expr = $this->dbh->quoteString($expr);
        $expr = "%$expr%";

        $query = sprintf("SELECT * FROM %s WHERE owner='%s' AND " .
                         "(firstname LIKE '%s' OR lastname LIKE '%s')",
                         $this->table, $this->owner, $expr, $expr);
        $res = $this->dbh->query($query);

        if (DB::isError($res)) {
            return $this->set_error(sprintf(_("Database error: %s"),
                                            DB::errorMessage($res)));
        }

        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            array_push($ret, array('nickname'  => $row['nickname'],
                                   'name'      => "$row[firstname] $row[lastname]",
                                   'firstname' => $row['firstname'],
                                   'lastname'  => $row['lastname'],
                                   'email'     => $row['email'],
                                   'label'     => $row['label'],
                                   'backend'   => $this->bnum,
                                   'source'    => &$this->sname));
        }
        return $ret;
    }

    /**
     * Lookup alias
     * @param string $alias alias
     * @return array search results
     */
    function lookup($alias) {
        if (empty($alias)) {
            return array();
        }

        $alias = strtolower($alias);

        if (!$this->open()) {
            return false;
        }

        $query = sprintf("SELECT * FROM %s WHERE owner='%s' AND LOWER(nickname)='%s'",
                         $this->table, $this->owner, $this->dbh->quoteString($alias));

        $res = $this->dbh->query($query);

        if (DB::isError($res)) {
            return $this->set_error(sprintf(_("Database error: %s"),
                                            DB::errorMessage($res)));
        }

        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            return array('nickname'  => $row['nickname'],
                         'name'      => "$row[firstname] $row[lastname]",
                         'firstname' => $row['firstname'],
                         'lastname'  => $row['lastname'],
                         'email'     => $row['email'],
                         'label'     => $row['label'],
                         'backend'   => $this->bnum,
                         'source'    => &$this->sname);
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


        $query = sprintf("SELECT * FROM %s WHERE owner='%s'",
                         $this->table, $this->owner);

        $res = $this->dbh->query($query);

        if (DB::isError($res)) {
            return $this->set_error(sprintf(_("Database error: %s"),
                                            DB::errorMessage($res)));
        }

        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            array_push($ret, array('nickname'  => $row['nickname'],
                                   'name'      => "$row[firstname] $row[lastname]",
                                   'firstname' => $row['firstname'],
                                   'lastname'  => $row['lastname'],
                                   'email'     => $row['email'],
                                   'label'     => $row['label'],
                                   'backend'   => $this->bnum,
                                   'source'    => &$this->sname));
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
            return $this->set_error(_("Addressbook is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

        /* See if user exist already */
        $ret = $this->lookup($userdata['nickname']);
        if (!empty($ret)) {
            return $this->set_error(sprintf(_("User \"%s\" already exists"),$ret['nickname']));
        }

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
         if ($r == DB_OK) {
             return true;
         }

         /* Fail */
         return $this->set_error(sprintf(_("Database error: %s"),
                                         DB::errorMessage($r)));
    }

    /**
     * Delete address
     * @param string $alias alias that has to be deleted
     * @return bool
     */
    function remove($alias) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

        /* Create query */
        $query = sprintf("DELETE FROM %s WHERE owner='%s' AND (",
                         $this->table, $this->owner);

        $sepstr = '';
        while (list($undef, $nickname) = each($alias)) {
            $query .= sprintf("%s nickname='%s' ", $sepstr,
                              $this->dbh->quoteString($nickname));
            $sepstr = 'OR';
        }
        $query .= ')';

        /* Delete entry */
        $r = $this->dbh->simpleQuery($query);
        if ($r == DB_OK) {
            return true;
        }

        /* Fail */
        return $this->set_error(sprintf(_("Database error: %s"),
                                         DB::errorMessage($r)));
    }

    /**
     * Modify address
     * @param string $alias modified alias
     * @param array $userdata new data
     * @return bool
     */
    function modify($alias, $userdata) {
        if (!$this->writeable) {
            return $this->set_error(_("Addressbook is read-only"));
        }

        if (!$this->open()) {
            return false;
        }

         /* See if user exist */
        $ret = $this->lookup($alias);
        if (empty($ret)) {
            return $this->set_error(sprintf(_("User \"%s\" does not exist"),$alias));
        }

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
        if ($r == DB_OK) {
            return true;
        }

        /* Fail */
        return $this->set_error(sprintf(_("Database error: %s"),
                                        DB::errorMessage($r)));
    }
} /* End of class abook_database */

// vim: et ts=4
?>
