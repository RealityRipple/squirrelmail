<?
   /**
    **  imap_messages.php
    **
    **  This implements functions that manipulate messages 
    **/

   /******************************************************************************
    **  Copies specified messages to specified folder
    ******************************************************************************/
   function sqimap_messages_copy ($imap_stream, $start, $end, $mailbox) {
      fputs ($imap_stream, "a001 COPY $start:$end \"$mailbox\"\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
   }

   /******************************************************************************
    **  Deletes specified messages and moves them to trash if possible
    ******************************************************************************/
   function sqimap_messages_delete ($imap_stream, $start, $end, $mailbox) {
      global $move_to_trash, $trash_folder, $auto_expunge;

      if (($move_to_trash == true) && (sqimap_mailbox_exists($imap_stream, $trash_folder))) {
         sqimap_messages_copy ($imap_stream, $start, $end, $trash_folder);
         sqimap_messages_flag ($imap_stream, $start, $end, "Deleted");
      } else {
         sqimap_messages_flag ($imap_stream, $start, $end, "Deleted");
      }
   }

   /******************************************************************************
    **  Sets the specified messages with specified flag
    ******************************************************************************/
   function sqimap_messages_flag ($imap_stream, $start, $end, $flag) {
      fputs ($imap_stream, "a001 STORE $start:$end +FLAGS (\\$flag)\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
   }

   /******************************************************************************
    **  Returns some general header information -- FROM, DATE, and SUBJECT
    ******************************************************************************/
   function sqimap_get_small_header ($imap_stream, $id, &$from, &$subject, &$date) {
      fputs ($imap_stream, "a001 FETCH $id:$id BODY[HEADER.FIELDS (From Subject Date)]\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);

      for ($i = 0; $i < count($read); $i++) {
         if (strtolower(substr($read[$i], 0, 5)) == "from:") {
            $from = sqimap_find_displayable_name(substr($read[$i], 5));
         } else if (strtolower(substr($read[$i], 0, 5)) == "date:") {
            $date = substr($read[$i], 5);
         } else if (strtolower(substr($read[$i], 0, 8)) == "subject:") {
            $subject = htmlspecialchars(substr($read[$i], 8));
            if (strlen(trim($subject)) == 0)
               $subject = _("(no subject)");
         }
      }
   }

   /******************************************************************************
    **  Returns the flags for the specified messages 
    ******************************************************************************/
   function sqimap_get_flags ($imap_stream, $start, $end) {
      fputs ($imap_stream, "a001 FETCH $start:$end FLAGS\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
      $i = 0;
      while ($i < count($read)) {
         if (strpos($read[$i], "FLAGS")) {
            $tmp = ereg_replace("\(", "", $read[$i]);
            $tmp = ereg_replace("\)", "", $tmp);
            $tmp = str_replace("\\", "", $tmp);
            $tmp = substr($tmp, strpos($tmp, "FLAGS")+6, strlen($tmp));
            $tmp = trim($tmp);
            $flags[$i] = explode(" ", $tmp);
         } else {
            $flags[$i][0] = "None";
         }
         $i++;
      }
      return $flags;
   }

   /******************************************************************************
    **  Returns a message array with all the information about a message.  See
    **  the documentation folder for more information about this array.
    ******************************************************************************/
   function sqimap_get_message ($imap_stream, $id, $mailbox) {
      $message["INFO"]["ID"] = $id;
      $message["INFO"]["MAILBOX"] = $mailbox;
      $message["HEADER"] = sqimap_get_message_header($imap_stream, $id);
      $message["ENTITIES"] = sqimap_get_message_body($imap_stream, $message["HEADER"]["BOUNDARY"], $id, $message["HEADER"]["TYPE0"], $message["HEADER"]["TYPE1"], $message["HEADER"]["ENCODING"]);
      return $message;
   }

   /******************************************************************************
    **  Wrapper function that reformats the header information.
    ******************************************************************************/
   function sqimap_get_message_header ($imap_stream, $id) {
      fputs ($imap_stream, "a001 FETCH $id:$id BODY[HEADER]\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
     
      return sqimap_get_header($imap_stream, $read); 
   }

   /******************************************************************************
    **  Wrapper function that returns entity headers for use by decodeMime
    ******************************************************************************/
   function sqimap_get_entity_header ($imap_stream, &$read, &$type0, &$type1, &$bound, &$encoding, &$charset, &$filename) {
      $header = sqimap_get_header($imap_stream, $read);
      $type0 = $header["TYPE0"]; 
      $type1 = $header["TYPE1"];
      $bound = $header["BOUNDARY"];
      $encoding = $header["ENCODING"];
      $charset = $header["CHARSET"];
      $filename = $header["FILENAME"];
   }

   /******************************************************************************
    **  Queries the IMAP server and gets all header information.
    ******************************************************************************/
   function sqimap_get_header ($imap_stream, $read) {
      $i = 0;
      while ($i < count($read)) {
         if (substr($read[$i], 0, 17) == "MIME-Version: 1.0") {
            $header["MIME"] = true;
            $i++;
         }

         /** ENCODING TYPE **/
         else if (substr(strtolower($read[$i]), 0, 26) == "content-transfer-encoding:") {
            $header["ENCODING"] = strtolower(trim(substr($read[$i], 26)));
            $i++;
         }

         /** CONTENT-TYPE **/
         else if (substr($read[$i], 0, 13) == "Content-Type:") {
            $cont = strtolower(trim(substr($read[$i], 13)));
            if (strpos($cont, ";"))
               $cont = substr($cont, 0, strpos($cont, ";"));

            if (strpos($cont, "/")) {
               $header["TYPE0"] = substr($cont, 0, strpos($cont, "/"));
               $header["TYPE1"] = substr($cont, strpos($cont, "/")+1);
            } else {
               $header["TYPE0"] = $cont;
            }

            $line = $read[$i];
            $i++;
            while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
               str_replace("\n", "", $line);
               str_replace("\n", "", $read[$i]);
               $line = "$line $read[$i]";
               $i++;
            }

            /** Detect the boundary of a multipart message **/
            if (strpos(strtolower(trim($line)), "boundary=")) {
               $pos = strpos($line, "boundary=") + 9;
               $bound = trim($line);
               if (strpos($line, " ", $pos) > 0) {
                  $bound = substr($bound, $pos, strpos($line, " ", $pos));
               } else {
                  $bound = substr($bound, $pos);
               }
               $bound = str_replace("\"", "", $bound);
               $header["BOUNDARY"] = $bound;
            }

            /** Detect the charset **/
            if (strpos(strtolower(trim($line)), "charset=")) {
               $pos = strpos($line, "charset=") + 8;
               $charset = trim($line);
               if (strpos($line, " ", $pos) > 0) {
                  $charset = substr($charset, $pos, strpos($line, " ", $pos));
               } else {
                  $charset = substr($charset, $pos);
               }
               $charset = str_replace("\"", "", $charset);
               $header["CHARSET"] = $charset;
            } else {
               $header["CHARSET"] = "us-ascii";
            }

            /** Detects filename if any **/
            if (strpos(strtolower(trim($line)), "name=")) {
               $pos = strpos($line, "name=") + 5;
               $name = trim($line);
               if (strpos($line, " ", $pos) > 0) {
                  $name = substr($name, $pos, strpos($line, " ", $pos));
               } else {
                  $name = substr($name, $pos);
               }
               $name = str_replace("\"", "", $name);
               $header["FILENAME"] = $name;
            }
         }

         /** REPLY-TO **/
         else if (strtolower(substr($read[$i], 0, 9)) == "reply-to:") {
            $header["REPLYTO"] = trim(substr($read[$i], 9, strlen($read[$i])));
            $i++;
         }

         /** FROM **/
         else if (strtolower(substr($read[$i], 0, 5)) == "from:") {
            $header["FROM"] = trim(substr($read[$i], 5, strlen($read[$i]) - 6));
            if ($header["REPLYTO"] == "")
               $header["REPLYTO"] = $header["FROM"];
            $i++;
         }
         /** DATE **/
         else if (strtolower(substr($read[$i], 0, 5)) == "date:") {
            $d = substr($read[$i], 5);
            $d = trim($d);
            $d = ereg_replace("  ", " ", $d);
            $d = explode(" ", $d);
            $header["DATE"] = getTimeStamp($d);
            $i++;
         }
         /** SUBJECT **/
         else if (strtolower(substr($read[$i], 0, 8)) == "subject:") {
            $header["SUBJECT"] = trim(substr($read[$i], 8, strlen($read[$i]) - 9));
            if (strlen(Chop($header["SUBJECT"])) == 0)
               $header["SUBJECT"] = _("(no subject)");
            $i++;
         }
         /** CC **/
         else if (strtolower(substr($read[$i], 0, 3)) == "cc:") {
            $pos = 0;
            $header["CC"][$pos] = trim(substr($read[$i], 4));
            $i++;
            while ((substr($read[$i], 0, 1) == " ") && (trim($read[$i]) != "")) {
               $pos++;
               $header["CC"][$pos] = trim($read[$i]);
               $i++;
            }
         }
         /** TO **/
         else if (strtolower(substr($read[$i], 0, 3)) == "to:") {
            $pos = 0;
            $header["TO"][$pos] = trim(substr($read[$i], 4));
            $i++;
            while ((substr($read[$i], 0, 1) == " ")  && (trim($read[$i]) != "")){
               $pos++;
               $header["TO"][$pos] = trim($read[$i]);
               $i++;
            }
         }

         /** ERROR CORRECTION **/
         else if (substr($read[$i], 0, 1) == ")") {
            if ($header["SUBJECT"] == "")
                $header["SUBJECT"] = _("(no subject)");

            if ($header["FROM"] == "")
                $header["FROM"] = _("(unknown sender)");

            if ($header["DATE"] == "")
                $header["DATE"] = time();
            $i++;
         }
         else {
            $i++;
         }
      }
      return $header;
   }


   /******************************************************************************
    **  Returns the body of a message.
    ******************************************************************************/
   function sqimap_get_message_body ($imap_stream, $bound, $id, $type0, $type1, $encoding) {
      fputs ($imap_stream, "a001 FETCH $id:$id BODY[TEXT]\n");
      $read = sqimap_read_data ($imap_stream, "a001", true, $response, $message);
       
      $i = 0;
      $j = 0;
      while ($i < count($read)-1) {
         if ( ($i != 0) ) {
            $bodytmp[$j] = $read[$i];
            $j++;
         }
         $i++;
      }
      $body = $bodytmp;

      return decodeMime($body, $bound, $type0, $type1, $encoding);
   }
?>
