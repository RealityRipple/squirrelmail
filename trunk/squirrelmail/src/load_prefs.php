<?php
   /**
    **  load_prefs.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Loads preferences from the $username.pref file used by almost
    **  every other script in the source directory and alswhere.
    **
    **  $Id$
    **/

   if (defined('load_prefs_php'))
       return;
   define('load_prefs_php', true);

   global $theme, $chosen_theme, $color;
   if (! isset($theme))
      $theme = array();
   if (! isset($color))
      $color = array();
   include('../src/validate.php');
   include("../config/config.php");
   include("../functions/prefs.php");
   include("../functions/plugin.php");
      
   if (!isset($username))
       $username = '';
   checkForPrefs($data_dir, $username);

   $chosen_theme = getPref($data_dir, $username, "chosen_theme");
   $in_ary = false;
   for ($i=0; $i < count($theme); $i++){
   	  if ($theme[$i]["PATH"] == $chosen_theme) {
	  	 $in_ary = true;
		 break;
	  }
   }
   
   if (! $in_ary)
       $chosen_theme = "";

   if (isset($chosen_theme) && $in_ary && (file_exists($chosen_theme))) {
      @include($chosen_theme);
   } else {
      if (file_exists(isset($theme) && isset($theme[0]) && $theme[0]["PATH"])) {
         @include($theme[0]["PATH"]);
      } else {
          #
          #  I hard coded the theme as a failsafe if no themes were
          #  found.  It makes no sense to cause the whole thing to exit
          #  just because themes were not found.  This is the absolute
          #  last resort.
          #
          $color[0]   = "#DCDCDC"; // (light gray)     TitleBar
          $color[1]   = "#800000"; // (red)
          $color[2]   = "#CC0000"; // (light red)      Warning/Error Messages
          $color[3]   = "#A0B8C8"; // (green-blue)     Left Bar Background
          $color[4]   = "#FFFFFF"; // (white)          Normal Background
          $color[5]   = "#FFFFCC"; // (light yellow)   Table Headers
          $color[6]   = "#000000"; // (black)          Text on left bar
          $color[7]   = "#0000CC"; // (blue)           Links
          $color[8]   = "#000000"; // (black)          Normal text
          $color[9]   = "#ABABAB"; // (mid-gray)       Darker version of #0
          $color[10]  = "#666666"; // (dark gray)      Darker version of #9
          $color[11]  = "#770000"; // (dark red)       Special Folders color
      }
   }

    if (!defined('download_php')) 
       session_register("theme_css");

   global $use_javascript_addr_book;
   $use_javascript_addr_book = getPref($data_dir, $username, "use_javascript_addr_book");
   if ($use_javascript_addr_book == "")
      $use_javascript_addr_book = $default_use_javascript_addr_book;

   
   /** Load the user's sent folder preferences **/
   global $move_to_sent, $move_to_trash;
   $move_to_sent = getPref($data_dir, $username, "move_to_sent");
   if ($move_to_sent == "")
      $move_to_sent = $default_move_to_sent;

   /** Load the user's trash folder preferences **/
   $move_to_trash = getPref($data_dir, $username, "move_to_trash");
   if ($move_to_trash == "")
      $move_to_trash = $default_move_to_trash;


   global $unseen_type, $unseen_notify;
   $unseen_type = getPref($data_dir, $username, "unseen_type");
   if ($default_unseen_type == "")
      $default_unseen_type = 1;
   if ($unseen_type == "")
      $unseen_type = $default_unseen_type;

   $unseen_notify = getPref($data_dir, $username, "unseen_notify");
   if ($default_unseen_notify == "")
      $default_unseen_notify = 2;
   if ($unseen_notify == "")
      $unseen_notify = $default_unseen_notify;


   global $folder_prefix;
   $folder_prefix = getPref($data_dir, $username, "folder_prefix");
   if ($folder_prefix == "")
      $folder_prefix = $default_folder_prefix;


	/** Load special folders **/
	global $trash_folder, $sent_folder;
	$new_trash_folder = getPref($data_dir, $username, "trash_folder");
	if (($new_trash_folder == "") && ($move_to_trash == true))
		$trash_folder = $folder_prefix . $trash_folder;
	else
		$trash_folder = $new_trash_folder;

	/** Load special folders **/
	$new_sent_folder = getPref($data_dir, $username, "sent_folder");
	if (($new_sent_folder == "") && ($move_to_sent == true))
		$sent_folder = $folder_prefix . $sent_folder;
	else
		$sent_folder = $new_sent_folder;


   global $show_num, $wrap_at, $left_size;
   $show_num = getPref($data_dir, $username, "show_num");
   if ($show_num == "")
      $show_num = 25;
   
   $wrap_at = getPref($data_dir, $username, "wrap_at");
   if ($wrap_at == "")
      $wrap_at = 86;
   if ($wrap_at < 15)
      $wrap_at = 15;

   $left_size = getPref($data_dir, $username, "left_size");
   if ($left_size == "") {
      if (isset($default_left_size))
         $left_size = $default_left_size;
      else  
         $left_size = 200;
   }      


   global $editor_size, $use_signature, $prefix_sig;
   $editor_size = getPref($data_dir, $username, "editor_size");
   if ($editor_size == "")
      $editor_size = 76;

   $use_signature = getPref($data_dir, $username, "use_signature");
   if ($use_signature == "")
      $use_signature = false;

   $prefix_sig = getPref($data_dir, $username, "prefix_sig");
   if ($prefix_sig == "")
      $prefix_sig = true;


   global $left_refresh, $sort;
   $left_refresh = getPref($data_dir, $username, "left_refresh");
   if ($left_refresh == "")
      $left_refresh = false;

   $sort = getPref($data_dir, $username, "sort");
   if ($sort == "")
      $sort = 6;
   
   
   /** Load up the Signature file **/
   global $signature_abs;
   if ($use_signature == true) {
      $signature_abs = $signature = getSig($data_dir, $username);
   } else {
      $signature_abs = getSig($data_dir, $username);
   }


   //  highlightX comes in with the form: name,color,header,value
   global $message_highlight_list;
   for ($i=0; $hlt = getPref($data_dir, $username, "highlight$i"); $i++) {
      $ary = explode(",", $hlt);
      $message_highlight_list[$i]["name"] = $ary[0]; 
      $message_highlight_list[$i]["color"] = $ary[1];
      $message_highlight_list[$i]["value"] = $ary[2];
      $message_highlight_list[$i]["match_type"] = $ary[3];
   }


   #index order lets you change the order of the message index
   global $index_order;
   $order = getPref($data_dir, $username, "order1");
   for ($i=1; $order; $i++) {
      $index_order[$i] = $order;
      $order = getPref($data_dir, $username, "order".($i+1));
   }
   if (!isset($index_order)) {
      $index_order[1] = 1;
      $index_order[2] = 2;
      $index_order[3] = 3;
      $index_order[4] = 5;
      $index_order[5] = 4;
   }
   
   global $alt_index_colors;
   $alt_index_colors = getPref($data_dir, $username, 'alt_index_colors');
   if ($alt_index_colors === 0) {
      $alt_index_colors = false;
   } else {
      $alt_index_colors = true;
   }
   
   
   global $location_of_bar, $location_of_buttons;
   $location_of_bar = getPref($data_dir, $username, 'location_of_bar');
   if ($location_of_bar == '')
       $location_of_bar = 'left';
       
   $location_of_buttons = getPref($data_dir, $username, 'location_of_buttons');
   if ($location_of_buttons == '')
       $location_of_buttons = 'between';
       
       
   global $collapse_folders, $show_html_default;
   $collapse_folders = getPref($data_dir, $username, 'collapse_folders');
   
   $show_html_default = getPref($data_dir, $username, 'show_html_default');

   do_hook("loading_prefs");

?>
