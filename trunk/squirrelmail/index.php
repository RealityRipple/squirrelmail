<?php
   $headers = getallheaders();
   $path = substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'));
   $location = $headers["Host"] . $path;

   header("Location: http://$location/src/login.php\n\n");
   exit();
?>
