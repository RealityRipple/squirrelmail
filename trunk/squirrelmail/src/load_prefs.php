<?php
   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($prefs_php))
      include("../functions/prefs.php");
      
   $load_prefs_php = true;
   checkForPrefs($data_dir, $username);

   $chosen_theme = getPref($data_dir, $username, "chosen_theme");

   if ((isset($chosen_theme)) && (file_exists($chosen_theme))) {
      require("$chosen_theme");
   } else {
      if (file_exists($theme[0]["PATH"])) {
         require($theme[0]["PATH"]);
      } else {
         echo _("Theme: ");
         echo $theme[0]["PATH"];
         echo _(" was not found.");
         echo "<BR>";
         echo _("Exiting abnormally");
         exit;
      }
   }

   /** Load the user's sent folder preferences **/
   $move_to_sent = getPref($data_dir, $username, "move_to_sent");
   if ($move_to_sent == "")
      $move_to_sent = $default_move_to_sent;

   /** Load the user's trash folder preferences **/
   $move_to_trash = getPref($data_dir, $username, "move_to_trash");
   if ($move_to_trash == "")
      $move_to_trash = $default_move_to_trash;


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
      $signature = getSig($data_dir, $username);
   } else {
   }
?>
