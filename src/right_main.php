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

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/array.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/html.php');

/***********************************************************
 * incoming variables from URL:                            *
 *   $sort             Direction to sort by date           *
 *                        values:  0  -  descending order  *
 *                        values:  1  -  ascending order   *
 *   $startMessage     Message to start at                 *
 *    $mailbox          Full Mailbox name                  *
 *                                                         *
 * incoming from cookie:                                   *
 *    $key              pass                               *
 * incoming from session:                                  *
 *    $username         duh                                *
 *                                                         *
 ***********************************************************/


/* lets get the global vars we may need */
$username = $_SESSION['username'];
$key  = $_COOKIE['key'];
$onetimepad = $_SESSION['onetimepad'];
$base_uri = $_SESSION['base_uri'];
$delimiter = $_SESSION['delimiter'];
 
if (isset($_GET['startMessage'])) {
    $startMessage = $_GET['startMessage'];
}
if (isset($_GET['mailbox'])) {
    $mailbox = $_GET['mailbox'];
}
if (isset($_GET['PG_SHOWNUM'])) {
    $PG_SHOWNUM = $_GET['PG_SHOWNUM'];
}
elseif (isset($_SESSION['PG_SHOWNUM'])) {
    $PG_SHOWNUM = $_SESSION['PG_SHOWNUM'];
}
if (isset($_GET['PG_SHOWALL'])) {
    $PG_SHOWALL = $_GET['PG_SHOWALL'];
}
if (isset($_GET['newsort'])) {
    $newsort = $_GET['newsort'];
}
if (isset($_GET['checkall'])) {
    $checkall = $_GET['checkall'];
}
if (isset($_GET['set_thread'])) {
    $set_thread = $_GET['set_thread'];
}
if (isset($_SESSION['lastTargetMailbox'])) {
    $lastTargetMailbox =$_SESSION['lastTargetMailbox'];
}

/* end of get globals */


/* Open a connection on the imap port (143) */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

if (isset($PG_SHOWALL)) {
    if ($PG_SHOWALL) {
       $PG_SHOWNUM=999999;
       $show_num=$PG_SHOWNUM;
       sqsession_register($PG_SHOWNUM, 'PG_SHOWNUM');
    }
    else {
       sqsession_unregister('PG_SHOWNUM');
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

do_hook ('generic_header');

sqimap_mailbox_select($imapConnection, $mailbox);

if (isset($composenew) && $composenew) {
    $comp_uri = '../src/compose.php?mailbox='. urlencode($mailbox).
		"&amp;session=$composesession";
    displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri');", false);
} else {
    displayPageHeader($color, $mailbox);
}
do_hook('right_main_after_header');
if (isset($note)) {
    echo html_tag( 'div', '<b>' . $note .'</b>', 'center' ) . "<br>\n";
}

if (isset($_SESSION['just_logged_in'])) {
    $just_logged_in = $_SESSION['just_logged_in'];
    if ($just_logged_in == true) {
        $just_logged_in = false;

        if (strlen(trim($motd)) > 0) {
            echo html_tag( 'table',
                        html_tag( 'tr',
                            html_tag( 'td', 
                                html_tag( 'table',
                                    html_tag( 'tr',
                                        html_tag( 'td', $motd, 'center' )
                                    ) ,
                                '', $color[4], 'width="100%" cellpadding="5" cellspacing="1" border="0"' )
                             )
                        ) ,
                    'center', $color[9], 'width="70%" cellpadding="0" cellspacing="3" border="0"' );
        }
    }
}

if (isset($newsort)) {
    $sort = $newsort;
    sqsession_register($sort, 'sort');
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
        sqsession_register($msgs, 'msgs');
        $_SESSION['msgs'] = $msgs;
    }

    if (session_is_registered('msort') && isset($msort)) {
        sqsession_register($msort, 'msort');
        $_SESSION['msort'] = $msort;
    }

    sqsession_register($numMessages, 'numMessages');
    $_SESSION['numMessages'] = $numMessages;
}
do_hook('right_main_bottom');
sqimap_logout ($imapConnection);

echo '</body></html>';

?>
