<?
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME messages.
    **/


   function decodeMime($body, $bound, $type0, $type1) {
      if ($type0 == "multipart") {
         if ($body[0] == "")
            $i = 1;
         else
            $i = 0;

         $bound = trim($bound);
         $bound = "--$bound";
         while ($i < count($body)) {
            if (trim($body[$i]) == $bound) {
               $j = $i + 1;
               $p = 0;

               while ((substr(trim($body[$j]), 0, strlen($bound)) != $bound) && (trim($body[$j]) != "")) {
                  $entity_header[$p] = $body[$j];
                  $j++;
                  $p++;
               }

               fetchEntityHeader($imapConnection, $entity_header, $ent_type0, $ent_type1, $ent_bound, $encoding, $charset, $filename);

               if ($ent_type0 == "text") {
                  while (substr(trim($body[$j]), 0, strlen($bound)) != $bound) {
                     $entity_body[$p] = $body[$j];
                     $j++;
                     $p++;
                  }
               } else {
                  $j++;
                  $entity_body = "";
                  while (substr(trim($body[$j]), 0, strlen($bound)) != $bound) {
                     $entity_body .= $body[$j];
                     $j++;
                  }
               }
               $entity = getEntity($entity_body, $ent_bound, $ent_type0, $ent_type1, $encoding, $charset, $filename);

               $q = count($full_message);
               $full_message[$q] = $entity[0];
            }
            $i++;
         }
      } else {
         $full_message = getEntity($body, $bound, $type0, $type1);
      }

      return $full_message;
   }

   /** This gets one entity's properties **/
   function getEntity($body, $bound, $type0, $type1, $encoding, $charset, $filename) {
      $msg[0]["TYPE0"] = $type0;
      $msg[0]["TYPE1"] = $type1;
      $msg[0]["ENCODING"] = $encoding;
      $msg[0]["CHARSET"] = $charset;
      $msg[0]["FILENAME"] = $filename;

      if ($type0 == "text") {
         // error correcting if they didn't follow RFC standards
         if (trim($type1) == "")
            $type1 = "plain";

         if ($type1 == "plain") {
            for ($p = 0;$p < count($body);$p++) {
               $msg[0]["BODY"][$p] = parsePlainTextMessage($body[$p]);
            }
         } else {
            $msg[0]["BODY"] = $body;
         }
      } else {
         $msg[0]["BODY"][0] = $body;
      }

      return $msg;
   }

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

   function formatBody($message) {
      if (containsType($message, "text", "html", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
      } else if (containsType($message, "text", "plain", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
      } // add other primary displaying message types here

      else {
         // find any type that's displayable
         if (containsType($message, "text", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         } else if (containsType($message, "message", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         }
      }


      /** Display the ATTACHMENTS: message if there's more than one part **/
      if (count($message["ENTITIES"]) > 1) {
         $pos = count($body);
         $body[$pos] .= "<BR><TT><U><B>ATTACHMENTS:</B></U></TT><BR>";
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
               $filename = "UNKNOWN_FORMAT_" . time() . $i;
               $display_filename = "Attachment $i";
            } else {
               $display_filename = $filename;
            }

            $body[$pos] .= "<TT>&nbsp;&nbsp;&nbsp;<A HREF=\"../data/$filename\">" . $display_filename . "</A>&nbsp;&nbsp;<SMALL>(TYPE: $type0/$type1)</SMALL></TT><BR>";
            $file = fopen("../data/$filename", "w");

            /** Determine what encoding type is used **/
            if ($message["ENTITIES"][$i]["ENCODING"] == "base64") {
               $thefile = base64_decode($message["ENTITIES"][$i]["BODY"][0]);
            } else {
               $thefile = $message["ENTITIES"][$i]["BODY"][0];
            }

            fwrite($file, $thefile);
            fclose($file);
         }
      }

      return $body;
   }

   function decodeBody($body, $encoding) {
      $encoding = strtolower($encoding);
      if ($encoding == "us-ascii") {
         $newbody = $body; // if only they all were this easy
      } else if ($encoding == "quoted-printable") {
         for ($q=0; $q < count($body); $q++) {
            if (substr(trim($body[$q]), -1) == "=") {
               $body[$q] = trim($body[$q]);
               $body[$q] = substr($body[$q], 0, strlen($body[$q])-1);
            } else if (substr(trim($body[$q]), -3) == "=20") {
               $body[$q] = trim($body[$q]);
               $body[$q] = substr($body[$q], 0, strlen($body[$q])-3);
               $body[$q] = "$body[$q]\n"; // maybe should be \n.. dunno
            }
         }
         for ($q=0;$q < count($body);$q++) {
            $body[$q] = ereg_replace("=3D", "=", $body[$q]);
         }
         $newbody = $body;
      } else {
         $newbody = $body;
      }
      return $newbody;
   }
?>