<?php

    /**
    ** auth.php
    **
    **  Copyright (c) 1999-2001 The Squirrelmail Development Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    ** Contains functions used to do authentication.
    **
    ** $Id$
    **/

    require_once( '../functions/page_header.php' );

    function is_logged_in () {
        global $squirrelmail_language;

        if ( session_is_registered('user_is_logged_in') )
            return;

        set_up_language($squirrelmail_language, true);

        displayHtmlHeader( 'SquirrelMail', '', FALSE );

        echo "<body bgcolor=\"ffffff\">\n" .
            '<br><br><center><b>'.
            _("You must be logged in to access this page.").'</b><br><br>'.
            "<a href=\"../src/login.php\" target=\"_top\">"._("Go to the login page")."</a>\n".
            "</center></body></html>\n";
        exit;
    }

?>
