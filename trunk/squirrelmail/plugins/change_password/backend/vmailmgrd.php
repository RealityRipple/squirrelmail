<?php

/**
 * Change password vmailmgrd backend
 *
 * Backend won't work, if vmail.inc file is not included. vmail.inc file
 * should be part of your vmailmgr install. In some cases it is included in
 * separate package.
 *
 * If you use modified vmail.inc, it must provide vchpass() function that
 * acts same way as stock (vmailmgr v.0.96.9) vmail.inc function call
 * and other vmail.inc functions should use same $vm_tcphost and
 * $vm_tcphost_port globals as used by stock vm_daemon_raw() function call.
 * If you have heavily modified vmail.inc and this backend does not work
 * correctly - recheck, if you can reproduce your problem with stock
 * vmail.inc or adjust backend configuration for your site.
 *
 * Backend also needs vmailmgrd service. You can find information about
 * installing this service in vmailmgr FAQ and vmailmgrd.html.
 *
 * Backend might require functions, that are available only in SquirrelMail
 * v.1.5.1 and v.1.4.4.
 *
 * @author Tomas Kuliavas <tokul at users.sourceforge.net>
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @link http://www.vmailmgr.org vmailmgr site
 * @package plugins
 * @subpackage change_password
 */

/* Default backend configuration */

/**
 * path to vmail.inc
 *
 * This variable must provide full path to vmail.inc file including filename.
 *
 * WARNING: Don't disable this variable. It must be set to correct value or
 * to empty string. If variable is missing, backend can have security problems
 * in some PHP configurations.
 * @global string $vmail_inc_path
 */
global $vmail_inc_path;
$vmail_inc_path='';

/**
 * address of vmailmgrd host.
 *
 * Leave it empty, if you want to use unix socket
 * global is used by vmail.inc functions
 * @global string $vm_tcphost
 */
global $vm_tcphost;
$vm_tcphost='';

/**
 * port of vmailmgrd
 *
 * global is used by vmail.inc functions.
 * @global integer $vm_tcphost_port
 */
global $vm_tcphost_port;
$vm_tcphost_port=322;

/**
 * Option that controls use of 8bit passwords
 * Use of such passwords is not safe, because squirrelmail interface
 * can be running in different charsets.
 * @global boolean
 */
global $cpw_vmailmgrd_8bitpw;
$cpw_vmailmgrd_8bitpw=false;

/* end of backend configuration */

/** load configuration from config.php */
if ( isset($cpw_vmailmgrd) && is_array($cpw_vmailmgrd) && !empty($cpw_vmailmgrd) ) {
    if (isset($cpw_vmailmgrd['vmail_inc_path']))
        $vmail_inc_path=$cpw_vmailmgrd['vmail_inc_path'];
    if (isset($cpw_vmailmgrd['vm_tcphost']))
        $vm_tcphost=$cpw_vmailmgrd['vm_tcphost'];
    if (isset($cpw_vmailmgrd['vm_tcphost_port']))
        $vm_tcphost_port=$cpw_vmailmgrd['vm_tcphost_port'];
    if (isset($cpw_vmailmgrd['8bitpw']))
        $cpw_vmailmgrd_8bitpw=$cpw_vmailmgrd['8bitpw'];
}


/**
 * Init change_password plugin hooks.
 */
global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['vmailmgrd'] =
        'cpw_vmailmgrd_dochange';
$squirrelmail_plugin_hooks['change_password_init']['vmailmgrd'] =
        'cpw_vmailmgrd_init';


/**
 * Use this function to do any backend-specific initialisation,
 * e.g. checking requirements, before the password change form
 * is displayed to the user.
 */
function cpw_vmailmgrd_init(){
    global $vmail_inc_path, $username, $oTemplate;

    if ($vmail_inc_path=='' || ! file_exists($vmail_inc_path)) {
        // $vmail_inc_path is not set or file does not exist
        error_box(_("Incorrent path to vmail.inc file."));
        // close html and stop script execution
        $oTemplate->display('footer.tpl');
        exit();
    }

    include_once($vmail_inc_path);

    if (! function_exists('vchpass')) {
        // included vmail.inc does not have required functions.
        error_box(_("Invalid or corrupted vmail.inc file."));
        // close html and stop script execution
        $oTemplate->display('footer.tpl');
        exit();
    }

    if (! preg_match("/(.*)\@(.*)/", $username)) {
        // username does not match vmailmgr syntax
        error_box(_("Invalid user."));
        // close html and stop script execution
        $oTemplate->display('footer.tpl');
        exit();
    }
}


/**
 * function used to change password in change_password plugin hooks.
 *
 * @param array $data The username/curpw/newpw data.
 * @return array Array of error messages.
 */
function cpw_vmailmgrd_dochange($data)
{
    global $cpw_vmailmgrd_8bitpw;

    /**
     * getting params from hook function.
     */
    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    $msgs = array();

    // check for new 8bit password
    if (! $cpw_vmailmgrd_8bitpw && sq_is8bit($newpw)) {
        // 8bit chars in password when backend is configured to block them
        array_push($msgs,CPW_INVALID_PW);
        return $msgs;
    }

    // extract username and domain
    if (preg_match("/(.*)\@(.*)/", $username, $parts)) {
        $vm_user=$parts[1];
        $vm_domain=$parts[2];
    }

    // check if old password matches
    $vmgrd_response1 = cpw_vmailmgrd_passwd($vm_user,$vm_domain,$curpw,$curpw);
    if ($vmgrd_response1[0]!=0) {
        array_push($msgs, CPW_CURRENT_NOMATCH);
        return $msgs;
    }

    // change password
    $vmgrd_response2 = cpw_vmailmgrd_passwd($vm_user,$vm_domain,$curpw,$newpw);
    if ($vmgrd_response2[0]!=0) {
        // TODO: add vmail.inc error message parser.
        array_push($msgs, cpw_i18n_vmail_response($vmgrd_response2[1]));
    }

    return $msgs;
}

/**
 * function that calls required vmail.inc functions and returns error codes.
 *
 * Information about vmailmgr return codes.
 * vmailmgr functions return array with two keys.
 * Array(
 *    [0] => error code, integer (0=no error)
 *    [1] => error message, string
 * )
 * @return array
 */
function cpw_vmailmgrd_passwd($user,$domain,$oldpass,$newpass) {
    global $vmail_inc_path;

    // variable should be checked by cpw_vmailmgrd_init function
    include_once($vmail_inc_path);

    return vchpass($domain,$oldpass,$user,$newpass);
}

/**
 * Function is used to translate messages returned by vmailmgr
 * php library and vmailmgr daemon.
 * @param string $string vmailmrgd message.
 * @return string translated string.
 */
function cpw_i18n_vmail_response($string) {
    if ($string=='Empty domain') {
        // block one: vchpass responses
        $ret = _("Empty domain");
    } elseif ($string=='Empty domain password') {
        $ret = _("Empty domain password");
    } elseif ($string=='Empty username') {
        $ret = _("Empty username");
    } elseif ($string=='Empty new password') {
        $ret = _("Empty new password");
/*
 * block is disabled in order to reduce load on translators.
 * these error messages should be very rare.
    } elseif ($string=='Invalid or unknown base user or domain') {
        // block two: vmailmgr daemon strings
        $ret = _("Invalid or unknown base user or domain");
    } elseif ($string=='Invalid or unknown virtual user') {
        $ret = _("Invalid or unknown virtual user");
    } elseif ($string=='Invalid or incorrect password') {
        $ret = _("Invalid or incorrect password");
    } elseif ($string=='Unknown operation to stat') {
        $ret = _("Unknown operation to stat");
    } elseif (preg_match("/^Incorrect number of parameters to command (.+)/",$string,$match)) {
        $ret = sprintf(_("Incorrect number of parameters to command %s"),$match[1]);
    } elseif (preg_match("/^Invalid or unknown domain name: (.+)/",$string,$match)) {
        $ret = sprintf(_("Invalid or unknown domain name: %s"),$match[1]);
    } elseif ($string=='Invalid operation') {
        $ret = _("Invalid operation");
    } elseif (preg_match("/^Invalid or unknown base user name: (.+)/",$string,$match)) {
        $ret = sprintf(_("Invalid or unknown base user name: %s"),$match[1]);
    } elseif ($string=='Invalid or incorrect password') {
        $ret = _("Invalid or incorrect password");
    } elseif ($string=='Base user has no virtual password table') {
        $ret = _("Base user has no virtual password table");
    } elseif ($string=='Failed while writing initial OK response') {
        $ret = _("Failed while writing initial OK response");
    } elseif ($string=='Failed while writing list entry') {
        $ret = _("Failed while writing list entry");
    } elseif ($string=='Internal error -- userpass && !mustexist') {
        $ret = _("Internal error -- userpass && !mustexist");
    } elseif ($string=='Invalid or unknown base user or domain') {
        $ret = _("Invalid or unknown base user or domain");
    } elseif ($string=='Incorrect password') {
        $ret = CPW_INVALID_PW;
    } elseif ($string=='User name does not refer to a virtual user') {
        $ret = _("User name does not refer to a virtual user");
    } elseif ($string=='Invalid or unknown virtual user') {
        $ret = _("Invalid or unknown virtual user");
    } elseif ($string=='Virtual user already exists') {
        $ret = _("Virtual user already exists");
    } elseif ($string=='Timed out waiting for remote') {
        $ret = _("Timed out waiting for remote");
    } elseif ($string=='Connection to client lost') {
        $ret = _("Connection to client lost");
    } elseif ($string=="Couldn't decode the command string") {
        $ret = _("Couldn't decode the command string");
    } elseif ($string=='Empty command string') {
        $ret = _("Empty command string");
    } elseif ($string=='Error decoding a command parameter') {
        $ret = _("Error decoding a command parameter");
    } elseif ($string=='read system call failed or was interrupted') {
        $ret = _("read system call failed or was interrupted");
    } elseif ($string=='Short read while reading protocol header') {
        $ret = _("Short read while reading protocol header");
    } elseif ($string=='Invalid protocol from client') {
        $ret = _("Invalid protocol from client");
    } elseif ($string=='Short read while reading message data') {
        $ret = _("Short read while reading message data");
    } elseif ($string=='Error writing response') {
        $ret = _("Error writing response");
*/
    } else {
        // return unknown strings
        $ret = $string;
    }
    return $ret;
}
