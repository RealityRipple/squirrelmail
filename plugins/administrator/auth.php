<?php

/*
 *  This function tell other modules what users have access
 *  to the plugin.
 *  
 *  Philippe Mingo
 *  
 *  $Id$
 */
function adm_check_user() {

    GLOBAL $username, $PHP_SELF;

    if ( strpos( 'options.php', $PHP_SELF ) ) {
        $auth = FALSE;
    } else if ( file_exists( '../plugins/administrator/admins' ) ) {
        $auths = file( '../plugins/administrator/admins' );
        $auth = in_array( "$username\n", $auths );
    } else if ( file_exists( '../config/admins' ) ) {
        $auths = file( '../config/admins' );
        $auth = in_array( "$username\n", $auths );
    } else if ( $adm_id = fileowner('../config/config.php') ) {
        $adm = posix_getpwuid( $adm_id );
        $auth = ( $username == $adm['name'] );
    }
    else {
        $auth = FALSE;
    }

    return( $auth );

}

?>
