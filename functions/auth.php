<?php

/**
 ** auth.php
 **
 ** Contains functions used to do authentication.
 **
 ** $Id$
 **/

   $auth_php = true;

   function is_logged_in () {
      if (!session_is_registered("user_is_logged_in")) {
         echo _("You must login first.");
         echo "</body></html>\n\n";
         exit;
      } else {
         return true;
      }
   }

?>
