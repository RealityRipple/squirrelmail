<?php

    /**
    ** auth.php
    **
    ** Contains functions used to do authentication.
    **
    ** $Id$
    **/


    function is_logged_in () {
        global $squirrelmail_language;

        if ( session_is_registered('user_is_logged_in') )
            return;

        set_up_language($squirrelmail_language, true);

        echo "<html><body bgcolor=\"ffffff\">\n" .
            '<br><br>echo "<center><b>'.
            _("You must be logged in to access this page.").'</b><br>'.
            "<a href=\"../src/login.php\" target=\"_top\">"._("Go to the login page")."</a>\n".
            "</center></body></html>\n";
        exit;
    }

?>
