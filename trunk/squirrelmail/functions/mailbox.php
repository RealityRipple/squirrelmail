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
      $read = fgets($imapConnection, 1024);
      while ((substr($read, 0, 16) != "mailboxSelect OK") && (substr($read, 0, 17) != "mailboxSelect BAD")) {
         if (substr(Chop($read), -6) == "EXISTS") {
            $array = explode(" ", $read);
            $numberOfMessages = $array[1];
         }
         $read = fgets($imapConnection, 1024);
      }
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
         echo "Error in message header fetching.  Start message: $start, End message: $end<BR>";
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
               $read = ereg_replace("<", "EMAILSTART--", $read);
               $read = ereg_replace(">", "--EMAILEND", $read);
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
      $emailStart = strpos($emailAddr, "EMAILSTART--");
      $emailEnd = strpos($emailAddr, "--EMAILEND") - 10;

      $emailAddr = ereg_replace("EMAILSTART--", "", $emailAddr);
      $emailAddr = ereg_replace("--EMAILEND", "", $emailAddr);
      return $emailAddr;
   }

   function getEmailAddr($sender) {
      if (strpos($sender, "EMAILSTART--") == false)
         return "";

      $start = strpos($sender, "EMAILSTART--");
      $emailAddr = substr($sender, $start, strlen($sender));

      return $emailAddr;
   }

   function getSender($sender) {
      if (strpos($sender, "EMAILSTART--") == false)
         return "";

      $first = substr($sender, 0, strpos($sender, "EMAILSTART--"));
      $second = substr($sender, strpos($sender, "--EMAILEND") +10, strlen($sender));
      return "$first$second";
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
         echo "ERROR... $read<BR>";
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
   }

   function getFolderNameMinusINBOX($mailbox) {
      if (substr($mailbox, 0, 6) == "INBOX.")
         $box = substr($mailbox, 6, strlen($mailbox));
      else
         $box = $mailbox;

      return $box;
   }

   /** This function will fetch the body of a given message and format
       it into our standard format. **/
   function fetchBody($imapConnection, $id) {
      fputs($imapConnection, "messageFetch FETCH $id:$id BODY[TEXT]\n");
      $count = 0;
      $read[$count] = fgets($imapConnection, 1024);
      while ((substr($read[$count], 0, 15) != "messageFetch OK") && (substr($read[$count], 0, 16) != "messageFetch BAD")) {
         $count++;
         $read[$count] = fgets($imapConnection, 1024);
      }

      /** this loop removes the first line, and the last two which
          are IMAP information that we don't need. **/
      $i = 0;
      $j = 0;
      while ($i < count($read)) {
         if (($i != 0) && ($i != count($read) - 1) && ($i != count($read) - 2)){
            $readtmp[$j] = $read[$i];
            $j++;
         }
         $i++;
      }
      $read = $readtmp;

      /** This loop formats the text, creating links out of linkable stuff too **/
      $count = 0;
      $useHTML= false;
      while ($count < count($read)) {
         $read[$count] = "^^$read[$count]";

         if (strpos(strtolower($read[$count]), "<html>") == true) {
            $useHTML = true;
         } else if (strpos(strtolower($read[$count]), "</html>") == true) {
            $useHTML = false;
         }

         $read[$count] = substr($read[$count], 2, strlen($read[$count]));

         if ($useHTML == false) {
            $read[$count] = parsePlainBodyText($read[$count]);
         } else {
            $read[$count] = parseHTMLBodyText($read[$count]);
         }

         $count++;
      }
      return $read;
   }

   function parseHTMLBodyText($line) {
      return $line;
   }

   function parsePlainBodyText($line) {
      $line = "^^$line";

      if ((strpos(strtolower($line), "<!") == false) &&
          (strpos(strtolower($line), "<html>") == false) &&
          (strpos(strtolower($line), "</html>") == false)) {
         $line = str_replace("<", "&lt;", $line);
         $line = str_replace(">", "&gt;", $line);
      }

      $wrap_at = 80; // Make this configurable int the config file some time
      if (strlen($line) - 2 >= $wrap_at) // -2 because of the ^^ at the beginning
         $line = wordWrap($line, $wrap_at);

      $line = str_replace(" ", "&nbsp;", $line);
      $line = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $line);
      $line = str_replace("\n", "", $line);
      $line = str_replace("\r", "", $line);

      /** if >> or > are found at the beginning of a line, I'll assume that was
          replied text, so make it different colors **/
      if (strpos(trim(str_replace("&nbsp;", "", $line)), "&gt;&gt;") == 2) {
         $line = substr($line, 2, strlen($line));
         $line = "<TT><FONT COLOR=FF0000>$line</FONT></TT><BR>\n";
      } else if (strpos(trim(str_replace("&nbsp;", "", $line)), "&gt;") == 2) {
         $line = substr($line, 2, strlen($line));
         $line = "<TT><FONT COLOR=800000>$line</FONT></TT><BR>\n";
      } else {
         $line = substr($line, 2, strlen($line));
         $line = "<TT><FONT COLOR=000000>$line</FONT></TT><BR>\n";
      }

      /** This translates "http://" into a link.  It could be made better to accept
          "www" and "mailto" also.  That should probably be added later. **/
      if (strpos(strtolower($line), "http://") != false) {
         $line = ereg_replace("<BR>", "", $line);
         $start = strpos(strtolower($line), "http://");
         $link = substr($line, $start, strlen($line));

         if (strpos($link, " ")) {
            $end = strpos($link, " ")-1;
         }
         else if (strpos($link, "&nbsp;")) {
            $end = strpos($link, "&nbsp;")-1;
         }
         else if (strpos($link, "<")) {
            $end = strpos($link, "<");
         }
         else if (strpos($link, ">")) {
            $end = strpos($link, ">");
         }
         else if (strpos($link, "(")) {
            $end = strpos($link, "(")-1;
         }
         else if (strpos($link, ")")) {
            $end = strpos($link, ")")-1;
         }
         else if (strpos($link, "{")) {
            $end = strpos($link, "{")-1;
         }
         else if (strpos($link, "}")) {
            $end = strpos($link, "}")-1;
         }
         else
            $end = strlen($link);

         $link = substr($line, $start, $end);
         $end = $end + $start;
         $before = substr($line, 0, $start);
         $after  = substr($line, $end, strlen($line));

         $line = "$before<A HREF=\"$link\" TARGET=_top>$link</A>$after<BR>";
      }

      return $line;
   }

/*
         $start = strpos(strtolower($line), "http://");
         $text = substr($line, $start, strlen($line));
         $linktext = substr($link, 0, $end);
         $link = trim(ereg_replace("<BR>", "", $linktext));


//         $line = str_replace($text, "<A HREF=\"$link\" TARGET=_top>$link</A>", $line);
*/

   function getMessageHeadersTo($imapConnection, $start, $end, &$to) {
      $rel_start = $start;
      if (($start > $end) || ($start < 1)) {
         echo "Error in message header fetching.  Start message: $start, End message: $end<BR>";
         exit;
      }

      $pos = 0;
      while ($rel_start <= $end) {
         if ($end - $rel_start > 50) {
            $rel_end = $rel_start + 49;
         } else {
            $rel_end = $end;
         }
         fputs($imapConnection, "messageFetch FETCH $rel_start:$rel_end RFC822.HEADER.LINES (To)\n");
         $read = fgets($imapConnection, 1024);

         while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
            if (substr($read, 0, 3) == "To:") {
               $read = ereg_replace("<", "&lt;", $read);
               $read = ereg_replace(">", "&gt;", $read);
               $to[$pos] = substr($read, 3, strlen($read));
               if (strlen(Chop($to[$pos])) == 0)
                  $to[$pos] = "Unknown Recipients";
            }
            else if (substr($read, 0, 1) == ")") {
               if ($subject[$pos] == "")
                  $subject[$pos] = "Unknown Recipients";
               $pos++;
            }

            $read = fgets($imapConnection, 1024);
         }
         $rel_start = $rel_start + 50;
      }
   }

?>
