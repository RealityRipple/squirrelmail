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

   function getMessageHeaders($imapConnection, $start, $end, &$from, &$subject, &$date) {

      $rel_start = $start;

      if (($start > $end) || ($start < 1)) {
         echo "Error in message header fetching.  Start message: $start, End message: $end<BR>";
         exit;
      }

      while ($rel_start <= $end) {
         if ($end - $rel_start > 50) {
            $rel_end = $rel_start + 50;
         } else {
            $rel_end = $end;
         }
         fputs($imapConnection, "messageFetch FETCH $rel_start:$rel_end RFC822.HEADER.LINES (From Subject Date)\n");
         $read = fgets($imapConnection, 1024);

         $from_num = $rel_start - 1;
         $date_num = $rel_start - 1;
         $subj_num = $rel_start - 1;
         while ((substr($read, 0, 15) != "messageFetch OK") && (substr($read, 0, 16) != "messageFetch BAD")) {
            if (substr($read, 0, 5) == "From:") {
               $read = ereg_replace("<", "EMAILSTART--", $read);
               $read = ereg_replace(">", "--EMAILEND", $read);
               $from[$from_num] = substr($read, 5, strlen($read) - 6);
               $from_num++;
            }
            else if (substr($read, 0, 5) == "Date:") {
               $read = ereg_replace("<", "[", $read);
               $read = ereg_replace(">", "]", $read);
               $date[$date_num] = substr($read, 5, strlen($read) - 6);
               $date_num++;
            }
            else if (substr($read, 0, 8) == "Subject:") {
               $read = ereg_replace("<", "[", $read);
               $read = ereg_replace(">", "]", $read);
               $subject[$subj_num] = substr($read, 8, strlen($read) - 9);
               $subj_num++;
            }
            $read = fgets($imapConnection, 1024);
         }
         $rel_start = $rel_start + 50;
      }
   }

   function setMessageFlag($imapConnection, $i, $q, $flag) {
      fputs($imapConnection, "messageStore STORE $i:$q +FLAGS (\\$flag)\n");
   }

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
?>
