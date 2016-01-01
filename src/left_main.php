<?php

/**
 * left_main.php
 *
 * This is the code for the left bar. The left bar shows the folders
 * available, and has cookie information.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the left_main page */
define('PAGE_NAME', 'left_main');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/template/folder_list_util.php');

/* These constants are used for folder stuff. */
define('SM_BOX_UNCOLLAPSED', 0);
define('SM_BOX_COLLAPSED',   1);

/* get globals */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);

sqgetGlobalVar('fold', $fold, SQ_GET);
sqgetGlobalVar('unfold', $unfold, SQ_GET);
/* end globals */

// open a connection on the imap port (143)
// why hide the output?
global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, true, $imap_stream_options);

/**
 * Using stristr since very old preferences may contain "None" and "none".
 */
if (!empty($left_refresh) &&
    !stristr($left_refresh, 'none')){
    $xtra =  "\n<meta http-equiv=\"REFRESH\" content=\"$left_refresh;URL=left_main.php\" />\n";
} else {
    $xtra = '';
}

/**
 * Include extra javascript files needed by template
 */
$js_includes = $oTemplate->get_javascript_includes(TRUE);
foreach ($js_includes as $js_file) {
    $xtra .= '<script src="'.$js_file.'" type="text/javascript"></script>' ."\n";
}

// get mailbox list and cache it
$mailboxes=sqimap_get_mailboxes($imapConnection,false,$show_only_subscribed_folders);

displayHtmlHeader( $org_title, $xtra );
$oErrorHandler->setDelayedErrors(true);

sqgetGlobalVar('auto_create_done',$auto_create_done,SQ_SESSION);
/* If requested and not yet complete, attempt to autocreate folders. */
if ($auto_create_special && !isset($auto_create_done)) {
    $autocreate = array($sent_folder, $trash_folder, $draft_folder);
    $folders_created = false;
    foreach( $autocreate as $folder ) {
        if ($folder != '' && $folder != SMPREF_NONE) {
            /**
             * If $show_only_subscribed_folders is true, don't use 
             * $mailboxes array for checking if mailbox exists.
             * Mailbox list contains only subscribed folders. 
             * sqimap_mailbox_create() will fail, if folder exists.
             */
            if ($show_only_subscribed_folders) {
                $mailbox_cache = false;
            } else {
                $mailbox_cache = $mailboxes;
            }
            if ( !sqimap_mailbox_exists($imapConnection, $folder, $mailbox_cache)) {
                sqimap_mailbox_create($imapConnection, $folder, '');
                $folders_created = true;
            } else {
                // check for subscription is useless and expensive, just
                // surpress the NO response. Unless we're on Mecury, which
                // will just subscribe a folder again if it's already
                // subscribed.
                if ( strtolower($imap_server_type) != 'mercury32' ||
                    !sqimap_mailbox_is_subscribed($imapConnection, $folder) ) {
                    sqimap_subscribe($imapConnection, $folder, false);
                    $folders_created = true;
                }
            }
        }
    }

    /* Let the world know that autocreation is complete! Hurrah! */
    $auto_create_done = TRUE;
    sqsession_register($auto_create_done, 'auto_create_done');
    // reload mailbox list
    if ($folders_created)
        $mailboxes=sqimap_get_mailboxes($imapConnection,true,$show_only_subscribed_folders);
}

$clock = '';
if ($date_format != 6) {
    /* First, display the clock. */
    if ($hour_format == 1) {
        $hr = 'H:i';
        if ($date_format == 4) {
            $hr .= ':s';
        }
    } else {
        if ($date_format == 4) {
            $hr = 'g:i:s a';
        } else {
            $hr = 'g:i a';
        }
    }

    switch( $date_format ) {
    case 0:
        $clk = date('Y-m-d '.$hr. ' T', time());
        break;
    case 1:
        $clk = date('m/d/y '.$hr, time());
        break;
    case 2:
        $clk = date('d/m/y '.$hr, time());
        break;
    case 4:
    case 5:
        $clk = date($hr, time());
        break;
    default:
        $clk = getDayAbrv( date( 'w', time() ) ) . date( ', ' . $hr, time() );
    }
    $clk = str_replace(' ','&nbsp;',$clk);

//FIXME don't build HTML here - do it in template
    $clock = '<small><span style="white-space: nowrap;">'
       . str_replace(' ', '&nbsp;', _("Last Refresh"))
       . ":</span><br /><span style=\"white-space: nowrap;\">$clk</span></small><br />\n";
}

if ( $collapse_folders ) {
    /* If directed, collapse or uncollapse a folder. */
    if (isset($fold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $fold, SM_BOX_COLLAPSED);
    } else if (isset($unfold)) {
        setPref($data_dir, $username, 'collapse_folder_' . $unfold, SM_BOX_UNCOLLAPSED);
    }
}

/* Get unseen/total display prefs */
$unseen_type = getPref( $data_dir , $username , 'unseen_type' );
$unseen_notify = getPref( $data_dir , $username , 'unseen_notify' );

if (empty($unseen_type)) {
    if (!empty($default_unseen_type)) {
        $unseen_type = $default_unseen_type;
    } else {
        $unseen_type = 1;
    }
}

if (empty($unseen_notify)) {
    if (!empty($default_unseen_notify)) {
        $unseen_notify = $default_unseen_notify;
    } else {
        $unseen_notify = 0;
    }
}

/**
 * pass $mailboxes now instead of $imapconnection - sqimap_get_mailboxes() has been separated from
 * sqimap_mailbox_tree() so that the cached mailbox list can be used elsewhere in left_main and beyond
 */
$boxes = sqimap_mailbox_tree($imapConnection,$mailboxes,$show_only_subscribed_folders);
$mailbox_structure = getBoxStructure($boxes);

$oTemplate->assign('clock', $clock);
$oTemplate->assign('mailboxes', $mailbox_structure);

/*
 * Build an array to pass user prefs to the template in order to avoid using
 * globals, which are dirty, filthy things in templates. :)
 */
$settings = array();
#$settings['imapConnection'] = $imapConnection;
$settings['templateID'] = $sTemplateID;
$settings['unreadNotificationEnabled'] = $unseen_notify!=1;
$settings['unreadNotificationAllFolders'] = $unseen_notify == 3;
$settings['unreadNotificationDisplayTotal'] = $unseen_type == 2;
$settings['unreadNotificationCummulative'] = $unseen_cum==1;
$settings['useSpecialFolderColor'] = $use_special_folder_color;
$settings['messageRecyclingEnabled'] = $move_to_trash;
$settings['collapsableFoldersEnabled'] = $collapse_folders==1;
$oTemplate->assign('settings', $settings);

//access keys
//
$oTemplate->assign('accesskey_folders_refresh', $accesskey_folders_refresh);
$oTemplate->assign('accesskey_folders_purge_trash', $accesskey_folders_purge_trash);
$oTemplate->assign('accesskey_folders_inbox', $accesskey_folders_inbox);

$oTemplate->display('left_main.tpl');

sqimap_logout($imapConnection);
$oTemplate->display('footer.tpl');
