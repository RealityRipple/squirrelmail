<?php

/**
 * right_main.php
 *
 * This is where the mailboxes are listed. This controls most of what
 * goes on in SquirrelMail.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the right_main page */
define('PAGE_NAME', 'right_main');

//xdebug_start_profiling("/var/spool/xdebug/right_main.txt");

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

/* If email_address not set and admin wants us to ask user for it,
 * redirect to options page. */
if ( $ask_user_info && getPref($data_dir, $username,'email_address') == "" ) {
    header("Location: " . get_location() . "/options.php?optpage=personal");
    exit;
}

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_asearch.php');
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/compose.php');

/* lets get the global vars we may need */
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('delayed_errors',  $delayed_errors,  SQ_SESSION);
if (is_array($delayed_errors)) {
    $oErrorHandler->AssignDelayedErrors($delayed_errors);
    sqsession_unregister("delayed_errors");
}
sqgetGlobalVar('mailbox',   $mailbox);
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);
sqgetGlobalVar('targetMailbox', $lastTargetMailbox, SQ_POST);
sqgetGlobalVar('note',              $note,              SQ_GET);
sqgetGlobalVar('mail_sent',         $mail_sent,         SQ_GET);


if ( sqGetGlobalVarMultiple('startMessage', $temp, 'paginator_submit') ) {
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

if (!sqgetGlobalVar('checkall',$checkall,SQ_GET)) {
    $checkall = false;
}

if (!sqgetGlobalVar('preselected', $preselected, SQ_GET) || !is_array($preselected)) {
    $preselected = array();
} else {
    $preselected = array_keys($preselected);
}

/* future work */
if ( sqgetGlobalVar('account', $account, SQ_GET) ) {
  $account = (int) $account;
} else {
  $account = 0;
}

/* end of get globals */


/* Open an imap connection */

global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);

$mailbox = (isset($mailbox) && $mailbox) ? $mailbox : 'INBOX';

/**
 * Set the global settings for a mailbox and merge them with the usersettings
 * for the mailbox. In the future we can add more mailbox specific preferences
 * preferences.
 */

$aMailboxPrefSer=getPref($data_dir, $username,'pref_'.$account.'_'.$mailbox);
if ($aMailboxPrefSer) {
    $aMailboxPref = unserialize($aMailboxPrefSer);
    $aMailboxPref[MBX_PREF_COLUMNS] = $index_order; // index_order contains the columns to show and the order of the columns
} else {
    setUserPref($username,'pref_'.$account.'_'.$mailbox,serialize($default_mailbox_pref));
    $aMailboxPref = $default_mailbox_pref;
}
if (isset($srt)) {
    $aMailboxPref[MBX_PREF_SORT] = (int) $srt;
}

$trash_folder = (isset($trash_folder)) ? $trash_folder : false;
$sent_folder = (isset($sent_folder)) ? $sent_folder : false;
$draft_folder = (isset($draft_folder)) ? $draft_folder : false;


/**
 * until there is no per mailbox option screen to set prefs we override
 * the mailboxprefs by the default ones
 */
$aMailboxPref[MBX_PREF_LIMIT] = (int)  $show_num;
$aMailboxPref[MBX_PREF_AUTO_EXPUNGE] = (bool) $auto_expunge;
$aMailboxPref[MBX_PREF_INTERNALDATE] = (bool) getPref($data_dir, $username, 'internal_date_sort');
$aMailboxPref[MBX_PREF_COLUMNS] = $index_order;

/**
 * Replace From => To  in case it concerns a draft or sent folder
 */
if (handleAsSent($mailbox)
 && !in_array(SQM_COL_TO,$aMailboxPref[MBX_PREF_COLUMNS])) {
    $aNewOrder = array(); // nice var name ;)
    foreach($aMailboxPref[MBX_PREF_COLUMNS] as $iCol) {
        if ($iCol == SQM_COL_FROM) {
            $iCol = SQM_COL_TO;
        }
        $aNewOrder[] = $iCol;
   }
   $aMailboxPref[MBX_PREF_COLUMNS] = $aNewOrder;
   setUserPref($username,'pref_'.$account.'_'.$mailbox,serialize($aMailboxPref));
}



/**
 * Set the config options for the messages list
 */
$aColumns = array(); // contains settings per column. Switch to key -> value based array, order is the order of the array keys
foreach ($aMailboxPref[MBX_PREF_COLUMNS] as $iCol) {
    $aColumns[$iCol] = array();
    switch ($iCol) {
        case SQM_COL_SUBJ:
            if ($truncate_subject) {
                $aColumns[$iCol]['truncate'] = $truncate_subject;
            }
            break;
        case SQM_COL_FROM:
        case SQM_COL_TO:
        case SQM_COL_CC:
        case SQM_COL_BCC:
            if ($truncate_sender) {
                $aColumns[$iCol]['truncate'] = $truncate_sender;
            }
            break;
   }
}

/**
 * Properties required by showMessagesForMailbox
 */
$aProps = array(
    'columns' => $aColumns, // columns bound settings
    'config'  => array('alt_index_colors'       => $alt_index_colors,       // alternating row colors (should be a template thing)
                        'highlight_list'        => $message_highlight_list, // row highlighting rules
                        'fancy_index_highlite'  => $fancy_index_highlite,   // highlight rows on hover or on click -> check
                        'show_flag_buttons'     => (isset($show_flag_buttons)) ? $show_flag_buttons : true,
                        'lastTargetMailbox'     => (isset($lastTargetMailbox)) ? $lastTargetMailbox : '', // last mailbox where messages are moved/copied to
                        'trash_folder'          => $trash_folder,
                        'sent_folder'           => $sent_folder,
                        'draft_folder'          => $draft_folder,
                        'color'                 => $color,
                        'enablesort'            => true // enable sorting on columns
                ),
    'mailbox' => $mailbox,
    'account' => (isset($account)) ? $account : 0, // future usage if we support multiple imap accounts
    'module' => 'read_body',
    'email'  => false);


/**
 * system wide admin settings and incoming vars.
 */
$aConfig = array(
                'user'              => $username,
                // incoming vars
                'offset' => $startMessage // offset in paginator
                );
/**
 * The showall functionality is for the moment added to the config array
 * to avoid storage of the showall link in the mailbox pref. We could change
 * this behaviour later and add it to $aMailboxPref instead
 */
if (isset($showall)) {
    $aConfig['showall'] = $showall; // show all messages in a mailbox (paginator is disabled)
} else {
    $showall = false;
}


/**
 * Retrieve the mailbox cache from the session.
 */
sqgetGlobalVar('mailbox_cache',$mailbox_cache,SQ_SESSION);

/**
 * Select the mailbox and retrieve the cached info.
 */
$aMailbox = sqm_api_mailbox_select($imapConnection,$account, $mailbox,$aConfig,$aMailboxPref);

/**
 * MOVE THIS to a central init section !!!!
 */
if (!sqgetGlobalVar('align',$align,SQ_SESSION)) {
    $dir = ( isset( $languages[$squirrelmail_language]['DIR']) ) ? $languages[$squirrelmail_language]['DIR'] : 'ltr';
    if ( $dir == 'ltr' ) {
        $align = array('left' => 'left', 'right' => 'right');
    } else {
        $align = array('left' => 'right', 'right' => 'left');
    }
    sqsession_register($align, 'align');
}

/*
 * After initialisation of the mailbox array it's time to handle the FORM data
 */
$sError = handleMessageListForm($imapConnection,$aMailbox);
if ($sError) {
   $note = $sError;
} else if (sqgetGlobalVar('REQUEST_METHOD', $req_method, SQ_SERVER) && $req_method == 'POST') {
   $preselected = array(); // clear pre-checked checkboxes when action succeeded
}



/*
 * If we try to forward messages as attachment we have to open a new window
 * in case of compose in new window or redirect to compose.php
 */
$onload = '';
if (isset($aMailbox['FORWARD_SESSION'])) {
    if ($compose_new_win) {
        /* add the mailbox to the cache */
        $mailbox_cache[$account.'_'.$aMailbox['NAME']] = $aMailbox;
        sqsession_register($mailbox_cache,'mailbox_cache');
        // write the session in order to make sure that the compose window has
        // access to the composemessages array which is stored in the session
        session_write_close();
        // restart the session. Do not use sqsession_is_active because the session_id
        // isn't empty after a session_write_close
        sqsession_start();
        if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
            $compose_width = '640';
        }
        if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
            $compose_height = '550';
        }
        // do not use &amp;, it will break the query string and $session will not be detected!!!
        $comp_uri = $base_uri . 'src/compose.php?mailbox='. urlencode($mailbox)
                  . '&session='.urlencode($aMailbox['FORWARD_SESSION']['SESSION_NUMBER'])
                  . '&smaction=forward_as_attachment'
                  . '&fwduid=' . implode('_', $aMailbox['FORWARD_SESSION']['UIDS']);
        $onload = "comp_in_new('$comp_uri', $compose_width, $compose_height);";
    } else {
        $mailbox_cache[$account.'_'.$aMailbox['NAME']] = $aMailbox;
        sqsession_register($mailbox_cache,'mailbox_cache');

        // save mailboxstate
        sqsession_register($aMailbox,'aLastSelectedMailbox');
        session_write_close();
        // we have to redirect to the compose page
        $location = $base_uri . 'src/compose.php?mailbox='. urlencode($mailbox)
                  . '&session='.$aMailbox['FORWARD_SESSION']['SESSION_NUMBER']
                  . '&smaction=forward_as_attachment'
                  . '&fwduid=' . implode('_', $aMailbox['FORWARD_SESSION']['UIDS']);
        header("Location: $location");
        exit;
    }
}

// plugins can operate normally here (don't output anything, of course!),
// but can also return TRUE if they want to enable the MOTD display
// even when SM's MOTD is empty (there is plugin output that can
// be then hooked into in motd.tpl)  
// NOTE a TRUE return value here will cause the display of the MOTD on 
// *every* page view; if a plugin wants to support true MOTD (one-time 
// message display upon login), it should also check the value of 
// "just_logged_in" in the PHP session before returning TRUE.
//
$show_motd = boolean_hook_function('right_main_after_header', $null, 1);


// get "just_logged_in" from PHP session, save it in a temp variable
// for use below, and reset its value in PHP session
//
if (!sqgetGlobalVar('just_logged_in', $just_logged_in, SQ_SESSION))
    $just_logged_in = false;
$temp_just_logged_in = $just_logged_in;
$just_logged_in = false;
sqsession_register($just_logged_in, 'just_logged_in');


// now we're done with the PHP session, can send output to browser
//
displayPageHeader($color, $mailbox, $onload);


/* display a message to the user that their mail has been sent */
if (isset($mail_sent) && $mail_sent == 'yes') {
    $note = _("Your mail has been sent.");
}
if (isset($note)) {
    $oTemplate->assign('note', sm_encode_html_special_chars($note));
    $oTemplate->display('note.tpl');
}

if ($temp_just_logged_in || $show_motd) {
    $motd = trim($motd);
    if ($show_motd || strlen($motd) > 0) {
        $oTemplate->assign('motd', $motd);
        $oTemplate->display('motd.tpl');
    }
}


if ($aMailbox['EXISTS'] > 0) {
    $aTemplateVars = showMessagesForMailbox($imapConnection,$aMailbox,$aProps,$iError);
    if ($iError) {

    }
    foreach ($aTemplateVars as $k => $v) {
        $oTemplate->assign($k, $v);
    }

    /*
     * TODO: To many config related vars. We should move all config related vars to
     * one single associative array and assign that to the template
     */
    $oTemplate->assign('page_selector',  $page_selector);
    $oTemplate->assign('page_selector_max', $page_selector_max);
    $oTemplate->assign('compact_paginator', $compact_paginator);
    $oTemplate->assign('javascript_on', checkForJavascript());
    $oTemplate->assign('base_uri', sqm_baseuri());
    $oTemplate->assign('enablesort', (isset($aProps['config']['enablesort'])) ? $aProps['config']['enablesort'] : false);
    $oTemplate->assign('icon_theme_path', $icon_theme_path);
    $oTemplate->assign('aOrder', array_keys($aColumns));
    $oTemplate->assign('alt_index_colors', isset($alt_index_colors) ? $alt_index_colors: false);
    $oTemplate->assign('color', $color);
    $oTemplate->assign('align', $align);
    $oTemplate->assign('checkall', $checkall);
    $oTemplate->assign('preselected', $preselected);

    global $show_personal_names;
    $oTemplate->assign('show_personal_names', $show_personal_names);

    global $accesskey_mailbox_toggle_selected, $accesskey_mailbox_thread;
    $oTemplate->assign('accesskey_mailbox_toggle_selected', $accesskey_mailbox_toggle_selected);
    $oTemplate->assign('accesskey_mailbox_thread', $accesskey_mailbox_thread);

    $oTemplate->display('message_list.tpl');

} else {
    $oTemplate->display('empty_folder.tpl');
}

do_hook('right_main_bottom', $null);
sqimap_logout ($imapConnection);
$oTemplate->display('footer.tpl');


/* add the mailbox to the cache */
$mailbox_cache[$account.'_'.$aMailbox['NAME']] = $aMailbox;
sqsession_register($mailbox_cache,'mailbox_cache');
