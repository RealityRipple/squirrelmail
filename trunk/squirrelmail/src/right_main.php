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
sqgetGlobalVar('targetMailbox', $lastTargetMailbox, SQ_POST);
sqgetGlobalVar('note',              $note,              SQ_GET);
sqgetGlobalVar('mail_sent',         $mail_sent,         SQ_GET);


if ( sqgetGlobalVar('startMessage', $temp) ) {
    $startMessage = (int) $temp;
} else {
    $startMessage = 1;
}
// sort => srt because of the changed behaviour which can break new behaviour
if ( sqgetGlobalVar('srt', $temp, SQ_GET) ) {
    $srt = (int) $temp;
}

if ( sqgetGlobalVar('showall', $temp, SQ_GET) ) {
    $showall = (int) $temp;
}

if ( sqgetGlobalVar('checkall', $temp, SQ_GET) ) {
  $checkall = (int) $temp;
}
/* end of get globals */


/* Open an imap connection */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

$mailbox = (isset($mailbox) && $mailbox) ? $mailbox : 'INBOX';

/* compensate for the UW vulnerability. */
if ($imap_server_type == 'uw' && (strstr($mailbox, '../') ||
                                  substr($mailbox, 0, 1) == '/')) {
   $mailbox = 'INBOX';
}
/**
 * Set the global settings for a mailbox and merge them with the usersettings
 * for the mailbox. In the future we can add more mailbox specific preferences
 * preferences.
 */


$aMailboxGlobalPref = array(
                       MBX_PREF_SORT         => 0,
                       MBX_PREF_LIMIT        => (int)  $show_num,
                       MBX_PREF_AUTO_EXPUNGE => (bool) $auto_expunge,
                       MBX_PREF_INTERNALDATE => (bool) getPref($data_dir, $username, 'internal_date_sort')
                    // MBX_PREF_FUTURE       => (var)  $future
                     );

/* not sure if this hook should be capable to alter the global pref array */
do_hook ('generic_header');

$aMailboxPrefSer=getPref($data_dir, $username, "pref_$mailbox");
if ($aMailboxPrefSer) {
    $aMailboxPref = unserialize($aMailboxPrefSer);
} else {
    setUserPref($username,"pref_$mailbox",serialize($aMailboxGlobalPref));
    $aMailboxPref = $aMailboxGlobalPref;
}
if (isset($srt)) {
    $aMailboxPref[MBX_PREF_SORT] = (int) $srt;
}


/**
 * until there is no per mailbox option screen to set prefs we override
 * the mailboxprefs by the default ones
 */
$aMailboxPref[MBX_PREF_LIMIT] = (int)  $show_num;
$aMailboxPref[MBX_PREF_AUTO_EXPUNGE] = (bool) $auto_expunge;
$aMailboxPref[MBX_PREF_INTERNALDATE] = (bool) getPref($data_dir, $username, 'internal_date_sort');


/**
 * system wide admin settings and incoming vars.
 */
$aConfig = array(
                'allow_thread_sort' => $allow_thread_sort,
                'allow_server_sort' => $allow_server_sort,
                'user'              => $username,
                // incoming vars
                'offset' => $startMessage
                );
/**
 * The showall functionality is for the moment added to the config array
 * to avoid storage of the showall link in the mailbox pref. We could change
 * this behaviour later and add it to $aMailboxPref instead
 */
if (isset($showall)) {
   $aConfig['showall'] = $showall;
}

/**
 * Retrieve the mailbox cache from the session.
 */
sqgetGlobalVar('mailbox_cache',$mailbox_cache,SQ_SESSION);


$aMailbox = sqm_api_mailbox_select($imapConnection,$mailbox,$aConfig,$aMailboxPref);


/*
 * After initialisation of the mailbox array it's time to handle the FORM data
 */
$sError = handleMessageListForm($imapConnection,$aMailbox);
if ($sError) {
   $note = $sError;
}

/*
 * If we try to forward messages as attachment we have to open a new window
 * in case of compose in new window or redirect to compose.php
 */
if (isset($aMailbox['FORWARD_SESSION'])) {
    if ($compose_new_win) {
        // write the session in order to make sure that the compose window has
        // access to the composemessages array which is stored in the session
        session_write_close();
        sqsession_is_active();
        $comp_uri = SM_PATH . 'src/compose.php?mailbox='. urlencode($mailbox).
                    '&amp;session='.$aMailbox['FORWARD_SESSION'];
        displayPageHeader($color, $mailbox, "comp_in_new('$comp_uri');", false);
    } else {
        // save mailboxstate
        sqsession_register($aMailbox,'aLastSelectedMailbox');
        session_write_close();
        // we have to redirect to the compose page
        $location = SM_PATH . 'src/compose.php?mailbox='. urlencode($mailbox).
                    '&amp;session='.$aMailbox['FORWARD_SESSION'];
        header("Location: $location");
        exit;
    }
} else {
    displayPageHeader($color, $mailbox);
}

do_hook('right_main_after_header');

/* display a message to the user that their mail has been sent */
if (isset($mail_sent) && $mail_sent == 'yes') {
    $note = _("Your Message has been sent.");
}
if (isset($note)) {
    echo html_tag( 'div', '<b>' . $note .'</b>', 'center' ) . "<br />\n";
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
if ($aMailbox['EXISTS'] > 0) {
    showMessagesForMailbox($imapConnection,$aMailbox);
} else {
    $string = '<b>' . _("THIS FOLDER IS EMPTY") . '</b>';
    echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[9].'">';
    echo '     <tr><td>';
    echo '       <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0" bgcolor="'.$color[4].'">';
    echo '        <tr><td><br />';
    echo '            <table cellpadding="1" cellspacing="5" align="center" border="0">';
    echo '              <tr>' . html_tag( 'td', $string."\n", 'left')
                        . '</tr>';
    echo '            </table>';
    echo '        <br /></td></tr>';
    echo '       </table></td></tr>';
    echo '    </table>';
}

do_hook('right_main_bottom');
sqimap_logout ($imapConnection);
echo '</body></html>';

/* add the mailbox to the cache */
$mailbox_cache[$aMailbox['NAME']] = $aMailbox;
sqsession_register($mailbox_cache,'mailbox_cache');

?>