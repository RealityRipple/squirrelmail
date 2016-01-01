<?php

/**
 * file_prefs.php
 *
 * This contains functions for manipulating user preferences in files
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 * @since 1.2.5
 */


/**
 * Check the preferences into the session cache.
 *
 * @param string $data_dir
 * @param string $username
 *
 * @since 1.1.3
 *
 */
function cachePrefValues($data_dir, $username) {
    global $prefs_are_cached, $prefs_cache;

    sqgetGlobalVar('prefs_are_cached', $prefs_are_cached, SQ_SESSION );
    if ( isset($prefs_are_cached) && $prefs_are_cached) {
        sqgetGlobalVar('prefs_cache', $prefs_cache, SQ_SESSION );
//        sm_print_r($prefs_cache);
//        exit;
        return;
    }

    sqsession_unregister('prefs_cache');
    sqsession_unregister('prefs_are_cached');

    /* Calculate the filename for the user's preference file */
    $filename = getHashedFile($username, $data_dir, "$username.pref");

    /* A call to checkForPrefs here should take eliminate the need for */
    /* this to be called throughout the rest of the SquirrelMail code. */
    checkForPrefs($data_dir, $username, $filename);

    /* Make sure that the preference file now DOES exist. */
    if (!file_exists($filename)) {
        logout_error( sprintf( _("Preference file, %s, does not exist. Log out, and log back in to create a default preference file."), $filename)  );
        exit;
    }

    /* Open the file, or else display an error to the user. */
    if(!$file = @fopen($filename, 'r'))
    {
        logout_error( sprintf( _("Preference file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename) );
        exit;
    }

    /* Read in the preferences. */
    $highlight_num = 0;
    while (! feof($file)) {
        $pref = '';
        /* keep reading a pref until we reach an eol (\n (or \r for macs)) */
        while($read = fgets($file, 1024))
        {
                $pref .= $read;
                if(strpos($read,"\n") || strpos($read,"\r"))
                        break;
        }
        $pref = trim($pref);
        $equalsAt = strpos($pref, '=');
        if ($equalsAt > 0) {
            $key = substr($pref, 0, $equalsAt);
            $value = substr($pref, $equalsAt + 1);

//FIXME: this code is not in db_prefs.php that I can see
            /* this is to 'rescue' old-style highlighting rules. */
            if (substr($key, 0, 9) == 'highlight') {
                $key = 'highlight' . $highlight_num;
                $highlight_num ++;
            }

//FIXME: this code is not in db_prefs.php that I can see
            if ($value != '') {
                $prefs_cache[$key] = $value;
            }
        }
    }
    fclose($file);

    $prefs_are_cached = TRUE;

    sqsession_register($prefs_cache, 'prefs_cache');
    sqsession_register($prefs_are_cached, 'prefs_are_cached');
}

/**
 * Return the value for the desired preference.
 *
 * @param string $data_dir  data directory
 * @param string $username  user name
 * @param string $pref_name preference name
 * @param string $default   (since 1.2.0) default preference value
 *
 * @return mixed
 *
 */
function getPref($data_dir, $username, $pref_name, $default = '') {
    global $prefs_cache;

    $temp = array(&$username, &$pref_name);
    $result = do_hook('get_pref_override', $temp);
    if (is_null($result)) {
        cachePrefValues($data_dir, $username);
        if (isset($prefs_cache[$pref_name])) {
            $result = $prefs_cache[$pref_name];
        } else {
//FIXME: is there a justification for having two prefs hooks so close?  who uses them?
            $temp = array(&$username, &$pref_name);
            $result = do_hook('get_pref', $temp);
            if (is_null($result)) {
                $result = $default;
            }
        }
    }
    return ($result);
}

/**
 * Save the preferences for this user.
 *
 * @param string $data_dir data directory
 * @param string $username user name
 *
 * @since 1.1.3
 *
 */
function savePrefValues($data_dir, $username) {
    global $prefs_cache;

    $filename = getHashedFile($username, $data_dir, "$username.pref");

    /* Open the file for writing, or else display an error to the user. */
    if(!$file = @fopen($filename.'.tmp', 'w'))
    {
        logout_error( sprintf( _("Preference file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename.'.tmp') );
        exit;
    }
    foreach ($prefs_cache as $Key => $Value) {
        if (isset($Value)) {
            if ( sq_fwrite($file, $Key . '=' . $Value . "\n") === FALSE ) {
               logout_error( sprintf( _("Preference file, %s, could not be written. Contact your system administrator to resolve this issue.") , $filename . '.tmp') );
               exit;
            }
        }
    }
    fclose($file);
    if (! @copy($filename . '.tmp',$filename) ) {
        logout_error( sprintf( _("Preference file, %s, could not be copied from temporary file, %s. Contact your system administrator to resolve this issue."), $filename, $filename . '.tmp') );
        exit;
    }
    @unlink($filename . '.tmp');
    @chmod($filename, 0600);
    sqsession_register($prefs_cache , 'prefs_cache');
}

/**
 * Remove a preference for the current user.
 *
 * @param string $data_dir  data directory
 * @param string $username  user name
 * @param string $pref_name preference name
 *
 */
function removePref($data_dir, $username, $pref_name) {
    global $prefs_cache;

    cachePrefValues($data_dir, $username);

    if (isset($prefs_cache[$pref_name])) {
        unset($prefs_cache[$pref_name]);
    }

    savePrefValues($data_dir, $username);
}

/**
 * Set the desired preference setting ($pref_name) 
 * to whatever is in $value.
 *
 * @param string $data_dir  data directory
 * @param string $username  user name
 * @param string $pref_name preference name
 * @param mixed  $value     preference value
 *
 */
function setPref($data_dir, $username, $pref_name, $value) {
    global $prefs_cache;

    cachePrefValues($data_dir, $username);
    if (isset($prefs_cache[$pref_name]) && ($prefs_cache[$pref_name] == $value)) {
        return;
    }

    if ($value === '') {
        removePref($data_dir, $username, $pref_name);
        return;
    }

    $prefs_cache[$pref_name] = $value;
    savePrefValues($data_dir, $username);
}

/**
 * Check for a preferences file. If one can not be found, create it.
 *
 * @param string $data_dir data directory
 * @param string $username user name
 * @param string $filename (since 1.2.0) preference file name.
 *                         (OPTIONAL; default is an empty string,
 *                         in which case the file name is 
 *                         automatically detected)
 *
 */
function checkForPrefs($data_dir, $username, $filename = '') {
    /* First, make sure we have the filename. */
    if ($filename == '') {
        $filename = getHashedFile($username, $data_dir, "$username.pref");
    }

    /* Then, check if the file exists. */
    if (!@file_exists($filename) ) {

        /* If it does not exist, check for default_prefs */

        /* First, check legacy locations: data dir */
        if(substr($data_dir,-1) != '/') {
            $data_dir .= '/';
        }
        $default_pref = $data_dir . 'default_pref';

        /* or legacy location: internal data dir */
        if (!@file_exists($default_pref)) {
            $default_pref = SM_PATH . 'data/default_pref';
        }

        /* If no legacies, check where we'd expect it to be located:
         * under config/ */
        if (!@file_exists($default_pref)) {
            $default_pref = SM_PATH . 'config/default_pref';
        }

        /* If a default_pref file found, try to copy it, if none found,
         * try to create an empty one. If that fails, report an error.
         */
        if (
            ( is_readable($default_pref) && !@copy($default_pref, $filename) ) ||
            !@touch($filename)
        ) {
            $uid = 'that the web server is running as';
            if (function_exists('posix_getuid')){
                $user_data = posix_getpwuid(posix_getuid());
                $uid = $user_data['name'];
            }
            $errTitle = _("Could not create initial preference file!");
            $errString = $errTitle . "\n" .
                       sprintf( _("%s should be writable by the user %s."), $data_dir, $uid ) . "\n" .
                       _("Please contact your system administrator and report this error.") ;
            logout_error( $errString, $errTitle );
            exit;
        }
    }
}

/**
 * Write the User Signature.
 *
 * @param string $data_dir data directory
 * @param string $username user name
 * @param integer $number  (since 1.2.5) identity number.
 *                         (before 1.2.5., this parameter
 *                         was used for the signature value)
 * @param string $value    (since 1.2.5) signature value
 *
 */
function setSig($data_dir, $username, $number, $value) {
    // Limit signature size to 64KB (database BLOB limit)
    if (strlen($value)>65536) {
        error_option_save(_("Signature is too big."));
        return;
    }
    $filename = getHashedFile($username, $data_dir, "$username.si$number");
    /* Open the file for writing, or else display an error to the user. */
    if(!$file = @fopen("$filename.tmp", 'w')) {
        logout_error( sprintf( _("Signature file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename . '.tmp') );
        exit;
    }
    if ( sq_fwrite($file, $value) === FALSE ) {
       logout_error( sprintf( _("Signature file, %s, could not be written. Contact your system administrator to resolve this issue.") , $filename . '.tmp'));
       exit;
    }
    fclose($file);
    if (! @copy($filename . '.tmp',$filename) ) {
       logout_error( sprintf( _("Signature file, %s, could not be copied from temporary file, %s. Contact your system administrator to resolve this issue."), $filename, $filename . '.tmp') );
       exit;
    }
    @unlink($filename . '.tmp');
    @chmod($filename, 0600);

}

/**
 * Get the signature.
 *
 * @param string  $data_dir data directory
 * @param string  $username user name
 * @param integer $number   (since 1.2.5) identity number
 *
 * @return string signature
 *
 */
function getSig($data_dir, $username, $number) {
    $filename = getHashedFile($username, $data_dir, "$username.si$number");
    $sig = '';
    if (file_exists($filename)) {
        /* Open the file, or else display an error to the user. */
        if(!$file = @fopen($filename, 'r'))
        {
            logout_error( sprintf( _("Signature file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename) );
            exit;
        }
        while (!feof($file)) {
            $sig .= fgets($file, 1024);
        }
        fclose($file);
    }
    return $sig;
}

// vim: et ts=4
