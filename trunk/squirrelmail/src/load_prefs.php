<?
   include("../config/config.php");
   include("../functions/prefs.php");

   checkForPrefs($data_dir, $username);

   $chosen_theme = getPref($data_dir, $username, "chosen_theme");

   if ((isset($chosen_theme)) && (file_exists($chosen_theme))) {
      require("$chosen_theme");
   } else {
      if (file_exists($theme[0]["PATH"])) {
         require($theme[0]["PATH"]);
      } else {
         echo "Theme: " . $theme[0]["PATH"] . " was not found.<BR>";
         echo "Exiting abnormally";
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

   $editor_size = getPref($data_dir, $username, "editor_size");
   if ($editor_size == "")
      $editor_size = 76;
?>

