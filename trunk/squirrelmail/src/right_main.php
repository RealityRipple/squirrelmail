<?php

/**
 * right_main.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is where the mailboxes are listed. This controls most of what
 * goes on in SquirrelMail.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/date.php');
require_once('../functions/array.php');
require_once('../functions/mime.php');
require_once('../functions/mailbox_display.php');
require_once('../functions/display_messages.php');

/***********************************************************
 * incoming variables from URL:                            *
 *   $sort             Direction to sort by date           *
 *                        values:  0  -  descending order  *
 *                        values:  1  -  ascending order   *
 *   $startMessage     Message to start at                 *
 *    $mailbox          Full Mailbox name                  *
 *                                                         *
 * incoming from cookie:                                   *
 *    $username         duh                                *
 *    $key              pass                               *
 ***********************************************************/

$bob = getHashedFile($username, $data_dir, "username.pref");

/* Open a connection on the imap port (143) */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

global $PG_SHOWNUM;
if (isset($PG_SHOWALL)) {
    if ($PG_SHOWALL) {
       $PG_SHOWNUM=999999;
       $show_num=$PG_SHOWNUM;
       session_register('PG_SHOWNUM');
    }
    else {
       session_unregister('PG_SHOWNUM');
       unset($PG_SHOWNUM);
    }
}
else if( isset( $PG_SHOWNUM ) ) {
    $show_num = $PG_SHOWNUM;
}

if (isset($newsort) && $newsort != $sort) {
    setPref($data_dir, $username, 'sort', $newsort);
}



/* If the page has been loaded without a specific mailbox, */
/* send them to the inbox                                  */
if (!isset($mailbox)) {
    $mailbox = 'INBOX';
    $startMessage = 1;
}


if (!isset($startMessage) || ($startMessage == '')) {
    $startMessage = 1;
}

/* compensate for the UW vulnerability. */
if ($imap_server_type == 'uw' && (strstr($mailbox, '../') ||
                                  substr($mailbox, 0, 1) == '/')) {
   $mailbox = 'INBOX';
}

/* decide if we are thread sorting or not */
global $allow_thread_sort;
if ($allow_thread_sort == TRUE) {
    if (isset($set_thread)) {
        if ($set_thread == 1) {
            setPref($data_dir, $username, "thread_$mailbox", 1);
            $thread_sort_messages = '1';    
        }
        elseif ($set_thread == 2)  {
            setPref($data_dir, $username, "thread_$mailbox", 0);
            $thread_sort_messages = '0';    
        }
    }
    else {
        $thread_sort_messages = getPref($data_dir, $username, "thread_$mailbox");
    }
}
else {
    $thread_sort_messages = 0;
} 

global $color;
if( isset($do_hook) && $do_hook ) {
    do_hook ("generic_header");
}

sqimap_mailbox_select($imapConnection, $mailbox);

if (isset($composenew) && $composenew) {
    $comp_uri = "../src/compose.php?mailbox=". urlencode($mailbox).
		"&amp;session=$composesession&amp;attachedmessages=true&amp";

    displayPageHeader($color, $mailbox, "comp_in_new(false,'$comp_uri');", false);
} else {
    displayPageHeader($color, $mailbox);
}
echo "<br>\n";

do_hook('right_main_after_header');
if (isset($note)) {
    echo "<CENTER><B>$note</B></CENTER><BR>\n";
}

if ($just_logged_in == true) {
    $just_logged_in = false;

    if (strlen(trim($motd)) > 0) {
        echo "<br><table align=center width=\"70%\" cellpadding=0 cellspacing=3 border=0 bgcolor=\"$color[9]\">" .
         '<tr><td>' .
             "<table width=\"100%\" cellpadding=5 cellspacing=1 border=0 bgcolor=\"$color[4]\">" .
             "<tr><td align=center>$motd";
        do_hook('motd');
        echo '</td></tr>' .
             '</table>' .
             '</td></tr></table>';
    }
}

if (isset($newsort)) {
    $sort = $newsort;
    session_register('sort');
}

/*********************************************************************
 * Check to see if we can use cache or not. Currently the only time  *
 * when you will not use it is when a link on the left hand frame is *
 * used. Also check to make sure we actually have the array in the   *
 * registered session data.  :)                                      *
 *********************************************************************/
if (! isset($use_mailbox_cache)) {
    $use_mailbox_cache = 0;
}

/* There is a problem with registered vars in 4.1 */
/*
if( substr( phpversion(), 0, 3 ) == '4.1'  ) {
    $use_mailbox_cache = FALSE;
}
*/

if ($use_mailbox_cache && session_is_registered('msgs')) {
    showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color, $show_num, $use_mailbox_cache);
} else {
    if (session_is_registered('msgs')) {
        unset($msgs);
    }

    if (session_is_registered('msort')) {
        unset($msort);
    }

    if (session_is_registered('numMessages')) {
        unset($numMessages);
    }

    $numMessages = sqimap_get_num_messages ($imapConnection, $mailbox);

    showMessagesForMailbox($imapConnection, $mailbox, $numMessages, 
                           $startMessage, $sort, $color, $show_num,
                           $use_mailbox_cache);

    if (session_is_registered('msgs') && isset($msgs)) {
        session_register('msgs');
        $_SESSION['msgs'] = $msgs;
    }

    if (session_is_registered('msort') && isset($msort)) {
        session_register('msort');
        $_SESSION['msort'] = $msort;
    }

    session_register('numMessages');
    $_SESSION['numMessages'] = $numMessages;
}
do_hook('right_main_bottom');
sqimap_logout ($imapConnection);

echo '</BODY></HTML>';

?>
