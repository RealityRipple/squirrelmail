<?php
/**
 * right_main.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is where the mailboxes are listed. This controls most of what
 * goes on in SquirrelMail.
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/plugin.php');

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
sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('base_uri',  $base_uri,      SQ_SESSION);

sqgetGlobalVar('mailbox',   $mailbox);
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);
sqgetGlobalVar('numMessages'      , $numMessages,       SQ_SESSION);
sqgetGlobalVar('session',           $session,           SQ_GET);
sqgetGlobalVar('note',              $note,              SQ_GET);
sqgetGlobalVar('mail_sent',         $mail_sent,         SQ_GET);
sqgetGlobalVar('use_mailbox_cache', $use_mailbox_cache, SQ_GET);

if ( sqgetGlobalVar('startMessage', $temp) ) {
  $startMessage = (int) $temp;
}
if ( sqgetGlobalVar('PG_SHOWNUM', $temp) ) {
  $PG_SHOWNUM = (int) $temp;
}
if ( sqgetGlobalVar('PG_SHOWALL', $temp, SQ_GET) ) {
  $PG_SHOWALL = (int) $temp;
}
if ( sqgetGlobalVar('newsort', $temp, SQ_GET) ) {
  $newsort = (int) $temp;
}
if ( sqgetGlobalVar('checkall', $temp, SQ_GET) ) {
  $checkall = (int) $temp;
}
if ( sqgetGlobalVar('set_thread', $temp, SQ_GET) ) {
  $set_thread = (int) $temp;
}
if ( !sqgetGlobalVar('composenew', $composenew, SQ_GET) ) {
    $composenew = false;
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

if (isset($newsort) ) {
    if ( $newsort != $sort )
        setPref($data_dir, $username, 'sort', $newsort);

    $sort = $newsort;
    sqsession_register($sort, 'sort');
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

$aMbxResponse = sqimap_mailbox_select($imapConnection, $mailbox);
$aMbxResponse['SORT_ARRAY'] = false;

sqgetGlobalVar('aLastSelectedMailbox',$aLastSelectedMailbox,SQ_SESSION);

// deal with imap servers that do not return the required UIDNEXT or
// UIDVALIDITY response
// from a SELECT call (since rfc 3501 it's required)
if (!isset($aMbxResponse['UIDNEXT']) || !isset($aMbxResponse['UIDVALIDITY'])) {
    $aStatus = sqimap_status_messages($imapConnection,$mailbox,
                                      array('UIDNEXT','UIDVALIDITY'));
    $aMbxResponse['UIDNEXT'] = $aStatus['UIDNEXT'];
    $aMbxResponse['UIDVALIDTY'] = $aStatus['UIDVALIDITY'];
}

if ($aLastSelectedMailbox && !isset($newsort)) {
    // check if we deal with the same mailbox
    if ($aLastSelectedMailbox['NAME'] == $mailbox) {
       if ($aLastSelectedMailbox['EXISTS'] == $aMbxResponse['EXISTS'] &&
           $aLastSelectedMailbox['UIDVALIDITY'] == $aMbxResponse['UIDVALIDITY'] &&
           $aLastSelectedMailbox['UIDNEXT']  == $aMbxResponse['UIDNEXT']) {
           // sort is still valid
           sqgetGlobalVar('server_sort_array',$server_sort_array,SQ_SESSION);
           if ($server_sort_array && is_array($server_sort_array)) {
               $aMbxResponse['SORT_ARRAY'] = $server_sort_array;
           }
       }
    } 
}
 
$aLastSelectedMailbox['NAME'] = $mailbox;
$aLastSelectedMailbox['EXISTS'] = $aMbxResponse['EXISTS'];
$aLastSelectedMailbox['UIDVALIDITY'] = $aMbxResponse['UIDVALIDITY'];
$aLastSelectedMailbox['UIDNEXT'] = $aMbxResponse['UIDNEXT'];

if ($composenew) {
    $comp_uri = SM_PATH . 'src/compose.php?mailbox='. urlencode($mailbox).
        "&session=$session";
    displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri');", false);
} else {
    displayPageHeader($color, $mailbox);
}
do_hook('right_main_after_header');

/* display a message to the user that their mail has been sent */
if (isset($mail_sent) && $mail_sent == 'yes') {
    $note = _("Your Message has been sent.");
}
if (isset($note)) {
    echo html_tag( 'div', '<b>' . $note .'</b>', 'center' ) . "<br>\n";
}

if ( sqgetGlobalVar('just_logged_in', $just_logged_in, SQ_SESSION) ) {
    if ($just_logged_in == true) {
        $just_logged_in = false;
        sqsession_register($just_logged_in, 'just_logged_in');

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


/*********************************************************************
 * Check to see if we can use cache or not. Currently the only time  *
 * when you will not use it is when a link on the left hand frame is *
 * used. Also check to make sure we actually have the array in the   *
 * registered session data.  :)                                      *
 *********************************************************************/
if (! isset($use_mailbox_cache)) {
    $use_mailbox_cache = 0;
}

if ($use_mailbox_cache && sqsession_is_registered('msgs')) {
    showMessagesForMailbox($imapConnection, $mailbox, $numMessages, 
                           $startMessage, $sort, $color, $show_num, 
                           $use_mailbox_cache, '',$aMbxResponse);
} else {
    if (sqsession_is_registered('msgs')) {
        unset($msgs);
    }

    if (sqsession_is_registered('msort')) {
        unset($msort);
    }

    if (sqsession_is_registered('numMessages')) {
        unset($numMessages);
    }

    $numMessages = $aMbxResponse['EXISTS'];

    showMessagesForMailbox($imapConnection, $mailbox, $numMessages,
                           $startMessage, $sort, $color, $show_num,
                           $use_mailbox_cache,'',$aMbxResponse);

    if (sqsession_is_registered('msgs') && isset($msgs)) {
        sqsession_register($msgs, 'msgs');
    }

    if (sqsession_is_registered('msort') && isset($msort)) {
        sqsession_register($msort, 'msort');
    }

    sqsession_register($numMessages, 'numMessages');
}
do_hook('right_main_bottom');
sqimap_logout ($imapConnection);
echo '</body></html>';

sqsession_register($aLastSelectedMailbox,'aLastSelectedMailbox');

?>
