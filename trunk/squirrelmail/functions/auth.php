<?php

/**
 ** auth.php
 **
 ** Contains functions used to do authentication.
 **
 ** $Id$
 **/

   if (defined ('auth_php'))
      return; 
   define ('auth_php', true); 

   function is_logged_in () {
      if (session_is_registered('user_is_logged_in'))
         return;
	 
      echo "<html><body bgcolor=\"ffffff\">\n";
      echo "<br><br>";
      echo "<center>";
      echo "<b>"._("You must be logged in to access this page.")."</b><br>";
      echo "<a href=\"../src/login.php\">"._("Go to the login page")."</a>\n";
      echo "</center>";
      echo "</body></html>\n";
      exit;
   }

?>
