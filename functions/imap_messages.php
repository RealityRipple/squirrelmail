<?php

/**
 * imap_messages.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements functions that manipulate messages
 * NOTE: Quite a few functions in this file are obsolete
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */


/**
 * Copy a set of messages ($id) to another mailbox ($mailbox)
 * @param int $imap_stream The resource ID for the IMAP socket
 * @param string $id The list of messages to copy
 * @param string $mailbox The destination to copy to
 * @return bool
 */
function sqimap_msgs_list_copy($imap_stream, $id, $mailbox) {
    $msgs_id = sqimap_message_list_squisher($id);
    $read = sqimap_run_command ($imap_stream, "COPY $msgs_id " . sqimap_encode_mailbox_name($mailbox), true, $response, $message, TRUE);
    if ($response == 'OK') {
        return true;
    } else {
        return false;
    }
}


/**
 * Move a set of messages ($id) to another mailbox. Deletes the originals.
 * @param int $imap_stream The resource ID for the IMAP socket
 * @param string $id The list of messages to move
 * @param string $mailbox The destination to move to
 * @return void
 */
function sqimap_msgs_list_move($imap_stream, $id, $mailbox) {
    $msgs_id = sqimap_message_list_squisher($id);
    if (sqimap_msgs_list_copy ($imap_stream, $id, $mailbox)) {
        return sqimap_toggle_flag($imap_stream, $id, '\\Deleted', true, true);
    } else {
        return false;
    }
}


/**
 * Deletes a message and move it to trash or expunge the mailbox
 * @param  resource imap connection
 * @param  string $mailbox mailbox, used for checking if it concerns the trash_folder
 * @param  array $id list with uid's
 * @param  bool   $bypass_trash skip copy to trash
 * @return array  $aMessageList array with messages containing the new flags and UID @see parseFetch
 */
function sqimap_msgs_list_delete($imap_stream, $mailbox, $id, $bypass_trash=false) {
    // FIX ME, remove globals by introducing an associative array with properties
    // as 4th argument as replacement for the bypass_trash var
    global $move_to_trash, $trash_folder;
    $bRes = true;
    if (($move_to_trash == true) && ($bypass_trash != true) &&
        (sqimap_mailbox_exists($imap_stream, $trash_folder) &&  ($mailbox != $trash_folder)) ) {
        $bRes = sqimap_msgs_list_copy ($imap_stream, $id, $trash_folder);
    }
    if ($bRes) {
        return sqimap_toggle_flag($imap_stream, $id, '\\Deleted', true, true);
    } else {
        return false;
    }
}


/**
 * Set a flag on the provided uid list
 * @param  resource imap connection
 * @param  array $id list with uid's
 * @param  string $flag Flags to set/unset flags can be i.e.'\Seen', '\Answered', '\Seen \Answered'
 * @param  bool   $set  add (true) or remove (false) the provided flag
 * @param  bool   $handle_errors Show error messages in case of a NO, BAD or BYE response
 * @return array  $aMessageList array with messages containing the new flags and UID @see parseFetch
 */
function sqimap_toggle_flag($imap_stream, $id, $flag, $set, $handle_errors) {
    $msgs_id = sqimap_message_list_squisher($id);
    $set_string = ($set ? '+' : '-');
    $aResponse = sqimap_run_command_list($imap_stream, "STORE $msgs_id ".$set_string."FLAGS ($flag)", $handle_errors, $response, $message, TRUE);
    // parse the fetch response
    return parseFetch($aResponse);
}


/**
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


/**
 * Retrieves an array with a sorted uid list. Sorting is done on the imap server
 * @link http://www.ietf.org/internet-drafts/draft-ietf-imapext-sort-17.txt
 * @param resource $imap_stream IMAP socket connection
 * @param string $sSortField Field to sort on
 * @param bool $reverse Reverse order search
 * @return array $id sorted uid list
 */
function sqimap_get_sort_order($imap_stream, $sSortField, $reverse, $search='ALL') {
    global  $default_charset;

    if ($sSortField) {
        if ($reverse) {
            $sSortField = 'REVERSE '.$sSortField;
        }
        $query = "SORT ($sSortField) ".strtoupper($default_charset)." $search";
        // FIX ME sqimap_run_command should return the parsed data accessible by $aDATA['SORT']
        $aData = sqimap_run_command ($imap_stream, $query, false, $response, $message, TRUE);
        /* fallback to default charset */
        if ($response == 'NO' && strpos($message,'[BADCHARSET]') !== false) {
            $query = "SORT ($sSortField) US-ASCII $search";
            $aData = sqimap_run_command ($imap_stream, $query, true, $response, $message, TRUE);
        }
    }

    if ($response == 'OK') {
        return parseUidList($aData,'SORT');
    } else {
        return false;
    }
}


/**
 * Parses a UID list returned on a SORT or SEARCH request
 * @param array $aData imap response
 * @param string $sCommand issued imap command (SEARCH or SORT)
 * @return array $aUid uid list
 */
function parseUidList($aData,$sCommand) {
    $aUid = array();
    if (isset($aData) && count($aData)) {
        for ($i=0,$iCnt=count($aData);$i<$iCnt;++$i) {
            if (preg_match("/^\* $sCommand (.+)$/", $aData[$i], $aMatch)) {
                $aUid += preg_split("/ /", trim($aMatch[1]));
            }
        }
    }
    return array_unique($aUid);
}

/**
 * Retrieves an array with a sorted uid list. Sorting is done by SquirrelMail
 *
 * @param resource $imap_stream IMAP socket connection
 * @param string $sSortField Field to sort on
 * @param bool $reverse Reverse order search
 * @param array $aUid limit the search to the provided array with uid's default sqimap_get_small_headers uses 1:*
 * @return array $aUid sorted uid list
 */
function get_squirrel_sort($imap_stream, $sSortField, $reverse = false, $aUid = NULL) {
    if ($sSortField != 'RFC822.SIZE' && $sSortField != 'INTERNALDATE') {
        $msgs = sqimap_get_small_header_list($imap_stream, $aUid,
                                      array($sSortField), array());
    } else {
        $msgs = sqimap_get_small_header_list($imap_stream, $aUid,
                                      array(), array($sSortField));
    }
    $aUid = array();
    $walk = false;
    switch ($sSortField) {
      // natcasesort section
      case 'FROM':
      case 'TO':
      case 'CC':
        if(!$walk) {
            array_walk($msgs, create_function('&$v,&$k,$f',
                '$v[$f] = (isset($v[$f])) ? $v[$f] : "";
                 $addr = reset(parseRFC822Address($v[$f],1));
                 $sPersonal = (isset($addr[SQM_ADDR_PERSONAL]) && $addr[SQM_ADDR_PERSONAL]) ?
                   $addr[SQM_ADDR_PERSONAL] : "";
                 $sEmail = ($addr[SQM_ADDR_HOST]) ?
                      $addr[SQM_ADDR_HOST] . "@".$addr[SQM_ADDR_HOST] :
                      $addr[SQM_ADDR_HOST];
                 $v[$f] = ($sPersonal) ? decodeHeader($sPersonal):$sEmail;'),$sSortField);
            $walk = true;
        }
        // nobreak
      case 'SUBJECT':
        if(!$walk) {
            array_walk($msgs, create_function('&$v,&$k,$f',
                '$v[$f] = (isset($v[$f])) ? $v[$f] : "";
                 $v[$f] = strtolower(decodeHeader(trim($v[$f])));
                 $v[$f] = (preg_match("/^(vedr|sv|re|aw|\[\w\]):\s*(.*)$/si", $v[$f], $matches)) ?
                                    $matches[2] : $v[$f];'),$sSortField);
            $walk = true;
        }
        foreach ($msgs as $item) {
            $aUid[$item['UID']] = $item[$sSortField];
        }
        natcasesort($aUid);
        $aUid = array_keys($aUid);
        if ($reverse) {
             $aUid = array_reverse($aUid);
        }
        break;
        //  \natcasesort section
      // sort_numeric section
      case 'DATE':
      case 'INTERNALDATE':
        if(!$walk) {
            array_walk($msgs, create_function('&$v,$k,$f',
                '$v[$f] = (isset($v[$f])) ? $v[$f] : "";
                 $v[$f] = getTimeStamp(explode(" ",$v[$f]));'),$sSortField);
            $walk = true;
        }
        // nobreak;
      case 'RFC822.SIZE':
        if(!$walk) {
            // redefine $sSortField to maintain the same namespace between
            // server-side sorting and SquirrelMail sorting
            $sSortField = 'SIZE';
        }
        foreach ($msgs as $item) {
            $aUid[$item['UID']] = (isset($item[$sSortField])) ? $item[$sSortField] : 0;
        }
        if ($reverse) {
            arsort($aUid,SORT_NUMERIC);
        } else {
            asort($aUid, SORT_NUMERIC);
        }
        $aUid = array_keys($aUid);
        break;
        // \sort_numeric section
      case 'UID':
        $aUid = array_reverse($msgs);
        break;
    }
    return $aUid;
}


/**
 * Returns an indent array for printMessageinfo()
 * This represents the amount of indent needed (value),
 * for this message number (key)
 */

/*
 * Notes for future work:
 * indent_array should contain: indent_level, parent and flags,
 * sibling notes ..
 * To achieve that we  need to define the following flags:
 * 0: hasnochildren
 * 1: haschildren
 * 2: is first
 * 4: is last
 * a node has sibling nodes if it's not the last node
 * a node has no sibling nodes if it's the last node
 * By using binary comparations we can store the flag in one var
 *
 * example:
 * -1      par = 0, level = 0, flag = 1 + 2 + 4 = 7 (haschildren,   isfirst, islast)
 *  \-2    par = 1, level = 1, flag = 0 + 2     = 2 (hasnochildren, isfirst)
 *  |-3    par = 1, level = 1, flag = 1 + 4     = 5 (haschildren,   islast)
 *   \-4   par = 3, level = 2, flag = 1 + 2 + 4 = 7 (haschildren,   isfirst, islast)
 *     \-5 par = 4, level = 3, flag = 0 + 2 + 4 = 6 (hasnochildren, isfirst, islast)
 */
function get_parent_level($thread_new) {
    $parent = '';
    $child  = '';
    $cutoff = 0;

    /*
     * loop through the threads and take unwanted characters out
     * of the thread string then chop it up
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

        /*
         * now the children, checking each thread portion for
         * ),(, and space, adjusting the level and space values
         * to get the indent level
         */
        $level = 0;
        $spaces = array();
        $spaces_total = 0;
        $indent = 0;
        $fake = FALSE;
        for ($k=1,$iCnt=count($thread_new[$i])-1;$k<$iCnt;++$k) {
            $chars = count_chars($thread_new[$i][$k], 1);
            if (isset($chars['40'])) {       /* testing for ( */
                $level += $chars['40'];
            }
            if (isset($chars['41'])) {      /* testing for ) */
                $level -= $chars['41'];
                $spaces[$level] = 0;
                /* if we were faking lets stop, this portion
                 * of the thread is over
                 */
                if ($level == $cutoff) {
                    $fake = FALSE;
                }
            }
            if (isset($chars['32'])) {      /* testing for space */
                if (!isset($spaces[$level])) {
                    $spaces[$level] = 0;
                }
                $spaces[$level] += $chars['32'];
            }
            for ($x=0;$x<=$level;$x++) {
                if (isset($spaces[$x])) {
                    $spaces_total += $spaces[$x];
                }
            }
            $indent = $level + $spaces_total;
            /* must have run into a message that broke the thread
             * so we are adjusting for that portion
             */
            if ($fake == TRUE) {
                $indent = $indent +1;
            }
            if (preg_match("/(\d+)/", $thread_new[$i][$k], $regs)) {
                $child = $regs[1];
            }
            /* the thread must be broken if $indent == 0
             * so indent the message once and start faking it
             */
            if ($indent == 0) {
                $indent = 1;
                $fake = TRUE;
                $cutoff = $level;
            }
            /* dont need abs but if indent was negative
             * errors would occur
             */
            $indent_array[$child] = ($indent < 0) ? 0 : $indent;
            $spaces_total = 0;
        }
    }
    return $indent_array;
}


/**
 * Returns an array with each element as a string representing one
 * message-thread as returned by the IMAP server.
 * @link http://www.ietf.org/internet-drafts/draft-ietf-imapext-sort-13.txt
 */
function get_thread_sort($imap_stream, $search='ALL') {
    global $thread_new, $sort_by_ref, $default_charset, $server_sort_array, $indent_array;

    $thread_temp = array ();
    if ($sort_by_ref == 1) {
        $sort_type = 'REFERENCES';
    } else {
        $sort_type = 'ORDEREDSUBJECT';
    }
    $query = "THREAD $sort_type ".strtoupper($default_charset)." $search";

    $thread_test = sqimap_run_command ($imap_stream, $query, false, $response, $message, TRUE);
    /* fallback to default charset */
    if ($response == 'NO' && strpos($message,'[BADCHARSET]') !== false) {
        $query = "THREAD $sort_type US-ASCII $search";
        $thread_test = sqimap_run_command ($imap_stream, $query, true, $response, $message, TRUE);
    }
    if (isset($thread_test[0])) {
        for ($i=0,$iCnt=count($thread_test);$i<$iCnt;++$i) {
            if (preg_match("/^\* THREAD (.+)$/", $thread_test[$i], $regs)) {
                $thread_list = trim($regs[1]);
                break;
            }
        }
    } else {
        $thread_list = "";
    }
    if (!preg_match("/OK/", $response)) {
        $server_sort_array = 'no';
        return $server_sort_array;
    }
    if (isset($thread_list)) {
        $thread_temp = preg_split("//", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
    }

    $counter = 0;
    $thread_new = array();
    $k = 0;
    $thread_new[0] = "";
    /*
     * parse the thread response into separate threads
     *
     * example:
     *         [0] => (540)
     *         [1] => (1386)
     *         [2] => (1599 759 959 37)
     *         [3] => (492 1787)
     *         [4] => ((933)(1891))
     *         [5] => (1030 (1497)(845)(1637))
     */
    for ($i=0,$iCnt=count($thread_temp);$i<$iCnt;$i++) {
        if ($thread_temp[$i] != ')' && $thread_temp[$i] != '(') {
            $thread_new[$k] = $thread_new[$k] . $thread_temp[$i];
        } elseif ($thread_temp[$i] == '(') {
            $thread_new[$k] .= $thread_temp[$i];
            $counter++;
        } elseif ($thread_temp[$i] == ')') {
            if ($counter > 1) {
                $thread_new[$k] .= $thread_temp[$i];
                $counter = $counter - 1;
            } else {
                $thread_new[$k] .= $thread_temp[$i];
                $k++;
                $thread_new[$k] = "";
                $counter = $counter - 1;
            }
        }
    }

    $thread_new = array_reverse($thread_new);
    /* place the threads after each other in one string */
    $thread_list = implode(" ", $thread_new);
    $thread_list = str_replace("(", " ", $thread_list);
    $thread_list = str_replace(")", " ", $thread_list);
    $thread_list = preg_split("/\s/", $thread_list, -1, PREG_SPLIT_NO_EMPTY);
    $server_sort_array = $thread_list;

    $indent_array = get_parent_level ($thread_new);
    return array($thread_list,$indent_array);
}


function elapsedTime($start) {
    $stop = gettimeofday();
    $timepassed =  1000000 * ($stop['sec'] - $start['sec']) + $stop['usec'] - $start['usec'];
    return $timepassed;
}


function parsePriority($value) {
    $value = strtolower(array_shift(split('/\w/',trim($value))));
    if ( is_numeric($value) ) {
        return $value;
    }
    if ( $value == 'urgent' || $value == 'high' ) {
        return 1;
    } elseif ( $value == 'non-urgent' || $value == 'low' ) {
        return 5;
    }
    return 3;
}

/**
 * Parses a string in an imap response. String starts with " or { which means it
 * can handle double quoted strings and literal strings
 *
 * @param string $read imap response
 * @param integer $i (reference) offset in string
 * @return string $s parsed string without the double quotes or literal count
 */
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


/**
 * Parses a string containing an array from an imap response. String starts with ( and end with )
 *
 * @param string $read imap response
 * @param integer $i (reference) offset in string
 * @return array $a
 */
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


/**
 * Retrieves a list with headers, flags, size or internaldate from the imap server
 * @param resource $imap_stream imap connection
 * @param array    $msg_list array with id's to create a msgs set from
 * @param array    $aHeaderFields requested header fields
 * @param array    $aFetchItems   requested other fetch items like FLAGS, RFC822.SIZE
 * @return array   $aMessages associative array with messages. Key is the UID, value is an associative array
 */
function sqimap_get_small_header_list($imap_stream, $msg_list,
    $aHeaderFields = array('Date', 'To', 'Cc', 'From', 'Subject', 'X-Priority', 'Importance', 'Priority', 'Content-Type'),
    $aFetchItems = array('FLAGS', 'RFC822.SIZE', 'INTERNALDATE')) {

    $aMessageList = array();

    $bUidFetch = ! in_array('UID', $aFetchItems, true);

    /* Get the small headers for each message in $msg_list */
    if ($msg_list !== NULL) {
        $msgs_str = sqimap_message_list_squisher($msg_list);
        /*
        * We need to return the data in the same order as the caller supplied
        * in $msg_list, but IMAP servers are free to return responses in
        * whatever order they wish... So we need to re-sort manually
        */
        if ($bUidFetch) {
            for ($i = 0; $i < sizeof($msg_list); $i++) {
                $aMessageList["$msg_list[$i]"] = array();
            }
        }
    } else {
        $msgs_str = '1:*';
    }

    /*
     * Create the query
     */

    $sFetchItems = '';
    $query = "FETCH $msgs_str (";
    if (count($aFetchItems)) {
        $sFetchItems = implode(' ',$aFetchItems);
    }
    if (count($aHeaderFields)) {
        $sHeaderFields = implode(' ',$aHeaderFields);
        $sFetchItems .= ' BODY.PEEK[HEADER.FIELDS ('.$sHeaderFields.')]';
    }
    $query .= trim($sFetchItems) . ')';
    $aResponse = sqimap_run_command_list ($imap_stream, $query, true, $response, $message, $bUidFetch);
    $aMessages = parseFetch($aResponse,$aMessageList);
    array_reverse($aMessages);
    return $aMessages;
}


/**
 * Parses a fetch response, currently it can hande FLAGS, HEADERS, RFC822.SIZE, INTERNALDATE and UID
 * @param array    $aResponse Imap response
 * @param array    $aMessageList Placeholder array for results. The keys of the
 *                 placeholder array should be the UID so we can reconstruct the order.
 * @return array   $aMessageList associative array with messages. Key is the UID, value is an associative array
 * @author Marc Groot Koerkamp
 */
function parseFetch($aResponse,$aMessageList = array()) {
    foreach ($aResponse as $r) {
        $msg = array();
        // use unset because we do isset below
        $read = implode('',$r);

        /*
         * #id<space>FETCH<space>(
         */

        /* extract the message id */
        $i_space = strpos($read,' ',2);
        $id = substr($read,2,$i_space-2);
        $msg['ID'] = $id;
        $fetch = substr($read,$i_space+1,5);
        if (!is_numeric($id) && $fetch !== 'FETCH') {
            $msg['ERROR'] = $read; // htmlspecialchars should be done just before display. this is backend code
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
                $aFlags = array();
                foreach ($flags as $flag) {
                    $flag = strtolower($flag);
                    $aFlags[$flag] = true;
                }
                $msg['FLAGS'] = $aFlags;
                break;
            case 'RFC822.SIZE':
                $i_pos = strpos($read,' ',$i);
                if (!$i_pos) {
                    $i_pos = strpos($read,')',$i);
                }
                if ($i_pos) {
                    $msg['SIZE'] = substr($read,$i,$i_pos-$i);
                    $i = $i_pos+1;
                } else {
                    break 3;
                }

                break;
            case 'ENVELOPE':
                break; // to be implemented, moving imap code out of the nessages class
                sqimap_parse_address($read,$i,$msg);
                break; // to be implemented, moving imap code out of the nessages class
            case 'BODYSTRUCTURE':
                break;
            case 'INTERNALDATE':
                $msg['INTERNALDATE'] = trim(str_replace('  ', ' ',parseString($read,$i)));
                break;
            case 'BODY.PEEK[HEADER.FIELDS':
            case 'BODY[HEADER.FIELDS':
                $i = strpos($read,'{',$i);
                $header = parseString($read,$i);
                if ($header === false) break 2;
                /* First we replace all \r\n by \n, and unfold the header */
                $hdr = trim(str_replace(array("\r\n", "\n\t", "\n "),array("\n", ' ', ' '), $header));
                /* Now we can make a new header array with */
                /* each element representing a headerline  */
                $hdr = explode("\n" , $hdr);
                $aReceived = array();
                foreach ($hdr as $line) {
                    $pos = strpos($line, ':');
                    if ($pos > 0) {
                        $field = strtolower(substr($line, 0, $pos));
                        if (!strstr($field,' ')) { /* valid field */
                            $value = trim(substr($line, $pos+1));
                            switch($field)
                            {
                            case 'to': $msg['TO'] = $value; break;
                            case 'cc': $msg['CC'] = $value; break;
                            case 'from': $msg['FROM'] = $value; break;
                            case 'date':
                                $msg['DATE'] = str_replace('  ', ' ', $value);
                                break;
                            case 'x-priority':
                            case 'importance':
                            case 'priority':
                                $msg['PRIORITY'] = parsePriority($value); break;
                            case 'subject': $msg['SUBJECT'] = $value; break;
                            case 'content-type':
                                $type = $value;
                                if ($pos = strpos($type, ";")) {
                                    $type = substr($type, 0, $pos);
                                }
                                $type = explode("/", $type);
                                if(!is_array($type) || count($type) < 2) {
                                    $msg['TYPE0'] = 'text';
                                    $msg['TYPE1'] = 'plain';
                                } else {
                                    $msg['TYPE0'] = strtolower($type[0]);
                                    $msg['TYPE1'] = strtolower($type[1]);
                                }
                                break;
                            case 'received':
                                $aReceived[] = $value;
                                break;
                            default: break;
                            }
                        }
                    }
                }
                if (count($aReceived)) {
                    $msg['RECEIVED'] = $aReceived;
                }
                break;
            default:
                ++$i;
                break;
            }
        }
        $msgi ="$unique_id";
        $msg['UID'] = $unique_id;

        $aMessageList[$msgi] = $msg;
        ++$msgi;
    }
    return $aMessageList;
}


/**
 * Work in process
 * @private
 * @author Marc Groot Koerkamp
 */
function sqimap_parse_envelope($read, &$i, &$msg) {
    $arg_no = 0;
    $arg_a = array();
    ++$i;
    for ($cnt = strlen($read); ($i < $cnt) && ($read{$i} != ')'); ++$i) {
        $char = strtoupper($read{$i});
        switch ($char) {
            case '{':
            case '"':
                $arg_a[] = parseString($read,$i);
                ++$arg_no;
                break;
            case 'N':
                /* probably NIL argument */
                if (strtoupper(substr($read, $i, 3)) == 'NIL') {
                    $arg_a[] = '';
                    ++$arg_no;
                    $i += 2;
                }
                break;
            case '(':
                /* Address structure (with group support)
                * Note: Group support is useless on SMTP connections
                *       because the protocol doesn't support it
                */
                $addr_a = array();
                $group = '';
                $a=0;
                for (; $i < $cnt && $read{$i} != ')'; ++$i) {
                    if ($read{$i} == '(') {
                        $addr = sqimap_parse_address($read, $i);
                        if (($addr[3] == '') && ($addr[2] != '')) {
                            /* start of group */
                            $group = $addr[2];
                            $group_addr = $addr;
                            $j = $a;
                        } else if ($group && ($addr[3] == '') && ($addr[2] == '')) {
                        /* end group */
                            if ($a == ($j+1)) { /* no group members */
                                $group_addr[4] = $group;
                                $group_addr[2] = '';
                                $group_addr[0] = "$group: Undisclosed recipients;";
                                $addr_a[] = $group_addr;
                                $group ='';
                            }
                        } else {
                            $addr[4] = $group;
                            $addr_a[] = $addr;
                        }
                        ++$a;
                    }
                }
                $arg_a[] = $addr_a;
                break;
            default: break;
        }
    }

    if (count($arg_a) > 9) {
        $d = strtr($arg_a[0], array('  ' => ' '));
        $d = explode(' ', $d);
        if (!$arg_a[1]) $arg_a[1] = '';
        $msg['DATE'] = $d; /* argument 1: date */
        $msg['SUBJECT'] = $arg_a[1];     /* argument 2: subject */
        $msg['FROM'] = is_array($arg_a[2]) ? $arg_a[2][0] : '';     /* argument 3: from        */
        $msg['SENDER'] = is_array($arg_a[3]) ? $arg_a[3][0] : '';   /* argument 4: sender      */
        $msg['REPLY-TO'] = is_array($arg_a[4]) ? $arg_a[4][0] : '';  /* argument 5: reply-to    */
        $msg['TO'] = $arg_a[5];          /* argument 6: to          */
        $msg['CC'] = $arg_a[6];          /* argument 7: cc          */
        $msg['BCC'] = $arg_a[7];         /* argument 8: bcc         */
        $msg['IN-REPLY-TO'] = $arg_a[8];   /* argument 9: in-reply-to */
        $msg['MESSAGE-ID'] = $arg_a[9];  /* argument 10: message-id */
    }
}


/**
 * Work in process
 * @private
 * @author Marc Groot Koerkamp
 */
function sqimap_parse_address($read, &$i) {
    $arg_a = array();
    for (; $read{$i} != ')'; ++$i) {
        $char = strtoupper($read{$i});
        switch ($char) {
            case '{':
            case '"': $arg_a[] =  parseString($read,$i); break;
            case 'n':
            case 'N':
                if (strtoupper(substr($read, $i, 3)) == 'NIL') {
                    $arg_a[] = '';
                    $i += 2;
                }
                break;
            default: break;
        }
    }

    if (count($arg_a) == 4) {
        return $arg_a;

//        $adr = new AddressStructure();
//        $adr->personal = $arg_a[0];
//        $adr->adl = $arg_a[1];
//        $adr->mailbox = $arg_a[2];
//        $adr->host = $arg_a[3];
    } else {
        $adr = '';
    }
    return $adr;
}


/**
 * Returns a message array with all the information about a message.
 * See the documentation folder for more information about this array.
 *
 * @param  resource $imap_stream imap connection
 * @param  integer  $id uid of the message
 * @param  string   $mailbox used for error handling, can be removed because we should return an error code and generate the message elsewhere
 * @return Message  Message object
 */
function sqimap_get_message($imap_stream, $id, $mailbox) {
    // typecast to int to prohibit 1:* msgs sets
    $id = (int) $id;
    $flags = array();
    $read = sqimap_run_command($imap_stream, "FETCH $id (FLAGS BODYSTRUCTURE)", true, $response, $message, TRUE);
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
        error_message($errmessage, $mailbox, $sort, (int) $startMessage, $color);
        exit;
    }
    $bodystructure = implode('',$read);
    $msg =  mime_structure($bodystructure,$flags);
    $read = sqimap_run_command($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, TRUE);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader($read);
    $msg->rfc822_header = $rfc822_header;
    return $msg;
}


/**
 * Deprecated !!!!!!! DO NOT USE THIS, use sqimap_msgs_list_copy instead
 */
function sqimap_messages_copy($imap_stream, $start, $end, $mailbox) {
    $read = sqimap_run_command ($imap_stream, "COPY $start:$end " . sqimap_encode_mailbox_name($mailbox), true, $response, $message, TRUE);
}


/**
 * Deprecated !!!!!!! DO NOT USE THIS, use sqimap_msgs_list_delete instead
 */
function sqimap_messages_delete($imap_stream, $start, $end, $mailbox, $bypass_trash=false) {
    global $move_to_trash, $trash_folder;

    if (($move_to_trash == true) && ($bypass_trash != true) &&
        (sqimap_mailbox_exists($imap_stream, $trash_folder) && ($mailbox != $trash_folder))) {
        sqimap_messages_copy ($imap_stream, $start, $end, $trash_folder);
    }
    sqimap_messages_flag ($imap_stream, $start, $end, "Deleted", true);
}


/**
 * Deprecated !!!!!!! DO NOT USE THIS, use sqimap_toggle_flag instead
 * Set a flag on the provided uid list
 * @param  resource imap connection
 */
function sqimap_messages_flag($imap_stream, $start, $end, $flag, $handle_errors) {
    $read = sqimap_run_command ($imap_stream, "STORE $start:$end +FLAGS (\\$flag)", $handle_errors, $response, $message, TRUE);
}


/**
 * @deprecated
 */
function sqimap_get_small_header($imap_stream, $id, $sent) {
    $res = sqimap_get_small_header_list($imap_stream, $id, $sent);
    return $res[0];
}

?>