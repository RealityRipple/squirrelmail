<?php

/**
 * addressbook.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Functions and classes for the addressbook system.
 *
 * $Id$
 */

/*
   This is the path to the global site-wide addressbook.
   It looks and feels just like a user's .abook file
   If this is in the data directory, use "$data_dir/global.abook"
   If not, specify the path as though it was accessed from the
   src/ directory ("../global.abook" -> in main directory)

   If you don't want a global site-wide addressbook, comment these
   two lines out.  (They are disabled by default.)

   The global addressbook is unmodifiable by anyone.  You must actually
   use a shell script or whatnot to modify the contents.

  global $data_dir;
  $address_book_global_filename = "$data_dir/global.abook";

  Include backends here.
*/

require_once(SM_PATH . 'functions/abook_local_file.php');
require_once(SM_PATH . 'functions/abook_ldap_server.php');

global $addrbook_dsn;

/* Use this if you wanna have a global address book */
if (isset($address_book_global_filename)) {
    include_once(SM_PATH . 'functions/abook_global_file.php');
}

/* Only load database backend if database is configured */
if(isset($addrbook_dsn) && !empty($addrbook_dsn)) {
  include_once(SM_PATH . 'functions/abook_database.php');
}

/*
   Create and initialize an addressbook object.
   Returns the created object
*/
function addressbook_init($showerr = true, $onlylocal = false) {
    global $data_dir, $username, $ldap_server, $address_book_global_filename;
    global $addrbook_dsn, $addrbook_table;

    /* Create a new addressbook object */
    $abook = new AddressBook;

    /*
        Always add a local backend. We use *either* file-based *or* a
        database addressbook. If $addrbook_dsn is set, the database
        backend is used. If not, addressbooks are stores in files.
    */
    if (isset($addrbook_dsn) && !empty($addrbook_dsn)) {
        /* Database */
        if (!isset($addrbook_table) || empty($addrbook_table)) {
            $addrbook_table = 'address';
        }
        $r = $abook->add_backend('database', Array('dsn' => $addrbook_dsn,
                            'owner' => $username,
                            'table' => $addrbook_table));
        if (!$r && $showerr) {
            echo _("Error initializing addressbook database.");
            exit;
        }
    } else {
        /* File */
        $filename = getHashedFile($username, $data_dir, "$username.abook");
        $r = $abook->add_backend('local_file', Array('filename' => $filename,
                              'create'   => true));
        if(!$r && $showerr) {
            printf( _("Error opening file %s"), $filename );
            exit;
        }

    }

    /* This would be for the global addressbook */
    if (isset($address_book_global_filename)) {
        $r = $abook->add_backend('global_file');
        if (!$r && $showerr) {
            echo _("Error initializing global addressbook.");
            exit;
        }
    }

    if ($onlylocal) {
        return $abook;
    }

    /* Load configured LDAP servers (if PHP has LDAP support) */
    if (isset($ldap_server) && is_array($ldap_server) && function_exists('ldap_connect')) {
        reset($ldap_server);
        while (list($undef,$param) = each($ldap_server)) {
            if (is_array($param)) {
                $r = $abook->add_backend('ldap_server', $param);
                if (!$r && $showerr) {
                    printf( '&nbsp;' . _("Error initializing LDAP server %s:") .
                            "<BR>\n", $param['host']);
                    echo '&nbsp;' . $abook->error;
                    exit;
                }
            }
        }
    }

    /* Return the initialized object */
    return $abook;
}


/*
 *   Had to move this function outside of the Addressbook Class
 *   PHP 4.0.4 Seemed to be having problems with inline functions.
 */    
function addressbook_cmp($a,$b) {

    if($a['backend'] > $b['backend']) {
        return 1;
    } else if($a['backend'] < $b['backend']) {
        return -1;
    }
    
    return (strtolower($a['name']) > strtolower($b['name'])) ? 1 : -1;

}


/*
 * This is the main address book class that connect all the
 * backends and provide services to the functions above.
 *
 */

class AddressBook {

    var $backends    = array();
    var $numbackends = 0;
    var $error       = '';
    var $localbackend = 0;
    var $localbackendname = '';
    
      // Constructor function.
    function AddressBook() {
        $localbackendname = _("Personal address book");
    }

    /*
     * Return an array of backends of a given type,
     * or all backends if no type is specified.
     */
    function get_backend_list($type = '') {
        $ret = array();
        for ($i = 1 ; $i <= $this->numbackends ; $i++) {
            if (empty($type) || $type == $this->backends[$i]->btype) {
                $ret[] = &$this->backends[$i];
            }
        }
        return $ret;
    }


    /*
       ========================== Public ========================

        Add a new backend. $backend is the name of a backend
        (without the abook_ prefix), and $param is an optional
        mixed variable that is passed to the backend constructor.
        See each of the backend classes for valid parameters.
     */
    function add_backend($backend, $param = '') {
        $backend_name = 'abook_' . $backend;
        eval('$newback = new ' . $backend_name . '($param);');
        if(!empty($newback->error)) {
            $this->error = $newback->error;
            return false;
        }

        $this->numbackends++;

        $newback->bnum = $this->numbackends;
        $this->backends[$this->numbackends] = $newback;
        
        /* Store ID of first local backend added */
        if ($this->localbackend == 0 && $newback->btype == 'local') {
            $this->localbackend = $this->numbackends;
            $this->localbackendname = $newback->sname;
        }

        return $this->numbackends;
    }


    /*
     * This function takes a $row array as returned by the addressbook 
     * search and returns an e-mail address with the full name or
     * nickname optionally prepended.
     */

    function full_address($row) {
        global $addrsrch_fullname, $datadir, $user;

        if (($prefix = getPref($datadir, $user, 'addrsrch_fullname') or
            isset($addrsrch_fullname) and $prefix = $addrsrch_fullname)
            and $prefix !== 'noprefix') {
            $name = ($prefix === 'nickname') ? $row['nickname']
                : $row['name'];
            return $name . ' <' . trim($row['email']) . '>';
        } else {
            return trim($row['email']);
        }
    }

    /*
        Return a list of addresses matching expression in
        all backends of a given type.
    */
    function search($expression, $bnum = -1) {
        $ret = array();
        $this->error = '';

        /* Search all backends */
        if ($bnum == -1) {
            $sel = $this->get_backend_list('');
            $failed = 0;
            for ($i = 0 ; $i < sizeof($sel) ; $i++) {
                $backend = &$sel[$i];
                $backend->error = '';
                $res = $backend->search($expression);
                if (is_array($res)) {
                    $ret = array_merge($ret, $res);
                } else {
                    $this->error .= "<br>\n" . $backend->error;
                    $failed++;
                }
            }

            /* Only fail if all backends failed */
            if( $failed >= sizeof( $sel ) ) {
                $ret = FALSE;
            }

        }  else {

            /* Search only one backend */

            $ret = $this->backends[$bnum]->search($expression);
            if (!is_array($ret)) {
                $this->error .= "<br>\n" . $this->backends[$bnum]->error;
                $ret = FALSE;
            }
        }

        return( $ret );
    }


    /* Return a sorted search */
    function s_search($expression, $bnum = -1) {
    
        $ret = $this->search($expression, $bnum);
        if ( is_array( $ret ) ) {
            usort($ret, 'addressbook_cmp');
        }    
        return $ret;
    }


    /*
     *  Lookup an address by alias. Only possible in
     *  local backends.
     */
    function lookup($alias, $bnum = -1) {
    
        $ret = array();
    
        if ($bnum > -1) {
            $res = $this->backends[$bnum]->lookup($alias);
            if (is_array($res)) {
               return $res;
            } else {
               $this->error = $backend->error;
               return false;
            }
        }
    
        $sel = $this->get_backend_list('local');
        for ($i = 0 ; $i < sizeof($sel) ; $i++) {
            $backend = &$sel[$i];
            $backend->error = '';
            $res = $backend->lookup($alias);
            if (is_array($res)) {
               if(!empty($res))
              return $res;
            } else {
               $this->error = $backend->error;
               return false;
            }
        }
        
        return $ret;
    }


    /* Return all addresses */
    function list_addr($bnum = -1) {
        $ret = array();
        
        if ($bnum == -1) {
            $sel = $this->get_backend_list('local');
        } else {
            $sel = array(0 => &$this->backends[$bnum]);
        }
        
        for ($i = 0 ; $i < sizeof($sel) ; $i++) {
            $backend = &$sel[$i];
            $backend->error = '';
            $res = $backend->list_addr();
            if (is_array($res)) {
               $ret = array_merge($ret, $res);
            } else {
               $this->error = $backend->error;
               return false;
            }
        }
        
        return $ret;
    }

    /*
     * Create a new address from $userdata, in backend $bnum.
     * Return the backend number that the/ address was added
     * to, or false if it failed.
     */
    function add($userdata, $bnum) {
    
        /* Validate data */
        if (!is_array($userdata)) {
            $this->error = _("Invalid input data");
            return false;
        }
        if (empty($userdata['firstname']) && empty($userdata['lastname'])) {
            $this->error = _("Name is missing");
            return false;
        }
        if (empty($userdata['email'])) {
            $this->error = _("E-mail address is missing");
            return false;
        }
        if (empty($userdata['nickname'])) {
            $userdata['nickname'] = $userdata['email'];
        }
        
        if (eregi('[ \\:\\|\\#\\"\\!]', $userdata['nickname'])) {
            $this->error = _("Nickname contains illegal characters");
            return false;
        }
        
        /* Check that specified backend accept new entries */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Addressbook is read-only");
            return false;
        }
        
        /* Add address to backend */
        $res = $this->backends[$bnum]->add($userdata);
        if ($res) {
            return $bnum;
        } else {
            $this->error = $this->backends[$bnum]->error;
            return false;
        }
        
        return false;  // Not reached
    } /* end of add() */


    /*
     * Remove the user identified by $alias from backend $bnum
     * If $alias is an array, all users in the array are removed.
     */
    function remove($alias, $bnum) {
    
        /* Check input */
        if (empty($alias)) {
            return true;
        }
        
        /* Convert string to single element array */
        if (!is_array($alias)) {
            $alias = array(0 => $alias);
        }
        
        /* Check that specified backend is writable */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Addressbook is read-only");
            return false;
        }
        
        /* Remove user from backend */
        $res = $this->backends[$bnum]->remove($alias);
        if ($res) {
            return $bnum;
        } else {
            $this->error = $this->backends[$bnum]->error;
            return false;
        }
        
        return FALSE;  /* Not reached */
    } /* end of remove() */


    /*
     * Remove the user identified by $alias from backend $bnum
     * If $alias is an array, all users in the array are removed.
     */
    function modify($alias, $userdata, $bnum) {
    
        /* Check input */
        if (empty($alias) || !is_string($alias)) {
            return true;
        }
        
        /* Validate data */
        if(!is_array($userdata)) {
            $this->error = _("Invalid input data");
            return false;
        }
        if (empty($userdata['firstname']) && empty($userdata['lastname'])) {
            $this->error = _("Name is missing");
            return false;
        }
        if (empty($userdata['email'])) {
            $this->error = _("E-mail address is missing");
            return false;
        }
        
        if (eregi('[\\: \\|\\#"\\!]', $userdata['nickname'])) {
            $this->error = _("Nickname contains illegal characters");
            return false;
        }
        
        if (empty($userdata['nickname'])) {
            $userdata['nickname'] = $userdata['email'];
        }
        
        /* Check that specified backend is writable */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Addressbook is read-only");;
            return false;
        }
        
        /* Modify user in backend */
        $res = $this->backends[$bnum]->modify($alias, $userdata);
        if ($res) {
            return $bnum;
        } else {
            $this->error = $this->backends[$bnum]->error;
            return false;
        }
        
        return FALSE;  /* Not reached */
    } /* end of modify() */
    
    
} /* End of class Addressbook */

/*
 * Generic backend that all other backends extend
 */
class addressbook_backend {

    /* Variables that all backends must provide. */
    var $btype      = 'dummy';
    var $bname      = 'dummy';
    var $sname      = 'Dummy backend';
    
    /*
     * Variables common for all backends, but that
     * should not be changed by the backends.
     */
    var $bnum       = -1;
    var $error      = '';
    var $writeable  = false;
    
    function set_error($string) {
        $this->error = '[' . $this->sname . '] ' . $string;
        return false;
    }
    
    
    /* ========================== Public ======================== */
    
    function search($expression) {
        $this->set_error('search not implemented');
        return false;
    }
    
    function lookup($alias) {
        $this->set_error('lookup not implemented');
        return false;
    }
    
    function list_addr() {
        $this->set_error('list_addr not implemented');
        return false;
    }
    
    function add($userdata) {
        $this->set_error('add not implemented');
        return false;
    }
    
    function remove($alias) {
        $this->set_error('delete not implemented');
        return false;
    }
    
    function modify($alias, $newuserdata) {
        $this->set_error('modify not implemented');
        return false;
    }

}

/* Sort array by the key "name" */
function alistcmp($a,$b) {
    if ($a['backend'] > $b['backend']) {
        return 1;
    } else {
        if ($a['backend'] < $b['backend']) {
            return -1;
        }
    }
    return (strtolower($a['name']) > strtolower($b['name'])) ? 1 : -1;
}

?>
