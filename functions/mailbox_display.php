<?php

/**
* mailbox_display.php
*
* Copyright (c) 1999-2004 The SquirrelMail Project Team
* Licensed under the GNU GPL. For full terms see the file COPYING.
*
* This contains functions that display mailbox information, such as the
* table row that has sender, date, subject, etc...
*
* @version $Id$
* @package squirrelmail
*/

/** The standard includes.. */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'class/html.class.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/forms.php');

/**
* default value for page_selector_max
*/
define('PG_SEL_MAX', 10);

define('SQSORT_NONE',0);
define('SQSORT_DATE_ASC',1);
define('SQSORT_DATE_DEC',2);
define('SQSORT_FROM_ASC',3);
define('SQSORT_FROM_DEC',4);
define('SQSORT_SUBJ_ASC',5);
define('SQSORT_SUBJ_DEC',6);
define('SQSORT_SIZE_ASC',7);
define('SQSORT_SIZE_DEC',8);
define('SQSORT_TO_ASC',9);
define('SQSORT_TO_DEC',10);
define('SQSORT_CC_ASC',11);
define('SQSORT_CC_DEC',12);
define('SQSORT_INT_DATE_ASC',13);
define('SQSORT_INT_DATE_DEC',14);
/**
* @param mixed $start UNDOCUMENTED
*/
function elapsed($start) {

    $end = microtime();
    list($start2, $start1) = explode(" ", $start);
    list($end2, $end1) = explode(" ", $end);
    $diff1 = $end1 - $start1;
    $diff2 = $end2 - $start2;
    if( $diff2 < 0 ){
        $diff1 -= 1;
        $diff2 += 1.0;
    }
    return $diff2 + $diff1;
}

/**
* Displays message listing
*
* @param mixed $t UNDOCUMENTED
* @param bool $not_last UNDOCUMENTED
* @param mixed $key UNDOCUMENTED
* @param string $mailbox mail folder
* @param mixed $start_msg UNDOCUMENTED
* @param mixed $where UNDOCUMENTED
* @param mixed $what UNDOCUMENTED
*/

function printMessageInfo($t, $last=false, $msg, $mailbox,
                        $start_msg, $where, $what) {
    global $checkall,
        $color, $td_str,
        $default_use_priority,
        $message_highlight_list,
        $index_order,
        $indent_array,         /* indent subject by */
        $pos,                  /* Search postion (if any)  */
        $thread_sort_messages, /* thread sorting on/off */
        $row_count,
        $truncate_sender,      /* number of characters for From/To field (<= 0 for unchanged) */
        $email_address,
        $show_recipient_instead,	/* show recipient name instead of default identity */
        $use_icons,            /* indicates to use icons or text markers */
        $icon_theme;           /* icons theming */

    $color_string = $color[4];

    // initialisation:
   $sSubject = (isset($msg['SUBJECT']) && $msg['SUBJECT'] != '') ? $msg['SUBJECT'] : _("(no subject)");
   $sFrom    = (isset($msg['FROM'])) ? $msg['FROM'] : _("Unknown sender");
   $sTo      = (isset($msg['TO'])) ? $msg['TO'] : _("Unknown recipient");
   $sCc      = (isset($msg['CC'])) ? $msg['CC'] : '';
   $aFlags   = (isset($msg['FLAGS'])) ? $msg['FLAGS'] : array();
   $iPrio    = (isset($msg['PRIORITY'])) ? $msg['PRIORITY'] : 3;
   $iSize    = (isset($msg['SIZE'])) ? $msg['SIZE'] : 0;
   $sType0   = (isset($msg['TYPE0'])) ? $msg['TYPE0'] : 'text';
   $sType1   = (isset($msg['TYPE1'])) ? $msg['TYPE1'] : 'plain';
   $sDate    = (isset($msg['DATE'])) ? getDateString(getTimeStamp(explode(' ',$msg['DATE']))) : '';
   $iId      = (isset($msg['ID'])) ? $msg['ID'] : false;

   sqgetGlobalVar('indent_array',$indent_array,SQ_SESSION);
   if (!$iId) {
       return;
   }

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

    $urlMailbox = urlencode($mailbox);

    $bSentFolder = handleAsSent($mailbox);
    if ((!$bSentFolder) && ($show_recipient_instead)) {
        // If the From address is the same as $email_address, then handle as Sent
        $from_array = parseAddress($sFrom, 1);
        if (!isset($email_address)) {
            global $datadir, $username;
            $email_address = getPref($datadir, $username, 'email_address');
        }
        $bHandleAsSent = ((isset($from_array[0][0])) && ($from_array[0][0] == $email_address));
    }
    else
        $bHandleAsSent = $bSentFolder;
    // If this is a Sent message, display To address instead of From
    if ($bHandleAsSent) {
        $sFrom = $sTo;
    }
    // Passing 1 below results in only 1 address being parsed, thus defeating the following code
    $sFrom = parseAddress($sFrom/*,1*/);

    /*
        * This is done in case you're looking into Sent folders,
        * because you can have multiple receivers.
        */
    $senderNames = $sFrom;
    $senderName  = '';
    $senderAddress = '';
    if (sizeof($senderNames)){
        foreach ($senderNames as $senderNames_part) {
            if ($senderName != '') {
                $senderName .= ', ';
                $senderAddress .= ', ';
            }
            $sender_address_part = htmlspecialchars($senderNames_part[0]);
            $sender_name_part = str_replace('&nbsp;',' ', decodeHeader($senderNames_part[1]));
            if ($sender_name_part) {
                $senderName .= $sender_name_part;
                $senderAddress .= $sender_name_part . ' <' . $sender_address_part . '>';
            } else {
                $senderName .= $sender_address_part;
                $senderAddress .= $sender_address_part;
            }
        }
    }
    // If Sent, prefix with To: but only if not Sent folder
    if ($bHandleAsSent ^ $bSentFolder) {
        $senderName = _("To:") . ' ' . $senderName;
        $senderAddress = _("To:") . ' ' . $senderAddress;
    }

    if ($truncate_sender > 0)
    $senderName = truncateWithEntities($senderName, $truncate_sender);

    echo html_tag( 'tr','','','','VALIGN="top"') . "\n";

    if (isset($aFlags['\\flagged']) && ($aFlags['\\flagged'] == true)) {
        $flag = "<font color=\"$color[2]\">";
        $flag_end = '</font>';
    } else {
        $flag = '';
        $flag_end = '';
    }
    if (!isset($aFlags['\\seen']) || ($aFlags['\\seen'] == false)) {
        $bold = '<b>';
        $bold_end = '</b>';
    } else {
        $bold = '';
        $bold_end = '';
    }
    if ($bHandleAsSent) {
        $italic = '<i>';
        $italic_end = '</i>';
    } else {
        $italic = '';
        $italic_end = '';
    }
    if (isset($aFlags['\\deleted']) && $aFlags['\\deleted']) {
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

    $matches = array('TO' => 'sTo', 'CC' => 'sCc', 'FROM' => 'sFrom', 'SUBJECT' => 'sSubject');
    if (is_array($message_highlight_list) && count($message_highlight_list)) {
        $sTo = parseAddress($sTo);
        $sCc = parseAddress($sCc);
        foreach ($message_highlight_list as $message_highlight_list_part) {
            if (trim($message_highlight_list_part['value']) != '') {
                $high_val   = strtolower($message_highlight_list_part['value']);
                $match_type = strtoupper($message_highlight_list_part['match_type']);
                if($match_type == 'TO_CC') {
                    $match = array('TO', 'CC');
                } else {
                    $match = array($match_type);
                }
                foreach($match as $match_type) {
                    switch($match_type) {
                        case('TO'):
                        case('CC'):
                        case('FROM'):
                            foreach ($$matches[$match_type] as $address) {
                                $address[0] = decodeHeader($address[0], true, false);
                                $address[1] = decodeHeader($address[1], true, false);
                                if (strstr('^^' . strtolower($address[0]), $high_val) ||
                                    strstr('^^' . strtolower($address[1]), $high_val)) {
                                    $hlt_color = $message_highlight_list_part['color'];
                                    break 4;
                                }
                            }
                            break;
                        default:
                            $headertest = strtolower(decodeHeader($$matches[$match_type], true, false));
                            if (strstr('^^' . $headertest, $high_val)) {
                                $hlt_color = $message_highlight_list_part['color'];
                                break 3;
                            }
                            break;
                    }
                }
            }
        }
    }

    if (!isset($hlt_color)) {
        $hlt_color = $color_string;
    }
    $col = 0;
    $sSubject = str_replace('&nbsp;', ' ', decodeHeader($sSubject));
    if (isset($indent_array[$iId])) {
        $subject = processSubject($sSubject, $indent_array[$iId]);
    } else {
        $subject = processSubject($sSubject, 0);
    }
    if (sizeof($index_order)) {
        foreach ($index_order as $index_order_part) {
            switch ($index_order_part) {
            case 1: /* checkbox */
                echo html_tag( 'td',
                    addCheckBox("msg[$t]", $checkall, $iId),
                            'center',
                            $hlt_color );
                break;
            case 2: /* from */
                if ($senderAddress != $senderName) {
                    $senderAddress = strtr($senderAddress, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
                    $title = ' title="' . str_replace('"', "''", $senderAddress) . '"';
                }
                else
                    $title = '';
                echo html_tag( 'td',
                            $italic . $bold . $flag . $fontstr . $senderName .
                            $fontstr_end . $flag_end . $bold_end . $italic_end,
                            'left',
                            $hlt_color, $title );
                break;
            case 3: /* date */
                if ($sDate == '') {
                    $sDate = _("Unknown date");
                }
                echo html_tag( 'td',
                            $bold . $flag . $fontstr . $sDate .
                            $fontstr_end . $flag_end . $bold_end,
                            'center',
                            $hlt_color,
                            'nowrap' );
                break;
            case 4: /* subject */
                $td_str = $bold;
                if ($thread_sort_messages == 1) {
                    if (isset($indent_array[$iId])) {
                        $td_str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;",$indent_array[$iId]);
                    }
                }
                $td_str .= '<a href="read_body.php?mailbox='.$urlMailbox
                        .  '&amp;passed_id='. $msg["ID"]
                        .  '&amp;startMessage='.$start_msg.$searchstr.'"';
                $td_str .= ' ' .concat_hook_function('subject_link', array($start_msg, $searchstr));
                if ($subject != $sSubject) {
                    $title = get_html_translation_table(HTML_SPECIALCHARS);
                    $title = array_flip($title);
                    $title = strtr($sSubject, $title);
                    $title = str_replace('"', "''", $title);
                    $td_str .= " title=\"$title\"";
                }
                $td_str .= ">$flag$subject$flag_end</a>$bold_end";
                echo html_tag( 'td', $td_str, 'left', $hlt_color );
                break;
            case 5: /* flags */

                // icon message markers
                //
                if ($use_icons && $icon_theme != 'none') {
                    $td_str = "<b><small>";
                    if (isset($aFlags['\\flagged']) && $aFlags['\\flagged'] == true) {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/flagged.png" border="0" height="10" width="10" /> ';
                    }
                    if ($default_use_priority) {
                        if ( ($iPrio == 1) || ($iPrio == 2) ) {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/prio_high.png" border="0" height="10" width="5" /> ';
                        }
                        else if ($iPrio == 5) {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/prio_low.png" border="0" height="10" width="5" /> ';
                        }
                        else {
                            $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/transparent.png" border="0" width="5" /> ';
                        }
                    }
                    if ($sType1 == 'mixed') {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/attach.png" border="0" height="10" width="6" />';
                    } else {
                        $td_str .= '<img src="' . SM_PATH . 'images/themes/' . $icon_theme . '/transparent.png" border="0" width="6" />';
                    }

                    $msg_icon = '';
                    if (!isset($aFlags['\\seen']) || ($aFlags['\\seen']) == false) {
                        $msg_alt = '(' . _("New") . ')';
                        $msg_title = '(' . _("New") . ')';
                        $msg_icon .= SM_PATH . 'images/themes/' . $icon_theme . '/msg_new';
                    } else {
                        $msg_alt = '(' . _("Read") . ')';
                        $msg_title = '(' . _("Read") . ')';
                        $msg_icon .= SM_PATH . 'images/themes/' . $icon_theme . '/msg_read';
                    }
                    if (isset($aFlags['\\deleted']) && ($aFlags['\\deleted']) == true) {
                        $msg_icon .= '_deleted';
                    }
                    if (isset($aFlags['\\answered']) && ($aFlags['\\answered']) == true) {
                        $msg_alt = '(' . _("Answered") . ')';
                        $msg_title = '(' . _("Answered") . ')';
                        $msg_icon .= '_reply';
                    }
                    $td_str .= '<img src="' . $msg_icon . '.png" border="0" alt="'. $msg_alt . '" title="' . $msg_title . '" height="12" width="18" />';
                    $td_str .= '</small></b>';
                    echo html_tag( 'td',
                                $td_str,
                                'right',
                                $hlt_color,
                                'nowrap' );
                }


                // plain text message markers
                //
                else {
                    $stuff = false;
                    $td_str = "<b><small>";
                    if (isset($aFlags['\\answered']) && $aFlags['\\answered'] == true) {
                        $td_str .= _("A");
                        $stuff = true;
                    }
                    if ($sType1 == 'mixed') {
                        $td_str .= '+';
                        $stuff = true;
                    }
                    if ($default_use_priority) {
                        if ( ($iPrio == 1) || ($iPrio == 2) ) {
                            $td_str .= "<font color=\"$color[1]\">!</font>";
                            $stuff = true;
                        }
                        if ($iPrio == 5) {
                            $td_str .= "<font color=\"$color[8]\">?</font>";
                            $stuff = true;
                        }
                    }
                    if (isset($aFlags['\\deleted']) && $aFlags['\\deleted'] == true) {
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
                }
                break;
            case 6: /* size */
                echo html_tag( 'td',
                            $bold . $fontstr . show_readable_size($iSize) .
                            $fontstr_end . $bold_end,
                            'right',
                            $hlt_color );
                break;
            }
            ++$col;
        }
    }
    if ($last) {
        echo '</tr>'."\n";
    } else {
        echo '</tr>' . "\n" . '<tr><td colspan="' . $col . '" bgcolor="' .
            $color[0] . '" height="1"></td></tr>' . "\n";
    }
}

/**
* Does the $sort $_GET var to field mapping
*
* @param int $sort Field to sort on
* @param bool $bServerSort Server sorting is true
* @param mixed $key UNDOCUMENTED
* @return string $sSortField Field tosort on
*/
function getSortField($sort,$bServerSort) {
    switch($sort) {
        case SQSORT_NONE:
            $sSortField = 'UID';
            break;
        case SQSORT_DATE_ASC:
        case SQSORT_DATE_DEC:
            $sSortField = 'DATE';
            break;
        case SQSORT_FROM_ASC:
        case SQSORT_FROM_DEC:
            $sSortField = 'FROM';
            break;
        case SQSORT_SUBJ_ASC:
        case SQSORT_SUBJ_DEC:
            $sSortField = 'SUBJECT';
            break;
        case SQSORT_SIZE_ASC:
        case SQSORT_SIZE_DEC:
            $sSortField = ($bServerSort) ? 'SIZE' : 'RFC822.SIZE';
            break;
        case SQSORT_TO_ASC:
        case SQSORT_TO_DEC:
            $sSortField = 'TO';
            break;
        case SQSORT_CC_ASC:
        case SQSORT_CC_DEC:
            $sSortField = 'CC';
            break;
        case SQSORT_INT_DATE_ASC:
        case SQSORT_INT_DATE_DEC:
            $sSortField = ($bServerSort) ? 'ARRIVAL' : 'INTERNALDATE';
            break;
        default: $sSortField = 'DATE';
            break;
    }
    return $sSortField;
}

function get_sorted_msgs_list($imapConnection,$sort,$mode,&$error) {
    $bDirection = ($sort % 2);
    $error = false;
    switch ($mode) {
      case 'thread':
        $id = get_thread_sort($imapConnection);
        if ($id === false) {
            $error = '<b><small><center><font color=red>' .
                    _("Thread sorting is not supported by your IMAP server.") . '<br />' .
                    _("Please report this to the system administrator.").
                    '</center></small></b>';
        }
        break;
      case 'server_sort':
        $sSortField = getSortField($sort,true);
        $id = sqimap_get_sort_order($imapConnection, $sSortField, $bDirection);
        if ($id === false) {
            $error =  '<b><small><center><font color=red>' .
                _( "Server-side sorting is not supported by your IMAP server.") . '<br />' .
                _("Please report this to the system administrator.").
                '</center></small></b>';
        }
        break;
      default:
        $sSortField = getSortField($sort,false);
        $id = get_squirrel_sort($imapConnection, $sSortField, $bDirection);
        break;
    }
    return $id;
}

/**
* This function loops through a group of messages in the mailbox
* and shows them to the user.
*
* @param mixed $imapConnection
* @param string $mailbox mail folder
* @param mixed $num_msgs
* @param mixed $start_msg
* @param mixed $sort
* @param mixed $color
* @param mixed $show_num
* @param mixed $use_cache
* @param mixed $mode
*/
function showMessagesForMailbox($imapConnection, $mailbox, $num_msgs,
                                $start_msg, $sort, $color, $show_num,
                                $use_cache, $mode='',$mbxresponse) {
    global $msgs, $msort, $auto_expunge, $thread_sort_messages,$server_sort_array,
        $allow_server_sort, $server_sort_order;
    /* if there's no messages in this folder */
    if ($mbxresponse['EXISTS'] == 0) {
        $string = '<b>' . _("THIS FOLDER IS EMPTY") . '</b>';
        echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
        echo '     <tr><td>';
        echo '       <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0" bgcolor="'.$color[4].'">';
        echo '        <tr><td><br />';
        echo '            <table cellpadding="1" cellspacing="5" align="center" border="0">';
        echo '              <tr>' . html_tag( 'td', $string."\n", 'left')
                            . '</tr>';
        echo '            </table>';
        echo '        <br /></td></tr>';
        echo '       </table></td></tr>';
        echo '    </table>';
        return;
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
        } else if ($allow_server_sort == 1) {
            $mode = 'server_sort';
        } else {
            $mode = '';
        }

        if (isset($mbxresponse['SORT_ARRAY']) && is_array($mbxresponse['SORT_ARRAY'])) {
            $id = $mbxresponse['SORT_ARRAY'];
            if (sqsession_is_registered('msgs')) {
                sqsession_unregister('msgs');
            }
            $id_slice = array_slice($id,$start_msg-1, $show_num);
            if (count($id_slice)) {
                $msgs = sqimap_get_small_header_list($imapConnection,$id_slice,$show_num);
            } else {
                return false;
            }
            sqsession_register($msgs, 'msgs');
        } else {
            if (sqsession_is_registered('server_sort_array')) {
                sqsession_unregister('server_sort_array');
            }
            $id = get_sorted_msgs_list($imapConnection,$sort,$mode,$error);
            if ($id !== false) {
                sqsession_register($id, 'server_sort_array');
                $id_slice = array_slice($id,$start_msg-1, $show_num);
                if (count($id_slice)) {
                    $msgs = sqimap_get_small_header_list($imapConnection,$id_slice,$show_num);
                } else {
                    return false;
                }
                sqsession_register($msgs, 'msgs');
            } else {
                echo $error;
                return false;
            }

        }
    } /* if exists > 0 */

    $res = getEndMessage($start_msg, $show_num, $num_msgs);
    $start_msg = $res[0];
    $end_msg   = $res[1];

    $paginator_str = get_paginator_str($mailbox, $start_msg, $end_msg,
                                    $num_msgs, $show_num, $sort);

    $msg_cnt_str = get_msgcnt_str($start_msg, $end_msg, $num_msgs);

    do_hook('mailbox_index_before');
?>
<table border="0" width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td>
    <?php mail_message_listing_beginning($imapConnection, $mbxresponse, $mailbox, $sort,
                                        $msg_cnt_str, $paginator_str, $start_msg); ?>
    </td>
</tr>
<tr><td height="5" bgcolor="<?php echo $color[4]; ?>"></td></tr>
<tr>
    <td>
    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="<?php echo $color[9]; ?>">
        <tr>
        <td>
            <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="<?php echo $color[5]; ?>">
            <tr>
                <td>
                <?php
                    printHeader($mailbox, $sort, $color, !$thread_sort_messages, $start_msg);
                    displayMessageArray($imapConnection, $num_msgs, $start_msg,
                                        $id, $msgs, $mailbox, $sort, $show_num,0,0);
                ?>
                </td>
            </tr>
            </table>
        </td>
        </tr>
    </table>
    <?php
        mail_message_listing_end($num_msgs, $paginator_str, $msg_cnt_str, $color);
    ?>
    </td>
</tr>
</table>
<?php
    //$t = elapsed($start);
    //echo("elapsed time = $t seconds\n");
}


/**
* Generic function to convert the msgs array into an HTML table.
*
* @param resource $imapConnection
* @param int $num_msgs total number of messages in the mailbox
* @param int $start_msg offset in messages to sisplay
* @param array $msort sorted array which is used to map the index to the unsorted $msgs index
* @param string $mailbox mail folder name
* @param int $sort     sort order. 6 means no sorting or server side / thread sort
* @param array $color
* @param int $show_num number of messages to show
* @param mixed $where
* @param mixed $what
*/

// fix me:
// $color not used
// remove thread stuff
// remove $msgs global and add it as argument (i hate globals)
function displayMessageArray($imapConnection, $num_msgs, $start_msg,
                            $id, $msgs, $mailbox, $sort,
                            $show_num, $where=0, $what=0) {

    // if client side sorting and no sort we only fetch num_msgs so the start_msg in the $msgs
    // array must be corrected
    $i = $start_msg -1;

    /*
    * Loop through and display the info for each message.
    * ($t is used for the checkbox number)
    */

    $iEnd = $i + $show_num;
    for ($j=$i,$t=0;$j<$iEnd;++$j) {
        if (isset($id[$j])) {
            $last = (isset($id[$j+1]) || $j == $iEnd) ? false : true;
            $msg = $msgs[$id[$j]];
            printMessageInfo($t, $last, $msg, $mailbox,
                                $start_msg, $where, $what);
            ++$t;
        } else {
            break;
        }
    }
}

/**
* Displays the standard message list header.
*
* To finish the table, you need to do a "</table></table>";
*
* @param mixed $imapConnection
* @param array $mbxresponse the array with the results of SELECT against the current mailbox
* @param string $mailbox the current mailbox
* @param mixed $sort the current sorting method (-1 for no sorting available [searches])
* @param mixed $msg_cnt_str
* @param mixed $paginator
* @param mixed $start_msg
*/
function mail_message_listing_beginning ($imapConnection,
                                        $mbxresponse,
                                        $mailbox = '', $sort = -1,
                                        $msg_cnt_str = '',
                                        $paginator = '&nbsp;',
                                        $start_msg = 1) {
    global $color, $auto_expunge, $base_uri, $show_flag_buttons,
        $allow_server_sort, $server_sort_order,
        $PHP_SELF, $allow_thread_sort, $thread_sort_messages;

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

    if (!strpos($php_self,'?')) {
        $location = $php_self.'?mailbox=INBOX&amp;startMessage=1';
    } else {
        $location = $php_self;
    }

    $moveFields = addHidden('msg', $msg).
                addHidden('mailbox', $mailbox).
        addHidden('startMessage', $start_msg).
        addHidden('location', $location);

    /* build thread sorting links */
    if ($allow_thread_sort == TRUE) {
    if ($thread_sort_messages == 1 ) {
        $set_thread = 2;
        $thread_name = _("Unthread View");
    } elseif ($thread_sort_messages == 0) {
        $set_thread = 1;
        $thread_name = _("Thread View");
    }
    $thread_link_str = '<small>[<a href="' . $source_url . '?sort='
        . $sort . '&start_messages=1&set_thread=' . $set_thread
        . '&mailbox=' . urlencode($mailbox) . '">' . $thread_name
        . '</a>]</small>';
    }
    else
        $thread_link_str ='';

    /*
    * This is the beginning of the message list table.
    * It wraps around all messages
    */
    $safe_name = preg_replace("/[^0-9A-Za-z_]/", '_', $mailbox);
    $form_name = "FormMsgs" . $safe_name;

    echo '<form name="' . $form_name . '" method="post" action="move_messages.php">' ."\n"
        . $moveFields;
?>
    <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>">
        <tr>
        <td>
            <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                <td align="left"><small><?php echo $paginator . $thread_link_str; ?></small></td>
                <td align="center"></td>
                <td align="right"><small><?php echo $msg_cnt_str; ?></small></td>
            </tr>
            </table>
        </td>
        </tr>
        <tr width="100%" cellpadding="1"  cellspacing="0" border="0" bgcolor="<?php echo $color[0]; ?>">
        <td>
            <table border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                <td align="left">
                <small><?php

                    // display flag buttons only if supported
                    if ($show_flag_buttons && $mbxresponse != NULL &&
                    array_search('\\flagged',$mbxresponse['PERMANENTFLAGS'], true) !== FALSE) {
                        echo getButton('SUBMIT', 'markUnflagged',_("Unflag"));
                        echo getButton('SUBMIT', 'markFlagged',_("Flag"));
                        echo '&nbsp;';
                    }
                    if (array_search('\\seen',$mbxresponse['PERMANENTFLAGS'], true) !== FALSE) {
                        echo getButton('SUBMIT', 'markUnread',_("Unread"));
                        echo getButton('SUBMIT', 'markRead',_("Read"));
                        echo '&nbsp;';
                    }

                    echo getButton('SUBMIT', 'attache',_("Forward"));
                    echo '&nbsp;';
                    if (array_search('\\deleted',$mbxresponse['PERMANENTFLAGS'], true) !== FALSE) {
                        echo getButton('SUBMIT', 'delete',_("Delete"));
                        echo '<input type="checkbox" name="bypass_trash" />' . _("Bypass Trash");
                        echo '&nbsp;';
                    }
                    if (!$auto_expunge && $mbxresponse['RIGHTS'] != 'READ-ONLY') {
                    echo getButton('SUBMIT', 'expungeButton',_("Expunge"))  .'&nbsp;' . _("mailbox") . "\n";
                    echo '&nbsp;';
                    }
                    do_hook('mailbox_display_buttons');
                ?></small>
                </td>
                <?php
                if (array_search('\\deleted',$mbxresponse['PERMANENTFLAGS'], true) !== FALSE) {
                    echo '<td align="right">
                <small>';
                    //echo $thread_link_str;	//previous behaviour
                    getMbxList($imapConnection);
                    echo getButton('SUBMIT', 'moveButton',_("Move")) . "\n
                </small>";
                }
                ?>
                </td>
            </tr>
            </table>
        </td>
        </tr>
    </table>

<?php
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

/**
* FIXME: Undocumented function
*
* @param mixed $num_msgs
* @param mixed $paginator_str
* @param mixed $msg_cnt_str
* @param mixed $color
*/
function mail_message_listing_end($num_msgs, $paginator_str, $msg_cnt_str, $color) {
if ($num_msgs) {
    /* space between list and footer */
?>
<tr><td height="5" bgcolor="<?php echo $color[4]; ?>" colspan="1"></td></tr>
<tr>
    <td>
    <table width="100%" cellpadding="1"  cellspacing="0" style="border: 1px solid <?php echo $color[0]; ?>">
        <tr>
        <td>
            <table bgcolor="<?php echo $color[4]; ?>" border="0" width="100%" cellpadding="1"  cellspacing="0">
            <tr>
                <td align="left"><small><?php echo $paginator_str; ?></small></td>
                <td align="right"><small><?php echo $msg_cnt_str; ?></small></td>
            </tr>
            </table>
        </td>
        </tr>
    </table>
    </td>
</tr>
<?php
}
    /* End of message-list table */

    do_hook('mailbox_index_after');
    echo "</form>\n";
}

/**
* FIXME: Undocumented function
*
* @param string $mailbox
* @param mixed $sort
* @param mixed $color
* @param bool $showsort
* @param mixed $start_msg
*/
function printHeader($mailbox, $sort, $color, $showsort=true, $start_msg=1) {
    global $index_order, $internal_date_sort;
    echo html_tag( 'tr' ,'' , 'center', $color[5] );

    /* calculate the width of the subject column based on the
    * widths of the other columns */
    $widths = array(1=>1,2=>25,3=>5,4=>0,5=>1,6=>5);
    $subjectwidth = 100;
    foreach($index_order as $item) {
        $subjectwidth -= $widths[$item];
    }

    foreach ($index_order as $item) {
        switch ($item) {
        case 1: /* checkbox */
            echo html_tag( 'td',get_selectall_link($start_msg, $sort, $mailbox) , '', '', 'width="1%"' );
            break;
        case 5: /* flags */
            echo html_tag( 'td','' , '', '', 'width="1%"' );
            break;
        case 2: /* from */
            if (handleAsSent($mailbox)) {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                    . '<b>' . _("To") . '</b>';
                if ($showsort) {
                    ShowSortButton($sort, $mailbox, SQSORT_TO_ASC, SQSORT_TO_DEC);
                }
            } else {
                echo html_tag( 'td' ,'' , 'left', '', 'width="25%"' )
                    . '<b>' . _("From") . '</b>';
                if ($showsort) {
                    ShowSortButton($sort, $mailbox, SQSORT_FROM_ASC, SQSORT_FROM_DEC);
                }
            }
            echo "</td>\n";
            break;
        case 3: /* date */
            echo html_tag( 'td' ,'' , 'left', '', 'width="5%" nowrap' )
                . '<b>' . _("Date") . '</b>';
            if ($showsort) {
                if ($internal_date_sort) {
                    ShowSortButton($sort, $mailbox, SQSORT_INT_DATE_ASC, SQSORT_INT_DATE_DEC);
                } else {
                    ShowSortButton($sort, $mailbox, SQSORT_DATE_ASC, SQSORT_DATE_DEC);
                }
            }
            echo "</td>\n";
            break;
        case 4: /* subject */
            echo html_tag( 'td' ,'' , 'left', '', 'width="'.$subjectwidth.'%"' )
                . '<b>' . _("Subject") . '</b>';
            if ($showsort) {
                ShowSortButton($sort, $mailbox, SQSORT_SUBJ_ASC, SQSORT_SUBJ_DEC);
            }
            echo "</td>\n";
            break;
        case 6: /* size */
            echo html_tag( 'td', '', 'center','','width="5%" nowrap')
                . '<b>' . _("Size") . '</b>';
            if ($showsort) {
                ShowSortButton($sort, $mailbox, SQSORT_SIZE_ASC, SQSORT_SIZE_DEC);
            }
            echo "</td>\n";
            break;
        }
    }
    echo "</tr>\n";
}


/**
* This function shows the sort button. Isn't this a good comment?
*
* @param mixed $sort
* @param string $mailbox
* @param mixed $Down
* @param mixed $Up
*/
function ShowSortButton($sort, $mailbox, $Down, $Up ) {
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
        $which = 0;
    }

    if (preg_match('/^(.+)\?.+$/',$PHP_SELF,$regs)) {
        $source_url = $regs[1];
    } else {
        $source_url = $PHP_SELF;
    }

    /* Now that we have everything figured out, show the actual button. */
    echo ' <a href="' . $source_url .'?newsort=' . $which
        . '&amp;startMessage=1&amp;mailbox=' . urlencode($mailbox)
        . '"><img src="../images/' . $img
        . '" border="0" width="12" height="10" alt="sort" title="'
        . _("Click here to change the sorting of the message list") .' /"></a>';
}

/**
* FIXME: Undocumented function
*
* @param mixed $start_msg
* @param mixed $sort
* @param string $mailbox
*/
function get_selectall_link($start_msg, $sort, $mailbox) {
    global $checkall, $what, $where, $javascript_on;
    global $PHP_SELF, $PG_SHOWNUM;

    $result = '';
    if ($javascript_on) {
        $safe_name = preg_replace("/[^0-9A-Za-z_]/", '_', $mailbox);
        $func_name = "CheckAll" . $safe_name;
        $form_name = "FormMsgs" . $safe_name;
        $result = '<script language="JavaScript" type="text/javascript">'
                . "\n<!-- \n"
                . "function " . $func_name . "() {\n"
                . "  for (var i = 0; i < document." . $form_name . ".elements.length; i++) {\n"
                . "    if(document." . $form_name . ".elements[i].type == 'checkbox' && "
                . "       document." . $form_name . ".elements[i].name.substring(0,3) == 'msg'){\n"
                . "      document." . $form_name . ".elements[i].checked = "
                . "        !(document." . $form_name . ".elements[i].checked);\n"
                . "    }\n"
                . "  }\n"
                . "}\n"
                . "//-->\n"
                . '</script>'
                . '<input type="checkbox" name="toggleAll" title="'._("Toggle All").'" onclick="'.$func_name.'();" />';
//                . <a href="javascript:void(0)" onClick="' . $func_name . '();">' . _("Toggle All")
//                . "</a>\n";
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
        $result .= _("All");
        $result .= "</a>\n";
    }

    /* Return our final result. */
    return ($result);
}

/**
* This function computes the "Viewing Messages..." string.
*
* @param integer $start_msg first message number
* @param integer $end_msg last message number
* @param integer $num_msgs total number of message in folder
* @return string
*/
function get_msgcnt_str($start_msg, $end_msg, $num_msgs) {
    /* Compute the $msg_cnt_str. */
    $result = '';
    if ($start_msg < $end_msg) {
        $result = sprintf(_("Viewing Messages: %s to %s (%s total)"),
                        '<b>'.$start_msg.'</b>', '<b>'.$end_msg.'</b>', $num_msgs);
    } else if ($start_msg == $end_msg) {
        $result = sprintf(_("Viewing Message: %s (1 total)"), '<b>'.$start_msg.'</b>');
    } else {
        $result = '<br />';
    }
    /* Return our result string. */
    return ($result);
}

/**
* Generate a paginator link.
*
* @param mixed $box
* @param mixed $start_msg
* @param mixed $use
* @param string $text text used for paginator link
* @return string
*/
function get_paginator_link($box, $start_msg, $use, $text) {

    $result = "<a href=\"right_main.php?use_mailbox_cache=$use"
            . "&amp;startMessage=$start_msg&amp;mailbox=$box\" "
            . ">$text</a>";

    return ($result);
}

/**
* This function computes the paginator string.
*
* @param mixed $box
* @param mixed $start_msg
* @param mixed $end_msg
* @param integer $num_msgs
* @param mixed $show_num
* @param mixed $sort
*/
function get_paginator_str($box, $start_msg, $end_msg, $num_msgs,
                        $show_num, $sort) {
    global $username, $data_dir, $use_mailbox_cache, $color, $PG_SHOWNUM;

    /* Initialize paginator string chunks. */
    $prv_str = '';
    $nxt_str = '';
    $pg_str  = '';
    $all_str = '';

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
        $nxt_str = _("Next");
    } else if (($next_grp <= $num_msgs) && ($prev_grp < 0)) {
        $prv_str = _("Previous");
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
                $extra_pgs -= ceil(($cur_pg - $q1_pgs - 1) * 3/4);
                $q2_pgs = ceil(($cur_pg - $q1_pgs - 1) * 3/4);
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
                $extra_pgs -= ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                $q3_pgs = ceil(($tot_pgs - $cur_pg - $q4_pgs) * 3/4);
                $q1_pgs += floor($extra_pgs / 2);
                $q2_pgs += ceil($extra_pgs / 2);
            }
        }

        /*
        * I am leaving this debug code here, commented out, because
        * it is a really nice way to see what the above code is doing.
        * echo "qts =  $q1_pgs/$q2_pgs/$q3_pgs/$q4_pgs = "
        *    . ($q1_pgs + $q2_pgs + $q3_pgs + $q4_pgs) . '<br />';
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
        $pg_str = "<a href=\"right_main.php?PG_SHOWALL=0"
                . "&amp;use_mailbox_cache=$use&amp;startMessage=1&amp;mailbox=$box\" "
                . ">" ._("Paginate") . '</a>';
    }

    /* Put all the pieces of the paginator string together. */
    /**
    * Hairy code... But let's leave it like it is since I am not certain
    * a different approach would be any easier to read. ;)
    */
    $result = '';
    if ( $prv_str != '' || $nxt_str != '' )
    {
    $result .= '[';
    $result .= ($prv_str != '' ? $prv_str . $spc . $sep . $spc : '');
    $result .= ($nxt_str != '' ? $nxt_str : '');
    $result .= ']' . $spc ;

    /* Compute the 'show all' string. */
    $all_str = "<a href=\"right_main.php?PG_SHOWALL=1"
                . "&amp;use_mailbox_cache=$use&amp;startMessage=1&amp;mailbox=$box\" "
                . ">" . _("Show All") . '</a>';
    }

    $result .= ($pg_str  != '' ? $spc . '['.$spc.$pg_str.']' .  $spc : '');
    $result .= ($all_str != '' ? $spc . '['.$all_str.']' . $spc . $spc : '');

    /* If the resulting string is blank, return a non-breaking space. */
    if ($result == '') {
        $result = '&nbsp;';
    }

    /* Return our final magical paginator string. */
    return ($result);
}

/**
* FIXME: Undocumented function
*/
function truncateWithEntities($subject, $trim_at)
{
    $ent_strlen = strlen($subject);
    if (($trim_at <= 0) || ($ent_strlen <= $trim_at))
        return $subject;

    global $languages, $squirrelmail_language;

    /*
    * see if this is entities-encoded string
    * If so, Iterate through the whole string, find out
    * the real number of characters, and if more
    * than $trim_at, substr with an updated trim value.
    */
    $trim_val = $trim_at;
    $ent_offset = 0;
    $ent_loc = 0;
    while ( $ent_loc < $trim_val && (($ent_loc = strpos($subject, '&', $ent_offset)) !== false) &&
            (($ent_loc_end = strpos($subject, ';', $ent_loc+3)) !== false) ) {
        $trim_val += ($ent_loc_end-$ent_loc);
        $ent_offset  = $ent_loc_end+1;
    }
    if (($trim_val > $trim_at) && ($ent_strlen > $trim_val) && (strpos($subject,';',$trim_val) < ($trim_val + 6))) {
        $i = strpos($subject,';',$trim_val);
        if ($i) {
            $trim_val = strpos($subject,';',$trim_val);
        }
    }
    // only print '...' when we're actually dropping part of the subject
    if ($ent_strlen <= $trim_val)
        return $subject;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        return $languages[$squirrelmail_language]['XTRA_CODE']('strimwidth', $subject, $trim_val);
    }

    return substr_replace($subject, '...', $trim_val);
}

/**
* FIXME: Undocumented function
*/
function processSubject($subject, $threadlevel = 0) {
    /* Shouldn't ever happen -- caught too many times in the IMAP functions */
    if ($subject == '') {
        return _("(no subject)");
    }

    global $truncate_subject;     /* number of characters for Subject field (<= 0 for unchanged) */
    $trim_at = $truncate_subject;

    /* if this is threaded, subtract two chars per indentlevel */
    if (($threadlevel > 0) && ($threadlevel <= 10))
        $trim_at -= (2*$threadlevel);

    return truncateWithEntities($subject, $trim_at);
}

/**
* FIXME: Undocumented function
*
* @param mixed $imapConnection
* @param mixed $boxes
*/
function getMbxList($imapConnection, $boxes = 0) {
    global $lastTargetMailbox;
    echo  '         <small>&nbsp;<tt><select name="targetMailbox">';
    echo sqimap_mailbox_option_list($imapConnection, array(strtolower($lastTargetMailbox)), 0, $boxes);
    echo '         </select></tt>&nbsp;';
}

/**
* Creates button
*
* @deprecated see form functions available in 1.5.1 and 1.4.3.
* @param string $type
* @param string $name
* @param string $value
* @param string $js
* @param bool $enabled
*/
function getButton($type, $name, $value, $js = '', $enabled = TRUE) {
    $disabled = ( $enabled ? '' : 'disabled ' );
    $js = ( $js ? $js.' ' : '' );
    return '<input '.$disabled.$js.
            'type="'.$type.
            '" name="'.$name.
            '" value="'.$value .
            '" style="padding: 0px; margin: 0px" />';
}

/**
* Puts string into cell, aligns it and adds <small> tag
*
* @param string $string string
* @param string $align alignment
*/
function getSmallStringCell($string, $align) {
    return html_tag('td',
                    '<small>' . $string . ':&nbsp; </small>',
                    $align,
                    '',
                    'nowrap' );
}

/**
* FIXME: Undocumented function
*
* @param integer $start_msg
* @param integer $show_num
* @param integer $num_msgs
*/
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

/**
* This should go in imap_mailbox.php
* @param string $mailbox
*/
function handleAsSent($mailbox) {
    global $handleAsSent_result;

    /* First check if this is the sent or draft folder. */
    $handleAsSent_result = isSentMailbox($mailbox) || isDraftMailbox($mailbox);

    /* Then check the result of the handleAsSent hook. */
    do_hook('check_handleAsSent_result', $mailbox);

    /* And return the result. */
    return $handleAsSent_result;
}

?>
