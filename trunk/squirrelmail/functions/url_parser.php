<?php
   /* URL Passing code to allow links from with in emails */

   $url_parser_php = true;

   function replaceBlock ($in, $replace, $start, $end) {
      $begin = substr($in,0,$start);
      $end   = substr($in,$end,strlen($in)-$end);
      $ret   = $begin.$replace.$end;
      return $ret;
   }

   function parseEmail ($body) {
      global $color;
      
      // Changed the expression to the one in abook_take
      // This works very well, especially it looks like you might have
      // three instances of it below.  Having it defined in
      // just one spot could help when you need to change it.
      $Expression = "[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-wyz][a-z](g|l|m|pa|t|u|v)?";
      
      /*
        This is here in case we ever decide to use highlighting of searched
        text.  this does it for email addresses
        
      if ($what && ($where == "BODY" || $where == "TEXT")) {
         // Use the $Expression
         eregi ($Expression, $body, $regs);
         $oldaddr = $regs[0];
         if ($oldaddr) {
            $newaddr = eregi_replace ($what, "<b><font color=\"$color[2]\">$what</font></font></b>", $oldaddr);
            $body = str_replace ($oldaddr, "<a href=\"../src/compose.php?send_to=$oldaddr\">$newaddr</a>", $body); 
         }
      } else { 
         // Use the $Expression
         $body = eregi_replace ($Expression, "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
      }
      */
      // Use the $Expression
      $body = eregi_replace ($Expression, "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body); 
      return $body;
   }
   
   function parseUrl ($body) {
      #Possible ways a URL could finish.

      // Removed "--" since it could be part of a URL
      $poss_ends=array(" ", "\n", "\r", "<", ">", ".\r", ".\n", ".&nbsp;", "&nbsp;", ")", "(", 
                       "&quot;", "&lt;", "&gt;", ".<", "]", "[", "{", "}");
      $done=False;
      while (!$done) {
         #Look for when a URL starts
         // Added gopher, news.  Modified telnet.
         $url_tokens = array(
                         "http://",
                         "https://",
                         "ftp://",
                         "telnet:",  // Special case -- doesn't need the slashes
                         "gopher://",
                         "news://");
         for($i = 0; $i < sizeof($url_tokens); $i++) {
           // Removed the use of "^^" -- it is unneeded
           if(is_int($where = strpos(strtolower($body), $url_tokens[$i], $start)))
             break;
         }
         // Look between $start and $where for email links
         $check_str = substr($body, $start, $where);
         $new_str = parseEmail($check_str);
       
         if ($check_str != $new_str)
         {
             $body = replaceBlock($body, $new_str, $start, $where);
             $where = strlen($new_str) + $start;
         }
         
         //$where = strpos(strtolower($body),"http://",$start);
         // Fixed this to work with $i instead of $where
         if ($i < sizeof($url_tokens)) {
            // Removed the "^^" so I removed the next line
            //$where = $where - 2;  // because we added the ^^ at the begining
            # Find the end of that URL
            reset($poss_ends); $end=0; 
            while (list($key, $val) = each($poss_ends)) {
               $enda = strpos($body,$val,$where);
               if ($end == 0) $end = $enda;
               if ($enda < $end and $enda != 0) $end = $enda;
            } 
            if (!$end) $end = strlen($body);
            #Extract URL
            $url = substr($body,$where,$end-$where);
            #Replace URL with HyperLinked Url
            // Now this code doesn't simply match on url_tokens
            // It will need some more text.  This is good.
            if ($url != "" && $url != $url_tokens[$i]) {
               $url_str = "<a href=\"$url\" target=\"_blank\">$url</a>";
               #    $body = str_replace($url,$url_str,$body); 
               # echo "$where, $end<br>";
               $body = replaceBlock($body,$url_str,$where,$end);
               // Removed unnecessary strpos call.  Searching
               // a string takes longer than just figuring out
               // the length.
               // $start = strpos($body,"</a>",$where);
               $start = $where + strlen($url_str);
            } else { 
               // Proper length increment -- Don't just assume 7
               $start = $where + strlen($url_tokens[$i]); 
            } 
         } else {
            $done=true;
         }
      }

      // Look after $start for more email links.
      $check_str = substr($body, $start);
      $new_str = parseEmail($check_str);
       
      if ($check_str != $new_str)
      {
          $body = replaceBlock($body, $new_str, $start, strlen($body));
      }

      return $body;
   }

?>
