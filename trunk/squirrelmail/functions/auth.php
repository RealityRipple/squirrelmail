<?php

/**
 * auth.php
 *
 * Copyright (c) 1999-2001 The Squirrelmail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Contains functions used to do authentication.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below.                              ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

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
