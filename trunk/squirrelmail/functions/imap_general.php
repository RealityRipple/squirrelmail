<?php

/**
 * imap_general.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements all functions that do general imap functions.
 *
 * $Id$
 */

require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/auth.php');


global $sqimap_session_id;
$sqimap_session_id = 1;

/* Sets an unique session id in order to avoid simultanous sessions crash. */
function sqimap_session_id($unique_id = false) {
    global $data_dir, $username, $sqimap_session_id;
    if (!$unique_id) {
        return( sprintf("A%03d", $sqimap_session_id++) );
    } else {
        return( sprintf("A%03d", $sqimap_session_id++) . ' UID' );
    }
}

/*
 * Both send a command and accept the result from the command.
 * This is to allow proper session number handling.
 */
function sqimap_run_command_list ($imap_stream, $query, $handle_errors, &$response, &$message, $unique_id = false) {
    if ($imap_stream) {
        $sid = sqimap_session_id($unique_id);
        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
        $read = sqimap_read_data_list ($imap_stream, $sid, $handle_errors, $response, $message, $query );
        return $read;
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

function sqimap_run_command ($imap_stream, $query, $handle_errors, &$response, &$message, $unique_id = false) {
    if ($imap_stream) {
        $sid = sqimap_session_id($unique_id);
        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
        $read = sqimap_read_data ($imap_stream, $sid, $handle_errors, $response, $message, $query);
        return $read;
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


/* 
 * custom fgets function. gets a line from IMAP
 * no matter how big it may be
 */

function sqimap_fgets($imap_stream) {
    $read = '';
    $buffer = 4096;
    $results = '';
    $offset = 0;
    $i=0;
    while (strpos($results, "\r\n", $offset) === false) {
        if (!($read = fgets($imap_stream, $buffer))) {
            break;
        }
        if ( $results != '' ) {
            $offset = strlen($results) - 1;
        }
        $results .= $read;
    }
    return $results;
}

/*
 * Reads the output from the IMAP stream.  If handle_errors is set to true,
 * this will also handle all errors that are received.  If it is not set,
 * the errors will be sent back through $response and $message
 */

function sqimap_read_data_list ($imap_stream, $tag_uid, $handle_errors, &$response, &$message, $query = '') {
    global $color, $squirrelmail_language;
    $read = '';
    $tag_uid_a = explode(' ',trim($tag_uid));
    $tag = $tag_uid_a[0];
    $resultlist = array();
    $data = array();
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
            if ($arg && $found_tag==$tag) {
                switch ($arg)
                {
                  case 'OK':
                  case 'BAD':
                  case 'NO':
                  case 'BYE':
                  case 'PREAUTH':
                    $response = $arg;
                    $message = trim(substr($read,$i+strlen($arg)));
                    break 3;
                 default: 
                    /* this shouldn't happen */
                    $response = $arg;
                    $message = trim(substr($read,$i+strlen($arg)));
                    break 3;
                }
            } elseif($found_tag !== $tag) {
                /* reset data array because we do not need this reponse */
                $data = array();
                $read = sqimap_fgets($imap_stream);
                break;
            }
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
                            $sLiteral = fread($imap_stream,$iLit);
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
                            $fetch_data[] = sqimap_fgets($imap_stream);
                        } else {
                           $fetch_data[] = $read;
                        }
                        /* retrieve next line and check in the while
                           statements if it belongs to this fetch response */
                        $read = sqimap_fgets($imap_stream);
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
                        $data[] = fread($imap_stream,$iLit);
                        $fetch_data[] = sqimap_fgets($imap_stream);
                    } else {
                        $data[] = $read;
                    }
                    $read = sqimap_fgets($imap_stream);
                    if ($read{0} == '*') break;
                    $s = substr($read,-3);
                } while ($s === "}\r\n");
                break 1;
            }
            break;
          } // end case '*'
        }   // end switch
    }

    /* Set $resultlist array */
    if (!empty($data)) {
        $resultlist[] = $data;
    }
    elseif (empty($resultlist)) {
        $resultlist[] = array(); 
    }

    /* Return result or handle errors */
    if ($handle_errors == false) {
        return( $resultlist );
    } 
    elseif ($response == 'NO') {
    /* ignore this error from M$ exchange, it is not fatal (aka bug) */
        if (strstr($message, 'command resulted in') === false) {
            set_up_language($squirrelmail_language);
            require_once(SM_PATH . 'functions/display_messages.php');
            $string = "<b><font color=$color[2]>\n" .
                _("ERROR : Could not complete request.") .
                "</b><br>\n" .
                _("Query:") . ' ' .
                htmlspecialchars($query) . '<br>' .
                _("Reason Given: ") .
                htmlspecialchars($message) . "</font><br>\n";
            error_box($string,$color);
            exit;
        }
    } 
    elseif ($response == 'BAD') {
        set_up_language($squirrelmail_language);
        require_once(SM_PATH . 'functions/display_messages.php');
        $string = "<b><font color=$color[2]>\n" .
            _("ERROR : Bad or malformed request.") .
            "</b><br>\n" .
            _("Query:") . ' '.
            htmlspecialchars($query) . '<br>' .
            _("Server responded: ") .
            htmlspecialchars($message) . "</font><br>\n";
        error_box($string,$color);    
        exit;
    } 
    else {
        return $resultlist;
    }
}

function sqimap_read_data ($imap_stream, $tag_uid, $handle_errors, &$response, &$message, $query = '') {
    $res = sqimap_read_data_list($imap_stream, $tag_uid, $handle_errors, $response, $message, $query);
  
    /* sqimap_read_data should be called for one response
       but since it just calls sqimap_read_data_list which 
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
        return $result;
    }
    else {
        return $res[0];
    }

}

/*
 * Logs the user into the imap server.  If $hide is set, no error messages
 * will be displayed.  This function returns the imap connection handle.
 */
function sqimap_login ($username, $password, $imap_server_address, $imap_port, $hide) {
    global $color, $squirrelmail_language, $onetimepad, $use_imap_tls, $imap_auth_mech;

    if (!isset($onetimepad) || empty($onetimepad)) {
        sqgetglobalvar('onetimepad' , $onetimepad , SQ_SESSION );
    }
    $imap_server_address = sqimap_get_user_server($imap_server_address, $username);
        $host=$imap_server_address;
        
        if (($use_imap_tls == true) and (check_php_version(4,3)) and (extension_loaded('openssl'))) {
          /* Use TLS by prefixing "tls://" to the hostname */
          $imap_server_address = 'tls://' . $imap_server_address;
        }
    
    $imap_stream = fsockopen ( $imap_server_address, $imap_port, $error_number, $error_string, 15);

    /* Do some error correction */
    if (!$imap_stream) {
        if (!$hide) {
            set_up_language($squirrelmail_language, true);
            require_once(SM_PATH . 'functions/display_messages.php');
            $string = sprintf (_("Error connecting to IMAP server: %s.") .
                              "<br>\r\n", $imap_server_address) .
                      "$error_number : $error_string<br>\r\n";
            logout_error($string,$color);
        }
        exit;
    }

    $server_info = fgets ($imap_stream, 1024);

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
                /* Replace this with SASL PLAIN if it ever gets implemented */
                $response="BAD";
                $message='SquirrelMail does not support SASL PLAIN yet. Rerun conf.pl and use login instead.';
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

/* Simply logs out the IMAP session */
function sqimap_logout ($imap_stream) {
    /* Logout is not valid until the server returns 'BYE'
     * If we don't have an imap_ stream we're already logged out */
    if(isset($imap_stream) && $imap_stream)
        sqimap_run_command($imap_stream, 'LOGOUT', false, $response, $message);
}

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

/* Returns the delimeter between mailboxes: INBOX/Test, or INBOX.Test */
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
            $quote_position = strpos ($read[0], '"');
            $sqimap_delimiter = substr ($read[0], $quote_position+1, 1);
        }
    }
    return $sqimap_delimiter;
}


/* Gets the number of messages in the current mailbox. */
function sqimap_get_num_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, "EXAMINE \"$mailbox\"", false, $result, $message);
    for ($i = 0; $i < count($read_ary); $i++) {
        if (ereg("[^ ]+ +([^ ]+) +EXISTS", $read_ary[$i], $regs)) {
            return $regs[1];
        }
    }
    return false; //"BUG! Couldn't get number of messages in $mailbox!";
}


function parseAddress($address, $max=0, $addr_ar = array(), $group = '', $host='', $limit=0) {
    $pos = 0;
    $j = strlen($address);
    $personal = '';
    $addr = '';
    $comment = '';
    if ($max && $max == count($addr_ar)) {
        return $addr_ar;
    }
    while ($pos < $j) {
        if ($max && $max == count($addr_ar)) {
            return $addr_ar;
        }
        $char = $address{$pos};
        switch ($char) {
            case '=':
            /* check if it is an encoded string */
                if (preg_match('/^(=\?([^?]*)\?(Q|B)\?([^?]*)\?=)(.*)/Ui',substr($address,$pos),$reg)) {
            /* add stringpart before the encoded string to the personal var */
            if (!$personal) {
            $personal = substr($address,0,$pos);
            }
                    $personal .= $reg[1];
            $pos += strlen($reg[1]);
                } else {
                    ++$pos;
        }
                break;
            case '"': /* get the personal name */
                ++$pos;
                if ($address{$pos} == '"') {
                    ++$pos;
                } else {                
                    $personal_start = $personal_end = $pos;
                    while ($pos < $j) {
                        $personal_end = strpos($address,'"',$pos);
                        if (($personal_end-2)>0 && (substr($address,$personal_end-2,2) === '\\"' ||
                            substr($address,$personal_end-2,2) === '\\\\')) {
                            $pos = $personal_end+1;
                        } else {
                            $personal = substr($address,$personal_start,$personal_end-$personal_start);
                            break;
                        }
                    }
                    if ($personal_end) { /* prohibit endless loops due to very wrong addresses */
                         $pos = $personal_end+1;
                    } else {
                         $pos = $j;
                    }
                }
                break;
            case '<':  /* get email address */
                $addr_start = $pos;
                $addr_end = strpos($address,'>',$addr_start);
                if($addr_end === FALSE) {
                    // in case the address doesn't end, prevent loop
                    $pos++;
                } else {
                    $addr = substr($address,$addr_start+1,$addr_end-$addr_start-1);
                    $pos = $addr_end+1;
                }
                break;
            case '(':  /* rip off comments */
                $addr_start = $pos;
                $pos = strpos($address,')');
                if ($pos !== false) {
                    $comment = substr($address, $addr_start+1,($pos-$addr_start-1));
                    $address_start = substr($address, 0, $addr_start);
                    $address_end   = substr($address, $pos + 1);
                    $address       = $address_start . $address_end;
                }
                $j = strlen($address);
                $pos = $addr_start + 1;
                break;
            case ';': /* we reached a non rfc2822 compliant delimiter */
                if ($group) {
                    $address = substr($address, 0, $pos - 1);
            ++$pos;
            break;
                }
            case ',':  /* we reached a delimiter */
                if ($addr == '') {
                    $addr = substr($address, 0, $pos);
                } else if ($personal == '') {
                    $personal = trim(substr($address, 0, $addr_start));
                }
                if (!$personal && $comment) $personal = $comment;
                if ($personal) $personal = decodeHeader($personal);
                $addr_ar[] = array($addr,$personal);
                $address = trim(substr($address, $pos+1));
                $j = strlen($address);
                $pos = 0;
                $personal = '';
                $addr = '';
                break;
            case ':':  /* process the group addresses */
                /* group marker */
                $group = substr($address, 0, $pos);
                $address = substr($address, $pos+1);
                $result = parseAddress($address, $max, $addr_ar, $group);
                $addr_ar = $result[0];
                $pos = $result[1];
                $address = substr($address, $pos++);
                $j = strlen($address);
                $group = '';
                break;
            default:
                ++$pos;
                break;
        }
    }
    if ($addr == '') {
        $addr = substr($address, 0, $pos);
    } else if ($personal == '') {
        $personal = trim(substr($address, 0, $addr_start));
    }
    if (!$personal && $comment) $personal = $comment;
    $email = $addr;
    if ($group && $addr == '') { /* no addresses found in group */
        $personal = $group;
        $addr_ar[] = array('',$personal);
        return (array($addr_ar,$pos+1 ));
    } elseif ($group) {
        $addr_ar[] = array($addr,$personal);
        return (array($addr_ar,$pos+1 ));
    } else {
        if ($personal || $addr) {
            $addr_ar[] = array($addr, $personal);
        }
    }
    return ($addr_ar);
}

/*
 * Returns the number of unseen messages in this folder
 */
function sqimap_unseen_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, "STATUS \"$mailbox\" (UNSEEN)", false, $result, $message);
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

/*
 * Returns the number of unseen/total messages in this folder
 */
function sqimap_status_messages ($imap_stream, $mailbox) {
    $read_ary = sqimap_run_command ($imap_stream, "STATUS \"$mailbox\" (MESSAGES UNSEEN RECENT)", false, $result, $message);
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


/*
 *  Saves a message to a given folder -- used for saving sent messages
 */
function sqimap_append ($imap_stream, $sent_folder, $length) {
    fputs ($imap_stream, sqimap_session_id() . " APPEND \"$sent_folder\" (\\Seen) \{$length}\r\n");
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

/* This is an example that gets imapservers from yellowpages (NIS).
 * you can simple put map:map_yp_alias in your $imap_server_address 
 * in config.php use your own function instead map_yp_alias to map your
 * LDAP whatever way to find the users imapserver. */

function map_yp_alias($username) {
   $yp = `ypmatch $username aliases`;
   return chop(substr($yp, strlen($username)+1));
} 

?>
