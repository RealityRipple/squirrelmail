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

   if (!isset($strings_php))
      include ("../functions/strings.php");
   include("../config/config.php");

   // Before starting the session, the base URI must be known.
   // Assuming that this file is in the src/ subdirectory (or
   // something).
   ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
   $base_uri = $regs[1];

   header("Pragma: no-cache");
   $location = get_location();

   session_set_cookie_params (0, $base_uri);
   session_start();

   session_register ("base_uri");

   if(!isset($login_username)) {
      exit;
   }

   // Refresh the language cookie.
   if (isset($squirrelmail_language)) {
      setcookie("squirrelmail_language", $squirrelmail_language, time()+2592000);
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

   if (!session_is_registered("user_is_logged_in") || $logged_in != 1) {
      do_hook ("login_before");

      $onetimepad = OneTimePadCreate(strlen($secretkey));
      $key = OneTimePadEncrypt($secretkey, $onetimepad);
      session_register("onetimepad");
      // verify that username and password are correct
      if ($force_username_lowercase)
          $login_username = strtolower($login_username);
      $imapConnection = sqimap_login($login_username, $key, $imapServerAddress, $imapPort, 0);
	  if (!$imapConnection) {
	  	exit;
	  }
      sqimap_logout($imapConnection);

      setcookie("username", $login_username, 0, $base_uri);
      setcookie("key", $key, 0, $base_uri);
      setcookie("logged_in", 1, 0, $base_uri);
      do_hook ("login_verified");
   }

   session_register ("user_is_logged_in");
   $user_is_logged_in = true;

   header("Location: $location/webmail.php");
?>
