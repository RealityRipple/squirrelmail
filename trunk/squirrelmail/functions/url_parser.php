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
      /*
        This is here in case we ever decide to use highlighting of searched
        text.  this does it for email addresses
        
      if ($what && ($where == "BODY" || $where == "TEXT")) {
         eregi ("([a-z]|[0-9]|_|\.|-)+\@([a-z]|[0-9]|_|-)+(\.([a-z]|[0-9]|_|-)+)*", $body, $regs);
         $oldaddr = $regs[0];
         if ($oldaddr) {
            $newaddr = eregi_replace ($what, "<b><font color=\"$color[2]\">$what</font></font></b>", $oldaddr);
            $body = str_replace ($oldaddr, "<a href=\"../src/compose.php?send_to=$oldaddr\">$newaddr</a>", $body); 
         }
      } else { 
         $body = eregi_replace ("([a-z]|[0-9]|_|\.|-)+\@([a-z]|[0-9]|_|-)+(\.([a-z]|[0-9]|_|-)+)*", "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
      }
      */
      $body = eregi_replace ("([a-z]|[0-9]|_|\.|-)+\@([a-z]|[0-9]|_|-)+(\.([a-z]|[0-9]|_|-)+)*", "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
      return $body;
   }

   function parseUrl ($body) {
      #Possible ways a URL could finish.

      $poss_ends=array(" ", "\n", "\r", "<", ">", ".\r", ".\n", ".&nbsp;", "&nbsp;", ")", "(", 
                       "&quot;", "&lt;", "&gt;", ".<");
      $done=False;
      while (!$done) {
         #Look for when a URL starts
         $url_tokens = array(
                         "http://",
                         "https://",
                         "ftp://",
                         "telnet://");
         for($i = 0; $i < sizeof($url_tokens); $i++) {
           if($where = strpos(strtolower("^^".$body), $url_tokens[$i], $start))
             break;
         }
         //$where = strpos(strtolower($body),"http://",$start);
         if ($where) {
            $where = $where - 2;  // because we added the ^^ at the begining
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
            if ($url != "") {
               $url_str = "<a href=\"$url\" target=\"_blank\">$url</a>";
               #    $body = str_replace($url,$url_str,$body); 
               # echo "$where, $end<br>";
               $body = replaceBlock($body,$url_str,$where,$end);
               $start = strpos($body,"</a>",$where);
            } else { 
               $start = $where + 7; 
            } 
         } else {
            $done=true;
         }
      }

      return $body;
   }

?>

