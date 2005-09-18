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
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
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
 *    host      => LDAP server hostname/IP-address
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
 *  ? filter    => Filter expression to limit ldap searches
 *  ? limit_scope => Limits scope to base DN (Specific to Win2k3 ADS).
 *  ? listing   => Controls listing of LDAP directory.
 *  ? search_tree => Controls subtree or one level search.
 *  ? starttls  => Controls use of StartTLS on LDAP connections
 * </pre>
 * NOTE. This class should not be used directly. Use the
 *       "AddressBook" class instead.
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
            $this->basedn = $param['base'];

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

            if(isset($param['limit_scope']))
                $this->limit_scope = (bool) $param['limit_scope'];

            if(isset($param['listing']))
                $this->listing = (bool) $param['listing'];

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
     * Search LDAP server.
     *
     * Warning: You must make sure that ldap query is correctly formated and 
     * sanitize use of special ldap keywords.
     * @param string $expression ldap query
     * @return array search results (false on error)
     * @since 1.5.1
     */
    function ldap_search($expression) {
        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        if ($this->search_tree) {
            // ldap_search - search subtree
            $sret = @ldap_search($this->linkid, $this->basedn, $expression,
                array('dn', 'o', 'ou', 'sn', 'givenname', 'cn', 'mail'),
                0, $this->maxrows, $this->timeout);
        } else {
            // ldap_list - search one level
            $sret = @ldap_list($this->linkid, $this->basedn, $expression,
                array('dn', 'o', 'ou', 'sn', 'givenname', 'cn', 'mail'),
                0, $this->maxrows, $this->timeout);
        }

        /* Return error if search failed */
        if(!$sret) {
            return $this->set_error($this->ldap_error('ldap_search failed'));
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
             * calculate length of basedn and remove it from nickname
             * ignore whitespaces between RDNs
             * Nicknames are shorter and still unique
             */ 
            $basedn_len=strlen(preg_replace('/,\s*/',',',trim($this->basedn)));
            $nickname=substr(preg_replace('/,\s*/',',',$nickname),0,(-1 - $basedn_len));

            $fullname = $this->charset_decode($row['cn'][0]);

            if(!empty($row['ou'][0])) {
                $label = $this->charset_decode($row['ou'][0]);
            }
            else if(!empty($row['o'][0])) {
                $label = $this->charset_decode($row['o'][0]);
            } else {
                $label = '';
            }

            if(empty($row['givenname'][0])) {
                $firstname = '';
            } else {
                $firstname = $this->charset_decode($row['givenname'][0]);
            }

            if(empty($row['sn'][0])) {
                $surname = '';
            } else {
                $surname = $this->charset_decode($row['sn'][0]);
            }

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
            /* Convert search from user's charset to the one used in ldap */
            $expr = $this->charset_encode($expr);

            /* Make sure that search does not contain ldap special chars */
            $expression = '(cn=*' . $this->ldapspecialchars($expr) . '*)';

            /* Undo sanitizing of * symbol */
            $expression = str_replace('\2a','*',$expression);
            /* TODO: implement any single character (?) matching */
        }

        /* Add search filtering */
        if ($this->filter!='')
            $expression = '(&' . $this->filter . $expression . ')';

        /* Use internal search function and return search results */
        return $this->ldap_search($expression);
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
}
?>