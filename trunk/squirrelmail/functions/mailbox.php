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

   function getMessageHeaders($imapConnection, $i, &$from, &$subject, &$date) {
      fputs($imapConnection, "messageFetch FETCH $i:$i RFC822.HEADER.LINES (From Subject Date)\n");
      $read = fgets($imapConnection, 1024);
      /* I have to replace <> with [] because HTML uses <> as tags, thus not printing what's in <> */
      $read = ereg_replace("<", "[", $read);
      $read = ereg_replace(">", "]", $read);

      while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
         if (substr($read, 0, 5) == "From:") {
            $read = ereg_replace("<", "EMAILSTART--", $read);
            $read = ereg_replace(">", "--EMAILEND", $read);
            $from = substr($read, 5, strlen($read) - 6);
         }
         else if (substr($read, 0, 5) == "Date:") {
            $read = ereg_replace("<", "[", $read);
            $read = ereg_replace(">", "]", $read);
            $date = substr($read, 5, strlen($read) - 6);
         }
         else if (substr($read, 0, 8) == "Subject:") {
            $read = ereg_replace("<", "[", $read);
            $read = ereg_replace(">", "]", $read);
            $subject = substr($read, 8, strlen($read) - 9);
         }

         $read = fgets($imapConnection, 1024);
      }
   }

   function getMessageFlags($imapConnection, $i, &$flags) {
      /**   * 2 FETCH (FLAGS (\Answered \Seen))   */
      fputs($imapConnection, "messageFetch FETCH $i:$i FLAGS\n");
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
         $read = fgets($imapConnection, 1024);
      }
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
?>
