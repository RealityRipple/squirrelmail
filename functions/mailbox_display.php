<?php

/**
 * mailbox_display.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions that display mailbox information, such as the
 * table row that has sender, date, subject, etc...
 *
 * $Id$
 */

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
    if( $mailbox == 'None' ) {
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
        $flag = "<font color=\"$color[2]\">";
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

    if (handleAsSent($mailbox)) {
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
            echo "   <td bgcolor=\"$hlt_color\" align=center><input type=checkbox name=\"msg[$t]\" value=".$msg["ID"]."$checked></TD>\n";
            break;
        case 2: /* from */
            echo "   <td bgcolor=\"$hlt_color\">$italic$bold$flag$fontstr$senderName$fontstr_end$flag_end$bold_end$italic_end</td>\n";
            break;
        case 3: /* date */
            echo "   <td nowrap bgcolor=\"$hlt_color\"><center>$bold$flag$fontstr".$msg["DATE_STRING"]."$fontstr_end$flag_end$bold_end</center></td>\n";
            break;
        case 4: /* subject */
            echo "   <td bgcolor=\"$hlt_color\">$bold";
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
            echo "   <td bgcolor=\"$hlt_color\" align=center nowrap><b><small>\n";
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
                    echo "<font color=\"$color[1]\">!</font>\n";
                    $stuff = true;
                }
                if (ereg('(5)',substr($msg['PRIORITY'],0,1))) {
                    echo "<font color=\"$color[8]\">?</font>\n";
                    $stuff = true;
                }
            }
            if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED']) {
                echo "<font color=\"$color[1]\">D</font>\n";
                $stuff = true;
            }

            if (!$stuff) {
                echo "&nbsp;\n";
            }
            echo "</small></b></td>\n";
            break;
        case 6: /* size */
            echo "   <td bgcolor=\"$hlt_color\">$bold$fontstr" .
                 show_readable_size($msg['SIZE']) .
                 "$fontstr_end$bold_end</td>\n";
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

    $issent = handleAsSent($mailbox);
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
                $tmpdate = $date = array('', '', '', '', '', '');
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
            $re_abbr =                  /* Add more here! */
                        'vedr|sv|' .    /* Danish         */
                        're|aw';        /* English        */

            if (eregi( "^($re_abbr):[ ]*(.*)$", $messages[$j]['SUBJECT-SORT'], $regs)) {
                $messages[$j]['SUBJECT-SORT'] = $regs[2];
            }

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

/**
 * Generic function to convert the msgs array into an HTML table.
 */
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
    $paginator_str = get_paginator_str
        ($urlMailbox, $start_msg, $end_msg, $num_msgs, $show_num, $sort);

    if (! isset($msg)) {
        $msg = '';
    }

    mail_message_listing_beginning( $imapConnection,
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
        echo "<TR><TD BGCOLOR=\"$color[4]\" COLSPAN=" . count($index_order) . ">\n".
            "  <CENTER><BR><B>". _("THIS FOLDER IS EMPTY") ."</B><BR>&nbsp;</CENTER>\n".
            "</TD></TR>";
    } elseif ($start_msg == $end_msg) {
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

    echo '</table>'.
        "<table bgcolor=\"$color[9]\" width=\"100%\" border=0 cellpadding=1 cellspacing=1>" .
            "<tr BGCOLOR=\"$color[4]\"><td>" .
                "<table width=\"100%\" BGCOLOR=\"$color[4]\" border=0 cellpadding=1 cellspacing=0><tr><td>$paginator_str</td>".
                "<td align=right>$msg_cnt_str</td></tr></table>".
            "</td></tr>".
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

    /*
    * This is the beginning of the message list table.
    * It wraps around all messages
    */
    echo "<FORM name=messageList method=post action=\"$moveURL\">\n"
       . "<TABLE WIDTH=\"100%\" BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"0\">\n"
       . "<TR BGCOLOR=\"$color[0]\"><TD>"
       . "    <TABLE BGCOLOR=\"$color[4]\" width=\"100%\" CELLPADDING=\"2\" CELLSPACING=\"0\" BORDER=\"0\"><TR>\n"
       . "    <TD ALIGN=LEFT>$paginator</TD>\n"
       . "    <TD ALIGN=RIGHT>$msg_cnt_str</TD>\n"
       . "  </TR></TABLE>\n"
       . '</TD></TR>'
       . "<TR><TD BGCOLOR=\"$color[0]\">\n"
       . "<TABLE BGCOLOR=\"$color[0]\" COLS=2 BORDER=0 cellpadding=0 cellspacing=0 width=\"100%\">\n"
       . "   <TR>\n"
       . "      <TD ALIGN=LEFT VALIGN=MIDDLE NOWRAP>\n"
       . '         <SMALL>&nbsp;' . _("Move Selected To:") . "</SMALL>\n"
       . "      </TD>\n"
       . "      <TD ALIGN=RIGHT NOWRAP>\n"
       . '         <SMALL>&nbsp;' . _("Transform Selected Messages") . ": &nbsp; </SMALL><BR>\n"
       . "      </TD>\n"
       . "   </TR>\n"
       . "   <TR>\n"
       . "      <TD ALIGN=LEFT VALIGN=MIDDLE NOWRAP>\n"
       . '         <SMALL>&nbsp;<TT><SELECT NAME="targetMailbox">';

    $boxes = sqimap_mailbox_list($imapConnection);
    for ($i = 0; $i < count($boxes); $i++) {
        if (!in_array("noselect", $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            echo "         <OPTION VALUE=\"$box\">$box2</option>\n";
        }
    }
    echo '         </SELECT></TT>&nbsp;'.
         "<INPUT TYPE=SUBMIT NAME=\"moveButton\" VALUE=\"" . _("Move") . "\"></SMALL>\n".
         "      </TD>\n".
         "      <TD ALIGN=RIGHT NOWRAP>&nbsp;&nbsp;&nbsp;\n";
    if (!$auto_expunge) {
        echo '         <INPUT TYPE=SUBMIT NAME="expungeButton" VALUE="'. _("Expunge") .'">&nbsp;'. _("mailbox") ."&nbsp;\n";
    }
    echo "         <INPUT TYPE=SUBMIT NAME=\"markRead\" VALUE=\"". _("Read")."\">\n".
         "         <INPUT TYPE=SUBMIT NAME=\"markUnread\" VALUE=\"". _("Unread")."\">\n".
         "         <INPUT TYPE=SUBMIT VALUE=\"". _("Delete") . "\">&nbsp;\n".
         "      </TD>\n".
         "   </TR>\n".
         "</TABLE>\n";
    do_hook('mailbox_form_before');
    echo '</TD></TR>'.

        "<TR><TD BGCOLOR=\"$color[0]\">".
        "<TABLE WIDTH=\"100%\" BORDER=0 CELLPADDING=2 CELLSPACING=";
    if ($GLOBALS['alt_index_colors']) {
        echo "0";
    } else {
        echo "1";
    }
    echo " BGCOLOR=\"$color[0]\">".
        "<TR BGCOLOR=\"$color[5]\" ALIGN=\"center\">";

    /* Print the headers. */
    for ($i=1; $i <= count($index_order); $i++) {
        switch ($index_order[$i]) {
        case 1: /* checkbox */
        case 5: /* flags */
            echo '   <TD WIDTH="1%"><B>&nbsp;</B></TD>';
            break;

        case 2: /* from */
            if (handleAsSent($mailbox)) {
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

/*
 * This function shows the sort button. Isn't this a good comment?
 */
function ShowSortButton($sort, $mailbox, $Up, $Down) {
    /* Figure out which image we want to use. */
    if ($sort != $Up && $sort != $Down) {
        $img = 'sort_none.png';
        $which = $Up;
    } elseif ($sort == $Up) {
        $img = 'up_pointer.png';
        $which = $Down;
    } else {
        $img = 'down_pointer.png';
        $which = 6;
    }

    /* Now that we have everything figured out, show the actual button. */
    echo ' <a href="right_main.php?newsort=' . $which .
        '&startMessage=1&mailbox=' . urlencode($mailbox) .
        '"><IMG SRC="../images/' . $img .
        '" BORDER=0 WIDTH=12 HEIGHT=10></a>';
}

function get_selectall_link($start_msg, $sort) {
    global $checkall, $PHP_SELF, $what, $where, $mailbox, $javascript_on;

    if ($javascript_on) {
        $result =
            '<script language="JavaScript">' .
            "\n<!-- \n" .
            "function CheckAll() {\n" .
            "   for (var i = 0; i < document.messageList.elements.length; i++) {\n" .
            "       if( document.messageList.elements[i].type == 'checkbox' ) {\n" .
            "           document.messageList.elements[i].checked = !(document.messageList.elements[i].checked);\n".
            "       }\n" .
            "   }\n" .
            "}\n" .
            "//-->\n" .
            '</script><a href=\"#\" onClick="CheckAll();">' . _("Toggle All") . "</a>\n";
    } else {
        $result .= "<a href=\"$PHP_SELF?mailbox=" . urlencode($mailbox)
                    . "&startMessage=$start_msg&sort=$sort&checkall=";
        if (isset($checkall) && $checkall == '1') {
            $result .= '0';
        } else {
            $result .= '1';
        }

        if (isset($where) && isset($what)) {
            $result .= '&where=' . urlencode($where)
                    . '&what=' . urlencode($what);
        }

        $result .= "\">";

        if (isset($checkall) && ($checkall == '1')) {
            $result .= _("Unselect All");
        } else {
            $result .= _("Select All");
        }

        $result .= "</A>\n";
    }

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
 * Generate a paginator link.
 */
function get_paginator_link
($box, $start_msg, $use, $text) {
    $result = "<A HREF=\"right_main.php?use_mailbox_cache=$use"
            . "&startMessage=$start_msg&mailbox=$box\" "
            . "TARGET=\"right\">$text</A>";
    return ($result);
}

/**
 * This function computes the paginator string.
 */
function get_paginator_str
($box, $start_msg, $end_msg, $num_msgs, $show_num, $sort) {
    global $username, $data_dir, $use_mailbox_cache, $color;

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str = '';
    $all_str = '';
    $tgl_str = '';

    /* Create simple strings that will be creating the paginator. */
    $spc = '&nbsp;';     /* This will be used as a space. */
    $sep = '|';          /* This will be used as a seperator. */

    /* Get some paginator preference values. */
    $pg_sel = getPref($data_dir, $username, 'page_selector', SMPREF_ON);
    $pg_max = getPref($data_dir, $username, 'page_selector_max', PG_SEL_MAX);

    /* Make sure that our start message number is not too big. */
    $start_msg = min($start_msg, $num_msgs);

    /* Decide whether or not we will use the mailbox cache. */
    /* Not sure why $use_mailbox_cache is even passed in.   */
    if ($sort == 6) {
        $use = 0;
    } else {
        $use = 1;
    }

    /* Compute the starting message of the previous and next page group. */
    $next_grp = $start_msg + $show_num;
    $prev_grp = $start_msg - $show_num;

    /* Compute the basic previous and next strings. */
    if (($next_grp <= $num_msgs) && ($prev_grp >= 0)) {
        $prv_str = get_paginator_link($box, $prev_grp, $use, _("Previous"));
        $nxt_str = get_paginator_link($box, $next_grp, $use, _("Next"));
    } else if (($next_grp > $num_msgs) && ($prev_grp >= 0)) {
        $prv_str = get_paginator_link($box, $prev_grp, $use, _("Previous"));
        $nxt_str = "<FONT COLOR=\"$color[9]\">"._("Next")."</FONT>\n";
    } else if (($next_grp <= $num_msgs) && ($prev_grp < 0)) {
        $prv_str = "<FONT COLOR=\"$color[9]\">"._("Previous") . '</FONT>';
        $nxt_str = get_paginator_link($box, $next_grp, $use, _("Next"));
    }

    /* Page selector block. Following code computes page links. */
    if ($pg_sel && ($num_msgs > $show_num)) {
        /* Most importantly, what is the current page!!! */
        $cur_pg = intval($start_msg / $show_num) + 1;

        /* Compute total # of pages and # of paginator page links. */
        $tot_pgs = ceil($num_msgs / $show_num);  /* Total # of Pages */
        $vis_pgs = min($pg_max, $tot_pgs - 1);   /* Visible Pages    */

        /************************************************************/
        /* Compute the size of the four quarters of the page links. */
        /************************************************************/

        /* If we can, just show all the pages. */
        if (($tot_pgs - 1) <= $pg_max) {
            $q1_pgs = $cur_pg - 1;
            $q2_pgs = $q3_pgs = 0;
            $q4_pgs = $tot_pgs - $cur_pg;
            
        /* Otherwise, compute some magic to choose the four quarters. */
        } else {
            /* Compute the magic base values. Added together,  */
            /* these values will always equal to the $pag_pgs. */
            /* NOTE: These are DEFAULT values and do not take  */
            /* the current page into account. That is below.   */
            $q1_pgs = floor($vis_pgs/4);
            $q2_pgs = round($vis_pgs/4, 0);
            $q3_pgs = ceil($vis_pgs/4);
            $q4_pgs = round(($vis_pgs - $q2_pgs)/3, 0);
        
            /* Adjust if the first quarter contains the current page. */
            if (($cur_pg - $q1_pgs) < 1) {
                $extra_pgs = ($q1_pgs - ($cur_pg - 1)) + $q2_pgs;
                $q1_pgs = $cur_pg - 1;
                $q2_pgs = 0;
                $q3_pgs += ceil($extra_pgs / 2);
                $q4_pgs += floor($extra_pgs / 2);

            /* Adjust if the first and second quarters intersect. */
            } else if (($cur_pg - $q2_pgs - ceil($q2_pgs/3)) <= $q1_pgs) {
                $extra_pgs = $q2_pgs;
                $extra_pgs -= ceil(($cur_pg - $q1_pgs - 1) * 0.75);
                $q2_pgs = ceil(($cur_pg - $q1_pgs - 1) * 0.75);
                $q3_pgs += ceil($extra_pgs / 2);
                $q4_pgs += floor($extra_pgs / 2);

            /* Adjust if the fourth quarter contains the current page. */
            } else if (($cur_pg + $q4_pgs) >= $tot_pgs) {
                $extra_pgs = ($q4_pgs - ($tot_pgs - $cur_pg)) + $q3_pgs;
                $q3_pgs = 0;
                $q4_pgs = $tot_pgs - $cur_pg;
                $q1_pgs += floor($extra_pgs / 2);
                $q2_pgs += ceil($extra_pgs / 2);

            /* Adjust if the third and fourth quarter intersect. */
            } else if (($cur_pg + $q3_pgs + 1) >= ($tot_pgs - $q4_pgs + 1)) {
                $extra_pgs = $q3_pgs;
                $extra_pgs -= ceil(($tot_pgs - $cur_pg - $q4_pgs) * 0.75);
                $q3_pgs = ceil(($tot_pgs - $cur_pg - $q4_pgs) * 0.75);
                $q1_pgs += floor($extra_pgs / 2);
                $q2_pgs += ceil($extra_pgs / 2);
            }
        }

        /* I am leaving this debug code here, commented out, because    */
        /* it is a really nice way to see what the above code is doing. */
        /* echo "qts =  $q1_pgs/$q2_pgs/$q3_pgs/$q4_pgs = "             */
        /*    . ($q1_pgs + $q2_pgs + $q3_pgs + $q4_pgs) . '<br>';       */

        /************************************************************/
        /* Print out the page links from the compute page quarters. */
        /************************************************************/

        /* Start with the first quarter. */
        if (($q1_pgs == 0) && ($cur_pg > 1)) {
            $pg_str .= "...$spc";
        } else {
            for ($pg = 1; $pg <= $q1_pgs; ++$pg) {
                $start = (($pg-1) * $show_num) + 1;
                $pg_str .= get_paginator_link($box, $start, $use, $pg) . $spc;
            }
            if ($cur_pg - $q2_pgs - $q1_pgs > 1) {
                $pg_str .= "...$spc";
            }
        }

        /* Continue with the second quarter. */
        for ($pg = $cur_pg - $q2_pgs; $pg < $cur_pg; ++$pg) {
            $start = (($pg-1) * $show_num) + 1;
            $pg_str .= get_paginator_link($box, $start, $use, $pg) . $spc;
        }

        /* Now print the current page. */
        $pg_str .= $cur_pg . $spc;

        /* Next comes the third quarter. */
        for ($pg = $cur_pg + 1; $pg <= $cur_pg + $q3_pgs; ++$pg) {
            $start = (($pg-1) * $show_num) + 1;
            $pg_str .= get_paginator_link($box, $start, $use, $pg) . $spc;
        }

        /* And last, print the forth quarter page links. */
        if (($q4_pgs == 0) && ($cur_pg < $tot_pgs)) {
            $pg_str .= "...$spc";
        } else {
            if (($tot_pgs - $q4_pgs) > ($cur_pg + $q3_pgs)) {
                $pg_str .= "...$spc";
            }
            for ($pg = $tot_pgs - $q4_pgs + 1; $pg <= $tot_pgs; ++$pg) {
                $start = (($pg-1) * $show_num) + 1;
                $pg_str .= get_paginator_link($box, $start, $use, $pg) . $spc;
            }
        }
    }

    /* If necessary, compute the 'show all' string. */
    if (($prv_str != '') || ($nxt_str != '')) {
        $all_str = "<A HREF=\"right_main.php?PG_SHOWNUM=9999"
                 . "&use_mailbox_cache=$use&startMessage=1&mailbox=$box\" "
                 . "TARGET=\"right\">" . _("Show All") . '</A>';
    }

    /* Last but not least, get the value for the toggle all link. */
    $tgl_str = get_selectall_link($start_msg, $sort);

    /* Put all the pieces of the paginator string together. */
    $result = '';
    $result .= ($all_str != '' ? $all_str . $spc . $sep . $spc: '');
    $result .= ($prv_str != '' ? $prv_str . $spc . $sep . $spc : '');
    $result .= ($nxt_str != '' ? $nxt_str . $spc . $sep . $spc : '');
    $result .= ($pg_str != '' ? $pg_str : '');
    $result .= ($result != '' ? $sep . $spc . $tgl_str: $tgl_str);

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = '&nbsp;';
    }

    /* Return our final magical paginator string. */
    return ($result);
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

function handleAsSent($mailbox) {
    global $sent_folder, $draft_folder;
    global $handleAsSent_result;

    /* First check if this is the sent or draft folder. */
    $handleAsSent_result = (($mailbox == $sent_folder)
                        || ($mailbox == $draft_folder));

    /* Then check the result of the handleAsSent hook. */
    do_hook('check_handleAsSent_result', $mailbox);

    /* And return the result. */
    return ($handleAsSent_result);
}

?>
