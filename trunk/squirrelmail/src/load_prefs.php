<?php
    /**
     * load_prefs.php
     *
     * Copyright (c) 1999-2001 The SquirrelMail Development Team
     * Licensed under the GNU GPL. For full terms see the file COPYING.
     *
     * Loads preferences from the $username.pref file used by almost
     * every other script in the source directory and alswhere.
     *
     * $Id$
     **/

    require_once('../src/validate.php');

    /**************************************************************/
    /* Following code should be removed in the next foo_once step */
    if (defined('load_prefs_php')) { return; }
    define('load_prefs_php', true);
    /**************************************************************/

    global $theme, $chosen_theme, $color;
    if (! isset($theme)) { $theme = array(); }
    if (! isset($color)) { $color = array(); }
    require_once('../functions/prefs.php');
    require_once('../functions/plugin.php');
    require_once('../functions/constants.php');
      
    if (!isset($username)) { $username = ''; }
    checkForPrefs($data_dir, $username);

    $chosen_theme = getPref($data_dir, $username, "chosen_theme");
    $in_ary = false;
    for ($i=0; $i < count($theme); $i++){
        if ($theme[$i]["PATH"] == $chosen_theme) {
            $in_ary = true;
            break;
        }
    }
    if (! $in_ary) { $chosen_theme = ""; }

    if (isset($chosen_theme) && $in_ary && (file_exists($chosen_theme))) {
        @include_once($chosen_theme);
    } else {
        if (isset($theme) && isset($theme[0])
              && file_exists($theme[0]["PATH"])) {
            @include_once($theme[0]["PATH"]);
        } else {
            /**
             * This theme as a failsafe if no themes were found. It makes
             * no sense to cause the whole thing to exit just because themes
             * were not found. This is the absolute last resort.
             */
             $color[0]   = "#DCDCDC"; // light gray    TitleBar
             $color[1]   = "#800000"; // red
             $color[2]   = "#CC0000"; // light red     Warning/Error Messages
             $color[3]   = "#A0B8C8"; // green-blue    Left Bar Background
             $color[4]   = "#FFFFFF"; // white         Normal Background
             $color[5]   = "#FFFFCC"; // light yellow  Table Headers
             $color[6]   = "#000000"; // black         Text on left bar
             $color[7]   = "#0000CC"; // blue          Links
             $color[8]   = "#000000"; // black         Normal text
             $color[9]   = "#ABABAB"; // mid-gray      Darker version of #0
             $color[10]  = "#666666"; // dark gray     Darker version of #9
             $color[11]  = "#770000"; // dark red      Special Folders color
        }
    }

    if (!defined('download_php')) { session_register("theme_css"); }

    global $use_javascript_addr_book;
    $use_javascript_addr_book = getPref($data_dir, $username, 'use_javascript_addr_book', $default_use_javascript_addr_book);

    /** Declare the global variables for the special folders. */
    global $move_to_sent, $move_to_trash, $save_as_draft;

    /** Load the user's special folder preferences **/
    $move_to_sent = getPref($data_dir, $username, 'move_to_sent', $default_move_to_sent);
    $move_to_trash = getPref($data_dir, $username, 'move_to_trash', $default_move_to_trash);
    $save_as_draft = getPref($data_dir, $username, 'save_as_draft', $default_save_as_draft);

    global $unseen_type, $unseen_notify;
    if ($default_unseen_type == '') { $default_unseen_type = 1; }
    $unseen_type = getPref($data_dir, $username, 'unseen_type', $default_unseen_type);
    if ($default_unseen_notify == '') { $default_unseen_notify = 2; }
    $unseen_notify = getPref($data_dir, $username, 'unseen_notify', $default_unseen_notify);

    global $folder_prefix;
    $folder_prefix = getPref($data_dir, $username, 'folder_prefix', $default_folder_prefix);

    /* Declare global variables for special folders. */
    global $trash_folder, $sent_folder, $draft_folder;

    /** Load special folder - trash **/
    $load_trash_folder = getPref($data_dir, $username, 'trash_folder');
    if (($load_trash_folder == '') && ($move_to_trash)) {
        $trash_folder = $folder_prefix . $trash_folder;
    } else {
        $trash_folder = $load_trash_folder;
    }

    /** Load special folder - sent **/
    $load_sent_folder = getPref($data_dir, $username, 'sent_folder');
    if (($load_sent_folder == '') && ($move_to_sent)) {
        $sent_folder = $folder_prefix . $sent_folder;
    } else {
        $sent_folder = $load_sent_folder;
    }

    /** Load special folder - draft **/
    $load_draft_folder = getPref($data_dir, $username, 'draft_folder');
    if (($load_draft_folder == '') && ($save_as_draft)) {
        $draft_folder = $folder_prefix . $draft_folder;
    } else {
        $draft_folder = $load_draft_folder;
    }

    global $show_num, $wrap_at, $left_size;
    $show_num = getPref($data_dir, $username, 'show_num', 15 );

    $wrap_at = getPref( $data_dir, $username, 'wrap_at', 86 );
    if ($wrap_at < 15) { $wrap_at = 15; }

    $left_size = getPref($data_dir, $username, 'left_size');
    if ($left_size == "") {
        if (isset($default_left_size)) {
            $left_size = $default_left_size;
        } else {
            $left_size = 200;
        }
    }

    global $editor_size, $use_signature, $prefix_sig;
    $editor_size = getPref($data_dir, $username, "editor_size", 76 );

    $use_signature = getPref($data_dir, $username, 'use_signature', SMPREF_OFF );

    $prefix_sig = getPref($data_dir, $username, "prefix_sig");

    /* Load preferences for reply citation style. */
    global $reply_citation_style, $reply_citation_start, $reply_citation_end;

    $reply_citation_style = getPref($data_dir, $username, 'reply_citation_style', SMPREF_NONE );
    $reply_citation_start = getPref($data_dir, $username, 'reply_citation_start');
    $reply_citation_end = getPref($data_dir, $username, 'reply_citation_end');

    global $left_refresh, $sort;
    $left_refresh = getPref($data_dir, $username, 'left_refresh', SMPREF_NONE );
    $sort = getPref($data_dir, $username, 'sort', 6 );

    /** Load up the Signature file **/
    global $signature_abs;
    if ($use_signature) {
        $signature_abs = $signature = getSig($data_dir, $username);
    } else {
        $signature_abs = getSig($data_dir, $username);
    }

    /* HighlightX comes in with the form: name, color, header, value. */
    global $message_highlight_list;
    for ($i=0; $hlt = getPref($data_dir, $username, "highlight$i"); $i++) {
        $ary = explode(",", $hlt);
        $message_highlight_list[$i]['name'] = $ary[0];
        $message_highlight_list[$i]['color'] = $ary[1];
        $message_highlight_list[$i]['value'] = $ary[2];
        $message_highlight_list[$i]['match_type'] = $ary[3];
    }

    /* Index order lets you change the order of the message index */
    global $index_order;
    $order = getPref($data_dir, $username, 'order1');
    for ($i=1; $order; $i++) {
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

    global $alt_index_colors;
    $alt_index_colors = getPref($data_dir, $username, 'alt_index_colors', SMPREF_ON );

    global $location_of_bar, $location_of_buttons;
    $location_of_bar = getPref($data_dir, $username, 'location_of_bar', SMPREF_LOC_LEFT);
    $location_of_buttons = getPref($data_dir, $username, 'location_of_buttons', SMPREF_LOC_BETWEEN);

    global $collapse_folders, $show_html_default, $show_xmailer_default,
           $attachment_common_show_images, $pf_subtle_link, $pf_cleandisplay;
    $collapse_folders = getPref($data_dir, $username, 'collapse_folders', SMPREF_ON);

    /* show_html_default is a int value. */
    $show_html_default = intval(getPref($data_dir, $username, 'show_html_default', SMPREF_ON));

    $show_xmailer_default = getPref($data_dir, $username, 'show_xmailer_default', SMPREF_OFF );
    $attachment_common_show_images = getPref($data_dir, $username, 'attachment_common_show_images', SMPREF_OFF );
    $pf_subtle_link = getPref($data_dir, $username, 'pf_subtle_link', SMPREF_ON);
    $pf_cleandisplay = getPref($data_dir, $username, 'pf_cleandisplay', SMPREF_OFF);

    global $include_self_reply_all;
    $include_self_reply_all = getPref($data_dir, $username, 'include_self_reply_all', SMPREF_ON);

    global $page_selector, $page_selector_max;
    $page_selector = getPref($data_dir, $username, 'page_selector', SMPREF_ON);
    $page_selector_max = getPref($data_dir, $username, 'page_selector_max', 10);

    /* SqClock now in the core */
    global $date_format, $hour_format, $username, $data_dir;
    $date_format = getPref($data_dir, $username, 'date_format', 3);
    $hour_format = getPref($data_dir, $username, 'hour_format', 2);

    /* Load the javascript settings. */
    global $javascript_setting, $javascript_on;
    $javascript_setting = getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);
    $javascript_on = getPref($data_dir, $username, 'javascript_on', SMPREF_ON);

    do_hook("loading_prefs");
?>
