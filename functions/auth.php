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

require_once( '../functions/page_header.php' );

/* If a user opens a new session then comes back to this one,
 * $base_uri isn't set because it the session is invalid.
 */

if (! isset($base_uri)) {
    ereg ('(^.*/)[^/]+/[^/]+$', $PHP_SELF, $regs);
    $base_uri = $regs[1];
}

function is_logged_in () {
    global $squirrelmail_language, $frame_top, $base_uri;

    if ( session_is_registered('user_is_logged_in') ) {
        return;
    }

    if (!isset($frame_top) || $frame_top == '' ) {
        $frame_top = '_top';
    }

    set_up_language($squirrelmail_language, true);

    displayHtmlHeader( 'SquirrelMail', '', FALSE );

    echo "<body bgcolor=\"ffffff\">\n" .
         '<br><br><center><b>' .
         _("You must be logged in to access this page.").'</b><br><br>' .
         "<a href=\"$base_uri" . "src/login.php\" target=\"$frame_top\">"._("Go to the login page")."</a>\n" .
         "</center></body></html>\n";
    exit;
}

?>
