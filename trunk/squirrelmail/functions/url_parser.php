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
      
      // Having this defined in just one spot could help when changes need
      // to be made to the pattern
      // Make sure that the expression is evaluated case insensitively
      // 
      // Here's pretty sophisticated IP matching:
      // $IPMatch = '(2[0-5][0-9]|1?[0-9]{1,2})';
      // $IPMatch = '\[?' . $IPMatch . '(\.' . $IPMatch . '){3}\]?';
      //
      // Here's enough:
      $IPMatch = '\[?[0-9]{1,3}(\.[0-9]{1,3}){3}\]?';
      $Host = '(' . $IPMatch . '|[0-9a-z]([-.]?[0-9a-z])*\.[a-wyz][a-z](g|l|m|pa|t|u|v)?)';
      $Expression = '[0-9a-z]([-_.]?[0-9a-z])*(%' . $Host . ')?@' . $Host;
      
      /*
        This is here in case we ever decide to use highlighting of searched
        text.  this does it for email addresses
        
      if ($what && ($where == "BODY" || $where == "TEXT")) {
         eregi ($Expression, $body, $regs);
         $oldaddr = $regs[0];
         if ($oldaddr) {
            $newaddr = eregi_replace ($what, "<b><font color=\"$color[2]\">$what</font></font></b>", $oldaddr);
            $body = str_replace ($oldaddr, "<a href=\"../src/compose.php?send_to=$oldaddr\">$newaddr</a>", $body); 
         }
      } else { 
         $body = eregi_replace ($Expression, "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body);
      }
      */
      
      $body = eregi_replace ($Expression, "<a href=\"../src/compose.php?send_to=\\0\">\\0</a>", $body); 
      return $body;
   }


   function parseUrl ($body)
   {
      $url_tokens = array(
         'http://',
         'https://',
         'ftp://',
         'telnet:',  // Special case -- doesn't need the slashes
         'gopher://',
         'news://');

      $poss_ends = array(' ', '\n', '\r', '<', '>', '.\r', '.\n', '.&nbsp;', 
         '&nbsp;', ')', '(', '&quot;', '&lt;', '&gt;', '.<', ']', '[', '{', 
         '}', "\240");

      $start = 0;
      $target_pos = strlen($body);
      
      while ($start != $target_pos)
      {
        $target_token = '';
        
        // Find the first token to replace
        foreach ($url_tokens as $the_token)
        {
          $pos = strpos(strtolower($body), $the_token, $start);
          if (is_int($pos) && $pos < $target_pos)
          {
            $target_pos = $pos;
            $target_token = $the_token;
          }
        }
        
        // Look for email addresses between $start and $target_pos
        $check_str = substr($body, $start, $target_pos);
        $new_str = parseEmail($check_str);
       
        if ($check_str != $new_str)
        {
          $body = replaceBlock($body, $new_str, $start, $target_pos);
          $target_pos = strlen($new_str) + $start;
        }

        // If there was a token to replace, replace it
        if ($target_token != '')
        {
          // Find the end of the URL
          $end=strlen($body); 
          foreach ($poss_ends as $key => $val)
          {
            $enda = strpos($body,$val,$target_pos);
            if (is_int($enda) && $enda < $end) 
              $end = $enda;
          }
          
          // Extract URL
          $url = substr($body, $target_pos, $end-$target_pos);
          
          // Replace URL with HyperLinked Url, requires 1 char in link
          if ($url != '' && $url != $target_token) 
          {
            $url_str = "<a href=\"$url\" target=\"_blank\">$url</a>";
            $body = replaceBlock($body,$url_str,$target_pos,$end);
            $target_pos += strlen($url_str);
          } 
          else 
          { 
             // Not quite a valid link, skip ahead to next chance
             $target_pos += strlen($target_token);
          }
        }
        
        // Move forward
        $start = $target_pos;
        $target_pos = strlen($body);
      }
      
     return $body;
   }
   
?>
