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
                  fetchEntityHeader($imapConnection, $entity_header, $ent_type0, $ent_type1, $ent_bound, $encoding, $charset, $filename);
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
         while ((substr(trim($body[$j]), 0, strlen("--$bound")) != "--$bound") && ($j < count($body))) {
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
   function formatBody($message) {
      include ("../config/config.php");

      /** this if statement checks for the entity to show as the primary message.  To
          add more of them, just put them in the order that is their priority.
       **/
      if (containsType($message, "text", "html", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
      } else if (containsType($message, "text", "plain", $ent_num)) {
         $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
         $body = "<TT>" . nl2br($body) . "</TT>";
      }
      // add other primary displaying message types here
      else {
         // find any type that's displayable
         if (containsType($message, "text", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
            $body = "<TT>" . nl2br($body) . "</TT>";
         } else if (containsType($message, "message", "any_type", $ent_num)) {
            $body = decodeBody($message["ENTITIES"][$ent_num]["BODY"], $message["ENTITIES"][$ent_num]["ENCODING"]);
            $body = "<TT>" . nl2br($body) . "</TT>";
         }
      }

      $body .= "<BR>";

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
               $filename = "UNKNOWN_FORMAT_" . time() . $i;
               $display_filename = "Attachment $i";
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
         echo "$body";
         $body = ereg_replace("=3D", "=", $body);
         $body = ereg_replace("=\n", "", $body);
         $body = ereg_replace("=20", "\n", $body);
         $newbody= $body;

      } else if ($encoding == "base64") {
         $newbody = base64_decode($body);

      } else {
         $newbody = $body;
      }
      return $newbody;
   }
?>