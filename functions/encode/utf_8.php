<?php
/**
 * utf-8 encoding function
 *
 * takes a string of unicode entities and converts it to a utf-8 encoded string
 * each unicode entitiy has the form &#nnn(nn); n={0..9} and can be displayed by utf-8 supporting
 * browsers.  Ascii will not be modified.
 *
 * code is taken from www.php.net manual comments
 * Author: ronen at greyzone dot com
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage encode
 */

/**
 * Converts string to utf-8
 * @param $source string of unicode entities [STRING]
 * @return a utf-8 encoded string [STRING]
 */
function charset_encode_utf_8 ($source) {

   // don't run though encoding function, if there is no encoded characters
   if (! preg_match("'&#'",$source) ) return $source;

   $utf8Str = '';
   $entityArray = explode ("&#", $source);
   $size = count ($entityArray);
   for ($i = 0; $i < $size; $i++) {
       $subStr = $entityArray[$i];
       $nonEntity = strstr ($subStr, ';');
       if ($nonEntity !== false) {
           $unicode = intval (substr ($subStr, 0, (strpos ($subStr, ';') + 1)));
           // determine how many chars are needed to reprsent this unicode char
           if ($unicode < 128) {
               $utf8Substring = chr ($unicode);
           }
           else if ($unicode >= 128 && $unicode < 2048) {
               $binVal = str_pad (decbin ($unicode), 11, "0", STR_PAD_LEFT);
               $binPart1 = substr ($binVal, 0, 5);
               $binPart2 = substr ($binVal, 5);
          
               $char1 = chr (192 + bindec ($binPart1));
               $char2 = chr (128 + bindec ($binPart2));
               $utf8Substring = $char1 . $char2;
           }
           else if ($unicode >= 2048 && $unicode < 65536) {
               $binVal = str_pad (decbin ($unicode), 16, "0", STR_PAD_LEFT);
               $binPart1 = substr ($binVal, 0, 4);
               $binPart2 = substr ($binVal, 4, 6);
               $binPart3 = substr ($binVal, 10);
          
               $char1 = chr (224 + bindec ($binPart1));
               $char2 = chr (128 + bindec ($binPart2));
               $char3 = chr (128 + bindec ($binPart3));
               $utf8Substring = $char1 . $char2 . $char3;
           }
           else {
               $binVal = str_pad (decbin ($unicode), 21, "0", STR_PAD_LEFT);
               $binPart1 = substr ($binVal, 0, 3);
               $binPart2 = substr ($binVal, 3, 6);
               $binPart3 = substr ($binVal, 9, 6);
               $binPart4 = substr ($binVal, 15);
      
               $char1 = chr (240 + bindec ($binPart1));
               $char2 = chr (128 + bindec ($binPart2));
               $char3 = chr (128 + bindec ($binPart3));
               $char4 = chr (128 + bindec ($binPart4));
               $utf8Substring = $char1 . $char2 . $char3 . $char4;
           }
          
           if (strlen ($nonEntity) > 1)
               $nonEntity = substr ($nonEntity, 1); // chop the first char (';')
           else
               $nonEntity = '';

           $utf8Str .= $utf8Substring . $nonEntity;
       }
       else {
           $utf8Str .= $subStr;
       }
   }

   return $utf8Str;
}
?>