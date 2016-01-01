<?php

/**
 * identity.php
 *
 * This contains utility functions for dealing with multiple identities
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @since 1.4.2
 */


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
            $thisem = getPref($data_dir,$username,'email_address' . $i);
            $identities[] = array('full_name' => getPref($data_dir,$username,'full_name' . $i),
            'email_address' => empty($thisem)?$em:$thisem,
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

                    // inform plugins about renumbering of ids
                    $temp = array(&$id, 'default');
                    do_hook('options_identities_renumber', $temp);

                    continue 2;
                } else {
                    $fixed[$i+1] = $ident;
                }
                break;

            case 'move':

                if ($key == ($id - 1)) {
                    $tmp_hold = $ident;

                    // inform plugins about renumbering of ids
                    $temp = array(&$id , $id - 1);
                    do_hook('options_identities_renumber', $temp);

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
                    // inform plugins about deleted id
                    $temp = array(&$action, &$id);
                    do_hook('options_identities_process', $temp);

                    continue 2;
                } else {
                    $fixed[$i] = $ident;
                }
                break;

            // Process actions from plugins and save/update action //
            default:
                /**
                 * send action and id information. number of hook arguments
                 * differs from 1.4.4 or older and 1.5.0. count($args) can
                 * be used to detect modified hook. Older hook does not
                 * provide information that can be useful for plugins.
                 */
                $temp = array(&$action, &$id);
                do_hook('options_identities_process', $temp);

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

/**
 * Construct our "From:" header based on
 * a supplied identity number.
 * Will fall back when no sensible email address has been defined.
 *
 * @param   int $identity   identity# to use
 * @since 1.5.2
 */
function build_from_header($identity = 0) {

    global $domain;

    $idents = get_identities();

    if (! isset($idents[$identity]) ) $identity = 0;

    if ( !empty($idents[$identity]['full_name']) ) {
        $from_name = $idents[$identity]['full_name'];
    }

    $from_mail = $idents[$identity]['email_address'];
    if (strpos($from_mail, '@') === FALSE)
        $from_mail .= '@' . $domain;
    
    if ( isset($from_name) ) {
        $from_name_encoded = encodeHeader('"' . $from_name . '"');
        if ($from_name_encoded != $from_name) {
            return $from_name_encoded . ' <' . $from_mail . '>';
        }
        return '"' . $from_name . '" <' . $from_mail . '>';
    }
    return $from_mail;
}

/**
 * Find a matching identity based on a set of emailaddresses.
 * Will return the first identity to have a matching address.
 * When nothing found, returns the default identity.
 *
 * @param needles   array   list of mailadresses
 * @returns int identity
 * @since 1.5.2
 */
function find_identity($needles) {
    $idents = get_identities();
    if ( count($idents) == 1 || empty($needles) ) return 0;

    foreach ( $idents as $nr => $ident ) {
        if ( isset($ident['email_address']) ) {
            foreach ( $needles as $needle ) {
                if ( strcasecmp($needle, $ident['email_address']) == 0 ) {
                    return $nr;
                }
            }
        }
    }
    return 0;
}
