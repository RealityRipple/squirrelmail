<?
   if(!isset($logged_in)) {
      echo "You must <a href=\"login.php3\">login</a> first.";
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }
?>
<HTML>
<BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<FONT FACE="Arial,Helvetica"> 
<?
   include("config/config.php3");
   include("functions/imap.php3");
   include("functions/strings.php3");
   include("functions/date.php3");
   include("functions/pageheader.php3");
   include("functions/array.php3");
   
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
            $i = 0;
            while ($i < count($flags)) {
               $flags[$i] = substr($flags[$i], 1, strlen($flags[$i]));
               $i++;
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

   function printMessageInfo($imapConnection, $i, $from, $subject, $date) {
      getMessageHeaders($imapConnection, $i, $from, $subject, $date);
      getMessageFlags($imapConnection, $i, $flags);
      $dateParts = explode(" ", trim($date));
      $dateString = getDateString($dateParts);  // this will reformat the date string into a good format for us.
      $senderName = getSenderName($from);
      if (strlen(Chop($subject)) == 0)
         $subject = "(no subject)";

      $j = 0;
      $deleted = false;
      $seen = false;
      $answered = false;
      while ($j < count($flags)) {
         if ($flags[$j] == "Deleted") {
            $deleted = true;
         } else if ($flags[$j] == "Answered") {
            $answered = true;
         } else if ($flags[$j] == "Seen") {
            $seen = true;
         }
         $j++;
      }

      if ($deleted == false) {
         echo "<TR>\n";
         if ($seen == false) {
            echo "   <TD><FONT FACE=\"Arial,Helvetica\"><B>$i</B></FONT></TD>\n";
            echo "   <TD><FONT FACE=\"Arial,Helvetica\"><B>$senderName</B></FONT></TD>\n";
            echo "   <TD><CENTER><B><FONT FACE=\"Arial,Helvetica\">$dateString</FONT></B></CENTER></TD>\n";
            echo "   <TD><FONT FACE=\"Arial,Helvetica\"><B>$subject</B></FONT></TD>\n";
         } else {
            echo "   <TD><FONT FACE=\"Arial,Helvetica\">$i</FONT></TD>\n";
            echo "   <TD><FONT FACE=\"Arial,Helvetica\">$senderName</FONT></TD>\n";
            echo "   <TD><FONT FACE=\"Arial,Helvetica\"><CENTER>$dateString</CENTER></FONT></TD>\n";
            echo "   <TD><FONT FACE=\"Arial,Helvetica\">$subject</FONT></TD>\n";
         }
         echo "</TR>\n";
      }
   }



   /////////////////////////////////////////////////////////////////////////////////
   //
   // incoming variables from URL:
   //    $sort             Direction to sort by date
   //                         values:  0  -  descending order
   //                         values:  1  -  ascending order
   //    $startMessage     Message to start at
   //    $mailbox          Full Mailbox name
   //
   // incoming from cookie:
   //    $username         duh
   //    $key              pass
   //
   /////////////////////////////////////////////////////////////////////////////////
   
   
   // If the page has been loaded without a specific mailbox,
   //    just show a page of general info.
   if (!isset($mailbox)) {
      displayPageHeader("None");
      
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><B><CENTER>Welcome to $org_name's WebMail system</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "   <TR><TD BGCOLOR=FFFFFF>";
      echo "         <FONT FACE=\"Arial,Helvetica\" SIZE=-1><CENTER>Running SquirrelMail version $version (c) 1999 by Nathan and Luke Ehresman.</CENTER></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <TABLE COLS=2 WIDTH=75% NOBORDER align=\"center\">";
      echo "         <TR>";
      echo "            <TD BGCOLOR=FFFFFF><CENTER>";
      if (strlen($org_logo) > 3)
         echo "               <IMG SRC=\"$org_logo\">";
      else
         echo "               <B><FONT FACE=\"Arial,Helvetica\">$org_name</FONT></B>";
      echo "            </CENTER></TD></TR><TR>";
      echo "            <TD BGCOLOR=FFFFFF>";
      echo "               <FONT FACE=\"Arial,Helvetica\">$motd</FONT>";
      echo "            </TD>";
      echo "         </TR>";
      echo "      </TABLE>";
      echo "   </TD></TR>";
      echo "</TABLE>";
      echo "</BODY></HTML>";
      exit;
   }

   // open a connection on the imap port (143)
   $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
   if (!$imapConnection) {
      echo "Error connecting to IMAP Server.<br>";
      echo "$errorNumber : $errorString<br>";
      exit;
   }
   $serverInfo = fgets($imapConnection, 256);
   echo "LOGGING IN<BR>";

   // login
   fputs($imapConnection, "1 login $username $key\n");
   $read = fgets($imapConnection, 1024);

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // make a URL safe $mailbox for use in the links
   $urlMailbox = urlencode($mailbox);
  
   displayPageHeader($mailbox);
   $i = 0;
   echo "NumMessages: $numMessages<BR>";
   while ($i < $numMessages) {
      getMessageHeaders($imapConnection, $i, $from, $subject, $date);
      $messages[$i]["DATE"] = $date;
      $messages[$i]["ID"] = $i;
      $messages[$i]["FROM"] = $from;
      $messages[$i]["SUBJECT"] = $subject;
      echo "$messages[$i][\"FROM\"]<BR>";
      $i++;
   }

   if ($sort == 0) {
      ary_sort($messages, "SUBJECT", -1);
   } else {
      ary_sort($messages, "SUBJECT", 1);
   }

   /** This is the beginning of the message list table.  It wraps around all messages */
   echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1>";
//   echo "<TR><TD ALIGN=center><FONT FACE=\"Arial,Helvetica\"><BR>Viewing messages <B>$startMessage</B> to <B>$endMessage</B>&nbsp&nbsp&nbsp($numMessages total)<BR></FONT></TD></TR>";
   echo "<TR><TD BGCOLOR=DCDCDC>";
   // display the correct next/Previous listings...
/*   if ($sort == 1) {
      if ($endMessage < $numMessages) {
         $nextGroup = $endMessage + 1;
         echo "<A HREF=\"mailboxMessageList.php3?sort=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>&nbsp&nbsp&nbsp";
      }
      if ($startMessage > 1) {
         $nextGroup = $startMessage - 25;
         echo "<A HREF=\"mailboxMessageList.php3?sort=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">Previous</A>";
      }
   } else {
      if ($endMessage > 1) {
         $nextGroup = $endMessage - 1;
         echo "<A HREF=\"mailboxMessageList.php3?sort=0&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>&nbsp&nbsp&nbsp";
      }
      if ($startMessage < $numMessages) {
         $nextGroup = $startMessage + 25;
         echo "<A HREF=\"mailboxMessageList.php3?sort=0&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">Previous</A>";
      }
   }
   */
   echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=FFFFFF>";
   echo "<TR BGCOLOR=FFFFCC ALIGN=\"center\">";
   echo "   <TD WIDTH=5%><FONT FACE=\"Arial,Helvetica\"><B>Num</B></FONT></TD>";
   echo "   <TD WIDTH=25%><FONT FACE=\"Arial,Helvetica\"><B>From</B></FONT></TD>";
   echo "   <TD WIDTH=15%><FONT FACE=\"Arial,Helvetica\"><B>Date</B></FONT>";
   if ($sort == 0)
      echo "   <A HREF=\"mailboxMessageList.php3?sort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"images/up_pointer.gif\" BORDER=0></A></TD>\n";
   else
      echo "   <A HREF=\"mailboxMessageList.php3?sort=0&startMessage=0&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"images/down_pointer.gif\" BORDER=0></A></TD>\n";
   echo "   <TD WIDTH=*><FONT FACE=\"Arial,Helvetica\"><B>Subject</B></FONT></TD>\n";
   echo "</TR>";

   // loop through and display the info for each message.
   if ($sort == 1) {
      for ($i = $startMessage;$i <= $endMessage; $i++) {
         getMessageHeaders($imapConnection, $i, $from, $subject, $date);
         printMessageInfo($imapConnection, $i, $from, $subject, $date);
      }
   } else {
      for ($i = $startMessage;$i >= $endMessage; $i--) {
         getMessageHeaders($imapConnection, $i, $from, $subject, $date);
         printMessageInfo($imapConnection, $i, $from, $subject, $date);
      }
   }
   
   echo "</TABLE>\n";

   // display the correct next/Previous listings...
   if ($sort == 1) {
      if ($endMessage < $numMessages) {
         $nextGroup = $endMessage + 1;
         echo "<A HREF=\"mailboxMessageList.php3?sort=1&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>&nbsp&nbsp&nbsp";
      }
      if ($startMessage > 1) {
         $nextGroup = $startMessage - 25;
         echo "<A HREF=\"mailboxMessageList.php3?sort=1&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\">Previous</A>";
      }
   } else {
      if ($endMessage > 1) {
         $nextGroup = $endMessage - 1;
         echo "<A HREF=\"mailboxMessageList.php3?sort=0&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>&nbsp&nbsp&nbsp";
      }
      if ($startMessage < $numMessages) {
         $nextGroup = $startMessage + 25;
         echo "<A HREF=\"mailboxMessageList.php3?sort=0&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\">Previous</A>";
      }
   }
   echo "</TD></TR></TABLE>"; /** End of message-list table */

   fclose($imapConnection);
?>
</FONT>
</BODY>
</HTML>
