<?

   /**
    **  mailbox_display.php
    **
    **  This contains functions that display mailbox information, such as the
    **  table row that has sender, date, subject, etc...
    **
    **/

   function printMessageInfo($imapConnection, $t, $i, $from, $subject, $dateString, $answered, $seen, $mailbox, $sort, $startMessage) {
      $senderName = getSenderName($from);
      $urlMailbox = urlencode($mailbox);
      echo "<TR>\n";
      if ($seen == false) {
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><nobr><B><input type=checkbox name=\"msg[$t]\" value=$i></B></nobr></FONT></TD>\n";
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><B>$senderName</B></FONT></TD>\n";
         echo "   <TD><CENTER><B><FONT FACE=\"Arial,Helvetica\">$dateString</FONT></B></CENTER></TD>\n";
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><B><A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$i&sort=$sort&startMessage=$startMessage\">$subject</A></B></FONT></TD>\n";
      } else {
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><nobr><input type=checkbox name=\"msg[$t]\" value=$i></nobr></FONT></TD>\n";
         echo "   <TD><FONT FACE=\"Arial,Helvetica\">$senderName</FONT></TD>\n";
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><CENTER>$dateString</CENTER></FONT></TD>\n";
         echo "   <TD><FONT FACE=\"Arial,Helvetica\"><A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$i&sort=$sort&startMessage=$startMessage\">$subject</A></FONT></TD>\n";
      }
      echo "</TR>\n";
   }

   /**
    ** This function loops through a group of messages in the mailbox and shows them
    **/
   function showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort) {
      include("../config/config.php");

      if (1 <= $numMessages) {
         getMessageHeaders($imapConnection, 1, $numMessages, $from, $subject, $date);
      }

      $j = 0;
      while ($j < $numMessages) {
//         echo "$numMessages -- $j -- $subject[$j]<BR>";
         $date[$j] = ereg_replace("  ", " ", $date[$j]);
         $tmpdate = explode(" ", trim($date[$j]));

         $messages[$j]["TIME_STAMP"] = getTimeStamp($tmpdate);
         $messages[$j]["DATE_STRING"] = getDateString($tmpdate);
         $messages[$j]["ID"] = $j+1;
         $messages[$j]["FROM"] = getSenderName($from[$j]);
         $messages[$j]["SUBJECT"] = $subject[$j];
         $messages[$j]["FLAG_DELETED"] = false;
         $messages[$j]["FLAG_ANSWERED"] = false;
         $messages[$j]["FLAG_SEEN"] = false;

         $num = 0;
         getMessageFlags($imapConnection, $j+1, $flags);
         while ($num < count($flags)) {
            if ($flags[$num] == "Deleted") {
               $messages[$j]["FLAG_DELETED"] = true;
            }
            else if ($flags[$num] == "Answered") {
               $messages[$j]["FLAG_ANSWERED"] = true;
            }
            else if ($flags[$num] == "Seen") {
               $messages[$j]["FLAG_SEEN"] = true;
            }
            $num++;
         }
         $j++;
      }

      /** Find and remove the ones that are deleted */
      $i = 0;
      $j = 0;
      while ($j < $numMessages) {
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

      $numMessages = $i;

      // There's gotta be messages in the array for it to sort them.
      if ($numMessages > 0) {
         /** 0 = Date (up)      4 = Subject (up)
          ** 1 = Date (dn)      5 = Subject (dn)
          ** 2 = Name (up)
          ** 3 = Name (dn)
          **/
         if ($sort == 0)
            $msgs = ary_sort($msgs, "TIME_STAMP", -1);
         else if ($sort == 1)
            $msgs = ary_sort($msgs, "TIME_STAMP", 1);
         else {
            $original = $msgs;
            $i = 0;
            while ($i < count($msgs)) {
               $msgs[$i]["FROM"] = strtolower($msgs[$i]["FROM"]);
               $msgs[$i]["SUBJECT"] = strtolower($msgs[$i]["SUBJECT"]);
               $i++;
            }

            if ($sort == 2)
               $msgs = ary_sort($msgs, "FROM", -1);
            else if ($sort == 3)
               $msgs = ary_sort($msgs, "FROM", 1);
            else if ($sort == 4)
               $msgs = ary_sort($msgs, "SUBJECT", -1);
            else if ($sort == 5)
               $msgs = ary_sort($msgs, "SUBJECT", 1);
            else
               $msgs = ary_sort($msgs, "TIME_STAMP", -1);

            $i = 0;
            while ($i < count($msgs)) {
               $j = 0;
               while ($j < count($original)) {
                  if ($msgs[$i]["ID"] == $original[$j]["ID"]) {
                     $msgs[$i]["FROM"] = $original[$j]["FROM"];
                     $msgs[$i]["SUBJECT"] = $original[$j]["SUBJECT"];
                  }
                  $j++;
               }
               $i++;
            }
         }
      }

//      $j = 0;
//      while ($j < $numMessages) {
//         $sub = $msgs[$j]["SUBJECT"];
//         $id = $msgs[$j]["ID"];
//         echo "$id -- $sub<BR>";
//         $j++;
//      }
//exit;

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

      if ($startMessage < $endMessage) {
         echo "<TR BGCOLOR=FFFFFF><TD>";
         echo "<CENTER><FONT FACE=\"Arial,Helvetica\">Viewing messages <B>$startMessage</B> to <B>$endMessage</B> ($numMessages total)</FONT></CENTER>\n";
         echo "</TD></TR>\n";
      } else if ($startMessage == $endMessage) {
         echo "<TR BGCOLOR=FFFFFF><TD>";
         echo "<CENTER><FONT FACE=\"Arial,Helvetica\">Viewing message <B>$startMessage</B> ($numMessages total)</FONT></CENTER>\n";
         echo "</TD></TR>\n";
      }

      echo "<TR BGCOLOR=FFFFFF><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=ACACAC>Next</FONT>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=ACACAC>Previous</FONT>\n";
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      echo "</TD></TR>\n";

      /** The delete and move options */
      echo "<TR><TD BGCOLOR=$color_lgray>";

      echo "\n\n\n<FORM name=messageList method=post action=\"move_messages.php?msg=$msg&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage\">";
      echo "<TABLE BGCOLOR=$color_lgray>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=30% ALIGN=LEFT>\n";
      echo "         <NOBR><FONT FACE=\"Arial,Helvetica\" SIZE=2><INPUT TYPE=SUBMIT NAME=\"moveButton\" VALUE=\"Move to:\">\n";
      echo "         <SELECT NAME=\"targetMailbox\">\n";
      getFolderList($imapConnection, $boxesFormatted, $boxesUnformatted);
      for ($i = 0; $i < count($boxesUnformatted); $i++) {
         $use_folder = true;
         for ($p = 0; $p < count($special_folders); $p++) {
            // don't allow moving messages to any special folder EXCEPT for INBOX.
            if (($boxesUnformatted[$i] == $special_folders[$p]) && ($boxesUnformatted[$i] != "INBOX")) {
               $use_folder = false;
            } else if (substr($boxesUnformatted[$i], 0, strlen($trash_folder)) == $trash_folder) {
               $use_folder = false;
            }
         }
         if ($use_folder == true)
            echo "         <OPTION VALUE=\"$boxesUnformatted[$i]\">$boxesUnformatted[$i]\n";
      }
      echo "         </SELECT></NOBR></FONT>\n";

      echo "      </TD>\n";
      echo "      <TD WIDTH=70% ALIGN=RIGHT>\n";
      echo "         <NOBR><FONT FACE=\"Arial,Helvetica\" SIZE=2><INPUT TYPE=SUBMIT VALUE=\"Delete\">&nbsp;checked messages</FONT></NOBR>\n";
      echo "      </TD>";
      echo "   </TR>\n";

      if (($move_to_trash == true) && ($mailbox == $trash_folder))
         echo "<TR><TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<A HREF=\"empty_trash.php?numMessages=$numMessages&mailbox=$urlMailbox\">Empty Trash</A></TD></TR>";
      echo "</TABLE>\n\n\n";
      echo "</TD></TR>";

      echo "<TR><TD BGCOLOR=$color_lgray>";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=FFFFFF>";
      echo "<TR BGCOLOR=FFFFCC ALIGN=\"center\">";
      echo "   <TD WIDTH=5%><FONT FACE=\"Arial,Helvetica\"><B>Num</B></FONT></TD>";
      /** FROM HEADER **/
      echo "   <TD WIDTH=25%><FONT FACE=\"Arial,Helvetica\"><B>From</B></FONT>";
      if ($sort == 2)
         echo "   <A HREF=\"right_main.php?sort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 3)
         echo "   <A HREF=\"right_main.php?sort=2&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?sort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
      /** DATE HEADER **/
      echo "   <TD WIDTH=15%><FONT FACE=\"Arial,Helvetica\"><B>Date</B></FONT>";
      if ($sort == 0)
         echo "   <A HREF=\"right_main.php?sort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 1)
         echo "   <A HREF=\"right_main.php?sort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?sort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
      /** SUBJECT HEADER **/
      echo "   <TD WIDTH=*><FONT FACE=\"Arial,Helvetica\"><B>Subject</B></FONT>\n";
      if ($sort == 4)
         echo "   <A HREF=\"right_main.php?sort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 5)
         echo "   <A HREF=\"right_main.php?sort=4&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?sort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
      echo "</TR>";

      // loop through and display the info for each message.
      $t = 0; // $t is used for the checkbox number
      if ($numMessages == 0) { // if there's no messages in this folder
         echo "<TR><TD BGCOLOR=FFFFFF COLSPAN=4><CENTER><BR><B>THIS FOLDER IS EMPTY</B><BR>&nbsp</CENTER></TD></TR>";
      } else if ($startMessage == $endMessage) { // if there's only one message in the box, handle it different.
         $i = $startMessage - 1;
         printMessageInfo($imapConnection, $t, $msgs[$i]["ID"], $msgs[$i]["FROM"], $msgs[$i]["SUBJECT"], $msgs[$i]["DATE_STRING"], $msgs[$i]["FLAG_ANSWERED"], $msgs[$i]["FLAG_SEEN"], $mailbox, $sort, $startMessage);
      } else {
         for ($i = $startMessage - 1;$i <= $endMessage - 1; $i++) {
            printMessageInfo($imapConnection, $t, $msgs[$i]["ID"], $msgs[$i]["FROM"], $msgs[$i]["SUBJECT"], $msgs[$i]["DATE_STRING"], $msgs[$i]["FLAG_ANSWERED"], $msgs[$i]["FLAG_SEEN"], $mailbox, $sort, $startMessage);
            $t++;
         }
      }
      echo "</FORM></TABLE>";

      echo "</TABLE>\n";
      echo "</TD></TR>\n";

      echo "<TR BGCOLOR=FFFFFF><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Previous</FONT></A>\n";
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=ACACAC>Next</FONT>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<FONT FACE=\"Arial,Helvetica\" COLOR=ACACAC>Previous</FONT>\n";
         echo "<A HREF=\"right_main.php?sort=$sort&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>\n";
      }
      echo "</TD></TR></TABLE>"; /** End of message-list table */
   }
?>
