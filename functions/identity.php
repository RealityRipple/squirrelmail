<?php

/**
 * identity.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains utility functions for dealing with multiple identities
 *
 * @version $Id$
 * @package squirrelmail
 */

/** Used to simplify includes */
if (!defined('SM_PATH')) {
    define('SM_PATH','../');
}

include_once(SM_PATH . 'include/load_prefs.php');

/**
* Returns an array of all the identities.
* Array is keyed: full_name, reply_to, email_address, index, signature
* @return array full_name,reply_to,email_address,index,signature
*/
function get_identities() {

    global $username, $data_dir;

    $num_ids = getPref($data_dir,$username,'identities');
    $identities = array();
    /* We always have this one, even if the user doesn't use multiple identities */
    $identities[] = array('full_name' => getPref($data_dir,$username,'full_name'),
        'email_address' => getPref($data_dir,$username,'email_address'),
        'reply_to' => getPref($data_dir,$username,'reply_to'),
        'signature' => getSig($data_dir,$username,'g'),
        'index' => 0 );

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

?>
