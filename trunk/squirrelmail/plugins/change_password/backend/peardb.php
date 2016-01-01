<?php

/**
 * Change password PearDB backend
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/** load Pear DB.
 * Global is needed because library must be loaded before configuration
 * in order to use DB constants.
 */
global $cpw_peardb_detect;
$cpw_peardb_detect=@include_once('DB.php');

/** declare configuration globals */
global $cpw_peardb_dsn, $cpw_peardb_connect_opts, $cpw_peardb_table,
 $cpw_peardb_uid_field, $cpw_peardb_domain_field, $cpw_peardb_passwd_field,
 $cpw_peardb_crypted_passwd, $cpw_peardb_debug;

/**
 * Connection DSN.
 * Any format supported by peardb
 * @global mixed $cpw_peardb_dsn
 */
$cpw_peardb_dsn='';

/**
 * Pear DB connection options
 * @global array $cpw_peardb_connect_opts
 */
$cpw_peardb_connect_opts=array();

/**
 * Table that stores user information
 * @global string $cpw_peardb_table
 */
$cpw_peardb_table='';

/**
 * Field that stores user name
 * @global string $cpw_peardb_uid_field
 */
$cpw_peardb_uid_field='userid';

/**
 * Field that stores domain part of username
 * @global string $cpw_peardb_domain_field
 */
$cpw_peardb_domain_field='';

/**
 * Field that stores password
 * @global string $cpw_peardb_passwd_field
 */
$cpw_peardb_passwd_field='password';

/**
 * Passwords are plaintext or encrypted
 * @global boolean $cpw_peardb_crypted_passwd
 */
$cpw_peardb_crypted_passwd=false;

/**
 * Controls output debugging errors
 * Error messages might contain login and password information.
 * Don't enable on production systems.
 * @global boolean $cpw_peardb_debug
 */
$cpw_peardb_debug=false;

/** configuration overrides */
if ( isset($cpw_peardb) && is_array($cpw_peardb) && !empty($cpw_peardb) ) {
    if (isset($cpw_peardb['dsn']))
        $cpw_peardb_dsn=$cpw_peardb['dsn'];
    if (isset($cpw_peardb['connect_opts']))
        $cpw_peardb_connect_opts=$cpw_peardb['connect_opts'];
    if (isset($cpw_peardb['table']))
        $cpw_peardb_table=$cpw_peardb['table'];
    if (isset($cpw_peardb['uid_field']))
        $cpw_peardb_uid_field=$cpw_peardb['uid_field'];
    if (isset($cpw_peardb['domain_field']))
        $cpw_peardb_domain_field=$cpw_peardb['domain_field'];
    if (isset($cpw_peardb['password_field']))
        $cpw_peardb_passwd_field=$cpw_peardb['password_field'];
    if (isset($cpw_peardb['crypted_passwd']))
        $cpw_peardb_crypted_passwd=true;
    if (isset($cpw_peardb['debug']))
        $cpw_peardb_debug=$cpw_peardb['debug'];
}

/**
 * Define here the name of your password changing function.
 */
global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['peardb'] =
        'cpw_peardb_dochange';
$squirrelmail_plugin_hooks['change_password_init']['peardb'] =
        'cpw_peardb_init';

/**
 * Checks if configuration is correct
 */
function cpw_peardb_init() {
    global $oTemplate, $cpw_peardb_detect, $cpw_peardb_dsn, $cpw_peardb_table;

    if (! $cpw_peardb_detect) {
        error_box(_("Plugin is unable to use PHP Pear DB libraries. PHP Pear includes must be available in your PHP include_path setting."));
        $oTemplate->display('footer.tpl');
        exit();
    }

    // Test required settings
    if ((is_string($cpw_peardb_dsn) && trim($cpw_peardb_dsn)=='')
        || trim($cpw_peardb_table)=='' ) {
        error_box(_("Required change password backend configuration options are missing."));
        $oTemplate->display('footer.tpl');
        exit();
    }
}


/**
 * Changes password
 * @param array data The username/curpw/newpw data.
 * @return array Array of error messages.
 */
function cpw_peardb_dochange($data) {
    global $cpw_peardb_dsn, $cpw_peardb_table, $cpw_peardb_connect_opts, $cpw_peardb_debug,
        $cpw_peardb_uid_field, $cpw_peardb_passwd_field, $cpw_peardb_domain_field,
        $cpw_peardb_crypted_passwd, $domain;

    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    $msgs = array();

    // split user and domain parts from username, if domain field is set and username looks like email.
    if ($cpw_peardb_domain_field!='' && preg_match("/(.*)@(.*)/",$username,$match)) {
        $user=$match[1];
        $user_domain=$match[2];
    } else {
        $user=$username;
        $user_domain=$domain;
    }

    // connect to database and make sure that table exists
    $cpw_db = DB::connect($cpw_peardb_dsn, $cpw_peardb_connect_opts);
    if (PEAR::isError($cpw_db)) {
        array_push($msgs,sprintf(_("Connection error: %s"),sm_encode_html_special_chars($cpw_db->getMessage())));
        if ($cpw_peardb_debug)
            array_push($msgs,sm_encode_html_special_chars($cpw_db->getuserinfo()));
        return $msgs;
    }

    // get table information
    $table_info = $cpw_db->tableinfo($cpw_peardb_table);
    if (PEAR::isError($table_info)) {
        array_push($msgs,sprintf(_("Invalid table name: %s"),sm_encode_html_special_chars($cpw_peardb_table)));
        $cpw_db->disconnect();
        return $msgs;
    }

    if (empty($table_info)) {
        array_push($msgs,_("User table is empty."));
        $cpw_db->disconnect();
        return $msgs;
    }

    $cpw_peardb_uid_check=false;
    $cpw_peardb_passwd_check=false;
    $cpw_peardb_domain_check=(($cpw_peardb_domain_field=='')? true : false);
    foreach($table_info as $key => $field_data) {
        if ($field_data['name']==$cpw_peardb_uid_field)
            $cpw_peardb_uid_check=true;
        if ($field_data['name']==$cpw_peardb_passwd_field)
            $cpw_peardb_passwd_check=true;
        if ($cpw_peardb_domain_field!='' && $field_data['name']==$cpw_peardb_domain_field)
            $cpw_peardb_domain_check=true;
    }
    if (! $cpw_peardb_uid_check) {
        array_push($msgs,_("Invalid uid field."));
    }
    if (! $cpw_peardb_passwd_check) {
        array_push($msgs,_("Invalid password field"));
    }
    if (! $cpw_peardb_domain_check) {
        array_push($msgs,_("Invalid domain field"));
    }
    if (! empty($msgs)) {
        $cpw_db->disconnect();
        return $msgs;
    }

    // find user's entry
    $query='SELECT'
        .' '.$cpw_db->quoteIdentifier($cpw_peardb_uid_field)
        .', '.$cpw_db->quoteIdentifier($cpw_peardb_passwd_field)
        .(($cpw_peardb_domain_field!='') ? ', '.$cpw_db->quoteIdentifier($cpw_peardb_domain_field):'')
        .' FROM '.$cpw_db->quoteIdentifier($cpw_peardb_table)
        .' WHERE '
        .$cpw_db->quoteIdentifier($cpw_peardb_uid_field).'='.$cpw_db->quoteSmart($user)
        .(($cpw_peardb_domain_field!='') ?
          ' AND '.$cpw_db->quoteIdentifier($cpw_peardb_domain_field).'='.$cpw_db->quoteSmart($user_domain):
          '');
    $cpw_res=$cpw_db->query($query);
    if (PEAR::isError($cpw_res)) {
        array_push($msgs,sprintf(_("Query failed: %s"),sm_encode_html_special_chars($cpw_res->getMessage())));
        $cpw_db->disconnect();
        return $msgs;
    }

    // make sure that there is only one user.
    if ($cpw_res->numRows()==0) {
        array_push($msgs,_("Unable to find user in user table."));
        $cpw_db->disconnect();
        return $msgs;
    }

    if ($cpw_res->numRows()>1) {
        array_push($msgs,_("Too many matches found in user table."));
        $cpw_db->disconnect();
        return $msgs;
    }

    // FIXME: process possible errors
    $cpw_res->fetchInto($userdb,DB_FETCHMODE_ASSOC);

    // validate password
    $valid_passwd=false;
    if ($cpw_peardb_crypted_passwd) {
        // detect password type
        $pw_type=cpw_peardb_detect_crypto($userdb[$cpw_peardb_passwd_field]);
        if (! $pw_type) {
            array_push($msgs,_("Unable to detect password crypto algorithm."));
        } else {
            $hashed_pw=cpw_peardb_passwd_hash($curpw,$pw_type,$msgs,$userdb[$cpw_peardb_passwd_field]);
            if ($hashed_pw==$userdb[$cpw_peardb_passwd_field]) {
                $valid_passwd=true;
            }
        }
    } elseif ($userdb[$cpw_peardb_passwd_field]==$curpw) {
        $valid_passwd=true;
    }

    if (! $valid_passwd) {
        array_push($msgs,CPW_CURRENT_NOMATCH);
        $cpw_db->disconnect();
        return $msgs;
    }

    // create new password
    if ($cpw_peardb_crypted_passwd) {
        $hashed_passwd=cpw_peardb_passwd_hash($newpw,$pw_type,$msgs);
    } else {
        $hashed_passwd=$newpw;
    }

    // make sure that password was created
    if (! empty($msgs)) {
        array_push($msgs,_("Unable to encrypt new password."));
        $cpw_db->disconnect();
        return $msgs;
    }

    // create update query
    $update_query='UPDATE '
        . $cpw_db->quoteIdentifier($cpw_peardb_table)
        .' SET '.$cpw_db->quoteIdentifier($cpw_peardb_passwd_field)
        .'='.$cpw_db->quoteSmart($hashed_passwd)
        .' WHERE '.$cpw_db->quoteIdentifier($cpw_peardb_uid_field)
        .'='.$cpw_db->quoteSmart($user)
        .(($cpw_peardb_domain_field!='') ?
          ' AND '.$cpw_db->quoteIdentifier($cpw_peardb_domain_field).'='.$cpw_db->quoteSmart($user_domain) :
          '');

    // update database
    $cpw_res=$cpw_db->query($update_query);

    // check for update error
    if (PEAR::isError($cpw_res)) {
        array_push($msgs,sprintf(_("Unable to set new password: %s"),sm_encode_html_special_chars($cpw_res->getMessage())));
    }

    // close database connection
    $cpw_db->disconnect();

    return $msgs;
}

/**
 * Detects password crypto
 * reports 'crypt' if fails to detect any other crypt
 * @param string $password
 * @return string
 */
function cpw_peardb_detect_crypto($password) {
    $ret = false;

    if (preg_match("/^\{(.+)\}+/",$password,$crypto)) {
        $ret=strtolower($crypto[1]);
    }

    if ($ret=='crypt') {
        // {CRYPT} can be standard des crypt, extended des crypt, md5 crypt or blowfish
        // depends on first salt symbols (ext_des = '_', md5 = '$1$', blowfish = '$2')
        // and length of salt (des = 2 chars, ext_des = 9, md5 = 12, blowfish = 16).
        if (preg_match("/^\{crypt\}\\\$1\\\$+/i",$password)) {
            $ret='tagged_md5crypt';
        } elseif (preg_match("/^\{crypt\}\\\$2+/i",$password)) {
            $ret='tagged_blowfish';
        } elseif (preg_match("/^\{crypt\}_+/i",$password)) {
            $ret='tagged_extcrypt';
        } else {
            $ret='tagged_crypt';
        }
    }

    if (! $ret) {
        if (preg_match("/^\\\$1\\\$+/i",$password)) {
            $ret='md5crypt';
        } elseif (preg_match("/^\\\$2+/i",$password)) {
            $ret='blowfish';
        } elseif (preg_match("/^_+/i",$password)) {
            $ret='extcrypt';
        } else {
            $ret='crypt';
        }
    }
    return $ret;
}

/**
 * Encode password
 * @param string $password plain text password
 * @param string $crypto used crypto
 * @param array $msgs error messages
 * @param string $forced_salt old password used to create password hash for verification
 * @return string hashed password. false, if hashing fails
 */
function cpw_peardb_passwd_hash($password,$crypto,&$msgs,$forced_salt='') {
    global $username;

    $crypto = strtolower($crypto);

    $ret=false;
    $salt='';
    // extra symbols used for random string in crypt salt
    // squirrelmail GenerateRandomString() adds alphanumerics with third argument = 7.
    $extra_salt_chars='./';

    switch($crypto) {
    case 'plain-md5':
        $ret='{PLAIN-MD5}' . md5($password);
        break;
    case 'digest-md5':
        // split username into user and domain parts
        if (preg_match("/(.*)@(.*)/",$username,$match)) {
            $ret='{DIGEST-MD5}' . md5($match[1].':'.$match[2].':'.$password);
        } else {
            array_push($msgs,_("Unable to use digest-md5 crypto."));
        }
        break;
    case 'tagged_crypt':
    case 'crypt':
        if (! defined('CRYPT_STD_DES') || CRYPT_STD_DES==0) {
            array_push($msgs,sprintf(_("Unsupported crypto: %s"),'crypt'));
            break;
        }
        if ($forced_salt=='') {
            $salt=GenerateRandomString(2,$extra_salt_chars,7);
        } else {
            $salt=$forced_salt;
        }
        $ret = ($crypto=='tagged_crypt' ? '{crypt}' : '');
        $ret.= crypt($password,$salt);
        break;
    case 'tagged_md5crypt':
    case 'md5crypt':
        if (! defined('CRYPT_MD5') || CRYPT_MD5==0) {
            array_push($msgs,sprintf(_("Unsupported crypto: %s"),'md5crypt'));
            break;
        }
        if ($forced_salt=='') {
            $salt='$1$' .GenerateRandomString(9,$extra_salt_chars,7);
        } else {
            $salt=$forced_salt;
        }
        $ret = ($crypto=='tagged_md5crypt' ? '{crypt}' : '');
        $ret.= crypt($password,$salt);
        break;
    case 'tagged_extcrypt':
    case 'extcrypt':
        if (! defined('CRYPT_EXT_DES') || CRYPT_EXT_DES==0) {
            array_push($msgs,sprintf(_("Unsupported crypto: %s"),'extcrypt'));
            break;
        }
        if ($forced_salt=='') {
            $salt='_' . GenerateRandomString(8,$extra_salt_chars,7);
        } else {
            $salt=$forced_salt;
        }
        $ret = ($crypto=='tagged_extcrypt' ? '{crypt}' : '');
        $ret.= crypt($password,$salt);
        break;
    case 'tagged_blowfish':
    case 'blowfish':
        if (! defined('CRYPT_BLOWFISH') || CRYPT_BLOWFISH==0) {
            array_push($msgs,sprintf(_("Unsupported crypto: %s"),'blowfish'));
            break;
        }
        if ($forced_salt=='') {
            $salt='$2a$12$' . GenerateRandomString(13,$extra_salt_chars,7);
        } else {
            $salt=$forced_salt;
        }
        $ret = ($crypto=='tagged_blowfish' ? '{crypt}' : '');
        $ret.= crypt($password,$salt);
        break;
    case 'plain':
    case 'plaintext':
        $ret = $password;
        break;
    default:
        array_push($msgs,sprintf(_("Unsupported crypto: %s"),sm_encode_html_special_chars($crypto)));
    }
    return $ret;
}
