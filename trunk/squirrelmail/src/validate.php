<?php
   /**
    **  validate.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  $Id$
    **/

   if (defined ('validate_php')) { 
      return; 
   } else { 
      define ('validate_php', true); 
   }

   session_start();
   include ('../functions/auth.php');
   
   // Everyone needs stuff from config, and config needs stuff from
   // strings.php, so include them both here.
   include ('../functions/strings.php');
   include ('../config/config.php');
   
   is_logged_in();


   // Remove all slashes for form values
   if (get_magic_quotes_gpc())
   {
       global $REQUEST_METHOD;
       if ($REQUEST_METHOD == "POST")
       {
           global $HTTP_POST_VARS;
           RemoveSlashes($HTTP_POST_VARS);
       }
       elseif ($REQUEST_METHOD == "GET")
       {
           global $HTTP_GET_VARS;
           RemoveSlashes($HTTP_GET_VARS);
       }
   }

   // Auto-detection
   //
   // if $send (the form button's name) contains "\n" as the first char
   // and the script is compose.php, then trim everything.  Otherwise,
   // we don't have to worry.
   //
   // This is for a RedHat package bug and a Konqueror (pre 2.1.1?) bug
   global $send, $PHP_SELF;
   if (isset($send) && substr($send, 0, 1) == "\n" &&
       substr($PHP_SELF, -12) == '/compose.php')
   {
      if ($REQUEST_METHOD == "POST") {
         global $HTTP_POST_VARS;
         TrimArray($HTTP_POST_VARS);
      } else {
         global $HTTP_GET_VARS;
         TrimArray($HTTP_GET_VARS);
      }
   }

   //**************************************************************************
   // Trims every element in the array
   //**************************************************************************
   function TrimArray(&$array) {
      foreach ($array as $k => $v) {
         global $$k;
         if (is_array($$k)) {
            foreach ($$k as $k2 => $v2) {
	       $$k[$k2] = substr($v2, 1);
            }
         } else {
            $$k = substr($v, 1);
         }
	 // Re-assign back to array
	 $array[$k] = $$k;
      }
   }
   
   
   //**************************************************************************
   // Removes slashes from every element in the array
   //**************************************************************************
   function RemoveSlashes(&$array)
   {
       foreach ($array as $k => $v)
       {
           global $$k;
           if (is_array($$k))
           {
               foreach ($$k as $k2 => $v2)
               {
                   $newArray[stripslashes($k2)] = stripslashes($v2);
               }
               $$k = $newArray;
           }
           else
           {
               $$k = stripslashes($v);
           }
	   // Re-assign back to the array
	   $array[$k] = $$k;
       }
   }

?>
