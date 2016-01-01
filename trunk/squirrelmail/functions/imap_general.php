<?php

/**
 * imap_general.php
 *
 * This implements all functions that do general IMAP functions.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */

/** Includes.. */

require_once(SM_PATH . 'functions/rfc822address.php');


/**
 * Generates a new session ID by incrementing the last one used;
 * this ensures that each command has a unique ID.
 * @param bool $unique_id (since 1.3.0) controls use of unique
 *  identifiers/message sequence numbers in IMAP commands. See IMAP
 *  rfc 'UID command' chapter.
 * @return string IMAP session id of the form 'A000'.
 * @since 1.2.0
 */
function sqimap_session_id($unique_id = FALSE) {
    static $sqimap_session_id = 1;

    if (!$unique_id) {
        return( sprintf("A%03d", $sqimap_session_id++) );
    } else {
        return( sprintf("A%03d", $sqimap_session_id++) . ' UID' );
    }
}

/**
 * Both send a command and accept the result from the command.
 * This is to allow proper session number handling.
 * @param stream $imap_stream imap connection resource
 * @param string $query imap command
 * @param boolean $handle_errors see sqimap_retrieve_imap_response()
 * @param array $response
 * @param array $message
 * @param boolean $unique_id (since 1.3.0) see sqimap_session_id().
 * @return mixed returns false on imap error. displays error message
 *  if imap stream is not available.
 * @since 1.2.3
 */
function sqimap_run_command_list ($imap_stream, $query, $handle_errors, &$response, &$message, $unique_id = false) {
    if ($imap_stream) {
        $sid = sqimap_session_id($unique_id);
        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
        $tag_uid_a = explode(' ',trim($sid));
        $tag = $tag_uid_a[0];
        $read = sqimap_retrieve_imap_response ($imap_stream, $tag, $handle_errors, $response, $message, $query );
        /* get the response and the message */
        $message = $message[$tag];
        $response = $response[$tag];
        return $read[$tag];
//FIXME: obey $handle_errors below!
    } else {
        global $squirrelmail_language, $color;
        set_up_language($squirrelmail_language);
//FIXME: NO HTML IN CORE!
        $string = "<b><font color=\"$color[2]\">\n" .
                _("ERROR: No available IMAP stream.") .
//FIXME: NO HTML IN CORE!
                "</b></font>\n";
        error_box($string);
        return false;
    }
}

/**
 * @param stream $imap_stream imap connection resource
 * @param string $query imap command
 * @param boolean $handle_errors see sqimap_retrieve_imap_response()
 * @param array $response empty string, if return = false
 * @param array $message empty string, if return = false
 * @param boolean $unique_id (since 1.3.0) see sqimap_session_id()
 * @param boolean $filter (since 1.4.1 and 1.5.0) see sqimap_fread()
 * @param mixed $outputstream (since 1.4.1 and 1.5.0) see sqimap_fread()
 * @param boolean $no_return (since 1.4.1 and 1.5.0) see sqimap_fread()
 * @return mixed returns false on imap error. displays error message
 *  if imap stream is not available.
 * @since 1.2.3
 */
function sqimap_run_command ($imap_stream, $query, $handle_errors, &$response,
                            &$message, $unique_id = false,$filter=false,
                             $outputstream=false,$no_return=false) {
    if ($imap_stream) {
        $sid = sqimap_session_id($unique_id);
        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
        $tag_uid_a = explode(' ',trim($sid));
        $tag = $tag_uid_a[0];

        $read = sqimap_read_data ($imap_stream, $tag, $handle_errors, $response,
                                  $message, $query,$filter,$outputstream,$no_return);
        if (empty($read)) {    //IMAP server dropped its connection
            $response = '';
            $message = '';
            return false;
        }
        /* retrieve the response and the message */
        $response = $response[$tag];
        $message  = $message[$tag];

        if (!empty($read[$tag])) {
            /* sqimap_read_data should be called for one response
               but since it just calls sqimap_retrieve_imap_response
               which handles multiple responses we need to check for
               that and merge the $read[$tag] array IF they are
               separated and IF it was a FETCH response. */

            if (isset($read[$tag][1]) && is_array($read[$tag][1]) && isset($read[$tag][1][0])
                && preg_match('/^\* \d+ FETCH/', $read[$tag][1][0])) {
                $result = array();
                foreach($read[$tag] as $index => $value) {
                    $result = array_merge($result, $read[$tag]["$index"]);
                }
                return $result;
            }

            return $read[$tag][0];
        } else {
            return $read[$tag];
        }
//FIXME: obey $handle_errors below!
    } else {
        global $squirrelmail_language, $color;
        set_up_language($squirrelmail_language);
//FIXME: NO HTML IN CORE!
        $string = "<b><font color=\"$color[2]\">\n" .
                _("ERROR: No available IMAP stream.") .
//FIXME: NO HTML IN CORE!
                "</b></font>\n";
        error_box($string);
        return false;
    }
}

/**
 * @param mixed $new_query
 * @param string $tag
 * @param array $aQuery
 * @param boolean $unique_id see sqimap_session_id()
 * @since 1.5.0
 */
function sqimap_prepare_pipelined_query($new_query,&$tag,&$aQuery,$unique_id) {
    $sid = sqimap_session_id($unique_id);
    $tag_uid_a = explode(' ',trim($sid));
    $tag = $tag_uid_a[0];
    $query = $sid . ' '.$new_query."\r\n";
    $aQuery[$tag] = $query;
}

/**
 * @param stream $imap_stream imap stream
 * @param array $aQueryList
 * @param boolean $handle_errors
 * @param array $aServerResponse
 * @param array $aServerMessage
 * @param boolean $unique_id see sqimap_session_id()
 * @param boolean $filter see sqimap_fread()
 * @param mixed $outputstream see sqimap_fread()
 * @param boolean $no_return see sqimap_fread()
 * @since 1.5.0
 */
function sqimap_run_pipelined_command ($imap_stream, $aQueryList, $handle_errors,
                       &$aServerResponse, &$aServerMessage, $unique_id = false,
                       $filter=false,$outputstream=false,$no_return=false) {
    $aResponse = false;

    /*
       Do not fire all calls at once to the IMAP server but split the calls up
       in portions of $iChunkSize. If we do not do that I think we misbehave as
       IMAP client or should handle BYE calls if the IMAP server drops the
       connection because the number of queries is to large. This isn't tested
       but a wild guess how it could work in the field.

       After testing it on Exchange 2000 we discovered that a chunksize of 32
       was quicker then when we raised it to 128.
    */
    $iQueryCount = count($aQueryList);
    $iChunkSize = 32;
    // array_chunk would also do the job but it's supported from php > 4.2
    $aQueryChunks = array();
    $iLoops = floor($iQueryCount / $iChunkSize);

    if ($iLoops * $iChunkSize != $iQueryCount) ++$iLoops;

    if (!function_exists('array_chunk')) { // arraychunk replacement
        reset($aQueryList);
        for($i=0;$i<$iLoops;++$i) {
            for($j=0;$j<$iChunkSize;++$j) {
                $key = key($aQueryList);
                $aTmp[$key] = $aQueryList[$key];
                if (next($aQueryList) === false) break;
            }
            $aQueryChunks[] = $aTmp;
        }
    } else {
        $aQueryChunks = array_chunk($aQueryList,$iChunkSize,true);
    }

    for ($i=0;$i<$iLoops;++$i) {
        $aQuery = $aQueryChunks[$i];
        foreach($aQuery as $tag => $query) {
            fputs($imap_stream,$query);
            $aResults[$tag] = false;
        }
        foreach($aQuery as $tag => $query) {
            if ($aResults[$tag] == false) {
                $aReturnedResponse = sqimap_retrieve_imap_response ($imap_stream, $tag,
                                    $handle_errors, $response, $message, $query,
                                    $filter,$outputstream,$no_return);
                foreach ($aReturnedResponse as $returned_tag => $aResponse) {
                    if (!empty($aResponse)) {
                        $aResults[$returned_tag] = $aResponse[0];
                    } else {
                        $aResults[$returned_tag] = $aResponse;
                    }
                    $aServerResponse[$returned_tag] = $response[$returned_tag];
                    $aServerMessage[$returned_tag] = $message[$returned_tag];
                }
            }
        }
    }
    return $aResults;
}

/**
 * Custom fgets function: gets a line from the IMAP server,
 * no matter how big it may be.
 * @param stream $imap_stream the stream to read from
 * @return string a line
 * @since 1.2.8
 */
function sqimap_fgets($imap_stream) {
    $read = '';
    $buffer = 4096;
    $results = '';
    $offset = 0;
    while (strpos($results, "\r\n", $offset) === false) {
        if (!($read = fgets($imap_stream, $buffer))) {
        /* this happens in case of an error */
        /* reset $results because it's useless */
        $results = false;
            break;
        }
        if ( $results != '' ) {
            $offset = strlen($results) - 1;
        }
        $results .= $read;
    }
    return $results;
}

/**
 * @param stream $imap_stream
 * @param integer $iSize
 * @param boolean $filter
 * @param mixed $outputstream stream or 'php://stdout' string
 * @param boolean $no_return controls data returned by function
 * @return string
 * @since 1.4.1
 */
function sqimap_fread($imap_stream,$iSize,$filter=false,
                      $outputstream=false, $no_return=false) {
    if (!$filter || !$outputstream) {
        $iBufferSize = $iSize;
    } else {
        // see php bug 24033. They changed fread behaviour %$^&$%
        $iBufferSize = 7800; // multiple of 78 in case of base64 decoding.
    }
    if ($iSize < $iBufferSize) {
        $iBufferSize = $iSize;
    }

    $iRetrieved = 0;
    $results = '';
    $sRead = $sReadRem = '';
    // NB: fread can also stop at end of a packet on sockets.
    while ($iRetrieved < $iSize) {
        $sRead = fread($imap_stream,$iBufferSize);
        $iLength = strlen($sRead);
        $iRetrieved += $iLength ;
        $iRemaining = $iSize - $iRetrieved;
        if ($iRemaining < $iBufferSize) {
            $iBufferSize = $iRemaining;
        }
        if ($sRead == '') {
            $results = false;
            break;
        }
        if ($sReadRem != '') {
            $sRead = $sReadRem . $sRead;
            $sReadRem = '';
        }

        if ($filter && $sRead != '') {
           // in case the filter is base64 decoding we return a remainder
           $sReadRem = $filter($sRead);
        }
        if ($outputstream && $sRead != '') {
           if (is_resource($outputstream)) {
               fwrite($outputstream,$sRead);
           } else if ($outputstream == 'php://stdout') {
               echo $sRead;
           }
        }
        if ($no_return) {
            $sRead = '';
        } else {
            $results .= $sRead;
        }
    }
    return $results;
}


/**
 * Obsolete function, inform plugins that use it
 * @param stream $imap_stream
 * @param string $tag
 * @param boolean $handle_errors
 * @param array $response
 * @param array $message
 * @param string $query
 * @since 1.1.3
 * @deprecated (since 1.5.0) use sqimap_run_command or sqimap_run_command_list instead
 */
function sqimap_read_data_list($imap_stream, $tag, $handle_errors,
          &$response, &$message, $query = '') {
    global $color, $oTemplate, $squirrelmail_language;
    set_up_language($squirrelmail_language);
//FIXME: NO HTML IN CORE!
    $string = "<b><font color=\"$color[2]\">\n" .
        _("ERROR: Bad function call.") .
//FIXME: NO HTML IN CORE!
        "</b><br />\n" .
        _("Reason:") . ' '.
          'There is a plugin installed which make use of the  <br />' .
          'SquirrelMail internal function sqimap_read_data_list.<br />'.
          'Please adapt the installed plugin and let it use<br />'.
          'sqimap_run_command or sqimap_run_command_list instead<br /><br />'.
          'The following query was issued:<br />'.
//FIXME: NO HTML IN CORE!
           sm_encode_html_special_chars($query) . '<br />' . "</font><br />\n";
    error_box($string);
    $oTemplate->display('footer.tpl');
    exit;
}

/**
 * Function to display an error related to an IMAP query.
 * @param string title the caption of the error box
 * @param string query the query that went wrong
 * @param string message_title optional message title
 * @param string message optional error message
 * @param string $link an optional link to try again
 * @return void
 * @since 1.5.0
 */
function sqimap_error_box($title, $query = '', $message_title = '', $message = '', $link = '')
{
    global $color, $squirrelmail_language;

    set_up_language($squirrelmail_language);
//FIXME: NO HTML IN CORE!
    $string = "<font color=\"$color[2]\"><b>\n" . $title . "</b><br />\n";
    $cmd = explode(' ',$query);
    $cmd= strtolower($cmd[0]);

    if ($query != '' &&  $cmd != 'login')
        $string .= _("Query:") . ' ' . sm_encode_html_special_chars($query) . '<br />';
    if ($message_title != '')
        $string .= $message_title;
    if ($message != '')
        $string .= sm_encode_html_special_chars($message);
//FIXME: NO HTML IN CORE!
    $string .= "</font><br />\n";
    if ($link != '')
        $string .= $link;
    error_box($string);
}

/**
 * Reads the output from the IMAP stream.  If handle_errors is set to true,
 * this will also handle all errors that are received.  If it is not set,
 * the errors will be sent back through $response and $message.
 * @param stream $imap_stream imap stream
 * @param string $tag
 * @param boolean $handle_errors handle errors internally or send them in $response and $message.
 * @param array $response
 * @param array $message
 * @param string $query command that can be printed if something fails
 * @param boolean $filter see sqimap_fread()
 * @param mixed $outputstream  see sqimap_fread()
 * @param boolean $no_return  see sqimap_fread()
 * @since 1.5.0
 */
function sqimap_retrieve_imap_response($imap_stream, $tag, $handle_errors,
          &$response, &$message, $query = '',
           $filter = false, $outputstream = false, $no_return = false) {
    global $color, $squirrelmail_language;
    $read = '';
    if (!is_array($message)) $message = array();
    if (!is_array($response)) $response = array();
    $aResponse = '';
    $resultlist = array();
    $data = array();
    $sCommand = '';
    if (preg_match("/^(\w+)\s*/",$query,$aMatch)) {
        $sCommand = strtoupper($aMatch[1]);
    } else {
        // error reporting (shouldn't happen)
    }
    $read = sqimap_fgets($imap_stream);
    $i = 0;
    while ($read) {
        $char = $read{0};
        switch ($char)
        {
          case '+':
          default:
            $read = sqimap_fgets($imap_stream);
            break;

          case $tag{0}:
          {
            /* get the command */
            $arg = '';
            $i = strlen($tag)+1;
            $s = substr($read,$i);
            if (($j = strpos($s,' ')) || ($j = strpos($s,"\n"))) {
                $arg = substr($s,0,$j);
            }
            $found_tag = substr($read,0,$i-1);
            if ($found_tag) {
                switch ($arg)
                {
                  case 'OK':
                  case 'BAD':
                  case 'NO':
                  case 'BYE':
                  case 'PREAUTH':
                    $response[$found_tag] = $arg;
                    $message[$found_tag] = trim(substr($read,$i+strlen($arg)));
                    if (!empty($data)) {
                        $resultlist[] = $data;
                    }
                    $aResponse[$found_tag] = $resultlist;
                    $data = $resultlist = array();
                    if ($found_tag == $tag) {
                        break 3; /* switch switch while */
                    }
                  break;
                  default:
                    /* this shouldn't happen */
                    $response[$found_tag] = $arg;
                    $message[$found_tag] = trim(substr($read,$i+strlen($arg)));
                    if (!empty($data)) {
                        $resultlist[] = $data;
                    }
                    $aResponse[$found_tag] = $resultlist;
                    $data = $resultlist = array();
                    if ($found_tag == $tag) {
                        break 3; /* switch switch while */
                    }
                }
            }
            $read = sqimap_fgets($imap_stream);
            if ($read === false) { /* error */
                 break 2; /* switch while */
            }
            break;
          } // end case $tag{0}

          case '*':
          {
            if (($sCommand == "FETCH" || $sCommand == "STORE")  && preg_match('/^\*\s\d+\sFETCH/',$read)) {
                /* check for literal */
                $s = substr($read,-3);
                $fetch_data = array();
                do { /* outer loop, continue until next untagged fetch
                        or tagged reponse */
                    do { /* innerloop for fetching literals. with this loop
                            we prohibid that literal responses appear in the
                            outer loop so we can trust the untagged and
                            tagged info provided by $read */
                        $read_literal = false;
                        if ($s === "}\r\n") {
                            $j = strrpos($read,'{');
                            $iLit = substr($read,$j+1,-3);
                            $fetch_data[] = $read;
                            $sLiteral = sqimap_fread($imap_stream,$iLit,$filter,$outputstream,$no_return);
                            if ($sLiteral === false) { /* error */
                                break 4; /* while while switch while */
                            }
                            /* backwards compattibility */
                            $aLiteral = explode("\n", $sLiteral);
                            /* release not neaded data */
                            unset($sLiteral);
                            foreach ($aLiteral as $line) {
                                $fetch_data[] = $line ."\n";
                            }
                            /* release not neaded data */
                            unset($aLiteral);
                            /* next fgets belongs to this fetch because
                               we just got the exact literalsize and there
                               must follow data to complete the response */
                            $read = sqimap_fgets($imap_stream);
                            if ($read === false) { /* error */
                                break 4; /* while while switch while */
                            }
                            $s = substr($read,-3);
                            $read_literal = true;
                            continue;
                        } else {
                            $fetch_data[] = $read;
                        }
                        /* retrieve next line and check in the while
                           statements if it belongs to this fetch response */
                        $read = sqimap_fgets($imap_stream);
                        if ($read === false) { /* error */
                            break 4; /* while while switch while */
                        }
                        /* check for next untagged reponse and break */
                        if ($read{0} == '*') break 2;
                        $s = substr($read,-3);
                    } while ($s === "}\r\n" || $read_literal);
                    $s = substr($read,-3);
                } while ($read{0} !== '*' &&
                         substr($read,0,strlen($tag)) !== $tag);
                $resultlist[] = $fetch_data;
                /* release not neaded data */
                unset ($fetch_data);
            } else {
                $s = substr($read,-3);
                do {
                    if ($s === "}\r\n") {
                        $j = strrpos($read,'{');
                        $iLit = substr($read,$j+1,-3);
                        // check for numeric value to avoid that untagged responses like:
                        // * OK [PARSE] Unexpected characters at end of address: {SET:debug=51}
                        // will trigger literal fetching  ({SET:debug=51} !== int )
                        if (is_numeric($iLit)) {
                            $data[] = $read;
                            $sLiteral = fread($imap_stream,$iLit);
                            if ($sLiteral === false) { /* error */
                                $read = false;
                                break 3; /* while switch while */
                            }
                            $data[] = $sLiteral;
                            $data[] = sqimap_fgets($imap_stream);
                        } else {
                            $data[] = $read;
                        }
                    } else {
                         $data[] = $read;
                    }
                    $read = sqimap_fgets($imap_stream);
                    if ($read === false) {
                        break 3; /* while switch while */
                    } else if ($read{0} == '*') {
                        break;
                    }
                    $s = substr($read,-3);
                } while ($s === "}\r\n");
                break 1;
            }
            break;
          } // end case '*'
        }   // end switch
    } // end while

    /* error processing in case $read is false */
    if ($read === false) {
        // try to retrieve an untagged bye respons from the results
        $sResponse = array_pop($data);
        if ($sResponse !== NULL && strpos($sResponse,'* BYE') !== false) {
            if (!$handle_errors) {
                $query = '';
            }
            sqimap_error_box(_("ERROR: IMAP server closed the connection."), $query, _("Server responded:"),$sResponse);
//FIXME: NO HTML IN CORE!
            echo '</body></html>';
            exit;
        } else if ($handle_errors) {
            unset($data);
            sqimap_error_box(_("ERROR: Connection dropped by IMAP server."), $query);
            exit;
        }
    }

    /* Set $resultlist array */
    if (!empty($data)) {
        //$resultlist[] = $data;
    }
    elseif (empty($resultlist)) {
        $resultlist[] = array();
    }

    /* Return result or handle errors */
    if ($handle_errors == false) {
        return $aResponse;
    }
    switch ($response[$tag]) {
    case 'OK':
        return $aResponse;
        break;
    case 'NO':
        /* ignore this error from M$ exchange, it is not fatal (aka bug) */
        if (strstr($message[$tag], 'command resulted in') === false) {
            sqsession_register('NO', 'IMAP_FATAL_ERROR_TYPE');
            sqsession_register($query, 'IMAP_FATAL_ERROR_QUERY');
            sqsession_register($message[$tag], 'IMAP_FATAL_ERROR_MESSAGE');
            sqimap_error_box(_("ERROR: Could not complete request."), $query, _("Reason Given:") . ' ', $message[$tag]);
            echo '</body></html>';
            exit;
        }
        break;
    case 'BAD':
        sqsession_register('BAD', 'IMAP_FATAL_ERROR_TYPE');
        sqsession_register($query, 'IMAP_FATAL_ERROR_QUERY');
        sqsession_register($message[$tag], 'IMAP_FATAL_ERROR_MESSAGE');
        sqimap_error_box(_("ERROR: Bad or malformed request."), $query, _("Server responded:") . ' ', $message[$tag]);
//FIXME: NO HTML IN CORE!
        echo '</body></html>';
        exit;
    case 'BYE':
        sqsession_register('BYE', 'IMAP_FATAL_ERROR_TYPE');
        sqsession_register($query, 'IMAP_FATAL_ERROR_QUERY');
        sqsession_register($message[$tag], 'IMAP_FATAL_ERROR_MESSAGE');
        sqimap_error_box(_("ERROR: IMAP server closed the connection."), $query, _("Server responded:") . ' ', $message[$tag]);
//FIXME: NO HTML IN CORE!
        echo '</body></html>';
        exit;
    default:
        sqsession_register('UNKNOWN', 'IMAP_FATAL_ERROR_TYPE');
        sqsession_register($query, 'IMAP_FATAL_ERROR_QUERY');
        sqsession_register($message[$tag], 'IMAP_FATAL_ERROR_MESSAGE');
        sqimap_error_box(_("ERROR: Unknown IMAP response."), $query, _("Server responded:") . ' ', $message[$tag]);
       /* the error is displayed but because we don't know the reponse we
          return the result anyway */
       return $aResponse;
       break;
    }
}

/**
 * @param stream $imap_stream imap string
 * @param string $tag_uid
 * @param boolean $handle_errors
 * @param array $response
 * @param array $message
 * @param string $query (since 1.2.5)
 * @param boolean $filter (since 1.4.1) see sqimap_fread()
 * @param mixed $outputstream (since 1.4.1) see sqimap_fread()
 * @param boolean $no_return (since 1.4.1) see sqimap_fread()
 */
function sqimap_read_data ($imap_stream, $tag_uid, $handle_errors,
                           &$response, &$message, $query = '',
                           $filter=false,$outputstream=false,$no_return=false) {

    $tag_uid_a = explode(' ',trim($tag_uid));
    $tag = $tag_uid_a[0];

    $res = sqimap_retrieve_imap_response($imap_stream, $tag, $handle_errors,
              $response, $message, $query,$filter,$outputstream,$no_return);
    return $res;
}

/**
 * Connects to the IMAP server and returns a resource identifier for use with
 * the other SquirrelMail IMAP functions. Does NOT login!
 * @param string server hostname of IMAP server
 * @param int port port number to connect to
 * @param integer $tls whether to use plain text(0), TLS(1) or STARTTLS(2) when connecting.
 *  Argument was boolean before 1.5.1.
 * @param array $stream_options Stream context options, see config_local.php
 *                           for more details (OPTIONAL)
 * @return imap-stream resource identifier
 * @since 1.5.0 (usable only in 1.5.1 or later)
 */
function sqimap_create_stream($server,$port,$tls=0,$stream_options=array()) {
    global $squirrelmail_language;

    if (strstr($server,':') && ! preg_match("/^\[.*\]$/",$server)) {
        // numerical IPv6 address must be enclosed in square brackets
        $server = '['.$server.']';
    }

    // NB: Using "ssl://" ensures the highest possible TLS version
    // will be negotiated with the server (whereas "tls://" only
    // uses TLS version 1.0)
    //
    if ($tls == 1) {
        if ((check_php_version(4,3)) and (extension_loaded('openssl'))) {
            if (function_exists('stream_socket_client')) {
                $server_address = 'ssl://' . $server . ':' . $port;
                $ssl_context = @stream_context_create($stream_options);
                $connect_timeout = ini_get('default_socket_timeout');
                // null timeout is broken
                if ($connect_timeout == 0)
                    $connect_timeout = 15;
                $imap_stream = @stream_socket_client($server_address, $error_number, $error_string, $connect_timeout, STREAM_CLIENT_CONNECT, $ssl_context);
            } else {
                $imap_stream = @fsockopen('ssl://' . $server, $port, $error_number, $error_string, 15);
            }
        } else {
            require_once(SM_PATH . 'functions/display_messages.php');
            logout_error( sprintf(_("Error connecting to IMAP server: %s."), $server).
                '<br />'.
                _("TLS is enabled, but this version of PHP does not support TLS sockets, or is missing the openssl extension.").
                '<br /><br />'.
                _("Please contact your system administrator and report this error."),
                          sprintf(_("Error connecting to IMAP server: %s."), $server));
        }
    } else {
        $imap_stream = @fsockopen($server, $port, $error_number, $error_string, 15);
    }


    /* Do some error correction */
    if (!$imap_stream) {
        set_up_language($squirrelmail_language, true);
        require_once(SM_PATH . 'functions/display_messages.php');
        logout_error( sprintf(_("Error connecting to IMAP server: %s."), $server).
//FIXME: NO HTML IN CORE!
            "<br />\r\n$error_number : $error_string<br />\r\n",
                      sprintf(_("Error connecting to IMAP server: %s."), $server) );
        exit;
    }
    $server_info = fgets ($imap_stream, 1024);

    /**
     * Implementing IMAP STARTTLS (rfc2595) in php 5.1.0+
     * http://www.php.net/stream-socket-enable-crypto
     */
    if ($tls === 2) {
        if (function_exists('stream_socket_enable_crypto')) {
            // check starttls capability, don't use cached capability version
            if (! sqimap_capability($imap_stream, 'STARTTLS', false)) {
                // imap server does not declare starttls support
                sqimap_error_box(sprintf(_("Error connecting to IMAP server: %s."), $server),
                                 '','',
                                 _("IMAP STARTTLS is enabled in SquirrelMail configuration, but used IMAP server does not support STARTTLS."));
                exit;
            }

            // issue starttls command and check response
            sqimap_run_command($imap_stream, 'STARTTLS', false, $starttls_response, $starttls_message);
            // check response
            if ($starttls_response!='OK') {
                // starttls command failed
                sqimap_error_box(sprintf(_("Error connecting to IMAP server: %s."), $server),
                                 'STARTTLS',
                                 _("Server replied:") . ' ',
                                 $starttls_message);
                exit();
            }

            // start crypto on connection. suppress function errors.
            if (@stream_socket_enable_crypto($imap_stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                // starttls was successful

                /**
                 * RFC 2595 requires to discard CAPABILITY information after successful
                 * STARTTLS command. We don't follow RFC, because SquirrelMail stores CAPABILITY
                 * information only after successful login (src/redirect.php) and cached information
                 * is used only in other php script connections after successful STARTTLS. If script
                 * issues sqimap_capability() call before sqimap_login() and wants to get initial
                 * capability response, script should set third sqimap_capability() argument to false.
                 */
                //sqsession_unregister('sqimap_capabilities');
            } else {
                /**
                 * stream_socket_enable_crypto() call failed. Possible issues:
                 * - broken ssl certificate (uw drops connection, error is in syslog mail facility)
                 * - some ssl error (can reproduce with STREAM_CRYPTO_METHOD_SSLv3_CLIENT, PHP E_WARNING
                 *   suppressed in stream_socket_enable_crypto() call)
                 */
                sqimap_error_box(sprintf(_("Error connecting to IMAP server: %s."), $server),
                                 '','',
                                 _("Unable to start TLS."));
                /**
                 * Bug: stream_socket_enable_crypto() does not register SSL errors in
                 * openssl_error_string() or stream notification wrapper and displays
                 * them in E_WARNING level message. It is impossible to retrieve error
                 * message without own error handler.
                 */
                exit;
            }
        } else {
            // php install does not support stream_socket_enable_crypto() function
            sqimap_error_box(sprintf(_("Error connecting to IMAP server: %s."), $server),
                             '','',
                             _("IMAP STARTTLS is enabled in SquirrelMail configuration, but used PHP version does not support functions that allow to enable encryption on open socket."));
            exit;
        }
    }
    return $imap_stream;
}

/**
 * Logs the user into the IMAP server.  If $hide is set, no error messages
 * will be displayed (if set to 1, just exits, if set to 2, returns FALSE).
 * This function returns the IMAP connection handle.
 * @param string $username user name
 * @param string $password password encrypted with onetimepad. Since 1.5.2
 *  function can use internal password functions, if parameter is set to
 *  boolean false.
 * @param string $imap_server_address address of imap server
 * @param integer $imap_port port of imap server
 * @param int $hide controls display connection errors:
 *                  0 = do not hide
 *                  1 = show no errors (just exit)
 *                  2 = show no errors (return FALSE)
 *                  3 = show no errors (return error string)
 * @param array $stream_options Stream context options, see config_local.php
 *                           for more details (OPTIONAL)
 * @return mixed The IMAP connection stream, or if the connection fails,
 *               FALSE if $hide is set to 2 or an error string if $hide
 *               is set to 3.
 */
function sqimap_login ($username, $password, $imap_server_address,
                       $imap_port, $hide, $stream_options=array()) {
    global $color, $squirrelmail_language, $onetimepad, $use_imap_tls,
           $imap_auth_mech, $sqimap_capabilities, $display_imap_login_error;

    // Note/TODO: This hack grabs the $authz argument from the session. In the short future,
    // a new argument in function sqimap_login() will be used instead.
    $authz = '';
    global $authz;
    sqgetglobalvar('authz' , $authz , SQ_SESSION);

    if(!empty($authz)) {
        /* authz plugin - specific:
         * Get proxy login parameters from authz plugin configuration. If they
         * exist, they will override the current ones.
         * This is useful if we want to use different SASL authentication mechanism
         * and/or different TLS settings for proxy logins. */
        global $authz_imap_auth_mech, $authz_use_imap_tls, $authz_imapPort_tls;
        $imap_auth_mech = !empty($authz_imap_auth_mech) ? strtolower($authz_imap_auth_mech) : $imap_auth_mech;
        $use_imap_tls = !empty($authz_use_imap_tls)? $authz_use_imap_tls : $use_imap_tls;
        $imap_port = !empty($authz_use_imap_tls)? $authz_imapPort_tls : $imap_port;

        if($imap_auth_mech == 'login' || $imap_auth_mech == 'cram-md5') {
            logout_error("Misconfigured Plugin (authz or equivalent):<br/>".
            "The LOGIN and CRAM-MD5 authentication mechanisms cannot be used when attempting proxy login.");
            exit;
        }
    }

    /* get imap login password */
    if ($password===false) {
        /* standard functions */
        $password = sqauth_read_password();
    } else {
        /* old way. $key must be extracted from cookie */
        if (!isset($onetimepad) || empty($onetimepad)) {
            sqgetglobalvar('onetimepad' , $onetimepad , SQ_SESSION );
        }
        /* Decrypt the password */
        $password = OneTimePadDecrypt($password, $onetimepad);
    }

    if (!isset($sqimap_capabilities)) {
        sqgetglobalvar('sqimap_capabilities' , $sqimap_capabilities , SQ_SESSION );
    }

    $host = $imap_server_address;
    $imap_server_address = sqimap_get_user_server($imap_server_address, $username);

    $imap_stream = sqimap_create_stream($imap_server_address,$imap_port,$use_imap_tls,$stream_options);

    if (($imap_auth_mech == 'cram-md5') OR ($imap_auth_mech == 'digest-md5')) {
        // We're using some sort of authentication OTHER than plain or login
        $tag=sqimap_session_id(false);
        if ($imap_auth_mech == 'digest-md5') {
            $query = $tag . " AUTHENTICATE DIGEST-MD5\r\n";
        } elseif ($imap_auth_mech == 'cram-md5') {
            $query = $tag . " AUTHENTICATE CRAM-MD5\r\n";
        }
        fputs($imap_stream,$query);
        $answer=sqimap_fgets($imap_stream);
        // Trim the "+ " off the front
        $response=explode(" ",$answer,3);
        if ($response[0] == '+') {
            // Got a challenge back
            $challenge=$response[1];
            if ($imap_auth_mech == 'digest-md5') {
                $reply = digest_md5_response($username,$password,$challenge,'imap',$host,$authz);
            } elseif ($imap_auth_mech == 'cram-md5') {
                $reply = cram_md5_response($username,$password,$challenge);
            }
            fputs($imap_stream,$reply);
            $read=sqimap_fgets($imap_stream);
            if ($imap_auth_mech == 'digest-md5') {
                // DIGEST-MD5 has an extra step..
                if (substr($read,0,1) == '+') { // OK so far..
                    fputs($imap_stream,"\r\n");
                    $read=sqimap_fgets($imap_stream);
                }
            }
            $results=explode(" ",$read,3);
            $response=$results[1];
            $message=$results[2];
        } else {
            // Fake the response, so the error trap at the bottom will work
            $response="BAD";
            $message='IMAP server does not appear to support the authentication method selected.';
            $message .= '  Please contact your system administrator.';
        }
    } elseif ($imap_auth_mech == 'login') {
    // Original IMAP login code
        $query = 'LOGIN "' . quoteimap($username) .  '" "' . quoteimap($password) . '"';
        $read = sqimap_run_command ($imap_stream, $query, false, $response, $message);
    } elseif ($imap_auth_mech == 'plain') {
        /***
         * SASL PLAIN, RFC 4616 (updates 2595)
         *
         * The mechanism consists of a single message, a string of [UTF-8]
         * encoded [Unicode] characters, from the client to the server.  The
         * client presents the authorization identity (identity to act as),
         * followed by a NUL (U+0000) character, followed by the authentication
         * identity (identity whose password will be used), followed by a NUL
         * (U+0000) character, followed by the clear-text password.  As with
         * other SASL mechanisms, the client does not provide an authorization
         * identity when it wishes the server to derive an identity from the
         * credentials and use that as the authorization identity.
         */
        $tag=sqimap_session_id(false);
        $sasl = (isset($sqimap_capabilities['SASL-IR']) && $sqimap_capabilities['SASL-IR']) ? true : false;
        if(!empty($authz)) {
            $auth = base64_encode("$username\0$authz\0$password");
        } else {
            $auth = base64_encode("$username\0$username\0$password");
        }
        if ($sasl) {
            // IMAP Extension for SASL Initial Client Response
            // <draft-siemborski-imap-sasl-initial-response-01b.txt>
            $query = $tag . " AUTHENTICATE PLAIN $auth\r\n";
            fputs($imap_stream, $query);
            $read = sqimap_fgets($imap_stream);
        } else {
            $query = $tag . " AUTHENTICATE PLAIN\r\n";
            fputs($imap_stream, $query);
            $read=sqimap_fgets($imap_stream);
            if (substr($read,0,1) == '+') { // OK so far..
                fputs($imap_stream, "$auth\r\n");
                $read = sqimap_fgets($imap_stream);
            }
        }
        $results=explode(" ",$read,3);
        $response=$results[1];
        $message=$results[2];

    } else {
        $response="BAD";
        $message="Internal SquirrelMail error - unknown IMAP authentication method chosen.  Please contact the developers.";
    }

    /* If the connection was not successful, lets see why */
    if ($response != 'OK') {
        if (!$hide || $hide == 3) {
//FIXME: UUURG... We don't want HTML in error messages, should also do html sanitizing of error messages elsewhere; should't assume output is destined for an HTML browser here
            if ($response != 'NO') {
                /* "BAD" and anything else gets reported here. */
                $message = sm_encode_html_special_chars($message);
                set_up_language($squirrelmail_language, true);
                if ($response == 'BAD') {
                    if ($hide == 3) return sprintf(_("Bad request: %s"), $message);
                    $string = sprintf (_("Bad request: %s")."<br />\r\n", $message);
                } else {
                    if ($hide == 3) return sprintf(_("Unknown error: %s"), $message);
                    $string = sprintf (_("Unknown error: %s") . "<br />\n", $message);
                }
                if (isset($read) && is_array($read)) {
                    $string .= '<br />' . _("Read data:") . "<br />\n";
                    foreach ($read as $line) {
                        $string .= sm_encode_html_special_chars($line) . "<br />\n";
                    }
                }
                error_box($string);
                exit;
            } else {
                /*
                 * If the user does not log in with the correct
                 * username and password it is not possible to get the
                 * correct locale from the user's preferences.
                 * Therefore, apply the same hack as on the login
                 * screen.
                 *
                 * $squirrelmail_language is set by a cookie when
                 * the user selects language and logs out
                 */

                set_up_language($squirrelmail_language, true);
                sqsession_destroy();

                /* terminate the session nicely */
                sqimap_logout($imap_stream);

                // determine what error message to use
                //
                $fail_msg = _("Unknown user or password incorrect.");
                if ($display_imap_login_error) {
                    // See if there is an error message from the server
                    // Skip any rfc5530 response code: '[something]' at the
                    // start of the message
                    if (!empty($message)
                     && $message{0} == '['
                     && ($end = strstr($message, ']'))
                     && $end != ']') {
                        $message = substr($end, 1);
                    }
                    // Remove surrounding spaces and if there
                    // is anything left, display that as the
                    // error message:
                    $message = trim($message);
                    if (strlen($message))
                        $fail_msg = _($message);
                }

                if ($hide == 3) return $fail_msg;
                logout_error($fail_msg);
                exit;
            }
        } else {
            if ($hide == 2) return FALSE;
            exit;
        }
    }

    /* Special error case:
     * Login referrals. The server returns:
     * ? OK [REFERRAL <imap url>]
     * Check RFC 2221 for details. Since we do not support login referrals yet
     * we log the user out.
     */
    if ( stristr($message, 'REFERRAL imap') === TRUE ) {
        sqimap_logout($imap_stream);
        set_up_language($squirrelmail_language, true);
        sqsession_destroy();
        logout_error( _("Your mailbox is not located at this server. Try a different server or consult your system administrator") );
        exit;
    }

    return $imap_stream;
}

/**
 * Simply logs out the IMAP session
 * @param stream $imap_stream the IMAP connection to log out.
 * @return void
 */
function sqimap_logout ($imap_stream) {
    /* Logout is not valid until the server returns 'BYE'
     * If we don't have an imap_ stream we're already logged out */
    if(isset($imap_stream) && $imap_stream)
        sqimap_run_command($imap_stream, 'LOGOUT', false, $response, $message);
}

/**
 * Retrieve the CAPABILITY string from the IMAP server.
 * If capability is set, returns only that specific capability,
 * else returns array of all capabilities.
 * @param stream $imap_stream
 * @param string $capability (since 1.3.0)
 * @param boolean $bUseCache (since 1.5.1) Controls use of capability data stored in session
 * @return mixed (string if $capability is set and found,
 *  false, if $capability is set and not found,
 *  array if $capability not set)
 */
function sqimap_capability($imap_stream, $capability='', $bUseCache=true) {
    // sqgetGlobalVar('sqimap_capabilities', $sqimap_capabilities, SQ_SESSION);

    if (!$bUseCache || ! sqgetGlobalVar('sqimap_capabilities', $sqimap_capabilities, SQ_SESSION)) {
        $read = sqimap_run_command($imap_stream, 'CAPABILITY', true, $a, $b);
        $c = explode(' ', $read[0]);
        for ($i=2; $i < count($c); $i++) {
            $cap_list = explode('=', $c[$i]);
            if (isset($cap_list[1])) {
                if(isset($sqimap_capabilities[trim($cap_list[0])]) &&
                 !is_array($sqimap_capabilities[trim($cap_list[0])])) {
                    // Remove array key that was added in 'else' block below
                    // This is to accomodate for capabilities like:
                    // SORT SORT=MODSEQ
                    unset($sqimap_capabilities[trim($cap_list[0])]);
                }
                $sqimap_capabilities[trim($cap_list[0])][] = $cap_list[1];
            } else {
                if(!isset($sqimap_capabilities[trim($cap_list[0])])) {
                    $sqimap_capabilities[trim($cap_list[0])] = TRUE;
                }
            }
        }
    }
    if ($capability) {
        if (isset($sqimap_capabilities[$capability])) {
                return $sqimap_capabilities[$capability];
        } else {
                return false;
        }
    }
    return $sqimap_capabilities;
}

/**
 * Returns the delimiter between mailboxes: INBOX/Test, or INBOX.Test
 * @param stream $imap_stream
 * @return string
 */
function sqimap_get_delimiter ($imap_stream = false) {
    global $sqimap_delimiter, $optional_delimiter;

    /* Use configured delimiter if set */
    if((!empty($optional_delimiter)) && $optional_delimiter != 'detect') {
        return $optional_delimiter;
    }

    /* Delimiter is stored in the session from redirect.  Try fetching from there first */
    if (empty($sqimap_delimiter)) {
        sqgetGlobalVar('delimiter',$sqimap_delimiter,SQ_SESSION);
    }

    /* Do some caching here */
    if (!$sqimap_delimiter) {
        if (sqimap_capability($imap_stream, 'NAMESPACE')
            /*
             * According to something that I can't find, this is supposed to work on all systems
             * OS: This won't work in Courier IMAP.
             * OS: According to rfc2342 response from NAMESPACE command is:
             * OS: * NAMESPACE (PERSONAL NAMESPACES) (OTHER_USERS NAMESPACE) (SHARED NAMESPACES)
             * OS: We want to lookup all personal NAMESPACES...
             *
             * TODO: remove this in favour of the information from sqimap_get_namespace()
             */
         && ($read = sqimap_run_command($imap_stream, 'NAMESPACE', true, $a, $b))
         && preg_match('/\* NAMESPACE +(\( *\(.+\) *\)|NIL) +(\( *\(.+\) *\)|NIL) +(\( *\(.+\) *\)|NIL)/i', $read[0], $data)
         && preg_match('/^\( *\((.*)\) *\)/', $data[1], $data2)) {
            $pn = $data2[1];
            $pna = explode(')(', $pn);
            $delnew = array();
            while (list($k, $v) = each($pna)) {
                $lst = explode('"', $v);
                if (isset($lst[3])) {
                    $delnew[$lst[1]] = $lst[3];
                } else {
                    $delnew[$lst[1]] = '';
                }
            }
            $sqimap_delimiter = array_shift($delnew);
        } else {
            fputs ($imap_stream, ". LIST \"INBOX\" \"\"\r\n");
            $read = sqimap_read_data($imap_stream, '.', true, $a, $b);
            $read = $read['.'][0];    //sqimap_read_data() now returns a tag array of response array
            $quote_position = strpos ($read[0], '"');
            $sqimap_delimiter = substr ($read[0], $quote_position+1, 1);
        }
    }
    return $sqimap_delimiter;
}

/**
 * Retrieves the namespaces from the IMAP server.
 * NAMESPACE is an IMAP extension defined in RFC 2342.
 *
 * @param stream $imap_stream
 * @return array
 */
function sqimap_get_namespace($imap_stream) {
    $read = sqimap_run_command($imap_stream, 'NAMESPACE', true, $a, $b);
    return sqimap_parse_namespace($read[0]);
}

/**
 * Parses a NAMESPACE response and returns an array with the available
 * personal, users and shared namespaces.
 *
 * @param string $input
 * @return array The returned array has the following format:
 * <pre>
 * array(
 *   'personal' => array(
 *       0 => array('prefix'=>'INBOX.','delimiter' =>'.'),
 *       1 => ...
 *    ),
 *    'users' => array(..
 *    ),
 *    'shared' => array( ..
 *    )
 * )
 * </pre>
 * Note that if a namespace is not defined in the server, then the corresponding
 * array will be empty.
 */
function sqimap_parse_namespace(&$input) {
    $ns_strings = array(1=>'personal', 2=>'users', 3=>'shared');
    $namespace = array();

    if (preg_match('/NAMESPACE (\(\(.*\)\)|NIL) (\(\(.*\)\)|NIL) (\(\(.*\)\)|NIL)/', $input, $regs)) {
        for($i=1; $i<=3; $i++) {
            if($regs[$i] == 'NIL') {
                $namespace[$ns_strings[$i]] = array();
            } else {
                // Pop-out the first ( and last ) for easier parsing
                $ns = substr($regs[$i], 1, sizeof($regs[$i])-2);
                if($c = preg_match_all('/\((?:(.*?)\s*?)\)/', $ns, $regs2)) {
                    $namespace[$ns_strings[$i]] = array();
                    for($j=0; $j<sizeof($regs2[1]); $j++) {
                        preg_match('/"(.*)"\s+("(.*)"|NIL)/', $regs2[1][$j], $regs3);
                        $namespace[$ns_strings[$i]][$j]['prefix'] = $regs3[1];
                        if($regs3[2] == 'NIL') {
                            $namespace[$ns_strings[$i]][$j]['delimiter'] = null;
                        } else {
                            // $regs[3] is $regs[2] without the quotes
                            $namespace[$ns_strings[$i]][$j]['delimiter'] = $regs3[3];
                        }
                        unset($regs3);
                    }
                }
                unset($ns);
            }
        }
    }
    return($namespace);
}

/**
 * This encodes a mailbox name for use in IMAP commands.
 * @param string $what the mailbox to encode
 * @return string the encoded mailbox string
 * @since 1.5.0
 */
function sqimap_encode_mailbox_name($what)
{
    if (preg_match('/["\\\r\n]/', $what))
        return '{' . strlen($what) . "}\r\n" . $what;        /* 4.3 literal form */
    return '"' . $what . '"';        /* 4.3 quoted string form */
}

/**
 * Gets the number of messages in the current mailbox.
 *
 * OBSOLETE use sqimap_status_messages instead.
 * @param stream $imap_stream imap stream
 * @param string $mailbox
 * @deprecated
 */
function sqimap_get_num_messages ($imap_stream, $mailbox) {
    $aStatus = sqimap_status_messages($imap_stream,$mailbox,array('MESSAGES'));
    return $aStatus['MESSAGES'];
}

/**
 * OBSOLETE FUNCTION should be removed after mailbox_display,
 * printMessage function is adapted
 * $addr_ar = array(), $group = '' and $host='' arguments are used in 1.4.0
 * @param string $address
 * @param integer $max
 * @since 1.4.0
 * @deprecated See Rfc822Address.php
 */
function parseAddress($address, $max=0) {
    $aAddress = parseRFC822Address($address,array('limit'=> $max));
    /*
     * Because the expected format of the array element is changed we adapt it now.
     * This also implies that this function is obsolete and should be removed after the
     * rest of the source is adapted. See Rfc822Address.php for the new function.
     */
     array_walk($aAddress, '_adaptAddress');
     return $aAddress;
}

/**
 * OBSOLETE FUNCTION should be removed after mailbox_display,
 * printMessage function is adapted
 *
 * callback function used for formating of addresses array in
 * parseAddress() function
 * @param array $aAddr
 * @param integer $k array key
 * @since 1.5.1
 * @deprecated
 */
function _adaptAddress(&$aAddr,$k) {
   $sPersonal = (isset($aAddr[SQM_ADDR_PERSONAL]) && $aAddr[SQM_ADDR_PERSONAL]) ?
       $aAddr[SQM_ADDR_PERSONAL] : '';
   $sEmail = ($aAddr[SQM_ADDR_HOST]) ?
       $aAddr[SQM_ADDR_MAILBOX] . '@'.$aAddr[SQM_ADDR_HOST] :
       $aAddr[SQM_ADDR_MAILBOX];
   $aAddr = array($sEmail,$sPersonal);
}

/**
 * Returns the number of unseen messages in this folder.
 * obsoleted by sqimap_status_messages !
 * Arguments differ in 1.0.x
 * @param stream $imap_stream
 * @param string $mailbox
 * @return integer
 * @deprecated
 */
function sqimap_unseen_messages ($imap_stream, $mailbox) {
    $aStatus = sqimap_status_messages($imap_stream,$mailbox,array('UNSEEN'));
    return $aStatus['UNSEEN'];
}

/**
 * Returns the status items of a mailbox.
 * Default it returns MESSAGES,UNSEEN and RECENT
 * Supported status items are MESSAGES, UNSEEN, RECENT (since 1.4.0),
 * UIDNEXT (since 1.5.1) and UIDVALIDITY (since 1.5.1)
 * @param stream $imap_stream imap stream
 * @param string $mailbox mail folder
 * @param array $aStatusItems status items
 * @return array
 * @since 1.3.2
 */
function sqimap_status_messages ($imap_stream, $mailbox,
                       $aStatusItems = array('MESSAGES','UNSEEN','RECENT')) {

    $aStatusItems = implode(' ',$aStatusItems);
    $read_ary = sqimap_run_command ($imap_stream, 'STATUS ' . sqimap_encode_mailbox_name($mailbox) .
                                    " ($aStatusItems)", false, $result, $message);
    $i = 0;
    $messages = $unseen = $recent = $uidnext = $uidvalidity = false;
    $regs = array(false,false);
    while (isset($read_ary[$i])) {
        if (preg_match('/UNSEEN\s+([0-9]+)/i', $read_ary[$i], $regs)) {
            $unseen = $regs[1];
        }
        if (preg_match('/MESSAGES\s+([0-9]+)/i', $read_ary[$i], $regs)) {
            $messages = $regs[1];
        }
        if (preg_match('/RECENT\s+([0-9]+)/i', $read_ary[$i], $regs)) {
            $recent = $regs[1];
        }
        if (preg_match('/UIDNEXT\s+([0-9]+)/i', $read_ary[$i], $regs)) {
            $uidnext = $regs[1];
        }
        if (preg_match('/UIDVALIDITY\s+([0-9]+)/i', $read_ary[$i], $regs)) {
            $uidvalidity = $regs[1];
        }
        $i++;
    }

    $status=array('MESSAGES' => $messages,
                 'UNSEEN'=>$unseen,
                 'RECENT' => $recent,
                 'UIDNEXT' => $uidnext,
                 'UIDVALIDITY' => $uidvalidity);

    if (!empty($messages)) { $hook_status['MESSAGES']=$messages; }
    if (!empty($unseen)) { $hook_status['UNSEEN']=$unseen; }
    if (!empty($recent)) { $hook_status['RECENT']=$recent; }
    if (!empty($hook_status)) {
         $hook_status['MAILBOX']=$mailbox;
         $hook_status['CALLER']='sqimap_status_messages';
         do_hook('folder_status', $hook_status);
    }
    return $status;
}


/**
 * Saves a message to a given folder -- used for saving sent messages
 * @param stream $imap_stream
 * @param string $sent_folder
 * @param $length
 * @return string $sid
 */
function sqimap_append ($imap_stream, $sMailbox, $length) {
    $sid = sqimap_session_id();
    $query = $sid . ' APPEND ' . sqimap_encode_mailbox_name($sMailbox) . " (\\Seen) {".$length."}";
    fputs ($imap_stream, "$query\r\n");
    $tmp = fgets ($imap_stream, 1024);
    sqimap_append_checkresponse($tmp, $sMailbox,$sid, $query);
    return $sid;
}

/**
 * @param stream imap_stream
 * @param string $folder (since 1.3.2)
 */
function sqimap_append_done ($imap_stream, $sMailbox='') {
    fputs ($imap_stream, "\r\n");
    $tmp = fgets ($imap_stream, 1024);
    while (!sqimap_append_checkresponse($tmp, $sMailbox)) {
        $tmp = fgets ($imap_stream, 1024);
    }
}

/**
 * Displays error messages, if there are errors in responses to
 * commands issues by sqimap_append() and sqimap_append_done() functions.
 * @param string $response
 * @param string $sMailbox
 * @return bool $bDone
 * @since 1.5.1 and 1.4.5
 */
function sqimap_append_checkresponse($response, $sMailbox, $sid='', $query='') {
    // static vars to keep them available when sqimap_append_done calls this function.
    static $imapquery, $imapsid;

    $bDone = false;

    if ($query) {
        $imapquery = $query;
    }
    if ($sid) {
        $imapsid = $sid;
    }
    if ($response{0} == '+') {
        // continuation request triggerd by sqimap_append()
        $bDone = true;
    } else {
        $i = strpos($response, ' ');
        $sRsp = substr($response,0,$i);
        $sMsg = substr($response,$i+1);
        $aExtra = array('MAILBOX' => $sMailbox);
        switch ($sRsp) {
            case '*': //untagged response
                $i = strpos($sMsg, ' ');
                $sRsp = strtoupper(substr($sMsg,0,$i));
                $sMsg = substr($sMsg,$i+1);
                if ($sRsp == 'NO' || $sRsp == 'BAD') {
                    // for the moment disabled. Enable after 1.5.1 release.
                    // Notices could give valueable information about the mailbox
                    // Update: seems this was forgotten, but now it is finally enabled
                    sqm_trigger_imap_error('SQM_IMAP_APPEND_NOTICE',$imapquery,$sRsp,$sMsg);
                }
                $bDone = false;
            case $imapsid:
                // A001 OK message
                // $imapsid<space>$sRsp<space>$sMsg
                $bDone = true;
                $i = strpos($sMsg, ' ');
                $sRsp = strtoupper(substr($sMsg,0,$i));
                $sMsg = substr($sMsg,$i+1);
                switch ($sRsp) {
                    case 'NO':
                        if (preg_match("/(.*)(quota)(.*)$/i", $sMsg, $aMatch)) {
                            sqm_trigger_imap_error('SQM_IMAP_APPEND_QUOTA_ERROR',$imapquery,$sRsp,$sMsg,$aExtra);
                        } else {
                            sqm_trigger_imap_error('SQM_IMAP_APPEND_ERROR',$imapquery,$sRsp,$sMsg,$aExtra);
                        }
                        break;
                    case 'BAD':
                        sqm_trigger_imap_error('SQM_IMAP_ERROR',$imapquery,$sRsp,$sMsg,$aExtra);
                        break;
                    case 'BYE':
                        sqm_trigger_imap_error('SQM_IMAP_BYE',$imapquery,$sRsp,$sMsg,$aExtra);
                        break;
                    case 'OK':
                        break;
                    default:
                        break;
                }
                break;
            default:
                // should be false because of the unexpected response but i'm not sure if
                // that will cause an endless loop in sqimap_append_done
                $bDone = true;
        }
    }
    return $bDone;
}

/**
 * Allows mapping of IMAP server address with custom function
 * see map_yp_alias()
 * @param string $imap_server imap server address or mapping
 * @param string $username
 * @return string
 * @since 1.3.0
 */
function sqimap_get_user_server ($imap_server, $username) {
   if (substr($imap_server, 0, 4) != "map:") {
       return $imap_server;
   }
   $function = substr($imap_server, 4);
   return $function($username);
}

/**
 * This is an example that gets IMAP servers from yellowpages (NIS).
 * you can simple put map:map_yp_alias in your $imap_server_address
 * in config.php use your own function instead map_yp_alias to map your
 * LDAP whatever way to find the users IMAP server.
 *
 * Requires access to external ypmatch program
 * FIXME: it can be implemented in php yp extension or pecl (since php 5.1.0)
 * @param string $username
 * @return string
 * @since 1.3.0
 */
function map_yp_alias($username) {
   $safe_username = escapeshellarg($username);
   $yp = `ypmatch $safe_username aliases`;
   return chop(substr($yp, strlen($username)+1));
}
