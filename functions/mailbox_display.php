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

require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'class/html.class.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');

/* Default value for page_selector_max. */
define('PG_SEL_MAX', 10);

function printMessageInfo($imapConnection, $t, $not_last=true, $key, $mailbox,
                          $start_msg, $where, $what) {
    global $checkall,
           $color, $msgs, $msort,
           $default_use_priority,
           $message_highlight_list,
           $index_order,
           $indent_array,   /* indent subject by */
           $pos,            /* Search postion (if any)  */
           $thread_sort_messages, /* thread sorting on/off */
           $server_sort_order, /* sort value when using server-sorting */
           $row_count,
           $allow_server_sort; /* enable/disable server-side sorting */
    $color_string = $color[4];

    if ($GLOBALS['alt_index_colors']) {
        if (!isset($row_count)) {
            $row_count = 0;
        }
        $row_count++;
        if ($row_count % 2) {
            if (!isset($color[12])) {
                $color[12] = '#EAEAEA';
            }
            $color_string = $color[12];
        }
    }
    $msg = $msgs[$key];

    if($mailbox == 'None') {
        $boxes   = sqimap_mailbox_list($imapConnection);
        $mailbox = $boxes[0]['unformatted'];
        unset($boxes);
    }
    $urlMailbox = urlencode($mailbox);

    if (handleAsSent($mailbox)) {
       $msg['FROM'] = $msg['TO'];
       /*
        * This is done in case you're looking into Sent folders,
        * because you can have multiple receivers.
        */
       $senderNames = explode(',', $msg['FROM']);
       $senderName  = '';
       if (sizeof($senderNames)){
          foreach ($senderNames as $senderNames_part) {
            if ($senderName != '') {
                $senderName .= ', ';
            }
            $senderName .= sqimap_find_displayable_name($senderNames_part);
          }
       }
    } else {
       $senderName = sqimap_find_displayable_name($msg['FROM']);
    }

    $subject = processSubject($msg['SUBJECT']);

    echo html_tag( 'tr','','','','VALIGN="top"') . "\n";

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

    if ($where && $what) {
        $searchstr = '&amp;where='.$where.'&amp;what='.$what;
    } else {
        $searchstr = '';
    }
    /**
    * AAAAH! Make my eyes stop bleeding!
    * Who wrote this?!
    */
    if (sizeof($message_highlight_list)){
        foreach ($message_highlight_list as $message_highlight_list_part) {
            if (trim($message_highlight_list_part['value']) != '') {
                $high_val   = strtolower($message_highlight_list_part['value']);
                $match_type = strtoupper($message_highlight_list_part['match_type']);
                if ($match_type == 'TO_CC') {
                    if (strstr('^^' . strtolower($msg['TO']), $high_val) ||
                        strstr('^^' . strtolower($msg['CC']), $high_val)) {
                        $hlt_color = $message_highlight_list_part['color'];
                        continue;
                    }
                } else {
                    if (strstr('^^' . strtolower($msg[$match_type]), $high_val)) {
                        $hlt_color = $message_highlight_list_part['color'];
                        continue;
                    }
                }
            }
        }
    }

    if (!isset($hlt_color)) {
        $hlt_color = $color_string;
    }
    $checked = ($checkall == 1) ? true : false;
    $col = 0;
    if (sizeof($index_order)) {
        foreach ($index_order as $index_order_part) {
            switch ($index_order_part) {
            case 1: /* checkbox */
                echo html_tag( 'td',
                               "<input type=checkbox name=\"msg[$t]\" value=\"".$msg['ID']."\"$checked>",
                               'center',
                               $hlt_color );
                break;
            case 2: /* from */
                echo html_tag( 'td',
                               $italic . $bold . $flag . $fontstr . $senderName .
                               $fontstr_end . $flag_end . $bold_end . $italic_end,
                               'left',
                               $hlt_color );
                break;
            case 3: /* date */
                $date_string = $msg['DATE_STRING'] . '';
                if ($date_string == '') {
                    $date_string = _("Unknown date");
                }
                echo html_tag( 'td',
                               $bold . $flag . $fontstr . $date_string .
                               $fontstr_end . $flag_end . $bold_end,
                               'center',
                               $hlt_color,
                               'nowrap' );
                break;
            case 4: /* subject */
                $td_str = $bold;
                if ($thread_sort_messages == 1) {
                    if (isset($indent_array[$msg["ID"]])) {
                        $td_str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;",$indent_array[$msg['ID']]);
                    }
                }
                $td_str .= '<a href="read_body.php?mailbox='.$urlMailbox
                        .  '&amp;passed_id='. $msg["ID"]
                        .  '&amp;startMessage='.$start_msg.$searchstr.'"';
                do_hook("subject_link");
                if ($subject != $msg['SUBJECT']) {
                    $title = get_html_translation_table(HTML_SPECIALCHARS);
                    $title = array_flip($title);
                    $title = strtr($msg['SUBJECT'], $title);
                    $title = str_replace('"', "''", $title);
                    $td_str .= " title=\"$title\"";
                }
                $td_str .= ">$flag$subject$flag_end</a>$bold_end";
                echo html_tag( 'td', $td_str, 'left', $hlt_color );
                break;
            case 5: /* flags */
                $stuff = false;
                $td_str = "<b><small>";

                if (isset($msg['FLAG_ANSWERED']) && $msg['FLAG_ANSWERED'] == true) {
                    $td_str .= _("A");
                    $stuff = true;
                }
                if ($msg['TYPE0'] == 'multipart') {
                    $td_str .= '+';
                    $stuff = true;
                }
                if ($default_use_priority) {
                    if ( ($msg['PRIORITY'] == 1) || ($msg['PRIORITY'] == 2) ) {
                        $td_str .= "<font color=\"$color[1]\">!</font>";
                        $stuff = true;
                    }
                    if ($msg['PRIORITY'] == 5) {
                        $td_str .= "<font color=\"$color[8]\">?</font>";
                        $stuff = true;
                    }
                }
                if (isset($msg['FLAG_DELETED']) && $msg['FLAG_DELETED'] == true) {
                    $td_str .= "<font color=\"$color[1]\">D</font>";
                    $stuff = true;
                }
                if (!$stuff) {
                    $td_str .= '&nbsp;';
                }
                $td_str .= '</small></b>';
                echo html_tag( 'td',
                               $td_str,
                               'center',
                               $hlt_color,
                               'nowrap' );
                break;
            case 6: /* size */
                echo html_tag( 'td',
                               $bold . $fontstr . show_readable_size($msg['SIZE']) .
                               $fontstr_end . $bold_end,
                               'right',
                               $hlt_color );
                break;
            }
            ++$col;
        }
    }
    if ($not_last) {
        echo '</tr>' . "\n" . '<tr><td COLSPAN="' . $col . '" BGCOLOR="' .
             $color[0] . '" HEIGHT="1"></td></tr>' . "\n";
    } else {
        echo '</tr>'."\n";
    }
}

function getServerMessages($imapConnection, $start_msg, $show_num, $num_msgs, $id) {
    if ($id != 'no') {
        if ($start_msg + ($show_num - 1) < $num_msgs) {
            $end_msg = $start_msg + ($show_num-1);
        } else {
            $end_msg = $num_msgs;
        }
        $id = array_slice($id, ($start_msg-1), ($end_msg));

        $end = $start_msg + $show_num - 1;
        if ($num_msgs < $show_num) {
            $end_loop = $num_msgs;
        } else if ($end > $num_msgs) {
            $end_loop = $num_msgs - $start_msg + 1;
        } else {
            $end_loop = $show_num;
        }
        return fillMessageArray($imapConnection,$id,$end_loop);
    } else {
        return false;
    }
}

function getThreadMessages($imapConnection, $start_msg, $show_num, $num_msgs) {
    $id = get_thread_sort($imapConnection);
    return getServerMessages($imapConnection, $start_msg, $show_num, $num_msgs, $id);
}

function getServerSortMessages($imapConnection, $start_msg, $show_num,
                               $num_msgs, $server_sort_order, $mbxresponse) {
    $id = sqimap_get_sort_order($imapConnection, $server_sort_order,$mbxresponse);
    return getServerMessages($imapConnection, $start_msg, $show_num, $num_msgs, $id);
}

function getSelfSortMessages($imapConnection, $start_msg, $show_num,
                              $num_msgs, $sort, $mbxresponse) {
    $msgs = array();
    if ($num_msgs >= 1) {
        $id = sqimap_get_php_sort_order ($imapConnection, $mbxresponse);
        if ($sort < 6 ) {
            $end = $num_msgs;
            $end_loop = $end;
        } else {
            /* if it's not sorted */
            if ($start_msg + ($show_num - 1) < $num_msgs) {
                $end_msg = $start_msg + ($show_num - 1);
            } else {
                $end_msg = $num_msgs;
            }
            if ($end_msg < $start_msg) {
                $start_msg = $start_msg - $show_num;
                if ($start_msg < 1) {
                    $start_msg = 1;
                }
            }
            $id = array_slice(array_reverse($id), ($start_msg-1), ($end_msg));
            $end = $start_msg + $show_num - 1;
            if ($num_msgs < $show_num) {
                $end_loop = $num_msgs;
            } else if ($end > $num_msgs) {
                $end_loop = $num_msgs - $start_msg + 1;
            } else {
                $end_loop = $show_num;
            }
        }
        $msgs = fillMessageArray($imapConnection,$id,$end_loop);
    }
    return $msgs;
}



/*
 * This function loops through a group of messages in the mailbox
 * and shows them to the user.
 */
function showMessagesForMailbox($imapConnection, $mailbox, $num_msgs,
                                $start_msg, $sort, $color, $show_num,
                                $use_cache, $mode='') {
    global $msgs, $msort, $auto_expunge, $thread_sort_messages,
           $allow_server_sort, $server_sort_order;

    /* If autoexpunge is turned on, then do it now. */
    $mbxresponse = sqimap_mailbox_select($imapConnection, $mailbox);
    $srt = $sort;
    /* If autoexpunge is turned on, then do it now. */
    if ($auto_expunge == true) {
        $exp_cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, false, '');
        $mbxresponse['EXISTS'] = $mbxresponse['EXISTS'] - $exp_cnt;
        $num_msgs = $mbxresponse['EXISTS'];
    }

    if ($mbxresponse['EXISTS'] > 0) {
        /* if $start_msg is lower than $num_msgs, we probably deleted all messages
         * in the last page. We need to re-adjust the start_msg
         */

        if($start_msg > $num_msgs) {
            $start_msg -= $show_num;
            if($start_msg < 1) {
                $start_msg = 1;
            }
        }

        /* This code and the next if() block check for
         * server-side sorting methods. The $id array is
         * formatted and $sort is set to 6 to disable
         * SM internal sorting
         */

        if ($thread_sort_messages == 1) {
            $mode = 'thread';
        } elseif ($allow_server_sort == 1) {
            $mode = 'serversort';
        } else {
            $mode = '';
        }

        sqsession_unregister('msort');
        sqsession_unregister('msgs');
        switch ($mode) {
            case 'thread':
                $id   = get_thread_sort($imapConnection);
                $msgs = getServerMessages($imapConnection, $start_msg, $show_num, $num_msgs, $id);
                if ($msgs === false) {
                    echo '<b><small><center><font color=red>' .
                         _("Thread sorting is not supported by your IMAP server.<br>Please report this to the system administrator.").
                         '</center></small></b>';
                    $thread_sort_messages = 0;
                    $msort = $msgs = array();
                } else {
                    $msort= $msgs;
                    $sort = 6;
                }
                break;
            case 'serversort':
                $id   = sqimap_get_sort_order($imapConnection, $sort, $mbxresponse);
                $msgs = getServerMessages($imapConnection, $start_msg, $show_num, $num_msgs, $id);
                if ($msgs === false) {
                    echo '<b><small><center><font color=red>' .
                         _( "Server-side sorting is not supported by your IMAP server.<br>Please report this to the system administrator.").
                         '</center></small></b>';
                    $sort = $server_sort_order;
                    $allow_server_sort = FALSE;
                    $msort = $msgs = array();
                    $id = array();
                } else {
                    $msort = $msgs;
                    $sort = 6;
                }
                break;
            default:
                if (!$use_cache) {
                    $msgs = getSelfSortMessages($imapConnection, $start_msg, $show_num,
                                                $num_msgs, $sort, $mbxresponse);
                    $msort = calc_msort($msgs, $sort);
                } /* !use cache */
                break;
        } // switch
        sqsession_register($msort, 'msort');
        sqsession_register($msgs,  'msgs');
    } /* if exists > 0 */

    $res = getEndMessage($start_msg, $show_num, $num_msgs);
    $start_msg = $res[0];
    $end_msg   = $res[1];

    $paginator_str = get_paginator_str($mailbox, $start_msg, $end_msg,
                                       $num_msgs, $show_num, $sort);

    $msg_cnt_str = get_msgcnt_str($start_msg, $end_msg, $num_msgs);

    do_hook('mailbox_index_before');
    echo '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
    echo '<tr><td>';

    mail_message_listing_beginning($imapConnection, $mailbox, $sort, 
                                  $msg_cnt_str, $paginator_str, $start_msg);
    echo '</td></tr>';
    /* line between the button area and the list */
    echo '<tr><td HEIGHT="5" BGCOLOR="'.$color[4].'"></td></tr>';  

    echo '<tr><td>';
    echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
    echo '     <tr><td>';
    echo '       <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[5].'">';
    echo '<tr><td>';
    printHeader($mailbox, $srt, $color, !$thread_sort_messages);

    displayMessageArray($imapConnection, $num_msgs, $start_msg, 
		      $msort, $mailbox, $sort, $color, $show_num,0,0);
    echo '</td></tr></table></td></tr></table>';

    mail_message_listing_end($num_msgs, $paginator_str, $msg_cnt_str, $color); 
    echo '</td></tr></table>';
}

function calc_msort($msgs, $sort) {

    /*
     * 0 = Date (up)
     * 1 = Date (dn)
     * 2 = Name (up)
     * 3 = Name (dn)
     * 4 = Subject (up)
     * 5 = Subject (dn)
     */
    if (($sort == 0) || ($sort == 1)) {
        $msort = array_cleave ($msgs, 'TIME_STAMP');
    } elseif (($sort == 2) || ($sort == 3)) {
        $msort = array_cleave ($msgs, 'FROM-SORT');
    } elseif (($sort == 4) || ($sort == 5)) {
        $msort = array_cleave ($msgs, 'SUBJECT-SORT');
    } else {
        $msort = $msgs;
    }
    if ($sort < 6) {
        if ($sort % 2) {
            asort($msort);
        } else {
            arsort($msort);
        }
    }
    return $msort;
}

function fillMessageArray($imapConnection, $id, $count) {
    $msgs_list = sqimap_get_small_header_list($imapConnection, $id);
    $messages = array();
    if (sizeof($msgs_list)) {
        foreach ($msgs_list as $hdr) {
            $unique_id[] = $hdr->uid;
            $from[] = $hdr->from;
            $date[] = $hdr->date;
            $subject[] = $hdr->subject;
            $to[] = $hdr->to;
            $priority[] = $hdr->priority;
            $cc[] = $hdr->cc;
            $size[] = $hdr->size;
            $type[] = $hdr->type0;
            $flag_deleted[] = $hdr->flag_deleted;
            $flag_answered[] = $hdr->flag_answered;
            $flag_seen[] = $hdr->flag_seen;
            $flag_flagged[] = $hdr->flag_flagged;
        }
    }

    for($j = 0; $j < $count; ++$j) {
        if (isset($date[$j])) {
            $date[$j] = str_replace('  ', ' ', $date[$j]);
            $tmpdate  = explode(' ', trim($date[$j]));
        } else {
            $tmpdate = $date = array('', '', '', '', '', '');
        }
        $messages[$j]['TIME_STAMP'] = getTimeStamp($tmpdate);
        $messages[$j]['DATE_STRING'] =
        getDateString($messages[$j]['TIME_STAMP']);
        $messages[$j]['ID'] = $unique_id[$j];
        $messages[$j]['FROM'] = decodeHeader($from[$j]);
        $messages[$j]['FROM-SORT'] =
        strtolower(sqimap_find_displayable_name(decodeHeader($from[$j])));
        $messages[$j]['SUBJECT'] = decodeHeader($subject[$j]);
        $messages[$j]['SUBJECT-SORT'] = strtolower(decodeHeader($subject[$j]));
        $messages[$j]['TO'] = decodeHeader($to[$j]);
        $messages[$j]['PRIORITY'] = $priority[$j];
        $messages[$j]['CC'] = $cc[$j];
        $messages[$j]['SIZE'] = $size[$j];
        $messages[$j]['TYPE0'] = $type[$j];
        $messages[$j]['FLAG_DELETED'] = $flag_deleted[$j];
        $messages[$j]['FLAG_ANSWERED'] = $flag_answered[$j];
        $messages[$j]['FLAG_SEEN'] = $flag_seen[$j];
        $messages[$j]['FLAG_FLAGGED'] = $flag_flagged[$j];

        /*
         * fix SUBJECT-SORT to remove Re:
         *     vedr|sv  (Danish)
         *     re|aw (English)
         *
         * TODO: i18n should be incorporated here. E.g. we catch the ones
         * we know about, but also define in i18n what the localized
         * "Re: " is for this or that locale.
         */
        if (preg_match("/^(vedr|sv|re|aw):\s*(.*)$/si",
            $messages[$j]['SUBJECT-SORT'], $matches)){
            $messages[$j]['SUBJECT-SORT'] = $matches[2];
        }
    }
    return $messages;
}


/* Generic function to convert the msgs array into an HTML table. */
function displayMessageArray($imapConnection, $num_msgs, $start_msg,
                             $msort, $mailbox, $sort, $color,
                             $show_num, $where=0, $what=0) {
    global $imapServerAddress, $use_mailbox_cache,
           $index_order, $checkall,
           $indent_array, $thread_sort_messages, $allow_server_sort,
           $server_sort_order, $PHP_SELF;

    $res = getEndMessage($start_msg, $show_num, $num_msgs);
    $start_msg = $res[0];
    $end_msg   = $res[1];

    $urlMailbox = urlencode($mailbox);

    /* get indent level for subject display */
    if ($thread_sort_messages == 1 ) {
        $indent_array = get_parent_level($imapConnection);
    }

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

    /*
     * Loop through and display the info for each message.
     * ($t is used for the checkbox number)
     */
    $t = 0;

    /* messages display */

    if ($num_msgs == 0) {
    /* if there's no messages in this folder */
        echo html_tag( 'tr',
                html_tag( 'td',
                          "<BR><b>" . _("THIS FOLDER IS EMPTY") . "</b><BR>&nbsp;",
                          'center',
                          $color[4],
                          'COLSPAN="' . count($index_order) . '"'
                )
        );
    } elseif ($start_msg == $end_msg) {
    /* if there's only one message in the box, handle it differently. */
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
        printMessageInfo($imapConnection, $t, true, $key, $mailbox,
                         $real_startMessage, $where, $what);
    } else {
        $i = $start_msg;
        reset($msort);
        $k = 0;
        do {
            $key = key($msort);
            next($msort);
            $k++;
        } while (isset ($key) && ($k < $i));
        $not_last = true;
        do {
            if (!$i || $i == $endVar-1) $not_last = false;
                printMessageInfo($imapConnection, $t, $not_last, $key, $mailbox,
                                 $real_startMessage, $where, $what);
            $key = key($msort);
            $t++;
            $i++;
            next($msort);
        } while ($i && $i < $endVar);
    }
}

/*
 * Displays the standard message list header. To finish the table,
 * you need to do a "</table></table>";
 *
 * $moveURL is the URL to submit the delete/move form to
 * $mailbox is the current mailbox
 * $sort is the current sorting method (-1 for no sorting available [searches])
 * $Message is a message that is centered on top of the list
 * $More is a second line that is left aligned
 */

function mail_message_listing_beginning ($imapConnection,
                                         $mailbox = '', $sort = -1,
                                         $msg_cnt_str = '',
                                         $paginator = '&nbsp;',
                                         $start_msg = 1) {
    global $color, $auto_expunge, $base_uri, $thread_sort_messages,
           $allow_thread_sort, $allow_server_sort, $server_sort_order,
           $PHP_SELF;

    $php_self = $PHP_SELF;
    /* fix for incorrect $PHP_SELF */
    if (strpos($php_self, 'move_messages.php')) {
        $php_self = str_replace('move_messages.php', 'right_main.php', $php_self);
    }
    $urlMailbox = urlencode($mailbox);

    if (preg_match('/^(.+)\?.+$/',$php_self,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $php_self;
    }

    if (!isset($msg)) {
        $msg = '';
    }
    $moveURL = "move_messages.php?msg=$msg&amp;mailbox=$urlMailbox"
             . "&amp;startMessage=$start_msg";
    /*
     * This is the beginning of the message list table.
     * It wraps around all messages
     */
    echo "<FORM name=\"messageList\" method=post action=\"$moveURL\">\n"
        . html_tag( 'table' ,
            html_tag( 'tr',
                html_tag( 'td' ,
                    html_tag( 'table' ,
                        html_tag( 'tr',
                            html_tag( 'td', $paginator, 'left' ) .
                            html_tag( 'td', $msg_cnt_str, 'right' ) 
                        )
                    , '', $color[4], 'border="0" width="100%" cellpadding="1"  cellspacing="0"' ) 
                , 'left', '', '' )
            , '', $color[0] )
	    , '', '', 'border="0" width="100%" cellpadding="1"  cellspacing="0"' );
	/* line between header and button area */
        echo '<tr><td HEIGHT="5" BGCOLOR="'.$color[4].'"></td></tr>';

        echo '<tr><td>';
        echo html_tag( 'tr' ) . "\n"
        . html_tag( 'td' ,'' , 'left', '', '' )
         . html_tag( 'table' ,'' , '', $color[9], 'border="0" width="100%" cellpadding="1"  cellspacing="0"' )
	  . '<tr><td>'
           . html_tag( 'table' ,'' , '', $color[0], 'border="0" width="100%" cellpadding="1"  cellspacing="0"' )
            . html_tag( 'tr',
	        getSmallStringCell(_("Move Selected To"), 'left', 'nowrap') .
	        getSmallStringCell(_("Transform Selected Messages"), 'right')
            )
            . html_tag( 'tr' ) ."\n"
            . html_tag( 'td', '', 'left', '', 'valign="middle" nowrap' );
            getMbxList($imapConnection);  
            echo getButton('SUBMIT', 'moveButton',_("Move")) . "\n";   
            echo getButton('SUBMIT', 'attache',_("Forward")) . "\n";   

  echo "      </TD>\n"
         . html_tag( 'td', '', 'right', '', 'nowrap' );



    if (!$auto_expunge) {
        echo getButton('SUBMIT', 'expungeButton',_("Expunge"))
             .'&nbsp;' . _("mailbox") . "\n";
    }

    echo getButton('SUBMIT', 'markRead',_("Read"));
    echo getButton('SUBMIT', 'markUnread',_("Unread"));
    echo getButton('SUBMIT', 'delete',_("Delete")) ."&nbsp\n";
    if (!strpos($php_self,'mailbox')) {
        $location = $php_self.'?mailbox=INBOX&amp;startMessage=1';
    } else {
        $location = $php_self;
    }
    echo '<INPUT TYPE="HIDDEN" NAME="location" VALUE="'.$location.'">';
    echo "</TD>\n"
         . "   </TR>\n";

    /* draws thread sorting links */
    if ($allow_thread_sort == TRUE) {
        if ($thread_sort_messages == 1 ) {
            $set_thread = 2;
            $thread_name = _("Unthread View");
        } elseif ($thread_sort_messages == 0) {
            $set_thread = 1;
            $thread_name = _("Thread View");
        }
        echo html_tag( 'tr' ,
                    html_tag( 'td' ,
                              '&nbsp;<a href=' . $source_url . '?sort='
                              . "$sort" . '&start_messages=1&set_thread=' . "$set_thread"
                              . '&mailbox=' . urlencode($mailbox) . '><small>' . $thread_name
                              . '</a></small>&nbsp;'
                     , '', '', '' )
                 , '', '', '' );
    }

    echo "</TABLE></td></tr></table></td></tr>\n";

    do_hook('mailbox_form_before');

    /* if using server sort we highjack the
     * the $sort var and use $server_sort_order
     * instead. but here we reset sort for a bit
     * since its easy
     */
    if ($allow_server_sort == TRUE) {
        $sort = $server_sort_order;
    }
}

function mail_message_listing_end($num_msgs, $paginator_str, $msg_cnt_str, $color) {
  if ($num_msgs) {
    /* space between list and footer */
    echo '<tr><td HEIGHT="5" BGCOLOR="'.$color[4].'" COLSPAN="1">';  

    echo '</td></tr><tr><td>';
    echo html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td',
                    html_tag( 'table',
                        html_tag( 'tr',
                            html_tag( 'td', $paginator_str ) .
                            html_tag( 'td', $msg_cnt_str, 'right' )
                        )
                    , '', $color[4], 'width="100%" border="0" cellpadding="1" cellspacing="0"' )
                )
            )
        , '', $color[9], 'width="100%" border="0" cellpadding="1"  cellspacing="0"' );
    echo '</td></tr>';
  }
    /* End of message-list table */

    do_hook('mailbox_index_after');
    echo "</FORM>\n";
}

function printHeader($mailbox, $sort, $color, $showsort=true) {
    global $index_order;
    echo html_tag( 'tr' ,'' , 'center', $color[5] );
    for ($i = 1; $i <= count($index_order); $i++) {
        switch ($index_order[$i]) {
        case 1: /* checkbox */
        case 5: /* flags */
            echo html_tag( 'td' ,'&nbsp;' , '', '', 'width="1%"' );
            break;
        case 2: /* from */
            if (handleAsSent($mailbox)) {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                     . '<b>' . _("To") . '</b>';
            } else {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                     . '<b>' . _("From") . '</b>';
            }
            if ($showsort) {
                ShowSortButton($sort, $mailbox, 2, 3);
            }
            echo "</td>\n";
            break;
        case 3: /* date */
            echo html_tag( 'td' ,'' , 'left', '', 'width="5%" nowrap' )
                 . '<b>' . _("Date") . '</b>';
            if ($showsort) {
                ShowSortButton($sort, $mailbox, 0, 1);
            }
            echo "</td>\n";
            break;
        case 4: /* subject */
            echo html_tag( 'td' ,'' , 'left', '', '' )
                 . '<b>' . _("Subject") . '</b>';
            if ($showsort) {
                ShowSortButton($sort, $mailbox, 4, 5);
            }
            echo "</td>\n";
            break;
        case 6: /* size */
            echo html_tag( 'td', '<b>' . _("Size") . '</b>', 'center', '', 'width="5%"' );
            break;
        }
    }
    echo "</tr>\n";
}


/*
 * This function shows the sort button. Isn't this a good comment?
 */
function ShowSortButton($sort, $mailbox, $Up, $Down ) {
    global $PHP_SELF;
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

    if (preg_match('/^(.+)\?.+$/',$PHP_SELF,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $PHP_SELF;
    }

    /* Now that we have everything figured out, show the actual button. */
    echo ' <a href="' . $source_url .'?newsort=' . $which
         . '&amp;startMessage=1&amp;mailbox=' . urlencode($mailbox)
         . '"><IMG SRC="../images/' . $img
         . '" BORDER=0 WIDTH=12 HEIGHT=10 ALT="sort"></a>';
}

function get_selectall_link($start_msg, $sort) {
    global $checkall, $what, $where, $mailbox, $javascript_on;
    global $PHP_SELF, $PG_SHOWNUM;

    $result = '';
    if ($javascript_on) {
        $result = '<script language="JavaScript" type="text/javascript">'
                . "\n<!-- \n"
                . "function CheckAll() {\n"
                . "  for (var i = 0; i < document.messageList.elements.length; i++) {\n"
                . "    if(document.messageList.elements[i].type == 'checkbox'){\n"
                . "      document.messageList.elements[i].checked = "
                . "        !(document.messageList.elements[i].checked);\n"
                . "    }\n"
                . "  }\n"
                . "}\n"
                . "//-->\n"
                . '</script><a href="#" onClick="CheckAll();">' . _("Toggle All")
                . "</a>\n";
    } else {
        if (strpos($PHP_SELF, "?")) {
            $result .= "<a href=\"$PHP_SELF&amp;mailbox=" . urlencode($mailbox)
                    .  "&amp;startMessage=$start_msg&amp;sort=$sort&amp;checkall=";
        } else {
            $result .= "<a href=\"$PHP_SELF?mailbox=" . urlencode($mailbox)
                    .  "&amp;startMessage=$start_msg&amp;sort=$sort&amp;checkall=";
        }
        if (isset($checkall) && $checkall == '1') {
            $result .= '0';
        } else {
            $result .= '1';
        }

        if (isset($where) && isset($what)) {
            $result .= '&amp;where=' . urlencode($where)
                    .  '&amp;what=' . urlencode($what);
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

/*
 * This function computes the "Viewing Messages..." string.
 */
function get_msgcnt_str($start_msg, $end_msg, $num_msgs) {
    /* Compute the $msg_cnt_str. */
    $result = '';
    if ($start_msg < $end_msg) {
        $result = sprintf(_("Viewing Messages: <B>%s</B> to <B>%s</B> (%s total)"),
                          $start_msg, $end_msg, $num_msgs);
    } else if ($start_msg == $end_msg) {
        $result = sprintf(_("Viewing Message: <B>%s</B> (1 total)"), $start_msg);
    } else {
        $result = '<br>';
    }
    /* Return our result string. */
    return ($result);
}

/*
 * Generate a paginator link.
 */
function get_paginator_link($box, $start_msg, $use, $text) {
    global $PHP_SELF;

    $result = "<A HREF=\"right_main.php?use_mailbox_cache=$use"
            . "&amp;startMessage=$start_msg&amp;mailbox=$box\" "
            . "TARGET=\"right\">$text</A>";
    return ($result);
/*
    if (preg_match('/^(.+)\?.+$/',$PHP_SELF,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $PHP_SELF;
    }

    $result = '<A HREF="'. $source_url . "?use_mailbox_cache=$use"
            . "&amp;startMessage=$start_msg&amp;mailbox=$box\" "
            . "TARGET=\"right\">$text</A>";
    return ($result);
*/
}

/*
 * This function computes the paginator string.
 */
function get_paginator_str($box, $start_msg, $end_msg, $num_msgs,
                           $show_num, $sort) {
    global $username, $data_dir, $use_mailbox_cache, $color, $PG_SHOWNUM;

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str  = '';
    $all_str = '';
    $tgl_str = '';

    $box = urlencode($box);

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
        $tot_pgs = ceil($num_msgs / $show_num);  /* Total number of Pages */
        $vis_pgs = min($pg_max, $tot_pgs - 1);   /* Visible Pages    */

        /* Compute the size of the four quarters of the page links. */

        /* If we can, just show all the pages. */
        if (($tot_pgs - 1) <= $pg_max) {
            $q1_pgs = $cur_pg - 1;
            $q2_pgs = $q3_pgs = 0;
            $q4_pgs = $tot_pgs - $cur_pg;

        /* Otherwise, compute some magic to choose the four quarters. */
        } else {
            /*
             * Compute the magic base values. Added together,
             * these values will always equal to the $pag_pgs.
             * NOTE: These are DEFAULT values and do not take
             * the current page into account. That is below.
             */
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

        /*
         * I am leaving this debug code here, commented out, because
         * it is a really nice way to see what the above code is doing.
         * echo "qts =  $q1_pgs/$q2_pgs/$q3_pgs/$q4_pgs = "
         *    . ($q1_pgs + $q2_pgs + $q3_pgs + $q4_pgs) . '<br>';
         */

        /* Print out the page links from the compute page quarters. */

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
    } else if ($PG_SHOWNUM == 999999) {
        $pg_str = "<A HREF=\"right_main.php?PG_SHOWALL=0"
                . "&amp;use_mailbox_cache=$use&amp;startMessage=1&amp;mailbox=$box\" "
                . "TARGET=\"right\">" ._("Paginate") . '</A>' . $spc;
    }

    /* If necessary, compute the 'show all' string. */
    if (($prv_str != '') || ($nxt_str != '')) {
        $all_str = "<A HREF=\"right_main.php?PG_SHOWALL=1"
                 . "&amp;use_mailbox_cache=$use&amp;startMessage=1&amp;mailbox=$box\" "
                 . "TARGET=\"right\">" . _("Show All") . '</A>';
    }

    /* Last but not least, get the value for the toggle all link. */
    $tgl_str = get_selectall_link($start_msg, $sort);

    /* Put all the pieces of the paginator string together. */
    /**
     * Hairy code... But let's leave it like it is since I am not certain
     * a different approach would be any easier to read. ;)
     */
    $result = '';
    $result .= ($prv_str != '' ? $prv_str . $spc . $sep . $spc : '');
    $result .= ($nxt_str != '' ? $nxt_str . $spc . $sep . $spc : '');
    $result .= ($pg_str  != '' ? $pg_str : '');
    $result .= ($all_str != '' ? $sep . $spc . $all_str . $spc : '');
    $result .= ($result  != '' ? $sep . $spc . $tgl_str: $tgl_str);

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = '&nbsp;';
    }

    /* Return our final magical paginator string. */
    return ($result);
}

function processSubject($subject) {
    global $languages, $squirrelmail_language;
    /* Shouldn't ever happen -- caught too many times in the IMAP functions */
    if ($subject == '')
        return _("(no subject)");

    if (strlen($subject) <= 55)
        return $subject;

    $ent_strlen = strlen($subject);
    $trim_val=50;
    $ent_offset=0;
    /*
     * see if this is entities-encoded string
     * If so, Iterate through the whole string, find out
     * the real number of characters, and if more
     * than 55, substr with an updated trim value.
     */
    while ( (($ent_loc = strpos($subject, '&', $ent_offset)) !== false) &&
            (($ent_loc_end = strpos($subject, ';', $ent_loc)) !== false) ) {
        $trim_val   += ($ent_loc_end-$ent_loc)+1;
        $ent_strlen -= $ent_loc_end-$ent_loc;
        $ent_offset  = $ent_loc_end+1;
    }

    if ($ent_strlen <= 55){
        return $subject;
    }

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        return $languages[$squirrelmail_language]['XTRA_CODE']('strimwidth', $subject, $trim_val);
    }

    return substr($subject, 0, $trim_val) . '...';
}

function getMbxList($imapConnection) {
    global $lastTargetMailbox;
    echo  '         <small>&nbsp;<tt><select name="targetMailbox">';
    $boxes = sqimap_mailbox_list($imapConnection);
    foreach ($boxes as $boxes_part) {
        if (!in_array('noselect', $boxes_part['flags'])) {
            $box = $boxes_part['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', imap_utf7_decode_local($boxes_part['unformatted-disp']));
            if($box2 == 'INBOX') {
                $box2 = _("INBOX");
            }
            if ($lastTargetMailbox == $box) {
                echo "       <OPTION VALUE=\"$box\" SELECTED>$box2</OPTION>\n";
            } else {
                echo "         <OPTION VALUE=\"$box\">$box2</OPTION>\n";
            }
        }
    }
    echo '         </SELECT></TT>&nbsp;';
}

function getButton($type, $name, $value) {
    return '<INPUT TYPE="'.$type.'" NAME="'.$name.'" VALUE="'.$value . '">';
}

function getSmallStringCell($string, $align) {
    return html_tag('td',
                    '<small>' . $string . ':&nbsp; </small>',
                    $align,
                    '',
                    'nowrap' );
}

function getEndMessage($start_msg, $show_num, $num_msgs) {
    if ($start_msg + ($show_num - 1) < $num_msgs){
        $end_msg = $start_msg + ($show_num - 1);
    } else {
        $end_msg = $num_msgs;
    }

    if ($end_msg < $start_msg) {
        $start_msg = $start_msg - $show_num;
        if ($start_msg < 1) {
            $start_msg = 1;
        }
    }
    return (array($start_msg,$end_msg));
}

function handleAsSent($mailbox) {
    global $sent_folder, $draft_folder, $handleAsSent_result;

    /* First check if this is the sent or draft folder. */
    $handleAsSent_result = (($mailbox == $sent_folder)
                             || ($mailbox == $draft_folder));

    /* Then check the result of the handleAsSent hook. */
    do_hook('check_handleAsSent_result', $mailbox);

    /* And return the result. */
    return ($handleAsSent_result);
}
?>
