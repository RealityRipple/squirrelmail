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
      $body = eregi_replace ("([a-z]|[0-9]|_|\.|-)+\@([a-z]|[0-9]|_|-)+(\.([a-z]|[0-9]|_|-)+)*", "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
      return $body;
   }

   function parseUrl ($body) {
      #Possible ways a URL could finish.

      $poss_ends=array(" ","\n","\r",">",".&nbsp","&nbsp");
      $done=False;
      while (!$done) {
         #Look for when a URL starts
         $where = strpos($body,"http:",$start);
         if ($where) {
            # Find the end of that URL
            reset($poss_ends); $end=0; 
            while (list($key, $val) = each($poss_ends)) {
               $enda = strpos($body,$val,$where);
               if ($end == 0) $end = $enda;
               if ($enda < $end and $enda != 0) $end = $enda;
            } 
            #Extract URL
            $url = substr($body,$where,$end-$where);
            #Replace URL with HyperLinked Url
            if ($url != "") {
               $url_str = "<a href=\"$url\" target=\"_blank\">$url</a>";
               #    $body = str_replace($url,$url_str,$body); 
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

