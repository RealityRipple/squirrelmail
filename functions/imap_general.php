<?php

/**
 * imap_general.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements all functions that do general imap functions.
 *
 * $Id$
 * @package squirrelmail
 */

/** Includes.. */
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/auth.php');


global $sqimap_session_id;
$sqimap_session_id = 1;

/**
 * Generates a new session ID by incrementing the last one used;
 * this ensures that each command has a unique ID.
 * @param bool unique_id
 * @return string IMAP session id of the form 'A000'.
 */
function sqimap_session_id($unique_id = false) {
    global $data_dir, $username, $sqimap_session_id;
    if (!$unique_id) {
        return( sprintf("A%03d", $sqimap_session_id++) );
    } else {
        return( sprintf("A%03d", $sqimap_session_id++) . ' UID' );
    }
}

/**
 * Both send a command and accept the result from the command.
 * This is to allow proper session number handling.
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
    } else {
        global $squirrelmail_language, $color;
        set_up_language($squirrelmail_language);
        require_once(SM_PATH . 'functions/display_messages.php');
        $string = "<b><font color=$color[2]>\n" .
                _("ERROR : No available imapstream.") .
                "</b></font>\n";
        error_box($string,$color);
        return false;
    }
}

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
        if (empty($read)) {	//Imap server dropped its connection
            $response = '';
            $message = '';
            return false;
        }
        /* retrieve the response and the message */
        $response = $response[$tag];
        $message  = $message[$tag];
    
        if (!empty($read[$tag])) {
            return $read[$tag][0];
        } else {
            return $read[$tag];
        }
    } else {
        global $squirrelmail_language, $color;
        set_up_language($squirrelmail_language);
        require_once(SM_PATH . 'functions/display_messages.php');
        $string = "<b><font color=$color[2]>\n" .
                _("ERROR : No available imapstream.") .
                "</b></font>\n";
        error_box($string,$color);
        return false;
    }    
}

function sqimap_prepare_pipelined_query($new_query,&$tag,&$aQuery,$unique_id) {
    $sid = sqimap_session_id($unique_id);
    $tag_uid_a = explode(' ',trim($sid));
    $tag = $tag_uid_a[0];
    $query = $sid . ' '.$new_query."\r\n";
    $aQuery[$tag] = $query;
}

function sqimap_run_pipelined_command ($imap_stream, $aQueryList, $handle_errors, 
                       &$aServerResponse, &$aServerMessage, $unique_id = false,
                       $filter=false,$outputstream=false,$no_return=false) {    
    $aResponse = false;

    /* 
       Do not fire all calls at once to the imap-server but split the calls up
       in portions of $iChunkSize. If we do not do that I think we misbehave as 
       IMAP client or should handle BYE calls if the IMAP-server drops the
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
 * Custom fgets function: gets a line from the IMAP-server,
 * no matter how big it may be.
 * @param stream imap_stream the stream to read from
 * @return string a line
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
        if (!$sRead) {
            $results = false;
            break;
        }
        if ($sReadRem) {
            $sRead = $sReadRem . $sRead;
            $sReadRem = '';
        }
        if (substr($sRead,-1) !== "\n") {  
            $i = strrpos($sRead,"\n");
            if ($i !== false && $iRetrieved<$iSize) {
                ++$i;
                $sReadRem = substr($sRead,$i);
                $sRead = substr($sRead,0,$i);
            } else if ($iLength && $iRetrieved<$iSize) { // linelength > received buffer
                $sReadRem = $sRead;
                $sRead = '';
            }
        } 
        if ($filter && $sRead) {
           $filter($sRead);
        }
        if ($outputstream && $sRead) {
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
 * @deprecated use sqimap_run_command or sqimap_run_command_list instead
 */
function sqimap_read_data_list($imap_stream, $tag, $handle_errors, 
          &$response, &$message, $query = '') {
    global $color, $squirrelmail_language;
    set_up_language($squirrelmail_language);
    require_once(SM_PATH . 'functions/display_messages.php');
    $string = "<b><font color=$color[2]>\n" .
        _("ERROR : Bad function call.") .
        "</b><br>\n" .
        _("Reason:") . ' '.
          'There is a plugin installed which make use of the  <br>' .
          'SquirrelMail internal function sqimap_read_data_list.<br>'.
          'Please adapt the installed plugin and let it use<br>'.
          'sqimap_run_command or sqimap_run_command_list instead<br><br>'.
          'The following query was issued:<br>'.
           htmlspecialchars($query) . '<br>' . "</font><br>\n";
    error_box($string,$color);
    echo '</body></html>';        
    exit; 
}

/**
 * Function to display an error related to an IMAP-query.
 * @param string title the caption of the error box
 * @param string query the query that went wrong
 * @param string message_title optional message title
 * @param string message optional error message
 * @param string $link an optional link to try again
 * @return void
 */
function sqimap_error_box($title, $query = '', $message_title = '', $message = '', $link = '')
{
    global $color, $squirrelmail_language;

    set_up_language($squirrelmail_language);
    require_once(SM_PATH . 'functions/display_messages.php');
    $string = "<font color=$color[2]><b>\n" . $title . "</b><br>\n";
    $cmd = explode(' ',$query);
    $cmd= strtolower($cmd[0]);

    if ($query != '' &&  $cmd != 'login')
        $string .= _("Query:") . ' ' . htmlspecialchars($query) . '<br>';
    if ($message_title != '')
        $string .= $message_title;
    if ($message != '')
        $string .= htmlspecialchars($message);
    $string .= "</font><br>\n";
    if ($link != '')
        $string .= $link;
    error_box($string,$color);
}

/**
 * Reads the output from the IMAP stream.  If handle_errors is set to true,
 * this will also handle all errors that are received.  If it is not set,
 * the errors will be sent back through $response and $message.
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
    $read = sqimap_fgets($imap_stream);
    $i = $k = 0;
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
                 break 3; /* switch switch while */
            }
            break;
          } // end case $tag{0}

          case '*':
          {
            if (preg_match('/^\*\s\d+\sFETCH/',$read)) {
                /* check for literal */
                $s = substr($read,-3);
                $fetch_data = array();
                do { /* outer loop, continue until next untagged fetch
                        or tagged reponse */
                    do { /* innerloop for fetching literals. with this loop
                            we prohibid that literal responses appear in the
                            outer loop so we can trust the untagged and
                            tagged info provided by $read */
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
                            $fetch_data[] = $read;
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
                    } while ($s === "}\r\n");
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
        unset($data);
        if ($handle_errors) {
            sqimap_error_box(_("ERROR : Connection dropped by imap-server."), $query);
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
            sqimap_error_box(_("ERROR : Could not complete request."), $query, _("Reason Given: "), $message[$tag]);
            echo '</body></html>';
            exit;
        }
        break;
    case 'BAD': 
        sqimap_error_box(_("ERROR : Bad or malformed request."), $query, _("Server responded: "), $message[$tag]);
        echo '</body></html>';        
        exit; 
    case 'BYE': 
        sqimap_error_box(_("ERROR : Imap server closed the connection."), $query, _("Server responded: "), $message[$tag]);
        echo '</body></html>';        
        exit;
    default: 
        sqimap_error_box(_("ERROR : Unknown imap response."), $query, _("Server responded: "), $message[$tag]);
       /* the error is displayed but because we don't know the reponse we
          return the result anyway */
       return $aResponse;    
       break;
    }
}

function sqimap_read_data ($imap_stream, $tag_uid, $handle_errors, 
                           &$response, &$message, $query = '',
                           $filter=false,$outputstream=false,$no_return=false) {

    $tag_uid_a = explode(' ',trim($tag_uid));
    $tag = $tag_uid_a[0];

    $res = sqimap_retrieve_imap_response($imap_stream, $tag, $handle_errors, 
              $response, $message, $query,$filter,$outputstream,$no_return); 
    /* sqimap_read_data should be called for one response
       but since it just calls sqimap_retrieve_imap_response which 
       handles multiple responses we need to check for that
       and merge the $res array IF they are seperated and 
       IF it was a FETCH response. */
  
//    if (isset($res[1]) && is_array($res[1]) && isset($res[1][0]) 
//        && preg_match('/^\* \d+ FETCH/', $res[1][0])) {
//        $result = array();
//        foreach($res as $index=>$value) {
//            $result = array_merge($result, $res["$index"]);
//        }
//    }
    if (isset($result)) {
        return $result[$tag];
    }
    else {
        return $res;
    }
}

/**
 * Connects to the IMAP server and returns a resource identifier for use with
 * the other SquirrelMail IMAP functions.  Does NOT login!
 * @param string server hostname of IMAP server
 * @param int port port number to connect to
 * @param bool tls whether to use TLS when connecting.
 * @return imap-stream resource identifier
 */
function sqimap_create_stream($server,$port,$tls=false) {
    global $username, $use_imap_tls;

    if ($use_imap_tls == true) {
        if ((check_php_version(4,3)) and (extension_loaded('openssl'))) {
            /* Use TLS by prefixing "tls://" to the hostname */
            $server = 'tls://' . $imap_server_address;
        } else {
            require_once(SM_PATH . 'functions/display_messages.php');
            $string = "Unable to connect to IMAP server!<br>TLS is enabled, but this " .
              "version of PHP does not support TLS sockets, or is missing the openssl " .
              "extension.<br><br>Please contact your system administrator.";
            logout_error($string,$color);
        }
    }

    $imap_stream = fsockopen($server, $port, $error_number, $error_string, 15);

    /* Do some error correction */
    if (!$imap_stream) {
        set_up_language($squirrelmail_language, true);
        require_once(SM_PATH . 'functions/display_messages.php');
        $string = sprintf (_("Error connecting to IMAP server: %s.") .
           "<br>\r\n", $server) .
           "$error_number : $error_string<br>\r\n";
        logout_error($string,$color);
        exit;
    }
    $server_info = fgets ($imap_stream, 1024);
    return $imap_stream;
}

/**
 * Logs the user into the imap server.  If $hide is set, no error messages
 * will be displayed.  This function returns the imap connection handle.
 */
function sqimap_login ($username, $password, $imap_server_address, $imap_port, $hide) {
    global $color, $squirrelmail_language, $onetimepad, $use_imap_tls,
           $imap_auth_mech, $sqimap_capabilities;

    if (!isset($onetimepad) || empty($onetimepad)) {
        sqgetglobalvar('onetimepad' , $onetimepad , SQ_SESSION );
    }
    if (!isset($sqimap_capabilities)) {
        sqgetglobalvar('sqimap_capabilities' , $capability , SQ_SESSION );
    }

    $host = $imap_server_address;
    $imap_server_address = sqimap_get_user_server($imap_server_address, $username);

    $imap_stream = sqimap_create_stream($imap_server_address,$imap_port,$use_imap_tls);

    /* Decrypt the password */
    $password = OneTimePadDecrypt($password, $onetimepad);

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
                $reply = digest_md5_response($username,$password,$challenge,'imap',$host);
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
         * SASL PLAIN
         *
         *  RFC 2595 Chapter 6
         *
         *  The mechanism consists of a single message from the client to the
         *  server.  The client sends the authorization identity (identity to
         *  login as), followed by a US-ASCII NUL character, followed by the
         *  authentication identity (identity whose password will be used),
         *  followed by a US-ASCII NUL character, followed by the clear-text
         *  password.  The client may leave the authorization identity empty to
         *  indicate that it is the same as the authentication identity.
         *
         **/
        $tag=sqimap_session_id(false);
        $sasl = (isset($capability['SASL-IR']) && $capability['SASL-IR']) ? true : false;
        $auth = base64_encode("$username\0$username\0$password");
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
        if (!$hide) {
            if ($response != 'NO') {
                /* "BAD" and anything else gets reported here. */
                $message = htmlspecialchars($message);
                set_up_language($squirrelmail_language, true);
                require_once(SM_PATH . 'functions/display_messages.php');
                if ($response == 'BAD') {
                    $string = sprintf (_("Bad request: %s")."<br>\r\n", $message);
                } else {
                    $string = sprintf (_("Unknown error: %s") . "<br>\n", $message);
                }
                if (isset($read) && is_array($read)) {
                    $string .= '<br>' . _("Read data:") . "<br>\n";
                    foreach ($read as $line) {
                        $string .= htmlspecialchars($line) . "<br>\n";
                    }
                }
                error_box($string,$color);
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
                include_once(SM_PATH . 'functions/display_messages.php' );
                sqsession_destroy();
                logout_error( _("Unknown user or password incorrect.") );
                exit;
            }
        } else {
            exit;
        }
    }
    return $imap_stream;
}

/**
 * Simply logs out the IMAP session
 * @param stream imap_stream the IMAP connection to log out.
 * @return void
 */
function sqimap_logout ($imap_stream) {
    /* Logout is not valid until the server returns 'BYE'
     * If we don't have an imap_ stream we're already logged out */
    if(isset($imap_stream) && $imap_stream)
        sqimap_run_command($imap_stream, 'LOGOUT', false, $response, $message);
}

/**
 * Retreive the CAPABILITY string from the IMAP server.
 * If capability is set, returns only that specific capability,
 * else returns array of all capabilities.
 */
function sqimap_capability($imap_stream, $capability='') {
    global $sqimap_capabilities;
    if (!is_array($sqimap_capabilities)) {
        $read = sqimap_run_command($imap_stream, 'CAPABILITY', true, $a, $b);

        $c = explode(' ', $read[0]);
        for ($i=2; $i < count($c); $i++) {
            $cap_list = explode('=', $c[$i]);
            if (isset($cap_list[1])) {
                $sqimap_capabilities[$cap_list[0]] = $cap_list[1];
            } else {
                $sqimap_capabilities[$cap_list[0]] = TRUE;
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
 * Returns the delimeter between mailboxes: INBOX/Test, or INBOX.Test
 */
function sqimap_get_delimiter ($imap_stream = false) {
    global $sqimap_delimiter, $optional_delimiter;

    /* Use configured delimiter if set */
    if((!empty($optional_delimiter)) && $optional_delimiter != 'detect') {
        return $optional_delimiter;
    }

    /* Do some caching here */
    if (!$sqimap_delimiter) {
        if (sqimap_capability($imap_stream, 'NAMESPACE')) {
            /*
             * According to something that I can't find, this is supposed to work on all systems
             * OS: This won't work in Courier IMAP.
             * OS: According to rfc2342 response from NAMESPACE command is:
             * OS: * NAMESPACE (PERSONAL NAMESPACES) (OTHER_USERS NAMESPACE) (SHARED NAMESPACES)
             * OS: We want to lookup all personal NAMESPACES...
             */
            $read = sqimap_run_command($imap_stream, 'NAMESPACE', true, $a, $b);
            if (eregi('\\* NAMESPACE +(\\( *\\(.+\\) *\\)|NIL) +(\\( *\\(.+\\) *\\)|NIL) +(\\( *\\(.+\\) *\\)|NIL)', $read[0], $data)) {
                if (eregi('^\\( *\\((.*)\\) *\\)', $data[1], $data2)) {
                    $pn = $data2[1];
                }
                $pna = explode(')(', $pn);
                while (list($k, $v) = each($pna)) {
                    $lst = explode('"', $v);
                    if (isset($lst[3])) {
                        $pn[$lst[1]] = $lst[3];
                    } else {
                        $pn[$lst[1]] = '';
                    }
                }
            }
            $sqimap_delimiter = $pn[0];
        } else {
            fputs ($imap_stream, ". LIST \"INBOX\" \"\"\r\n");
            $read = sqimap_read_data($imap_stream, '.', true, $a, $b);
            $read = $read['.'][0];	//sqimap_read_data() now returns a tag array of response array
            $quote_position = strpos ($read[0], '"');
            $sqimap_delimiter = substr ($read[0], $quote_position+1, 1);
        }
    }
    return $sqimap_delimiter;
}

/**
 * This encodes a mailbox name for use in IMAP commands.
 * @param string what the mailbox to encode
 * @return string the encoded mailbox string
 */
function sqimap_encode_mailbox_name($what)
{
	if (ereg("[\"\\\r\n]", $what))
		return '{' . strlen($what) . "}\r\n" . $what;	/* 4.3 literal form */
	return '"' . $what . '"';	/* 4.3 quoted string form */
}


/**
 * Gets the number of messages in the current mailbox.
 */
function sqimap_get_num_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, 'EXAMINE ' . sqimap_encode_mailbox_name($mailbox), false, $result, $message);
    for ($i = 0; $i < count($read_ary); $i++) {
        if (ereg("[^ ]+ +([^ ]+) +EXISTS", $read_ary[$i], $regs)) {
            return $regs[1];
        }
    }
    return false; //"BUG! Couldn't get number of messages in $mailbox!";
}

function parseAddress($address, $max=0) {
    $aTokens = array();
    $aAddress = array();
    $iCnt = strlen($address);
    $aSpecials = array('(' ,'<' ,',' ,';' ,':');
    $aReplace =  array(' (',' <',' ,',' ;',' :');
    $address = str_replace($aSpecials,$aReplace,$address);
    $i = $iAddrFound = $bGroup = 0;
    while ($i < $iCnt) {
        $cChar = $address{$i};
        switch($cChar)
        {
        case '<':
            $iEnd = strpos($address,'>',$i+1);
            if (!$iEnd) {
               $sToken = substr($address,$i);
               $i = $iCnt;
            } else {
               $sToken = substr($address,$i,$iEnd - $i +1);
               $i = $iEnd;
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            $aTokens[] = $sToken;
            break;
        case '"':
            $iEnd = strpos($address,$cChar,$i+1);
            if ($iEnd) {
               // skip escaped quotes
               $prev_char = $address{$iEnd-1};
               while ($prev_char === '\\' && substr($address,$iEnd-2,2) !== '\\\\') {
                   $iEnd = strpos($address,$cChar,$iEnd+1);
                   if ($iEnd) {
                      $prev_char = $address{$iEnd-1};
                   } else {
                      $prev_char = false;
                   }
               }
            }
            if (!$iEnd) {
                $sToken = substr($address,$i);
                $i = $iCnt;
            } else {
                // also remove the surrounding quotes
                $sToken = substr($address,$i+1,$iEnd - $i -1);
                $i = $iEnd;
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            if ($sToken) $aTokens[] = $sToken;
            break;
        case '(':
            $iEnd = strpos($address,')',$i);
            if (!$iEnd) {
                $sToken = substr($address,$i);
                $i = $iCnt;
            } else {
                $sToken = substr($address,$i,$iEnd - $i + 1);
                $i = $iEnd;
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            $aTokens[] = $sToken;
            break;
        case ',':
            ++$iAddrFound;
        case ';':
            if (!$bGroup) {
               ++$iAddrFound;
            } else {
               $bGroup = false;
            }
            if ($max && $max == $iAddrFound) {
               break 2;
            } else {
               $aTokens[] = $cChar;
               break;
            }
        case ':':
           $bGroup = true;
        case ' ':
            $aTokens[] = $cChar;
            break;
        default:
            $iEnd = strpos($address,' ',$i+1);
            if ($iEnd) {
                $sToken = trim(substr($address,$i,$iEnd - $i));
                $i = $iEnd-1;
            } else {
                $sToken = trim(substr($address,$i));
                $i = $iCnt;
            }
            if ($sToken) $aTokens[] = $sToken;
        }
        ++$i;
    }
    $sPersonal = $sEmail = $sComment = $sGroup = '';
    $aStack = $aComment = array();
    foreach ($aTokens as $sToken) {
        if ($max && $max == count($aAddress)) {
            return $aAddress;
        }
        $cChar = $sToken{0};
        switch ($cChar)
        {
          case '=':
          case '"':
          case ' ':
            $aStack[] = $sToken; 
            break;
          case '(':
            $aComment[] = substr($sToken,1,-1);
            break;
          case ';':
            if ($sGroup) {
                $sEmail = trim(implode(' ',$aStack));
                $aAddress[] = array($sGroup,$sEmail);
                $aStack = $aComment = array();
                $sGroup = '';
                break;
            }
          case ',':
            if (!$sEmail) {
                while (count($aStack) && !$sEmail) {
                    $sEmail = trim(array_pop($aStack));
                }
            }
            if (count($aStack)) {
                $sPersonal = trim(implode('',$aStack));
            } else { 
                $sPersonal = '';
            }
            if (!$sPersonal && count($aComment)) {
                $sComment = implode(' ',$aComment);
                $sPersonal .= $sComment;
            }
            $aAddress[] = array($sEmail,$sPersonal);
            $sPersonal = $sComment = $sEmail = '';
            $aStack = $aComment = array();
            break;
          case ':': 
            $sGroup = implode(' ',$aStack); break;
            $aStack = array();
            break;
          case '<':
            $sEmail = trim(substr($sToken,1,-1));
            break;
          case '>':
            /* skip */
            break; 
          default: $aStack[] = $sToken; break;
        }
    }
    /* now do the action again for the last address */
    if (!$sEmail) {
        while (count($aStack) && !$sEmail) {
            $sEmail = trim(array_pop($aStack));
        }
    }
    if (count($aStack)) {
        $sPersonal = trim(implode('',$aStack));
    } else {
        $sPersonal = '';
    }
    if (!$sPersonal && count($aComment)) {
        $sComment = implode(' ',$aComment);
        $sPersonal .= $sComment;
    }
    $aAddress[] = array($sEmail,$sPersonal);
    return $aAddress;
} 


/**
 * Returns the number of unseen messages in this folder.
 */
function sqimap_unseen_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, 'STATUS ' . sqimap_encode_mailbox_name($mailbox) . ' (UNSEEN)', false, $result, $message);
    $i = 0;
    $regs = array(false, false);
    while (isset($read_ary[$i])) {
        if (ereg("UNSEEN ([0-9]+)", $read_ary[$i], $regs)) {
            break;
        }
        $i++;
    }
    return $regs[1];
}

/**
 * Returns the number of total/unseen/recent messages in this folder
 */
function sqimap_status_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, 'STATUS ' . sqimap_encode_mailbox_name($mailbox) . ' (MESSAGES UNSEEN RECENT)', false, $result, $message);
    $i = 0;
    $messages = $unseen = $recent = false;
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
        $i++;
    }
    return array('MESSAGES' => $messages, 'UNSEEN'=>$unseen, 'RECENT' => $recent);
}


/**
 * Saves a message to a given folder -- used for saving sent messages
 */
function sqimap_append ($imap_stream, $sent_folder, $length) {
    fputs ($imap_stream, sqimap_session_id() . ' APPEND ' . sqimap_encode_mailbox_name($sent_folder) . " (\\Seen) \{$length}\r\n");
    $tmp = fgets ($imap_stream, 1024);
}

function sqimap_append_done ($imap_stream, $folder='') {
    global $squirrelmail_language, $color;
    fputs ($imap_stream, "\r\n");
    $tmp = fgets ($imap_stream, 1024);
    if (preg_match("/(.*)(BAD|NO)(.*)$/", $tmp, $regs)) {
        set_up_language($squirrelmail_language);
        require_once(SM_PATH . 'functions/display_messages.php');
        $reason = $regs[3];
        if ($regs[2] == 'NO') {
           $string = "<b><font color=$color[2]>\n" .
                  _("ERROR : Could not append message to") ." $folder." .
                  "</b><br>\n" .
                  _("Server responded: ") .
                  $reason . "<br>\n";
           if (preg_match("/(.*)(quota)(.*)$/i", $reason, $regs)) {
              $string .= _("Solution: ") . 
            _("Remove unneccessary messages from your folder and start with your Trash folder.") 
              ."<br>\n";
           }
           $string .= "</font>\n";
           error_box($string,$color);
        } else {
           $string = "<b><font color=$color[2]>\n" .
                  _("ERROR : Bad or malformed request.") .
                  "</b><br>\n" .
                  _("Server responded: ") .
                  $tmp . "</font><br>\n";
           error_box($string,$color);
           exit;
        }
    }
}

function sqimap_get_user_server ($imap_server, $username) {
   if (substr($imap_server, 0, 4) != "map:") {
       return $imap_server;
   }
   $function = substr($imap_server, 4);
   return $function($username);
}

/**
 * This is an example that gets imapservers from yellowpages (NIS).
 * you can simple put map:map_yp_alias in your $imap_server_address 
 * in config.php use your own function instead map_yp_alias to map your
 * LDAP whatever way to find the users imapserver.
 */
function map_yp_alias($username) {
   $yp = `ypmatch $username aliases`;
   return chop(substr($yp, strlen($username)+1));
} 

?>
