<?php

/**
 * load_prefs.php
 *
 * Loads preferences from the $username.pref file used by almost
 * every other script in the source directory and alswhere.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** SquirrelMail required files. */
include_once(SM_PATH . 'functions/constants.php');
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/plugin.php');


if( ! sqgetGlobalVar('username', $username, SQ_SESSION) ) {
    $username = '';
}

$custom_css = getPref($data_dir, $username, 'custom_css', 'none' );

$theme = ( !isset($theme) ? array() : $theme );
$color = ( !isset($color) ? array() : $color );
$aTemplateSet = ( !isset($aTemplateSet) ? array() : $aTemplateSet );
$templateset_default = ( !isset($templateset_default) ? 0 : $templateset_default );

$chosen_theme = getPref($data_dir, $username, 'chosen_theme');
$sTplDir = getPref($data_dir, $username, 'sTplDir', SM_PATH . 'templates/default/');
$found_templateset = false;

/* need to adjust $chosen_template path with SM_PATH */
$sTplDir = preg_replace("/(\.\.\/){1,}/", SM_PATH, $sTplDir);

for ($i = 0; $i < count($aTemplateSet); ++$i){
    if ($aTemplateSet[$i]['PATH'] == $sTplDir) {
        $found_templateset = true;
        break;
    }
}
$sTplDir = ($found_templateset ? $sTplDir : '');
if (!$found_templateset) {
    if (isset($aTemplateSet) && isset($aTemplateSet[$templateset_default]) && file_exists($aTemplateSet[$templateset_default]['PATH'])) {
       $sTplDir = $aTemplateSet[$templateset_default]['PATH'];
    } else {
       $sTplDir = SM_PATH.'templates/default/';
    }
} else if (!file_exists($sTplDir)) {
    $sTplDir = SM_PATH.'templates/default/';
}

$found_theme = false;

/* need to adjust $chosen_theme path with SM_PATH */
$chosen_theme = preg_replace("/(\.\.\/){1,}/", SM_PATH, $chosen_theme);

for ($i = 0; $i < count($theme); ++$i){
    if ($theme[$i]['PATH'] == $chosen_theme) {
        $found_theme = true;
        break;
    }
}
$chosen_theme = (!$found_theme ? '' : $chosen_theme);


/**
* This theme as a failsafe if no themes were found. It makes
* no sense to cause the whole thing to exit just because themes
* were not found. This is the absolute last resort.
* Moved here to provide 'sane' defaults for incomplete themes.
*/
$color[0]  = '#DCDCDC';  /* light gray    TitleBar               */
$color[1]  = '#800000';  /* red                                  */
$color[2]  = '#CC0000';  /* light red     Warning/Error Messages */
$color[3]  = '#A0B8C8';  /* green-blue    Left Bar Background    */
$color[4]  = '#FFFFFF';  /* white         Normal Background      */
$color[5]  = '#FFFFCC';  /* light yellow  Table Headers          */
$color[6]  = '#000000';  /* black         Text on left bar       */
$color[7]  = '#0000CC';  /* blue          Links                  */
$color[8]  = '#000000';  /* black         Normal text            */
$color[9]  = '#ABABAB';  /* mid-gray      Darker version of #0   */
$color[10] = '#666666';  /* dark gray     Darker version of #9   */
$color[11] = '#770000';  /* dark red      Special Folders color  */
$color[12] = '#EDEDED';
$color[15] = '#002266';  /* (dark blue)      Unselectable folders */

if (isset($chosen_theme) && $found_theme && (file_exists($chosen_theme))) {
    @include_once($chosen_theme);
} else {
    if (isset($theme) && isset($theme[$theme_default]) && file_exists($theme[$theme_default]['PATH'])) {
        @include_once($theme[$theme_default]['PATH']);
        $chosen_theme = $theme[$theme_default]['PATH'];
    }
}


if (!defined('download_php')) {
    sqsession_register($theme_css, 'theme_css');
}

// user's icon theme, if using icons
$icon_theme = getPref($data_dir, $username, 'icon_theme', 'images/themes/xp/' );
if ($icon_theme == 'template') {
    $icon_theme = $sTplDir . 'images/';
}

/*
 * NOTE: The $icon_theme_path var should contain the path to the icon 
 *       theme to use.  If the admin has disabled icons, or the user has
 *       set the icon theme to "None," no icons will be used.
 */
$icon_theme_path = (!$use_icons || $icon_theme=='none') ? NULL : $icon_theme;

// show (or not) flag and unflag buttons on mailbox list screen
$show_flag_buttons = getPref($data_dir, $username, 'show_flag_buttons', SMPREF_ON );

/* Load the user's special folder preferences */
$move_to_sent =
    getPref($data_dir, $username, 'move_to_sent', $default_move_to_sent);
$move_to_trash =
    getPref($data_dir, $username, 'move_to_trash', $default_move_to_trash);
$save_as_draft =
    getPref($data_dir, $username, 'save_as_draft', $default_save_as_draft);

if ($default_unseen_type == '') {
    $default_unseen_type = 1;
}
if ($default_unseen_notify == '') {
    $default_unseen_notify = 2;
}
$unseen_type =
    getPref($data_dir, $username, 'unseen_type', $default_unseen_type);
$unseen_notify =
    getPref($data_dir, $username, 'unseen_notify', $default_unseen_notify);

$unseen_cum =
    getPref($data_dir, $username, 'unseen_cum', false);

$folder_prefix =
    getPref($data_dir, $username, 'folder_prefix', $default_folder_prefix);

/* Load special folder - trash */
$load_trash_folder = getPref($data_dir, $username, 'trash_folder');
if (($load_trash_folder == '') && ($move_to_trash)) {
    $trash_folder = $folder_prefix . $trash_folder;
} else {
    $trash_folder = $load_trash_folder;
}

/* Load special folder - sent */
$load_sent_folder = getPref($data_dir, $username, 'sent_folder');
if (($load_sent_folder == '') && ($move_to_sent)) {
    $sent_folder = $folder_prefix . $sent_folder;
} else {
    $sent_folder = $load_sent_folder;
}

/* Load special folder - draft */
$load_draft_folder = getPref($data_dir, $username, 'draft_folder');
if (($load_draft_folder == '') && ($save_as_draft)) {
    $draft_folder = $folder_prefix . $draft_folder;
} else {
    $draft_folder = $load_draft_folder;
}

$show_num = getPref($data_dir, $username, 'show_num', 15 );

$wrap_at = getPref( $data_dir, $username, 'wrap_at', 86 );
if ($wrap_at < 15) { $wrap_at = 15; }

$left_size = getPref($data_dir, $username, 'left_size');
if ($left_size == '') {
    if (isset($default_left_size)) {
        $left_size = $default_left_size;
    } else {
        $left_size = 200;
    }
}

$editor_size = getPref($data_dir, $username, 'editor_size', 76 );
$editor_height = getPref($data_dir, $username, 'editor_height', 20 );
$use_signature = getPref($data_dir, $username, 'use_signature', SMPREF_OFF );
$prefix_sig = getPref($data_dir, $username, 'prefix_sig');

/* Load timezone preferences */
$timezone = getPref($data_dir, $username, 'timezone', SMPREF_NONE );

/* Load preferences for reply citation style. */

$reply_citation_style =
    getPref($data_dir, $username, 'reply_citation_style', 'date_time_author' );
$reply_citation_start = getPref($data_dir, $username, 'reply_citation_start');
$reply_citation_end = getPref($data_dir, $username, 'reply_citation_end');

$body_quote = getPref($data_dir, $username, 'body_quote', '>');
if ($body_quote == 'NONE') $body_quote = '';

// who is using those darn block comments?  poo!

// Load preference for cursor behavior for replies
//
$reply_focus = getPref($data_dir, $username, 'reply_focus', '');

/* left refresh rate, strtolower makes 1.0.6 prefs compatible */
$left_refresh = getPref($data_dir, $username, 'left_refresh', 600 );
$left_refresh = strtolower($left_refresh);

/* Load up the Signature file */
$signature_abs = $signature = getSig($data_dir, $username, 'g');

/* Message Highlighting Rules */
$message_highlight_list = array();

/* use new way of storing highlighting rules */
if( $ser = getPref($data_dir, $username, 'hililist') ) {
    $message_highlight_list = unserialize($ser);
} else {
    /* use old way */
    for ($i = 0; $hlt = getPref($data_dir, $username, "highlight$i"); ++$i) {
        $highlight_array = explode(',', $hlt);
        $message_highlight_list[$i]['name'] = $highlight_array[0];
        $message_highlight_list[$i]['color'] = $highlight_array[1];
        $message_highlight_list[$i]['value'] = $highlight_array[2];
        $message_highlight_list[$i]['match_type'] = $highlight_array[3];
        removePref($data_dir, $username, "highlight$i");
    }
    /* store in new format for the next time */
    setPref($data_dir, $username, 'hililist', serialize($message_highlight_list));
}

/* use the internal date of the message for sorting instead of the supplied header date */
/* OBSOLETE */

$internal_date_sort = getPref($data_dir, $username, 'internal_date_sort', SMPREF_ON);

/* Index order lets you change the order of the message index */
$order = getPref($data_dir, $username, 'order1');
if (isset($order1)) {
    removePref($data_dir, $username, 'order1');
    for ($i = 1; $order; ++$i) {
        $index_order[$i-1] = $order -1;
        $order = getPref($data_dir, $username, 'order'.($i+1));
        removePref($data_dir, $username, 'order'.($i+1));
    }
    if (isset($internal_date_sort) && $internal_date_sort) {
        if (in_array(SQM_COL_DATE,$index_order)) {
            $k = array_search(SQM_COL_DATE,$index_order,true);
            $index_order[$k] = SQM_COL_INT_DATE;
        }
    }
    setPref($data_dir, $username, 'index_order', serialize($index_order));
}
$index_order = getPref($data_dir, $username, 'index_order');
if (is_string($index_order)) {
    $index_order = unserialize($index_order);
}


// new Index order handling
//$default_mailbox_pref = unserialize(getPref($data_dir, $username, 'default_mailbox_pref'));

if (!$index_order) {
    if (isset($internal_date_sort) && $internal_date_sort == false) {
        $index_order = array(SQM_COL_CHECK,SQM_COL_FROM,SQM_COL_DATE,SQM_COL_FLAGS,SQM_COL_ATTACHMENT,SQM_COL_PRIO,SQM_COL_SUBJ);
    } else {
        $index_order = array(SQM_COL_CHECK,SQM_COL_FROM,SQM_COL_INT_DATE,SQM_COL_FLAGS,SQM_COL_ATTACHMENT,SQM_COL_PRIO,SQM_COL_SUBJ);
    }
    setPref($data_dir, $username, 'index_order', serialize($index_order));
}



if (!isset($default_mailbox_pref)) {
    $show_num = (isset($show_num)) ? $show_num : 15;

    $default_mailbox_pref = array (
        MBX_PREF_SORT => 0,
        MBX_PREF_LIMIT => $show_num,
        MBX_PREF_AUTO_EXPUNGE => $auto_expunge,
        MBX_PREF_COLUMNS => $index_order);
    // setPref($data_dir, $username, 'default_mailbox_pref', serialize($default_mailbox_pref));
    // clean up the old prefs
//    if (isset($prefs_cache['internal_date_sort'])) {
//        unset($prefs_cache['internal_date_sort']);
//        removePref($data_dir,$username,'internal_date_sort');
//    }
//    if (isset($prefs_cache['show_num'])) {
//        unset($prefs_cache['show_num']);
//        removePref($data_dir,$username,'show_num');
//    }
}


$alt_index_colors =
    getPref($data_dir, $username, 'alt_index_colors', SMPREF_ON );

$fancy_index_highlite =
    getPref($data_dir, $username, 'fancy_index_highlite', SMPREF_ON );

/* Folder List Display Format */
$location_of_bar =
    getPref($data_dir, $username, 'location_of_bar', SMPREF_LOC_LEFT);
$location_of_buttons =
    getPref($data_dir, $username, 'location_of_buttons', SMPREF_LOC_BETWEEN);

$collapse_folders =
    getPref($data_dir, $username, 'collapse_folders', SMPREF_ON);

$show_html_default =
   getPref($data_dir, $username, 'show_html_default', SMPREF_ON);

$enable_forward_as_attachment =
   getPref($data_dir, $username, 'enable_forward_as_attachment', SMPREF_ON);

$show_xmailer_default =
    getPref($data_dir, $username, 'show_xmailer_default', SMPREF_OFF );
$attachment_common_show_images = getPref($data_dir, $username, 'attachment_common_show_images', SMPREF_OFF );


/* message disposition notification support setting */
$mdn_user_support = getPref($data_dir, $username, 'mdn_user_support', SMPREF_ON);

$include_self_reply_all =
    getPref($data_dir, $username, 'include_self_reply_all', SMPREF_ON);

/* Page selector options */
$page_selector = getPref($data_dir, $username, 'page_selector', SMPREF_ON);
$compact_paginator = getPref($data_dir, $username, 'compact_paginator', SMPREF_OFF);
$page_selector_max = getPref($data_dir, $username, 'page_selector_max', 10);

/* SqClock now in the core */
$date_format = getPref($data_dir, $username, 'date_format', 3);
$hour_format = getPref($data_dir, $username, 'hour_format', SMPREF_TIME_12HR);

/*  compose in new window setting */
$compose_new_win = getPref($data_dir, $username, 'compose_new_win', SMPREF_OFF);
$compose_height = getPref($data_dir, $username, 'compose_height', 550);
$compose_width = getPref($data_dir, $username, 'compose_width', 640);


/* signature placement settings */
$sig_first = getPref($data_dir, $username, 'sig_first', SMPREF_OFF);

/* Strip signature when replying */
$strip_sigs = getPref($data_dir, $username, 'strip_sigs', SMPREF_ON);

/* use the internal date of the message for sorting instead of the supplied header date */
$internal_date_sort = getPref($data_dir, $username, 'internal_date_sort', SMPREF_ON);

/* if server sorting is enabled/disabled */
$sort_by_ref = getPref($data_dir, $username, 'sort_by_ref', SMPREF_ON);

/* Load the javascript settings. */
$javascript_setting = getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);
if ( checkForJavascript() )
{
  $use_javascript_folder_list = getPref($data_dir, $username, 'use_javascript_folder_list');
  $use_javascript_addr_book = getPref($data_dir, $username, 'use_javascript_addr_book', $default_use_javascript_addr_book);
} else {
  $use_javascript_folder_list = false;
  $use_javascript_addr_book = false;
}

$search_memory = getPref($data_dir, $username, 'search_memory', SMPREF_OFF);

$show_only_subscribed_folders =
    getPref($data_dir, $username, 'show_only_subscribed_folders', SMPREF_ON);


$forward_cc = getPref($data_dir, $username, 'forward_cc', SMPREF_OFF);

/* How are mailbox select lists displayed: 0. full names, 1. indented (default),
 * 3. delimited) */
$mailbox_select_style = getPref($data_dir, $username, 'mailbox_select_style', SMPREF_ON);

/* Allow user to customize, and display the full date, instead of day, or time based
   on time distance from date of message */
$show_full_date = getPref($data_dir, $username, 'show_full_date', SMPREF_OFF);

/* Allow user to customize length of from field */
$truncate_sender = getPref($data_dir, $username, 'truncate_sender', 50);
/* Allow user to customize length of subject field */
$truncate_subject = getPref($data_dir, $username, 'truncate_subject', 50);
/* Allow user to show recipient name if the message is from default identity */
$show_recipient_instead = getPref($data_dir, $username, 'show_recipient_instead', SMPREF_OFF);

$delete_prev_next_display = getPref($data_dir, $username, 'delete_prev_next_display', SMPREF_ON);

/**
 * Height of iframe that displays html formated emails
 * @since 1.5.1
 */
$iframe_height = getPref($data_dir, $username, 'iframe_height', '300');

if (! isset($default_fontset)) $default_fontset=SMPREF_NONE;
$chosen_fontset = getPref($data_dir, $username, 'chosen_fontset', $default_fontset);
if (! isset($default_fontsize)) $default_fontsize=SMPREF_NONE;
$chosen_fontsize = getPref($data_dir, $username, 'chosen_fontsize', $default_fontsize);

do_hook('loading_prefs');

?>
