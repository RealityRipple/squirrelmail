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
   $languages[2]["NAME"] = "Deutcsh";
   $languages[2]["CODE"] = "de";

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
      } else if ($charset == "koi8-r") {
        return charset_decode_koi8r ($string);
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

   // iso-8859-7 is Greek.
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
         $replace = "&#" . (ord($res[1])+720) . ";";
         $string = ereg_replace($res[1], $replace, $string);
      }

      // 11/07 (0xB7) Middle dot is the same in iso-8859-1

      // ISO-8859-7 characters from 11/08 (0xB8) to 11/10 (0xBA)
      // These are Unicode 900-902
      while (ereg("([\270-\272])", $string, $res)) {
         $replace = "&#" . (ord($res[1])+720) . ";";
         $string = ereg_replace($res[1], $replace, $string);
      }

      // 11/11 (0xBB) Right angle quotation mark is the same as in
      // iso-8859-1

      // And now the rest of the charset
      while (ereg("([\273-\376])", $string, $res)) {
         $replace = "&#" . (ord($res[1])+720) . ";";
         $string = ereg_replace($res[1], $replace, $string);
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

   // ISO-8859-15 is Cyrillic
   function charset_decode_iso_8859_5 ($string) {
      // Not yet implemented.
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

   // KOI8-R is used to encode Russian mail (Cyrrilic). Defined in RFC
   // 1489.
   function charset_decode_koi8r ($string) {
      global $default_charset;

      if ($default_charset == "koi8-r") {
         return $string;
      } else { 
         // Convert to Unicode HTML entities.
         // This code is rather ineffective.
         $string = ereg_replace("\200", "&#9472;", $string);
         $string = ereg_replace("\201", "&#9474;", $string);
         $string = ereg_replace("\202", "&#9484;", $string);
         $string = ereg_replace("\203", "&#9488;", $string);
         $string = ereg_replace("\204", "&#9492;", $string);
         $string = ereg_replace("\205", "&#9496;", $string);
         $string = ereg_replace("\206", "&#9500;", $string);
         $string = ereg_replace("\207", "&#9508;", $string);
         $string = ereg_replace("\210", "&#9516;", $string);
         $string = ereg_replace("\211", "&#9524;", $string);
         $string = ereg_replace("\212", "&#9532;", $string);
         $string = ereg_replace("\213", "&#9600;", $string);
         $string = ereg_replace("\214", "&#9604;", $string);
         $string = ereg_replace("\215", "&#9608;", $string);
         $string = ereg_replace("\216", "&#9612;", $string);
         $string = ereg_replace("\217", "&#9616;", $string);
         $string = ereg_replace("\220", "&#9617;", $string);
         $string = ereg_replace("\221", "&#9618;", $string);
         $string = ereg_replace("\222", "&#9619;", $string);
         $string = ereg_replace("\223", "&#8992;", $string);
         $string = ereg_replace("\224", "&#9632;", $string);
         $string = ereg_replace("\225", "&#8729;", $string);
         $string = ereg_replace("\226", "&#8730;", $string);
         $string = ereg_replace("\227", "&#8776;", $string);
         $string = ereg_replace("\230", "&#8804;", $string);
         $string = ereg_replace("\231", "&#8805;", $string);
         $string = ereg_replace("\232", "&#160;", $string);
         $string = ereg_replace("\233", "&#8993;", $string);
         $string = ereg_replace("\234", "&#176;", $string);
         $string = ereg_replace("\235", "&#178;", $string);
         $string = ereg_replace("\236", "&#183;", $string);
         $string = ereg_replace("\237", "&#247;", $string);
         $string = ereg_replace("\240", "&#9552;", $string);
         $string = ereg_replace("\241", "&#9553;", $string);
         $string = ereg_replace("\242", "&#9554;", $string);
         $string = ereg_replace("\243", "&#1105;", $string);
         $string = ereg_replace("\244", "&#9555;", $string);
         $string = ereg_replace("\245", "&#9556;", $string);
         $string = ereg_replace("\246", "&#9557;", $string);
         $string = ereg_replace("\247", "&#9558;", $string);
         $string = ereg_replace("\250", "&#9559;", $string);
         $string = ereg_replace("\251", "&#9560;", $string);
         $string = ereg_replace("\252", "&#9561;", $string);
         $string = ereg_replace("\253", "&#9562;", $string);
         $string = ereg_replace("\254", "&#9563;", $string);
         $string = ereg_replace("\255", "&#9564;", $string);
         $string = ereg_replace("\256", "&#9565;", $string);
         $string = ereg_replace("\257", "&#9566;", $string);
         $string = ereg_replace("\260", "&#9567;", $string);
         $string = ereg_replace("\261", "&#9568;", $string);
         $string = ereg_replace("\262", "&#9569;", $string);
         $string = ereg_replace("\263", "&#1025;", $string);
         $string = ereg_replace("\264", "&#9570;", $string);
         $string = ereg_replace("\265", "&#9571;", $string);
         $string = ereg_replace("\266", "&#9572;", $string);
         $string = ereg_replace("\267", "&#9573;", $string);
         $string = ereg_replace("\270", "&#9574;", $string);
         $string = ereg_replace("\271", "&#9575;", $string);
         $string = ereg_replace("\272", "&#9576;", $string);
         $string = ereg_replace("\273", "&#9577;", $string);
         $string = ereg_replace("\274", "&#9578;", $string);
         $string = ereg_replace("\275", "&#9579;", $string);
         $string = ereg_replace("\276", "&#9580;", $string);
         $string = ereg_replace("\277", "&#169;", $string);
         $string = ereg_replace("\300", "&#1102;", $string);
         $string = ereg_replace("\301", "&#1072;", $string);
         $string = ereg_replace("\302", "&#1073;", $string);
         $string = ereg_replace("\303", "&#1094;", $string);
         $string = ereg_replace("\304", "&#1076;", $string);
         $string = ereg_replace("\305", "&#1077;", $string);
         $string = ereg_replace("\306", "&#1092;", $string);
         $string = ereg_replace("\307", "&#1075;", $string);
         $string = ereg_replace("\310", "&#1093;", $string);
         $string = ereg_replace("\311", "&#1080;", $string);
         $string = ereg_replace("\312", "&#1081;", $string);
         $string = ereg_replace("\313", "&#1082;", $string);
         $string = ereg_replace("\314", "&#1083;", $string);
         $string = ereg_replace("\315", "&#1084;", $string);
         $string = ereg_replace("\316", "&#1085;", $string);
         $string = ereg_replace("\317", "&#1086;", $string);
         $string = ereg_replace("\320", "&#1087;", $string);
         $string = ereg_replace("\321", "&#1103;", $string);
         $string = ereg_replace("\322", "&#1088;", $string);
         $string = ereg_replace("\323", "&#1089;", $string);
         $string = ereg_replace("\324", "&#1090;", $string);
         $string = ereg_replace("\325", "&#1091;", $string);
         $string = ereg_replace("\326", "&#1078;", $string);
         $string = ereg_replace("\327", "&#1074;", $string);
         $string = ereg_replace("\330", "&#1100;", $string);
         $string = ereg_replace("\331", "&#1099;", $string);
         $string = ereg_replace("\332", "&#1079;", $string);
         $string = ereg_replace("\333", "&#1096;", $string);
         $string = ereg_replace("\334", "&#1101;", $string);
         $string = ereg_replace("\335", "&#1097;", $string);
         $string = ereg_replace("\336", "&#1095;", $string);
         $string = ereg_replace("\337", "&#1098;", $string);
         $string = ereg_replace("\340", "&#1070;", $string);
         $string = ereg_replace("\341", "&#1040;", $string);
         $string = ereg_replace("\342", "&#1041;", $string);
         $string = ereg_replace("\343", "&#1062;", $string);
         $string = ereg_replace("\344", "&#1044;", $string);
         $string = ereg_replace("\345", "&#1045;", $string);
         $string = ereg_replace("\346", "&#1060;", $string);
         $string = ereg_replace("\347", "&#1043;", $string);
         $string = ereg_replace("\350", "&#1061;", $string);
         $string = ereg_replace("\351", "&#1048;", $string);
         $string = ereg_replace("\352", "&#1049;", $string);
         $string = ereg_replace("\353", "&#1050;", $string);
         $string = ereg_replace("\354", "&#1051;", $string);
         $string = ereg_replace("\355", "&#1052;", $string);
         $string = ereg_replace("\356", "&#1053;", $string);
         $string = ereg_replace("\357", "&#1054;", $string);
         $string = ereg_replace("\360", "&#1055;", $string);
         $string = ereg_replace("\361", "&#1071;", $string);
         $string = ereg_replace("\362", "&#1056;", $string);
         $string = ereg_replace("\363", "&#1057;", $string);
         $string = ereg_replace("\364", "&#1058;", $string);
         $string = ereg_replace("\365", "&#1059;", $string);
         $string = ereg_replace("\366", "&#1046;", $string);
         $string = ereg_replace("\367", "&#1042;", $string);
         $string = ereg_replace("\370", "&#1068;", $string);
         $string = ereg_replace("\371", "&#1067;", $string);
         $string = ereg_replace("\372", "&#1047;", $string);
         $string = ereg_replace("\373", "&#1064;", $string);
         $string = ereg_replace("\374", "&#1069;", $string);
         $string = ereg_replace("\375", "&#1065;", $string);
         $string = ereg_replace("\376", "&#1063;", $string);
         $string = ereg_replace("\377", "&#1066;", $string);

         return $string;
      }
   }

?>
