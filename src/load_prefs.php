<?
   include("../config/config.php");
   include("../functions/prefs.php");

   $chosen_theme = getPref($data_dir, $username, "chosen_theme");

   if (isset($chosen_theme)) {
      require("$chosen_theme");
   } else {
      require($theme[0]["PATH"]);
   }
?>