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

    if ( substr( $PHP_SELF, -11 ) <> 'options.php' ) {
    $auth = FALSE;
    } else if ( file_exists( '../plugins/administrator/admins' ) ) {
        $auths = file( '../plugins/administrator/admins' );
        $auth = in_array( "$username\n", $auths );
    } else if ( $adm_id = fileowner('../config/config.php') ) {
        $adm = posix_getpwuid( $adm_id );
        $auth = ( $username == $adm['name'] );
    }

    return( $auth );

}

?>