<?
   /**
    **  mailbox_display.php3
    **
    **  This contains functions that display mailbox information, such as the
    **  table row that has sender, date, subject, etc...
    **
    **/

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
?>