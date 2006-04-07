<?php
/**
 * LDAP connection test script
 * 
 * Script is extended version of LDAP test script from PHP LDAP extension 
 * manual. It does not suppress LDAP function errors. If some LDAP function 
 * fails, you should see PHP error messages. If function is missing, you should
 * see errors too. If LDAP server returns unexpected output, you should see 
 * errors.
 * 
 * Change file extension from .phps to .php, if you want to use it. Don't store
 * important information (like your luggage password) on this file. 
 * Copyright (c) 2006 The SquirrelMail Project
 * License: script is licensed under GPL.
 * See http://www.opensource.org/licenses/gpl-license.php
 */

/** Configuration variables */

/**
 * URL of LDAP server
 *
 * You can use IP address, hostname or any other type of URL 
 * supported by your LDAP libraries. For example: you can add ldaps:// prefix 
 * for LDAP over SSL connection (636 port) or ldapi:// for LDAP socket 
 * connection.
 */
$ldap_host='localhost';
/**
 * LDAP BaseDN
 *
 * If you don't know it, script will try to show first available basedn when 
 * it reads LDAP server's base.
 */
$ldap_basedn='dc=example,dc=org';
/**
 * Controls use of LDAP v3 bind protocol
 *
 * PHP scripts default to v2 protocol and some LDAP servers (for example: newer
 * OpenLDAP versions and ADS) don't support it. 
 */
$ldap_v3bind=false;
/**
 * Controls use of LDAP STARTTLS
 *
 * Allows to enable TLS encryption on plain text LDAP connection.
 * Requires PHP 4.2.0 or newer.
 */
$ldap_starttls=false;
/**
 * ADS limit scope option
 * http://msdn.microsoft.com/library/en-us/ldap/ldap/ldap_server_domain_scope_oid.asp
 * Might be required for some Win2k3 ADS setups. Don't enable on other servers.
 * Warning: LDAP base search will fail, if option is enabled. 
 */
$ldap_limit_scope=false;
/**
 * BindDN used for authentication
 */
$ldap_binddn='';
/**
 * Password used for authentication
 */
$ldap_bindpw='';

/* end of configuration variables */

// modifications stop here.

/* set error reporting options */
ini_set('html_errors','off');
ini_set('display_errors','on');
error_reporting(E_ALL);

/* set plain text header */
header('Content-Type: text/plain');

/* start testing*/
echo "LDAP query test\n\n";
echo "Connecting ...\n";
$ds=ldap_connect($ldap_host);  // must be a valid LDAP server!
echo " connect result - ";
var_dump($ds);
echo "\n";

if ($ds) {
    echo "\nSetting LDAP options:\n";
    if ($ldap_v3bind) {
        if (ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            echo " Using LDAPv3\n";
        } else {
            echo " Failed to set protocol version to 3\n";
        }
    } else {
        echo " Using LDAPv2 (php default)\n";
    }

    if ($ldap_starttls) {
        if ($ldap_v3bind) {
            if (ldap_start_tls($ds)) {
                echo " Turned on TLS\n";
            } else {
                echo " Unable to turn on TLS\n";
            }
        } else {
            echo " You must use LDAPv3 protocol with STARTTLS.\n";
        }
    } else {
        echo " Not using LDAP STARTTLS.\n";
    }

    if ($ldap_limit_scope) {
        if ($ldap_v3bind) {
            $ctrl = array ( "oid" => "1.2.840.113556.1.4.1339", "iscritical" => TRUE );
            if (ldap_set_option($ds, LDAP_OPT_SERVER_CONTROLS, array($ctrl))) {
                echo " Turned on limit_scope\n";
            } else {
                echo " Unable to turn on limit_scope\n";
            }
        } else {
            echo " You must use LDAPv3 protocol with limit_scope option.\n";
        }
    } else {
        echo " Not using limit_scope option.\n";
    }

    echo "\nReading LDAP base:\n";
    if ($sr = ldap_read($ds,'',"(objectclass=*)")) {
        $info = ldap_get_entries($ds, $sr);
        echo " namingContexts:\n";
        if (isset($info[0]['namingcontexts'])) {
            for ($i=0; $i<$info[0]['namingcontexts']['count']; $i++) {
                echo '  ' . $i .': ' . $info[0]['namingcontexts'][$i] . "\n";
            }
        } else {
            echo " unavailable\n";
        }
    } else {
        echo " Unable to read LDAP base.\n";
    }
    echo "\n";

    echo "Authentication:\n";
    echo " Binding";
    if ($ldap_binddn!='') {
        echo " with authenticated bind ...\n";
        $r = ldap_bind($ds,$ldap_binddn,$ldap_bindpw);
    } else {
        echo " with anonymous bind ...\n";
        $r=ldap_bind($ds);
    }
    echo " Bind result - ";
    var_dump($r);
    echo "\n";

    echo "\n";
    echo "Search:\n";
    echo " Searching for (mail=*) ...\n";
    // Search for mail entries
    if ($sr=ldap_search($ds, $ldap_basedn, "(mail=*)")) {
     
        echo " Search result - ";
        var_dump($sr);
        echo "\n";

        echo " Number of entries: " . ldap_count_entries($ds, $sr) . "\n";

        echo " Getting entries ...\n";
        $info = ldap_get_entries($ds, $sr);

        echo " Data for " . $info["count"] . " items returned:\n";

        for ($i=0; $i<$info["count"]; $i++) {
            echo "  dn is: " . $info[$i]["dn"] . "\n";
            if (isset($info[$i]["cn"][0])) {
                echo "  first cn entry is: " . $info[$i]["cn"][0] . "\n";
            } else {
                echo "  cn attribute is not available.";
            }
            echo "  first email entry is: " . $info[$i]["mail"][0] . "\n------\n";
        }
    } else {
        echo " LDAP search failed.\n";
    }
    echo "\n";
    echo "Closing connection\n";
    ldap_close($ds);

} else {
    echo "Unable to connect to LDAP server\n";
}
?>