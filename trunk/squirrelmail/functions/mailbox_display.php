<?php

   /**
    **  mailbox_display.php
    **
    **  This contains functions that display mailbox information, such as the
    **  table row that has sender, date, subject, etc...
    **
    **  $Id$
    **/

   $mailbox_display_php = true;

   function printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $startMessage, $where, $what) {
      global $checkall;
      global $color, $msgs, $msort;
      global $sent_folder;
      global $message_highlight_list;
      global $index_order;

      $msg = $msgs[$key];

      $senderName = sqimap_find_displayable_name($msg['FROM']);
      $urlMailbox = urlencode($mailbox);
      $subject = processSubject($msg['SUBJECT']);

      echo "<TR>\n";

      if (isset($msg['FLAG_FLAGGED']) && $msg['FLAG_FLAGGED'] == true) 
      { 
         $flag = "<font color=$color[2]>"; 
         $flag_end = '</font>'; 
      }
      else
      {
         $flag = '';
         $flag_end = '';
      }
      if (!isset($msg['FLAG_SEEN']) || $msg['FLAG_SEEN'] == false) 
      { 
         $bold = '<b>'; 
         $bold_end = '</b>'; 
      }
      else
      {
         $bold = '';
         $bold_end = '';
      }
      if ($mailbox == $sent_folder) 
      { 
         $italic = '<i>'; 
         $italic_end = '</i>'; 
      }
      else
      {
         $italic = '';
         $italic_end = '';
      }
      if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED'])
      { 
         $fontstr = "<font color=\"$color[9]\">"; 
         $fontstr_end = '</font>'; 
      }
      else
      {
         $fontstr = '';
         $fontstr_end = '';
      }

      for ($i=0; $i < count($message_highlight_list); $i++) {
         if (trim($message_highlight_list[$i]['value']) != '') {
            if ($message_highlight_list[$i]['match_type'] == 'to_cc') {
               if (strpos('^^'.strtolower($msg['TO']), strtolower($message_highlight_list[$i]['value'])) || strpos('^^'.strtolower($msg['CC']), strtolower($message_highlight_list[$i]['value']))) {
                  $hlt_color = $message_highlight_list[$i]['color'];
                  continue;
               }
            } else if (strpos('^^'.strtolower($msg[strtoupper($message_highlight_list[$i]['match_type'])]),strtolower($message_highlight_list[$i]['value']))) {
               $hlt_color = $message_highlight_list[$i]['color'];
               continue;
            }
         }
      }

      if (!isset($hlt_color))
         $hlt_color = $color[4];

      if ($where && $what) {
         $search_stuff = '&where='.urlencode($where).'&what='.urlencode($what);
      }

      if ($checkall == 1) 
         $checked = ' checked';
      else
         $checked = '';
      
      for ($i=1; $i <= count($index_order); $i++) {
         switch ($index_order[$i]) {
            case 1: # checkbox
               echo "   <td width=1% bgcolor=$hlt_color align=center><input type=checkbox name=\"msg[$t]\" value=".$msg["ID"]."$checked></TD>\n";
               break;
            case 2: # from
               echo "   <td width=30% bgcolor=$hlt_color>$italic$bold$flag$fontstr$senderName$fontstr_end$flag_end$bold_end$italic_end</td>\n";
               break;
            case 3: # date
               echo "   <td nowrap width=1% bgcolor=$hlt_color><center>$bold$flag$fontstr".$msg["DATE_STRING"]."$fontstr_end$flag_end$bold_end</center></td>\n";
               break;
            case 4: # subject
               echo "   <td bgcolor=$hlt_color>$bold";
                   if (! isset($search_stuff)) { $search_stuff = ''; }
               echo "<a href=\"read_body.php?mailbox=$urlMailbox&passed_id=".$msg["ID"]."&startMessage=$startMessage&show_more=0$search_stuff\"";
               do_hook("subject_link");
               echo ">$flag$subject$flag_end</a>$bold_end</td>\n";
               break;
            case 5: # flags
               $stuff = false;
               echo "   <td bgcolor=$hlt_color align=center width=1% nowrap><b><small>\n";
               if (isset($msg['FLAG_ANSWERED']) && 
                   $msg['FLAG_ANSWERED'] == true) {
                  echo "A\n";
                  $stuff = true;
               }
               if ($msg['TYPE0'] == 'multipart') {
                  echo "+\n";
                  $stuff = true;
               }
               if (ereg('(1|2)',substr($msg['PRIORITY'],0,1))) {
                  echo "<font color=$color[1]>!</font>\n";
                  $stuff = true;
               }
               if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED']) {
                  echo "<font color=\"$color[1]\">D</font>\n";
                  $stuff = true;
               }

               if (!$stuff) echo "&nbsp;\n";
               echo "</small></b></td>\n";
               break;
            case 6: # size
               echo "   <td bgcolor=$hlt_color width=1%>$bold$fontstr".show_readable_size($msg['SIZE'])."$fontstr_end$bold_end</td>\n";
               break;
         }
      }


      echo "</tr>\n";
   }

   /**
    ** This function loops through a group of messages in the mailbox and shows them
    **/
   function showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color,$show_num, $use_cache) {
      global $msgs, $msort;
      global $sent_folder;
      global $message_highlight_list;
      global $auto_expunge;

     if ($auto_expunge == true) sqimap_mailbox_expunge($imapConnection, $mailbox, false);
      sqimap_mailbox_select($imapConnection, $mailbox);

      if (!$use_cache) {
         // if it's sorted
         if ($numMessages >= 1) {
            if ($sort < 6) {
               for ($q = 0; $q < $numMessages; $q++) {
                  if($mailbox == $sent_folder)
                     $hdr = sqimap_get_small_header ($imapConnection, $q+1, true);
                  else
                     $hdr = sqimap_get_small_header ($imapConnection, $q+1, false);
                       
                  $from[$q] = $hdr->from;
                  $date[$q] = $hdr->date;
                  $subject[$q] = $hdr->subject;
                  $to[$q] = $hdr->to;
                  $priority[$q] = $hdr->priority;
                  $cc[$q] = $hdr->cc;
                  $size[$q] = $hdr->size;
                  $type[$q] = $hdr->type0;
                  $flags[$q] = sqimap_get_flags ($imapConnection, $q+1);
                  $id[$q] = $q + 1;
               }
            } else {
               // if it's not sorted
               if ($startMessage + ($show_num - 1) < $numMessages) {
                  $endMessage = $startMessage + ($show_num-1);
               } else {
                  $endMessage = $numMessages;
               }

               if ($endMessage < $startMessage) {
                  $startMessage = $startMessage - $show_num;
                  if ($startMessage < 1)
                     $startMessage = 1;
               }


               $real_startMessage = $numMessages - $startMessage + 1;
               $real_endMessage = $numMessages - $startMessage - $show_num;
               if ($real_endMessage <= 0)
                  $real_endMessage = 1;

               $j = 0;
               for ($q = $real_startMessage; $q >= $real_endMessage; $q--) {
                  if($mailbox == $sent_folder)
                     $hdr = sqimap_get_small_header ($imapConnection, $q, true);
                  else
                     $hdr = sqimap_get_small_header ($imapConnection, $q, false);

                  $from[$j] = $hdr->from;
                  $date[$j] = $hdr->date;
                  $subject[$j] = $hdr->subject;
                  $to[$j] = $hdr->to;
                  $priority[$j] = $hdr->priority;
                  $cc[$j] = $hdr->cc;
                  $size[$j] = $hdr->size;
                  $type[$j] = $hdr->type0;
                  $flags[$j] = sqimap_get_flags ($imapConnection, $q);
                  $id[$j] = $q;
                  $j++;
               }
            }
         }

         $j = 0;
         if ($sort == 6) {
            $end = $startMessage + $show_num - 1;
            if ($numMessages < $show_num)
                $end_loop = $numMessages;
            else
                $end_loop = $show_num;
         } else {
            $end = $numMessages;
            $end_loop = $end;
         }
         if ($end > $numMessages) $end = $numMessages;
         while ($j < $end_loop) {
            if (isset($date[$j])) {
                $date[$j] = ereg_replace('  ', ' ', $date[$j]);
                $tmpdate = explode(' ', trim($date[$j]));
            } else {
                $tmpdate = $date = array("","","","","","");
            }

            $messages[$j]['TIME_STAMP'] = getTimeStamp($tmpdate);
            $messages[$j]['DATE_STRING'] = getDateString($messages[$j]['TIME_STAMP']);
            $messages[$j]['ID'] = $id[$j];
            $messages[$j]['FROM'] = decodeHeader($from[$j]);
            $messages[$j]['FROM-SORT'] = strtolower(sqimap_find_displayable_name(decodeHeader($from[$j])));
            $messages[$j]['SUBJECT'] = decodeHeader($subject[$j]);
            $messages[$j]['SUBJECT-SORT'] = strtolower(decodeHeader($subject[$j]));
            $messages[$j]['TO'] = decodeHeader($to[$j]);
            $messages[$j]['PRIORITY'] = $priority[$j];
            $messages[$j]['CC'] = $cc[$j];
            $messages[$j]['SIZE'] = $size[$j];
            $messages[$j]['TYPE0'] = $type[$j];

            # fix SUBJECT-SORT to remove Re:
            $re_abbr = # Add more here!
               'vedr|sv|' .    # Danish
               're|aw';        # English
            if (eregi("^($re_abbr):[ ]*(.*)$", $messages[$j]['SUBJECT-SORT'], $regs))
               $messages[$j]['SUBJECT-SORT'] = $regs[2];

            $num = 0;
            while ($num < count($flags[$j])) {
               if ($flags[$j][$num] == 'Deleted') {
                  $messages[$j]['FLAG_DELETED'] = true;
               }
               elseif ($flags[$j][$num] == 'Answered') {
                  $messages[$j]['FLAG_ANSWERED'] = true;
               }
               elseif ($flags[$j][$num] == 'Seen') {
                  $messages[$j]['FLAG_SEEN'] = true;
               }
               elseif ($flags[$j][$num] == 'Flagged') {
                  $messages[$j]['FLAG_FLAGGED'] = true;
               }
               $num++;
            }
            $j++;
         }

         /* Only ignore messages flagged as deleted if we are using a
          * trash folder or auto_expunge */
         if (((isset($move_to_trash) && $move_to_trash) 
              || (isset($auto_expunge) && $auto_expunge)) && $sort != 6)
         {
            /** Find and remove the ones that are deleted */
            $i = 0;
            $j = 0;
            while ($j < $numMessages) {
               if (isset($messages[$j]['FLAG_DELETED']) && $messages[$j]['FLAG_DELETED'] == true) {
                  $j++;
                  continue;
               }
               $msgs[$i] = $messages[$j];

               $i++;
               $j++;
            }
            $numMessages = $i;
         } else {
            if (! isset($messages))
                $messages = array();
            $msgs = $messages;
         }
      }         

      // There's gotta be messages in the array for it to sort them.
      if ($numMessages > 0 && ! $use_cache) {
         /** 0 = Date (up)      4 = Subject (up)
          ** 1 = Date (dn)      5 = Subject (dn)
          ** 2 = Name (up)
          ** 3 = Name (dn)
          **/
         session_unregister("msgs");
         if (($sort == 0) || ($sort == 1))
            $msort = array_cleave ($msgs, 'TIME_STAMP');
         if (($sort == 2) || ($sort == 3))
            $msort = array_cleave ($msgs, 'FROM-SORT');
         if (($sort == 4) || ($sort == 5))
            $msort = array_cleave ($msgs, 'SUBJECT-SORT');
         if ($sort == 6)
            $msort = $msgs;

         if ($sort < 6) {
            if($sort % 2) {
               asort($msort);
            } else {
               arsort($msort);
            }
         }
         session_register('msort');
      }
      displayMessageArray($imapConnection, $numMessages, $startMessage, $msgs, $msort, $mailbox, $sort, $color,$show_num);
     session_register('msgs');
   }

   // generic function to convert the msgs array into an HTML table
   function displayMessageArray($imapConnection, $numMessages, $startMessage, &$msgs, $msort, $mailbox, $sort, $color,$show_num) {
      global $folder_prefix, $sent_folder;
      global $imapServerAddress;
      global $index_order, $real_endMessage, $real_startMessage, $checkall;
      
      // if cache isn't already set, do it now
      if (!session_is_registered('msgs'))
         session_register('msgs');
      if (!session_is_registered('msort'))
         session_register('msort');

      if ($startMessage + ($show_num - 1) < $numMessages) {
         $endMessage = $startMessage + ($show_num-1);
      } else {
         $endMessage = $numMessages;
      }

      if ($endMessage < $startMessage) {
         $startMessage = $startMessage - $show_num;
         if ($startMessage < 1)
            $startMessage = 1;
      }

      $nextGroup = $startMessage + $show_num;
      $prevGroup = $startMessage - $show_num;
      $urlMailbox = urlencode($mailbox);

      do_hook('mailbox_index_before');

      $Message = '';
      if ($startMessage < $endMessage) {
         $Message = _("Viewing messages") ." <B>$startMessage</B> ". _("to") ." <B>$endMessage</B> ($numMessages " . _("total") . ")\n";
      } elseif ($startMessage == $endMessage) {
         $Message = _("Viewing message") ." <B>$startMessage</B> ($numMessages " . _("total") . ")\n";
      }

      $More = '';
      if ($sort == 6) {
         $use = 0;
      } else {
         $use = 1;
      }
      if (($nextGroup <= $numMessages) && ($prevGroup >= 0)) {
         $More = "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") ."</A> | \n";
         $More .= "<A HREF=\"right_main.php?use_mailbox_cache=$use&&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
      }
      elseif (($nextGroup > $numMessages) && ($prevGroup >= 0)) {
         $More = "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") ."</A> | \n";
         $More .= "<FONT COLOR=\"$color[9]\">"._("Next")."</FONT>\n";
      }
      elseif (($nextGroup <= $numMessages) && ($prevGroup < 0)) {
         $More = "<FONT COLOR=\"$color[9]\">"._("Previous")."</FONT> | \n";
         $More .= "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
      }

      if (! isset($msg))
          $msg = "";
      mail_message_listing_beginning($imapConnection,
         "move_messages.php?msg=$msg&mailbox=$urlMailbox&startMessage=$startMessage",
          $mailbox, $sort, $Message, $More, $startMessage);

      $groupNum = $startMessage % ($show_num - 1);
      $real_startMessage = $startMessage;
      if ($sort == 6) {
         if ($endMessage - $startMessage < $show_num - 1) {
            $endMessage = $endMessage - $startMessage + 1;
            $startMessage = 1;
         } else if ($startMessage > $show_num) {
            $endMessage = $show_num;
            $startMessage = 1;
         }
      }

      $endVar = $endMessage + 1;

      // loop through and display the info for each message.
      $t = 0; // $t is used for the checkbox number
      if ($numMessages == 0) { // if there's no messages in this folder
         echo "<TR><TD BGCOLOR=\"$color[4]\" COLSPAN=" . count($index_order);
         echo "><CENTER><BR><B>". _("THIS FOLDER IS EMPTY") ."</B><BR>&nbsp;</CENTER></TD></TR>";
      } else if ($startMessage == $endMessage) { // if there's only one message in the box, handle it different.
         if ($sort != 6)
            $i = $startMessage;
         else
            $i = 1;
         reset($msort);
         $k = 0;
         do {
            $key = key($msort);
            next($msort);
            $k++;
         } while (isset ($key) && ($k < $i));
         printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $real_startMessage, 0, 0);
      } else {
         $i = $startMessage;

         reset($msort);
         $k = 0;
         do {
            $key = key($msort);
            next($msort);
            $k++;
         } while (isset ($key) && ($k < $i));

         do {
            printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $real_startMessage, 0, 0);
            $key = key($msort);
            $t++;
            $i++;
            next($msort);
         } while ($i && $i < $endVar);
      }
      echo '</TABLE>';

      echo "</td></tr>\n";

      echo "<TR BGCOLOR=\"$color[4]\"><TD>";
      echo '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>';
      echo "$More</td><td align=right>\n";
      if (!$startMessage) $startMessage=1;
      ShowSelectAllLink($startMessage, $sort);

      echo '</td></tr></table>';
      echo '</td></tr>';
      echo '</table>'; /** End of message-list table */

      do_hook('mailbox_index_after');
   }

   /* Displays the standard message list header.
    * To finish the table, you need to do a "</table></table>";
    * $moveURL is the URL to submit the delete/move form to
    * $mailbox is the current mailbox
    * $sort is the current sorting method (-1 for no sorting available [searches])
    * $Message is a message that is centered on top of the list
    * $More is a second line that is left aligned
    */
   function mail_message_listing_beginning($imapConnection, $moveURL,
       $mailbox = '', $sort = -1, $Message = '', $More = '', $startMessage = 1)
   {
      global $color, $index_order, $auto_expunge, $move_to_trash;
      global $checkall, $sent_folder;
      $urlMailbox = urlencode($mailbox);

         /** This is the beginning of the message list table.  It wraps around all messages */
      echo '<TABLE WIDTH="100%" BORDER="0" CELLPADDING="2" CELLSPACING="0">';

      if ($Message)
      {
         echo "<TR BGCOLOR=\"$color[4]\"><TD align=center>$Message</td></tr>\n";
      }

      echo "<TR BGCOLOR=\"$color[4]\"><TD>";
      echo '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>';
      echo "$More</td><td align=right>\n";
      ShowSelectAllLink($startMessage, $sort);

      echo '</td></tr></table>';
      echo '</td></tr>';

      /** The delete and move options */
      echo "<TR><TD BGCOLOR=\"$color[0]\">";

      echo "\n\n\n<FORM name=messageList method=post action=\"$moveURL\">\n";
      echo "<TABLE BGCOLOR=\"$color[0]\" COLS=2 BORDER=0 cellpadding=0 cellspacing=0 width=100%>\n";
      echo "   <TR>\n";
      echo "      <TD WIDTH=60% ALIGN=LEFT VALIGN=CENTER>\n";


      echo '         <NOBR><SMALL>'. _("Move selected to:") .'</SMALL>';
      echo '         <TT><SMALL><SELECT NAME="targetMailbox">';

      $boxes = sqimap_mailbox_list($imapConnection);
      for ($i = 0; $i < count($boxes); $i++) {
         if (!in_array("noselect", $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = replace_spaces($boxes[$i]['unformatted-disp']);
            echo "         <OPTION VALUE=\"$box\">$box2</option>\n";
         }
      }
      echo '         </SELECT></SMALL></TT>';
      echo '         <SMALL><INPUT TYPE=SUBMIT NAME="moveButton" VALUE="'. _("Move") ."\"></SMALL></NOBR>\n";
      echo "      </TD>\n";
      echo "      <TD WIDTH=40% ALIGN=RIGHT>\n";
      if (! $auto_expunge) {
         echo '         <NOBR><SMALL><INPUT TYPE=SUBMIT NAME="expungeButton" VALUE="'. _("Expunge") .'">&nbsp;'. _("mailbox") ."</SMALL></NOBR>&nbsp;&nbsp;\n";
      }
      echo "         <NOBR><SMALL><INPUT TYPE=SUBMIT VALUE=\"". _("Delete") ."\">&nbsp;". _("checked messages") ."</SMALL></NOBR>\n";
      echo "      </TD>\n";
      echo "   </TR>\n";

      echo "</TABLE>\n";
      do_hook('mailbox_form_before');
      echo '</TD></TR>';

      echo "<TR><TD BGCOLOR=\"$color[0]\">";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=\"$color[0]\">";
      echo "<TR BGCOLOR=\"$color[5]\" ALIGN=\"center\">";

      $urlMailbox=urlencode($mailbox);

      // Print the headers
      for ($i=1; $i <= count($index_order); $i++) {
         switch ($index_order[$i]) {
            case 1: # checkbox
            case 5: # flags
               echo '   <TD WIDTH="1%"><B>&nbsp;</B></TD>';
               break;
               
            case 2: # from
               if ($mailbox == $sent_folder)
                  echo '   <TD WIDTH="30%"><B>'. _("To") .'</B>';
               else
                    echo '   <TD WIDTH="30%"><B>'. _("From") .'</B>';
         
               if ($sort == 2)
                  echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort == 3)
                  echo "   <A HREF=\"right_main.php?newsort=2&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort != -1)
                  echo "   <A HREF=\"right_main.php?newsort=3&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
               echo "</TD>";
               break;
               
            case 3: # date
               echo '   <TD nowrap WIDTH="1%"><B>'. _("Date") .'</B>';
               if ($sort == 0)
                  echo "   <A HREF=\"right_main.php?newsort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort == 1)
                  echo "   <A HREF=\"right_main.php?newsort=6&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort == 6)
                  echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
               elseif ($sort != -1)
                  echo "   <A HREF=\"right_main.php?newsort=0&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
               echo '</TD>';
               break;
               
            case 4: # subject
               echo '   <TD WIDTH=%><B>'. _("Subject") ."</B>\n";
               if ($sort == 4)
                 echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort == 5)
                  echo "   <A HREF=\"right_main.php?newsort=4&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
               elseif ($sort != -1)
                  echo "   <A HREF=\"right_main.php?newsort=5&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/sort_none.gif\" BORDER=0></A></TD>\n";
               echo "</TD>";
               break;
               
            case 6: # size   
               echo '   <TD WIDTH="1%"><b>' . _("Size")."</b></TD>\n";
               break;
         }
      }
      echo "</TR>\n";
   }
   
   function ShowSelectAllLink($startMessage, $sort)
   {
       global $checkall, $PHP_SELF, $what, $where, $mailbox;
       
       echo "\n<A HREF=\"$PHP_SELF?mailbox=" . urlencode($mailbox) .
           "&startMessage=$startMessage&sort=$sort&";
       if ( isset($checkall) && $checkall == '1')
           echo "checkall=0";
       else
           echo "checkall=1";
       if (isset($where) && isset($what))
           echo "&where=" . urlencode($where) . "&what=" . urlencode($what);
       echo "\">";
       if (isset($checkall) && $checkall == '1')
           echo _("Unselect All");
       else
           echo _("Select All");
       echo "</A>\n";
   }

   function processSubject($subject)
   {
      // Shouldn't ever happen -- caught too many times in the IMAP functions
      if ($subject == '')
          return _("(no subject)");
	  
      if (strlen($subject) <= 55)
          return $subject;
	  
      $ent_strlen=strlen($subject);
      $trim_val=50;
      $ent_offset=0;
      // see if this is entities-encoded string
      // If so, Iterate through the whole string, find out
      // the real number of characters, and if more
      // than 55, substr with an updated trim value.
      while (($ent_loc = strpos($subject, '&', $ent_offset)) !== false &&
             ($ent_loc_end = strpos($subject, ';', $ent_loc)) !== false)
      {
	 $trim_val += ($ent_loc_end-$ent_loc)+1;
	 $ent_strlen -= $ent_loc_end-$ent_loc;
	 $ent_offset = $ent_loc_end+1;
      }
      
      if ($ent_strlen <= 55)
          return $subject;

      return substr($subject, 0, $trim_val) . '...';
   }

?>
