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

               while (substr(trim($body[$j]), 0, strlen($bound)) != $bound) {
                  $entity[$p] = $body[$j];
                  $j++;
                  $p++;
               }
               fetchEntityHeader($imapConnection, $entity, $ent_type0, $ent_type1, $ent_bound);
               $entity = decodeMime($entity, $ent_bound, $ent_type0, $ent_type1);

               $q = count($full_message);
               $full_message[$q] = $entity;
            }
            $i++;
         }
      } else if ($type0 == "text") {
         $entity_msg["TYPE0"] = "text";
         if ($type1 == "plain") {
            $entity_msg["TYPE1"] = "plain";
            for ($p = 0;$p < count($body);$p++) {
               $entity_msg["BODY"][$p] = parsePlainTextMessage($body[$p]);
            }

         } else if ($type1 == "html") {
            $entity_msg["TYPE1"] = "html";
            $entity_msg["BODY"] = $body;
         }
         $full_message[0] = $entity_msg;
      }

      return $full_message;
   }
?>