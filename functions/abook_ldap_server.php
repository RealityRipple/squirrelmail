<?php

/**
 * abook_ldap_server.php
 *
 * Address book backend for LDAP server
 *
 * LDAP filtering code by Tim Bell
 *   <bhat at users.sourceforge.net> (#539534)
 * ADS limit_scope code by Michael Brown
 *   <mcb30 at users.sourceforge.net> (#1035454)
 * StartTLS code by John Lane
 *   <starfry at users.sourceforge.net> (#1197703)
 * Code for remove, add, modify, lookup by David Härdeman
 *   <david at hardeman.nu> (#1495763)
 *
 * This backend uses LDAP person (RFC2256), organizationalPerson (RFC2256)
 * and inetOrgPerson (RFC2798) objects and dn, description, sn, givenname,
 * cn, mail attributes. Other attributes are ignored.
 * 
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Address book backend for LDAP server
 *
 * An array with the following elements must be passed to
 * the class constructor (elements marked ? are optional)
 *
 * Main settings:
 * <pre>
 *    host      => LDAP server hostname, IP-address or any other URI compatible
 *                 with used LDAP library.
 *    base      => LDAP server root (base dn). Empty string allowed.
 *  ? port      => LDAP server TCP port number (default: 389)
 *  ? charset   => LDAP server charset (default: utf-8)
 *  ? name      => Name for LDAP server (default "LDAP: hostname")
 *                 Used to tag the result data
 *  ? maxrows   => Maximum # of rows in search result
 *  ? timeout   => Timeout for LDAP operations (in seconds, default: 30)
 *                 Might not work for all LDAP libraries or servers.
 *  ? binddn    => LDAP Bind DN.
 *  ? bindpw    => LDAP Bind Password.
 *  ? protocol  => LDAP Bind protocol.
 * </pre>
 * Advanced settings:
 * <pre>
 *  ? filter    => Filter expression to limit ldap search results.
 *    You can use this to *limit* the result set, based on specific
 *    requirements. The filter must be enclosed in parentheses, e.g.:
 *    '(objectclass=mailRecipient)'
 *    or '(&(objectclass=mailRecipient)(obectclass=myCustomClass))'
 *    The default value is empty.
 *
 *  ? search_expression => Custom expression to expand ldap searches.
 *    This can help *expand* the result set, because of hits in more
 *    LDAP attributes. It must be a printf()-style string with either
 *    one placeholder '%s', or, if you want to repeat the expression
 *    many times, '%1$s'. The default value is:
 *    '(|(cn=*%1$s*)(mail=*%1$s*)(sn=*%1$s*))'
 *    that is, the search expression is search in the fields cn (common
 *    name), sn (surname) and mail.
 *
 *  ? limit_scope => Limits scope to base DN (Specific to Win2k3 ADS).
 *  ? listing   => Controls listing of LDAP directory.
 *  ? writeable => Controls write access to address book
 *  ? search_tree => Controls subtree or one level search.
 *  ? starttls  => Controls use of StartTLS on LDAP connections
 * </pre>
 * NOTE. This class should not be used directly. Use addressbook_init()
 *       function instead.
 * @package squirrelmail
 * @subpackage addressbook
 */
class abook_ldap_server extends addressbook_backend {
    /**
     * @var string backend type
     */
    var $btype = 'remote';
    /**
     * @var string backend name
     */
    var $bname = 'ldap_server';

    /* Parameters changed by class */
    /**
     * @var string displayed name
     */
    var $sname   = 'LDAP';       /* Service name */
    /**
     * @var string LDAP server name or address or url
     */
    var $server  = '';
    /**
     * @var integer LDAP server port
     */
    var $port    = 389;
    /**
     * @var string LDAP base DN
     */
    var $basedn  = '';
    /**
     * @var string charset used for entries in LDAP server
     */
    var $charset = 'utf-8';
    /**
     * @var object PHP LDAP link ID
     */
    var $linkid  = false;
    /**
     * @var bool True if LDAP server is bound
     */
    var $bound   = false;
    /**
     * @var integer max rows in result
     */
    var $maxrows = 250;
    /**
     * @var string ldap filter
     * @since 1.5.1
     */
    var $filter = '';
    /**
     * @var string printf()-style ldap search expression.
     * The default is to search for same string in cn, mail and sn.
     * @since 1.5.2
     */
    var $search_expression = '(|(cn=*%1$s*)(mail=*%1$s*)(sn=*%1$s*))';
    /**
     * @var integer timeout of LDAP operations (in seconds)
     */
    var $timeout = 30;
    /**
     * @var string DN to bind to (non-anonymous bind)
     * @since 1.5.0 and 1.4.3
     */
    var $binddn = '';
    /**
     * @var string  password to bind with (non-anonymous bind)
     * @since 1.5.0 and 1.4.3
     */
    var $bindpw = '';
    /**
     * @var integer protocol used to connect to ldap server
     * @since 1.5.0 and 1.4.3
     */
    var $protocol = '';
    /**
     * @var boolean limits scope to base dn
     * @since 1.5.1
     */
    var $limit_scope = false;
    /**
     * @var boolean controls listing of directory
     * @since 1.5.1
     */
    var $listing = false;
    /**
     * @var boolean true if removing/adding/modifying entries is allowed
     * @since 1.5.2
     */
    var $writeable = false;
    /**
     * @var boolean controls ldap search type.
     * only first level entries are displayed if set to false
     * @since 1.5.1
     */
    var $search_tree = true;
    /**
     * @var boolean controls use of StartTLS on ldap 
     * connections. Requires php 4.2+ and protocol >= 3
     * @since 1.5.1
     */
    var $starttls = false;

    /**
     * Constructor. Connects to database
     * @param array connection options
     */
    function abook_ldap_server($param) {
        if(!function_exists('ldap_connect')) {
            $this->set_error(_("PHP install does not have LDAP support."));
            return;
        }
        if(is_array($param)) {
            $this->server = $param['host'];
            // remove whitespace from basedn
            $this->basedn = preg_replace('/,\s*/',',',trim($param['base']));

            if(!empty($param['port']))
                $this->port = $param['port'];

            if(!empty($param['charset']))
                $this->charset = strtolower($param['charset']);

            if(isset($param['maxrows']))
                $this->maxrows = $param['maxrows'];

            if(isset($param['timeout']))
                $this->timeout = $param['timeout'];

            if(isset($param['binddn']))
                $this->binddn = $param['binddn'];

            if(isset($param['bindpw']))
                $this->bindpw = $param['bindpw'];

            if(isset($param['protocol']))
                $this->protocol = (int) $param['protocol'];

            if(isset($param['filter']))
                $this->filter = trim($param['filter']);
            
            if(isset($param['search_expression']) &&
               (strstr($param['search_expression'], '%s') || strstr($param['search_expression'], '%1$s'))) {
                $this->search_expression = trim($param['search_expression']);
            }

            if(isset($param['limit_scope']))
                $this->limit_scope = (bool) $param['limit_scope'];

            if(isset($param['listing']))
                $this->listing = (bool) $param['listing'];

            if(isset($param['writeable'])) {
                $this->writeable = (bool) $param['writeable'];
                // switch backend type to local, if it is writable
                if($this->writeable) $this->btype = 'local';
            }

            if(isset($param['search_tree']))
                $this->search_tree = (bool) $param['search_tree'];

            if(isset($param['starttls']))
                $this->starttls = (bool) $param['starttls'];

            if(empty($param['name'])) {
                $this->sname = 'LDAP: ' . $param['host'];
            } else {
                $this->sname = $param['name'];
            }

            /*
             * don't open LDAP server on addressbook_init(),
             * open ldap connection only on search. Speeds up
             * addressbook_init() call.
             */
            // $this->open(true);
        } else {
            $this->set_error('Invalid argument to constructor');
        }
    }


    /**
     * Open the LDAP server.
     * @param bool $new is it a new connection
     * @return bool
     */
    function open($new = false) {
        $this->error = '';

        /* Connection is already open */
        if($this->linkid != false && !$new) {
            return true;
        }

        $this->linkid = @ldap_connect($this->server, $this->port);
        /**
         * check if connection was successful
         * It does not work with OpenLDAP 2.x libraries. Connect error will be 
         * displayed only on ldap command that tries to make connection 
         * (ldap_start_tls or ldap_bind). 
         */
        if(!$this->linkid) {
            return $this->set_error($this->ldap_error('ldap_connect failed'));
        }

        if(!empty($this->protocol)) {
            // make sure that ldap_set_option() is available before using it
            if(! function_exists('ldap_set_option') ||
               !@ldap_set_option($this->linkid, LDAP_OPT_PROTOCOL_VERSION, $this->protocol)) {
                return $this->set_error('unable to set ldap protocol number');
            }
        }

        /**
         * http://www.php.net/ldap-start-tls
         * Check if v3 or newer protocol is used,
         * check if ldap_start_tls function is available.
         * Silently ignore setting, if these requirements are not satisfied.
         * Break with error message if somebody tries to start TLS on
         * ldaps or socket connection.
         */
        if($this->starttls && 
           !empty($this->protocol) && $this->protocol >= 3 &&
           function_exists('ldap_start_tls') ) {
            // make sure that $this->server is not ldaps:// or ldapi:// URL.
            if (preg_match("/^ldap[si]:\/\/.+/i",$this->server)) {
                return $this->set_error("you can't enable starttls on ldaps and ldapi connections.");
            }
            
            // try starting tls
            if (! @ldap_start_tls($this->linkid)) {
                // set error if call fails
                return $this->set_error($this->ldap_error('ldap_start_tls failed'));
            }
        }

        if(!empty($this->limit_scope) && $this->limit_scope) {
            if(empty($this->protocol) || intval($this->protocol) < 3) {
                return $this->set_error('limit_scope requires protocol >= 3');
            }
            // See http://msdn.microsoft.com/library/en-us/ldap/ldap/ldap_server_domain_scope_oid.asp
            $ctrl = array ( "oid" => "1.2.840.113556.1.4.1339", "iscritical" => TRUE );
            /*
             * Option is set only during connection.
             * It does not cause immediate errors with OpenLDAP 2.x libraries.
             */
            if(! function_exists('ldap_set_option') ||
               !@ldap_set_option($this->linkid, LDAP_OPT_SERVER_CONTROLS, array($ctrl))) {
                return $this->set_error($this->ldap_error('limit domain scope failed'));
            }
        }

        // authenticated bind
        if(!empty($this->binddn)) {
            if(!@ldap_bind($this->linkid, $this->binddn, $this->bindpw)) {
                return $this->set_error($this->ldap_error('authenticated ldap_bind failed'));
            }
        } else {
            // anonymous bind
            if(!@ldap_bind($this->linkid)) {
                return $this->set_error($this->ldap_error('anonymous ldap_bind failed'));
            }
        }

        $this->bound = true;

        return true;
    }

    /**
     * Encode string to the charset used by this LDAP server
     * @param string string that has to be encoded
     * @return string encoded string
     */
    function charset_encode($str) {
        global $default_charset;
        if($this->charset != $default_charset) {
            return charset_convert($default_charset,$str,$this->charset,false);
        } else {
            return $str;
        }
    }

    /**
     * Decode from charset used by this LDAP server to charset used by translation
     *
     * Uses SquirrelMail charset_decode functions
     * @param string string that has to be decoded
     * @return string decoded string
     */
    function charset_decode($str) {
        global $default_charset;
        if ($this->charset != $default_charset) {
            return charset_convert($this->charset,$str,$default_charset,false);
        } else {
            return $str;
        }
    }

    /**
     * Sanitizes ldap search strings.
     * See rfc2254
     * @link http://www.faqs.org/rfcs/rfc2254.html
     * @since 1.5.1 and 1.4.5
     * @param string $string
     * @return string sanitized string
     */
    function ldapspecialchars($string) {
        $sanitized=array('\\' => '\5c',
                         '*' => '\2a',
                         '(' => '\28',
                         ')' => '\29',
                         "\x00" => '\00');

        return str_replace(array_keys($sanitized),array_values($sanitized),$string);
    }

    /**
     * Prepares user input for use in a ldap query.
     *
     * Function converts input string to character set used in LDAP server 
     * (charset_encode() method) and sanitizes it (ldapspecialchars()).
     *
     * @param string $string string to encode
     * @return string ldap encoded string
     * @since 1.5.2
     */
    function quotevalue($string) {
        $sanitized = $this->charset_encode($string);
        return $this->ldapspecialchars($sanitized);
    }

    /**
     * Search LDAP server.
     *
     * Warning: You must make sure that ldap query is correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $expression ldap query
     * @param boolean $singleentry (since 1.5.2) whether we are looking for a 
     *  single entry. Boolean true forces LDAP_SCOPE_BASE search.
     * @return array search results (false on error)
     * @since 1.5.1
     */
    function ldap_search($expression, $singleentry = false) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        $attributes = array('dn', 'description', 'sn', 'givenName', 'cn', 'mail');

        if ($singleentry) {
            // ldap_read - search for one single entry
            $sret = @ldap_read($this->linkid, $expression, "objectClass=*",
                               $attributes, 0, $this->maxrows, $this->timeout);
        } elseif ($this->search_tree) {
            // ldap_search - search subtree
            $sret = @ldap_search($this->linkid, $this->basedn, $expression,
                $attributes, 0, $this->maxrows, $this->timeout);
        } else {
            // ldap_list - search one level
            $sret = @ldap_list($this->linkid, $this->basedn, $expression,
                $attributes, 0, $this->maxrows, $this->timeout);
        }

        /* Return error if search failed */
        if(!$sret) {
            // Check for LDAP_NO_SUCH_OBJECT (0x20 or 32) error
            if (ldap_errno($this->linkid)==32) {
                return array();
            } else {
                return $this->set_error($this->ldap_error('ldap_search failed'));
            }
        }

        if(@ldap_count_entries($this->linkid, $sret) <= 0) {
            return array();
        }

        /* Get results */
        $ret = array();
        $returned_rows = 0;
        $res = @ldap_get_entries($this->linkid, $sret);
        for($i = 0 ; $i < $res['count'] ; $i++) {
            $row = $res[$i];

            /* Extract data common for all e-mail addresses
             * of an object. Use only the first name */      
            $nickname = $this->charset_decode($row['dn']);

            /**
             * remove trailing basedn
             * remove whitespaces between RDNs
             * remove leading "cn="
             * which gives nicknames which are shorter while still unique
             */
            $nickname = preg_replace('/,\s*/',',', trim($nickname));
            $offset = strlen($nickname) - strlen($this->basedn);
 
            if($offset > 0 && substr($nickname, $offset) == $this->basedn) {
                $nickname = substr($nickname, 0, $offset);
                if(substr($nickname, -1) == ",")
                    $nickname = substr($nickname, 0, -1);
            }
            if(strncasecmp($nickname, "cn=", 3) == 0)
                $nickname=substr($nickname, 3);         

            if(empty($row['description'][0])) {
                $label = '';
            } else {
                $label = $this->charset_decode($row['description'][0]);
            }

            if(empty($row['givenname'][0])) {
                $firstname = '';
            } else {
                $firstname = $this->charset_decode($row['givenname'][0]);
            }

            if(empty($row['sn'][0])) {
                $surname = '';
            } else {
                // remove whitespace in order to handle sn set to empty string
                $surname = trim($this->charset_decode($row['sn'][0]));
            }

            $fullname = $this->fullname($firstname,$surname);

            /* Add one row to result for each e-mail address */
            if(isset($row['mail']['count'])) {
                for($j = 0 ; $j < $row['mail']['count'] ; $j++) {
                    array_push($ret, array('nickname'  => $nickname,
                   'name'      => $fullname,
                   'firstname' => $firstname,
                   'lastname'  => $surname,
                   'email'     => $row['mail'][$j],
                   'label'     => $label,
                   'backend'   => $this->bnum,
                   'source'    => &$this->sname));

                    // Limit number of hits
                    $returned_rows++;
                    if(($returned_rows >= $this->maxrows) &&
                       ($this->maxrows > 0) ) {
                        ldap_free_result($sret);
                        return $ret;
                    }

                } // for($j ...)

            } // isset($row['mail']['count'])

        }

        ldap_free_result($sret);
        return $ret;
    }

    /**
     * Add an entry to LDAP server.
     *
     * Warning: You must make sure that the arguments are correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $dn the dn of the entry to be added
     * @param array $data the values of the entry to be added
     * @return boolean result (false on error)
     * @since 1.5.2
     */
    function ldap_add($dn, $data) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        if(!@ldap_add($this->linkid, $dn, $data)) {
            $this->set_error(_("Write to address book failed"));
            return false;
        }
        
        return true;
    }

    /**
     * Remove an entry from LDAP server.
     *
     * Warning: You must make sure that the argument is correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $dn the dn of the entry to remove
     * @return boolean result (false on error)
     * @since 1.5.2
     */
    function ldap_remove($dn) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        if(!@ldap_delete($this->linkid, $dn)) {
            $this->set_error(_("Removing entry from address book failed"));
            return false;
        }

        return true;
    }

    /**
     * Rename an entry on LDAP server.
     *
     * Warning: You must make sure that the arguments are correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $sourcedn the dn of the entry to be renamed
     * @param string $targetdn the dn which $sourcedn should be renamed to
     * @param string $parent the dn of the parent entry
     * @return boolean result (false on error)
     * @since 1.5.2
     */
    function ldap_rename($sourcedn, $targetdn, $parent) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        /* Make sure that the protocol version supports rename */
        if($this->protocol < 3) {
            $this->set_error(_("LDAP rename is not supported by used protocol version"));
            return false;
        }
        /**
         * Function is available only in OpenLDAP 2.x.x or Netscape Directory 
         * SDK x.x, and was added in PHP 4.0.5
         * @todo maybe we can use copy + delete instead of ldap_rename()
         */
        if(!function_exists('ldap_rename')) {
            $this->set_error(_("LDAP rename is not supported by used LDAP library. You can't change nickname"));
            return false;
        }

        /* OK, go for it */
        if(!@ldap_rename($this->linkid, $sourcedn, $targetdn, $parent, true)) {
            $this->set_error(_("LDAP rename failed"));
            return false;
        }

        return true;
    }

    /**
     * Modify the values of an entry on LDAP server.
     *
     * Warning: You must make sure that the arguments are correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $dn the dn of the entry to be modified
     * @param array $data the new values of the entry
     * @param array $deleted_attribs attributes that should be deleted.
     * @return bool result (false on error)
     * @since 1.5.2
     */
    function ldap_modify($dn, $data, $deleted_attribs) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        if(!@ldap_modify($this->linkid, $dn, $data)) {
            $this->set_error(_("Write to address book failed"));
            return false;
        }

        if (!@ldap_mod_del($this->linkid, $dn, $deleted_attribs)) {
            $this->set_error(_("Unable to remove some field values"));
            return false;
        }

        return true;
    }

    /**
     * Get error from LDAP resource if possible
     *
     * Should get error from server using the ldap_errno() and ldap_err2str() functions
     * @param string $sError error message used when ldap error functions 
     * and connection resource are unavailable
     * @return string error message
     * @since 1.5.1
     */
    function ldap_error($sError) {
        // it is possible that function_exists() tests are not needed
        if(function_exists('ldap_err2str') && 
           function_exists('ldap_errno') && 
           is_resource($this->linkid)) {
            return ldap_err2str(ldap_errno($this->linkid));
            // return ldap_error($this->linkid);
        } else {
            return $sError;
        }
    }

    /**
     * Determine internal attribute name given one of
     * the SquirrelMail SM_ABOOK_FIELD_* constants
     *
     * @param integer $attr The SM_ABOOK_FIELD_* contant to look up
     *
     * @return string The desired attribute name, or the string "ERROR"
     *                if the $field is not understood (the caller
     *                is responsible for handing errors)
     *
     */
    function get_attr_name($attr) {
        switch ($attr) {
            case SM_ABOOK_FIELD_NICKNAME:
                return 'cn';
            case SM_ABOOK_FIELD_FIRSTNAME:
                return 'givenName';
            case SM_ABOOK_FIELD_LASTNAME:
                return 'sn';
            case SM_ABOOK_FIELD_EMAIL:
                return 'mail';
            case SM_ABOOK_FIELD_LABEL:
                return 'description';
            default:
                return 'ERROR';
        }
    }

    /* ========================== Public ======================== */

    /**
     * Search the LDAP server
     * @param string $expr search expression
     * @return array search results
     */
    function search($expr) {
        /* To be replaced by advanded search expression parsing */
        if(is_array($expr)) return false;

        // don't allow wide search when listing is disabled.
        if ($expr=='*' && ! $this->listing) {
            return array();
        } elseif ($expr=='*') {
            // allow use of wildcard when listing is enabled.
            $expression = '(cn=*)';
        } else {
            /* Convert search from user's charset to the one used in ldap and sanitize */
            $expr = $this->quotevalue($expr);

            /* If search expr contains %s or %1$s, replace them with escaped values,
             * so that a wrong printf()-style string is not created by mistake.
             * (Probably overkill but who knows...) */
            $expr = str_replace('%s', '\\25s', $expr);
            $expr = str_replace('%1$s', '\\251$s', $expr);

            /* Substitute %s or %1$s in printf()-formatted search_expresison with
             * the value that the user searches for. */
            $expression = sprintf($this->search_expression, $expr);

            /* Undo sanitizing of * symbol */
            $expression = str_replace('\2a','*',$expression);

            /* Replace '**', '***' etc. with '*' in case it occurs in final 
             * search expression */
            while(strstr($expression, '**')) {
                $expression = str_replace('**', '*', $expression);
            }
        }

        /* Add search filtering */
        if ($this->filter!='')
            $expression = '(&' . $this->filter . $expression . ')';

        /* Use internal search function and return search results */
        return $this->ldap_search($expression);
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
     * @since 1.5.2
     *
     */
    function lookup($value, $field=SM_ABOOK_FIELD_NICKNAME) {


        $attr = get_attr_name($field);
        if ($attr == 'ERROR') {
            return $this->set_error(sprintf(_("Unknown field name: %s"), $field));
        }

        // Generate the dn
        $dn = $attr . '=' . $this->quotevalue($value) . ',' . $this->basedn;

        // Do the search
        $result = $this->ldap_search($dn, true);
        if (!is_array($result) || count($result) < 1)
            return array();

        return $result[0];
    }

    /**
     * List all entries present in LDAP server
     *
     * maxrows setting might limit list of returned entries.
     * Careful with this -- it could get quite large for big sites.
     * @return array all entries in ldap server
     */
     function list_addr() {
         if (! $this->listing)
             return array();

         /* set wide search expression */
         $expression = '(cn=*)';

         /* add filtering */
         if ($this->filter!='')
             $expression = '(&' . $this->filter . $expression .')';

         /* use internal search function and return search results */
         return $this->ldap_search($expression);
     }

    /**
     * Add address
     * @param array $userdata new data
     * @return boolean
     * @since 1.5.2
     */
    function add($userdata) {
        if(!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        /* Convert search from user's charset to the one used in ldap and sanitize */
        $cn = $this->quotevalue($userdata['nickname']);
        $dn = 'cn=' . $cn . ',' . trim($this->basedn);

        /* See if user exists already */
        $user = $this->ldap_search($dn, true);
        if (!is_array($user)) {
            return false;
        } elseif (count($user) > 0) {
            return $this->set_error(sprintf(_("User \"%s\" already exists"), $userdata['nickname']));
        }

        /* init variable */
        $data = array();

        /* Prepare data */
        $data['cn'] = $cn;
        $data['mail'] = $this->quotevalue($userdata['email']);
        $data["objectclass"][0] = "top";
        $data["objectclass"][1] = "person";
        $data["objectclass"][2] = "organizationalPerson";
        $data["objectclass"][3] = "inetOrgPerson";
        /* sn is required in person object */
        if(!empty($userdata['lastname'])) {
            $data['sn'] = $this->quotevalue($userdata['lastname']);
        } else {
            $data['sn'] = ' ';
        }
        /* optional fields */
        if(!empty($userdata['firstname']))
            $data['givenName'] = $this->quotevalue($userdata['firstname']);
        if(!empty($userdata['label'])) {
            $data['description'] = $this->quotevalue($userdata['label']);
        }
        return $this->ldap_add($dn, $data);
    }

    /**
     * Delete address
     * @param array $aliases array of entries that have to be removed.
     * @return boolean
     * @since 1.5.2
     */
    function remove($aliases) {
        if(!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        foreach ($aliases as $alias) {
            /* Convert nickname from user's charset and derive cn/dn */
            $cn = $this->quotevalue($alias);
            $dn = 'cn=' . $cn . ',' . $this->basedn;

            if (!$this->ldap_remove($dn))
                return false;
        }

        return true;
    }

    /**
     * Modify address
     * @param string $alias modified alias
     * @param array $userdata new data
     * @return boolean
     * @since 1.5.2
     */
    function modify($alias, $userdata) {
        if(!$this->writeable) {
            return $this->set_error(_("Address book is read-only"));
        }

        /* Convert search from user's charset to the one used in ldap and sanitize */
        $sourcecn = $this->quotevalue($alias);
        $sourcedn = 'cn=' . $sourcecn . ',' . trim($this->basedn);
        $targetcn = $this->quotevalue($userdata['nickname']);
        $targetdn = 'cn=' . $targetcn . ',' . trim($this->basedn);

        /* Check that the dn to modify exists */
        $sourceuser = $this->lookup($alias);
        if (!is_array($sourceuser) || count($sourceuser) < 1)
            return false;

        /* Check if dn is going to change */
        if ($alias != $userdata['nickname']) {

            /* Check that the target dn doesn't exist */
            $targetuser = $this->lookup($userdata['nickname']);
            if (is_array($targetuser) && count($targetuser) > 0)
                return $this->set_error(sprintf(_("User \"%s\" already exists"), $userdata['nickname']));

            /* Rename from the source dn to target dn */
            if (!$this->ldap_rename($sourcedn, 'cn=' . $targetcn, $this->basedn))
                    return $this->set_error(sprintf(_("Unable to rename user \"%s\" to \"%s\""), $alias, $userdata['nickname']));
        }

        // initial vars
        $data = array();
        $deleted_attribs = array();

        /* Prepare data */
        $data['cn'] = $this->quotevalue($targetcn);
        $data['mail'] = $this->quotevalue($userdata['email']);
        $data["objectclass"][0] = "top";
        $data["objectclass"][1] = "person";
        $data["objectclass"][2] = "organizationalPerson";
        $data["objectclass"][3] = "inetOrgPerson";

        if(!empty($userdata['firstname'])) {
            $data['givenName'] = $this->quotevalue($userdata['firstname']);
        } elseif (!empty($sourceuser['firstname'])) {
            $deleted_attribs['givenName'] = $this->quotevalue($sourceuser['firstname']);
        }

        if(!empty($userdata['lastname'])) {
            $data['sn'] = $this->quotevalue($userdata['lastname']);
        } else {
            // sn is required attribute in LDAP person object.
            // SquirrelMail requires givenName or Surname 
            $data['sn'] = ' ';
        }

        if(!empty($userdata['label'])) {
            $data['description'] = $this->quotevalue($userdata['label']);
        } elseif (!empty($sourceuser['label'])) {
            $deleted_attribs['description'] = $this->quotevalue($sourceuser['label']);
        }

        return $this->ldap_modify($targetdn, $data, $deleted_attribs);
    }
}
