<?
   /**
    **  mailbox.php
    **
    **  This contains functions that request information about a mailbox.  Including
    **  reading and parsing headers, getting folder information, etc.
    **
    **/

   function selectMailbox($imapConnection, $mailbox, &$numberOfMessages) {
      // select mailbox
      fputs($imapConnection, "mailboxSelect SELECT \"$mailbox\"\n");
      $data = imapReadData($imapConnection, "mailboxSelect");
      for ($i = 0; $i < count($data); $i++) {
         if (substr(Chop($data[$i]), -6) == "EXISTS") {
            $array = explode(" ", $data[$i]);
            $numberOfMessages = $array[1];
         }
      }
   }

   function unseenMessages($imapConnection, &$numUnseen) {
      fputs($imapConnection, "1 SEARCH UNSEEN NOT DELETED\n");
      $read = fgets($imapConnection, 1024);
      $unseen = false;

      if (strlen($read) > 10) {
         $unseen = true;
         $ary = explode(" ", $read);
         $numUnseen = count($ary) - 2;
      }
      else {
         $unseen = false;
         $numUnseen = 0;
      }

      $read = fgets($imapConnection, 1024);
      return $unseen;
   }

   /**  This function sends a request to the IMAP server for headers, 50 at a time
    **  until $end is reached.  I originally had it do them all at one time, but found
    **  it slightly faster to do it this way.
    **
    **  Originally we had getMessageHeaders get the headers for one message at a time.
    **  Doing it in bunches gave us a speed increase from 9 seconds (for a box of 800
    **  messages) to about 3.5 seconds.
    **/
   function getMessageHeaders($imapConnection, $start, $end, &$from, &$subject, &$date) {
      $rel_start = $start;
      if (($start > $end) || ($start < 1)) {
         echo _("Error in message header fetching.  Start message: "). $start, _("End message: "). "$end<BR>";
         exit;
      }

      $pos = 0;
      while ($rel_start <= $end) {
         if ($end - $rel_start > 50) {
            $rel_end = $rel_start + 49;
         } else {
            $rel_end = $end;
         }
         fputs($imapConnection, "messageFetch FETCH $rel_start:$rel_end RFC822.HEADER.LINES (From Subject Date)\n");
         $read = fgets($imapConnection, 1024);

         while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {

            if (substr($read, 0, 5) == "From:") {
               $read = encodeEmailAddr("$read");
               $from[$pos] = substr($read, 5, strlen($read) - 6);
            }
            else if (substr($read, 0, 5) == "Date:") {
               $read = ereg_replace("<", "&lt;", $read);
               $read = ereg_replace(">", "&gt;", $read);
               $date[$pos] = substr($read, 5, strlen($read) - 6);
            }
            else if (substr($read, 0, 8) == "Subject:") {
               $read = ereg_replace("<", "&lt;", $read);
               $read = ereg_replace(">", "&gt;", $read);
               $subject[$pos] = substr($read, 8, strlen($read) - 9);
               if (strlen(Chop($subject[$pos])) == 0)
                  $subject[$pos] = "(no subject)";
            }
            else if (substr($read, 0, 1) == ")") {
               if ($subject[$pos] == "")
                  $subject[$pos] = "(no subject)";
               else if ($from[$pos] == "")
                  $from[$pos] = "(unknown sender)";
               else if ($date[$pos] == "")
                  $from[$pos] = gettimeofday();

               $pos++;
            }

            $read = fgets($imapConnection, 1024);
         }
         $rel_start = $rel_start + 50;
      }
   }

   function encodeEmailAddr($string) {
      $string = ereg_replace("<", "EMAILSTART--", $string);
      $string = ereg_replace(">", "--EMAILEND", $string);
      return $string;
   }

   function setMessageFlag($imapConnection, $i, $q, $flag) {
      fputs($imapConnection, "messageStore STORE $i:$q +FLAGS (\\$flag)\n");
   }

   /**  This function gets the flags for message $j.  It does only one message at a
    **  time, rather than doing groups of messages (like getMessageHeaders does).
    **  I found it one or two seconds quicker (on a box of 800 messages) to do it
    **  individually.  I'm not sure why it happens like that, but that's what my
    **  testing found.  Perhaps later I will be proven wrong and this will change.
    **/
   function getMessageFlags($imapConnection, $j, &$flags) {
      /**   * 2 FETCH (FLAGS (\Answered \Seen))   */
      fputs($imapConnection, "messageFetch FETCH $j:$j FLAGS\n");
      $read = fgets($imapConnection, 1024);
      $count = 0;
      while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
         if (strpos($read, "FLAGS")) {
            $read = ereg_replace("\(", "", $read);
            $read = ereg_replace("\)", "", $read);
            $read = substr($read, strpos($read, "FLAGS")+6, strlen($read));
            $read = trim($read);
            $flags = explode(" ", $read);;
            $s = 0;
            while ($s < count($flags)) {
               $flags[$s] = substr($flags[$s], 1, strlen($flags[$s]));
               $s++;
            }
         } else {
            $flags[0] = "None";
         }
         $count++;
         $read = fgets($imapConnection, 1024);
      }
   }

   function decodeEmailAddr($sender) {
      $emailAddr = getEmailAddr($sender);
      if (strpos($emailAddr, "EMAILSTART--")) {

         $emailAddr = ereg_replace("EMAILSTART--", "", $emailAddr);
         $emailAddr = ereg_replace("--EMAILEND", "", $emailAddr);
      } else {
         $emailAddr = $emailAddr;
      }
      return $emailAddr;
   }

   function getEmailAddr($sender) {
      if (strpos($sender, "EMAILSTART--") == false)
         return "$sender";

      $emailStart = strpos($sender, "EMAILSTART--") + 12;
      $emailAddr = substr($sender, $emailStart, strlen($sender));
      $emailAddr = substr($emailAddr, 0, strpos($emailAddr, "--EMAILEND"));

      return $emailAddr;
   }

   function getSender($sender) {
      if (strpos($sender, "EMAILSTART--") == false)
         return "$sender";

      $first = substr($sender, 0, strpos($sender, "EMAILSTART--"));
      $second = substr($sender, strpos($sender, "--EMAILEND") +10, strlen($sender));
      return "$first $second";
   }

   function getSenderName($sender) {
      $name = getSender($sender);
      $emailAddr = getEmailAddr($sender);
      $emailStart = strpos($emailAddr, "EMAILSTART--");
      $emailEnd = strpos($emailAddr, "--EMAILEND") - 10;

      if (($emailAddr == "") && ($name == "")) {
         $from = $sender;
      }
      else if ((strstr($name, "?") != false) || (strstr($name, "$") != false) || (strstr($name, "%") != false)){
         $emailAddr = ereg_replace("EMAILSTART--", "", $emailAddr);
         $emailAddr = ereg_replace("--EMAILEND", "", $emailAddr);
         $from = $emailAddr;
      }
      else if (strlen($name) > 0) {
         $from = $name;
      }
      else if (strlen($emailAddr > 0)) {
         $emailAddr = ereg_replace("EMAILSTART--", "", $emailAddr);
         $emailAddr = ereg_replace("--EMAILEND", "", $emailAddr);
         $from = $emailAddr;
      }

      $from = trim($from);

      // strip out any quotes if they exist
      if ((strlen($from) > 0) && ($from[0] == "\"") && ($from[strlen($from) - 1] == "\""))
         $from = substr($from, 1, strlen($from) - 2);

      return $from;
   }

   /** returns "true" if the copy was completed successfully.
    ** returns "false" with an error message if unsuccessful.
    **/
   function copyMessages($imapConnection, $from_id, $to_id, $folder) {
      fputs($imapConnection, "mailboxStore COPY $from_id:$to_id \"$folder\"\n");
      $read = fgets($imapConnection, 1024);
      while ((substr($read, 0, 15) != "mailboxStore OK") && (substr($read, 0, 15) != "mailboxStore NO")) {
         $read = fgets($imapConnection, 1024);
      }

      if (substr($read, 0, 15) == "mailboxStore NO") {
         return false;
      } else if (substr($read, 0, 15) == "mailboxStore OK") {
         return true;
      }

      echo "UNKNOWN ERROR copying messages $from_id to $to_id to folder $folder.<BR>";
      return false;
   }

   /** expunges a mailbox **/
   function expungeBox($imapConnection, $mailbox) {
      selectMailbox($imapConnection, $mailbox, $num);
      fputs($imapConnection, "1 EXPUNGE\n");
      imapReadData($imapConnection, "1", true, $response, $message);
   }

   function getFolderNameMinusINBOX($mailbox, $del) {
      $inbox = "INBOX" . $del;
      if (substr($mailbox, 0, strlen($inbox)) == $inbox)
         $box = substr($mailbox, strlen($inbox), strlen($mailbox));
      else
         $box = $mailbox;

      return $box;
   }

   /** This function gets all the information about a message.  Including Header and body **/
   function fetchMessage($imapConnection, $id, $mailbox) {
      $message["INFO"]["ID"] = $id;
      $message["INFO"]["MAILBOX"] = $mailbox;
      $message["HEADER"] = fetchHeader($imapConnection, $id);
      $message["ENTITIES"] = fetchBody($imapConnection, $message["HEADER"]["BOUNDARY"], $id, $message["HEADER"]["TYPE0"], $message["HEADER"]["TYPE1"]);
      return $message;
   }

   function fetchHeader($imapConnection, $id) {
      fputs($imapConnection, "messageFetch FETCH $id:$id RFC822.HEADER\n");
      $read = fgets($imapConnection, 1024);

      /** defaults... if the don't get overwritten, it will display text **/
      $header["TYPE0"] = "text";
      $header["TYPE1"] = "plain";
      $header["ENCODING"] = "us-ascii";
      while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
         /** MIME-VERSION **/
         if (substr($read, 0, 17) == "MIME-Version: 1.0") {
            $header["MIME"] = true;
            $read = fgets($imapConnection, 1024);
         }

         /** ENCODING TYPE **/
         else if (substr(strtolower($read[$i]), 0, 26) == "content-transfer-encoding:") {
            $header["ENCODING"] = strtolower(trim(substr($read[$i], 26)));
         }

         /** CONTENT-TYPE **/
         else if (substr($read, 0, 13) == "Content-Type:") {
            $cont = strtolower(trim(substr($read, 13)));
            if (strpos($cont, ";"))
               $cont = substr($cont, 0, strpos($cont, ";"));

            if (strpos($cont, "/")) {
               $header["TYPE0"] = substr($cont, 0, strpos($cont, "/"));
               $header["TYPE1"] = substr($cont, strpos($cont, "/")+1);
            } else {
               $header["TYPE0"] = $cont;
            }

            $line = $read;
            $read = fgets($imapConnection, 1024);
            while ( (substr(substr($read, 0, strpos($read, " ")), -1) != ":") && (trim($read) != "") && (trim($read) != ")")) {
               str_replace("\n", "", $line);
               str_replace("\n", "", $read);
               $line = "$line $read";
               $read = fgets($imapConnection, 1024);
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
         else if (strtolower(substr($read, 0, 9)) == "reply-to:") {
            $header["REPLYTO"] = trim(substr($read, 9, strlen($read)));
            $read = fgets($imapConnection, 1024);
         }

         /** FROM **/
         else if (strtolower(substr($read, 0, 5)) == "from:") {
            $header["FROM"] = trim(substr($read, 5, strlen($read) - 6));
            if ($header["REPLYTO"] == "")
               $header["REPLYTO"] = $header["FROM"];
            $read = fgets($imapConnection, 1024);
         }
         /** DATE **/
         else if (strtolower(substr($read, 0, 5)) == "date:") {
            $d = substr($read, 5, strlen($read) - 6);
            $d = trim($d);
            $d = ereg_replace("  ", " ", $d);
            $d = explode(" ", $d);
            $header["DATE"] = getTimeStamp($d);
            $read = fgets($imapConnection, 1024);
         }
         /** SUBJECT **/
         else if (strtolower(substr($read, 0, 8)) == "subject:") {
            $header["SUBJECT"] = trim(substr($read, 8, strlen($read) - 9));
            if (strlen(Chop($header["SUBJECT"])) == 0)
               $header["SUBJECT"] = "(no subject)";
            $read = fgets($imapConnection, 1024);
         }
         /** CC **/
         else if (strtolower(substr($read, 0, 3)) == "cc:") {
            $pos = 0;
            $header["CC"][$pos] = trim(substr($read, 4));
            $read = fgets($imapConnection, 1024);
            while ((substr($read, 0, 1) == " ") && (trim($read) != "")) {
               $pos++;
               $header["CC"][$pos] = trim($read);
               $read = fgets($imapConnection, 1024);
            }
         }
         /** TO **/
         else if (strtolower(substr($read, 0, 3)) == "to:") {
            $pos = 0;
            $header["TO"][$pos] = trim(substr($read, 4));
            $read = fgets($imapConnection, 1024);
            while ((substr($read, 0, 1) == " ")  && (trim($read) != "")){
               $pos++;
               $header["TO"][$pos] = trim($read);
               $read = fgets($imapConnection, 1024);
            }
         }

         /** ERROR CORRECTION **/
         else if (substr($read, 0, 1) == ")") {
            if ($header["SUBJECT"] == "")
                $header["SUBJECT"] = "(no subject)";

            if ($header["FROM"] == "")
                $header["FROM"] = "(unknown sender)";

            if ($header["DATE"] == "")
                $header["DATE"] = time();
            $read = fgets($imapConnection, 1024);
         }
         else {
            $read = fgets($imapConnection, 1024);
         }
      }
      return $header;
   }

   function fetchBody($imapConnection, $bound, $id, $type0, $type1) {
      /** This first part reads in the full body of the message **/
      fputs($imapConnection, "messageFetch FETCH $id:$id BODY[TEXT]\n");
      $read = fgets($imapConnection, 1024);

      $count = 0;
      while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
         $body[$count] = $read;
         $count++;

         $read = fgets($imapConnection, 1024);
      }

      /** this deletes the first line, and the last two (imap stuff we ignore) **/
      $i = 0;
      $j = 0;
      while ($i < count($body)) {
         if ( ($i != 0) && ($i != count($body) - 1) && ($i != count($body)) ) {
            $bodytmp[$j] = $body[$i];
            $j++;
         }
         $i++;
      }
      $body = $bodytmp;

      /** Now, lets work out the MIME stuff **/
      /** (needs mime.php included)         **/
      return decodeMime($body, $bound, $type0, $type1);
   }

   function fetchEntityHeader($imapConnection, &$read, &$type0, &$type1, &$bound, &$encoding, &$charset, &$filename) {
      /** defaults... if the don't get overwritten, it will display text **/
      $type0 = "text";
      $type1 = "plain";
      $encoding = "us-ascii";
      $i = 0;
      while (trim($read[$i]) != "") {
         if (substr(strtolower($read[$i]), 0, 26) == "content-transfer-encoding:") {
            $encoding = strtolower(trim(substr($read[$i], 26)));

         } else if (substr($read[$i], 0, 13) == "Content-Type:") {
            $cont = strtolower(trim(substr($read[$i], 13)));
            if (strpos($cont, ";"))
               $cont = substr($cont, 0, strpos($cont, ";"));

            if (strpos($cont, "/")) {
               $type0 = substr($cont, 0, strpos($cont, "/"));
               $type1 = substr($cont, strpos($cont, "/")+1);
            } else {
               $type0 = $cont;
            }

            $read[$i] = trim($read[$i]);
            $line = $read[$i];
            $i++;
            while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
               str_replace("\n", "", $line);
               str_replace("\n", "", $read[$i]);
               $line = "$line $read[$i]";
               $i++;
               $read[$i] = trim($read[$i]);
            }
            $i--;

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
               $filename = $name;
            }
         }
         $i++;
      }

      /** remove the header from the entity **/
      $i = 0;
      while (trim($read[$i]) != "") {
         $i++;
      }
      $i++;

      for ($p = 0; $i < count($read); $p++) {
         $entity[$p] = $read[$i];
         $i++;
      }

      $read = $entity;
   }

?>
