<?php
   /** This redirects to the login script **/

   // Get the path
   $path = substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'));

   // Check if this is a HTTPS or regular HTTP request
   $proto = "http://";
   if(isset($HTTPS) && $HTTPS == 'on' ) {
     $proto = "https://";
   }

   // Get the hostname from the Host header or server config.
   // Fallback is to omit the server name and use a relative URI,
   // although this is not RFC 2616 compliant.
   if(isset($HTTP_HOST) && !empty($HTTP_HOST)) {
     $location = $proto . $HTTP_HOST . $path;
   } else if(isset($SERVER_NAME) && !empty($SERVER_NAME)) {
     $location = $proto . $SERVER_NAME . $path;
   } else {
     $location = $path;
   }

   // Redirect
   header("Location: $location/src/login.php\n\n");
   exit();
?>
