<?php

/**
 * identity.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains utility functions for dealing with multiple identities
 *
 * @version $Id$
 * @package squirrelmail
 * @since 1.4.2
 */

/** Used to simplify includes
 * @ignore
 */
if (!defined('SM_PATH')) {
    define('SM_PATH','../');
}

/** preference and signature functions */
include_once(SM_PATH . 'include/load_prefs.php');

/**
 * Returns an array of all the identities.
 * Array is keyed: full_name, reply_to, email_address, index, signature
 * @return array full_name,reply_to,email_address,index,signature
 * @since 1.4.2
 */
function get_identities() {

    global $username, $data_dir, $domain;

    $em = getPref($data_dir,$username,'email_address');
    if ( ! $em ) {
        if (strpos($username , '@') == false) {
            $em = $username.'@'.$domain;
        } else {
            $em = $username;
        }
    }
    $identities = array();
    /* We always have this one, even if the user doesn't use multiple identities */
    $identities[] = array('full_name' => getPref($data_dir,$username,'full_name'),
        'email_address' => $em,
        'reply_to' => getPref($data_dir,$username,'reply_to'),
        'signature' => getSig($data_dir,$username,'g'),
        'index' => 0 );

    $num_ids = getPref($data_dir,$username,'identities');
    /* If there are any others, add them to the array */
    if (!empty($num_ids) && $num_ids > 1) {
        for ($i=1;$i<$num_ids;$i++) {
            $identities[] = array('full_name' => getPref($data_dir,$username,'full_name' . $i),
            'email_address' => getPref($data_dir,$username,'email_address' . $i),
            'reply_to' => getPref($data_dir,$username,'reply_to' . $i),
            'signature' => getSig($data_dir,$username,$i),
            'index' => $i );
        }
    }

    return $identities;
}

/**
 * Function to save the identities array
 *
 * @param  array     $identities     Array of identities
 * @since 1.5.1 and 1.4.5
 */
function save_identities($identities) {

    global $username, $data_dir, $domain;

    if (empty($identities) || !is_array($identities)) {
        return;
    }


    $num_cur = getPref($data_dir, $username, 'identities');
    
    $cnt = count($identities);

    // Remove any additional identities in prefs //
    for($i=$cnt; $i <= $num_cur; $i++) {
        removePref($data_dir, $username, 'full_name' . $i);
        removePref($data_dir, $username, 'email_address' . $i);
        removePref($data_dir, $username, 'reply_to' . $i);
        setSig($data_dir, $username, $i, '');
    }

    foreach($identities as $id=>$ident) {

        $key = ($id?$id:'');

        setPref($data_dir, $username, 'full_name' . $key, $ident['full_name']);
        setPref($data_dir, $username, 'email_address' . $key, $ident['email_address']);
        setPref($data_dir, $username, 'reply_to' . $key, $ident['reply_to']);

        if ($id === 0) {
            setSig($data_dir, $username, 'g', $ident['signature']);
        } else {
            setSig($data_dir, $username, $key, $ident['signature']);
        }

    }

    setPref($data_dir, $username, 'identities', $cnt);

}

/**
 * Returns an array with a fixed set of identities
 *
 * @param   array       $identities      Array of identities
 * @param   int         $id             Identity to modify
 * @param   string      $action         Action to perform
 * @return  array
 * @since 1.5.1 and 1.4.5
 */
function sqfixidentities( $identities, $id, $action ) {

    $fixed = array();
    $tmp_hold = array();
    $i = 0;

    if (empty($identities) || !is_array($identities)) {
        return $fixed;
    }

    foreach( $identities as $key=>$ident ) {

        if (empty_identity($ident)) {
            continue;
        }

        switch($action) {

            case 'makedefault':

                if ($key == $id) {
                    $fixed[0] = $ident;
                    continue 2;
                } else {
                    $fixed[$i+1] = $ident;
                }
                break;

            case 'move':

                if ($key == ($id - 1)) {
                    $tmp_hold = $ident;
                    continue 2;
                } else {
                    $fixed[$i] = $ident;

                    if ($key == $id) {
                        $i++;
                        $fixed[$i] = $tmp_hold;
                    }
                }
                break;

            case 'delete':

                if ($key == $id) {
                    continue 2;
                } else {
                    $fixed[$i] = $ident;
                }
                break;

            // we should never hit this but just in case //
            default:
                $fixed[$i] = $ident;

        }

        // Inc array index //
        $i++;
    }

    ksort($fixed);
    return $fixed;

}

/**
 * Function to test if identity is empty
 *
 * @param   array   $identity   Identitiy Array
 * @return  boolean
 * @since 1.5.1 and 1.4.5
 */
function empty_identity($ident) {
    if (empty($ident['full_name']) && empty($ident['email_address']) && empty($ident['signature']) && empty($ident['reply_to'])) {
        return true;
    } else {
        return false;
    }
}

?>