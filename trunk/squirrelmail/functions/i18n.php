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
   $languages["en"]["NAME"]    = "English";
   $languages["no"]["NAME"]    = "Norsk";
   $languages["no"]["CHARSET"] = "iso-8859-1";
   $languages["de"]["NAME"]    = "Deutcsh";
   $languages["de"]["CHARSET"] = "iso-8859-1";
   $languages["ru"]["NAME"]    = "Russian KOI8-R";
   $languages["ru"]["CHARSET"] = "koi8-r";

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
         return "$string";
   }

   // iso-8859-1 is the same as Latin 1 and is normally used
   // in western europe.
   function charset_decode_iso_8859_1 ($string) {
      global $default_charset;

      if (strtolower($default_charset) == "iso-8859-1") {
         return $string;
      } else { 
         // Only do the slow convert if there are 8-bit characters
         if (ereg("[\200-\377]", $string)) {
            $string = str_replace("\201", "&#129;", $string);
            $string = str_replace("\202", "&#130;", $string);
            $string = str_replace("\203", "&#131;", $string);
            $string = str_replace("\204", "&#132;", $string);
            $string = str_replace("\205", "&#133;", $string);
            $string = str_replace("\206", "&#134;", $string);
            $string = str_replace("\207", "&#135;", $string);
            $string = str_replace("\210", "&#136;", $string);
            $string = str_replace("\211", "&#137;", $string);
            $string = str_replace("\212", "&#138;", $string);
            $string = str_replace("\213", "&#139;", $string);
            $string = str_replace("\214", "&#140;", $string);
            $string = str_replace("\215", "&#141;", $string);
            $string = str_replace("\216", "&#142;", $string);
            $string = str_replace("\217", "&#143;", $string);
            $string = str_replace("\220", "&#144;", $string);
            $string = str_replace("\221", "&#145;", $string);
            $string = str_replace("\222", "&#146;", $string);
            $string = str_replace("\223", "&#147;", $string);
            $string = str_replace("\224", "&#148;", $string);
            $string = str_replace("\225", "&#149;", $string);
            $string = str_replace("\226", "&#150;", $string);
            $string = str_replace("\227", "&#151;", $string);
            $string = str_replace("\230", "&#152;", $string);
            $string = str_replace("\231", "&#153;", $string);
            $string = str_replace("\232", "&#154;", $string);
            $string = str_replace("\233", "&#155;", $string);
            $string = str_replace("\234", "&#156;", $string);
            $string = str_replace("\235", "&#157;", $string);
            $string = str_replace("\236", "&#158;", $string);
            $string = str_replace("\237", "&#159;", $string);
            $string = str_replace("\240", "&#160;", $string);
            $string = str_replace("\241", "&#161;", $string);
            $string = str_replace("\242", "&#162;", $string);
            $string = str_replace("\243", "&#163;", $string);
            $string = str_replace("\244", "&#164;", $string);
            $string = str_replace("\245", "&#165;", $string);
            $string = str_replace("\246", "&#166;", $string);
            $string = str_replace("\247", "&#167;", $string);
            $string = str_replace("\250", "&#168;", $string);
            $string = str_replace("\251", "&#169;", $string);
            $string = str_replace("\252", "&#170;", $string);
            $string = str_replace("\253", "&#171;", $string);
            $string = str_replace("\254", "&#172;", $string);
            $string = str_replace("\255", "&#173;", $string);
            $string = str_replace("\256", "&#174;", $string);
            $string = str_replace("\257", "&#175;", $string);
            $string = str_replace("\260", "&#176;", $string);
            $string = str_replace("\261", "&#177;", $string);
            $string = str_replace("\262", "&#178;", $string);
            $string = str_replace("\263", "&#179;", $string);
            $string = str_replace("\264", "&#180;", $string);
            $string = str_replace("\265", "&#181;", $string);
            $string = str_replace("\266", "&#182;", $string);
            $string = str_replace("\267", "&#183;", $string);
            $string = str_replace("\270", "&#184;", $string);
            $string = str_replace("\271", "&#185;", $string);
            $string = str_replace("\272", "&#186;", $string);
            $string = str_replace("\273", "&#187;", $string);
            $string = str_replace("\274", "&#188;", $string);
            $string = str_replace("\275", "&#189;", $string);
            $string = str_replace("\276", "&#190;", $string);
            $string = str_replace("\277", "&#191;", $string);
            $string = str_replace("\300", "&#192;", $string);
            $string = str_replace("\301", "&#193;", $string);
            $string = str_replace("\302", "&#194;", $string);
            $string = str_replace("\303", "&#195;", $string);
            $string = str_replace("\304", "&#196;", $string);
            $string = str_replace("\305", "&#197;", $string);
            $string = str_replace("\306", "&#198;", $string);
            $string = str_replace("\307", "&#199;", $string);
            $string = str_replace("\310", "&#200;", $string);
            $string = str_replace("\311", "&#201;", $string);
            $string = str_replace("\312", "&#202;", $string);
            $string = str_replace("\313", "&#203;", $string);
            $string = str_replace("\314", "&#204;", $string);
            $string = str_replace("\315", "&#205;", $string);
            $string = str_replace("\316", "&#206;", $string);
            $string = str_replace("\317", "&#207;", $string);
            $string = str_replace("\320", "&#208;", $string);
            $string = str_replace("\321", "&#209;", $string);
            $string = str_replace("\322", "&#210;", $string);
            $string = str_replace("\323", "&#211;", $string);
            $string = str_replace("\324", "&#212;", $string);
            $string = str_replace("\325", "&#213;", $string);
            $string = str_replace("\326", "&#214;", $string);
            $string = str_replace("\327", "&#215;", $string);
            $string = str_replace("\330", "&#216;", $string);
            $string = str_replace("\331", "&#217;", $string);
            $string = str_replace("\332", "&#218;", $string);
            $string = str_replace("\333", "&#219;", $string);
            $string = str_replace("\334", "&#220;", $string);
            $string = str_replace("\335", "&#221;", $string);
            $string = str_replace("\336", "&#222;", $string);
            $string = str_replace("\337", "&#223;", $string);
            $string = str_replace("\340", "&#224;", $string);
            $string = str_replace("\341", "&#225;", $string);
            $string = str_replace("\342", "&#226;", $string);
            $string = str_replace("\343", "&#227;", $string);
            $string = str_replace("\344", "&#228;", $string);
            $string = str_replace("\345", "&#229;", $string);
            $string = str_replace("\346", "&#230;", $string);
            $string = str_replace("\347", "&#231;", $string);
            $string = str_replace("\350", "&#232;", $string);
            $string = str_replace("\351", "&#233;", $string);
            $string = str_replace("\352", "&#234;", $string);
            $string = str_replace("\353", "&#235;", $string);
            $string = str_replace("\354", "&#236;", $string);
            $string = str_replace("\355", "&#237;", $string);
            $string = str_replace("\356", "&#238;", $string);
            $string = str_replace("\357", "&#239;", $string);
            $string = str_replace("\360", "&#240;", $string);
            $string = str_replace("\361", "&#241;", $string);
            $string = str_replace("\362", "&#242;", $string);
            $string = str_replace("\363", "&#243;", $string);
            $string = str_replace("\364", "&#244;", $string);
            $string = str_replace("\365", "&#245;", $string);
            $string = str_replace("\366", "&#246;", $string);
            $string = str_replace("\367", "&#247;", $string);
            $string = str_replace("\370", "&#248;", $string);
            $string = str_replace("\371", "&#249;", $string);
            $string = str_replace("\372", "&#250;", $string);
            $string = str_replace("\373", "&#251;", $string);
            $string = str_replace("\374", "&#252;", $string);
            $string = str_replace("\375", "&#253;", $string);
            $string = str_replace("\376", "&#254;", $string);
            $string = str_replace("\377", "&#255;", $string);
         }
      }

      return ($string);
   }

   // iso-8859-7 is Greek.
   function charset_decode_iso_8859_7 ($string) {
      global $default_charset;

      if (strtolower($default_charset) == "iso-8859-7") {
         return $string;
      } else { 
         // Only do the slow convert if there are 8-bit characters
         if (ereg("[\200-\377]", $string)) {
            // Some diverse characters in the beginning
            $string = str_replace("\240", "&#160;", $string);
            $string = str_replace("\241", "&#8216;", $string);
            $string = str_replace("\242", "&#8217;", $string);
            $string = str_replace("\243", "&#163;", $string);
            $string = str_replace("\246", "&#166;", $string);
            $string = str_replace("\247", "&#167;", $string);
            $string = str_replace("\250", "&#168;", $string);
            $string = str_replace("\251", "&#169;", $string);
            $string = str_replace("\253", "&#171;", $string);
            $string = str_replace("\254", "&#172;", $string);
            $string = str_replace("\255", "&#173;", $string);
            $string = str_replace("\257", "&#8213;", $string);
            $string = str_replace("\260", "&#176;", $string);
            $string = str_replace("\261", "&#177;", $string);
            $string = str_replace("\262", "&#178;", $string);
            $string = str_replace("\263", "&#179;", $string);
         
         // Horizontal bar (parentheki pavla)
            $string = str_replace ("\257", "&#8213;", $string);
         
            // ISO-8859-7 characters from 11/04 (0xB4) to 11/06 (0xB6)
            // These are Unicode 900-902
            while (ereg("([\264-\266])", $string, $res)) {
               $replace = "&#" . (ord($res[1])+720) . ";";
               $string = str_replace($res[1], $replace, $string);
            }
         
            // 11/07 (0xB7) Middle dot is the same in iso-8859-1
            $string = str_replace("\267", "&#183;", $string);
         
            // ISO-8859-7 characters from 11/08 (0xB8) to 11/10 (0xBA)
            // These are Unicode 900-902
            while (ereg("([\270-\272])", $string, $res)) {
               $replace = "&#" . (ord($res[1])+720) . ";";
               $string = str_replace($res[1], $replace, $string);
            }
         
            // 11/11 (0xBB) Right angle quotation mark is the same as in
            // iso-8859-1
            $string = str_replace("\273", "&#187;", $string);
         
         // And now the rest of the charset
            while (ereg("([\274-\376])", $string, $res)) {
               $replace = "&#" . (ord($res[1])+720) . ";";
               $string = str_replace($res[1], $replace, $string);
            }
         }
      }

      return $string;
   }

   // iso-8859-15 is Latin 15 and has very much the same use as Latin 1
   // but has the Euro symbol and some characters needed for French.
   function charset_decode_iso_8859_15 ($string) {
      // Euro sign
      $string = str_replace ("\244", "&#8364;", $string);
      // Latin capital letter S with caron
      $string = str_replace ("\244", "&#352;", $string);
      // Latin small letter s with caron
      $string = str_replace ("\250", "&#353;", $string);
      // Latin capital letter Z with caron
      $string = str_replace ("\264", "&#381;", $string);
      // Latin small letter z with caron
      $string = str_replace ("\270", "&#382;", $string);
      // Latin capital ligature OE
      $string = str_replace ("\274", "&#338;", $string);
      // Latin small ligature oe
      $string = str_replace ("\275", "&#339;", $string);
      // Latin capital letter Y with diaeresis
      $string = str_replace ("\276", "&#376;", $string);

      return (charset_decode_iso_8859_1($string));
   }

   // ISO-8859-15 is Cyrillic
   function charset_decode_iso_8859_5 ($string) {
      // Convert to KOI8-R, then return this decoded.
      $string = convert_cyr_string($string, "i", "k");
      return charset_decode_koi8r($string);
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
      return strtr ($string, "[\\]{|}", "ÆØÅæøå");
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
         $string = str_replace("\200", "&#9472;", $string);
         $string = str_replace("\201", "&#9474;", $string);
         $string = str_replace("\202", "&#9484;", $string);
         $string = str_replace("\203", "&#9488;", $string);
         $string = str_replace("\204", "&#9492;", $string);
         $string = str_replace("\205", "&#9496;", $string);
         $string = str_replace("\206", "&#9500;", $string);
         $string = str_replace("\207", "&#9508;", $string);
         $string = str_replace("\210", "&#9516;", $string);
         $string = str_replace("\211", "&#9524;", $string);
         $string = str_replace("\212", "&#9532;", $string);
         $string = str_replace("\213", "&#9600;", $string);
         $string = str_replace("\214", "&#9604;", $string);
         $string = str_replace("\215", "&#9608;", $string);
         $string = str_replace("\216", "&#9612;", $string);
         $string = str_replace("\217", "&#9616;", $string);
         $string = str_replace("\220", "&#9617;", $string);
         $string = str_replace("\221", "&#9618;", $string);
         $string = str_replace("\222", "&#9619;", $string);
         $string = str_replace("\223", "&#8992;", $string);
         $string = str_replace("\224", "&#9632;", $string);
         $string = str_replace("\225", "&#8729;", $string);
         $string = str_replace("\226", "&#8730;", $string);
         $string = str_replace("\227", "&#8776;", $string);
         $string = str_replace("\230", "&#8804;", $string);
         $string = str_replace("\231", "&#8805;", $string);
         $string = str_replace("\232", "&#160;", $string);
         $string = str_replace("\233", "&#8993;", $string);
         $string = str_replace("\234", "&#176;", $string);
         $string = str_replace("\235", "&#178;", $string);
         $string = str_replace("\236", "&#183;", $string);
         $string = str_replace("\237", "&#247;", $string);
         $string = str_replace("\240", "&#9552;", $string);
         $string = str_replace("\241", "&#9553;", $string);
         $string = str_replace("\242", "&#9554;", $string);
         $string = str_replace("\243", "&#1105;", $string);
         $string = str_replace("\244", "&#9555;", $string);
         $string = str_replace("\245", "&#9556;", $string);
         $string = str_replace("\246", "&#9557;", $string);
         $string = str_replace("\247", "&#9558;", $string);
         $string = str_replace("\250", "&#9559;", $string);
         $string = str_replace("\251", "&#9560;", $string);
         $string = str_replace("\252", "&#9561;", $string);
         $string = str_replace("\253", "&#9562;", $string);
         $string = str_replace("\254", "&#9563;", $string);
         $string = str_replace("\255", "&#9564;", $string);
         $string = str_replace("\256", "&#9565;", $string);
         $string = str_replace("\257", "&#9566;", $string);
         $string = str_replace("\260", "&#9567;", $string);
         $string = str_replace("\261", "&#9568;", $string);
         $string = str_replace("\262", "&#9569;", $string);
         $string = str_replace("\263", "&#1025;", $string);
         $string = str_replace("\264", "&#9570;", $string);
         $string = str_replace("\265", "&#9571;", $string);
         $string = str_replace("\266", "&#9572;", $string);
         $string = str_replace("\267", "&#9573;", $string);
         $string = str_replace("\270", "&#9574;", $string);
         $string = str_replace("\271", "&#9575;", $string);
         $string = str_replace("\272", "&#9576;", $string);
         $string = str_replace("\273", "&#9577;", $string);
         $string = str_replace("\274", "&#9578;", $string);
         $string = str_replace("\275", "&#9579;", $string);
         $string = str_replace("\276", "&#9580;", $string);
         $string = str_replace("\277", "&#169;", $string);
         $string = str_replace("\300", "&#1102;", $string);
         $string = str_replace("\301", "&#1072;", $string);
         $string = str_replace("\302", "&#1073;", $string);
         $string = str_replace("\303", "&#1094;", $string);
         $string = str_replace("\304", "&#1076;", $string);
         $string = str_replace("\305", "&#1077;", $string);
         $string = str_replace("\306", "&#1092;", $string);
         $string = str_replace("\307", "&#1075;", $string);
         $string = str_replace("\310", "&#1093;", $string);
         $string = str_replace("\311", "&#1080;", $string);
         $string = str_replace("\312", "&#1081;", $string);
         $string = str_replace("\313", "&#1082;", $string);
         $string = str_replace("\314", "&#1083;", $string);
         $string = str_replace("\315", "&#1084;", $string);
         $string = str_replace("\316", "&#1085;", $string);
         $string = str_replace("\317", "&#1086;", $string);
         $string = str_replace("\320", "&#1087;", $string);
         $string = str_replace("\321", "&#1103;", $string);
         $string = str_replace("\322", "&#1088;", $string);
         $string = str_replace("\323", "&#1089;", $string);
         $string = str_replace("\324", "&#1090;", $string);
         $string = str_replace("\325", "&#1091;", $string);
         $string = str_replace("\326", "&#1078;", $string);
         $string = str_replace("\327", "&#1074;", $string);
         $string = str_replace("\330", "&#1100;", $string);
         $string = str_replace("\331", "&#1099;", $string);
         $string = str_replace("\332", "&#1079;", $string);
         $string = str_replace("\333", "&#1096;", $string);
         $string = str_replace("\334", "&#1101;", $string);
         $string = str_replace("\335", "&#1097;", $string);
         $string = str_replace("\336", "&#1095;", $string);
         $string = str_replace("\337", "&#1098;", $string);
         $string = str_replace("\340", "&#1070;", $string);
         $string = str_replace("\341", "&#1040;", $string);
         $string = str_replace("\342", "&#1041;", $string);
         $string = str_replace("\343", "&#1062;", $string);
         $string = str_replace("\344", "&#1044;", $string);
         $string = str_replace("\345", "&#1045;", $string);
         $string = str_replace("\346", "&#1060;", $string);
         $string = str_replace("\347", "&#1043;", $string);
         $string = str_replace("\350", "&#1061;", $string);
         $string = str_replace("\351", "&#1048;", $string);
         $string = str_replace("\352", "&#1049;", $string);
         $string = str_replace("\353", "&#1050;", $string);
         $string = str_replace("\354", "&#1051;", $string);
         $string = str_replace("\355", "&#1052;", $string);
         $string = str_replace("\356", "&#1053;", $string);
         $string = str_replace("\357", "&#1054;", $string);
         $string = str_replace("\360", "&#1055;", $string);
         $string = str_replace("\361", "&#1071;", $string);
         $string = str_replace("\362", "&#1056;", $string);
         $string = str_replace("\363", "&#1057;", $string);
         $string = str_replace("\364", "&#1058;", $string);
         $string = str_replace("\365", "&#1059;", $string);
         $string = str_replace("\366", "&#1046;", $string);
         $string = str_replace("\367", "&#1042;", $string);
         $string = str_replace("\370", "&#1068;", $string);
         $string = str_replace("\371", "&#1067;", $string);
         $string = str_replace("\372", "&#1047;", $string);
         $string = str_replace("\373", "&#1064;", $string);
         $string = str_replace("\374", "&#1069;", $string);
         $string = str_replace("\375", "&#1065;", $string);
         $string = str_replace("\376", "&#1063;", $string);
         $string = str_replace("\377", "&#1066;", $string);

         return $string;
      }
   }

?>
