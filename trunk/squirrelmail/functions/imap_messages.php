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
function sqimap_messages_copy ($imap_stream, $start, $end, $mailbox) {
    $read = sqimap_run_command ($imap_stream, "COPY $start:$end \"$mailbox\"", true, $response, $message);
}

/* Deletes specified messages and moves them to trash if possible */
function sqimap_messages_delete ($imap_stream, $start, $end, $mailbox) {
    global $move_to_trash, $trash_folder, $auto_expunge;

    if (($move_to_trash == true) && (sqimap_mailbox_exists($imap_stream, $trash_folder) && ($mailbox != $trash_folder))) {
        sqimap_messages_copy ($imap_stream, $start, $end, $trash_folder);
    }
    sqimap_messages_flag ($imap_stream, $start, $end, "Deleted");
}

/* Sets the specified messages with specified flag */
function sqimap_messages_flag ($imap_stream, $start, $end, $flag) {
    $read = sqimap_run_command ($imap_stream, "STORE $start:$end +FLAGS (\\$flag)", true, $response, $message);
}

/* Remove specified flag from specified messages */
function sqimap_messages_remove_flag ($imap_stream, $start, $end, $flag) {
    $read = sqimap_run_command ($imap_stream, "STORE $start:$end -FLAGS (\\$flag)", true, $response, $message);
}

/* Returns some general header information -- FROM, DATE, and SUBJECT */
class small_header {
    var $from = '', $subject = '', $date = '', $to = '', 
        $priority = 0, $message_id = 0, $cc = '';
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
    $responses = array ();
    $sid = sqimap_session_id();
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
 
function sqimap_get_sort_order ($imap_stream, $sort) {
    global  $default_charset, $thread_sort_messages,
            $internal_date_sort, $server_sort_array,
            $sent_folder, $mailbox;
            
    if (session_is_registered('server_sort_array')) {
        session_unregister('server_sort_array');
    }
    if ($sort == 6) {
        $qty = sqimap_get_num_messages ($imap_stream, $mailbox);
        $server_sort_array = range(1, $qty);
        session_register('server_sort_array');
        return $server_sort_array;
    }
    $sid = sqimap_session_id();
    $sort_on = array();
    $reverse = 0;
    $server_sort_array = array();
    $sort_test = array();
    $sort_query = '';
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
        $sort_test = sqimap_read_data($imap_stream, $sid, false, $response, $message);
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
    session_register('server_sort_array');
    return $server_sort_array;
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
    global $thread_new, $sort_by_ref, $default_charset, $server_sort_array;
    if (session_is_registered('thread_new')) {
        session_unregister('thread_new');
    }
    if (session_is_registered('server_sort_array')) {
        session_unregister('server_srot_array');
    }
    $sid = sqimap_session_id();
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
    session_register('thread_new');
    $thread_new = array_reverse($thread_new);
    $thread_list = implode(" ", $thread_new);
    $thread_list = str_replace("(", " ", $thread_list);
    $thread_list = str_replace(")", " ", $thread_list);
    $thread_list = preg_split("/\s/", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
    $server_sort_array = $thread_list;
    session_register('server_sort_array');
    return $thread_list;
}

function elapsedTime($start) {
 $stop = gettimeofday();
 $timepassed =  1000000 * ($stop['sec'] - $start['sec']) + $stop['usec'] - $start['usec'];
 return $timepassed;
}


function sqimap_get_small_header_list ($imap_stream, $msg_list, $issent) {
    global $squirrelmail_language, $color, $data_dir, $username;
    $start = gettimeofday();
    /* Get the small headers for each message in $msg_list */
    $sid = sqimap_session_id();
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
	$query = "$sid FETCH $msgs_str (FLAGS RFC822.SIZE INTERNALDATE BODY.PEEK[HEADER.FIELDS (Date To From Cc Subject X-Priority Content-Type)])\r\n";
    } else {
	$query = "$sid FETCH $msgs_str (FLAGS RFC822.SIZE BODY.PEEK[HEADER.FIELDS (Date To From Cc Subject X-Priority Content-Type)])\r\n";
    }
    fputs ($imap_stream, $query);
    $readin_list = sqimap_read_data_list($imap_stream, $sid, true, $response, $message);

    foreach ($readin_list as $r) {
        if (!preg_match("/^\\*\s+([0-9]+)\s+FETCH/iAU",$r[0], $regs)) {
            set_up_language($squirrelmail_language);
            echo '<br><b><font color=$color[2]>' .
                  _("ERROR : Could not complete request.") .
                  '</b><br>' .
                  _("Unknown response from IMAP server: ") . ' 1.' .
                  $r[0] . "</font><br>\n";
         
	} else 	if (! isset($id2index[$regs[1]]) || !count($id2index[$regs[1]])) {
             set_up_language($squirrelmail_language);
             echo '<br><b><font color=$color[2]>' .
                  _("ERROR : Could not complete request.") .
                  '</b><br>' .
                  _("Unknown message number in reply from server: ") .
                  $regs[1] . "</font><br>\n";
        } else {
    	    $read_list[$id2index[$regs[1]]] = $r;
        }
    }
    arsort($read_list);

    $patterns = array (
			"/^To:(.*)\$/AU",
			"/^From:(.*)\$/AU",
			"/^X-Priority:(.*)\$/AU",
			"/^Cc:(.*)\$/AU",
			"/^Date:(.*)\$/AU",
			"/^Subject:(.*)\$/AU",
			"/^Content-Type:(.*)\$/AU"
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
            while ($prevline && strspn($read_part, "\t ") > 0) {
               $read_part = substr($prevline, 0, -2) . ' ' . ltrim($read_part);
            }
	    $prev_line = $read_part;
	    
	    if ($read_part{0} == '*') {
	        if ($internaldate) {
		    if (preg_match ("/^.+INTERNALDATE\s+\"(.+)\".+/iUA",$read_part, $reg)) {
                       if ($imap_server_type == 'courier') {
                            /** If we use courier, 
                              *  We need to reformat the INTERNALDATE-string 
                              **/
                            $tmpdate = trim($reg[1]);
                            $tmpdate = str_replace('  ',' ',$tmpdate);
                            $tmpdate = explode(' ',$tmpdate);
                            $date = str_replace('-',' ',$tmpdate[0]) . " " .
                                    $tmpdate[1] . " " .
                                    $tmpdate[2];
                        } 
                        else {
                            $date = $reg[1];
                        }
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
	    } else {
		$firstchar = $read_part{0};	    	
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
        if ($issent) {
            $header->from = (trim($to) != '' ? $to : '(' ._("No To Address") . ')');
        } else {
            $header->from = $from;
        }

        $header->date = $date;
        $header->subject = $subject;
        $header->to = $to;
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
//    echo 'processtime (us): ' . elapsedtime($start) .'<BR>';
    return $result;
}




/* Returns the flags for the specified messages */
function sqimap_get_flags ($imap_stream, $i) {
    $read = sqimap_run_command ($imap_stream, "FETCH $i:$i FLAGS", true, $response, $message);
    if (ereg('FLAGS(.*)', $read[0], $regs)) {
        return explode(' ', trim(ereg_replace('[\\(\\)\\\\]', '', $regs[1])));
    }
    return array('None');
}

function sqimap_get_flags_list ($imap_stream, $msg_list) {
    $msgs_str = sqimap_message_list_squisher($msg_list);
    for ($i = 0; $i < sizeof($msg_list); $i++) {
        $id2index[$msg_list[$i]] = $i;
    }
    $result_list = sqimap_run_command_list ($imap_stream, "FETCH $msgs_str FLAGS", true, $response, $message);
    $result_flags = array();

    for ($i = 0; $i < sizeof($result_list); $i++) {
        if (eregi('^\* ([0-9]+).*FETCH.*FLAGS(.*)', $result_list[$i][0], $regs)
          && isset($id2index[$regs[1]]) && count($id2index[$regs[1]])) {
            $result_flags[$id2index[$regs[1]]] = explode(" ", trim(ereg_replace('[\\(\\)\\\\]', '', $regs[2])));
        } else {
            set_up_language($squirrelmail_language);
            echo "<br><b><font color=$color[2]>\n" .
                 _("ERROR : Could not complete request.") .
                 "</b><br>\n" .
                 _("Unknown response from IMAP server: ") .
                 $result_list[$i][0] . "</font><br>\n";
            exit;
        }
    }
    arsort($result_flags);
    return $result_flags;
}

/*
 * Returns a message array with all the information about a message.  
 * See the documentation folder for more information about this array.
 */
function sqimap_get_message ($imap_stream, $id, $mailbox) {
    $header = sqimap_get_message_header($imap_stream, $id, $mailbox);
    return sqimap_get_message_body($imap_stream, $header);
}

/* Wrapper function that reformats the header information. */
function sqimap_get_message_header ($imap_stream, $id, $mailbox) {
    $read = sqimap_run_command ($imap_stream, "FETCH $id:$id BODY[HEADER]", true, $response, $message);
    $header = sqimap_get_header($imap_stream, $read); 
    $header->id = $id;
    $header->mailbox = $mailbox;
    return $header;
}

/* Wrapper function that reformats the entity header information. */
function sqimap_get_ent_header ($imap_stream, $id, $mailbox, $ent) {
    $read = sqimap_run_command ($imap_stream, "FETCH $id:$id BODY[$ent.HEADER]", true, $response, $message);
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
*/
/* Queries the IMAP server and gets all header information. */
function sqimap_get_header ($imap_stream, $read) {
    global $where, $what;

    $hdr = new msg_header();
    $i = 0;

    /* Set up some defaults */
    $hdr->type0 = "text";
    $hdr->type1 = "plain";
    $hdr->charset = "us-ascii";

    $read_fold = array();

    while ($i < count($read)) {
        /* unfold multi-line headers */
	/* remember line for to, cc and bcc */
    	$read_fold[] = $read[$i];
	$folded = false;
    	while (($i + 1 < count($read)) && (strspn($read[$i + 1], "\t ") > 0) ) {
    	    if ($read[$i+1] != '') $read_fold[] = $read[$i+1];
    	    $read[$i + 1] = substr($read[$i], 0, -2) . ' ' . ltrim($read[$i+1]);
	    array_splice($read, $i, 1);
	    $folded = true;
    	}
	if (!$folded) {
	    $read_fold = array();
	}

        if (substr($read[$i], 0, 17) == "MIME-Version: 1.0") {
            $hdr->mime = true;
            $i++;
        }
        /* ENCODING TYPE */
        else if (substr(strtolower($read[$i]), 0, 26) == "content-transfer-encoding:") {
            $hdr->encoding = strtolower(trim(substr($read[$i], 26)));
            $i++;
        }
        /* CONTENT-TYPE */
        else if (strtolower(substr($read[$i], 0, 13)) == "content-type:") {
            $cont = strtolower(trim(substr($read[$i], 13)));
            if (strpos($cont, ";")) {
                $cont = substr($cont, 0, strpos($cont, ";"));
            }

            if (strpos($cont, "/")) {
                $hdr->type0 = substr($cont, 0, strpos($cont, "/"));
                $hdr->type1 = substr($cont, strpos($cont, "/")+1);
            } else {
                $hdr->type0 = $cont;
            }

            $line = $read[$i];
            $i++;
            while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
                str_replace("\n", "", $line);
                str_replace("\n", "", $read[$i]);
                $line = "$line $read[$i]";
                $i++;
            }

            /* Detect the boundary of a multipart message */
            if (eregi('boundary="([^"]+)"', $line, $regs)) {
                $hdr->boundary = $regs[1];
            }

            /* Detect the charset */
            if (strpos(strtolower(trim($line)), "charset=")) {
                $pos = strpos($line, "charset=") + 8;
                $charset = trim($line);
                if (strpos($line, ";", $pos) > 0) {
                    $charset = substr($charset, $pos, strpos($line, ";", $pos)-$pos);
                } else {
                    $charset = substr($charset, $pos);
                }
                $charset = str_replace("\"", "", $charset);
                $hdr->charset = $charset;
            } else {
                $hdr->charset = "us-ascii";
            }
	    /* Detect type in case of multipart/related */
	    if (strpos(strtolower(trim($line)), "type=")) {
		$pos = strpos($line, "type=") + 6;
		$type = trim($line);
                if (strpos($line, ";", $pos) > 0) {
                    $type = substr($type, $pos, strpos($line, ";", $pos)-$pos);
                } else {
                    $type = substr($type, $pos);
                }
		$hdr->type = $type;
	    }
        }
        else if (strtolower(substr($read[$i], 0, 20)) == "content-disposition:") {
            /* Add better content-disposition support */
            $line = $read[$i];
            $i++;
            while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
                str_replace("\n", "", $line);
                str_replace("\n", "", $read[$i]);
                $line = "$line $read[$i]";
                $i++;
            }

            /* Detects filename if any */
            if (strpos(strtolower(trim($line)), "filename=")) {
                $pos = strpos($line, "filename=") + 9;
                $name = trim($line);
                if (strpos($line, " ", $pos) > 0) {
                    $name = substr($name, $pos, strpos($line, " ", $pos));
                } else {
                    $name = substr($name, $pos);
                }
                $name = str_replace("\"", "", $name);
                $hdr->filename = $name;
            }
        }
        /* REPLY-TO */
        else if (strtolower(substr($read[$i], 0, 9)) == "reply-to:") {
            $hdr->replyto = trim(substr($read[$i], 9, strlen($read[$i])));
            $i++;
        }
        /* FROM */
        else if (strtolower(substr($read[$i], 0, 5)) == "from:") {
            $hdr->from = trim(substr($read[$i], 5, strlen($read[$i]) - 6));
            if (! isset($hdr->replyto) || $hdr->replyto == "") {
                $hdr->replyto = $hdr->from;
            }
            $i++;
        }
        /* DATE */
        else if (strtolower(substr($read[$i], 0, 5)) == "date:") {
            $d = substr($read[$i], 5);
            $d = trim($d);
            $d = strtr($d, array('  ' => ' '));
            $d = explode(' ', $d);
            $hdr->date = getTimeStamp($d);
            $i++;
        }
        /* SUBJECT */
        else if (strtolower(substr($read[$i], 0, 8)) == "subject:") {
            $hdr->subject = trim(substr($read[$i], 8, strlen($read[$i]) - 9));
            if (strlen(Chop($hdr->subject)) == 0) {
               $hdr->subject = _("(no subject)");
            }
            /*
            if ($where == 'SUBJECT') {
                 $hdr->subject = $what;
                 // $hdr->subject = eregi_replace($what, "<b>\\0</b>", $hdr->subject);
            }
            */
            $i++;
        }
        /* CC */
        else if (strtolower(substr($read[$i], 0, 3)) == "cc:") {
            $pos = 0;
	    if (isset($read_fold[0])) {
        	$hdr->cc[$pos] = trim(substr($read_fold[0], 4));
		$pos++;
        	while ($pos < count($read_fold)) {		    
            	    $hdr->cc[$pos] = trim($read_fold[$pos]);
            	    $pos++;
		}
            } else {
        	$hdr->cc[$pos] = trim(substr($read[$i], 4));
            }
	    $i++;
        }
        /* BCC */
        else if (strtolower(substr($read[$i], 0, 4)) == "bcc:") {
            $pos = 0;
	    if (isset($read_fold[0])) {
        	$hdr->bcc[$pos] = trim(substr($read_fold[0], 5));
		$pos++;
        	while ($pos < count($read_fold)) {		    
            	    $hdr->bcc[$pos] = trim($read_fold[$pos]);
            	    $pos++;
		}
            } else {
        	$hdr->bcc[$pos] = trim(substr($read[$i], 5));
            }
	    $i++;
        }
        /* TO */
        else if (strtolower(substr($read[$i], 0, 3)) == "to:") {
            $pos = 0;
	    if (isset($read_fold[0])) {
        	$hdr->to[$pos] = trim(substr($read_fold[0], 4));
		$pos++;
        	while ($pos < count($read_fold)) {		    
            	    $hdr->to[$pos] = trim($read_fold[$pos]);
            	    $pos++;
		}
            } else {
        	$hdr->to[$pos] = trim(substr($read[$i], 4));
            }
	    $i++;
	    
        }
        /* MESSAGE ID */
        else if (strtolower(substr($read[$i], 0, 11)) == "message-id:") {
            $hdr->message_id = trim(substr($read[$i], 11));
            $i++;
        }
        /* ERROR CORRECTION */
        else if (substr($read[$i], 0, 1) == ")") {
            if (strlen(trim($hdr->subject)) == 0) {
                $hdr->subject = _("(no subject)");
            }
            if (strlen(trim($hdr->from)) == 0) {
                $hdr->from = _("(unknown sender)");
            }
            if (strlen(trim($hdr->date)) == 0) {
                $hdr->date = time();
            }
            $i++;
        }
        /* X-PRIORITY */
        else if (strtolower(substr($read[$i], 0, 11)) == "x-priority:") {
            $hdr->priority = trim(substr($read[$i], 11));
            $i++;
        }
        else {
            $i++;
        }
	$read_fold=array();
     }
     return $hdr;
}

/* Returns the body of a message. */
function sqimap_get_message_body ($imap_stream, &$header) {
    $id = $header->id;
    return decodeMime($imap_stream, $header);
}

?>
