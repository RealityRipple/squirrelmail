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
    **/

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($prefs_php))
      include("../functions/prefs.php");
   if (!isset($plugin_php))
      include("../functions/plugin.php");
      
   $load_prefs_php = true;
   checkForPrefs($data_dir, $username);

   $chosen_theme = getPref($data_dir, $username, "chosen_theme");
   if ((substr($chosen_theme, 0, 10) == "../config/")) {
      $chosen_theme = substr($chosen_theme, 10);
      $chosen_theme = "../themes/$chosen_theme";
   }

   if ((isset($chosen_theme)) && (file_exists($chosen_theme))) {
      require("$chosen_theme");
   } else {
      if (file_exists($theme[0]["PATH"])) {
         require($theme[0]["PATH"]);
      } else {
          #
          #  I hard coded the theme as a last resort if no themes were
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

	session_register("theme_css");

   $use_javascript_addr_book = getPref($data_dir, $username, "use_javascript_addr_book");
   if ($use_javascript_addr_book == "")
      $use_javascript_addr_book = $default_use_javascript_addr_book;

   
   /** Load the user's sent folder preferences **/
   $move_to_sent = getPref($data_dir, $username, "move_to_sent");
   if ($move_to_sent == "")
      $move_to_sent = $default_move_to_sent;

   /** Load the user's trash folder preferences **/
   $move_to_trash = getPref($data_dir, $username, "move_to_trash");
   if ($move_to_trash == "")
      $move_to_trash = $default_move_to_trash;


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


   $folder_prefix = getPref($data_dir, $username, "folder_prefix");
   if ($folder_prefix == "")
      $folder_prefix = $default_folder_prefix;

	/** Load special folders **/
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

   $show_num = getPref($data_dir, $username, "show_num");
   if ($show_num == "")
      $show_num = 25;
   
   $wrap_at = getPref($data_dir, $username, "wrap_at");
   if ($wrap_at == "")
      $wrap_at = 86;

   $left_size = getPref($data_dir, $username, "left_size");
   if ($left_size == "") {
      if (isset($default_left_size))
         $left_size = $default_left_size;
      else  
         $left_size = 200;
   }      

   $editor_size = getPref($data_dir, $username, "editor_size");
   if ($editor_size == "")
      $editor_size = 76;

   $use_signature = getPref($data_dir, $username, "use_signature");
   if ($use_signature == "")
      $use_signature = false;

   $left_refresh = getPref($data_dir, $username, "left_refresh");
   if ($left_refresh == "")
      $left_refresh = false;
   
   /** Load up the Signature file **/
   if ($use_signature == true) {
      $signature_abs = $signature = getSig($data_dir, $username);
   } else {
      $signature_abs = getSig($data_dir, $username);
   }

   //  highlightX comes in with the form: name,color,header,value
   for ($i=0; $hlt = getPref($data_dir, $username, "highlight$i"); $i++) {
      $ary = explode(",", $hlt);
      $message_highlight_list[$i]["name"] = $ary[0]; 
      $message_highlight_list[$i]["color"] = $ary[1];
      $message_highlight_list[$i]["value"] = $ary[2];
      $message_highlight_list[$i]["match_type"] = $ary[3];
   }

   do_hook("loading_prefs");
?>
