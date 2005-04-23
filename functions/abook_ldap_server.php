<?php
/**
 * abook_ldap_server.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Address book backend for LDAP server
 *
 * LDAP filtering code by Tim Bell
 *   <bhat at users.sourceforge.net> (#539534)
 * ADS limit_scope code by Michael Brown
 *   <mcb30 at users.sourceforge.net> (#1035454)
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Address book backend for LDAP server
 *
 * An array with the following elements must be passed to
 * the class constructor (elements marked ? are optional):
 * <pre>
 *    host      => LDAP server hostname/IP-address
 *    base      => LDAP server root (base dn). Empty string allowed.
 *  ? port      => LDAP server TCP port number (default: 389)
 *  ? charset   => LDAP server charset (default: utf-8)
 *  ? name      => Name for LDAP server (default "LDAP: hostname")
 *                 Used to tag the result data
 *  ? maxrows   => Maximum # of rows in search result
 *  ? filter    => Filter expression to limit ldap searches
 *  ? timeout   => Timeout for LDAP operations (in seconds, default: 30)
 *                 Might not work for all LDAP libraries or servers.
 *  ? binddn    => LDAP Bind DN.
 *  ? bindpw    => LDAP Bind Password.
 *  ? protocol  => LDAP Bind protocol.
 *  ? limit_scope => Limits scope to base DN.
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
                $this->protocol = $param['protocol'];

            if(isset($param['filter']))
                $this->filter = trim($param['filter']);

            if(isset($param['limit_scope']))
                $this->limit_scope = $param['limit_scope'];

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
        if(!$this->linkid) {
            if(function_exists('ldap_error') && is_resource($this->linkid)) {
                return $this->set_error(ldap_error($this->linkid));
            } else {
                return $this->set_error('ldap_connect failed');
            }
        }

        if(!empty($this->protocol)) {
            if(!@ldap_set_option($this->linkid, LDAP_OPT_PROTOCOL_VERSION, $this->protocol)) {
                if(function_exists('ldap_error')) {
                    return $this->set_error(ldap_error($this->linkid));
                } else {
                    return $this->set_error('ldap_set_option failed');
                }
            }
        }

        if(!empty($this->limit_scope) && $this->limit_scope) {
            if(empty($this->protocol) || intval($this->protocol) < 3) {
                return $this->set_error('limit_scope requires protocol >= 3');
            }
            // See http://msdn.microsoft.com/library/en-us/ldap/ldap/ldap_server_domain_scope_oid.asp
            $ctrl = array ( "oid" => "1.2.840.113556.1.4.1339", "iscritical" => TRUE );
            if(!@ldap_set_option($this->linkid, LDAP_OPT_SERVER_CONTROLS, array($ctrl))) {
                if(function_exists('ldap_error')) {
                    return $this->set_error(ldap_error($this->linkid));
                } else {
                    return $this->set_error('limit domain scope failed');
                }
            }
        }

        if(!empty($this->binddn)) {
            if(!@ldap_bind($this->linkid, $this->binddn, $this->bindpw)) {
                if(function_exists('ldap_error')) {
                    return $this->set_error(ldap_error($this->linkid));
                } else {
                    return $this->set_error('authenticated ldap_bind failed');
                }
              }
        } else {
            if(!@ldap_bind($this->linkid)) {
                if(function_exists('ldap_error')) {
                    return $this->set_error(ldap_error($this->linkid));
                } else {
                    return $this->set_error('anonymous ldap_bind failed');
                }
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

    /* ========================== Public ======================== */

    /**
     * Search the LDAP server
     * @param string $expr search expression
     * @return array search results
     */
    function search($expr) {
        /* To be replaced by advanded search expression parsing */
        if(is_array($expr)) return false;

        /* Encode the expression */
        $expr = $this->charset_encode($expr);

        /*
         * allow use of one asterisk in search.
         * Don't allow any ldap special chars if search is different
         */
        if($expr!='*') {
            $expr = '*' . $this->ldapspecialchars($expr) . '*';
        }
        $expression = "(cn=$expr)";

        if ($this->filter!='')
            $expression = '(&' . $this->filter . $expression . ')';

        /* Make sure connection is there */
        if(!$this->open()) {
            return false;
        }

        $sret = @ldap_search($this->linkid, $this->basedn, $expression,
            array('dn', 'o', 'ou', 'sn', 'givenname', 'cn', 'mail'),
            0, $this->maxrows, $this->timeout);

        /* Should get error from server using the ldap_error() function,
         * but it only exist in the PHP LDAP documentation. */
        if(!$sret) {
            if(function_exists('ldap_error')) {
                return $this->set_error(ldap_error($this->linkid));
            } else {
                return $this->set_error('ldap_search failed');
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
    } /* end search() */


    /**
     * List all entries present in LDAP server
     *
     * If you run a tiny LDAP server and you want the "List All" button
     * to show EVERYONE, disable first return call and enable the second one.
     * Remember that maxrows setting might limit list of returned entries.
     *
     * Careful with this -- it could get quite large for big sites.
     * @return array all entries in ldap server
     */
     function list_addr() {
         return array();
         // return $this->search('*');
     }
}
?>