<?
   //*************************************************************************
   // Count the number of occurances of $needle are in $haystack.
   //*************************************************************************
   function countCharInString($haystack, $needle) {
      $len = strlen($haystack);
      for ($i = 0; $i < $len; $i++) {
         if ($haystack[$i] == $needle)
            $count++;
      }
      return $count;
   }

   //*************************************************************************
   // Read from the back of $haystack until $needle is found, or the begining
   //    of the $haystack is reached.
   //*************************************************************************
   function readShortMailboxName($haystack, $needle) {
      $len = strlen($haystack);
      for ($i = $len - 1; ($i >= 0) && (!$found);$i--) {
         $char = $haystack[$i];
         if ($char == $needle)
            $found = 1;
         else
            $data .= $char;
      }
      return strrev($data);
   }

?>
