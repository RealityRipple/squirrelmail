<?php
   /** Author:       Tyler Akins
       Theme Name:   Random Theme Every Login

       Guess what this does!
       
   **/

   sq_mt_randomize();
   
   global $theme, $random_theme_good_themes;
   
   if (! session_is_registered('random_theme_good_theme')) {
      $good_themes = array();
      foreach ($theme as $data) {
         if (substr($data['PATH'], -18) != '/themes/random.php')
            $good_themes[] = $data['PATH'];
      }
      if (count($good_themes) == 0)
         $good_themes[] = "../themes/default.php";
      $which = mt_rand(0, count($good_themes));
      $random_theme_good_theme = $good_themes[$which];
      session_register('random_theme_good_theme');
   }
   
   @include_once ($random_theme_good_theme);

?>
