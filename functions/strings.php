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

   // Wraps text at $wrap_max characters
   function wordWrap($line) {
      $newline = $line;
      $lastpart = $line;
      $numlines = 0;
      $wrap_max = 80;
      while (strlen($lastpart) > $wrap_max) {
         $pos = $wrap_max;
         while ((substr($line, $pos, $pos+1) != " ") && ($pos > 0)) {
            $pos--;
         }
         $before = substr($line, 0, $pos);
         $lastpart = substr($line, $pos+1, strlen($line));
         $newline = $before . "<BR>" . $lastpart;
         $numlines++;
      }
      return $newline;
   }
?>
