<?php

   /**
    **  redirect.php -- derived from webmail.php by Ralf Kraudelt
    **                                              kraude@wiwi.uni-rostock.de
    **
    **  Copyright (c) 1999-2000 ...
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  prevents users from reposting their form data after a
    **  successful logout
    **
    **  $Id$
    **/

   include('../functions/i18n.php');
   include('../functions/strings.php');
   include('../config/config.php');

   // Before starting the session, the base URI must be known.
   // Assuming that this file is in the src/ subdirectory (or
   // something).
   ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
   $base_uri = $regs[1];

   header('Pragma: no-cache');
   $location = get_location();

   session_set_cookie_params (0, $base_uri);
   session_start();

   session_unregister ('user_is_logged_in');
   session_register ('base_uri');

   if (! isset($squirrelmail_language)) 
      $squirrelmail_language = '';
   set_up_language($squirrelmail_language, true);
   
   if(!isset($login_username)) {
      echo "<html><body bgcolor=\"ffffff\">\n";
      echo "<br><br>";
      echo "<center>";
      echo "<b>"._("You must be logged in to access this page.")."</b><br>";
      echo "<a href=\"../src/login.php\">"._("Go to the login page")."</a>\n";
      echo "</center>";
      echo "</body></html>\n";
      exit;
   }

   // Refresh the language cookie.
   if (isset($squirrelmail_language)) {
      setcookie('squirrelmail_language', $squirrelmail_language, time()+2592000);
   }


   include ('../functions/prefs.php');
   include ('../functions/imap.php');
   include ('../functions/plugin.php');

   if (!session_is_registered('user_is_logged_in')) {
      do_hook ('login_before');

      $onetimepad = OneTimePadCreate(strlen($secretkey));
      $key = OneTimePadEncrypt($secretkey, $onetimepad);
      session_register('onetimepad');
      // verify that username and password are correct
      if ($force_username_lowercase)
          $login_username = strtolower($login_username);
      $imapConnection = sqimap_login($login_username, $key, $imapServerAddress, $imapPort, 0);
	  if (!$imapConnection) {
             echo "<html><body bgcolor=\"ffffff\">\n";
	     echo "<br><br>";
	     echo "<center>";
	     echo "<b>"._("There was an error contacting the mail server.")."</b><br>";
	     echo _("Contact your administrator for help.")."\n";
	     echo "</center>";
	     echo "</body></html>\n";
	     exit;
	  }
      sqimap_logout($imapConnection);

      setcookie('username', $login_username, 0, $base_uri);
      setcookie('key', $key, 0, $base_uri);
      do_hook ('login_verified');
   }

   $user_is_logged_in = true;
   session_register ('user_is_logged_in');

   if(isset($rcptemail))
      header("Location: webmail.php?right_frame=compose.php&rcptaddress=$rcptemail");
   else
      header("Location: webmail.php");
?>
