<?php

   $strings_php = true;

   //*************************************************************************
   // Count the number of occurances of $needle are in $haystack.
   //*************************************************************************
   function countCharInString($haystack, $needle) {
      $haystack = ereg_replace("[^$needle]","",$haystack);
      return strlen($haystack);
   }

   //*************************************************************************
   // Read from the back of $haystack until $needle is found, or the begining
   //    of the $haystack is reached.  $needle is a single character
   //*************************************************************************
   function readShortMailboxName($haystack, $needle) {
      if ($needle == "") return $haystack;
      if ($needle == ".") $needle = "\.";
      ereg("([^$needle]+)$needle?$", $haystack, $regs);
      return $regs[1];
   }

   //*************************************************************************
   // Read from the back of $haystack until $needle is found, or the begining
   //    of the $haystack is reached.  $needle is a single character
   //*************************************************************************
   function readMailboxParent($haystack, $needle) {
      if ($needle == ".") $needle = "\.";
      ereg("^(.+)$needle([^$needle]+)$needle?$", $haystack, $regs);
      return $regs[1];
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
   // Has a problem with special HTML characters, so call this before
   // you do character translation.
   // Specifically, &#039 comes up as 5 characters instead of 1.
   // This should not add newlines to the end of lines.
   function sqWordWrap(&$line, $wrap) {
      preg_match("/^([\s>]*)([^\s>].*)?$/", $line, $regs);
      $beginning_spaces = $regs[1];
      $words = explode(" ", $regs[2]);

      $i = 0;
      $line = $beginning_spaces;
      
      while ($i < count($words)) {
         // Force one word to be on a line (minimum)
         $line .= $words[$i];
         $line_len = strlen($beginning_spaces) + strlen($words[$i]) +
             strlen($words[$i + 1]) + 2;
         $i ++;
            
         // Add more words (as long as they fit)
         while ($line_len < $wrap && $i < count($words)) {
            $line .= ' ' . $words[$i];
            $i++;
            $line_len += strlen($words[$i]) + 1;
         }
            
         // Skip spaces if they are the first thing on a continued line
         while (!$words[$i] && $i < count($words)) {
            $i ++;
         }

         // Go to the next line if we have more to process            
         if ($i < count($words)) {
            $line .= "\n$beginning_spaces";
         }
      }
   }
   
   
   // Does the opposite of sqWordWrap()
   function sqUnWordWrap(&$body)
   {
       $lines = explode("\n", $body);
       $body = "";
       $PreviousSpaces = "";
       for ($i = 0; $i < count($lines); $i ++)
       {
           preg_match("/^([\s>]*)([^\s>].*)?$/", $lines[$i], $regs);
           $CurrentSpaces = $regs[1];
           $CurrentRest = $regs[2];
           if ($i == 0)
           {
               $PreviousSpaces = $CurrentSpaces;
               $body = $lines[$i];
           }
           else if ($PreviousSpaces == $CurrentSpaces &&  // Do the beginnings match
               strlen($lines[$i - 1]) > 65 &&             // Over 65 characters long
               strlen($CurrentRest))                      // and there's a line to continue with
           {
               $body .= ' ' . $CurrentRest;
           }
           else
           {
               $body .= "\n" . $lines[$i];
               $PreviousSpaces = $CurrentSpaces;
           }
       }
       $body .= "\n";
   }
   

   /** Returns an array of email addresses **/
   /* Be cautious of "user@host.com" */
   function parseAddrs($text) {
      if (trim($text) == "")
         return;
      $text = str_replace(" ", "", $text);
      $text = ereg_replace('"[^"]*"', "", $text);
      $text = ereg_replace("\([^\)]*\)", "", $text);
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

   function translateText(&$body, $wrap_at, $charset) {
      global $where, $what; // from searching
		global $url_parser_php;

      if (!isset($url_parser_php)) {
         include "../functions/url_parser.php";
      }
      
      $body_ary = explode("\n", $body);
      $PriorQuotes = 0;
      for ($i=0; $i < count($body_ary); $i++) {
         $line = $body_ary[$i];
         if (strlen($line) - 2 >= $wrap_at) {
            sqWordWrap($line, $wrap_at);  
         }
         $line = charset_decode($charset, $line);
         $line = str_replace("\t", '        ', $line);
         
         parseUrl ($line);
         
         $Quotes = 0;
         $pos = 0;
         while (1)
         {
             if ($line[$pos] == ' ')
             {
                $pos ++;
             }
             else if (strpos($line, '&gt;', $pos) === $pos)
             {
                $pos += 4;
                $Quotes ++;
             }
             else
             {
                 break;
             }
         }
         
         if ($Quotes > 1)
            $line = "<FONT COLOR=FF0000>$line</FONT>";
         elseif ($Quotes)
            $line = "<FONT COLOR=800000>$line</FONT>";

         $body_ary[$i] = $line;
      }
      $body = "<pre>" . implode("\n", $body_ary) . "</pre>";
   }

   /* SquirrelMail version number -- DO NOT CHANGE */
   $version = "1.0pre2 (cvs)";


   function find_mailbox_name ($mailbox) {
/*
      $mailbox = trim($mailbox);
      if (substr($mailbox, strlen($mailbox)-1, strlen($mailbox)) == "\"") {
         $mailbox = substr($mailbox, 0, strlen($mailbox) - 1);
         $pos = strrpos ($mailbox, "\"")+1;
         $box = substr($mailbox, $pos);
      } else {
         $box = substr($mailbox, strrpos($mailbox, " ")+1, strlen($mailbox));
      }
      return $box;
*/      

      if (ereg(" *\"([^\r\n\"]*)\"[ \r\n]*$", $mailbox, $regs))
          return $regs[1];
      ereg(" *([^ \r\n\"]*)[ \r\n]*$",$mailbox,$regs);
      return $regs[1];

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
   
      global $PHP_SELF, $SERVER_NAME, $HTTPS, $HTTP_HOST, $SERVER_PORT;

      // Get the path
      $path = substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'));
   
      // Check if this is a HTTPS or regular HTTP request
      $proto = "http://";
      if(isset($HTTPS) && !strcasecmp($HTTPS, 'on') ) {
        $proto = "https://";
      }
   
      // Get the hostname from the Host header or server config.
      $host = "";
      if (isset($HTTP_HOST) && !empty($HTTP_HOST))
      {
          $host = $HTTP_HOST;
      }
      else if (isset($SERVER_NAME) && !empty($SERVER_NAME))
      {
          $host = $SERVER_NAME;
      }
      
      $port = '';
      if (! strstr($host, ':'))
      {
          if (isset($SERVER_PORT)) {
              if (($SERVER_PORT != 80 && $proto == "http://")
                      || ($SERVER_PORT != 443 && $proto == "https://")) {
                  $port = sprintf(':%d', $SERVER_PORT);
              }
          }
      }
      
      if ($host)
          return $proto . $host . $port . $path;

      // Fallback is to omit the server name and use a relative URI,
      // although this is not RFC 2616 compliant.
      return $path;    
   }   

   function sqStripSlashes($string) {
      if (get_magic_quotes_gpc()) {
         $string = stripslashes($string);
      }
      return $string;
   }


   // These functions are used to encrypt the passowrd before it is
   // stored in a cookie.
   function OneTimePadEncrypt ($string, $epad) {
      $pad = base64_decode($epad);
      for ($i = 0; $i < strlen ($string); $i++) {
	 $encrypted .= chr (ord($string[$i]) ^ ord($pad[$i]));
      }

      return base64_encode($encrypted);
   }

   function OneTimePadDecrypt ($string, $epad) {
      $pad = base64_decode($epad);
      $encrypted = base64_decode ($string);
      
      for ($i = 0; $i < strlen ($encrypted); $i++) {
	 $decrypted .= chr (ord($encrypted[$i]) ^ ord($pad[$i]));
      }

      return $decrypted;
   }


   // Randomize the mt_rand() function.  Toss this in strings or
   // integers and it will seed the generator appropriately.
   // With strings, it is better to get them long. Use md5() to
   // lengthen smaller strings.
   function sq_mt_seed($Val)
   {
       // if mt_getrandmax() does not return a 2^n - 1 number,
       // this might not work well.  This uses $Max as a bitmask.
       $Max = mt_getrandmax();
       
       if (! is_int($Val))
       {
           if (function_exists("crc32"))
           {
               $Val = crc32($Val);
           }
           else
           {
               $Str = $Val;
               $Pos = 0;
               $Val = 0;
               $Mask = $Max / 2;
               $HighBit = $Max ^ $Mask;
               while ($Pos < strlen($Str))
               {
                   if ($Val & $HighBit)
                   {
                       $Val = (($Val & $Mask) << 1) + 1;
                   }
                   else
                   {
                       $Val = ($Val & $Mask) << 1;
                   }
                   $Val ^= $Str[$Pos];
                   $Pos ++;
               }
           }
       }

       if ($Val < 0)
         $Val *= -1;
       if ($Val = 0)
         return;

       mt_srand(($Val ^ mt_rand(0, $Max)) & $Max);
   }
   
   
   // This function initializes the random number generator fairly well.
   // It also only initializes it once, so you don't accidentally get
   // the same 'random' numbers twice in one session.
   function sq_mt_randomize()
   {
      global $REMOTE_PORT, $REMOTE_ADDR, $UNIQUE_ID;
      static $randomized;
      
      if ($randomized)
         return;
      
      // Global   
      sq_mt_seed((int)((double) microtime() * 1000000));
      sq_mt_seed(md5($REMOTE_PORT . $REMOTE_ADDR . getmypid()));
      
      // getrusage
      if (function_exists("getrusage")) {
         $dat = getrusage();
	 sq_mt_seed(md5($dat["ru_nswap"] . $dat["ru_majflt"] . 
	    $dat["ru_utime.tv_sec"] . $dat["ru_utime.tv_usec"]));
      }
      
      // Apache-specific
      sq_mt_seed(md5($UNIQUE_ID));
      
      $randomized = 1;
   }
   
   function OneTimePadCreate ($length=100) {
      sq_mt_randomize();
      
      for ($i = 0; $i < $length; $i++) {
	 $pad .= chr(mt_rand(0,255));
      }

      return base64_encode($pad);
   }

   // Check if we have a required PHP-version. Return TRUE if we do,
   // or FALSE if we don't.
   // To check for 4.0.1, use sqCheckPHPVersion(4,0,1)
   // To check for 4.0b3, use sqCheckPHPVersion(4,0,-3)
   // Does not handle betas like 4.0.1b1 or development versions
   function sqCheckPHPVersion($major, $minor, $release) {

      $ver = phpversion();
      eregi("^([0-9]+)\.([0-9]+)(.*)", $ver, $regs);

      // Parse the version string
      $vmajor  = strval($regs[1]);
      $vminor  = strval($regs[2]);
      $vrel    = $regs[3];
      if($vrel[0] == ".") 
	 $vrel = strval(substr($vrel, 1));
      if($vrel[0] == "b" || $vrel[0] == "B") 
	 $vrel = - strval(substr($vrel, 1));
      if($vrel[0] == "r" || $vrel[0] == "R") 
	 $vrel = - strval(substr($vrel, 2))/10;
      
      // Compare major version
      if($vmajor < $major) return false;
      if($vmajor > $major) return true;

      // Major is the same. Compare minor
      if($vminor < $minor) return false;
      if($vminor > $minor) return true;
      
      // Major and minor is the same as the required one.
      // Compare release
      if($vrel >= 0 && $release >= 0) {       // Neither are beta
	 if($vrel < $release) return false;
      } else if($vrel >= 0 && $release < 0){  // This is not beta, required is beta
	 return true;
      } else if($vrel < 0 && $release >= 0){  // This is beta, require not beta
	 return false;
      } else {                                // Both are beta
	 if($vrel > $release) return false;
      }
      
      return true;
   }
   
   /* Returns a string showing the size of the message/attachment */
   function show_readable_size($bytes)
   {
       $bytes /= 1024;
       $type = 'k';
       
       if ($bytes / 1024 > 1)
       {
           $bytes /= 1024;
           $type = 'm';
       }
       
       if ($bytes < 10)
       {
           $bytes *= 10;
           settype($bytes, "integer");
           $bytes /= 10;
       }
       else
           settype($bytes, "integer");
       
       return $bytes . '<small>&nbsp;' . $type . '</small>';
   }

   /* Generates a random string from the caracter set you pass in
    *
    * Flags:
    *   1 = add lowercase a-z to $chars
    *   2 = add uppercase A-Z to $chars
    *   4 = add numbers 0-9 to $chars
    */
  
   function GenerateRandomString($size, $chars, $flags = 0)
   {
      if ($flags & 0x1)
          $chars .= 'abcdefghijklmnopqrstuvwxyz';
      if ($flags & 0x2)
          $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      if ($flags & 0x4)
          $chars .= '0123456789';
          
      if ($size < 1 || strlen($chars) < 1)
          return "";
          
      sq_mt_randomize(); // Initialize the random number generator
    
      while (strlen($String) < $size) {
         $String .= $chars[mt_rand(0, strlen($chars))];
      }
      
      return $String;
   }

?>
