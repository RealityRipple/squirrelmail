<?
   /**
    **  mailbox_display.php3
    **
    **  This contains functions that display mailbox information, such as the
    **  table row that has sender, date, subject, etc...
    **
    **/

   function printMessageInfo($imapConnection, $i, $from, $subject, $dateString, $answered, $seen) {
      $senderName = getSenderName($from);
      if (strlen(Chop($subject)) == 0)
         $subject = "(no subject)";

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

   /**
    ** This function loops through a group of messages in the mailbox and shows them
    **/
   function showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort) {
      $j = 1;
      while ($j <= $numMessages) {
         $q = 0;
         getMessageHeaders($imapConnection, $j, $from, $subject, $date);
         getMessageFlags($imapConnection, $j, $flags);

         $messages[$j]["TIME_STAMP"] = getTimeStamp(explode(" ", trim($date)));
         $messages[$j]["DATE_STRING"] = getDateString(explode(" ", trim($date)));
         $messages[$j]["ID"] = $j;
         $messages[$j]["FROM"] = $from;
         $messages[$j]["SUBJECT"] = $subject;
         $messages[$j]["FLAG_DELETED"] = false;
         $messages[$j]["FLAG_ANSWERED"] = false;
         $messages[$j]["FLAG_SEEN"] = false;

         while ($q < count($flags)) {
            if ($flags[$q] == "Deleted") {
               $messages[$j]["FLAG_DELETED"] = true;
            }
            else if ($flags[$q] == "Answered") {
               $messages[$j]["FLAG_ANSWERED"] = true;
            }
            else if ($flags[$q] == "Seen") {
               $messages[$j]["FLAG_SEEN"] = true;
            }
            $q++;
         }

         $j++;
      }

      /** Find and remove the ones that are deleted */
      $i = 1;
      $j = 1;
      while ($j <= $numMessages) {
         if ($messages[$j]["FLAG_DELETED"] == true) {
            $j++;
            continue;
         }
         $msgs[$i]["TIME_STAMP"]    = $messages[$j]["TIME_STAMP"];
         $msgs[$i]["DATE_STRING"]   = $messages[$j]["DATE_STRING"];
         $msgs[$i]["ID"]            = $messages[$j]["ID"];
         $msgs[$i]["FROM"]          = $messages[$j]["FROM"];
         $msgs[$i]["SUBJECT"]       = $messages[$j]["SUBJECT"];
         $msgs[$i]["FLAG_DELETED"]  = $messages[$j]["FLAG_DELETED"];
         $msgs[$i]["FLAG_ANSWERED"] = $messages[$j]["FLAG_ANSWERED"];
         $msgs[$i]["FLAG_SEEN"]     = $messages[$j]["FLAG_SEEN"];
         $i++;
         $j++;
      }

      $numMessagesOld = $numMessages;
      $numMessages = $i - 1;

      if ($sort == 0)
         $msgs = ary_sort($msgs, "TIME_STAMP", -1);
      else
         $msgs = ary_sort($msgs, "TIME_STAMP", 1);

      if ($startMessage + 24 < $numMessages) {
         $endMessage = $startMessage + 24;
      } else {
         $endMessage = $numMessages;
      }

      $nextGroup = $startMessage + 25;
      $prevGroup = $startMessage - 25;
      $urlMailbox = urlencode($mailbox);

      /** This is the beginning of the message list table.  It wraps around all messages */
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1>";

      echo "<TR BGCOLOR=FFFFFF><TD>";
      echo "<CENTER><FONT FACE=\"Arial,Helvetica\">Viewing messages <B>$startMessage</B> to <B>$endMessage</B> ($numMessages total)</FONT></CENTER>\n";
      echo "</TD></TR>\n";

      echo "<TR BGCOLOR=FFFFFF><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      echo "</TD></TR>\n";

      echo "<TR><TD BGCOLOR=DCDCDC>";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=FFFFFF>";
      echo "<TR BGCOLOR=FFFFCC ALIGN=\"center\">";
      echo "   <TD WIDTH=5%><FONT FACE=\"Arial,Helvetica\"><B>Num</B></FONT></TD>";
      echo "   <TD WIDTH=25%><FONT FACE=\"Arial,Helvetica\"><B>From</B></FONT></TD>";
      echo "   <TD WIDTH=15%><FONT FACE=\"Arial,Helvetica\"><B>Date</B></FONT>";
      if ($sort == 0)
         echo "   <A HREF=\"right_main.php3?sort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php3?sort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      echo "   <TD WIDTH=*><FONT FACE=\"Arial,Helvetica\"><B>Subject</B></FONT></TD>\n";
      echo "</TR>";

      // loop through and display the info for each message.
      for ($i = $startMessage - 1;$i <= $endMessage - 1; $i++) {
         printMessageInfo($imapConnection, $msgs[$i]["ID"], $msgs[$i]["FROM"], $msgs[$i]["SUBJECT"], $msgs[$i]["DATE_STRING"], $msgs[$i]["FLAG_ANSWERED"], $msgs[$i]["FLAG_SEEN"]);
      }
      echo "</TABLE>\n";

      echo "</TD></TR>\n";
      echo "<TR BGCOLOR=FFFFFF><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      echo "</TD></TR></TABLE>"; /** End of message-list table */
   }
?>
