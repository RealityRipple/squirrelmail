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
    **/

   session_start();

   if (!isset($i18n_php))
      include ("../functions/i18n.php");

   if(!isset($username)) {
      set_up_language($squirrelmail_language);
      echo _("You need a valid user and password to access this page!");
      exit;
   }

   include ("../config/config.php");
   include ("../functions/prefs.php");
   include ("../functions/imap.php");
   if (!isset($plugin_php))
      include ("../functions/plugin.php");
   if (!isset($auth_php))
      include ("../functions/auth.php");
   if (!isset($strings_php))
      include ("../functions/strings.php");

   if ($force_username_lowercase)
      $username = strtolower($username);

   include ("../src/load_prefs.php");

   // We'll need this to later have a noframes version
   set_up_language(getPref($data_dir, $username, "language"));

   echo "<html><head>\n";
   echo "<TITLE>";
   echo "$org_title";
   echo "</TITLE>";
   $ishelp = substr(getenv(REQUEST_URI),-8);			// If calling help, set left frame to 300
   
   if (ishelp == 'help.php')
       $bar_size = 300;
   else
       $bar_size = $left_size;
   
   if ($location_of_bar == 'right')
   {
      echo "<FRAMESET COLS=\"*, $left_size\" NORESIZE=yes BORDER=0>";
   } else {
      echo "<FRAMESET COLS=\"$left_size, *\" NORESIZE BORDER=0>";
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
   if ($right_frame == "right_main.php") {
      $urlMailbox = urlencode($mailbox);
      $right_frame_url = "right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage";
   } else if ($right_frame == "options.php") {
      $right_frame_url = "options.php";
   } else if ($right_frame == "folders.php") {
      $right_frame_url = "folders.php";
   } else {
      if (!isset($just_logged_in)) $just_logged_in = 0;
      $right_frame_url = "right_main.php?just_logged_in=$just_logged_in";
   }

   if ($location_of_bar == 'right')
   {
      echo "<FRAME SRC=\"$right_frame_url\" NAME=\"right\">";
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
   }
   else
   {
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"$right_frame_url\" NAME=\"right\">";
   }

?>
</FRAMESET>
</HEAD></HTML>
