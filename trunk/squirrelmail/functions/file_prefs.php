<?php

/**
 * file_prefs.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions for manipulating user preferences in files
 *
 * $Id$
 */

/**
 * Check the preferences into the session cache.
 */
function cachePrefValues($data_dir, $username) {
    global $prefs_are_cached, $prefs_cache;
       
    if ( isset($prefs_are_cached) && $prefs_are_cached) {
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
        include_once(SM_PATH . 'functions/display_messages.php');
        logout_error( sprintf( _("Preference file, %s, does not exist. Log out, and log back in to create a default preference file."), $filename)  );
        exit;
    }

    /* Open the file, or else display an error to the user. */
    if(!$file = @fopen($filename, 'r'))
    {
        include_once(SM_PATH . 'functions/display_messages.php');
        logout_error( sprintf( _("Preference file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename) );
        exit;
    }

    /* Read in the preferences. */
    $highlight_num = 0;
    while (! feof($file)) {
        $pref = trim(fgets($file, 1024));
        $equalsAt = strpos($pref, '=');
        if ($equalsAt > 0) {
            $key = substr($pref, 0, $equalsAt);
            $value = substr($pref, $equalsAt + 1);
            if (substr($key, 0, 9) == 'highlight') {
                $key = 'highlight' . $highlight_num;
                $highlight_num ++;
            }

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
 * Return the value for the prefernce given by $string.
 */
function getPref($data_dir, $username, $string, $default = '') {
    global $prefs_cache;
    $result = '';

    $result = do_hook_function('get_pref_override', array($username, $string));

    if ($result == '') {
        cachePrefValues($data_dir, $username);

        if (isset($prefs_cache[$string])) {
            $result = $prefs_cache[$string];
        } else {
            $result = do_hook_function('get_pref', array($username, $string));
            if ($result == '') {
                $result = $default;
            }
        }
    }

    return ($result);
}

/**
 * Save the preferences for this user.
 */
function savePrefValues($data_dir, $username) {
    global $prefs_cache;
   
    $filename = getHashedFile($username, $data_dir, "$username.pref");

    /* Open the file for writing, or else display an error to the user. */
    if(!$file = @fopen($filename.'.tmp', 'w'))
    {
        include_once(SM_PATH . 'functions/display_messages.php');
        logout_error( sprintf( _("Preference file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename.'.tmp') );
        exit;
    }

    foreach ($prefs_cache as $Key => $Value) {
        if (isset($Value)) {
            fwrite($file, $Key . '=' . $Value . "\n");
        }
    }
    fclose($file);
    copy($filename.'.tmp', $filename);
    unlink($filename.'.tmp');
    chmod($filename, 0600);
}

/**
 * Remove a preference for the current user.
 */
function removePref($data_dir, $username, $string) {
    global $prefs_cache;

    cachePrefValues($data_dir, $username);
 
    if (isset($prefs_cache[$string])) {
        unset($prefs_cache[$string]);
    }
 
    savePrefValues($data_dir, $username);
}

/**
 * Set a there preference $string to $value.
 */
function setPref($data_dir, $username, $string, $value) {
    global $prefs_cache;

    cachePrefValues($data_dir, $username);
    if (isset($prefs_cache[$string]) && ($prefs_cache[$string] == $value)) {
        return;
    }

    if ($value === '') {
        removePref($data_dir, $username, $string);
        return;
    }

    $prefs_cache[$string] = $value;
    savePrefValues($data_dir, $username);
}

/**
 * Check for a preferences file. If one can not be found, create it.
 */
function checkForPrefs($data_dir, $username, $filename = '') {
    /* First, make sure we have the filename. */
    if ($filename == '') {
        $filename = getHashedFile($username, $data_dir, "$username.pref");
    }

    /* Then, check if the file exists. */
    if (!@file_exists($filename) ) {
        /* First, check the $data_dir for the default preference file. */
        $default_pref = $data_dir . 'default_pref';

        /* If it is not there, check the internal data directory. */
        if (!@file_exists($default_pref)) {
            $default_pref = SM_PATH . 'data/default_pref';
        }

        /* Otherwise, report an error. */
        $errTitle = sprintf( _("Error opening %s"), $default_pref );
        if (!file_exists($default_pref)) {
            $errString = $errTitle . "<br>\n" .
                         _("Default preference file not found!") . "<br>\n" .
                         _("Please contact your system administrator and report this error.") . "<br>\n";
            include_once(SM_PATH . 'functions/display_messages.php' );
            logout_error( $errString, $errTitle );
            exit;
        } else if (!@copy($default_pref, $filename)) {
            $uid = 'httpd';
            if (function_exists('posix_getuid')){
                $user_data = posix_getpwuid(posix_getuid());
                $uid = $user_data['name'];
            }
            $errString = $errTitle . '<br>' .
                       _("Could not create initial preference file!") . "<br>\n" .
                       sprintf( _("%s should be writable by user %s"), $data_dir, $uid ) .
                       "<br>\n" . _("Please contact your system administrator and report this error.") . "<br>\n";
            include_once(SM_PATH . 'functions/display_messages.php' );
            logout_error( $errString, $errTitle );
            exit;
        }
    }
}

/**
 * Write the User Signature.
 */
function setSig($data_dir, $username, $number, $value) {
    $filename = getHashedFile($username, $data_dir, "$username.si$number");
    /* Open the file for writing, or else display an error to the user. */
    if(!$file = @fopen($filename.'.tmp', 'w'))
    {
        include_once(SM_PATH . '/functions/display_messages.php' );
        logout_error( sprintf( _("Signature file, %s, could not be opened. Contact your system administrator to resolve this issue."), $filename.'.tmp') );
        exit;
    }
    fwrite($file, $value);
    fclose($file);
    copy($filename.'.tmp',$filename);
    unlink($filename.'.tmp');
}

/**
 * Get the signature.
 */
function getSig($data_dir, $username, $number) {
    $filename = getHashedFile($username, $data_dir, "$username.si$number");
    $sig = '';
    if (file_exists($filename)) {
        /* Open the file, or else display an error to the user. */
        if(!$file = @fopen($filename, 'r'))
        {
            include_once(SM_PATH . 'functions/display_messages.php');
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
