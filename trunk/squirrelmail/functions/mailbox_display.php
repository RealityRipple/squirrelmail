<?php
   /**
    * mailbox_display.php
    *
    * Copyright (c) 1999-2001 The Squirrelmail Development Team
    * Licensed under the GNU GPL. For full terms see the file COPYING.
    *
    * This contains functions that display mailbox information, such as the
    * table row that has sender, date, subject, etc...
    *
    * $Id$
    */

   if (defined('mailbox_display_php'))
       return;
   define('mailbox_display_php', true);

   define('PG_SEL_MAX', 10);  /* Default value for page_selector_max. */

   function printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $start_msg, $where, $what) {
      global $checkall;
      global $color, $msgs, $msort;
      global $sent_folder, $draft_folder;
      global $default_use_priority;
      global $message_highlight_list;
      global $index_order;

      $color_string = $color[4];
      if ($GLOBALS['alt_index_colors']) {
          if (!isset($GLOBALS["row_count"])) {
            $GLOBALS["row_count"] = 0;
          }
          $GLOBALS["row_count"]++;
          if ($GLOBALS["row_count"] % 2) {
          if (!isset($color[12])) $color[12] = '#EAEAEA';
            $color_string = $color[12];
          }
      }
      $msg = $msgs[$key];

      $senderName = htmlspecialchars(sqimap_find_displayable_name($msg['FROM']));
      if( $mailbox == _("None") ) {
         // $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
         $boxes = sqimap_mailbox_list($imapConnection);
         // sqimap_logout($imapConnection);
         $mailbox = $boxes[0]['unformatted'];
         unset( $boxes );
      }
      $urlMailbox = urlencode($mailbox);
      $subject = processSubject($msg['SUBJECT']);
      echo "<TR>\n";

      if (isset($msg['FLAG_FLAGGED']) && ($msg['FLAG_FLAGGED'] == true)) {
         $flag = "<font color=$color[2]>";
         $flag_end = '</font>';
      } else {
         $flag = '';
         $flag_end = '';
      }
      if (!isset($msg['FLAG_SEEN']) || ($msg['FLAG_SEEN'] == false)) {
         $bold = '<b>';
         $bold_end = '</b>';
      } else {
         $bold = '';
         $bold_end = '';
      }

      if (($mailbox == $sent_folder) || ($mailbox == $draft_folder)) {
         $italic = '<i>';
         $italic_end = '</i>';
      } else {
         $italic = '';
         $italic_end = '';
      }

      if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED']) {
         $fontstr = "<font color=\"$color[9]\">";
         $fontstr_end = '</font>';
      } else {
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

      if (!isset($hlt_color)) {
          $hlt_color = $color_string;
      }

      if ($where && $what) {
         $search_stuff = '&where='.urlencode($where).'&what='.urlencode($what);
      }

      $checked = ($checkall == 1 ?' checked' : '');

      for ($i=1; $i <= count($index_order); $i++) {
         switch ($index_order[$i]) {
            case 1: /* checkbox */
               echo "   <td bgcolor=$hlt_color align=center><input type=checkbox name=\"msg[$t]\" value=".$msg["ID"]."$checked></TD>\n";
               break;
            case 2: /* from */
               echo "   <td bgcolor=$hlt_color>$italic$bold$flag$fontstr$senderName$fontstr_end$flag_end$bold_end$italic_end</td>\n";
               break;
            case 3: /* date */
               echo "   <td nowrap bgcolor=$hlt_color><center>$bold$flag$fontstr".$msg["DATE_STRING"]."$fontstr_end$flag_end$bold_end</center></td>\n";
               break;
            case 4: /* subject */
               echo "   <td bgcolor=$hlt_color>$bold";
                   if (! isset($search_stuff)) { $search_stuff = ''; }
               echo "<a href=\"read_body.php?mailbox=$urlMailbox&passed_id=".$msg["ID"]."&startMessage=$start_msg&show_more=0$search_stuff\"";
               do_hook("subject_link");

               if ($subject != $msg['SUBJECT']) {
                  $title = get_html_translation_table(HTML_SPECIALCHARS);
                  $title = array_flip($title);
                  $title = strtr($msg['SUBJECT'], $title);
                  $title = str_replace('"', "''", $title);
                  echo " title=\"$title\"";
               }
               echo ">$flag$subject$flag_end</a>$bold_end</td>\n";
               break;
            case 5: /* flags */
               $stuff = false;
               echo "   <td bgcolor=$hlt_color align=center nowrap><b><small>\n";
               if (isset($msg['FLAG_ANSWERED']) &&
                   $msg['FLAG_ANSWERED'] == true) {
                  echo "A\n";
                  $stuff = true;
               }
               if ($msg['TYPE0'] == 'multipart') {
                  echo "+\n";
                  $stuff = true;
               }
               if ($default_use_priority) {
                  if (ereg('(1|2)',substr($msg['PRIORITY'],0,1))) {
                     echo "<font color=$color[1]>!</font>\n";
                     $stuff = true;
                  }
                  if (ereg('(5)',substr($msg['PRIORITY'],0,1))) {
                     echo "<font color=$color[8]>?</font>\n";
                     $stuff = true;
                  }
               }
               if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED']) {
                  echo "<font color=\"$color[1]\">D</font>\n";
                  $stuff = true;
               }

               if (!$stuff) echo "&nbsp;\n";
               echo "</small></b></td>\n";
               break;
            case 6: /* size */
               echo "   <td bgcolor=$hlt_color>$bold$fontstr".show_readable_size($msg['SIZE'])."$fontstr_end$bold_end</td>\n";
               break;
         }
      }
      echo "</tr>\n";
   }

   /**
    * This function loops through a group of messages in the mailbox
    * and shows them to the user.
    */
   function showMessagesForMailbox
        ($imapConnection, $mailbox, $num_msgs, $start_msg,
         $sort, $color,$show_num, $use_cache) {
      global $msgs, $msort;
      global $sent_folder, $draft_folder;
      global $message_highlight_list;
      global $auto_expunge;

      /* If autoexpunge is turned on, then do it now. */
      if ($auto_expunge == true) {
          sqimap_mailbox_expunge($imapConnection, $mailbox, false);
      }
      sqimap_mailbox_select($imapConnection, $mailbox);

      $issent = (($mailbox == $sent_folder) || ($mailbox == $draft_folder));
      if (!$use_cache) {
         /* If it is sorted... */
         if ($num_msgs >= 1) {
            if ($sort < 6) {
               $id = range(1, $num_msgs);
            } else {
               // if it's not sorted
               if ($start_msg + ($show_num - 1) < $num_msgs) {
                  $end_msg = $start_msg + ($show_num-1);
               } else {
                  $end_msg = $num_msgs;
               }

               if ($end_msg < $start_msg) {
                  $start_msg = $start_msg - $show_num;
                  if ($start_msg < 1) {
                      $start_msg = 1;
                  }
               }

               $real_startMessage = $num_msgs - $start_msg + 1;
               $real_endMessage = $num_msgs - $start_msg - $show_num + 2;
               if ($real_endMessage <= 0) {
                   $real_endMessage = 1;
               }
               $id = array_reverse(range($real_endMessage, $real_startMessage));
            }

            $msgs_list = sqimap_get_small_header_list($imapConnection, $id, $issent);
            $flags = sqimap_get_flags_list($imapConnection, $id, $issent);
            foreach ($msgs_list as $hdr) {
               $from[] = $hdr->from;
               $date[] = $hdr->date;
               $subject[] = $hdr->subject;
               $to[] = $hdr->to;
               $priority[] = $hdr->priority;
               $cc[] = $hdr->cc;
               $size[] = $hdr->size;
               $type[] = $hdr->type0;
            }
         }

         $j = 0;
         if ($sort == 6) {
             $end = $start_msg + $show_num - 1;
             if ($num_msgs < $show_num) {
                 $end_loop = $num_msgs;
             } else if ($end > $num_msgs) {
                 $end_loop = $num_msgs - $start_msg + 1;
             } else {
                 $end_loop = $show_num;
             }
         } else {
            $end = $num_msgs;
            $end_loop = $end;
         }

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

            if (eregi( "^($re_abbr):[ ]*(.*)$", $messages[$j]['SUBJECT-SORT'], $regs))
               $messages[$j]['SUBJECT-SORT'] = $regs[2];

            $num = 0;
            while ($num < count($flags[$j])) {
               if ($flags[$j][$num] == 'Deleted') {
                   $messages[$j]['FLAG_DELETED'] = true;
               } else if ($flags[$j][$num] == 'Answered') {
                   $messages[$j]['FLAG_ANSWERED'] = true;
               } else if ($flags[$j][$num] == 'Seen') {
                   $messages[$j]['FLAG_SEEN'] = true;
               } else if ($flags[$j][$num] == 'Flagged') {
                   $messages[$j]['FLAG_FLAGGED'] = true;
               }
               $num++;
            }
            $j++;
         }

         /* Only ignore messages flagged as deleted if we are using a
          * trash folder or auto_expunge */
         if (((isset($move_to_trash) && $move_to_trash)
              || (isset($auto_expunge) && $auto_expunge)) && $sort != 6) {

            /** Find and remove the ones that are deleted */
            $i = 0;
            $j = 0;

            while ($j < $num_msgs) {
               if (isset($messages[$j]['FLAG_DELETED']) && $messages[$j]['FLAG_DELETED'] == true) {
                  $j++;
                  continue;
               }
               $msgs[$i] = $messages[$j];

               $i++;
               $j++;
            }
            $num_msgs = $i;
         } else {
             if (!isset($messages)) {
                 $messages = array();
             }
             $msgs = $messages;
         }
      }

      // There's gotta be messages in the array for it to sort them.
      if ($num_msgs > 0 && ! $use_cache) {
         /** 0 = Date (up)      4 = Subject (up)
          ** 1 = Date (dn)      5 = Subject (dn)
          ** 2 = Name (up)
          ** 3 = Name (dn)
          **/
         session_unregister("msgs");
         if (($sort == 0) || ($sort == 1))
            $msort = array_cleave ($msgs, 'TIME_STAMP');
         elseif (($sort == 2) || ($sort == 3))
            $msort = array_cleave ($msgs, 'FROM-SORT');
         elseif (($sort == 4) || ($sort == 5))
            $msort = array_cleave ($msgs, 'SUBJECT-SORT');
         else // ($sort == 6)
            $msort = $msgs;

         if ($sort < 6) {
            if ($sort % 2) {
               asort($msort);
            } else {
               arsort($msort);
            }
         }
         session_register('msort');
      }
      displayMessageArray($imapConnection, $num_msgs, $start_msg, $msgs, $msort, $mailbox, $sort, $color,$show_num);
      session_register('msgs');
   }

   /******************************************************************/
   /* Generic function to convert the msgs array into an HTML table. */
   /******************************************************************/
   function displayMessageArray($imapConnection, $num_msgs, $start_msg, &$msgs, $msort, $mailbox, $sort, $color, $show_num) {
      global $folder_prefix, $sent_folder;
      global $imapServerAddress, $data_dir, $username, $use_mailbox_cache;
      global $index_order, $real_endMessage, $real_startMessage, $checkall;

      /* If cache isn't already set, do it now. */
      if (!session_is_registered('msgs')) { session_register('msgs'); }
      if (!session_is_registered('msort')) { session_register('msort'); }

      if ($start_msg + ($show_num - 1) < $num_msgs) {
         $end_msg = $start_msg + ($show_num-1);
      } else {
         $end_msg = $num_msgs;
      }

      if ($end_msg < $start_msg) {
         $start_msg = $start_msg - $show_num;
         if ($start_msg < 1) { $start_msg = 1; }
      }

      $urlMailbox = urlencode($mailbox);

      do_hook('mailbox_index_before');

      $msg_cnt_str = get_msgcnt_str($start_msg, $end_msg, $num_msgs);
      $paginator_str = get_paginator_str($urlMailbox, $start_msg, $end_msg, $num_msgs, $show_num, $sort);

      if (! isset($msg)) {
          $msg = '';
      }

      mail_message_listing_beginning
         ($imapConnection,
         "move_messages.php?msg=$msg&mailbox=$urlMailbox&startMessage=$start_msg",
          $mailbox, $sort, $msg_cnt_str, $paginator_str, $start_msg);

      $groupNum = $start_msg % ($show_num - 1);
      $real_startMessage = $start_msg;
      if ($sort == 6) {
         if ($end_msg - $start_msg < $show_num - 1) {
            $end_msg = $end_msg - $start_msg + 1;
            $start_msg = 1;
         } else if ($start_msg > $show_num) {
            $end_msg = $show_num;
            $start_msg = 1;
         }
      }
      $endVar = $end_msg + 1;

      /* Loop through and display the info for each message. */
      $t = 0; // $t is used for the checkbox number
      if ($num_msgs == 0) { // if there's no messages in this folder
          echo "<TR><TD BGCOLOR=\"$color[4]\" COLSPAN=" . count($index_order) . ">\n";
          echo "  <CENTER><BR><B>". _("THIS FOLDER IS EMPTY") ."</B><BR>&nbsp;</CENTER>\n";
          echo "</TD></TR>";
      } else if ($start_msg == $end_msg) {
          /* If there's only one message in the box, handle it differently. */
          if ($sort != 6) {
              $i = $start_msg;
          } else {
              $i = 1;
          }

          reset($msort);
          $k = 0;
          do {
              $key = key($msort);
              next($msort);
              $k++;
          } while (isset ($key) && ($k < $i));
          printMessageInfo($imapConnection, $t, $i, $key, $mailbox, $sort, $real_startMessage, 0, 0);
      } else {
          $i = $start_msg;

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

      echo '</table>';
      echo "<table bgcolor=\"$color[9]\" width=100% border=0 cellpadding=1 cellspacing=1>" .
              "<tr BGCOLOR=\"$color[4]\">" .
                 "<table width=100% BGCOLOR=\"$color[4]\" border=0 cellpadding=1 cellspacing=0><tr><td>$paginator_str</td>".
                 "<td align=right>$msg_cnt_str</td></tr></table>".
              "</tr>".
           "</table>";
      /** End of message-list table */

      do_hook('mailbox_index_after');
      echo "</TABLE></FORM>\n";
   }

   /**
    * Displays the standard message list header. To finish the table,
    * you need to do a "</table></table>";
    *
    * $moveURL is the URL to submit the delete/move form to
    * $mailbox is the current mailbox
    * $sort is the current sorting method (-1 for no sorting available [searches])
    * $Message is a message that is centered on top of the list
    * $More is a second line that is left aligned
    */
   function mail_message_listing_beginning
        ($imapConnection, $moveURL, $mailbox = '', $sort = -1,
         $msg_cnt_str = '', $paginator = '&nbsp;', $start_msg = 1) {
      global $color, $index_order, $auto_expunge, $move_to_trash;
      global $checkall, $sent_folder, $draft_folder;
      $urlMailbox = urlencode($mailbox);

      /****************************************************
       * This is the beginning of the message list table. *
       * It wraps around all messages                     *
       ****************************************************/
      echo "<TABLE WIDTH=\"100%\" BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"0\">\n";

      echo "<TR BGCOLOR=\"$color[0]\"><TD>";
      echo "  <TABLE BGCOLOR=\"$color[4]\" width=\"100%\" CELLPADDING=\"2\" CELLSPACING=\"0\" BORDER=\"0\"><TR>\n";
      echo "    <TD ALIGN=LEFT>$paginator</TD>\n";
      echo '    <TD ALIGN=CENTER>' . get_selectall_link($start_msg, $sort) . "</TD>\n";
      echo "    <TD ALIGN=RIGHT>$msg_cnt_str</TD>\n";
      echo "  </TR></TABLE>\n";
      echo "</TD></TR>";

      /** The delete and move options */
      echo "<TR><TD BGCOLOR=\"$color[0]\">";

      echo "\n<FORM name=messageList method=post action=\"$moveURL\">\n";
      echo "<TABLE BGCOLOR=\"$color[0]\" COLS=2 BORDER=0 cellpadding=0 cellspacing=0 width=100%>\n";

      echo "   <TR>\n" .
           "      <TD ALIGN=LEFT VALIGN=CENTER NOWRAP>\n" .
           '         <SMALL>&nbsp;' . _("Move selected to:") . "</SMALL>\n" .
           "      </TD>\n" .
           "      <TD ALIGN=RIGHT NOWRAP>\n" .
           '         <SMALL>&nbsp;' . _("Transform Selected Messages") . ": &nbsp; </SMALL><BR>\n" .
           "      </TD>\n" .
           "   </TR>\n" .
           "   <TR>\n" .
           "      <TD ALIGN=LEFT VALIGN=CENTER NOWRAP>\n" .
           '         <SMALL>&nbsp;<TT><SELECT NAME="targetMailbox">';

      $boxes = sqimap_mailbox_list($imapConnection);
      for ($i = 0; $i < count($boxes); $i++) {
         if (!in_array("noselect", $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            echo "         <OPTION VALUE=\"$box\">$box2</option>\n";
         }
      }
      echo '         </SELECT></TT></SMALL>';
      echo "         <SMALL><INPUT TYPE=SUBMIT NAME=\"moveButton\" VALUE=\"" . _("Move") . "\"></SMALL>\n";
      echo "      </TD>\n";
      echo "      <TD ALIGN=RIGHT NOWRAP>&nbsp;&nbsp;&nbsp;\n";
      if (!$auto_expunge) {
         echo '         <INPUT TYPE=SUBMIT NAME="expungeButton" VALUE="'. _("Expunge") .'">&nbsp;'. _("mailbox") ."&nbsp;\n";
      }
      echo "         <INPUT TYPE=SUBMIT NAME=\"markRead\" VALUE=\"". _("Read")."\">\n";
      echo "         <INPUT TYPE=SUBMIT NAME=\"markUnread\" VALUE=\"". _("Unread")."\">\n";
      echo "         <INPUT TYPE=SUBMIT VALUE=\"". _("Delete") . "\">&nbsp;\n";
      echo "      </TD>\n";
      echo "   </TR>\n";
      echo "</TABLE>\n";
      do_hook('mailbox_form_before');
      echo '</TD></TR>';

      echo "<TR><TD BGCOLOR=\"$color[0]\">";
      echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=";
      if ($GLOBALS['alt_index_colors']) {
        echo "0";
      } else {
        echo "1";
      }
      echo " BGCOLOR=\"$color[0]\">";
      echo "<TR BGCOLOR=\"$color[5]\" ALIGN=\"center\">";

      /* Print the headers. */
      for ($i=1; $i <= count($index_order); $i++) {
         switch ($index_order[$i]) {
            case 1: /* checkbox */
            case 5: /* flags */
               echo '   <TD WIDTH="1%"><B>&nbsp;</B></TD>';
               break;

            case 2: /* from */
               if (($mailbox == $sent_folder)
                     || ($mailbox == $draft_folder)) {
                  echo '   <TD WIDTH="25%"><B>'. _("To") .'</B>';
               } else {
                   echo '   <TD WIDTH="25%"><B>'. _("From") .'</B>';
               }

           ShowSortButton($sort, $mailbox, 2, 3);
               echo "</TD>\n";
               break;

            case 3: /* date */
               echo '   <TD NOWRAP WIDTH="5%"><B>'. _("Date") .'</B>';
               ShowSortButton($sort, $mailbox, 0, 1);
               echo "</TD>\n";
               break;

            case 4: /* subject */
               echo '   <TD><B>'. _("Subject") .'</B> ';
               ShowSortButton($sort, $mailbox, 4, 5);
               echo "</TD>\n";
               break;

            case 6: /* size */
               echo '   <TD WIDTH="5%"><b>' . _("Size")."</b></TD>\n";
               break;
         }
      }
      echo "</TR>\n";
   }

   /*******************************************************************/
   /* This function shows the sort button. Isn't this a good comment? */
   /*******************************************************************/
   function ShowSortButton($sort, $mailbox, $Up, $Down) {
      /* Figure out which image we want to use. */
      if ($sort != $Up && $sort != $Down) {
         $img = 'sort_none.gif';
         $which = $Up;
      } elseif ($sort == $Up) {
         $img = 'up_pointer.gif';
         $which = $Down;
      } else {
         $img = 'down_pointer.gif';
         $which = 6;
      }

      /* Now that we have everything figured out, show the actual button. */
      echo ' <a href="right_main.php?newsort=' . $which .
           '&startMessage=1&mailbox=' . urlencode($mailbox) .
           '"><IMG SRC="../images/' . $img .
           '" BORDER=0 WIDTH=12 HEIGHT=10></a>';
   }

   function get_selectall_link($start_msg, $sort) {
       global $checkall, $PHP_SELF, $what, $where, $mailbox;

       $result =
            '&nbsp;<script language="JavaScript">' .
            "\n<!-- \n" .
            "function CheckAll() {\n" .
            "   for (var i = 0; i < document.messageList.elements.length; i++) {\n" .
            "       if( document.messageList.elements[i].type == 'checkbox' ) {\n" .
            "           document.messageList.elements[i].checked = !(document.messageList.elements[i].checked);\n".
            "       }\n" .
            "   }\n" .
            "}\n" .
            'window.document.write(\'<a href=# onClick="CheckAll();">' . _("Toggle All") . "</a>');\n" .
            "//-->\n" .
            "</script>\n<noscript>\n";

       $result .= "<a href=\"$PHP_SELF?mailbox=" . urlencode($mailbox)
          .  "&startMessage=$start_msg&sort=$sort&checkall=";
       if (isset($checkall) && $checkall == '1') {
           $result .= '0';
       } else {
           $result .= '1';
       }

       if (isset($where) && isset($what)) {
           $result .= '&where=' . urlencode($where) . '&what=' . urlencode($what);
       }

       $result .= "\">";

       if (isset($checkall) && ($checkall == '1')) {
           $result .= _("Unselect All");
       } else {
           $result .= _("Select All");
       }

       $result .= "</A>\n</noscript>\n";

       /* Return our final result. */
       return ($result);
   }

    /**
     * This function computes the "Viewing Messages..." string.
     */
    function get_msgcnt_str($start_msg, $end_msg, $num_msgs) {
        /* Compute the $msg_cnt_str. */
        $result = '';
        if ($start_msg < $end_msg) {
            $result = sprintf(_("Viewing Messages: <B>%s</B> to <B>%s</B> (%s total)"), $start_msg, $end_msg, $num_msgs);
        } else if ($start_msg == $end_msg) {
            $result = sprintf(_("Viewing Message: <B>%s</B> (1 total)"), $start_msg);
        } else {
            $result = '<br>';
        }

        /* Return our result string. */
        return ($result);
    }

    /**
     * This function computes the paginator string.
     */
    function get_paginator_str
    ($urlMailbox, $start_msg, $end_msg, $num_msgs, $show_num, $sort) {
        global $username, $data_dir, $use_mailbox_cache, $color;

        $nextGroup = $start_msg + $show_num;
        $prevGroup = $start_msg - $show_num;

        if ($sort == 6) {
            $use = 0;
        } else {
            $use = 1;
        }
        $lMore = '';
        $rMore = '';
        if (($nextGroup <= $num_msgs) && ($prevGroup >= 0)) {
            $lMore = "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") . '</A>';
            $rMore = "<A HREF=\"right_main.php?use_mailbox_cache=$use&&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
        } else if (($nextGroup > $num_msgs) && ($prevGroup >= 0)) {
            $lMore = "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$prevGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Previous") . '</A>';
            $rMore = "<FONT COLOR=\"$color[9]\">"._("Next")."</FONT>\n";
        } else if (($nextGroup <= $num_msgs) && ($prevGroup < 0)) {
            $lMore = "<FONT COLOR=\"$color[9]\">"._("Previous") . '</FONT>';
            $rMore = "<A HREF=\"right_main.php?use_mailbox_cache=$use&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\">". _("Next") ."</A>\n";
        }
        if ($lMore <> '') {
            $lMore .= '&nbsp;|&nbsp;';
        }

        /* Page selector block. Following code computes page links. */
        $mMore = '';
        if (!getPref($data_dir, $username, 'page_selector')
               && ($num_msgs > $show_num)) {
            $j = intval( $num_msgs / $show_num );  // Max pages
            $k = max( 1, $j / getPref($data_dir, $username, 'page_selector_max', PG_SEL_MAX ) );
            if ($num_msgs % $show_num <> 0 ) {
                $j++;
            }
            $start_msg = min( $start_msg, $num_msgs );
            $p = intval( $start_msg / $show_num ) + 1;
            $i = 1;
            while( $i < $p ) {
                $pg = intval( $i );
                $start = ( ($pg-1) * $show_num ) + 1;
                $mMore .= "<a href=\"right_main.php?use_mailbox_cache=$use_mailbox_cache&startMessage=$start" .
                          "&mailbox=$urlMailbox\" TARGET=\"right\">$pg</a>&nbsp;";
                $i += $k;
            }
            $mMore .= "<B>$p</B>&nbsp;";
            $i += $k;
            while( $i <= $j ) {
               $pg = intval( $i );
               $start = ( ($pg-1) * $show_num ) + 1;
               $mMore .= "<a href=\"right_main.php?use_mailbox_cache=$use_mailbox_cache&startMessage=$start"
                       . "&mailbox=$urlMailbox\" TARGET=\"right\">$pg</a>&nbsp;";
               $i+=$k;
            }
            $mMore .= '&nbsp;|&nbsp;';
        }

        /* Return the resulting string. */
        if( $lMore . $mMore . $rMore == '' ) {
            $lMore = '&nbsp;';
        }
        return ($lMore . $mMore . $rMore);
    }

   function processSubject($subject) {
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
