<?php

/**
 * imap_messages.php
 *
 * This implements functions that manipulate messages
 * NOTE: Quite a few functions in this file are obsolete
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */


/**
 * Copy a set of messages ($id) to another mailbox ($mailbox)
 * @param int $imap_stream The resource ID for the IMAP socket
 * @param string $id The list of messages to copy
 * @param string $mailbox The destination to copy to
 * @param bool $handle_errors Show error messages in case of a NO, BAD or BYE response
 * @return bool If the copy completed without errors
 */
function sqimap_msgs_list_copy($imap_stream, $id, $mailbox, $handle_errors = true) {
    $msgs_id = sqimap_message_list_squisher($id);
    $read = sqimap_run_command ($imap_stream, "COPY $msgs_id " . sqimap_encode_mailbox_name($mailbox), $handle_errors, $response, $message, TRUE);
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
 * @param bool $handle_errors Show error messages in case of a NO, BAD or BYE response
 * @param string $source_mailbox (since 1.5.1) name of source mailbox. It is used to
 *  validate that target mailbox != source mailbox.
 * @return bool If the move completed without errors
 */
function sqimap_msgs_list_move($imap_stream, $id, $mailbox, $handle_errors = true, $source_mailbox = false) {
    if ($source_mailbox!==false && $source_mailbox==$mailbox) {
        return false;
    }
    if (sqimap_msgs_list_copy ($imap_stream, $id, $mailbox, $handle_errors)) {
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
 * @param  bool   $bypass_trash (since 1.5.0) skip copy to trash
 * @return array  $aMessageList array with messages containing the new flags and UID @see parseFetch
 * @since 1.4.0
 */
function sqimap_msgs_list_delete($imap_stream, $mailbox, $id, $bypass_trash=false) {
    // FIXME: Remove globals by introducing an associative array with properties as 4th argument as replacement for the $bypass_trash variable.
    global $move_to_trash, $trash_folder;
    if (($move_to_trash == true) && ($bypass_trash != true) &&
        (sqimap_mailbox_exists($imap_stream, $trash_folder) &&  ($mailbox != $trash_folder)) ) {
        /**
         * turn off internal error handling (fourth argument = false) and
         * ignore copy to trash errors (allows to delete messages when overquota)
         */
        sqimap_msgs_list_copy ($imap_stream, $id, $trash_folder, false);
    }
    return sqimap_toggle_flag($imap_stream, $id, '\\Deleted', true, true);
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

    for ($i=0; $i<sizeof($id); $i++) {
        $aMessageList["$id[$i]"] = array();
    }

    $aResponse = sqimap_run_command_list($imap_stream, "STORE $msgs_id ".$set_string."FLAGS ($flag)", $handle_errors, $response, $message, TRUE);

    // parse the fetch response
    $parseFetchResults=parseFetch($aResponse,$aMessageList);

    // some broken IMAP servers do not return UID elements on UID STORE
    // if this is the case, then we need to do a UID FETCH
    $testkey=$id[0];
    if (!isset($parseFetchResults[$testkey]['UID'])) {
        $aResponse = sqimap_run_command_list($imap_stream, "FETCH $msgs_id (FLAGS)", $handle_errors, $response, $message, TRUE);
        $parseFetchResults = parseFetch($aResponse,$aMessageList);
    }

    return ($parseFetchResults);
}


/**
 * Sort the message list and crunch to be as small as possible
 * (overflow could happen, so make it small if possible)
 * @param array $aUid array with uid's
 * @return string $s message set string
 */
function sqimap_message_list_squisher($aUid) {
    if( !is_array( $aUid ) ) {
        return $aUid;
    }
    sort($aUid, SORT_NUMERIC);

    if (count($aUid)) {
        $s = '';
        for ($i=0,$iCnt=count($aUid);$i<$iCnt;++$i) {
            $iStart = $aUid[$i];
            $iEnd = $iStart;
            while ($i<($iCnt-1) && $aUid[$i+1] == $iEnd +1) {
                $iEnd = $aUid[$i+1];
                ++$i;
            }
            if ($s) {
                $s .= ',';
            }
            $s .= $iStart;
            if ($iStart != $iEnd) {
                $s .= ':' . $iEnd;
            }
        }
    }
    return $s;
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
        // FIXME: sqimap_run_command() should return the parsed data accessible by $aDATA['SORT']
        // use sqimap_run_command_list() in case of unsolicited responses. If we don't we could loose the SORT response.
        $aData = sqimap_run_command_list ($imap_stream, $query, false, $response, $message, TRUE);
        /* fallback to default charset */
        if ($response == 'NO') {
            if (strpos($message,'BADCHARSET') !== false ||
                strpos($message,'character') !== false) {
                sqm_trigger_imap_error('SQM_IMAP_BADCHARSET',$query, $response, $message);
                $query = "SORT ($sSortField) US-ASCII $search";
                $aData = sqimap_run_command_list ($imap_stream, $query, true, $response, $message, TRUE);
            } else {
                sqm_trigger_imap_error('SQM_IMAP_ERROR',$query, $response, $message);
            }
        } else if ($response == 'BAD') {
            sqm_trigger_imap_error('SQM_IMAP_NO_SORT',$query, $response, $message);
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
 * @param array $aData imap response (retrieved from sqimap_run_command_list)
 * @param string $sCommand issued imap command (SEARCH or SORT)
 * @return array $aUid uid list
 */
function parseUidList($aData,$sCommand) {
    $aUid = array();
    if (isset($aData) && count($aData)) {
        for ($i=0,$iCnt=count($aData);$i<$iCnt;++$i) {
            for ($j=0,$jCnt=count($aData[$i]);$j<$jCnt;++$j) {
                if (preg_match("/^\* $sCommand (.+)$/", $aData[$i][$j], $aMatch)) {
                    $aUid += explode(' ', trim($aMatch[1]));
                }
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

    // sqimap_get_small_header (see above) returns fields in lower case,
    // but the code below uses all upper case
    foreach ($msgs as $k => $v) 
        if (isset($msgs[$k][strtolower($sSortField)])) 
            $msgs[$k][strtoupper($sSortField)] = $msgs[$k][strtolower($sSortField)];

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
                      $addr[SQM_ADDR_MAILBOX] . "@".$addr[SQM_ADDR_HOST] :
                      $addr[SQM_ADDR_HOST];
                 $v[$f] = ($sPersonal) ? decodeHeader($sPersonal, true, false):$sEmail;'),$sSortField);
            $walk = true;
        }
        // nobreak
      case 'SUBJECT':
        if(!$walk) {
            array_walk($msgs, create_function('&$v,&$k,$f',
                '$v[$f] = (isset($v[$f])) ? $v[$f] : "";
                 $v[$f] = strtolower(decodeHeader(trim($v[$f]), true, false));
                 $v[$f] = (preg_match("/^(?:(?:vedr|sv|re|aw|fw|fwd|\[\w\]):\s*)*\s*(.*)$/si", $v[$f], $matches)) ?
                                    $matches[1] : $v[$f];'),$sSortField);
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
 * Returns an array with each element as a string representing one
 * message-thread as returned by the IMAP server.
 * @param resource $imap_stream IMAP socket connection
 * @param string $search optional search string
 * @return array
 * @link http://www.ietf.org/internet-drafts/draft-ietf-imapext-sort-13.txt
 */
function get_thread_sort($imap_stream, $search='ALL') {
    global $sort_by_ref, $default_charset;

    if ($sort_by_ref == 1) {
        $sort_type = 'REFERENCES';
    } else {
        $sort_type = 'ORDEREDSUBJECT';
    }
    $query = "THREAD $sort_type ".strtoupper($default_charset)." $search";

    // TODO use sqimap_run_command_list as we do in get_server_sort()
    $sRead = sqimap_run_command ($imap_stream, $query, false, $response, $message, TRUE);

    /* fallback to default charset */
    if ($response == 'NO') {
        if (strpos($message,'BADCHARSET') !== false ||
            strpos($message,'character') !== false) {
            sqm_trigger_imap_error('SQM_IMAP_BADCHARSET',$query, $response, $message);
            $query = "THREAD $sort_type US-ASCII $search";
            $sRead = sqimap_run_command ($imap_stream, $query, true, $response, $message, TRUE);
        } else {
            sqm_trigger_imap_error('SQM_IMAP_ERROR',$query, $response, $message);
        }
    } elseif ($response == 'BAD') {
        sqm_trigger_imap_error('SQM_IMAP_NO_THREAD',$query, $response, $message);
    }
    $sThreadResponse = '';
    if (isset($sRead[0])) {
        for ($i=0,$iCnt=count($sRead);$i<$iCnt;++$i) {
            if (preg_match("/^\* THREAD (.+)$/", $sRead[$i], $aMatch)) {
                $sThreadResponse = trim($aMatch[1]);
                break;
            }
        }
    }
    unset($sRead);

    if ($response !== 'OK') {
        return false;
    }

    /* Example response
     *  S: * THREAD (2)(3 6 (4 23)(44 7 96))
     * -- 2
     *
     * -- 3
     *    \-- 6
     *        |-- 4
     *        |   \-- 23
     *        |
     *        \-- 44
     *             \-- 7
     *                 \-- 96
     */
/*
 * Notes for future work:
 * indent_array should contain: indent_level, parent and flags,
 * sibling nodes ..
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

    $j = 0;
    $k = 0;
    $l = 0;
    $aUidThread = array();
    $aIndent = array();
    $aUidSubThread = array();
    $aDepthStack = array();
    $sUid = '';

    if ($sThreadResponse) {
        for ($i=0,$iCnt = strlen($sThreadResponse);$i<$iCnt;++$i) {
            $cChar = $sThreadResponse{$i};
            switch ($cChar) {
                case '(': // new sub thread
                    // correction for a subthread of a thread with no parents in thread
                    if (!count($aUidSubThread) && $j > 0) {
                       --$l;
                    }
                    $aDepthStack[$j] = $l;
                    ++$j;
                    break;
                case ')': // close sub thread
                    if($sUid !== '') {
                        $aUidSubThread[] = $sUid;
                        $aIndent[$sUid] = $j + $l - 1;
                        ++$l;
                        $sUid = '';
                    }
                    --$j;
                    if ($j === 0) {
                        // show message that starts the thread first.
                        $aUidSubThread = array_reverse($aUidSubThread);
                        // do not use array_merge because it's extremely slow and is causing timeouts
                        foreach ($aUidSubThread as $iUid) {
                            $aUidThread[] = $iUid;
                        }
                        $aUidSubThread = array();
                        $l = 0;
                        $aDepthStack = array();
                    } else {
                        $l = $aDepthStack[$j];
                    }
                    break;
                case ' ': // new child
                    if ($sUid !== '') {
                        $aUidSubThread[] = $sUid;
                        $aIndent[$sUid] = $j + $l - 1;
                        ++$l;
                        $sUid = '';
                    }
                    break;
                default: // part of UID
                    $sUid .= $cChar;
                    break;
            }
        }
    }
    unset($sThreadResponse);
    // show newest threads first
    $aUidThread = array_reverse($aUidThread);
    return array($aUidThread,$aIndent);
}


function elapsedTime($start) {
    $stop = gettimeofday();
    $timepassed =  1000000 * ($stop['sec'] - $start['sec']) + $stop['usec'] - $start['usec'];
    return $timepassed;
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
 *
 * WARNING: function is not portable between SquirrelMail 1.2.x, 1.4.x and 1.5.x.
 * Output format, third argument and $msg_list array format requirements differ.
 * @param stream $imap_stream imap connection
 * @param array  $msg_list array with id's to create a msgs set from
 * @param array  $aHeaderFields (since 1.5.0) requested header fields
 * @param array  $aFetchItems (since 1.5.0) requested other fetch items like FLAGS, RFC822.SIZE
 * @return array $aMessages associative array with messages. Key is the UID, value is an associative array
 * @since 1.1.3
 */
function sqimap_get_small_header_list($imap_stream, $msg_list,
    $aHeaderFields = array('Date', 'To', 'Cc', 'From', 'Subject', 'X-Priority', 'Content-Type'),
    $aFetchItems = array('FLAGS', 'RFC822.SIZE', 'INTERNALDATE')) {

    $aMessageList = array();

    /**
     * Catch other priority headers as well
     */
    if (in_array('X-Priority',$aHeaderFields,true)) {
        $aHeaderFields[] = 'Importance';
        $aHeaderFields[] = 'Priority';
    }

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
function parseFetch(&$aResponse,$aMessageList = array()) {
    for ($j=0,$iCnt=count($aResponse);$j<$iCnt;++$j) {
        $aMsg = array();

        $read = implode('',$aResponse[$j]);
        // free up memmory
        unset($aResponse[$j]); /* unset does not reindex the array. the for loop is safe */
        /*
            * #id<space>FETCH<space>(
        */

        /* extract the message id */
        $i_space = strpos($read,' ',2);/* position 2ed <space> */
        $id = substr($read,2/* skip "*<space>" */,$i_space -2);
        $aMsg['ID'] = $id;
        $fetch = substr($read,$i_space+1,5);
        if (!is_numeric($id) && $fetch !== 'FETCH') {
            $aMsg['ERROR'] = $read; // sm_encode_html_special_chars should be done just before display. this is backend code
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
            /*
             * use allcaps for imap items and lowcaps for headers as key for the $aMsg array
             */
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
                $aMsg['FLAGS'] = $aFlags;
                break;
            case 'RFC822.SIZE':
                $i_pos = strpos($read,' ',$i);
                if (!$i_pos) {
                    $i_pos = strpos($read,')',$i);
                }
                if ($i_pos) {
                    $aMsg['SIZE'] = substr($read,$i,$i_pos-$i);
                    $i = $i_pos+1;
                } else {
                    break 3;
                }
                break;
            case 'ENVELOPE':
                // sqimap_parse_address($read,$i,$aMsg);
                break; // to be implemented, moving imap code out of the Message class
            case 'BODYSTRUCTURE':
                break; // to be implemented, moving imap code out of the Message class
            case 'INTERNALDATE':
                $aMsg['INTERNALDATE'] = trim(str_replace('  ', ' ',parseString($read,$i)));
                break;
            case 'BODY.PEEK[HEADER.FIELDS':
            case 'BODY[HEADER.FIELDS':
                $i = strpos($read,'{',$i); // header is always returned as literal because it contain \n characters
                $header = parseString($read,$i);
                if ($header === false) break 2;
                /* First we replace all \r\n by \n, and unfold the header */
                $hdr = trim(str_replace(array("\r\n", "\n\t", "\n "),array("\n", ' ', ' '), $header));
                /* Now we can make a new header array with
                   each element representing a headerline  */
                $aHdr = explode("\n" , $hdr);
                $aReceived = array();
                foreach ($aHdr as $line) {
                    $pos = strpos($line, ':');
                    if ($pos > 0) {
                        $field = strtolower(substr($line, 0, $pos));
                        if (!strstr($field,' ')) { /* valid field */
                            $value = trim(substr($line, $pos+1));
                            switch($field) {
                                case 'date':
                                    $aMsg['date'] = trim(str_replace('  ', ' ', $value));
                                    break;
                                case 'x-priority': $aMsg['x-priority'] = ($value) ? (int) $value{0} : 3; break;
                                case 'priority':
                                case 'importance':
                                    // duplicate code with Rfc822Header.cls:parsePriority()
                                    if (!isset($aMsg['x-priority'])) {
                                        $aPrio = preg_split('/\s/',trim($value));
                                        $sPrio = strtolower(array_shift($aPrio));
                                        if  (is_numeric($sPrio)) {
                                            $iPrio = (int) $sPrio;
                                        } elseif ( $sPrio == 'non-urgent' || $sPrio == 'low' ) {
                                            $iPrio = 5;
                                        } elseif ( $sPrio == 'urgent' || $sPrio == 'high' ) {
                                            $iPrio = 1;
                                        } else {
                                            // default is normal priority
                                            $iPrio = 3;
                                        }
                                        $aMsg['x-priority'] = $iPrio;
                                    }
                                    break;
                                case 'content-type':
                                    $type = $value;
                                    if ($pos = strpos($type, ";")) {
                                        $type = substr($type, 0, $pos);
                                    }
                                    $type = explode("/", $type);
                                    if(!is_array($type) || count($type) < 2) {
                                        $aMsg['content-type'] = array('text','plain');
                                    } else {
                                        $aMsg['content-type'] = array(strtolower($type[0]),strtolower($type[1]));
                                    }
                                    break;
                                case 'received':
                                    $aMsg['received'][] = $value;
                                    break;
                                default:
                                    $aMsg[$field] = $value;
                                    break;
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
        if (!empty($unique_id)) {
            $msgi = "$unique_id";
            $aMsg['UID'] = $unique_id;
       } else {
            $msgi = '';
       }
       $aMessageList[$msgi] = $aMsg;
       $aResponse[$j] = NULL;
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
 * @param  int      $hide Indicates whether or not to hide any errors: 0 = don't hide, 1 = hide (just exit), 2 = hide (return FALSE), 3 = hide (return error string) (OPTIONAL; default don't hide)
 * @return mixed  Message object or FALSE/error string if error occurred and $hide is set to 2/3
 */
function sqimap_get_message($imap_stream, $id, $mailbox, $hide=0) {
    // typecast to int to prohibit 1:* msgs sets
    // Update: $id should always be sanitized into a BIGINT so this
    // is being removed; leaving this code here in case something goes
    // wrong, however
    //$id = (int) $id;
    $flags = array();
    $read = sqimap_run_command($imap_stream, "FETCH $id (FLAGS BODYSTRUCTURE)", true, $response, $message, TRUE);
    if ($read) {
        if (preg_match('/.+FLAGS\s\((.*)\)\s/AUi',$read[0],$regs)) {
            if (trim($regs[1])) {
                $flags = preg_split('/ /', $regs[1],-1,PREG_SPLIT_NO_EMPTY);
            }
        }
    } else {

        if ($hide == 1) exit;
        if ($hide == 2) return FALSE;

        /* the message was not found, maybe the mailbox was modified? */
        global $sort, $startMessage;

        $errmessage = _("The server couldn't find the message you requested.");

        if ($hide == 3) return $errmessage;

        $errmessage .= '<p>'._("Most probably your message list was out of date and the message has been moved away or deleted (perhaps by another program accessing the same mailbox).");

        /* this will include a link back to the message list */
        error_message($errmessage, $mailbox, $sort, (int) $startMessage);
        exit;
    }
    $bodystructure = implode('',$read);
    $msg =  mime_structure($bodystructure,$flags);
    $read = sqimap_run_command($imap_stream, "FETCH $id BODY[HEADER]", true, $response, $message, TRUE);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader($read);
    $msg->rfc822_header = $rfc822_header;

    parse_message_entities($msg, $id, $imap_stream);
    return $msg;
 }


/**
 * Recursively parse embedded messages (if any) in the given
 * message, building correct rfc822 headers for each one
 *
 * @param object $msg The message object to scan for attached messages
 *                    NOTE: this is passed by reference!  Changes made
 *                    within will affect the caller's copy of $msg!
 * @param int $id The top-level message UID on the IMAP server, even
 *                if the $msg being passed in is only an attached entity
 *                thereof.
 * @param resource $imap_stream A live connection to the IMAP server.
 *
 * @return void
 *
 * @since 1.5.2
 *
 */
function parse_message_entities(&$msg, $id, $imap_stream) {
    if (!empty($msg->entities)) foreach ($msg->entities as $i => $entity) {
        if (is_object($entity) && strtolower(get_class($entity)) == 'message') {
            if (!empty($entity->rfc822_header)) {
                $read = sqimap_run_command($imap_stream, "FETCH $id BODY[". $entity->entity_id .".HEADER]", true, $response, $message, TRUE);
                $rfc822_header = new Rfc822Header();
                $rfc822_header->parseHeader($read);
                $msg->entities[$i]->rfc822_header = $rfc822_header;
            }
            parse_message_entities($msg->entities[$i], $id, $imap_stream);
        }
    }
}
