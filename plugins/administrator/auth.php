<?php

function adm_check_user() {

    GLOBAL $username;

    $auth = FALSE;
    
    if ( file_exists( '../plugins/administrator/admins' ) ) {
        $auths = file( '../plugins/administrator/admins' );
        $auth = in_array( "$username\n", $auths );
    }else if ( $adm_id = fileowner('../config/config.php') ) {
        $adm = posix_getpwuid( $adm_id );
        if ( $username == $adm['name'] ) {
            $auth = TRUE;
        } 
    }

    return( $auth );

}

?>