<?
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME messages.
    **/


   function decodeMime($body, $bound, $type0, $type1) {
//      echo "$type0/$type1<BR>";
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

               while (substr(trim($body[$j]), 0, strlen($bound)) != $bound) {
                  $entity_body[$p] = $body[$j];
                  $j++;
                  $p++;
               }
               fetchEntityHeader($imapConnection, $entity_body, $ent_type0, $ent_type1, $ent_bound, &$encoding, &$charset);
               $entity = getEntity($entity_body, $ent_bound, $ent_type0, $ent_type1, $encoding, $charset);

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
   function getEntity($body, $bound, $type0, $type1, $encoding, $charset) {
//      echo "--$type0/$type1--<BR>";
      $msg[0]["TYPE0"] = $type0;
      $msg[0]["TYPE1"] = $type1;
      $msg[0]["ENCODING"] = $encoding;
      $msg[0]["CHARSET"] = $charset;

      if ($type0 == "text") {
         // error correcting if they didn't follow RFC standards
         if (trim($type1) == "")
            $type1 = "plain";

         if ($type1 == "plain") {
            $msg[0]["PRIORITY"] = 10;
            for ($p = 0;$p < count($body);$p++) {
               $msg[0]["BODY"][$p] = parsePlainTextMessage($body[$p]);
            }
         } else if ($type1 == "html") {
            $msg[0]["PRIORITY"] = 20;
            $msg[0]["BODY"] = $body;
         } else {
            $msg[0]["PRIORITY"] = 1;
            $msg[0]["BODY"][0] = "This entity is of an unknown format.  Doing my best to display anyway...<BR><BR>";
            for ($p = 1;$p < count($body);$p++) {
               $q = $p - 1;
               $msg[0]["BODY"][$p] = $body[$q];
            }
         }
      } else {
         $msg[0]["BODY"][0] = "<B><FONT COLOR=DD0000>This attachment is of an unknown format:  $type0/$type1</FONT></B>";
      }

      return $msg;
   }

   function formatBody($message) {
      for ($i=0; $i < count($message["ENTITIES"]); $i++) {
         if ($message["ENTITIES"][$i]["TYPE0"] == "text") {
            if ($message["ENTITIES"][$i]["PRIORITY"] > $priority)
               $priority = $message["ENTITIES"][$i]["PRIORITY"];
         }
      }

      for ($i = 0; $i < count($message["ENTITIES"]); $i++) {
         switch ($priority) {
            /** HTML **/
            case 20: for ($i=0; $i < count($message["ENTITIES"]); $i++) {
                        if (($message["ENTITIES"][$i]["TYPE0"] == "text") && ($message["ENTITIES"][$i]["TYPE1"] == "html")) {
                           $body = decodeBody($message["ENTITIES"][$i]["BODY"], $message["ENTITIES"][$i]["ENCODING"]);
                        }
                     }
                     break;
            /** PLAIN **/
            case 10: for ($i=0; $i < count($message["ENTITIES"]); $i++) {
                        if (($message["ENTITIES"][$i]["TYPE0"] == "text") && ($message["ENTITIES"][$i]["TYPE1"] == "plain")) {
                           $body = decodeBody($message["ENTITIES"][$i]["BODY"], $message["ENTITIES"][$i]["ENCODING"]);
                        }
                     }
                     break;
            /** UNKNOWN...SEND WHAT WE GOT **/
            case 1:  for ($i=0; $i < count($message["ENTITIES"]); $i++) {
                        if (($message["ENTITIES"][$i]["TYPE0"] == "text")) {
                           $pos = count($body);
                           for ($b=0; $b < count($message["ENTITIES"][$i]["BODY"]); $b++) {
                              $pos = $pos + $b;
                              $body[$pos] = $message["ENTITIES"][$i]["BODY"][$b];
                           }
                        }
                     }
                     break;
         }
      }

      for ($i = 0; $i < count($message["ENTITIES"]); $i++) {
         $pos = count($body);
         if ($message["ENTITIES"][$i]["TYPE0"] != "text") {
            $body[$pos] = "<BR><TT><U><B>ATTACHMENTS:</B></U></TT><BR>";
         }
      }

      for ($i = 0; $i < count($message["ENTITIES"]); $i++) {
         $pos = count($body);
         if ($message["ENTITIES"][$i]["TYPE0"] != "text") {
            if ($message["ENTITIES"][$i]["TYPE0"] == "image") {
               $body[$pos] = "<TT>&nbsp;&nbsp;&nbsp;Image: " . strtoupper($message["ENTITIES"][$i]["TYPE1"]) . "</TT><BR>";
            } else {
               $body[$pos] = "<TT>&nbsp;&nbsp;&nbsp;Unknown Type: " . $message["ENTITIES"][$i]["TYPE0"] . "/" . $message["ENTITIES"][$i]["TYPE1"] . "</TT><BR>";
            }
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