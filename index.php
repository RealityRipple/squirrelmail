<?php
   include "functions/strings.php";

   $location = get_location();
   header("Location: $location/src/login.php\n\n");
   exit();
?>
