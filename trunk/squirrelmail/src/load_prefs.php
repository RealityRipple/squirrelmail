<?
   include("../config/config.php");
   include("../functions/prefs.php");

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
?>