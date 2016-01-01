<?php

/**
 * Change password backend template
 *
 * This is a template for a password changing mechanism. Currently,
 * this contains two parts: the first is to register your function
 * in the squirrelmail_plugin_hooks global, and the second is
 * the function that does the actual changing.
 *
 * Replace the word template everywhere with a name for your backend.
 *
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Config vars: here's room for config vars specific to your
 * backend.
 */

/**
 * Define here the name of your password changing function.
 */
global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['template'] =
        'cpw_template_dochange';
$squirrelmail_plugin_hooks['change_password_init']['template'] =
        'cpw_template_init';


/**
 * Use this function to do any backend-specific initialization,
 * e.g. checking requirements, before the password change form
 * is displayed to the user.
 */
function cpw_template_init()
{
    global $oTemplate;

    // plugin is not configured. Handle error gracefully.
    error_box(_("No valid backend defined."));
    // close html and stop script execution
    $oTemplate->display('footer.tpl');
    exit();
}


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
function cpw_template_dochange($data)
{
    // unfortunately, we can only pass one parameter to a hook function,
    // so we have to pass it as an array.
    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    $msgs = array();

    // your code here to change the password for $username from
    // $currentpw into $newpw.
    user_error('No valid backend defined: this is just a template', E_USER_ERROR);

    return $msgs;
}