<?php

/**
 * folders_subscribe.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Subscribe and unsubcribe form folders. 
 * Called from folders.php
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'src/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/display_messages.php');

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

$location = get_location();

if (!isset($mailbox) || !isset($mailbox[0]) || $mailbox[0] == "") {
    header("Location: $location/folders.php");
    sqimap_logout($imapConnection);
    exit(0);
}

if ($method == 'sub') {
    if($no_list_for_subscribe && $imap_server_type == 'cyrus') {
       /* Cyrus, atleast, does not typically allow subscription to
	* nonexistent folders (this is an optional part of IMAP),
        * lets catch it here and report back cleanly. */
       if(!sqimap_mailbox_exists($imapConnection, $mailbox[0])) {
          header("Location: $location/folders.php?success=subscribe-doesnotexist");
          sqimap_logout($imapConnection);
          exit(0);
       }
    }

    for ($i=0; $i < count($mailbox); $i++) {
        $mailbox[$i] = trim($mailbox[$i]);
        sqimap_subscribe ($imapConnection, $mailbox[$i]);
        header("Location: $location/folders.php?success=subscribe");
    }
} else {
    for ($i=0; $i < count($mailbox); $i++) {
        $mailbox[$i] = trim($mailbox[$i]);
        sqimap_unsubscribe ($imapConnection, $mailbox[$i]);
        header("Location: $location/folders.php?success=unsubscribe");
    }
}
sqimap_logout($imapConnection);

/*
displayPageHeader($color, 'None');
echo "<BR><BR><BR><CENTER><B>";
if ($method == "sub") {
    echo _("Subscribed Successfully!");
    echo "</B><BR><BR>";
    echo _("You have been successfully subscribed.");
} else {
    echo _("Unsubscribed Successfully!");
    echo "</B><BR><BR>";
    echo _("You have been successfully unsubscribed.");
}
echo "<BR><A HREF=\"webmail.php?right_frame=folders.php\" TARGET=_top>";
echo _("Click here");
echo "</A> ";
echo _("to continue.");
echo "</CENTER>";
echo "</BODY></HTML>";
*/
?>
