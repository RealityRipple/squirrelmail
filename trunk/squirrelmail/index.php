<?php

/**
 * index.php -- Redirect to the login page.
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Redirects to the login page.
 *
 * $Id$
 */

// are we configured yet?
if( ! file_exists ( 'config/config.php' ) ) {
    echo "<html><body><p><strong>ERROR:</strong> Config file \"<tt>config/config.php</tt>\" not found. " .
        "You need to configure SquirrelMail before you can use it.</p></body></html>";
    exit;
}

// if we are, go ahead to the login page.
header("Location: src/login.php\n\n");

?>
<html></html>
