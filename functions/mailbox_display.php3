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
      $i = 0;
      $j = 1;
      while ($j <= $numMessages) {
         getMessageHeaders($imapConnection, $j, $from, $subject, $date);
         getMessageFlags($imapConnection, $j, $flags);

         $messages[$i]["TIME_STAMP"] = getTimeStamp(explode(" ", trim($date)));
         $messages[$i]["DATE_STRING"] = getDateString(explode(" ", trim($date)));
         $messages[$i]["ID"] = $j;
         $messages[$i]["FROM"] = $from;
         $messages[$i]["SUBJECT"] = $subject;

         $messages[$i]["FLAG_DELETED"] = false;
         $messages[$i]["FLAG_ANSWERED"] = false;
         $messages[$i]["FLAG_SEEN"] = false;

         $q = 0;
         while ($q < count($flags)) {
            if ($flags[$q] == "Deleted")
               $messages[$i]["FLAG_DELETED"] = true;
            else if ($flags[$q] == "Answered")
               $messages[$i]["FLAG_ANSWERED"] = true;
            else if ($flags[$q] == "Seen")
               $messages[$i]["FLAG_SEEN"] = true;
            $q++;
         }

         if ($messages[$i]["FLAG_DELETED"] == false)
            $i++;
         $j++;
      }

      $numMessagesOld = $numMessages;
      $numMessages = $i;

      if ($sort == 0)
         $msgs = ary_sort($messages, "TIME_STAMP", -1);
      else
         $msgs = ary_sort($messages, "TIME_STAMP", 1);

      if ($startMessage + 24 < $numMessages) {
         $nextGroup = $startMessage + 24 + 1; // +1 to go to beginning of next group
         $endMessage = $startMessage + 24;
      } else {
         $nextGroup = -1;
         $endMessage = $numMessages;
      }

      $prevGroup = $startMessage - 25;

      echo "Messages:  $numMessages, $numMessagesOld<BR>";
      echo "Start:     $startMessage to $endMessage<BR>";
      echo "NextGroup: $nextGroup<BR>";
      echo "PrevGroup: $prevGroup<BR>";

      if (($nextGroup > -1) && ($prevGroup > 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\">Next</A>\n";
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$mailbox\" TARGET=\"right\">Previous</A>\n";
      }
      else if (($nextGroup == -1) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$prevGroup&mailbox=$mailbox\" TARGET=\"right\">Previous</A>\n";
      }
      else if (($nextGroup > -1) && ($prevGroup < 0)) {
         echo "<A HREF=\"right_main.php3?sort=$sort&startMessage=$nextGroup&mailbox=$mailbox\" TARGET=\"right\">Next</A>\n";
      }

      /** This is the beginning of the message list table.  It wraps around all messages */
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1>";
      echo "<TR><TD BGCOLOR=DCDCDC>";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=FFFFFF>";
      echo "<TR BGCOLOR=FFFFCC ALIGN=\"center\">";
      echo "   <TD WIDTH=5%><FONT FACE=\"Arial,Helvetica\"><B>Num</B></FONT></TD>";
      echo "   <TD WIDTH=25%><FONT FACE=\"Arial,Helvetica\"><B>From</B></FONT></TD>";
      echo "   <TD WIDTH=15%><FONT FACE=\"Arial,Helvetica\"><B>Date</B></FONT>";
      if ($sort == 0)
         echo "   <A HREF=\"right_main.php3?sort=1&startMessage=1&mailbox=$mailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php3?sort=0&startMessage=1&mailbox=$mailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      echo "   <TD WIDTH=*><FONT FACE=\"Arial,Helvetica\"><B>Subject</B></FONT></TD>\n";
      echo "</TR>";

      // loop through and display the info for each message.
      for ($i = $startMessage - 1;$i <= $endMessage - 1; $i++) {
         printMessageInfo($imapConnection, $msgs[$i]["ID"], $msgs[$i]["FROM"], $msgs[$i]["SUBJECT"], $msgs[$i]["DATE_STRING"], $msgs[$i]["FLAG_ANSWERED"], $msgs[$i]["FLAG_SEEN"]);
      }

      echo "</TABLE>\n";
      echo "</TD></TR></TABLE>"; /** End of message-list table */
   }
?>