<?php

/**
 * imap_messages.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements functions that manipulate messages
 *
 * $Id$
 */

/* NOTE: quite some functions in this file are not used anymore. */

/* Copies specified messages to specified folder */
/* obsolete */
function sqimap_messages_copy ($imap_stream, $start, $end, $mailbox) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "COPY $start:$end \"$mailbox\"", true, $response, $message, $uid_support);
}

function sqimap_msgs_list_copy ($imap_stream, $id, $mailbox) {
    global $uid_support;
    $msgs_id = sqimap_message_list_squisher($id);    
    $read = sqimap_run_command ($imap_stream, "COPY $msgs_id \"$mailbox\"", true, $response, $message, $uid_support);
    $read = sqimap_run_command ($imap_stream, "STORE $msgs_id +FLAGS (\\Deleted)", true, $response, $message, $uid_support);
}


/* Deletes specified messages and moves them to trash if possible */
/* obsolete */
function sqimap_messages_delete ($imap_stream, $start, $end, $mailbox) {
    global $move_to_trash, $trash_folder, $auto_expunge, $uid_support;

    if (($move_to_trash == true) && (sqimap_mailbox_exists($imap_stream, $trash_folder) && ($mailbox != $trash_folder))) {
        sqimap_messages_copy ($imap_stream, $start, $end, $trash_folder);
    }
    sqimap_messages_flag ($imap_stream, $start, $end, "Deleted", true);
}

function sqimap_msgs_list_delete ($imap_stream, $mailbox, $id) {
    global $move_to_trash, $trash_folder, $uid_support;
    $msgs_id = sqimap_message_list_squisher($id);
    if (($move_to_trash == true) && (sqimap_mailbox_exists($imap_stream, $trash_folder) && ($mailbox != $trash_folder))) {
        $read = sqimap_run_command ($imap_stream, "COPY $msgs_id \"$trash_folder\"", true, $response, $message, $uid_support);
    }
    $read = sqimap_run_command ($imap_stream, "STORE $msgs_id +FLAGS (\\Deleted)", true, $response, $message, $uid_support);
}


/* Sets the specified messages with specified flag */
function sqimap_messages_flag ($imap_stream, $start, $end, $flag, $handle_errors) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "STORE $start:$end +FLAGS (\\$flag)", $handle_errors, $response, $message, $uid_support);
}

/* Remove specified flag from specified messages */
function sqimap_messages_remove_flag ($imap_stream, $start, $end, $flag, $handle_errors) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "STORE $start:$end -FLAGS (\\$flag)", $handle_errors, $response, $message, $uid_support);
}

function sqimap_toggle_flag($imap_stream, $id, $flag, $set, $handle_errors) {
    global $uid_support;
    $msgs_id = sqimap_message_list_squisher($id);
    $set_string = ($set ? '+' : '-');
    $read = sqimap_run_command ($imap_stream, "STORE $msgs_id ".$set_string."FLAGS ($flag)", $handle_errors, $response, $message, $uid_support);
}

// obsolete?
function sqimap_get_small_header ($imap_stream, $id, $sent) {
    $res = sqimap_get_small_header_list($imap_stream, $id, $sent);
    return $res[0];
}

/*
 * Sort the message list and crunch to be as small as possible
 * (overflow could happen, so make it small if possible)
 */
function sqimap_message_list_squisher($messages_array) {
    if( !is_array( $messages_array ) ) {
        return $messages_array;
    }

    sort($messages_array, SORT_NUMERIC);
    $msgs_str = '';
    while ($messages_array) {
        $start = array_shift($messages_array);
        $end = $start;
        while (isset($messages_array[0]) && $messages_array[0] == $end + 1) {
            $end = array_shift($messages_array);
        }
        if ($msgs_str != '') {
            $msgs_str .= ',';
        }
        $msgs_str .= $start;
        if ($start != $end) {
            $msgs_str .= ':' . $end;
        }
    }
    return $msgs_str;
}

/* returns the references header lines */
function get_reference_header ($imap_stream, $message) {
    global $uid_support;
    $responses = array ();
    $results = array();
    $references = "";
    $responses = sqimap_run_command_list ($imap_stream, "FETCH $message BODY[HEADER.FIELDS (References)]", true, $response, $message, $uid_support);
    if (!eregi("^\\* ([0-9]+) FETCH", $responses[0][0], $regs)) {
        $responses = array ();
    } 
    return $responses;
}


/* get sort order from server and
 * return it as the $id array for
 * mailbox_display
 */

function sqimap_get_sort_order ($imap_stream, $sort, $mbxresponse) {
    global  $default_charset, $thread_sort_messages,
            $internal_date_sort, $server_sort_array,
            $sent_folder, $mailbox, $uid_support;

    if (sqsession_is_registered('server_sort_array')) {
        sqsession_unregister('server_sort_array');
    }

    $sort_on = array();
    $reverse = 0;
    $server_sort_array = array();
    $sort_test = array();
    $sort_query = '';

    if ($sort == 6) {
        if ($uid_support) {
            if (isset($mbxresponse['UIDNEXT']) && $mbxresponse['UIDNEXT']) {
                $uidnext = $mbxresponse['UIDNEXT']-1;
            } else {
                $uidnext = '*';
            }
            $query = "SEARCH UID 1:$uidnext";
            $uids = sqimap_run_command ($imap_stream, $query, true, $response, $message, true);
            if (isset($uids[0])) {
                if (preg_match("/^\* SEARCH (.+)$/", $uids[0], $regs)) {
                    $server_sort_array = preg_split("/ /", trim($regs[1]));
                }
            }
            if (!preg_match("/OK/", $response)) {
                $server_sort_array = 'no';
            }
        } else {
            $qty = $mbxresponse['EXISTS'];
            $server_sort_array = range(1, $qty);
        }
        $server_sort_array = array_reverse($server_sort_array);
        sqsession_register($server_sort_array, 'server_sort_array');
        return $server_sort_array;
    }

    $sort_on = array (0=> 'DATE',
                      1=> 'DATE',
                      2=> 'FROM',
                      3=> 'FROM',
                      4=> 'SUBJECT',
                      5=> 'SUBJECT');
    if ($internal_date_sort == true) {
        $sort_on[0] = 'ARRIVAL';
        $sort_on[1] = 'ARRIVAL';
    }
    if ($sent_folder == $mailbox) {
        $sort_on[2] = 'TO';
        $sort_on[3] = 'TO';
    }
    if (!empty($sort_on[$sort])) {
        $query = "SORT ($sort_on[$sort]) ".strtoupper($default_charset).' ALL';
        $sort_test = sqimap_run_command ($imap_stream, $query, true, $response, $message, $uid_support);
    }
    if (isset($sort_test[0])) {
        if (preg_match("/^\* SORT (.+)$/", $sort_test[0], $regs)) {
            $server_sort_array = preg_split("/ /", trim($regs[1]));
        }
    }
    if ($sort == 0 || $sort == 2 || $sort == 4) {
       $server_sort_array = array_reverse($server_sort_array);
    }
    if (!preg_match("/OK/", $response)) {
       $server_sort_array = 'no';
    }
    sqsession_register($server_sort_array, 'server_sort_array');
    return $server_sort_array;
}


function sqimap_get_php_sort_order ($imap_stream, $mbxresponse) {
    global $uid_support;

    if (sqsession_is_registered('php_sort_array')) {
        sqsession_unregister('php_sort_array');
    }

    $php_sort_array = array();

    if ($uid_support) {
        if (isset($mbxresponse['UIDNEXT']) && $mbxresponse['UIDNEXT']) {
                $uidnext = $mbxresponse['UIDNEXT']-1;
        } else {
            $uidnext = '*';
        }
        $query = "SEARCH UID 1:$uidnext";
        $uids = sqimap_run_command ($imap_stream, $query, true, $response, $message, true);
        if (isset($uids[0])) {
            if (preg_match("/^\* SEARCH (.+)$/", $uids[0], $regs)) {
                $php_sort_array = preg_split("/ /", trim($regs[1]));
            }
        }
        if (!preg_match("/OK/", $response)) {
            $php_sort_array = 'no';
        }
    } else {
       $qty = $mbxresponse['EXISTS'];
       $php_sort_array = range(1, $qty);
    }
    sqsession_register($php_sort_array, 'php_sort_array');
    return $php_sort_array;
}


/* returns an indent array for printMessageinfo()
   this represents the amount of indent needed (value)
   for this message number (key)
*/

function get_parent_level ($imap_stream) {
    global $sort_by_ref, $default_charset, $thread_new;
        $parent = "";
        $child = "";
        $cutoff = 0;

    /* loop through the threads and take unwanted characters out 
       of the thread string then chop it up 
     */
    for ($i=0;$i<count($thread_new);$i++) {
        $thread_new[$i] = preg_replace("/\s\(/", "(", $thread_new[$i]);
        $thread_new[$i] = preg_replace("/(\d+)/", "$1|", $thread_new[$i]);
        $thread_new[$i] = preg_split("/\|/", $thread_new[$i], -1, PREG_SPLIT_NO_EMPTY);
    } 
    $indent_array = array();
        if (!$thread_new) {
            $thread_new = array();
        }
    /* looping through the parts of one message thread */

    for ($i=0;$i<count($thread_new);$i++) {
    /* first grab the parent, it does not indent */

        if (isset($thread_new[$i][0])) {
            if (preg_match("/(\d+)/", $thread_new[$i][0], $regs)) {
                $parent = $regs[1];
            }
        }
        $indent_array[$parent] = 0;

    /* now the children, checking each thread portion for
       ),(, and space, adjusting the level and space values
       to get the indent level
    */
        $level = 0;
        $spaces = array();
        $spaces_total = 0;
        $indent = 0;
        $fake = FALSE;
        for ($k=1;$k<(count($thread_new[$i]))-1;$k++) {
            $chars = count_chars($thread_new[$i][$k], 1);
            if (isset($chars['40'])) {       /* testing for ( */
                $level = $level + $chars['40'];
            }
            if (isset($chars['41'])) {      /* testing for ) */
                $level = $level - $chars['41'];
                $spaces[$level] = 0;
                /* if we were faking lets stop, this portion
                   of the thread is over
                */
                if ($level == $cutoff) {
                    $fake = FALSE;
                }
            }
            if (isset($chars['32'])) {      /* testing for space */
                if (!isset($spaces[$level])) {
                    $spaces[$level] = 0;
                }
                $spaces[$level] = $spaces[$level] + $chars['32'];
            }
            for ($x=0;$x<=$level;$x++) {
                if (isset($spaces[$x])) {
                    $spaces_total = $spaces_total + $spaces[$x];
                }
            }
            $indent = $level + $spaces_total;
            /* must have run into a message that broke the thread
               so we are adjusting for that portion
            */
            if ($fake == TRUE) {
                $indent = $indent +1;
            }
            if (preg_match("/(\d+)/", $thread_new[$i][$k], $regs)) {
                $child = $regs[1];
            }
            /* the thread must be broken if $indent == 0
               so indent the message once and start faking it
            */
            if ($indent == 0) {
                $indent = 1;
                $fake = TRUE;
                $cutoff = $level;
            }
            /* dont need abs but if indent was negative
               errors would occur
            */
            $indent_array[$child] = abs($indent);
            $spaces_total = 0;
        }
    }
    return $indent_array;
}


/* returns an array with each element as a string
   representing one message thread as returned by
   the IMAP server
*/

function get_thread_sort ($imap_stream) {
    global $thread_new, $sort_by_ref, $default_charset, $server_sort_array, $uid_support;
    if (sqsession_is_registered('thread_new')) {
        sqsession_unregister('thread_new');
    }
    if (sqsession_is_registered('server_sort_array')) {
        sqsession_unregister('server_sort_array');
    }
    $thread_temp = array ();
    if ($sort_by_ref == 1) {
        $sort_type = 'REFERENCES';
    }
    else {
        $sort_type = 'ORDEREDSUBJECT';
    }
    $query = "THREAD $sort_type ".strtoupper($default_charset)." ALL";
    $thread_test = sqimap_run_command ($imap_stream, $query, true, $response, $message, $uid_support);
    if (isset($thread_test[0])) {
        if (preg_match("/^\* THREAD (.+)$/", $thread_test[0], $regs)) {
            $thread_list = trim($regs[1]);
        }
    }
    else {
       $thread_list = "";
    }
    if (!preg_match("/OK/", $response)) {
       $server_sort_array = 'no';
       return $server_sort_array;
    }
    if (isset($thread_list)) {
        $thread_temp = preg_split("//", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
    }
    $char_count = count($thread_temp);
    $counter = 0;
    $thread_new = array();
    $k = 0;
    $thread_new[0] = "";
    for ($i=0;$i<$char_count;$i++) {
            if ($thread_temp[$i] != ')' && $thread_temp[$i] != '(') {
                    $thread_new[$k] = $thread_new[$k] . $thread_temp[$i];
            }
            elseif ($thread_temp[$i] == '(') {
                    $thread_new[$k] .= $thread_temp[$i];
                    $counter++;
            }
            elseif ($thread_temp[$i] == ')') {
                    if ($counter > 1) {
                            $thread_new[$k] .= $thread_temp[$i];
                            $counter = $counter - 1;
                    }
                    else {
                            $thread_new[$k] .= $thread_temp[$i];
                            $k++;
                            $thread_new[$k] = "";
                            $counter = $counter - 1;
                    }
            }
    }
    sqsession_register($thread_new, 'thread_new');
    $thread_new = array_reverse($thread_new);
    $thread_list = implode(" ", $thread_new);
    $thread_list = str_replace("(", " ", $thread_list);
    $thread_list = str_replace(")", " ", $thread_list);
    $thread_list = preg_split("/\s/", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
    $server_sort_array = $thread_list;
    sqsession_register($server_sort_array, 'server_sort_array');
    return $thread_list;
}


function elapsedTime($start) {
 $stop = gettimeofday();
 $timepassed =  1000000 * ($stop['sec'] - $start['sec']) + $stop['usec'] - $start['usec'];
 return $timepassed;
}

// only used in sqimap_get_small_header_list
function parseString($read,&$i) {
    $char = $read{$i};
    $s = '';
    if ($char == '"') {
       $iPos = ++$i;
       while (true) {
          $iPos = strpos($read,'"',$iPos);
          if (!$iPos) break;
             if ($iPos && $read{$iPos -1} != '\\') {
                $s = substr($read,$i,($iPos-$i));
                $i = $iPos;
                break;
             }
             $iPos++;
             if ($iPos > strlen($read)) {
                break;
             }
       }
    } else if ($char == '{') {
        $lit_cnt = '';
        ++$i;
        $iPos = strpos($read,'}',$i);
        if ($iPos) {
           $lit_cnt = substr($read, $i, $iPos - $i);
           $i += strlen($lit_cnt) + 3; /* skip } + \r + \n */
           /* Now read the literal */
           $s = ($lit_cnt ? substr($read,$i,$lit_cnt): '');
           $i += $lit_cnt;
           /* temp bugfix (SM 1.5 will have a working clean version)
              too much work to implement that version right now */
           --$i;
         } else { /* should never happen */
            $i += 3; /* } + \r + \n */
            $s = '';
         }
    } else {
       return false;
    }
    ++$i;
    return $s;
}

// only used in sqimap_get_small_header_list
function parseArray($read,&$i) {
    $i = strpos($read,'(',$i);
    $i_pos = strpos($read,')',$i);
    $s = substr($read,$i+1,$i_pos - $i -1);
    $a = explode(' ',$s);
    if ($i_pos) {
        $i = $i_pos+1;
        return $a;
    } else {
        return false;
    }
}

function sqimap_get_small_header_list ($imap_stream, $msg_list, $show_num=false) {
    global $squirrelmail_language, $color, $data_dir, $username, $imap_server_type;
    global $uid_support, $allow_server_sort;
    /* Get the small headers for each message in $msg_list */
    $maxmsg = sizeof($msg_list);
    if ($show_num != '999999') {
        $msgs_str = sqimap_message_list_squisher($msg_list);
    } else { 
        $msgs_str = '1:*';
    }
    $messages = array();
    $read_list = array();

    /*
     * We need to return the data in the same order as the caller supplied
     * in $msg_list, but IMAP servers are free to return responses in
     * whatever order they wish... So we need to re-sort manually
     */
    for ($i = 0; $i < sizeof($msg_list); $i++) {
        $messages["$msg_list[$i]"] = array();
    }

    $internaldate = getPref($data_dir, $username, 'internal_date_sort');
    if ($internaldate) {
        $query = "FETCH $msgs_str (FLAGS UID RFC822.SIZE INTERNALDATE BODY.PEEK[HEADER.FIELDS (Date To Cc From Subject X-Priority Content-Type)])";
    } else {
        $query = "FETCH $msgs_str (FLAGS UID RFC822.SIZE BODY.PEEK[HEADER.FIELDS (Date To Cc From Subject X-Priority Content-Type)])";
    }
    $read_list = sqimap_run_command_list ($imap_stream, $query, true, $response, $message, $uid_support);
    $i = 0;
    
    foreach ($read_list as $r) {
        $subject = _("(no subject)");
        $from = _("Unknown Sender");
        $priority = 0;
        $messageid = '<>';
        $cc = $to = $date = $type[0] = $type[1] = $inrepto = '';
        $flag_seen = $flag_answered = $flag_deleted = $flag_flagged = false;

        $read = implode('',$r);

        /* 
            * #id<space>FETCH<space>(
        */
    
        /* extract the message id */
        $i_space = strpos($read,' ',2);
        $id = substr($read,2,$i_space-2);
        $fetch = substr($read,$i_space+1,5);
        if (!is_numeric($id) && $fetch !== 'FETCH') {
            set_up_language($squirrelmail_language);
            echo '<br><b><font color=$color[2]>' .
                 _("ERROR : Could not complete request.") .
                 '</b><br>' .
                 _("Unknown response from IMAP server: ") . ' 1.' .
                 htmlspecialchars($read) . "</font><br>\n";
                 break;
        }
        $i = strpos($read,'(',$i_space+5);
        $read = substr($read,$i+1);
        $i_len = strlen($read);
        $i = 0;
        while ($i < $i_len && $i !== false) {
            /* get argument */
            $read = trim(substr($read,$i));
            $i_len = strlen($read);
            $i = strpos($read,' ');
            $arg = substr($read,0,$i);
            ++$i;
            switch ($arg)
            {
            case 'UID':
                $i_pos = strpos($read,' ',$i);
                if (!$i_pos) {
                    $i_pos = strpos($read,')',$i);
                }
                if ($i_pos) {
                    $unique_id = substr($read,$i,$i_pos-$i);
                    $i = $i_pos+1;
                } else {
                    break 3;
                }
                break;
            case 'FLAGS':
                $flags = parseArray($read,$i);
                if (!$flags) break 3;
                foreach ($flags as $flag) {
                    $flag = strtolower($flag);
                    switch ($flag)
                    {
                    case '\\seen': $flag_seen = true; break;
                    case '\\answered': $flag_answered = true; break;
                    case '\\deleted': $flag_deleted = true; break;
                    case '\\flagged': $flag_flagged = true; break;
                    default: break;
                    }
                }
                break;
            case 'RFC822.SIZE':
                $i_pos = strpos($read,' ',$i);
                if (!$i_pos) {
                    $i_pos = strpos($read,')',$i);
                }
                if ($i_pos) {
                    $size = substr($read,$i,$i_pos-$i);
                    $i = $i_pos+1;
                } else {
                    break 3;
                }
                
                break;
            case 'INTERNALDATE':
                $date = parseString($read,$i);
                //if ($tmpdate === false) break 3;
                //$tmpdate = str_replace('  ',' ',$tmpdate);
                //$tmpdate = explode(' ',$tmpdate);
                //$date = str_replace('-',' ',$tmpdate[0]) . " " . 
                //                            $tmpdate[1] . ' ' . $tmpdate[2];
                break;
            case 'BODY.PEEK[HEADER.FIELDS':
            case 'BODY[HEADER.FIELDS':
                $i = strpos($read,'{',$i);
                $header = parseString($read,$i);
                if ($header === false) break 3;
                /* First we unfold the header */
                $hdr = trim(str_replace(array("\r\n\t", "\r\n "),array('', ''), $header));
                /* Now we can make a new header array with */
                /* each element representing a headerline  */
                $hdr = explode("\r\n" , $hdr);
                foreach ($hdr as $line) {
                    $pos = strpos($line, ':');
                    if ($pos > 0) {
                        $field = strtolower(substr($line, 0, $pos));
                        if (!strstr($field,' ')) { /* valid field */
                            $value = trim(substr($line, $pos+1));
                            switch($field)
                            {
                            case 'to': $to = $value; break;
                            case 'cc': $cc = $value; break;
                            case 'from': $from = $value; break;
                            case 'date': $date = $value; break;
                            case 'x-priority': $priority = $value; break;
                            case 'subject':
                                $subject = $value;
                                if ($subject == "") {
                                    $subject = _("(no subject)");
                                }
                                break;
                            case 'content-type':
                                $type = $value;
                                if ($pos = strpos($type, ";")) {
                                    $type = substr($type, 0, $pos);
                                }
                                $type = explode("/", $type);
                                if(!is_array($type)) {
                                    $type[0] = 'text';
                                }
                                if (!isset($type[1])) {
                                    $type[1] = '';
                                }
                                break;
                            default: break;
                            }
                        }
                    }
                }
                break;
            default:
                ++$i;
                break;
            }
        }
        if (isset($date)) {
            $date = str_replace('  ', ' ', $date);
            $tmpdate  = explode(' ', trim($date));
        } else {
            $tmpdate = $date = array('', '', '', '', '', '');
        }
        if ($uid_support) {
            $msgi ="$unique_id";
            $messages[$msgi]['ID'] = $unique_id;
        } else {
            $msgi = "$id";
            $messages[$msgi]['ID'] = $id;
        }
        $messages[$msgi]['TIME_STAMP'] = getTimeStamp($tmpdate);
        $messages[$msgi]['DATE_STRING'] = getDateString($messages[$msgi]['TIME_STAMP']);
        $messages[$msgi]['FROM'] = $from; //parseAddress($from);
        $messages[$msgi]['SUBJECT'] = $subject;
//        if (handleAsSent($mailbox)) {
            $messages[$msgi]['TO'] = $to; //parseAddress($to);
//        }
        $messages[$msgi]['PRIORITY'] = $priority;
        $messages[$msgi]['CC'] = $cc; //parseAddress($cc);
        $messages[$msgi]['SIZE'] = $size;
        $messages[$msgi]['TYPE0'] = $type[0];
        $messages[$msgi]['FLAG_DELETED'] = $flag_deleted;
        $messages[$msgi]['FLAG_ANSWERED'] = $flag_answered;
        $messages[$msgi]['FLAG_SEEN'] = $flag_seen;
        $messages[$msgi]['FLAG_FLAGGED'] = $flag_flagged;

        /* non server sort stuff */
        if (!$allow_server_sort) {
           $from = parseAddress($from);
           if ($from[0][1]) {
              $from = decodeHeader($from[0][1]);
           } else {
              $from = $from[0][0];
           }
           $messages[$msgi]['FROM-SORT'] = $from;
           $subject_sort = strtolower(decodeHeader($subject));
           if (preg_match("/^(vedr|sv|re|aw):\s*(.*)$/si", $subject_sort, $matches)){
                $messages[$msgi]['SUBJECT-SORT'] = $matches[2];
           } else {
               $messages[$msgi]['SUBJECT-SORT'] = $subject_sort;
           }
        }
        ++$msgi;
    }
    array_reverse($messages);
    $new_messages = array();
    foreach ($messages as $i =>$message) {
        $new_messages[] = $message;
    }
    return $new_messages;
}

// obsolete?
function sqimap_get_headerfield($imap_stream, $field) {
    global $uid_support;
    $sid = sqimap_session_id(false);

    $results = array();
    $read_list = array();

    $query = "FETCH 1:* (UID BODY.PEEK[HEADER.FIELDS ($field)])";
    $readin_list = sqimap_run_command_list ($imap_stream, $query, true, $response, $message, $uid_support);
    $i = 0;

    foreach ($readin_list as $r) {
        $r = implode('',$r);
        /* first we unfold the header */
        $r = str_replace(array("\r\n\t","\r\n\s"),array('',''),$r);
        /* 
         * now we can make a new header array with each element representing 
         * a headerline
         */
        $r = explode("\r\n" , $r);  
        if (!$uid_support) {
            if (!preg_match("/^\\*\s+([0-9]+)\s+FETCH/iAU",$r[0], $regs)) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                      _("ERROR : Could not complete request.") .
                      '</b><br>' .
                      _("Unknown response from IMAP server: ") . ' 1.' .
                      $r[0] . "</font><br>\n";
            } else {
                $id = $regs[1];
            }
        } else {
            if (!preg_match("/^\\*\s+([0-9]+)\s+FETCH.*UID\s+([0-9]+)\s+/iAU",$r[0], $regs)) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                     _("ERROR : Could not complete request.") .
                     '</b><br>' .
                     _("Unknown response from IMAP server: ") . ' 1.' .
                     $r[0] . "</font><br>\n";
            } else {
                $id = $regs[2];
            }
        }
        $field = $r[1];
        $field = substr($field,strlen($field)+2);
        $result[] = array($id,$field);
    }
    return $result;
}




 
/*
 * Returns a message array with all the information about a message.  
 * See the documentation folder for more information about this array.
 */
function sqimap_get_message ($imap_stream, $id, $mailbox) {
    global $uid_support;

    $flags = array();
    $read = sqimap_run_command ($imap_stream, "FETCH $id (FLAGS BODYSTRUCTURE)", true, $response, $message, $uid_support);
    if ($read) {
        if (preg_match('/.+FLAGS\s\((.*)\)\s/AUi',$read[0],$regs)) {
            if (trim($regs[1])) {
                $flags = preg_split('/ /', $regs[1],-1,'PREG_SPLIT_NI_EMPTY');
            }
        }
    } else {
        /* the message was not found, maybe the mailbox was modified? */
        global $sort, $startMessage, $color;

        $errmessage = _("The server couldn't find the message you requested.") .
              '<p>'._("Most probably your message list was out of date and the message has been moved away or deleted (perhaps by another program accessing the same mailbox).");
        /* this will include a link back to the message list */
        error_message($errmessage, $mailbox, $sort, $startMessage, $color);
        exit;
    } 
    $bodystructure = implode('',$read);
    $msg =  mime_structure($bodystructure,$flags);
    $read = sqimap_run_command ($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, $uid_support);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader($read);
    $msg->rfc822_header = $rfc822_header;
    return $msg;
}

/* Wrapper function that reformats the header information. */
// obsolete?
function sqimap_get_message_header ($imap_stream, $id, $mailbox) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

/* Wrapper function that reformats the entity header information. */
// obsolete?
function sqimap_get_ent_header ($imap_stream, $id, $mailbox, $ent) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id BODY[$ent.HEADER]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

/* function to get the mime headers */
// obsolete?
function sqimap_get_mime_ent_header ($imap_stream, $id, $mailbox, $ent) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id:$id BODY[$ent.MIME]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

?>
