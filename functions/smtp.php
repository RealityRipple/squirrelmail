<?php

/**
 * smtp.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains all the functions needed to send messages through
 * an smtp server or sendmail.
 *
 * $Id$
 */

require_once('../functions/addressbook.php');
require_once('../functions/plugin.php');
require_once('../functions/prefs.php');

global $username, $popuser, $domain;

/* This should most probably go to some initialization... */
if (ereg("^([^@%/]+)[@%/](.+)$", $username, $usernamedata)) {
    $popuser = $usernamedata[1];
    $domain  = $usernamedata[2];
    unset($usernamedata);
} else {
    $popuser = $username;
}
/* We need domain for smtp */
if (!$domain) {
    $domain = getenv('HOSTNAME');
}

/* Returns true only if this message is multipart */
function isMultipart ($session) {
    global $attachments;

    foreach ($attachments as $info) {
        if ($info['session'] == $session) {
            return true;
        }
    }
    return false;
}

/* looks up aliases in the addressbook and expands them to
 * the full address. 
 *
 * Adds @$domain if it wasn't in the address book and if it
 * doesn't have an @ symbol in it 
 */
function expandAddrs ($array) {
    global $domain;
    
    /* don't show errors -- kinda critical that we don't see
     * them here since the redirect won't work if we do show them
     */
    $abook = addressbook_init(false, true);
    for ($i=0; $i < count($array); $i++) {
        $result = $abook->lookup($array[$i]);
        $ret = "";
        if (isset($result['email'])) {
            if (isset($result['name'])) {
                $ret = '"'.$result['name'].'" ';
            }
            $ret .= '<'.$result['email'].'>';
            $array[$i] = $ret;
        }
        else
        {
            if (strpos($array[$i], '@') === false) {
                $array[$i] .= '@' . $domain;
            }
            $array[$i] = '<' . $array[$i] . '>';
        }
    }
    return $array;
}


/* looks up aliases in the addressbook and expands them to
 * the RFC 821 valid RCPT address. ie <user@example.com>
 * Adds @$domain if it wasn't in the address book and if it
 * doesn't have an @ symbol in it
 */
function expandRcptAddrs ($array) {
    global $domain;
    
    /* don't show errors -- kinda critical that we don't see
     * them here since the redirect won't work if we do show them
     */
    $abook = addressbook_init(false, true);
    for ($i=0; $i < count($array); $i++) {
        $result = $abook->lookup($array[$i]);
        $ret = "";
        if (isset($result['email'])) {
            $ret = '<'.$result['email'].'>';
            $array[$i] = $ret;
        }
        else {
            if (strpos($array[$i], '@') === false) {
                $array[$i] .= '@' . $domain;
            }
            $array[$i] = '<' . $array[$i] . '>';
        }
    }
    return $array;
}


/* Attach the files that are due to be attached
 */
function attachFiles ($fp, $session, $rn="\r\n") {
    global $attachments, $attachment_dir, $username;

    $length = 0;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (isMultipart($session)) {
        foreach ($attachments as $info) {
            if ($info['session'] == $session) {
                if (isset($info['type'])) {
                    $filetype = $info['type'];
                }
                else {
                    $filetype = 'application/octet-stream';
                }
                
                $header = '--' . mimeBoundary() . "$rn";
                if ( isset($info['remotefilename']) 
                     && $info['remotefilename'] != '') {
                    $header .= "Content-Type: $filetype; name=\"" .
                        $info['remotefilename'] . "\"$rn";
                    $header .= "Content-Disposition: attachment; filename=\""
                        . $info['remotefilename'] . "\"$rn";
                } else {
                    $header .= "Content-Type: $filetype$rn";
                }

                
                /* Use 'rb' for NT systems -- read binary
                 * Unix doesn't care -- everything's binary!  :-)
                 */
                
                $filename = $hashed_attachment_dir . '/' 
                    . $info['localfilename'];
                $file = fopen ($filename, 'rb');
                if (substr($filetype, 0, 5) == 'text/' ||
                    substr($filetype, 0, 8) == 'message/' ) {
                    $header .= $rn;
					if ($fp) {
					    fputs ($fp, $header);
					}
                    $length += strlen($header);
                    while ($tmp = fgets($file, 4096)) {
                        $tmp = str_replace("\r\n", "\n", $tmp);
                        $tmp = str_replace("\r", "\n", $tmp);
                        if ($rn == "\r\n"){
                            $tmp = str_replace("\n", "\r\n", $tmp);
                        }
                        /**
                         * Check if the last line has newline ($rn) in it
                         * and append if it doesn't.
                         */
                        if ($file && feof($file) && !strstr($tmp, "$rn")){
                            $tmp .= $rn;
                        }
                        if ($fp) { 
                            fputs($fp, $tmp);
                        }
                        $length += strlen($tmp);
                    }
                } else {
                    $header .= "Content-Transfer-Encoding: base64" 
                        . "$rn" . "$rn";
                    if ($fp) fputs ($fp, $header);
                    $length += strlen($header);
                    while ($tmp = fread($file, 570)) {
                        $encoded = chunk_split(base64_encode($tmp));
                        $length += strlen($encoded);
                        if ($fp) fputs ($fp, $encoded);
                    }
                }
                fclose ($file);
            }
        }
    }
    return $length;
}

/* Delete files that are uploaded for attaching
 */
function deleteAttachments($session) {
    global $username, $attachments, $attachment_dir;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $rem_attachments = array();
    foreach ($attachments as $info) {
        if ($info['session'] == $session) {
    	    $attached_file = "$hashed_attachment_dir/$info[localfilename]";
    	    if (file_exists($attached_file)) {
                unlink($attached_file);
    	    }
        } else {
            $rem_attachments[] = $info;
        }
    }
    $attachments = $rem_attachments;
}

/* Return a nice MIME-boundary
 */
function mimeBoundary () {
    static $mimeBoundaryString;

    if ( !isset( $mimeBoundaryString ) ||
         $mimeBoundaryString == '') {
        $mimeBoundaryString = '----=_' . date( 'YmdHis' ) . '_' .
            mt_rand( 10000, 99999 );
    }

    return $mimeBoundaryString;
}

/* Time offset for correct timezone */
function timezone () {
    global $invert_time;
    
    $diff_second = date('Z');
    if ($invert_time) {
        $diff_second = - $diff_second;
    }
    if ($diff_second > 0) {
        $sign = '+';
    }
    else {
        $sign = '-';
    }

    $diff_second = abs($diff_second);
    
    $diff_hour = floor ($diff_second / 3600);
    $diff_minute = floor (($diff_second-3600*$diff_hour) / 60);
    
    $zonename = '('.strftime('%Z').')';
    $result = sprintf ("%s%02d%02d %s", $sign, $diff_hour, $diff_minute, 
                       $zonename);
    return ($result);
}

/* Print all the needed RFC822 headers */
function write822Header ($fp, $t, $c, $b, $subject, $more_headers, $session, $rn="\r\n") {
    global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
    global $data_dir, $username, $popuser, $domain, $version, $useSendmail;
    global $default_charset, $HTTP_VIA, $HTTP_X_FORWARDED_FOR;
    global $REMOTE_HOST, $identity;

    /* Storing the header to make sure the header is the same
     * everytime the header is printed.
     */
    static $header, $headerlength, $headerrn;
    
    if ($header == '') {
		$headerrn = $rn;
        $to = expandAddrs(parseAddrs($t));
        $cc = expandAddrs(parseAddrs($c));
        $bcc = expandAddrs(parseAddrs($b));
        if (isset($identity) && $identity != 'default') {
            $reply_to = getPref($data_dir, $username, 'reply_to' . $identity);
            $from = getPref($data_dir, $username, 'full_name' . $identity);
            $from_addr = getFrom();
        } else {
            $reply_to = getPref($data_dir, $username, 'reply_to');
            $from = getPref($data_dir, $username, 'full_name');
            $from_addr = getFrom();
        }
        
        $to_list = getLineOfAddrs($to);
        $cc_list = getLineOfAddrs($cc);
        $bcc_list = getLineOfAddrs($bcc);
        
        /* Encoding 8-bit characters and making from line */
        $subject = encodeHeader($subject);
        if ($from == '') {
            $from = "<$from_addr>";
        }
        else {
            $from = '"' . encodeHeader($from) . "\" <$from_addr>";
        }
        
        /* This creates an RFC 822 date */
        $date = date("D, j M Y H:i:s ", mktime()) . timezone();
        
        /* Create a message-id */
        $message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
        $message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';
        
        /* Make an RFC822 Received: line */
        if (isset($REMOTE_HOST)) {
            $received_from = "$REMOTE_HOST ([$REMOTE_ADDR])";
        }
        else {
            $received_from = $REMOTE_ADDR;
        }

        if (isset($HTTP_VIA) || isset ($HTTP_X_FORWARDED_FOR)) {
            if ($HTTP_X_FORWARDED_FOR == '') {
                $HTTP_X_FORWARDED_FOR = 'unknown';
            }
            $received_from .= " (proxying for $HTTP_X_FORWARDED_FOR)";
        }

        $header  = "Received: from $received_from" . $rn;
        $header .= "        (SquirrelMail authenticated user $username)" . $rn;
        $header .= "        by $SERVER_NAME with HTTP;" . $rn;
        $header .= "        $date" . $rn;

        /* Insert the rest of the header fields */
        $header .= "Message-ID: $message_id" . $rn;
        $header .= "Date: $date" . $rn;
        $header .= "Subject: $subject" . $rn;
        $header .= "From: $from" . $rn;
        $header .= "To: $to_list" . $rn;    // Who it's TO

        if (isset($more_headers["Content-Type"])) {
            $contentType = $more_headers["Content-Type"];
            unset($more_headers["Content-Type"]);
        }
        else {
            if (isMultipart($session)) {
                $contentType = "multipart/mixed;";
            }
            else {
                if ($default_charset != '') {
                    $contentType = 'text/plain; charset='.$default_charset;
                }
                else {
                    $contentType = 'text/plain;';
                }
            }
        }

        /* Insert headers from the $more_headers array */
        if(is_array($more_headers)) {
            reset($more_headers);
            while(list($h_name, $h_val) = each($more_headers)) {
	        if ($h_name == 'References') {
		    $h_val = str_replace(' ', "$rn        ", $h_val);
		}    
                $header .= sprintf("%s: %s%s", $h_name, $h_val, $rn);
            }
        }

        if ($cc_list) {
            $header .= "Cc: $cc_list" . $rn; // Who the CCs are
        }

        if ($reply_to != '') {
            $header .= "Reply-To: $reply_to" . $rn;
        }

        if ($useSendmail) {
            if ($bcc_list) {
                // BCCs is removed from header by sendmail
                $header .= "Bcc: $bcc_list" . $rn;
            }
        }

        /* Identify SquirrelMail */
        $header .= "X-Mailer: SquirrelMail (version $version)" . $rn; 

        /* Do the MIME-stuff */
        $header .= "MIME-Version: 1.0" . $rn;

        if (isMultipart($session)) {
            $header .= 'Content-Type: '.$contentType.' boundary="';
            $header .= mimeBoundary();
            $header .= "\"$rn";
        } else {
            $header .= 'Content-Type: ' . $contentType . $rn;
            $header .= "Content-Transfer-Encoding: 8bit" . $rn;
        }
        $header .= $rn; // One blank line to separate header and body
        
        $headerlength = strlen($header);
    }     

	if ($headerrn != $rn) {
		$header = str_replace($headerrn, $rn, $header);
        $headerlength = strlen($header);
		$headerrn = $rn;
	}
    
    /* Write the header */
    if ($fp) fputs ($fp, $header);
    
    return $headerlength;
}

/* Send the body
 */
function writeBody ($fp, $passedBody, $session, $rn="\r\n") {
    global $default_charset;

    $attachmentlength = 0;
    
    if (isMultipart($session)) {
        $body = '--'.mimeBoundary() . $rn;
        
        if ($default_charset != "") {
            $body .= "Content-Type: text/plain; charset=$default_charset".$rn;
        }
        else {
            $body .= "Content-Type: text/plain" . $rn;
        }
        
        $body .= "Content-Transfer-Encoding: 8bit" . $rn . $rn;
        $body .= $passedBody . $rn . $rn;
        if ($fp) fputs ($fp, $body);
        
        $attachmentlength = attachFiles($fp, $session, $rn);
        
        if (!isset($postbody)) { 
            $postbody = ""; 
        }
        $postbody .= $rn . "--" . mimeBoundary() . "--" . $rn . $rn;
        if ($fp) fputs ($fp, $postbody);
    } else {
        $body = $passedBody . $rn;
        if ($fp) fputs ($fp, $body);
        $postbody = $rn;
        if ($fp) fputs ($fp, $postbody);
    }

    return (strlen($body) + strlen($postbody) + $attachmentlength);
}

/* Send mail using the sendmail command
 */
function sendSendmail($t, $c, $b, $subject, $body, $more_headers, $session) {
    global $sendmail_path, $popuser, $username, $domain;
    
    /* Build envelope sender address. Make sure it doesn't contain 
     * spaces or other "weird" chars that would allow a user to
     * exploit the shell/pipe it is used in.
     */
    $envelopefrom = getFrom();
    $envelopefrom = ereg_replace("[[:blank:]]",'', $envelopefrom);
    $envelopefrom = ereg_replace("[[:space:]]",'', $envelopefrom);
    $envelopefrom = ereg_replace("[[:cntrl:]]",'', $envelopefrom);
    
    /**
     * open pipe to sendmail or qmail-inject 
     * (qmail-inject doesn't accept -t param) 
     */
    if (strstr($sendmail_path, "qmail-inject")) {
        $fp = popen (escapeshellcmd("$sendmail_path -f$envelopefrom"), "w");
    } else {
        $fp = popen (escapeshellcmd("$sendmail_path -t -f$envelopefrom"), "w");
    }
    
    $headerlength = write822Header ($fp, $t, $c, $b, $subject, 
                                    $more_headers, $session, "\n");
    $bodylength = writeBody($fp, $body, $session, "\n");
    
    pclose($fp);

    return ($headerlength + $bodylength);
}

function smtpReadData($smtpConnection) {
    $read = fgets($smtpConnection, 1024);
    $counter = 0;
    while ($read) {
        echo $read . '<BR>';
        $data[$counter] = $read;
        $read = fgets($smtpConnection, 1024);
        $counter++;
    }
}

function sendSMTP($t, $c, $b, $subject, $body, $more_headers, $session) {
    global $username, $popuser, $domain, $version, $smtpServerAddress, 
        $smtpPort, $data_dir, $color, $use_authenticated_smtp, $identity, 
        $key, $onetimepad;
    
    $to = expandRcptAddrs(parseAddrs($t));
    $cc = expandRcptAddrs(parseAddrs($c));
    $bcc = expandRcptAddrs(parseAddrs($b));
    if (isset($identity) && $identity != 'default') {
        $from_addr = getPref($data_dir, $username, 
                             'email_address' . $identity);
    }
    else {
        $from_addr = getPref($data_dir, $username, 'email_address');
    }
    
    if (!$from_addr) {
        $from_addr = "$popuser@$domain";
    }

    /* POP3 BEFORE SMTP CODE HERE */
    global $pop_before_smtp;
    if (isset($pop_before_smtp) && $pop_before_smtp === true) {
        if (!isset($pop_port)) {
            $pop_port = 110;
        }
        if (!isset($pop_server)) {
            $pop_server = $smtpServerAddress; /* usually the same host! */
        }
        $popConnection = fsockopen($pop_server, $pop_port, $err_no, $err_str);
        if (!$popConnection) {
            error_log("Error connecting to POP Server ($pop_server:$pop_port)"
                  . " $err_no : $err_str");
        } else {
            $tmp = fgets($popConnection, 1024); /* banner */
            if (!eregi("^\+OK", $tmp, $regs)) {
                return(0);
            }
            fputs($popConnection, "USER $username\r\n");
            $tmp = fgets($popConnection, 1024);
            if (!eregi("^\+OK", $tmp, $regs)) {
                return(0);
            }
            fputs($popConnection, 'PASS ' . OneTimePadDecrypt($key, $onetimepad) . "\r\n");
            $tmp = fgets($popConnection, 1024);
            if (!eregi("^\+OK", $tmp, $regs)) {
                return(0);
            }
            fputs($popConnection, "QUIT\r\n"); /* log off */
            fclose($popConnection);
        }
    }
    
    $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, 
                                $errorNumber, $errorString);
    if (!$smtpConnection) {
        echo 'Error connecting to SMTP Server.<br>';
        echo "$errorNumber : $errorString<br>";
        exit;
    }
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }
    
    $to_list = getLineOfAddrs($to);
    $cc_list = getLineOfAddrs($cc);
    
    /* Lets introduce ourselves */
    if (! isset ($use_authenticated_smtp) 
        || $use_authenticated_smtp == false) {
        fputs($smtpConnection, "HELO $domain\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) return(0);
    } else {
        fputs($smtpConnection, "EHLO $domain\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) return(0);
        
        fputs($smtpConnection, "AUTH LOGIN\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }

        fputs($smtpConnection, base64_encode ($username) . "\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
        
        fputs($smtpConnection, base64_encode 
              (OneTimePadDecrypt($key, $onetimepad)) . "\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }
    
    /* Ok, who is sending the message? */
    fputs($smtpConnection, "MAIL FROM: <$from_addr>\r\n");
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }
    
    /* send who the recipients are */
    for ($i = 0; $i < count($to); $i++) {
        fputs($smtpConnection, "RCPT TO: $to[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }
    for ($i = 0; $i < count($cc); $i++) {
        fputs($smtpConnection, "RCPT TO: $cc[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }
    for ($i = 0; $i < count($bcc); $i++) {
        fputs($smtpConnection, "RCPT TO: $bcc[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }

    /* Lets start sending the actual message */
    fputs($smtpConnection, "DATA\r\n");
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }

    /* Send the message */
    $headerlength = write822Header ($smtpConnection, $t, $c, $b, 
                                    $subject, $more_headers, $session);
    $bodylength = writeBody($smtpConnection, $body, $session);
    
    fputs($smtpConnection, ".\r\n"); /* end the DATA part */
    $tmp = fgets($smtpConnection, 1024);
    $num = errorCheck($tmp, $smtpConnection, true);
    if ($num != 250) {
        return(0);
    }
    
    fputs($smtpConnection, "QUIT\r\n"); /* log off */
    
    fclose($smtpConnection);
    
    return ($headerlength + $bodylength);
}


function errorCheck($line, $smtpConnection, $verbose = false) {
    global $color, $compose_new_win;
    
    /* Read new lines on a multiline response */
    $lines = $line;
    while(ereg("^[0-9]+-", $line)) {
        $line = fgets($smtpConnection, 1024);
        $lines .= $line;
    }
    
    /* Status:  0 = fatal
     *          5 = ok
     */
    $err_num = substr($line, 0, strpos($line, " "));
    switch ($err_num) {
    case 500:   $message = 'Syntax error; command not recognized';
        $status = 0;
        break;
    case 501:   $message = 'Syntax error in parameters or arguments';
        $status = 0;
        break;
    case 502:   $message = 'Command not implemented';
        $status = 0;
        break;
    case 503:   $message = 'Bad sequence of commands';
        $status = 0;
        break;
    case 504:   $message = 'Command parameter not implemented';
        $status = 0;
        break;    
        
    case 211:   $message = 'System status, or system help reply';
        $status = 5;
        break;
    case 214:   $message = 'Help message';
        $status = 5;
        break;
        
    case 220:   $message = 'Service ready';
        $status = 5;
        break;
    case 221:   $message = 'Service closing transmission channel';
        $status = 5;
        break;

    case 421:   $message = 'Service not available, closing chanel';
        $status = 0;
        break;
        
    case 235:   return(5); 
        break;
    case 250:   $message = 'Requested mail action okay, completed';
        $status = 5;
        break;
    case 251:   $message = 'User not local; will forward';
        $status = 5;
        break;
    case 334:   return(5); break;
    case 450:   $message = 'Requested mail action not taken:  mailbox unavailable';
        $status = 0;
        break;
    case 550:   $message = 'Requested action not taken:  mailbox unavailable';
        $status = 0;
        break;
    case 451:   $message = 'Requested action aborted:  error in processing';
        $status = 0;
        break;
    case 551:   $message = 'User not local; please try forwarding';
        $status = 0;
        break;
    case 452:   $message = 'Requested action not taken:  insufficient system storage';
        $status = 0;
        break;
    case 552:   $message = 'Requested mail action aborted:  exceeding storage allocation';
        $status = 0;
        break;
    case 553:   $message = 'Requested action not taken: mailbox name not allowed';
        $status = 0;
        break;
    case 354:   $message = 'Start mail input; end with .';
        $status = 5;
        break;
    case 554:   $message = 'Transaction failed';
        $status = 0;
        break;
    default:    $message = 'Unknown response: '. nl2br(htmlspecialchars($lines));
        $status = 0;
        $error_num = '001';
        break;
    }

    if ($status == 0) {
        include_once('../functions/page_header.php');
        if ($compose_new_win == '1') {
            compose_Header($color, 'None');
        }
        else {
            displayPageHeader($color, 'None');
        }
        include_once('../functions/display_messages.php');
        $lines = nl2br(htmlspecialchars($lines));
        $msg  = $message . "<br>\nServer replied: $lines";
        plain_error_message($msg, $color);
    }
    if (! $verbose) return $status;
    return $err_num;
}

/* create new reference header per rfc2822 */

function calculate_references($refs, $inreplyto, $old_reply_to) {

    $refer = "";
    for ($i=1;$i<count($refs[0]);$i++) {
        if (!empty($refs[0][$i])) {
            if (preg_match("/^References:(.+)$/", $refs[0][$i], $regs)) {
                $refer = trim($regs[1]);
            }
            else {   
                $refer .= ' ' . trim($refs[0][$i]);
            }
        }
    }
    $refer = trim($refer);
    if (strlen($refer) > 2) {
        $refer .= ' ' . $inreplyto;
    }
    else {
        if (!empty($old_reply_to)) {
            $refer .= $old_reply_to . ' ' . $inreplyto;
        }
        else {
            $refer .= $inreplyto;
        }                        
    }
    trim($refer);
    return $refer;
}

function sendMessage($t, $c, $b, $subject, $body, $reply_id, $MDN, 
                     $prio = 3, $session) {
    global $useSendmail, $msg_id, $is_reply, $mailbox, $onetimepad,
        $data_dir, $username, $domain, $key, $version, $sent_folder, 
        $imapServerAddress, $imapPort, $default_use_priority, $more_headers, 
        $request_mdn, $request_dr;

    $more_headers = Array();
    
    do_hook('smtp_send');

    $imap_stream = sqimap_login($username, $key, $imapServerAddress, 
                                $imapPort, 1);

    if (isset($reply_id) && $reply_id) {
        sqimap_mailbox_select ($imap_stream, $mailbox);
        sqimap_messages_flag ($imap_stream, $reply_id, $reply_id, 'Answered');

        /* Insert In-Reply-To and References headers if the
         * message-id of the message we reply to is set (longer than "<>")
         * The References header should really be the old Referenced header
         * with the message ID appended, and now it is (jmunro)
         */
	$sid = sqimap_session_id(); 
	$query = "$sid FETCH $reply_id (BODY.PEEK[HEADER.FIELDS (Message-Id In-Reply-To)])\r\n";
	fputs ($imap_stream, $query);
	$read = sqimap_read_data($imap_stream, $sid, true, $response, $message);
	$message_id = '';
	$in_reply_to = '';

	foreach ($read as $r) {
		if (preg_match("/^message-id:(.*)/iA", $r, $regs)) {
		    $message_id = trim($regs[1]);
		}
		if (preg_match("/^in-reply-to:(.*)/iA", $r, $regs)) {
		    $in_reply_to = trim($regs[1]);
		}
	}

        if(strlen($message_id) > 2) {
            $refs = get_reference_header ($imap_stream, $reply_id);
            $inreplyto = $message_id;
            $old_reply_to = $in_reply_to;
            $refer = calculate_references ($refs, $inreplyto, $old_reply_to);
            $more_headers['In-Reply-To'] = $inreplyto;
            $more_headers['References']  = $refer;
        }

    }
    if ($default_use_priority) {
        $more_headers = array_merge($more_headers, createPriorityHeaders($prio));
    }

    $requestRecipt = 0;
    if (isset($request_dr)) {
        $requestRecipt += 1;
    }
    if (isset($request_mdn)) {
        $requestRecipt += 2;
    }
    if ( $requestRecipt > 0) {
        $more_headers = array_merge($more_headers, createReceiptHeaders($requestRecipt));
    }

    /* In order to remove the problem of users not able to create
     * messages with "." on a blank line, RFC821 has made provision
     * in section 4.5.2 (Transparency).
     */
    $body = ereg_replace("\n\\.", "\n..", $body);
    $body = ereg_replace("^\\.", "..", $body);

    /* this is to catch all plain \n instances and
     * replace them with \r\n.  All newlines were converted
     * into just \n inside the compose.php file.
     * But only if delimiter is, in fact, \r\n.
     */
    
    if ($MDN) {
        $more_headers["Content-Type"] = "multipart/report; ".
            "report-type=disposition-notification;";
    }

    if ($useSendmail) {
        $length = sendSendmail($t, $c, $b, $subject, $body, $more_headers, 
                               $session);
        $body = ereg_replace("\n", "\r\n", $body);
    } else {
        $body = ereg_replace("\n", "\r\n", $body);
        $length = sendSMTP($t, $c, $b, $subject, $body, $more_headers, 
                           $session);
    }
    if (sqimap_mailbox_exists ($imap_stream, $sent_folder)) {
		$headerlength = write822Header (FALSE, $t, $c, $b, $subject, $more_headers, $session, "\r\n");
		$bodylength = writeBody(FALSE, $body, $session, "\r\n");
		$length = $headerlength + $bodylength;

        sqimap_append ($imap_stream, $sent_folder, $length);
        write822Header ($imap_stream, $t, $c, $b, $subject, $more_headers, 
                        $session);
        writeBody ($imap_stream, $body, $session);
        sqimap_append_done ($imap_stream);
    }
    sqimap_logout($imap_stream);
    /* Delete the files uploaded for attaching (if any).
     * only if $length != 0 (if there was no error)
     */
    if ($length) {
        ClearAttachments($session);
    }

    return $length;
}

function createPriorityHeaders($prio) {
    $prio_headers = Array();
    $prio_headers['X-Priority'] = $prio;

    switch($prio) {
    case 1: $prio_headers['Importance'] = 'High';
        $prio_headers['X-MSMail-Priority'] = 'High';
        break;

    case 3: $prio_headers['Importance'] = 'Normal';
        $prio_headers['X-MSMail-Priority'] = 'Normal';
        break;

    case 5:
        $prio_headers['Importance'] = 'Low';
        $prio_headers['X-MSMail-Priority'] = 'Low';
        break;
    }
    return  $prio_headers;
}

function createReceiptHeaders($receipt) {

    GLOBAL $data_dir, $username, $identity, $popuser, $domain;

    $receipt_headers = Array();
    if (isset($identity) && $identity != 'default') {
        $from = getPref($data_dir, $username, 'full_name' . $identity);
        $from_addr = getPref($data_dir, $username, 'email_address' . $identity);
    } else {
        $from = getPref($data_dir, $username, 'full_name');
        $from_addr = getPref($data_dir, $username, 'email_address');
    }
    if ($from_addr == '') {
        $from_addr = $popuser.'@'.$domain;
    }

    if ($from == '') {
        $from = "<$from_addr>";
    }
    else {
        $from = '"' . encodeHeader($from) . "\" <$from_addr>";
    }

    /* On Delivery */
    if ( $receipt == 1
        || $receipt == 3 ) {
        $receipt_headers["Return-Receipt-To"] = $from;
    }
    /* On Read */
    if ($receipt == 2
        || $receipt == 3 ) {
        /* Pegasus Mail */
        $receipt_headers["X-Confirm-Reading-To"] = $from;
        /* RFC 2298 */
        $receipt_headers["Disposition-Notification-To"] = $from;
    }
    return $receipt_headers;
}

/* Figure out what the 'From:' address is
 */

function getFrom() {
    global $username, $popuser, $domain, $data_dir, $identity;
    if (isset($identity) && $identity != 'default') {
        $from_addr = getPref($data_dir, $username, 
                             'email_address' . $identity);
    }
    else {
        $from_addr = getPref($data_dir, $username, 'email_address');
    }
    
    if (!$from_addr) {
        $from_addr = "$popuser@$domain";
    }
    return $from_addr;
}


?>
