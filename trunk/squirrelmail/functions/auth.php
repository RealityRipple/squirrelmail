<?php

/**
 * auth.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Contains functions used to do authentication.
 *
 * $Id$
 */

function is_logged_in() {

    if ( session_is_registered('user_is_logged_in') ) {
        return;
    } else {
        global $HTTP_POST_VARS, $PHP_SELF, $session_expired_post, 
	       $session_expired_location;

        /*  First we store some information in the new session to prevent
         *  information-loss.
         */
	$session_expired_post = $HTTP_POST_VARS;
        $session_expired_location = $PHP_SELF;
        if (!session_is_registered('session_expired_post')) {    
           session_register('session_expired_post');
        }
        if (!session_is_registered('session_expired_location')) {
           session_register('session_expired_location');
        }
        include_once( '../functions/display_messages.php' );
        logout_error( _("You must be logged in to access this page.") );
        exit;
    }
}

?>