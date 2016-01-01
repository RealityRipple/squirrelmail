<?php
/**
 * functions/addressbook.php - Functions and classes for the addressbook system
 *
 * Functions require SM_PATH and support of forms.php functions
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Create and initialize an addressbook object.
 * @param boolean $showerr display any address book init errors. html page header
 * must be created before calling addressbook_init() with $showerr enabled.
 * @param boolean $onlylocal enable only local address book backends. Should 
 *  be used when code does not need access to remote backends. Backends
 *  that provide read only address books with limited listing options can be
 *  tagged as remote.
 * @return object address book object.
 */
function addressbook_init($showerr = true, $onlylocal = false) {
    global $data_dir, $username, $ldap_server, $address_book_global_filename;
    global $addrbook_dsn, $addrbook_table;
    global $abook_global_file, $abook_global_file_writeable, $abook_global_file_listing;
    global $addrbook_global_dsn, $addrbook_global_table, $addrbook_global_writeable, $addrbook_global_listing;
    global $abook_file_line_length;

    /* Create a new addressbook object */
    $abook = new AddressBook;

    /* Create empty error message */
    $abook_init_error='';

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
            $abook_init_error.=_("Error initializing address book database.") . "\n" . $abook->error;
        }
    } else {
        /* File */
        $filename = getHashedFile($username, $data_dir, "$username.abook");
        $r = $abook->add_backend('local_file', Array('filename' => $filename,
                                                     'umask' => 0077,
                                                     'line_length' => $abook_file_line_length,
                                                     'create'   => true));
        if(!$r && $showerr) {
            // no need to use $abook->error, because message explains error.
            $abook_init_error.=sprintf( _("Error opening file %s"), $filename );
        }
    }

    /* Global file based addressbook */
    if (isset($abook_global_file) &&
        isset($abook_global_file_writeable) &&
        isset($abook_global_file_listing) &&
        trim($abook_global_file)!=''){

        // Detect place of address book
        if (! preg_match("/[\/\\\]/",$abook_global_file)) {
            /* no path chars, address book stored in data directory
             * make sure that there is a slash between data directory
             * and address book file name
             */
            $abook_global_filename=$data_dir
                . ((substr($data_dir, -1) != '/') ? '/' : '')
                . $abook_global_file;
        } elseif (preg_match("/^\/|\w:/",$abook_global_file)) {
            // full path is set in options (starts with slash or x:)
            $abook_global_filename=$abook_global_file;
        } else {
            $abook_global_filename=SM_PATH . $abook_global_file;
        }

        $r = $abook->add_backend('local_file',array('filename'=>$abook_global_filename,
                                                    'name' => _("Global Address Book"),
                                                    'detect_writeable' => false,
                                                    'line_length' => $abook_file_line_length,
                                                    'writeable'=> $abook_global_file_writeable,
                                                    'listing' => $abook_global_file_listing));

        /* global abook init error is not fatal. add error message and continue */
        if (!$r && $showerr) {
            if ($abook_init_error!='') $abook_init_error.="\n";
            $abook_init_error.=_("Error initializing global address book.") . "\n" . $abook->error;
        }
    }

    /* Load global addressbook from SQL if configured */
    if (isset($addrbook_global_dsn) && !empty($addrbook_global_dsn)) {
      /* Database configured */
      if (!isset($addrbook_global_table) || empty($addrbook_global_table)) {
          $addrbook_global_table = 'global_abook';
      }
      $r = $abook->add_backend('database',
                               Array('dsn' => $addrbook_global_dsn,
                                     'owner' => 'global',
                                     'name' => _("Global Address Book"),
                                     'writeable' => $addrbook_global_writeable,
                                     'listing' => $addrbook_global_listing,
                                     'table' => $addrbook_global_table));
      /* global abook init error is not fatal. add error message and continue */
      if (!$r && $showerr) {
          if ($abook_init_error!='') $abook_init_error.="\n";
          $abook_init_error.=_("Error initializing global address book.") . "\n" . $abook->error;
      }
    }

    /*
     * hook allows to include different address book backends.
     * plugins should extract $abook and $r from arguments
     * and use same add_backend commands as above functions.
     * Since 1.5.2 hook sends third ($onlylocal) argument to address book
     * plugins in order to allow detection of local address book init.
     * @since 1.5.1 and 1.4.5
     * Since 1.5.2, the plugin arguments are passed inside an array
     * and by reference, so plugins hooking in here need to accept arguments
     * in an array and change those values as needed instead of returning
     * the changed values.
     */
    $temp = array(&$abook, &$r, &$onlylocal);
    do_hook('abook_init', $temp);
    if (!$r && $showerr) {
        if ($abook_init_error!='') $abook_init_error.="\n";
        $abook_init_error.=_("Error initializing other address books.") . "\n" . $abook->error;
    }

    /* Load configured LDAP servers (if PHP has LDAP support) */
    if (isset($ldap_server) && is_array($ldap_server)) {
        reset($ldap_server);
        while (list($undef,$param) = each($ldap_server)) {
            if (!is_array($param))
                continue;

            /* if onlylocal is true, we only add writeable ldap servers */
            if ($onlylocal && (!isset($param['writeable']) || $param['writeable'] != true))
                continue;

            $r = $abook->add_backend('ldap_server', $param);
            if (!$r && $showerr) {
                if ($abook_init_error!='') $abook_init_error.="\n";
                $abook_init_error.=sprintf(_("Error initializing LDAP server %s:"), $param['host'])."\n";
                $abook_init_error.= $abook->error;
            }
        }
    } // end of ldap server init

    /**
     * display address book init errors.
     */
    if ($abook_init_error!='' && $showerr) {
        error_box(nl2br(sm_encode_html_special_chars($abook_init_error)));
    }

    /* Return the initialized object */
    return $abook;
}

/**
 * Constructs the "new address" form
 *
 * NOTE!  The form is not closed - the caller
 *        must add the closing form tag itself.
 *
 * @since 1.5.1
 *
 * @param string $form_url Form action url
 * @param string $name     Form name
 * @param string $title    Form title
 * @param string $button   Form button name
 * @param int    $backend  The current backend being displayed
 * @param array  $defdata  Values of form fields
 *
 * @return string The desired address form display code
 *
 */
function abook_create_form($form_url, $name, $title, $button,
                           $backend, $defdata=array()) {

    global $oTemplate;

    $output = addForm($form_url, 'post', 'f_add', '', '', array(), TRUE);

    if ($button == _("Update address")) {
        $edit = true;
        $backends = NULL;
    } else {
        $edit = false;
        $backends = getWritableBackends();
    }
    
    $fields = array (
                        'nickname'  => 'NickName',
                        'firstname' => 'FirstName',
                        'lastname'  => 'LastName',
                        'email'     => 'Email',
                        'label'     => 'Info',
                    );
    $values = array();
    foreach ($fields as $sqm=>$template) {
        $values[$template] = isset($defdata[$sqm]) ? $defdata[$sqm] : '';
    }
    
    $oTemplate->assign('writable_backends', $backends);
    $oTemplate->assign('values', $values);
    $oTemplate->assign('edit', $edit);
    $oTemplate->assign('current_backend', $backend);
    
    $output .= $oTemplate->fetch('addrbook_addedit.tpl');

    return $output;
}


/**
 *   Had to move this function outside of the Addressbook Class
 *   PHP 4.0.4 Seemed to be having problems with inline functions.
 *   Note: this can return now since we don't support 4.0.4 anymore.
 */
function addressbook_cmp($a,$b) {

    if($a['backend'] > $b['backend']) {
        return 1;
    } else if($a['backend'] < $b['backend']) {
        return -1;
    }

    return (strtolower($a['name']) > strtolower($b['name'])) ? 1 : -1;

}

/**
 * Retrieve a list of writable backends
 * @since 1.5.2
 */
function getWritableBackends () {
    global $abook;
    
    $write = array();
    $backends = $abook->get_backend_list();
    while (list($undef,$v) = each($backends)) {
        if ($v->writeable) {
            $write[$v->bnum]=$v->sname;
        }
    }

    return $write;
}

/**
 * Sort array by the key "name"
 */
function alistcmp($a,$b) {
    $abook_sort_order=get_abook_sort();

    switch ($abook_sort_order) {
    case 0:
    case 1:
      $abook_sort='nickname';
      break;
    case 4:
    case 5:
      $abook_sort='email';
      break;
    case 6:
    case 7:
      $abook_sort='label';
      break;
    case 2:
    case 3:
    case 8:
    default:
      $abook_sort='name';
    }

    if ($a['backend'] > $b['backend']) {
        return 1;
    } else {
        if ($a['backend'] < $b['backend']) {
            return -1;
        }
    }

    if( (($abook_sort_order+2) % 2) == 1) {
      return (strtolower($a[$abook_sort]) < strtolower($b[$abook_sort])) ? 1 : -1;
    } else {
      return (strtolower($a[$abook_sort]) > strtolower($b[$abook_sort])) ? 1 : -1;
    }
}

/**
 * Address book sorting options
 *
 * returns address book sorting order
 * @return integer book sorting options order
 */
function get_abook_sort() {
    global $data_dir, $username;

    /* get sorting order */
    if(sqgetGlobalVar('abook_sort_order', $temp, SQ_GET)) {
      $abook_sort_order = (int) $temp;

      if ($abook_sort_order < 0 or $abook_sort_order > 8)
        $abook_sort_order=8;

      setPref($data_dir, $username, 'abook_sort_order', $abook_sort_order);
    } else {
      /* get previous sorting options. default to unsorted */
      $abook_sort_order = getPref($data_dir, $username, 'abook_sort_order', 8);
    }

    return $abook_sort_order;
}

/**
 * This function shows the address book sort button.
 *
 * @param integer $abook_sort_order Current sort value
 * @param string  $alt_tag          The alt tag value (string
 *                                  visible to text only browsers)
 * @param integer $Down             Sort value when list is sorted
 *                                  ascending
 * @param integer $Up               Sort value when list is sorted
 *                                  descending
 * @param array   $uri_extra        Any additional parameters to add
 *                                  to the button's link, as an
 *                                  associative array of key/value pairs
 *                                  (OPTIONAL; default none)
 *
 * @return string html code with sorting images and urls
 *
 */
function show_abook_sort_button($abook_sort_order, $alt_tag,
                                $Down, $Up, $uri_extra=array() ) {

    global $form_url, $icon_theme_path;

     /* Figure out which image we want to use. */
    if ($abook_sort_order != $Up && $abook_sort_order != $Down) {
        $img = 'sort_none.png';
        $text_icon = '&#9723;'; // U+25FB WHITE MEDIUM SQUARE
        $which = $Up;
    } elseif ($abook_sort_order == $Up) {
        $img = 'up_pointer.png';
        $text_icon = '&#8679;'; // U+21E7 UPWARDS WHITE ARROW
        $which = $Down;
    } else {
        $img = 'down_pointer.png';
        $text_icon = '&#8681;'; // U+21E9 DOWNWARDS WHITE ARROW
        $which = 8;
    }

    $uri_extra['abook_sort_order'] = $which;
    $uri = set_uri_vars($form_url, $uri_extra, FALSE);

    /* Now that we have everything figured out, show the actual button. */
    return create_hyperlink($uri,
                            getIcon($icon_theme_path, $img, $text_icon, $alt_tag),
                            '', '', '', '', '',
                            array('style' => 'text-decoration:none',
                                  'title' => $alt_tag),
                            FALSE);
}


/**
 * This is the main address book class that connect all the
 * backends and provide services to the functions above.
 * @package squirrelmail
 * @subpackage addressbook
 */
class AddressBook {
    /**
     * Enabled address book backends
     * @var array
     */
    var $backends    = array();
    /**
     * Number of enabled backends
     * @var integer
     */
    var $numbackends = 0;
    /**
     * Error messages
     * @var string
     */
    var $error       = '';
    /**
     * id of backend with personal address book
     * @var integer
     */
    var $localbackend = 0;
    /**
     * Name of backend with personal address book
     * @var string
     */
    var $localbackendname = '';
    /**
     * Controls use of 'extra' field
     *
     * Extra field can be used to add link to form, which allows
     * to modify all fields supported by backend. This is the only field
     * that is not sanitized with sm_encode_html_special_chars. Backends MUST make
     * sure that field data is sanitized and displayed correctly inside
     * table cell. Use of html formating in other address book fields is
     * not allowed. Backends that don't return 'extra' row in address book
     * data should not modify this object property.
     * @var boolean
     * @since 1.5.1
     */
    var $add_extra_field = false;

    /**
     * Constructor function.
     */
    function AddressBook() {
        $this->localbackendname = _("Personal Address Book");
    }

    /**
     * Return an array of backends of a given type,
     * or all backends if no type is specified.
     * @param string $type backend type
     * @return array list of backends
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


    /* ========================== Public ======================== */

    /**
     * Add a new backend.
     *
     * @param string $backend backend name (without the abook_ prefix)
     * @param mixed optional variable that is passed to the backend constructor.
     * See each of the backend classes for valid parameters
     * @return integer number of backends
     */
    function add_backend($backend, $param = '') {
        static $backend_classes;
        if (!isset($backend_classes)) {
            $backend_classes = array();
        }
        if (!isset($backend_classes[$backend])) {
            /**
              * Support backend provided by plugins. Plugin function must
              * return an associative array with as key the backend name ($backend)
              * and as value the file including the path containing the backend class.
              * i.e.: $aBackend = array('backend_template' => SM_PATH . 'plugins/abook_backend_template/functions.php')
              *
              * NB: Because the backend files are included from within this function they DO NOT have access to
              * vars in the global scope. This function is the global scope for the included backend !!!
              */
            global $null;
            $aBackend = do_hook('abook_add_class', $null);
            if (isset($aBackend) && is_array($aBackend) && isset($aBackend[$backend])) {
                require_once($aBackend[$backend]);
            } else {
                require_once(SM_PATH . 'functions/abook_'.$backend.'.php');
            }
            $backend_classes[$backend] = true;
        }
        $backend_name = 'abook_' . $backend;
        $newback = new $backend_name($param);
        //eval('$newback = new ' . $backend_name . '($param);');
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


    /**
     * create string with name and email address
     *
     * This function takes a $row array as returned by the addressbook
     * search and returns an e-mail address with the full name or
     * nickname optionally prepended.
     * @param array $row address book entry
     * @return string email address with real name prepended
     */
    static function full_address($row) {
        global $data_dir, $username, $addrsrch_fullname;

        // allow multiple addresses in one row (poor person's grouping - bah)
        // (separate with commas)
        //
        $return = '';
        $addresses = explode(',', $row['email']);
        foreach ($addresses as $address) {
            
            if (!empty($return)) $return .= ', ';

            if ($addrsrch_fullname == 'fullname')
                $return .= '"' . $row['name'] . '" <' . trim($address) . '>';
            else if ($addrsrch_fullname == 'nickname')
                $return .= '"' . $row['nickname'] . '" <' . trim($address) . '>';
            else // "noprefix"
                $return .= trim($address);

        }

        return $return;
    }

    /**
     * Search for entries in address books
     *
     * Return a list of addresses matching expression in
     * all backends of a given type.
     * @param string $expression search expression
     * @param integer $bnum backend number. default to search in all backends
     * @return array search results
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
                    $this->error .= "\n" . $backend->error;
                    $failed++;
                }
            }

            /* Only fail if all backends failed */
            if( $failed >= sizeof( $sel ) ) {
                $ret = FALSE;
            }

        } elseif (! isset($this->backends[$bnum])) {
            /* make sure that backend exists */
            $this->error = _("Unknown address book backend");
            $ret = false;
        } else {

            /* Search only one backend */

            $ret = $this->backends[$bnum]->search($expression);
            if (!is_array($ret)) {
                $this->error .= "\n" . $this->backends[$bnum]->error;
                $ret = FALSE;
            }
        }

        return( $ret );
    }


    /**
     * Sorted search
     * @param string $expression search expression
     * @param integer $bnum backend number. default to search in all backends
     * @return array search results
     */
    function s_search($expression, $bnum = -1) {

        $ret = $this->search($expression, $bnum);
        if ( is_array( $ret ) ) {
            usort($ret, 'addressbook_cmp');
        }
        return $ret;
    }


    /**
     * Lookup an address by the indicated field.
     *
     * Only possible in local backends.
     *
     * @param string  $value The value to look up
     * @param integer $bnum  The number of the backend to
     *                       look within (OPTIONAL; defaults 
     *                       to look in all local backends)
     * @param integer $field The field to look in, should be one
     *                       of the SM_ABOOK_FIELD_* constants
     *                       defined in include/constants.php
     *                       (OPTIONAL; defaults to nickname field)
     *                       NOTE: uniqueness is only guaranteed
     *                       when the nickname field is used here;
     *                       otherwise, the first matching address
     *                       is returned.
     *
     * @return mixed Array with lookup results when the value
     *               was found, an empty array if the value was
     *               not found, or false if an error occured.
     *
     */
    function lookup($value, $bnum = -1, $field = SM_ABOOK_FIELD_NICKNAME) {

        $ret = array();

        if ($bnum > -1) {
            if (!isset($this->backends[$bnum])) {
                $this->error = _("Unknown address book backend");
                return false;
            }
            $res = $this->backends[$bnum]->lookup($value, $field);
            if (is_array($res)) {
               return $res;
            } else {
               $this->error = $this->backends[$bnum]->error;
               return false;
            }
        }

        $sel = $this->get_backend_list('local');
        for ($i = 0 ; $i < sizeof($sel) ; $i++) {
            $backend = &$sel[$i];
            $backend->error = '';
            $res = $backend->lookup($value, $field);

            // return an address if one is found
            // (empty array means lookup concluded
            // but no result found - in this case,
            // proceed to next backend)
            //
            if (is_array($res)) {
                if (!empty($res)) return $res;
            } else {
                $this->error = $backend->error;
                return false;
            }
        }

        return $ret;
    }


    /**
     * Return all addresses
     * @param integer $bnum backend number
     * @return mixed array with search results or boolean false on error.
     */
    function list_addr($bnum = -1) {
        $ret = array();

        if ($bnum == -1) {
            $sel = $this->get_backend_list('');
        } elseif (! isset($this->backends[$bnum])) {
            /* make sure that backend exists */
            $this->error = _("Unknown address book backend");
            $ret = false;
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

    /**
     * Create a new address
     * @param array $userdata added address record
     * @param integer $bnum backend number
     * @return integer the backend number that the/ address was added
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

        /* Blocks use of space, :, |, #, " and ! in nickname */
        if (preg_match('/[ :|#"!]/', $userdata['nickname'])) {
            $this->error = _("Nickname contains illegal characters");
            return false;
        }

        /* make sure that backend exists */
        if (! isset($this->backends[$bnum])) {
            $this->error = _("Unknown address book backend");
            return false;
        }

        /* Check that specified backend accept new entries */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Address book is read-only");
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


    /**
     * Remove the entries from address book
     * @param mixed $alias entries that have to be removed. Can be string with nickname or array with list of nicknames
     * @param integer $bnum backend number
     * @return bool true if removed successfully. false if there s an error. $this->error contains error message
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

        /* make sure that backend exists */
        if (! isset($this->backends[$bnum])) {
            $this->error = _("Unknown address book backend");
            return false;
        }

        /* Check that specified backend is writable */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Address book is read-only");
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


    /**
     * Modify entry in address book
     * @param string $alias nickname
     * @param array $userdata newdata
     * @param integer $bnum backend number
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

        if (preg_match('/[: |#"!]/', $userdata['nickname'])) {
            $this->error = _("Nickname contains illegal characters");
            return false;
        }

        if (empty($userdata['nickname'])) {
            $userdata['nickname'] = $userdata['email'];
        }

        /* make sure that backend exists */
        if (! isset($this->backends[$bnum])) {
            $this->error = _("Unknown address book backend");
            return false;
        }

        /* Check that specified backend is writable */
        if (!$this->backends[$bnum]->writeable) {
            $this->error = _("Address book is read-only");;
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

/**
 * Generic backend that all other backends extend
 * @package squirrelmail
 * @subpackage addressbook
 */
class addressbook_backend {

    /* Variables that all backends must provide. */
    /**
     * Backend type
     *
     * Can be 'local' or 'remote'
     * @var string backend type
     */
    var $btype      = 'dummy';
    /**
     * Internal backend name
     * @var string
     */
    var $bname      = 'dummy';
    /**
     * Displayed backend name
     * @var string
     */
    var $sname      = 'Dummy backend';

    /*
     * Variables common for all backends, but that
     * should not be changed by the backends.
     */
    /**
     * Backend number
     * @var integer
     */
    var $bnum       = -1;
    /**
     * Error messages
     * @var string
     */
    var $error      = '';
    /**
     * Writeable flag
     * @var bool
     */
    var $writeable  = false;

    /**
     * Set error message
     * @param string $string error message
     * @return bool
     */
    function set_error($string) {
        $this->error = '[' . $this->sname . '] ' . $string;
        return false;
    }


    /* ========================== Public ======================== */

    /**
     * Search for entries in backend
     *
     * Working backend should support use of wildcards. * symbol
     * should match one or more symbols. ? symbol should match any
     * single symbol.
     * @param string $expression
     * @return bool
     */
    function search($expression) {
        $this->set_error('search is not implemented');
        return false;
    }

    /**
     * Find entry in backend by the indicated field
     *
     * @param string  $value The value to look up
     * @param integer $field The field to look in, should be one
     *                       of the SM_ABOOK_FIELD_* constants
     *                       defined in include/constants.php
     *                       NOTE: uniqueness is only guaranteed
     *                       when the nickname field is used here;
     *                       otherwise, the first matching address
     *                       is returned.
     *
     * @return mixed Array with lookup results when the value
     *               was found, an empty array if the value was
     *               not found, or false if an error occured.
     *
     */
    function lookup($value, $field=SM_ABOOK_FIELD_NICKNAME) {
        $this->set_error('lookup is not implemented');
        return false;
    }

    /**
     * List all entries in backend
     *
     * Working backend should provide this function or at least
     * dummy function that returns empty array.
     * @return bool
     */
    function list_addr() {
        $this->set_error('list_addr is not implemented');
        return false;
    }

    /**
     * Add entry to backend
     * @param array userdata
     * @return bool
     */
    function add($userdata) {
        $this->set_error('add is not implemented');
        return false;
    }

    /**
     * Remove entry from backend
     * @param string $alias name used for id
     * @return bool
     */
    function remove($alias) {
        $this->set_error('delete is not implemented');
        return false;
    }

    /**
     * Modify entry in backend
     * @param string $alias name used for id
     * @param array $newuserdata new data
     * @return bool
     */
    function modify($alias, $newuserdata) {
        $this->set_error('modify is not implemented');
        return false;
    }

    /**
     * Creates full name from given name and surname
     *
     * Handles name order differences. Function always runs in SquirrelMail gettext domain.
     * Plugins don't have to switch domains before calling this function.
     * @param string $firstname given name
     * @param string $lastname surname
     * @return string full name
     * @since 1.5.2
     */
    function fullname($firstname,$lastname) {
        // i18n: allows to control fullname layout in address book listing
        // first %s is for first name, second %s is for last name.
        // Translate it to '%2$s %1$s', if surname must be displayed first in your language.
        // Please note that variables can be set to empty string and extra formating 
        // (for example '%2$s, %1$s' as in 'Smith, John') might break. Use it only for 
        // setting name and surname order. scripts will remove all prepended and appended
        // whitespace.
        return trim(sprintf(dgettext('squirrelmail',"%s %s"),$firstname,$lastname));
    }
}
