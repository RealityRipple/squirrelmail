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

    if ( sqsession_is_registered('user_is_logged_in') ) {
        return;
    } else {
        global $PHP_SELF, $session_expired_post, 
	       $session_expired_location;

        /*  First we store some information in the new session to prevent
         *  information-loss.
         */
	 
	$session_expired_post = $_POST;
        $session_expired_location = $PHP_SELF;
        if (!sqsession_is_registered('session_expired_post')) {    
           sqsession_register($session_expired_post,'session_expired_post');
        }
        if (!sqsession_is_registered('session_expired_location')) {
           sqsession_register($session_expired_location,'session_expired_location');
        }
        include_once( '../functions/display_messages.php' );
        logout_error( _("You must be logged in to access this page.") );
        exit;
    }
}

?>
