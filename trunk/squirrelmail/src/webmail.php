<?php

   /**
    **  webmail.php -- Displays the main frameset
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This file generates the main frameset. The files that are
    **  shown can be given as parameters. If the user is not logged in
    **  this file will verify username and password.
    **
    **  $Id$
    **/

   include ('../functions/strings.php');
   include ('../config/config.php');
   include ('../functions/prefs.php');
   include ('../functions/imap.php');
   include ('../functions/plugin.php');
   include ('../functions/auth.php');

   session_start();
   is_logged_in();
   checkForPrefs($data_dir, $username);

   // We'll need this to later have a noframes version
   //
   // Check if the user has a language preference, but no cookie.
   // Send him a cookie with his language preference, if there is
   // such discrepancy.
   $my_language=getPref($data_dir, $username, "language");
   if ($my_language != $squirrelmail_language)
     setcookie('squirrelmail_language', $my_language, time()+2592000);

   set_up_language(getPref($data_dir, $username, 'language'));

   echo "<html><head>\n";
   echo '<TITLE>';
   echo $org_title;
   echo '</TITLE>';
   
   $left_size = getPref($data_dir, $username, "left_size");
   $location_of_bar = getPref($data_dir, $username, "location_of_bar");
   if ($location_of_bar == '')
       $location_of_bar = 'left';
   if ($left_size == "") {
      if (isset($default_left_size))
         $left_size = $default_left_size;
      else  
         $left_size = 200;
   }      
   
   if ($location_of_bar == 'right')
   {
      echo "<FRAMESET COLS=\"*, $left_size\" BORDER=0>";
   } else {
      echo "<FRAMESET COLS=\"$left_size, *\" BORDER=0>";
   }

/**
    There are three ways to call webmail.php
    1.  webmail.php
         - This just loads the default entry screen.
    2.  webmail.php?right_frame=right_main.php&sort=X&startMessage=X&mailbox=XXXX
         - This loads the frames starting at the given values.
    3.  webmail.php?right_frame=folders.php
         - Loads the frames with the Folder options in the right frame.

    This was done to create a pure HTML way of refreshing the folder list since
    we would like to use as little Javascript as possible.
**/
   if (!isset($right_frame)) $right_frame = "";

   if ($right_frame == 'right_main.php') {
      $urlMailbox = urlencode($mailbox);
      $right_frame_url = "right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage";
   } else if ($right_frame == 'options.php') {
      $right_frame_url = 'options.php';
   } else if ($right_frame == 'folders.php') {
      $right_frame_url = 'folders.php';
   } else {
      if (!isset($just_logged_in)) $just_logged_in = 0;
      $right_frame_url = "right_main.php?just_logged_in=$just_logged_in";
   }

   if ($location_of_bar == 'right')
   {
      echo "<FRAME SRC=\"$right_frame_url\" NORESIZE NAME=\"right\">";
      echo '<FRAME SRC="left_main.php" NORESIZE NAME="left">';
   }
   else
   {
      echo '<FRAME SRC="left_main.php" NORESIZE NAME="left">';
      echo "<FRAME SRC=\"$right_frame_url\" NORESIZE NAME=\"right\">";
   }

?>
</FRAMESET>
</HEAD></HTML>
