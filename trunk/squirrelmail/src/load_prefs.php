<?php

/**
 * load_prefs.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Loads preferences from the $username.pref file used by almost
 * every other script in the source directory and alswhere.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/prefs.php');
require_once('../functions/plugin.php');
require_once('../functions/constants.php');

$username = ( !isset($username) ? '' : $username );

$custom_css = getPref($data_dir, $username, 'custom_css', 'none' );

$theme = ( !isset($theme) ? array() : $theme );
$color = ( !isset($color) ? array() : $color );

$chosen_theme = getPref($data_dir, $username, 'chosen_theme');
$found_theme = false;
for ($i = 0; $i < count($theme); ++$i){
    if ($theme[$i]['PATH'] == $chosen_theme) {
        $found_theme = true;
        break;
    }
}
$chosen_theme = (!$found_theme ? '' : $chosen_theme);

if (isset($chosen_theme) && $found_theme && (file_exists($chosen_theme))) {
    @include_once($chosen_theme);
} else {
    if (isset($theme) && isset($theme[0]) && file_exists($theme[0]['PATH'])) {
        @include_once($theme[0]['PATH']);
    } else {
        /**
         * This theme as a failsafe if no themes were found. It makes
         * no sense to cause the whole thing to exit just because themes
         * were not found. This is the absolute last resort.
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
    }
}

if (!defined('download_php')) { 
    session_register('theme_css'); 
}

$use_javascript_addr_book = getPref($data_dir, $username, 'use_javascript_addr_book', $default_use_javascript_addr_book);

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
$use_signature = getPref($data_dir, $username, 'use_signature', SMPREF_OFF );
$prefix_sig = getPref($data_dir, $username, 'prefix_sig');

/* Load timezone preferences */
$timezone = getPref($data_dir, $username, 'timezone', SMPREF_NONE );

/* Load preferences for reply citation style. */

$reply_citation_style =
    getPref($data_dir, $username, 'reply_citation_style', SMPREF_NONE );
$reply_citation_start = getPref($data_dir, $username, 'reply_citation_start');
$reply_citation_end = getPref($data_dir, $username, 'reply_citation_end');

/* left refresh rate, strtolower makes 1.0.6 prefs compatible */
$left_refresh = getPref($data_dir, $username, 'left_refresh', SMPREF_NONE );
$left_refresh = strtolower($left_refresh);

$sort = getPref($data_dir, $username, 'sort', 6 );

/** Load up the Signature file **/
$signature_abs = $signature = getSig($data_dir, $username, "g");

/* Highlight comes in with the form: name, color, header, value. */
for ($i = 0; $hlt = getPref($data_dir, $username, "highlight$i"); ++$i) {
    $highlight_array = explode(',', $hlt);
    $message_highlight_list[$i]['name'] = $highlight_array[0];
    $message_highlight_list[$i]['color'] = $highlight_array[1];
    $message_highlight_list[$i]['value'] = $highlight_array[2];
    $message_highlight_list[$i]['match_type'] = $highlight_array[3];
}

/* Index order lets you change the order of the message index */
$order = getPref($data_dir, $username, 'order1');
for ($i = 1; $order; ++$i) {
    $index_order[$i] = $order;
    $order = getPref($data_dir, $username, 'order'.($i+1));
}
if (!isset($index_order)) {
    $index_order[1] = 1;
    $index_order[2] = 2;
    $index_order[3] = 3;
    $index_order[4] = 5;
    $index_order[5] = 4;
}

$alt_index_colors =
    getPref($data_dir, $username, 'alt_index_colors', SMPREF_ON );

$location_of_bar =
    getPref($data_dir, $username, 'location_of_bar', SMPREF_LOC_LEFT);
$location_of_buttons =
    getPref($data_dir, $username, 'location_of_buttons', SMPREF_LOC_BETWEEN);

$collapse_folders =
    getPref($data_dir, $username, 'collapse_folders', SMPREF_ON);

/* show_html_default is a int value. */
$show_html_default =
    intval(getPref($data_dir, $username, 'show_html_default', SMPREF_OFF));

$show_xmailer_default =
    getPref($data_dir, $username, 'show_xmailer_default', SMPREF_OFF );
$attachment_common_show_images = getPref($data_dir, $username, 'attachment_common_show_images', SMPREF_OFF );
$pf_subtle_link = getPref($data_dir, $username, 'pf_subtle_link', SMPREF_ON);
$pf_cleandisplay = getPref($data_dir, $username, 'pf_cleandisplay', SMPREF_OFF);

/* message disposition notification support setting */
$mdn_user_support = getPref($data_dir, $username, 'mdn_user_support', SMPREF_ON);

$include_self_reply_all =
    getPref($data_dir, $username, 'include_self_reply_all', SMPREF_ON);

$page_selector = getPref($data_dir, $username, 'page_selector', SMPREF_ON);
$page_selector_max = getPref($data_dir, $username, 'page_selector_max', 10);

/* SqClock now in the core */
$date_format = getPref($data_dir, $username, 'date_format', 3);
$hour_format = getPref($data_dir, $username, 'hour_format', 2);

/*  compose in new window setting */
$compose_new_win = getPref($data_dir, $username, 'compose_new_win', 0);

/* signature placement settings */
$sig_first = getPref($data_dir, $username, 'sig_first', 0);

/* use the internal date of the message for sorting instead of the supplied header date */
$internal_date_sort = getPref($data_dir, $username, 'internal_date_sort', SMPREF_ON);

/* Load the javascript settings. */
$javascript_setting =
    getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);
$javascript_on = getPref($data_dir, $username, 'javascript_on', SMPREF_ON);


$search_memory = getPref($data_dir, $username, 'search_memory', 0);

do_hook('loading_prefs');

?>
