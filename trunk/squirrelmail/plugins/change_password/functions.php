<?php

/**
 * functions.php - Change Password plugin
 *
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Will verify the input against a set of criteria:
 * is every field supplied, does verify password match,
 * does current password validate, ..
 * These criteria are (for now) backend-independent.
 *
 * @return array Array with zero or more error messages.
 */
function cpw_check_input()
{
    global $cpw_pass_min_length, $cpw_pass_max_length;

    // formdata
    sqgetGlobalVar('cpw_curpass', $currentpw, SQ_POST);
    sqgetGlobalVar('cpw_newpass', $newpw,     SQ_POST);
    sqgetGlobalVar('cpw_verify',  $verifypw,  SQ_POST);
    // for decrypting current password
    sqgetGlobalVar('key',         $key,       SQ_COOKIE);
    sqgetGlobalVar('onetimepad',  $onetimepad,SQ_SESSION);

    $msg = array();

    if(!$newpw) {
        $msg[] = _("You must type in a new password.");
    }
    if(!$verifypw) {
        $msg[] = _("You must also type in your new password in the verify box.");
    } elseif ($verifypw != $newpw) {
        $msg[] = _("Your new password does not match the verify password.");
    }

    $orig_pw = OneTimePadDecrypt($key, $onetimepad);

    if(!$currentpw) {
        $msg[] = _("You must type in your current password.");
    } elseif ($currentpw != $orig_pw) {
        $msg[] = _("Your current password is not correct.");
    }

    if($newpw && (strlen($newpw) < $cpw_pass_min_length ||
                  strlen($newpw) > $cpw_pass_max_length ) ) {
        $msg[] = sprintf(_("Your new password should be %s to %s characters long."),
                 $cpw_pass_min_length, $cpw_pass_max_length);
    }

    // do we need to do checks that are backend-specific and should
    // be handled by a hook? I know of none now, bnd those checks can
    // also be done in the backend dochange() function. If there turns
    // out to be a need for it we can add a hook for that here.

    return $msg;
}


define('CPW_CURRENT_NOMATCH', _("Your current password is not correct."));
define('CPW_INVALID_PW', _("Your new password contains invalid characters."));

/**
 * Does the actual password changing (meaning it calls the hook function
 * from the backend that does this. If something goes wrong, return error
 * message(s). If everything ok, change the password in the session so the
 * user doesn't have to log out, and redirect back to the options screen.
 */
function cpw_do_change()
{
    global $cpw_backend;
    sqgetGlobalVar('cpw_curpass', $curpw,      SQ_POST);
    sqgetGlobalVar('cpw_newpass', $newpw,      SQ_POST);
    sqgetGlobalVar('base_uri',    $base_uri,   SQ_SESSION);
    sqgetGlobalVar('onetimepad',  $onetimepad, SQ_SESSION);
    sqgetGlobalVar('key',         $key,        SQ_COOKIE);
    sqgetGlobalVar('username',    $username,   SQ_SESSION);

    require_once(SM_PATH . 'plugins/change_password/backend/'.$cpw_backend.'.php');

    $msgs = do_hook('change_password_dochange',
        $temp=array (
            'username' => &$username,
            'curpw' => &$curpw,
            'newpw' => &$newpw
        ) );

    /* something bad happened, return */
    if(count($msgs) > 0) {
        return $msgs;
    }

    /* update our password stored in the session */
    $onetimepad = OneTimePadCreate(strlen($newpw));
    sqsession_register($onetimepad,'onetimepad');
    $key = OneTimePadEncrypt($newpw, $onetimepad);
    sqsetcookie('key', $key, 0, $base_uri);

    /* make sure we write the session data before we redirect */
    session_write_close();
    header('Location: '.SM_PATH. 'src/options.php?optmode=submit&optpage=change_password&plugin_change_password=1&smtoken=' . sm_generate_security_token());
    exit;
}

