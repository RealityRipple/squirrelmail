<?php

/**
 * imap_messages.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements functions that manipulate messages
 *
 * $Id$
 */

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
    $read = sqimap_run_command ($imap_stream, "STORE $msgs_id +FLAGS (\\Deleted)", $handle_errors, $response, $message, $uid_support);
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


/* Returns some general header information -- FROM, DATE, and SUBJECT */
class small_header {
    var $from = '', $subject = '', $date = '', $to = '', 
        $priority = 0, $message_id = 0, $cc = '', $uid = '';
}

function sqimap_get_small_header ($imap_stream, $id, $sent) {
    $res = sqimap_get_small_header_list($imap_stream, array($id), $sent);
    return $res[0];
}

/*
 * Sort the message list and crunch to be as small as possible
 * (overflow could happen, so make it small if possible)
 */
function sqimap_message_list_squisher($messages_array) {
    if( !is_array( $messages_array ) ) {
        return;
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
    $sid = sqimap_session_id($uid_support);
    $results = array();
    $references = "";
    $query = "$sid FETCH $message BODY[HEADER.FIELDS (References)]\r\n";
    fputs ($imap_stream, $query);
    $responses = sqimap_read_data_list($imap_stream, $sid, true, $responses, $message);
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

    $sid = sqimap_session_id($uid_support);
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
            $uid_query = "$sid SEARCH UID 1:$uidnext\r\n";
            fputs($imap_stream, $uid_query);
            $uids = sqimap_read_data($imap_stream, $sid, true ,$response, $message);
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
        $sort_query = "$sid SORT ($sort_on[$sort]) ".strtoupper($default_charset)." ALL\r\n";
        fputs($imap_stream, $sort_query);
        $sort_test = sqimap_read_data($imap_stream, $sid, true ,$response, $message);
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

    $sid = sqimap_session_id($uid_support);
    $php_sort_array = array();

    if ($uid_support) {
        if (isset($mbxresponse['UIDNEXT']) && $mbxresponse['UIDNEXT']) {
    	    $uidnext = $mbxresponse['UIDNEXT']-1;
	} else {
	    $uidnext = '*';
	}
        $uid_query = "$sid SEARCH UID 1:$uidnext\r\n";
        fputs($imap_stream, $uid_query);
        $uids = sqimap_read_data($imap_stream, $sid, true ,$response, $message);
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
    $sid = sqimap_session_id($uid_support);
    $thread_temp = array ();
    if ($sort_by_ref == 1) {
        $sort_type = 'REFERENCES';
    }
    else {
        $sort_type = 'ORDEREDSUBJECT';
    }
    $thread_query = "$sid THREAD $sort_type ".strtoupper($default_charset)." ALL\r\n";
    fputs($imap_stream, $thread_query);
    $thread_test = sqimap_read_data($imap_stream, $sid, false, $response, $message);
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

function sqimap_get_small_header_list ($imap_stream, $msg_list) {
    global $squirrelmail_language, $color, $data_dir, $username, $imap_server_type;
    global $uid_support;

    /* Get the small headers for each message in $msg_list */
    $sid = sqimap_session_id($uid_support);

    $maxmsg = sizeof($msg_list);
    $msgs_str = sqimap_message_list_squisher($msg_list);
    $results = array();
    $read_list = array();
    /*
     * We need to return the data in the same order as the caller supplied
     * in $msg_list, but IMAP servers are free to return responses in
     * whatever order they wish... So we need to re-sort manually
     */
    for ($i = 0; $i < sizeof($msg_list); $i++) {
        $id2index[$msg_list[$i]] = $i;
    }

    $internaldate = getPref($data_dir, $username, 'internal_date_sort');
    if ($internaldate) {
        $query = "$sid FETCH $msgs_str (FLAGS UID RFC822.SIZE INTERNALDATE BODY.PEEK[HEADER.FIELDS (Date To From Cc Subject X-Priority Content-Type)])\r\n";
    } else {
        $query = "$sid FETCH $msgs_str (FLAGS UID RFC822.SIZE BODY.PEEK[HEADER.FIELDS (Date To From Cc Subject X-Priority Content-Type)])\r\n";
    }
    fputs ($imap_stream, $query);
    $readin_list = sqimap_read_data_list($imap_stream, $sid, false, $response, $message);
    $i = 0;
    foreach ($readin_list as $r) {
        if (!$uid_support) {
            if (!preg_match("/^\\*\s+([0-9]+)\s+FETCH/iAU",$r[0], $regs)) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                      _("ERROR : Could not complete request.") .
                      '</b><br>' .
                      _("Unknown response from IMAP server: ") . ' 1.' .
                      $r[0] . "</font><br>\n";
            } else if (! isset($id2index[$regs[1]]) || !count($id2index[$regs[1]])) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                      _("ERROR : Could not complete request.") .
                      '</b><br>' .
                      _("Unknown message number in reply from server: ") .
                      $regs[1] . "</font><br>\n";
            } else {
                $read_list[$id2index[$regs[1]]] = $r;
            }
        } else {
            if (!preg_match("/^\\*\s+([0-9]+)\s+FETCH.*UID\s+([0-9]+)\s+/iAU",$r[0], $regs)) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                     _("ERROR : Could not complete request.") .
                     '</b><br>' .
                     _("Unknown response from IMAP server: ") . ' 1.' .
                     $r[0] . "</font><br>\n";
            } else if (! isset($id2index[$regs[2]]) || !count($id2index[$regs[2]])) {
                set_up_language($squirrelmail_language);
                echo '<br><b><font color=$color[2]>' .
                      _("ERROR : Could not complete request.") .
                      '</b><br>' .
                      _("Unknown message number in reply from server: ") .
                      $regs[2] . "</font><br>\n";
            } else {
                $read_list[$id2index[$regs[2]]] = $r;
                $unique_id = $regs[2];
            }
        }
    }
    arsort($read_list);

    $patterns = array (
                    "/^To:(.*)\$/AUi",
                    "/^From:(.*)\$/AUi",
                    "/^X-Priority:(.*)\$/AUi",
                    "/^Cc:(.*)\$/AUi",
                    "/^Date:(.*)\$/AUi",
                    "/^Subject:(.*)\$/AUi",
                    "/^Content-Type:(.*)\$/AUi"
                );
    $regpattern = '';

    for ($msgi = 0; $msgi < $maxmsg; $msgi++) {
        $subject = _("(no subject)");
        $from = _("Unknown Sender");
        $priority = 0;
        $messageid = "<>";
        $cc = "";
        $to = "";
        $date = "";
        $type[0] = "";
        $type[1] = "";
        $inrepto = "";
        $flag_seen = false;
        $flag_answered = false;
        $flag_deleted = false;
        $flag_flagged = false;
        $read = $read_list[$msgi];
        $prevline = false;

        foreach ($read as $read_part) {
            //unfold multi-line headers
            if ($prevline && strpos($read_part, "\t ") === true) {
                $read_part = substr($prevline, 0, -2) . preg_replace('/(\t\s+)/',' ',$read_part);
            }
            $prevline = $read_part;
            if ($read_part{0} == '*') {
                if ($internaldate) {
                    if (preg_match ("/^.+INTERNALDATE\s+\"(.+)\".+/iUA",$read_part, $reg)) {
                        $tmpdate = trim($reg[1]);
                        $tmpdate = str_replace('  ',' ',$tmpdate);
                        $tmpdate = explode(' ',$tmpdate);
                        $date = str_replace('-',' ',$tmpdate[0]) . " " .
                                $tmpdate[1] . " " .
                                $tmpdate[2];
                    }
                }
                if (preg_match ("/^.+RFC822.SIZE\s+(\d+).+/iA",$read_part, $reg)) {
                    $size = $reg[1];
                }
                if (preg_match("/^.+FLAGS\s+\((.*)\).+/iUA", $read_part, $regs)) {
                    $flags = explode(' ',trim($regs[1]));
                    foreach ($flags as $flag) {
                        $flag = strtolower($flag);
                        if ($flag == '\\seen') {
                            $flag_seen = true;
                        } else if ($flag == '\\answered') {
                            $flag_answered = true;
                        } else if ($flag == '\\deleted') {
                            $flag_deleted = true;
                        } else if ($flag == '\\flagged') {
                            $flag_flagged = true;
                        }
                    }
                }
                if (preg_match ("/^.+UID\s+(\d+).+/iA",$read_part, $reg)) {
                    $unique_id = $reg[1];
                }
            } else {
                $firstchar = strtoupper($read_part{0});
                if ($firstchar == 'T') {
                    $regpattern = $patterns[0];
                    $id = 1;
                } else if ($firstchar == 'F') {
                    $regpattern = $patterns[1];
                    $id = 2;
                } else if ($firstchar == 'X') {
                    $regpattern = $patterns[2];
                    $id = 3;
                } else if ($firstchar == 'C') {
                    if (strtolower($read_part{1}) == 'c') {
                        $regpattern = $patterns[3];
                        $id = 4;
                    } else if (strtolower($read_part{1}) == 'o') {
                        $regpattern = $patterns[6];
                        $id = 7;
                    }
                } else if ($firstchar == 'D' && !$internaldate ) {
                    $regpattern = $patterns[4];
                    $id = 5;
                } else if ($firstchar == 'S') {
                    $regpattern = $patterns[5];
                    $id = 6;
                } else $regpattern = '';

                if ($regpattern) {
                    if (preg_match ($regpattern, $read_part, $regs)) {
                        switch ($id) {
                            case 1:
                                $to = $regs[1];
                                break;
                            case 2:
                                $from = $regs[1];
                                break;
                            case 3:
                                $priority = $regs[1];
                                break;
                            case 4:
                                $cc = $regs[1];
                                break;
                            case 5:
                                $date = $regs[1];
                                break;
                            case 6:
                                $subject = htmlspecialchars(trim($regs[1]));
                                if ($subject == "") {
                                    $subject = _("(no subject)");
                                }
                                break;
                            case 7:
                                $type = strtolower(trim($regs[1]));
                                if ($pos = strpos($type, ";")) {
                                    $type = substr($type, 0, $pos);
                                }
                                $type = explode("/", $type);
                                if (!isset($type[1])) {
                                    $type[1] = '';
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }
            }

        }

        $header = new small_header;

        if ($uid_support) {
            $header->uid = $unique_id;
        } else {
            $header->uid = $msg_list[$msgi];
        }
        $header->date = $date;
        $header->subject = $subject;
        $header->to = $to;
        $header->from = $from;	
        $header->priority = $priority;
        $header->message_id = $messageid;
        $header->cc = $cc;
        $header->size = $size;
        $header->type0 = $type[0];
        $header->type1 = $type[1];
        $header->flag_seen = $flag_seen;
        $header->flag_answered = $flag_answered;
        $header->flag_deleted = $flag_deleted;
        $header->flag_flagged = $flag_flagged;
        $header->inrepto = $inrepto;
        $result[] = $header;
    }
    return $result;
}

function sqimap_get_headerfield($imap_stream, $field) {
    $sid = sqimap_session_id(false);

    $results = array();
    $read_list = array();

    $query = "$sid FETCH 1:* (UID BODY.PEEK[HEADER.FIELDS ($field)])\r\n";
    fputs ($imap_stream, $query);
    $readin_list = sqimap_read_data_list($imap_stream, $sid, false, $response, $message);
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
      echo "ERROR Yeah I know, not a very usefull errormessage (id = $id, mailbox = $mailbox sqimap_get_message)";
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
function sqimap_get_message_header ($imap_stream, $id, $mailbox) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

/* Wrapper function that reformats the entity header information. */
function sqimap_get_ent_header ($imap_stream, $id, $mailbox, $ent) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id BODY[$ent.HEADER]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}


/* Wrapper function that returns entity headers for use by decodeMime */
/*
function sqimap_get_entity_header ($imap_stream, &$read, &$type0, &$type1, &$bound, &$encoding, &$charset, &$filename) {
    $header = sqimap_get_header($imap_stream, $read);
    $type0 = $header["TYPE0"]; 
    $type1 = $header["TYPE1"];
    $bound = $header["BOUNDARY"];
    $encoding = $header["ENCODING"];
    $charset = $header["CHARSET"];
    $filename = $header["FILENAME"];
}

/* function to get the mime headers */
function sqimap_get_mime_ent_header ($imap_stream, $id, $mailbox, $ent) {
    global $uid_support;
    $read = sqimap_run_command ($imap_stream, "FETCH $id:$id BODY[$ent.MIME]", true, $response, $message, $uid_support);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

/* Returns the body of a message. */
function sqimap_get_message_body ($imap_stream, &$header) {
//    return decodeMime($imap_stream, $header->id);
}

?>
