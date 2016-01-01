<?php

/**
 * Poppassd change password backend
 *
 * @author Seth Randall <sethr at missoulafcu.org>
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Config vars
 */

/**
 * Set the address of the server your poppass daemon runs on.
 * If it's the same as your imap server, you can leave it blank
 */
global $poppassd_server;

$poppassd_server = '';

/* get overrides from config.php */
if (isset($cpw_poppassd['server'])) $poppassd_server=$cpw_poppassd['server'];

/**
 * Define here the name of your password changing function.
 */
global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['poppassd'] = 'cpw_poppassd_dochange';

/**
 * This is the function that is specific to your backend. It takes
 * the current password (as supplied by the user) and the desired
 * new password. It will return an array of messages. If everything
 * was successful, the array will be empty. Else, it will contain
 * the errormessage(s).
 * Constants to be used for these messages:
 * CPW_CURRENT_NOMATCH -> "Your current password is not correct."
 * CPW_INVALID_PW -> "Your new password contains invalid characters."
 *
 * @param array data The username/currentpw/newpw data.
 * @return array Array of error messages.
 */
function cpw_poppassd_dochange($data) {
    // unfortunately, we can only pass one parameter to a hook function,
    // so we have to pass it as an array.
    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    $msgs = array();

    // your code here to change the password for $username from
    // $currentpw into $newpw.
    $msgs = cpw_poppassd_go($username, $curpw, $newpw, 0);

    return $msgs;
}

function cpw_poppassd_closeport($pop_socket, &$messages, $debug = 0) {
    if ($debug) {
        array_push($messages, _("Closing Connection"));
    }
    fputs($pop_socket, "quit\r\n");
    fclose($pop_socket);
}

function cpw_poppassd_readfb($pop_socket, &$result, &$messages, $debug = 0) {
   $strResp = '';
   $result  = '';

   if (!feof($pop_socket)) {
      $strResp = fgets($pop_socket, 1024);
      $result  = substr(trim($strResp), 0, 3);  // 200, 500
      if(!preg_match('/^[23]\d\d/', $result) || $debug) {
          $messages[] = "--> $strResp";
      }
   }
}

function cpw_poppassd_go($username, $old_pw, $new_pw, $debug = 0) {
    global $poppassd_server;
    global $imapServerAddress;

    /** sqimap_get_user_server() function */
    include_once(SM_PATH . 'functions/imap_general.php');

    if($poppassd_server == '') {
        // if poppassd address is not set, use imap server's address
        // make sure that setting contains address and not mapping
        $poppassd_server = sqimap_get_user_server($imapServerAddress,$username);
    }

    $messages = array();

    if ($debug) {
        $messages[] = _("Connecting to Password Server");
    }
    $pop_socket = fsockopen($poppassd_server, 106, $errno, $errstr);
    if (!$pop_socket) {
        $messages[] = _("ERROR") . ': ' . "$errstr ($errno)";
        return $messages;
    }

    cpw_poppassd_readfb($pop_socket, $result, $messages, $debug);
    if(!preg_match('/^2\d\d/', $result) ) {
        cpw_poppassd_closeport($pop_socket, $messages, $debug);
        return $messages;
    }

    fputs($pop_socket, "user $username\r\n");
    cpw_poppassd_readfb($pop_socket, $result, $messages, $debug);
    if(!preg_match('/^[23]\d\d/', $result) ) {
        cpw_poppassd_closeport($pop_socket, $messages, $debug);
        return $messages;
    }

    fputs($pop_socket, "pass $old_pw\r\n");
    cpw_poppassd_readfb($pop_socket, $result, $messages, $debug);
    if(!preg_match('/^[23]\d\d/', $result) ) {
        cpw_poppassd_closeport($pop_socket, $messages, $debug);
        return $messages;
    }

    fputs($pop_socket, "newpass $new_pw\r\n");
    cpw_poppassd_readfb($pop_socket, $result, $messages, $debug);
    cpw_poppassd_closeport($pop_socket, $messages, $debug);
    if(!preg_match('/^2\d\d/', $result) ) {
        return $messages;
    }

    return $messages;
}
