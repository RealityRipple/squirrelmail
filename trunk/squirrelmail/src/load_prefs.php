<?
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


   /** Load the user's trash folder preferences **/
   $move_to_trash = getPref($data_dir, $username, "move_to_trash");
   if ($move_to_trash == "")
      $move_to_trash = $default_move_to_trash;

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

   $folder_prefix = getPref($data_dir, $username, "folder_prefix");
   if ($folder_prefix == "")
      $folder_prefix = $default_folder_prefix;

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
