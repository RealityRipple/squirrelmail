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
      if (strpos($haystack, $needle)) {
         $pos = strrpos($haystack, $needle) + 1;
         $data = substr($haystack, $pos, strlen($haystack));
      } else {
         $data = $haystack;
      }
      return $data;
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
         $line_len = strlen($words[$i])+1;
         if ($line_len < $wrap) {
            if ($i < count($words)) // don't <BR> the last line
               $line = "$line<BR>";
         } else {
            $endline = $words[$i];
            while ($line_len >= $wrap) {
               $bigline = substr($endline, 0, $wrap);
               $endline = substr($endline, $wrap, strlen($endline));
               $line_len = strlen($endline);
               $line = "$line$bigline<BR>";
            }
            $line = "$line$endline<BR>";
            $i++;
         }
      }
      return $line;
   }

   /** Returns an array of email addresses **/
   function parseAddrs($text) {
      $text = str_replace(" ", "", $text);
      $text = str_replace(",", ";", $text);
      $array = explode(";", $text);
      return $array;
   }

   /** Returns a line of comma separated email addresses from an array **/
   function getLineOfAddrs($array) {
      $to_line = "";
      for ($i = 0; $i < count($array); $i++) {
         if ($to_line)
            $to_line = "$to_line, $array[$i]";
         else
            $to_line = "$array[$i]";
      }
      return $to_line;
   }
?>
