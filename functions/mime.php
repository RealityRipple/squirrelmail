<?
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME
    ** messages.
    **
    **/

   $mime_php = true;

   if (!isset($i18n_php))
      include "../functions/i18n.php";

   /** This is the first function called.  It decides if this is a multipart
       message or if it should be handled as a single entity
    **/
   function decodeMime($body, $bound, $type0, $type1, $encoding, $charset, &$entities) {
      if ($type0 == "multipart") {
         $bound = trim($bound);
         $i = 0;
         while (($i < count($body)) && (substr($body[$i], 0, strlen("--$bound--")) != "--$bound--")) {
            if (trim($body[$i]) == "--$bound") {
               $j = $i+1;
               $p = 0;

               /** Lets find the header for this entity **/
               /** If the first line after the boundary is blank, we
                   use default values **/
               if (trim($body[$j]) == "") {
                  $ent_type0 = "text";
                  $ent_type1 = "plain";
                  $charset = "us-ascii";
                  $j++;
               /** If the first line ISNT blank, read in the header
                   for this entity **/
               } else {
                  while ((substr(trim($body[$j]), 0, strlen("--$bound")) != "--$bound") && (trim($body[$j]) != "")) {
                     $entity_header[$p] = $body[$j];
                     $j++;
                     $p++;
                  }
                  /** All of these values are getting passed back to us **/
                  sqimap_get_entity_header($imapConnection, $entity_header, $ent_type0, $ent_type1, $ent_bound, $encoding, $charset, $filename);
               }


               /** OK, we have the header information, now lets decide
                   what to do with it **/
               if ($ent_type0 == "multipart") {
                  $y = 0;
                  while (substr($body[$j], 0, strlen("--$bound--")) != "--$bound--") {
                     $ent_body[$y] = $body[$j];
                     $y++;
                     $j++;
                  }
                  $ent = decodeMime($ent_body, $ent_bound, $ent_type0, $ent_type1, $charset, $entities);
                  $entities = $ent;
               } else {
                  $j++;
                  $entity_body = "";
                  while (substr(trim($body[$j]), 0, strlen("--$bound")) != "--$bound") {
                     $entity_body .= $body[$j];
                     $j++;
                  }
                  $count = count($entities);
                  $entities[$count] = getEntity($entity_body, $ent_bound, $ent_type0, $ent_type1, $encoding, $charset, $filename);
               }
            }
            $i++;
         }
      } else {
         /** If this isn't a multipart message **/
         $j = 0;
         $entity_body = "";
         while ($j < count($body)) {
            $entity_body .= $body[$j];
            $j++;
         }

         $count = count($entities);
         $entities[$count] = getEntity($entity_body, $bound, $type0, $type1, $encoding, $charset, $filename);
      }

      return $entities;
   }

   /** This gets one entity's properties **/
   function getEntity($body, $bound, $type0, $type1, $encoding, $charset, $filename) {
      $msg["TYPE0"] = $type0;
      $msg["TYPE1"] = $type1;
      $msg["ENCODING"] = $encoding;
      $msg["CHARSET"] = $charset;
      $msg["FILENAME"] = $filename;

      $msg["BODY"] = $body;

      return $msg;
   }

   /** This will check whether or not the message contains a certain type.  It
       searches through all the entities for a match.
    **/
   function containsType($message, $type0, $type1, &$ent_num) {
      $type0 = strtolower($type0);
      $type1 = strtolower($type1);
      for ($i = 0; $i < count($message["ENTITIES"]); $i++) {
         /** Check only on type0 **/
         if ( $type1 == "any_type" ) {
            if ( ($message["ENTITIES"][$i]["TYPE0"] == $type0) ) {
               $ent_num = $i;
               return true;
            }

         /** Check on type0 and type1 **/
         } else {
            if ( ($message["ENTITIES"][$i]["TYPE0"] == $type0) && ($message["ENTITIES"][$i]["TYPE1"] == $type1) ) {
               $ent_num = $i;
               return true;
            }
         }
      }
      return false;
   }

   /** This returns a parsed string called $body. That string can then
       be displayed as the actual message in the HTML. It contains
       everything needed, including HTML Tags, Attachments at the
       bottom, etc.
    **/
   function formatBody($message, $color, $wrap_at) {

      /** this if statement checks for the entity to show as the
          primary message. To add more of them, just put them in the
          order that is their priority.
       **/
      $id = $message["INFO"]["ID"];
      $urlmailbox = urlencode($message["INFO"]["MAILBOX"]);

      if (containsType($message, "text", "html", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         $charset = $message["ENTITIES"][$ent_num]["CHARSET"];
      } else if (containsType($message, "text", "plain", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         $charset = $message["ENTITIES"][$ent_num]["CHARSET"];
      }
      // add other primary displaying message types here
      else {
         // find any type that's displayable
         if (containsType($message, "text", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
            $charset = $message["ENTITIES"][$ent_num]["CHARSET"];
         } else if (containsType($message, "message", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
            $charset = $message["ENTITIES"][$ent_num]["CHARSET"];
         }
      }

      /** If there are other types that shouldn't be formatted, add
          them here **/
      if ($message["ENTITIES"][$ent_num]["TYPE1"] != "html")
         $body = translateText($body, $wrap_at, $charset);


      $body .= "<BR><SMALL><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$ent_num&mailbox=$urlmailbox\">". _("Download this as a file") ."</A></CENTER><BR></SMALL>";

      /** Display the ATTACHMENTS: message if there's more than one part **/
      if (count($message["ENTITIES"]) > 1) {
         $body .= "<TABLE WIDTH=100% CELLSPACING=0 CELLPADDING=4 BORDER=0><TR><TD BGCOLOR=\"$color[0]\">";
         $body .= "<TT><B>ATTACHMENTS:</B></TT>";
         $body .= "</TD></TR><TR><TD BGCOLOR=\"$color[0]\">";
         $num = 0;

         for ($i = 0; $i < count($message["ENTITIES"]); $i++) {
            /** If we've displayed this entity, go to the next one **/
            if ($ent_num == $i)
               continue;

            $type0 = strtolower($message["ENTITIES"][$i]["TYPE0"]);
            $type1 = strtolower($message["ENTITIES"][$i]["TYPE1"]);

            $num++;
            $filename = $message["ENTITIES"][$i]["FILENAME"];
            if (trim($filename) == "") {
               $display_filename = "untitled$i";
            } else {
               $display_filename = $filename;
            }

            $urlMailbox = urlencode($message["INFO"]["MAILBOX"]);
            $id = $message["INFO"]["ID"];
            $body .= "<TT>&nbsp;&nbsp;&nbsp;<A HREF=\"../src/download.php?passed_id=$id&mailbox=$urlMailbox&passed_ent_id=$i\">" . $display_filename . "</A>&nbsp;&nbsp;<SMALL>(TYPE: $type0/$type1)</SMALL></TT><BR>";
         }
         $body .= "</TD></TR></TABLE>";
      }
      return $body;
   }



   /** this function decodes the body depending on the encoding type. **/
   function decodeBody($body, $encoding) {
      $encoding = strtolower($encoding);

      if ($encoding == "quoted-printable") {
         $body = quoted_printable_decode($body);

         while (ereg("=\n", $body))
            $body = ereg_replace ("=\n", "", $body);
      } else if ($encoding == "base64") {
         $body = base64_decode($body);
      }

      // All other encodings are returned raw.
      return $body;
   }


   // This functions decode strings that is encoded according to 
   // RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
   function decodeHeader ($string) {
      if (eregi('=\?([^?]+)\?(q|b)\?([^?]+)\?=', 
                $string, $res)) {
         if (ucfirst($res[2]) == "B") {
            $replace = base64_decode($res[3]);
         } else {
            $replace = ereg_replace("_", " ", $res[3]);
            $replace = quoted_printable_decode($replace);
         }

         $replace = charset_decode ($res[1], $replace);

         $string = eregi_replace
            ('=\?([^?]+)\?(q|b)\?([^?]+)\?=',
             $replace, $string);
         // In case there should be more encoding in the string: recurse
         return (decodeHeader($string));
      } else         
         return ($string);
   }

   // Encode a string according to RFC 1522 for use in headers if it
   // contains 8-bit characters
   function encodeHeader ($string) {
      global $default_charset;

      // Encode only if the string contains 8-bit characters
      if (ereg("[\200-\377]", $string)) {
         $newstring = "=?$default_charset?Q?";
         $newstring .= str_replace(" ", "_", $string);
         
         while (ereg("([\200-\377])", $newstring, $regs)) {
            $replace = $regs[1];
            $insert = "=" . bin2hex($replace);
            $newstring = str_replace($replace, $insert, $newstring);
         }

         $newstring .= "?=";

         return $newstring;
      }

      return $string;
   }

?>
