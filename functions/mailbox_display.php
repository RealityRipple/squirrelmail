<?php

   /**
    **  mailbox_display.php
    **
    **  This contains functions that display mailbox information, such as the
    **  table row that has sender, date, subject, etc...
    **
    **/

   $mailbox_info = true;

   function printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $startMessage) {
      global $color, $msgs, $msort;
		global $sent_folder;
      global $message_highlight_list;

		$msg = $msgs[$key];

      $senderName = $msg["FROM"];
      $urlMailbox = urlencode($mailbox);
      $subject = trim(stripslashes($msg["SUBJECT"]));
      echo "<TR>\n";
      
      if ($msg["FLAG_FLAGGED"] == true) { $flag = "<font color=$color[2]>"; $flag_end = "</font>"; }
      if ($msg["FLAG_SEEN"] == false) { $bold = "<b>"; $bold_end = "</b>"; }
		if ($mailbox == $sent_folder) { $italic = "<i>"; $italic_end = "</i>"; }
      
      for ($i=0; $i < count($message_highlight_list); $i++) {
         if (eregi($message_highlight_list[$i]["value"],$msg[strtoupper($message_highlight_list[$i]["match_type"])])) {
            $hlt_color = $message_highlight_list[$i]["color"];
            continue;
         }   
      }   
      if (!$hlt_color)
         $hlt_color = $color[4];
      
      echo "   <td width=1% bgcolor=$hlt_color align=center><input type=checkbox name=\"msg[$t]\" value=".$msg["ID"]."></TD>\n";
      echo "   <td width=30% bgcolor=$hlt_color>$italic$bold$flag$senderName$flag_end$bold_end$italic_end</td>\n";
      echo "   <td nowrap width=1% bgcolor=$hlt_color><center>$bold$flag".$msg["DATE_STRING"]."$flag_end$bold_end</center></td>\n";
		if ($msg["FLAG_ANSWERED"] == true) echo "   <td bgcolor=$hlt_color width=1%><b><small>A</small></b></td>";
		else	echo "   <td bgcolor=$hlt_color width=1%>&nbsp;</td>";
      echo "   <td bgcolor=$hlt_color width=%>$bold<a href=\"read_body.php?mailbox=$urlMailbox&passed_id=".$msg["ID"]."&startMessage=$startMessage&show_more=0\">$flag$subject$flag_end</a>$bold_end</td>\n";

      echo "</tr>\n";
   }

   /**
    ** This function loops through a group of messages in the mailbox and shows them
    **/
   function showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color,$show_num, $use_cache) {
      global $msgs, $msort;
		global $sent_folder;
      global $message_highlight_list;

      if (!$use_cache) {
         if ($numMessages >= 1) {
            for ($q = 0; $q < $numMessages; $q++) {
					if ($mailbox == $sent_folder)
   	           	$hdr = sqimap_get_small_header ($imapConnection, $q+1, true);
					else
         	     	$hdr = sqimap_get_small_header ($imapConnection, $q+1, false);
						
					$from[$q] = $hdr->from;
					$date[$q] = $hdr->date;
					$subject[$q] = $hdr->subject;
               $to[$q] = $hdr->to;

               $flags[$q] = sqimap_get_flags ($imapConnection, $q+1);
            }
         }
   
         $j = 0;
         while ($j < $numMessages) {
            $date[$j] = ereg_replace("  ", " ", $date[$j]);
            $tmpdate = explode(" ", trim($date[$j]));
   
            $messages[$j]["TIME_STAMP"] = getTimeStamp($tmpdate);
            $messages[$j]["DATE_STRING"] = getDateString($messages[$j]["TIME_STAMP"]);
            $messages[$j]["ID"] = $j+1;
            $messages[$j]["FROM"] = decodeHeader($from[$j]);
            $messages[$j]["SUBJECT"] = decodeHeader($subject[$j]);
            $messages[$j]["TO"] = decodeHeader($to[$j]);
   
            $num = 0;
            while ($num < count($flags[$j])) {
               if ($flags[$j][$num] == "Deleted") {
                  $messages[$j]["FLAG_DELETED"] = true;
               }
               else if ($flags[$j][$num] == "Answered") {
                  $messages[$j]["FLAG_ANSWERED"] = true;
               }
               else if ($flags[$j][$num] == "Seen") {
                  $messages[$j]["FLAG_SEEN"] = true;
               }
               else if ($flags[$j][$num] == "Flagged") {
                  $messages[$j]["FLAG_FLAGGED"] = true;
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
            $msgs[$i] = $messages[$j];
   
            $i++;
            $j++;
         }
         $numMessages = $i;
      }         

      // There's gotta be messages in the array for it to sort them.
      if (($numMessages > 0) && (!$use_cache)) {
         /** 0 = Date (up)      4 = Subject (up)
          ** 1 = Date (dn)      5 = Subject (dn)
          ** 2 = Name (up)
          ** 3 = Name (dn)
          **/
         session_unregister("msgs");
         if (($sort == 0) || ($sort == 1))
            $msort = array_cleave ($msgs, "TIME_STAMP");
         if (($sort == 2) || ($sort == 3))
            $msort = array_cleave ($msgs, "FROM");
         if (($sort == 4) || ($sort == 5))
            $msort = array_cleave ($msgs, "SUBJECT");

         if(($sort % 2) == 1) {
            asort($msort);
         } else {
            arsort($msort);
         }
         session_register("msort");
      }
      displayMessageArray($imapConnection, $numMessages, $startMessage, $msgs, $msort, $mailbox, $sort, $color,$show_num);
     session_register("msgs");
   }

   // generic function to convert the msgs array into an HTML table
   function displayMessageArray($imapConnection, $numMessages, $startMessage, &$msgs, $msort, $mailbox, $sort, $color,$show_num) {
      global $folder_prefix, $sent_folder;
		global $imapServerAddress;

      // do a check to see if the config stuff has already been included or not
//      if (!isset($imapServerAddress))
//         include("../config/config.php");

      // if cache isn't already set, do it now
      if (!session_is_registered("msgs"))
         session_register("msgs");
      if (!session_is_registered("msort"))
         session_register("msort");

      if ($startMessage + ($show_num - 1) < $numMessages) {
         $endMessage = $startMessage + ($show_num-1);
      } else {
         $endMessage = $numMessages;
      }

      $nextGroup = $startMessage + $show_num;
      $prevGroup = $startMessage - $show_num;
      $urlMailbox = urlencode($mailbox);

      /** This is the beginning of the message list table.  It wraps around all messages */
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1>";

      if ($startMessage < $endMessage) {
         echo "<TR BGCOLOR=\"$color[4]\"><TD>";
         echo "<CENTER>". _("Viewing messages ") ."<B>$startMessage</B>". _(" to ") ."<B>$endMessage</B> ($numMessages total)</CENTER>\n";
         echo "</TD></TR>\n";
      } else if ($startMessage == $endMessage) {
         echo "<TR BGCOLOR=\"$color[4]\"><TD>";
         echo "<CENTER>". _("Viewing message ") ."<B>$startMessage</B> ($numMessages ". _("total") .")</CENTER>\n";
         echo "</TD></TR>\n";
      }

      echo "<TR BGCOLOR=\"$color[4]\"><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") ."</A>\n";
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") ."</A>\n";
         echo "<FONT COLOR=\"$color[9]\">Next</FONT>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<FONT COLOR=\"$color[9]\">Previous</FONT>\n";
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
      }
      echo "</TD></TR>\n";

      /** The delete and move options */
      echo "<TR><TD BGCOLOR=\"$color[0]\">";

      echo "\n\n\n<FORM name=messageList method=post action=\"move_messages.php?msg=$msg&mailbox=$urlMailbox&startMessage=$startMessage\">";
      echo "<TABLE BGCOLOR=\"$color[0]\" COLS=2 BORDER=0>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=60% ALIGN=LEFT>\n";
      echo "         <NOBR><SMALL>". _("Move selected to:") ."</SMALL>";
      echo "         <TT><SMALL><SELECT NAME=\"targetMailbox\">";

      $boxes = sqimap_mailbox_list($imapConnection);
      for ($i = 0; $i < count($boxes); $i++) {
			if ($boxes[$i]["flags"][0] != "noselect" && $boxes[$i]["flags"][1] != "noselect" && $boxes[$i]["flags"][2] != "noselect") {
         	$box = $boxes[$i]["unformatted"];
         	$box2 = replace_spaces($boxes[$i]["formatted"]);
         	echo "         <OPTION VALUE=\"$box\">$box2\n";
			}	
      }
      echo "         </SELECT></SMALL></TT>";
      echo "         <SMALL><INPUT TYPE=SUBMIT NAME=\"moveButton\" VALUE=\"". _("Move") ."\"></SMALL></NOBR>\n";

      echo "      </TD>\n";
      echo "      <TD WIDTH=40% ALIGN=RIGHT>\n";
      echo "         <NOBR><SMALL><INPUT TYPE=SUBMIT VALUE=\"". _("Delete") ."\">&nbsp;". _("checked messages") ."</SMALL></NOBR>\n";
      echo "      </TD>";
      echo "   </TR>\n";

      echo "</TABLE>\n\n\n";
      echo "</TD></TR>";

      echo "<TR><TD BGCOLOR=\"$color[0]\">";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[4]\">";
      echo "<TR BGCOLOR=\"$color[5]\" ALIGN=\"center\">";
      echo "   <TD WIDTH=1%><B>&nbsp;</B></TD>";
      /** FROM HEADER **/
		if ($mailbox == $sent_folder)
      	echo "   <TD WIDTH=30%><B>". _("To") ."</B>";
		else
      	echo "   <TD WIDTH=30%><B>". _("From") ."</B>";

      if ($sort == 2)
         echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 3)
         echo "   <A HREF=\"right_main.php?newsort=2&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
      /** DATE HEADER **/
      echo "   <TD nowrap WIDTH=1%><B>". _("Date") ."</B>";
      if ($sort == 0)
         echo "   <A HREF=\"right_main.php?newsort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 1)
         echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
      echo "   <TD WIDTH=1%>&nbsp;</TD>\n";
      /** SUBJECT HEADER **/
      echo "   <TD WIDTH=%><B>". _("Subject") ."</B>\n";
      if ($sort == 4)
        echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
      else if ($sort == 5)
         echo "   <A HREF=\"right_main.php?newsort=4&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
      else
         echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";

      echo "</TR>";

      
      // loop through and display the info for each message.
      $t = 0; // $t is used for the checkbox number
      if ($numMessages == 0) { // if there's no messages in this folder
         echo "<TR><TD BGCOLOR=\"$color[4]\" COLSPAN=5><CENTER><BR><B>". _("THIS FOLDER IS EMPTY") ."</B><BR>&nbsp;</CENTER></TD></TR>";
      } else if ($startMessage == $endMessage) { // if there's only one message in the box, handle it different.
         $i = $startMessage - 1;
         reset($msort);
         do {
            $key = key($msort);
            next($msort);
            $k++;
         } while (isset ($key) && ($k < $i));
         printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $startMessage);
      } else {
         $i = $startMessage;
         reset($msort);
         do {
            $key = key($msort);
            next($msort);
            $k++;
         } while (isset ($key) && ($k < $i));

		   do {
            printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $startMessage);
            $key = key($msort);
            $t++;
            $i++;
            next($msort);
         } while ($i < ($endMessage+1));
      }
      echo "</FORM></TABLE>";

      echo "</TABLE>\n";
      echo "</TD></TR>\n";

      echo "<TR BGCOLOR=\"$color[4]\"><TD>";
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">" . _("Previous") . "</A>\n";
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">" . _("Next") . "</A>\n";
      }
      else if (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">" . _("Previous") . "</A>\n";
         echo "<FONT COLOR=\"$color[9]\">" . _("Next") . "</FONT>\n";
      }
      else if (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         echo "<FONT COLOR=\"$color[9]\">Previous</FONT>\n";
         echo "<A HREF=\"right_main.php?use_mailbox_cache=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">" . _("Next") . "</A>\n";
      }
      echo "</TD></TR></TABLE>"; /** End of message-list table */

   }
?>
