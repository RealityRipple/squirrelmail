<?php

/**
 * Change password LDAP backend
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * do not allow to call this file directly
 */
if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == __FILE__) {
    header("Location: ../../../src/login.php");
    die();
}

/** load required functions */

/** sqimap_get_user_server() function */
include_once(SM_PATH . 'functions/imap_general.php');

/** get imap server and username globals */
global $imapServerAddress, $username;

/** Default plugin configuration.*/
/**
 * Address of LDAP server.
 * You can use any URL format that is supported by your LDAP extension.
 * Examples:
 * <ul>
 *   <li>'ldap.example.com' - connect to server on ldap.example.com address
 *   <li>'ldaps://ldap.example.com' - connect to server on ldap.example.com address
 *   and use SSL encrypted connection to default LDAPs port.
 * </ul>
 * defaults to imap server address.
 * @link http://www.php.net/ldap-connect
 * @global string $cpw_ldap_server
 */
global $cpw_ldap_server;
$cpw_ldap_server=$imapServerAddress;

/**
 * Port of LDAP server.
 * Used only when $cpw_ldap_server specifies IP address or DNS name.
 * @global integer $cpw_ldap_port
 */
global $cpw_ldap_port;
$cpw_ldap_port=389;

/**
 * LDAP basedn that is used for binding to LDAP server.
 * this option must be set to correct value.
 * @global string $cpw_ldap_basedn;
 */
global $cpw_ldap_basedn;
$cpw_ldap_basedn='';

/**
 * LDAP connection options
 * @link http://www.php.net/ldap-set-option
 * @global array $cpw_ldap_connect_opts
 */
global $cpw_ldap_connect_opts;
$cpw_ldap_connect_opts=array();

/**
 * Controls use of starttls on LDAP connection.
 * Requires PHP 4.2+, PHP LDAP extension with SSL support and
 * PROTOCOL_VERSION => 3 setting in $cpw_ldap_connect_opts
 * @global boolean $cpw_ldap_use_tls
 */
global $cpw_ldap_use_tls;
$cpw_ldap_use_tls=false;

/**
 * BindDN that should be able to search LDAP directory and find DN used by user.
 * Uses anonymous bind if set to empty string. You should not use DN with write
 * access to LDAP directory here. Write access is not required.
 * @global string $cpw_ldap_binddn
 */
global $cpw_ldap_binddn;
$cpw_ldap_binddn='';

/**
 * password used for $cpw_ldap_binddn
 * @global string $cpw_ldap_bindpw
 */
global $cpw_ldap_bindpw;
$cpw_ldap_bindpw='';

/**
 * BindDN that should be able to change password.
 * WARNING: sometimes user has enough privileges to change own password.
 * If you leave default value, plugin will try to connect with DN that
 * is detected in $cpw_ldap_username_attr=$username search and current
 * user password will be used for authentication.
 * @global string $cpw_ldap_admindn
 */
global $cpw_ldap_admindn;
$cpw_ldap_admindn='';

/**
 * password used for $cpw_ldap_admindn
 * @global string $cpw_ldap_adminpw
 */
global $cpw_ldap_adminpw;
$cpw_ldap_adminpw='';

/**
 * LDAP attribute that stores username.
 * username entry should be unique for $cpw_ldap_basedn
 * @global string $cpw_ldap_userid_attr
 */
global $cpw_ldap_userid_attr;
$cpw_ldap_userid_attr='uid';

/**
 * crypto that is used to encode new password
 * If set to empty string, system tries to keep same encoding/hashing algorithm
 * @global string $cpw_ldap_default_crypto
 */
global $cpw_ldap_default_crypto;
$cpw_ldap_default_crypto='';

/** end of default config */

/** configuration overrides from config file */
if (isset($cpw_ldap['server'])) $cpw_ldap_server=$cpw_ldap['server'];
if (isset($cpw_ldap['port'])) $cpw_ldap_port=$cpw_ldap['port'];
if (isset($cpw_ldap['basedn'])) $cpw_ldap_basedn=$cpw_ldap['basedn'];
if (isset($cpw_ldap['connect_opts'])) $cpw_ldap_connect_opts=$cpw_ldap['connect_opts'];
if (isset($cpw_ldap['use_tls'])) $cpw_ldap_use_tls=$cpw_ldap['use_tls'];
if (isset($cpw_ldap['binddn'])) $cpw_ldap_binddn=$cpw_ldap['binddn'];
if (isset($cpw_ldap['bindpw'])) $cpw_ldap_bindpw=$cpw_ldap['bindpw'];
if (isset($cpw_ldap['admindn'])) $cpw_ldap_admindn=$cpw_ldap['admindn'];
if (isset($cpw_ldap['adminpw'])) $cpw_ldap_adminpw=$cpw_ldap['adminpw'];
if (isset($cpw_ldap['userid_attr'])) $cpw_ldap_userid_attr=$cpw_ldap['userid_attr'];
if (isset($cpw_ldap['default_crypto'])) $cpw_ldap_default_crypto=$cpw_ldap['default_crypto'];

/** make sure that setting does not contain mapping */
$cpw_ldap_server=sqimap_get_user_server($cpw_ldap_server,$username);

/**
 * Adding plugin hooks
 */
global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['ldap'] =
        'cpw_ldap_dochange';
$squirrelmail_plugin_hooks['change_password_init']['ldap'] =
        'cpw_ldap_init';

/**
 * Makes sure that required functions and configuration options are set.
 */
function cpw_ldap_init() {
    global $oTemplate, $cpw_ldap_basedn;

    // set initial value for error tracker
    $cpw_ldap_initerr=false;

    // check for ldap support in php
    if (! function_exists('ldap_connect')) {
        error_box(_("Current configuration requires LDAP support in PHP."));
        $cpw_ldap_initerr=true;
    }

    // chech required configuration settings.
    if ($cpw_ldap_basedn=='') {
        error_box(_("Plugin is not configured correctly."));
        $cpw_ldap_initerr=true;
    }

    // if error var is positive, close html and stop execution
    if ($cpw_ldap_initerr) {
        $oTemplate->display('footer.tpl');
        exit;
    }
}


/**
 * Changes password. Main function attached to hook
 * @param array $data The username/curpw/newpw data.
 * @return array Array of error messages.
 */
function cpw_ldap_dochange($data) {
    global $cpw_ldap_server, $cpw_ldap_port, $cpw_ldap_basedn,
        $cpw_ldap_connect_opts,$cpw_ldap_use_tls,
        $cpw_ldap_binddn, $cpw_ldap_bindpw,
        $cpw_ldap_admindn, $cpw_ldap_adminpw;

    // unfortunately, we can only pass one parameter to a hook function,
    // so we have to pass it as an array.
    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    // globalize current password.

    $msgs = array();

    /**
     * connect to LDAP server
     * hide ldap_connect() function call errors, because they are processed in script.
     * any script execution error is treated as critical, error messages are dumped
     * to $msgs and LDAP connection is closed with ldap_unbind(). all ldap_unbind()
     * errors are suppressed. Any other error suppression should be explained.
     */
    $cpw_ldap_con=@ldap_connect($cpw_ldap_server);

    if ($cpw_ldap_con) {
        $cpw_ldap_con_err=false;

        // set connection options
        if (is_array($cpw_ldap_connect_opts) && $cpw_ldap_connect_opts!=array()) {
            // ldap_set_option() is available only with openldap 2.x and netscape directory sdk.
            if (function_exists('ldap_set_option')) {
                foreach ($cpw_ldap_connect_opts as $opt => $value) {
                    // Make sure that constant is defined defore using it.
                    if (defined('LDAP_OPT_' . $opt)) {
                        // ldap_set_option() should not produce E_NOTICE or E_ALL errors and does not modify ldap_error().
                        // leave it without @ in order to see any weird errors
                        if (! ldap_set_option($cpw_ldap_con,constant('LDAP_OPT_' . $opt),$value)) {
                            // set error message
                            array_push($msgs,sprintf(_("Setting of LDAP connection option %s to value %s failed."),$opt,$value));
                            $cpw_ldap_con_err=true;
                        }
                    } else {
                        array_push($msgs,sprintf(_("Incorrect LDAP connection option: %s"),$opt));
                        $cpw_ldap_con_err=true;
                    }
                }
            } else {
                array_push($msgs,_("Current PHP LDAP extension does not allow use of ldap_set_option() function."));
                $cpw_ldap_con_err=true;
            }
        }

        // check for connection errors and stop execution if something is wrong
        if ($cpw_ldap_con_err) {
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        // enable ldap starttls
        if ($cpw_ldap_use_tls &&
            check_php_version(4,2,0) &&
            isset($cpw_ldap_connect_opts['PROTOCOL_VERSION']) &&
            $cpw_ldap_connect_opts['PROTOCOL_VERSION']>=3 &&
            function_exists('ldap_start_tls')) {
            // suppress ldap_start_tls errors and process error messages
            if (! @ldap_start_tls($cpw_ldap_con)) {
                array_push($msgs,
                           _("Unable to use TLS."),
                           sprintf(_("Error: %s"),ldap_error($cpw_ldap_con)));
                $cpw_ldap_con_err=true;
            }
        } elseif ($cpw_ldap_use_tls) {
            array_push($msgs,_("Unable to use LDAP TLS in current setup."));
            $cpw_ldap_con_err=true;
        }

        // check for connection errors and stop execution if something is wrong
        if ($cpw_ldap_con_err) {
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        /**
         * Bind to LDAP (use anonymous bind or unprivileged DN) in order to get user's DN
         * hide ldap_bind() function call errors, because errors are processed in script
         */
        if ($cpw_ldap_binddn!='') {
            // authenticated bind
            $cpw_ldap_binding=@ldap_bind($cpw_ldap_con,$cpw_ldap_binddn,$cpw_ldap_bindpw);
        } else {
            // anonymous bind
            $cpw_ldap_binding=@ldap_bind($cpw_ldap_con);
        }

        // check ldap_bind errors
        if (! $cpw_ldap_binding) {
            array_push($msgs,
                       _("Unable to bind to LDAP server."),
                       sprintf(_("Server replied: %s"),ldap_error($cpw_ldap_con)));
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        // find userdn
        $cpw_ldap_search_err=cpw_ldap_uid_search($cpw_ldap_con,$cpw_ldap_basedn,$msgs,$cpw_ldap_res,$cpw_ldap_userdn);

        // check for search errors and stop execution if something is wrong
        if (! $cpw_ldap_search_err) {
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        /**
         * unset $cpw_ldap_res2 variable, if such var exists.
         * $cpw_ldap_res2 object can be set in two places and second place checks,
         * if object was created in first place. if variable name matches (somebody
         * uses $cpw_ldap_res2 in code or globals), incorrect validation might
         * cause script errors.
         */
        if (isset($cpw_ldap_res2)) unset($cpw_ldap_res2);

        // rebind as userdn or admindn
        if ($cpw_ldap_admindn!='') {
            // admindn bind
            $cpw_ldap_binding=@ldap_bind($cpw_ldap_con,$cpw_ldap_admindn,$cpw_ldap_adminpw);

            if ($cpw_ldap_binding) {
                // repeat search in order to get password info. Password info should be unavailable in unprivileged bind.
                $cpw_ldap_search_err=cpw_ldap_uid_search($cpw_ldap_con,$cpw_ldap_basedn,$msgs,$cpw_ldap_res2,$cpw_ldap_userdn);

                // check for connection errors and stop execution if something is wrong
                if (! $cpw_ldap_search_err) {
                    @ldap_unbind($cpw_ldap_con);
                    // errors are added to msgs by cpw_ldap_uid_search()
                    return $msgs;
                }

                // we should check user password here.
                // suppress errors and check value returned by function call
                $cpw_ldap_cur_pass_array=@ldap_get_values($cpw_ldap_con,
                                                         ldap_first_entry($cpw_ldap_con,$cpw_ldap_res2),'userpassword');

                // check if ldap_get_values() have found userpassword field
                if (! $cpw_ldap_cur_pass_array) {
                    array_push($msgs,_("Unable to find user's password attribute."));
                    return $msgs;
                }

                // compare passwords
                if (! cpw_ldap_compare_pass($cpw_ldap_cur_pass_array[0],$curpw,$msgs)) {
                    @ldap_unbind($cpw_ldap_con);
                    // errors are added to $msgs by cpw_ldap_compare_pass()
                    return $msgs;
                }
            }
        } else {
            $cpw_ldap_binding=@ldap_bind($cpw_ldap_con,$cpw_ldap_userdn,$curpw);
        }

        if (! $cpw_ldap_binding) {
            array_push($msgs,
                       _("Unable to rebind to LDAP server."),
                       sprintf(_("Server replied: %s"),ldap_error($cpw_ldap_con)));
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        // repeat search in order to get password info
        if (! isset($cpw_ldap_res2))
            $cpw_ldap_search_err=cpw_ldap_uid_search($cpw_ldap_con,$cpw_ldap_basedn,$msgs,$cpw_ldap_res2,$cpw_ldap_userdn);

        // check for connection errors and stop execution if something is wrong
        if (! $cpw_ldap_search_err) {
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        // getpassword. suppress errors and check value returned by function call
        $cpw_ldap_cur_pass_array=@ldap_get_values($cpw_ldap_con,ldap_first_entry($cpw_ldap_con,$cpw_ldap_res2),'userpassword');

        // check if ldap_get_values() have found userpassword field.
        // Error differs from previous one, because user managed to authenticate.
        if (! $cpw_ldap_cur_pass_array) {
            array_push($msgs,_("LDAP server uses different attribute to store user's password."));
            return $msgs;
        }

        // encrypt new password (old password is needed for plaintext encryption detection)
        $cpw_ldap_new_pass=cpw_ldap_encrypt_pass($newpw,$cpw_ldap_cur_pass_array[0],$msgs,$curpw);

        if (! $cpw_ldap_new_pass) {
            @ldap_unbind($cpw_ldap_con);
            return $msgs;
        }

        // set new password. suppress ldap_modify errors. script checks and displays ldap_modify errors.
        $ldap_pass_change=@ldap_modify($cpw_ldap_con,$cpw_ldap_userdn,array('userpassword'=>$cpw_ldap_new_pass));

        // check if ldap_modify was successful
        if(! $ldap_pass_change) {
            array_push($msgs,ldap_error($cpw_ldap_con));
        }

        // close connection
        @ldap_unbind($cpw_ldap_con);
    } else {
        array_push($msgs,_("Unable to connect to LDAP server."));
    }
    return $msgs;
}

/** backend support functions **/

/**
 * Sanitizes LDAP query strings.
 * original code - ldapquery plugin.
 * See rfc2254
 * @link http://www.faqs.org/rfcs/rfc2254.html
 * @param string $string
 * @return string sanitized string
 */
function cpw_ldap_specialchars($string) {
    $sanitized=array('\\' => '\5c',
                     '*' => '\2a',
                     '(' => '\28',
                     ')' => '\29',
                     "\x00" => '\00');

    return str_replace(array_keys($sanitized),array_values($sanitized),$string);
}

/**
 * returns crypto algorithm used in password.
 * @param string $pass encrypted/hashed password
 * @return string lowercased crypto algorithm name
 */
function cpw_ldap_get_crypto($pass,$curpass='') {
    $ret = false;

    if (preg_match("/^\{(.+)\}+/",$pass,$crypto)) {
        $ret=strtolower($crypto[1]);
    }

    if ($ret=='crypt') {
        // {CRYPT} can be standard des crypt, extended des crypt, md5 crypt or blowfish
        // depends on first salt symbols (ext_des = '_', md5 = '$1$', blowfish = '$2')
        // and length of salt (des = 2 chars, ext_des = 9, md5 = 12, blowfish = 16).
        if (preg_match("/^\{crypt\}\\\$1\\\$+/i",$pass)) {
            $ret='md5crypt';
        } elseif (preg_match("/^\{crypt\}\\\$2+/i",$pass)) {
            $ret='blowfish';
        } elseif (preg_match("/^\{crypt\}_+/i",$pass)) {
            $ret='extcrypt';
        }
    }

    // maybe password is plaintext
    if (! $ret && $curpass!='' && $pass==$curpass) $ret='plaintext';

    return $ret;
}

/**
 * Search LDAP for user id.
 * @param object $ldap_con ldap connection
 * @param string $ldap_basedn ldap basedn
 * @param array $msgs error messages
 * @param object $results ldap search results
 * @param string $userdn DN of found entry
 * @param boolean $onlyone require unique search results
 * @return boolean false if connection failed.
 */
function cpw_ldap_uid_search($ldap_con,$ldap_basedn,&$msgs,&$results,&$userdn,$onlyone=true) {
    global $cpw_ldap_userid_attr,$username;

    $ret=true;

    $results=ldap_search($ldap_con,$ldap_basedn,cpw_ldap_specialchars($cpw_ldap_userid_attr . '=' . $username));

    if (! $results) {
        array_push($msgs,
                   _("Unable to find user's DN."),
                   _("Search error."),
                   sprintf(_("Error: %s"),ldap_error($ldap_con)));
        $ret=false;
    } elseif ($onlyone && ldap_count_entries($ldap_con,$results)>1) {
        array_push($msgs,_("Multiple userid matches found."));
        $ret=false;
    } elseif (! $userdn = ldap_get_dn($ldap_con,ldap_first_entry($ldap_con,$results))) {
        // ldap_get_dn() returned error
        array_push($msgs,
                   _("Unable to find user's DN."),
                   _("ldap_get_dn error."));
        $ret=false;
    }
    return $ret;
}

/**
 * Encrypts LDAP password
 *
 * if $cpw_ldap_default_crypto is set to empty string or $same_crypto is set,
 * uses same crypto as in old password.
 * See phpldapadmin password_hash() function
 * @link http://phpldapadmin.sf.net
 * @param string $pass string that has to be encrypted/hashed
 * @param string $cur_pass_hash old password hash
 * @param array $msgs error message
 * @param string $curpass current password. Used for plaintext password detection.
 * @return string encrypted/hashed password or false
 */
function cpw_ldap_encrypt_pass($pass,$cur_pass_hash,&$msgs,$curpass='') {
    global $cpw_ldap_default_crypto;

    // which crypto should be used to encode/hash password
    if ($cpw_ldap_default_crypto=='') {
        $ldap_crypto=cpw_ldap_get_crypto($cur_pass_hash,$curpass);
    } else {
        $ldap_crypto=$cpw_ldap_default_crypto;
    }
    return cpw_ldap_password_hash($pass,$ldap_crypto,$msgs);
}

/**
 * create hashed password
 * @param string $pass plain text password
 * @param string $crypto used crypto algorithm
 * @param array $msgs array used for error messages
 * @param string $forced_salt salt that should be used during hashing.
 * Is used only when is not set to empty string. Salt should be formated
 * according to $crypto requirements.
 * @return hashed password or false.
 */
function cpw_ldap_password_hash($pass,$crypto,&$msgs,$forced_salt='') {
    // set default return code
    $ret=false;

    // lowercase crypto just in case
    $crypto=strtolower($crypto);

    // extra symbols used for random string in crypt salt
    // squirrelmail GenerateRandomString() adds alphanumerics with third argument = 7.
    $extra_salt_chars='./';

    // encrypt/hash password
    switch ($crypto) {
    case 'md4':
        // minimal requirement = php with mhash extension
        if ( function_exists( 'mhash' ) && defined('MHASH_MD4')) {
            $ret = '{MD4}' . base64_encode( mhash( MHASH_MD4, $pass) );
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'md4'),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'md5':
        $ret='{MD5}' . base64_encode(pack('H*',md5($pass)));
        break;
    case 'smd5':
        // minimal requirement = mhash extension with md5 support and php 4.0.4.
        if( function_exists( 'mhash' ) && function_exists( 'mhash_keygen_s2k' ) && defined('MHASH_MD5')) {
            if ($forced_salt!='') {
                $salt=$forced_salt;
            } else {
                $salt = mhash_keygen_s2k( MHASH_MD5, $pass, substr( pack( "h*", md5( mt_rand() ) ), 0, 8 ), 4 );
            }
            $ret = "{SMD5}".base64_encode( mhash( MHASH_MD5, $pass.$salt ).$salt );
        } else {
            // use two array_push calls in order to display messages in different lines.
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'smd5'),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'rmd160':
        // minimal requirement = php with mhash extension
        if ( function_exists( 'mhash' ) && defined('MHASH_RIPEMD160')) {
            $ret = '{RMD160}' . base64_encode( mhash( MHASH_RIPEMD160, $pass) );
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'ripe-md160'),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'sha':
        // minimal requirement = php 4.3.0+ or php with mhash extension
        if ( function_exists('sha1') && defined('MHASH_SHA1')) {
            // use php 4.3.0+ sha1 function, if it is available.
            $ret = '{SHA}' . base64_encode(pack('H*',sha1($pass)));
        } elseif( function_exists( 'mhash' ) ) {
            $ret = '{SHA}' . base64_encode( mhash( MHASH_SHA1, $pass) );
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'sha'),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'ssha':
        // minimal requirement = mhash extension and php 4.0.4
        if( function_exists( 'mhash' ) && function_exists( 'mhash_keygen_s2k' ) && defined('MHASH_SHA1')) {
            if ($forced_salt!='') {
                $salt=$forced_salt;
            } else {
                $salt = mhash_keygen_s2k( MHASH_SHA1, $pass, substr( pack( "h*", md5( mt_rand() ) ), 0, 8 ), 4 );
            }
            $ret = "{SSHA}".base64_encode( mhash( MHASH_SHA1, $pass.$salt ).$salt );
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'ssha'),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'crypt':
        if (defined('CRYPT_STD_DES') && CRYPT_STD_DES==1) {
            $ret = '{CRYPT}' . crypt($pass,GenerateRandomString(2,$extra_salt_chars,7));
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'crypt'),
                       _("System crypt library doesn't support standard DES crypt."));
        }
        break;
    case 'md5crypt':
        // check if crypt() supports md5
        if (defined('CRYPT_MD5') && CRYPT_MD5==1) {
            $ret = '{CRYPT}' . crypt($pass,'$1$' . GenerateRandomString(9,$extra_salt_chars,7));
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'md5crypt'),
                       _("System crypt library doesn't have MD5 support."));
        }
        break;
    case 'extcrypt':
        // check if crypt() supports extended des
        if (defined('CRYPT_EXT_DES') && CRYPT_EXT_DES==1) {
            $ret = '{CRYPT}' . crypt($pass,'_' . GenerateRandomString(8,$extra_salt_chars,7));
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'ext_des'),
                       _("System crypt library doesn't support extended DES crypt."));
        }
        break;
    case 'blowfish':
        // check if crypt() supports blowfish
        if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH==1) {
            $ret = '{CRYPT}' . crypt($pass,'$2a$12$' . GenerateRandomString(13,$extra_salt_chars,7));
        } else {
            array_push($msgs,
                       sprintf(_("Unsupported crypto: %s"),'Blowfish'),
                       _("System crypt library doesn't have Blowfish support."));
        }
        break;
    case 'plaintext':
        // clear plain text password
        $ret=$pass;
        break;
    default:
        array_push($msgs,sprintf(_("Unsupported crypto: %s"),
                                 (is_string($ldap_crypto) ? sm_encode_html_special_chars($ldap_crypto) : _("unknown"))));
    }
    return $ret;
}

/**
 * compares two passwords
 * Code reuse. See phpldapadmin password_compare() function.
 * Some parts of code was rewritten to backend specifics.
 * @link http://phpldapadmin.sf.net
 * @param string $pass_hash hashed password string with password type indicators
 * @param string $pass_clear plain text password
 * @param array $msgs error messages
 * @return boolean true, if passwords match
 */
function cpw_ldap_compare_pass($pass_hash,$pass_clear,&$msgs) {
    $ret=false;

    if( preg_match( "/{([^}]+)}(.*)/", $pass_hash, $cypher ) ) {
        $pass_hash = $cypher[2];
        $_cypher = strtolower($cypher[1]);
    } else  {
        $_cypher = NULL;
    }

    switch( $_cypher ) {
    case 'ssha':
        // Salted SHA
        // check for mhash support
        if ( function_exists('mhash') && defined('MHASH_SHA1')) {
            $hash = base64_decode($pass_hash);
            $salt = substr($hash, -4);
            $new_hash = base64_encode( mhash( MHASH_SHA1, $pass_clear.$salt).$salt );
            if( strcmp( $pass_hash, $new_hash ) == 0 )
                $ret=true;
        } else {
            array_push($msgs,
                       _("Unable to validate user's password."),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'smd5':
        // Salted MD5
        // check for mhash support
        if ( function_exists('mhash') && defined('MHASH_MD5')) {
            $hash = base64_decode($pass_hash);
            $salt = substr($hash, -4);
            $new_hash = base64_encode( mhash( MHASH_MD5, $pass_clear.$salt).$salt );
            if( strcmp( $pass_hash, $new_hash ) == 0)
                $ret=true;
        } else {
            array_push($msgs,
                       _("Unable to validate user's password."),
                       _("PHP mhash extension is missing or does not support selected crypto."));
        }
        break;
    case 'sha':
        // SHA crypted passwords
        if( strcasecmp( cpw_ldap_password_hash($pass_clear,'sha',$msgs), "{SHA}".$pass_hash ) == 0)
            $ret=true;
        break;
    case 'rmd160':
        // RIPE-MD160 crypted passwords
        if( strcasecmp( cpw_ldap_password_hash($pass_clear,'rmd160',$msgs), "{RMD160}".$pass_hash ) == 0 )
            $ret=true;
        break;
    case 'md5':
        // MD5 crypted passwords
        if( strcasecmp( cpw_ldap_password_hash($pass_clear,'md5',$msgs), "{MD5}".$pass_hash ) == 0 )
            $ret=true;
        break;
    case 'md4':
        // MD4 crypted passwords
        if( strcasecmp( cpw_ldap_password_hash($pass_clear,'md4',$msgs), "{MD4}".$pass_hash ) == 0 )
            $ret=true;
        break;
    case 'crypt':
        // Crypt passwords
        if(  preg_match( "/^\\\$2+/",$pass_hash ) ) { // Check if it's blowfish crypt
            // check CRYPT_BLOWFISH here.
            // ldap server might support it, but php can be on other OS
            if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH==1) {
                if( crypt( $pass_clear, $pass_hash ) == $pass_hash )
                    $ret=true;
            } else {
                array_push($msgs,
                           _("Unable to validate user's password."),
                           _("Blowfish is not supported by webserver's system crypt library."));
            }
        } elseif( strstr( $pass_hash, '$1$' ) ) { // Check if it's md5 crypt
            // check CRYPT_MD5 here.
            // ldap server might support it, but php might be on other OS
            if (defined('CRYPT_MD5') && CRYPT_MD5==1) {
                list(,$type,$salt,$hash) = explode('$',$pass_hash);
                if( crypt( $pass_clear, '$1$' .$salt ) == $pass_hash )
                    $ret=true;
            } else {
                array_push($msgs,
                           _("Unable to validate user's password."),
                           _("MD5 is not supported by webserver's system crypt library."));
            }
        } elseif( strstr( $pass_hash, '_' ) ) { // Check if it's extended des crypt
            // check CRYPT_EXT_DES here.
            // ldap server might support it, but php might be on other OS
            if (defined('CRYPT_EXT_DES') && CRYPT_EXT_DES==1) {
                if( crypt( $pass_clear, $pass_hash ) == $pass_hash )
                    $ret=true;
            } else {
                array_push($msgs,
                           _("Unable to validate user's password."),
                           _("Extended DES crypt is not supported by webserver's system crypt library."));
            }
        } else {
            // it is possible that this test is useless and any crypt library supports it, but ...
            if (defined('CRYPT_STD_DES') && CRYPT_STD_DES==1) {
                // plain crypt password
                if( crypt($pass_clear, $pass_hash ) == $pass_hash )
                    $ret=true;
            } else {
                array_push($msgs,
                           _("Unable to validate user's password."),
                           _("Standard DES crypt is not supported by webserver's system crypt library."));
            }
        }
        break;
    // No crypt is given, assume plaintext passwords are used
    default:
        if( $pass_clear == $pass_hash )
            $ret=true;
        break;
    }
    if (! $ret && empty($msgs)) {
        array_push($msgs,CPW_CURRENT_NOMATCH);
    }
    return $ret;
}
