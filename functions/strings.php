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

   // Searches for the next position in a string minus white space
   function next_pos_minus_white ($haystack, $pos) {
      while (substr($haystack, $pos, 1) == " " ||
             substr($haystack, $pos, 1) == "\t" ||
             substr($haystack, $pos, 1) == "\n" ||
             substr($haystack, $pos, 1) == "\r") {
         if ($pos >= strlen($haystack))
            return -1;
         $pos++;
      }        
      return $pos;        
   }

   // Wraps text at $wrap characters
   function sqWordWrap($passed, $wrap) {
      $passed = str_replace("&lt;", "<", $passed);
      $passed = str_replace("&gt;", ">", $passed);

      preg_match("/^(\s|>)+/", $passed, $regs);
      $beginning_spaces = $regs[0];

      $words = explode(" ", $passed);
      $i = -1;
      $line_len = strlen($words[0])+1;
      $line = "";
      if (count($words) > 1) {   
         while ($i++ < count($words)) {
            while ($line_len < $wrap) {
               $line = "$line$words[$i] ";
               $i++;
               $line_len = $line_len + strlen($words[$i]) + 1;
            }
            $line_len = strlen($words[$i])+1;
            if ($line_len <= $wrap) {
               if (strlen($beginning_spaces) +2 >= $wrap)
                  $beginning_spaces = "";
               if ($i < count($words)) { // don't <BR> the last line
                  $line = "$line\n$beginning_spaces";
               }   
               $line = "$line$words[$i] ";
               $line_len = strlen($beginning_spaces) + strlen($words[$i]) + 1;
            } else {
               /*
               $endline = $words[$i];
               while ($line_len >= $wrap) {
                  $bigline = substr($endline, 0, $wrap);
                  $endline = substr($endline, $wrap, strlen($endline));
                  $line_len = strlen($endline);
                  $line = "$line$bigline<BR>";
               }
               */
               if (strlen($line) > $wrap)
                  $line = "$line\n$words[$i]";
               else
                  $line = "$line$words[$i]";
               $line_len = strlen($words[$i]);
            }
         }
      } else {
         $line = $words[0];
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
      $text = ereg_replace( '"[^"]*"', "", $text);
      $text = str_replace(",", ";", $text);
      $array = explode(";", $text);
      for ($i = 0; $i < count ($array); $i++) {
			    $array[$i] = eregi_replace ("^.*[<]", "", $array[$i]);
			    $array[$i] = eregi_replace ("[>].*$", "", $array[$i]);
		  }
      return $array;
   }

   /** Returns a line of comma separated email addresses from an array **/
   function getLineOfAddrs($array) {
      if (is_array($array)) {
        $to_line = implode(", ", $array);
        $to_line = trim(ereg_replace(",,+", ",", $to_line));
      } else {
        $to_line = "";
      }
      return $to_line;
   }

   function translateText($body, $wrap_at, $charset) {
      global $where, $what; // from searching

      if (!isset($url_parser_php)) {
         include "../functions/url_parser.php";
      }
      
      $body_ary = explode("\n", $body);
      for ($i=0; $i < count($body_ary); $i++) {
         $line = $body_ary[$i];
         $line = charset_decode($charset, $line);
         $line = str_replace("\t", '        ', $line);
         
         if (strlen($line) - 2 >= $wrap_at) {
            $line = sqWordWrap($line, $wrap_at);  
         }
         
         $line = str_replace(' ', '&nbsp;', $line);
         $line = nl2br($line);

         // Removed parseEmail and integrated it into parseUrl
         // This line is no longer needed.
         // $line = parseEmail ($line);
         $line = parseUrl ($line);
         
         $test_line = str_replace('&nbsp;', '', $line);
         if (strpos($test_line, '&gt;&gt;') === 0) {
            $line = "<FONT COLOR=FF0000>$line</FONT>\n";
         } else if (strpos($test_line, '&gt;') === 0) {
            $line = "<FONT COLOR=800000>$line</FONT>\n";
         }

         if ($line)
         {
             $line = '<tt>' . $line . '</tt>';
         }

         $body_ary[$i] = $line . '<br>';
      }
      $body = implode("\n", $body_ary);
            
      return $body;
   }

   /* SquirrelMail version number -- DO NOT CHANGE */
   $version = "0.5";


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

   function get_location () {
      # This determines the location to forward to relative
      # to your server.  If this doesnt work correctly for
      # you (although it should), you can remove all this 
      # code except the last two lines, and change the header()
      # function to look something like this, customized to
      # the location of SquirrelMail on your server:
      #
      #   http://www.myhost.com/squirrelmail/src/login.php
   
      global $PHP_SELF, $SERVER_NAME, $HTTPS, $HTTP_HOST;

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
      return $location;
   }   

   function sqStripSlashes($string) {
      if (get_magic_quotes_gpc()) {
         $string = stripslashes($string);
      }
      return $string;
   }


   // These functions are used to encrypt the passowrd before it is
   // stored in a cookie.
   function OneTimePadEncrypt ($string, $pad) {
      for ($i = 0; $i < strlen ($string); $i++) {
	 $encrypted .= chr (ord($string[$i]) ^ ord($pad[$i]));
      }

      return base64_encode($encrypted);
   }

   function OneTimePadDecrypt ($string, $pad) {
      $encrypted = base64_decode ($string);
      
      for ($i = 0; $i < strlen ($encrypted); $i++) {
	 $decrypted .= chr (ord($encrypted[$i]) ^ ord($pad[$i]));
      }

      return $decrypted;
   }

   function OneTimePadCreate ($length=100) {
      srand ((double) microtime() * 1000000);
      
      for ($i = 0; $i < $length; $i++) {
	 $pad .= chr(rand(0,255));
      }

      return $pad;
   }

?>
