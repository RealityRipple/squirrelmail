<?php

/**
 * MySQL change password backend
 *
 * @author Thijs Kinkhorst <kink at squirrelmail.org>
 * @copyright 2003-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage change_password
 */

/**
 * Config vars
 */

global $mysql_server, $mysql_database, $mysql_table, $mysql_userid_field,
       $mysql_password_field, $mysql_manager_id, $mysql_manager_pw,
       $mysql_saslcrypt, $mysql_unixcrypt, $cpw_mysql;

// Initialize defaults
$mysql_server = 'localhost';
$mysql_database = 'email';
$mysql_table = 'users';

// The names of the user ID and password columns
$mysql_userid_field = 'id';
$mysql_password_field ='password';

// The user to log into MySQL with (must have rights)
$mysql_manager_id = 'email_admin';
$mysql_manager_pw = 'xxxxxxx';

// saslcrypt checked first - if it is 1, UNIX crypt is not used.
$mysql_saslcrypt = 0; // use MySQL password() function
$mysql_unixcrypt = 0; // use UNIX crypt() function

// get overrides from config.
if ( isset($cpw_mysql) && is_array($cpw_mysql) && !empty($cpw_mysql) )
{
  foreach ( $cpw_mysql as $key => $value )
  {
    if ( isset(${'mysql_'.$key}) )
      ${'mysql_'.$key} = $value;
  }
}

global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks['change_password_dochange']['mysql'] =
        'cpw_mysql_dochange';

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
function cpw_mysql_dochange($data)
{
    // unfortunately, we can only pass one parameter to a hook function,
    // so we have to pass it as an array.
    $username = $data['username'];
    $curpw = $data['curpw'];
    $newpw = $data['newpw'];

    $msgs = array();

    global $mysql_server, $mysql_database, $mysql_table, $mysql_userid_field,
           $mysql_password_field, $mysql_manager_id, $mysql_manager_pw,
           $mysql_saslcrypt, $mysql_unixcrypt;

    // TODO: allow to choose between mysql_connect() and mysql_pconnect() functions.
    $ds = mysql_pconnect($mysql_server, $mysql_manager_id, $mysql_manager_pw);
    if (! $ds) {
        array_push($msgs, _("Cannot connect to Database Server, please try later!"));
        return $msgs;
    }
    if (!mysql_select_db($mysql_database, $ds)) {
        array_push($msgs, _("Database not found on server"));
        return $msgs;
    }

    $query_string = 'SELECT ' . $mysql_userid_field . ',' . $mysql_password_field
                  . ' FROM '  . $mysql_table
                  . ' WHERE ' . $mysql_userid_field . '="' . mysql_real_escape_string($username, $ds) .'"'
                  . ' AND ' . $mysql_password_field;

    if ($mysql_saslcrypt) {
        $query_string  .= '=password("'.mysql_real_escape_string($curpw, $ds).'")';
    } elseif ($mysql_unixcrypt) {
        // FIXME: why password field name is used for salting
        $query_string  .= '=encrypt("'.mysql_real_escape_string($curpw, $ds).'", '.$mysql_password_field . ')';
    } else {
        $query_string  .= '="' . mysql_real_escape_string($curpw, $ds) . '"';
    }

    $select_result = mysql_query($query_string, $ds);
    if (!$select_result) {
        array_push($msgs, _("SQL call failed, try again later."));
        return $msgs;
    }

    if (mysql_num_rows($select_result) == 0) {
        array_push($msgs, CPW_CURRENT_NOMATCH);
        return $msgs;
    }
    if (mysql_num_rows($select_result) > 1) {
        //make sure we only have 1 uid
        array_push($msgs, _("Duplicate login entries detected, cannot change password!"));
        return $msgs;
    }

    $update_string = 'UPDATE '. $mysql_table . ' SET ' . $mysql_password_field;

    if ($mysql_saslcrypt) {
        $update_string  .= '=password("'.mysql_real_escape_string($newpw, $ds).'")';
    } elseif ($mysql_unixcrypt) {
        // FIXME: use random salt when you create new password
        $update_string  .= '=encrypt("'.mysql_real_escape_string($newpw, $ds).'", '.$mysql_password_field . ')';
    } else {
        $update_string  .= '="' . mysql_real_escape_string($newpw, $ds) . '"';
    }
    $update_string .= ' WHERE ' . $mysql_userid_field . ' = "' . mysql_real_escape_string($username, $ds) . '"';

    if (!mysql_query($update_string, $ds)) {
        array_push($msgs, _("Password change was not successful!"));
    }

    return $msgs;
}
