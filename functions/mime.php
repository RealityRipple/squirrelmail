<?
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME messages.
    **/


   /** This is the first function called.  It decides if this is a multipart
       message or if it should be handled as a single entity
    **/
   function decodeMime($body, $bound, $type0, $type1, &$entities) {
      if ($type0 == "multipart") {
         $bound = trim($bound);
         $i = 0;
         while (($i < count($body)) && (substr($body[$i], 0, strlen("--$bound--")) != "--$bound--")) {
            if (trim($body[$i]) == "--$bound") {
               $j = $i+1;
               $p = 0;

               /** Lets find the header for this entity **/
               /** If the first line after the boundary is blank, we use default values **/
               if (trim($body[$j]) == "") {
                  $ent_type0 = "text";
                  $ent_type1 = "plain";
                  $charset = "us-ascii";
                  $j++;
               /** If the first line ISNT blank, read in the header for this entity **/
               } else {
                  while ((substr(trim($body[$j]), 0, strlen("--$bound")) != "--$bound") && (trim($body[$j]) != "")) {
                     $entity_header[$p] = $body[$j];
                     $j++;
                     $p++;
                  }
                  /** All of these values are getting passed back to us **/
                  sqimap_get_entity_header($imapConnection, $entity_header, $ent_type0, $ent_type1, $ent_bound, $encoding, $charset, $filename);
               }


               /** OK, we have the header information, now lets decide what to do with it **/
               if ($ent_type0 == "multipart") {
                  $y = 0;
                  while (substr($body[$j], 0, strlen("--$bound--")) != "--$bound--") {
                     $ent_body[$y] = $body[$j];
                     $y++;
                     $j++;
                  }
                  $ent = decodeMime($ent_body, $ent_bound, $ent_type0, $ent_type1, $entities);
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

   /** This returns a parsed string called $body.  That string can then be displayed
       as the actual message in the HTML.   It contains everything needed, including
       HTML Tags, Attachments at the bottom, etc.
    **/
   function formatBody($message, $color, $wrap_at) {

      /** this if statement checks for the entity to show as the primary message.  To
          add more of them, just put them in the order that is their priority.
       **/
      $id = $message["INFO"]["ID"];
      $urlmailbox = urlencode($message["INFO"]["MAILBOX"]);

      if (containsType($message, "text", "html", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
      } else if (containsType($message, "text", "plain", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
      }
      // add other primary displaying message types here
      else {
         // find any type that's displayable
         if (containsType($message, "text", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         } else if (containsType($message, "message", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         }
      }

      /** If there are other types that shouldn't be formatted, add them here **/
      if ($message["ENTITIES"][$ent_num]["TYPE1"] != "html")
         $body = translateText($body, $wrap_at);


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

      if ($encoding == "us-ascii") {
         $newbody = $body; // if only they all were this easy

      } else if ($encoding == "quoted-printable") {
         $body_ary = explode("\n", $body);

         for ($q=0; $q < count($body_ary); $q++) {
            if (substr(trim($body_ary[$q]), -1) == "=") {
               $body_ary[$q] = trim($body_ary[$q]);
               $body_ary[$q] = substr($body_ary[$q], 0, strlen($body_ary[$q])-1);
            } else if (substr(trim($body_ary[$q]), -3) == "=20") {
               $body_ary[$q] = trim($body_ary[$q]);
               $body_ary[$q] = substr($body_ary[$q], 0, strlen($body_ary[$q])-3);
               $body_ary[$q] = "$body_ary[$q]\n";
            }
         }

         for ($q=0;$q < count($body_ary);$q++) {
            $body_ary[$q] = ereg_replace("=3D", "=", $body_ary[$q]);
         }

         $body = "";
         for ($i = 0; $i < count($body_ary); $i++) {
            $body .= "$body_ary[$i]\n";
         }

         $newbody = $body;
      } else if ($encoding == "base64") {
         $newbody = base64_decode($body);

      } else {
         $newbody = $body;
      }
      return $newbody;
   }


   // This functions decode strings that is encoded according to 
   // RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
   function rfc1522Decode ($string) {
      // Recognizing only US-ASCII and ISO-8859. Other charsets should
      // probably be recognized as well.
      if (eregi('=\?(us-ascii|iso-8859-([0-9])+)\?(q|b)\?([^?]+)\?=', 
                $string, $res)) {
         if (ucfirst($res[3]) == "B") {
            $replace = base64_decode($res[4]);
         } else {
            $replace = ereg_replace("_", " ", $res[4]);
            $replace = quoted_printable_decode($replace);
         }

         // Only US-ASCII and ISO-8859-1 can be displayed without further ado
         if ($res[2] != "" && $res[2] != "1") {
            // This get rid of all characters with over 0x9F
            $replace = strtr($replace, "\240\241\242\243\244\245\246\247".
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
                             "????????");
         }

         $string = eregi_replace
            ('=\?(us-ascii|iso-8859-([0-9])+)\?(q|b)\?([^?]+)\?=',
             $replace, $string);

         return (rfc1522Decode($string));
      } else         
         return ($string);
   }

?>
