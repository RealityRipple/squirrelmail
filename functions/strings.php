<?php

   $strings_php = true;

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
      if (substr($haystack, -1) == $needle)
         $haystack = substr($haystack, 0, strlen($haystack) - 1);

      if (strrpos($haystack, $needle)) {
         $pos = strrpos($haystack, $needle) + 1;
         $data = substr($haystack, $pos, strlen($haystack));
      } else {
         $data = $haystack;
      }
      return $data;
   }

   // Wraps text at $wrap characters
   function wordWrap($passed, $wrap) {
      $passed = str_replace("&gt;", ">", $passed);
      $passed = str_replace("&lt;", "<", $passed);

      $words = explode(" ", trim($passed));
      $i = 0;
      $line_len = strlen($words[$i])+1;
      $line = "";
      while ($i < count($words)) {
         while ($line_len < $wrap) {
            $line = "$line$words[$i] ";
            $i++;
            $line_len = $line_len + strlen($words[$i])+1;
         }
         $line_len = strlen($words[$i])+1;
         if ($line_len < $wrap) {
            if ($i < count($words)) // don't <BR> the last line
               $line = "$line\n";
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

      $line = str_replace(">", "&gt;", $line);
      $line = str_replace("<", "&lt;", $line);
      return $line;
   }

   /** Returns an array of email addresses **/
   function parseAddrs($text) {
      if (trim($text) == "") {
         return;
      }
      $text = str_replace(" ", "", $text);
      $text = str_replace(",", ";", $text);
      $array = explode(";", $text);
		for ($i = 0; $i < count ($array); $i++) {
			$array[$i] = eregi_replace ("^.*\<", "", $array[$i]);
			$array[$i] = eregi_replace ("\>.*$", "", $array[$i]);
		}
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

   function translateText($body, $wrap_at, $charset) {
      include ("../functions/url_parser.php");
      /** Add any parsing you want to in here */
      $body = trim($body);
      $body_ary = explode("\n", $body);

      for ($i = 0; $i < count($body_ary); $i++) {
         $line = $body_ary[$i];
         $line = "^^$line";

         //$line = str_replace(">", "&gt;", $line);
         //$line = str_replace("<", "&lt;", $line);
         //$line = htmlspecialchars($line);

         if (strlen($line) >= $wrap_at) // -2 because of the ^^ at the beginning
            $line = wordWrap($line, $wrap_at);

         $line = charset_decode($charset, $line);

         $line = str_replace(" ", "&nbsp;", $line);
         $line = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $line);
         $line = nl2br($line);

         if (strpos(trim(str_replace("&nbsp;", "", $line)), "&gt;&gt;") == 2) {
            $line = substr($line, 2, strlen($line));
            $line = "<TT><FONT COLOR=FF0000>$line</FONT></TT><BR>\n";
         } else if (strpos(trim(str_replace("&nbsp;", "", $line)), "&gt;") == 2) {
            $line = substr($line, 2, strlen($line));
            $line = "<TT><FONT COLOR=800000>$line</FONT></TT><BR>\n";
         } else {
            $line = substr($line, 2, strlen($line));
            $line = "<TT><FONT COLOR=000000>$line</FONT></TT><BR>\n";
         }

         $line = parseEmail ($line);
         $line = parseUrl ($line);
         $new_body[$i] = "$line";
      }
      $bdy = implode("\n", $new_body);
      return $bdy;
   }

   /* SquirrelMail version number -- DO NOT CHANGE */
   $version = "0.4pre1";


   function find_mailbox_name ($mailbox) {
      $mailbox = trim($mailbox);
      if (substr($mailbox, strlen($mailbox)-1, strlen($mailbox)) == "\"") {
         $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $pos = strrpos ($mailbox, "\"")+1;
         $box = substr($mailbox, $pos);
      } else {
         $box = substr($mailbox, strrpos($mailbox, " ")+1, strlen($mailbox));
      }
      return $box;
   }

   function replace_spaces ($string) {
      return str_replace(" ", "&nbsp;", $string);
   }

   function replace_escaped_spaces ($string) {
      return str_replace("&nbsp;", " ", $string);
   }
?>
