<?php

   /**
    **  index.php -- Displays the main frameset
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Redirects to the login page.
    **
    **  $Id$
    **/

   require_once('../../../functions/strings.php');

   $location = get_location();
   header("Location: $location/src/login.php\n\n");
   exit();

?>