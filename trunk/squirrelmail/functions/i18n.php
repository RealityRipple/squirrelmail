<?

/**
 ** i18n.php
 ** 
 ** This file contains variuos functions that are needed to do
 ** internationalization of SquirrelMail.
 **
 ** Internally iso-8859-1 is used as character set. Other characters
 ** are encoded using Unicode entities according to HTML 4.0.
 **
 **/

   $i18n_php = true;

   // This array specifies the available languages.
   $languages[0]["NAME"] = "English";
   $languages[0]["CODE"] = "en";
   $languages[1]["NAME"] = "Norsk";
   $languages[1]["CODE"] = "no";

   // Decodes a string to the internal encoding from the given charset
   function charset_decode ($charset, $string) {
      // All HTML special characters are 7 bit and can be replaced first
      $string = htmlspecialchars ($string);

      $charset = strtolower($charset);

      if (ereg("iso-8859-(.*)", $charset, $res)) {
         if ($res[1] == "1")
            return charset_decode_iso_8859_1 ($string);
         if ($res[1] == "7")
            return charset_decode_iso_8859_7 ($string);
         else if ($res[1] == "15")
            return charset_decode_iso_8859_15 ($string);
         else
            return charset_decode_iso_8859_default ($string);
      } else if ($charset == "ns_4551-1") {
         return charset_decode_ns_4551_1 ($string);
      } else
         return $string;
   }

   // iso-8859-1 is the same as Latin 1 and is normally used
   // in western europe.
   function charset_decode_iso_8859_1 ($string) {
      // This is only debug code as long as the internal
      // character set is iso-8859-1

      // Latin small letter o with stroke
      while (ereg("\370", $string))
         $string = ereg_replace ("\370", "&#248;", $string);

      return ($string);
   }

   // iso-8859-1 is Greek.
   function charset_decode_iso_8859_7 ($string) {
      // Could not find Unicode equivalent of 0xA1 and 0xA2
      // 0xA4, 0xA5, 0xAA, 0xAE, 0xD2 and 0xFF should not be used
      $string = strtr($string, "\241\242\244\245\252\256\322\377", 
                      "????????");

      // Horizontal bar (parentheki pavla)
      while (ereg("\257", $string))
         $string = ereg_replace ("\257", "&#8213;", $string);

      // ISO-8859-7 characters from 11/04 (0xB4) to 11/06 (0xB6)
      // These are Unicode 900-902
      while (ereg("([\264-\266])", $string, $res)) {
         $replace = "&#." . ord($res[1])+720 . ";";
         ereg_repleace("[\264-\266]", $replace, $string);
      }

      // 11/07 (0xB7) Middle dot is the same in iso-8859-1

      // ISO-8859-7 characters from 11/08 (0xB8) to 11/10 (0xBA)
      // These are Unicode 900-902
      while (ereg("([\270-\272])", $string, $res)) {
         $replace = "&#." . ord($res[1])+720 . ";";
         ereg_repleace("[\270-\272]", $replace, $string);
      }

      // 11/11 (0xBB) Right angle quotation mark is the same as in
      // iso-8859-1

      // And now the rest of the charset
      while (ereg("([\273-\376])", $string, $res)) {
         $replace = "&#." . ord($res[1])+720 . ";";
         ereg_repleace("[\273-\376]", $replace, $string);
      }

      return $string;
   }

   // iso-8859-15 is Latin 15 and has very much the same use as Latin 1
   // but has the Euro symbol and some characters needed for French.
   function charset_decode_iso_8859_15 ($string) {
      // Euro sign
      while (ereg("\244", $replace))
         $string = ereg_replace ("\244", "&#8364;", $string);
      // Latin capital letter S with caron
      while (ereg("\246", $string))
         $string = ereg_replace ("\244", "&#352;", $string);
      // Latin small letter s with caron
      while (ereg("\250", $string))
         $string = ereg_replace ("\250", "&#353;", $string);
      // Latin capital letter Z with caron
      while (ereg("\264", $string))
         $string = ereg_replace ("\264", "&#381;", $string);
      // Latin small letter z with caron
      while (ereg("\270", $string))
         $string = ereg_replace ("\270", "&#382;", $string);
      // Latin capital ligature OE
      while (ereg("\274", $string))
         $string = ereg_replace ("\274", "&#338;", $string);
      // Latin small ligature oe
      while (ereg("\275", $string))
         $string = ereg_replace ("\275", "&#339;", $string);
      // Latin capital letter Y with diaeresis
      while (ereg("\276", $string))
         $string = ereg_replace ("\276", "&#376;", $string);

      return ($string);
   }

   // Remove all 8 bit characters from all other ISO-8859 character sets
   function charset_decode_iso_8859_default ($string) {
      return (strtr($string, "\240\241\242\243\244\245\246\247".
                    "\250\251\252\253\254\255\256\257".
                    "\260\261\262\263\264\265\266\267".
                    "\270\271\272\273\274\275\276\277".
                    "\300\301\302\303\304\305\306\307".
                    "\310\311\312\313\314\315\316\317".
                    "\320\321\322\323\324\325\326\327".
                    "\330\331\332\333\334\335\336\337".
                    "\340\341\342\343\344\345\346\347".
                    "\350\351\352\353\354\355\356\357".
                    "\360\361\362\363\364\365\366\367".
                    "\370\371\372\373\374\375\376\377", 
                    "????????????????????????????????????????".
                    "????????????????????????????????????????".
                    "????????????????????????????????????????".
                    "????????"));
      
   }

   // This is the same as ISO-646-NO and is used by some
   // Microsoft programs when sending Norwegian characters
   function charset_decode_ns_4551_1 ($string) {
      // These characters are:
      // Latin capital letter AE
      // Latin capital letter O with stroke
      // Latin capital letter A with ring above
      // and the same as small letters
      return strtr ($string, "[\\]{|}", "������");
   }

?>
