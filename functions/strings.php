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

   // Wraps text at $wrap characters
   function wordWrap($passed, $wrap) {
      $words = explode(" ", trim($passed));
      $i = 0;
      $line_len = strlen($words[$i])+1;
      $line = "";
      while ($i < count($words)) {
         while ($line_len < $wrap) {
            $line = "$line$words[$i]&nbsp;";
            $i++;
            $line_len = $line_len + strlen($words[$i])+1;
         }
         if ($i < count($words)) // don't <BR> the last line
            $line = "$line<BR>";
         $line_len = strlen($words[$i])+1;
      }
      return $line;
   }
?>
