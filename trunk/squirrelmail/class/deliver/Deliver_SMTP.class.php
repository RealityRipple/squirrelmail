<?php
/**
 * Deliver_SMTP.class.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Delivery backend for the Deliver class.
 *
 * $Id$
 */

require_once(SM_PATH . 'class/deliver/Deliver.class.php');

class Deliver_SMTP extends Deliver {

    function preWriteToStream(&$s) {
       if ($s) {
          if ($s{0} == '.')   $s = '.' . $s;
    	  $s = str_replace("\n.","\n..",$s);
       }
    }
    
    function initStream($message, $domain, $length=0, $host='', $port='', $user='', $pass='', $authpop=false) {
	global $use_smtp_tls,$smtp_auth_mech,$username,$key,$onetimepad;
    
	if ($authpop) {
	   $this->authPop($host, '', $username, $pass);
	}
	
    $rfc822_header = $message->rfc822_header;      
	$from = $rfc822_header->from[0];  
	$to =   $rfc822_header->to;
	$cc =   $rfc822_header->cc;
	$bcc =  $rfc822_header->bcc;

    if (($use_smtp_tls == true) and (check_php_version(4,3)) and (extension_loaded('openssl'))) {
	  $stream = fsockopen('tls://' . $host, $port, $errorNumber, $errorString);
	} else {
	  $stream = fsockopen($host, $port, $errorNumber, $errorString);
	}

	if (!$stream) {
	    $this->dlv_msg = $errorString;
	    $this->dlv_ret_nr = $errorNumber;
	    return(0);
	}
	$tmp = fgets($stream, 1024);
	if ($this->errorCheck($tmp, $stream)) {
    	    return(0);
	}
    
	/* Lets introduce ourselves */
	if (( $smtp_auth_mech == 'cram-md5') or ( $smtp_auth_mech == 'digest-md5' )) {
	  // Doing some form of non-plain auth
	  fputs($stream, "EHLO $domain\r\n");
	  $tmp = fgets($stream,1024);
	  if ($this->errorCheck($tmp,$stream)) {
	    return(0);
	  }
	  if ($smtp_auth_mech == 'cram-md5') {
	    fputs($stream, "AUTH CRAM-MD5\r\n");
	  } elseif ($smtp_auth_mech == 'digest-md5') {
	    fputs($stream, "AUTH DIGEST-MD5\r\n");
	  }
	  $tmp = fgets($stream,1024);
	   
	  if ($this->errorCheck($tmp,$stream)) {
	    return(0);
	  }
	  
      // At this point, $tmp should hold "334 <challenge string>"
	  $chall = substr($tmp,4);
	  // Depending on mechanism, generate response string 
	  if ($smtp_auth_mech == 'cram-md5') {
	    $response = cram_md5_response($username,$pass,$chall);
	  } elseif ($smtp_auth_mech == 'digest-md5') {
	    $response = digest_md5_response($username,$pass,$chall,'smtp',$host);
	  }
	  fputs($stream, $response);
	  
	  // Let's see what the server had to say about that
	  $tmp = fgets($stream,1024);
	  if ($this->errorCheck($tmp,$stream)) {
	    return(0);
	  }
       
	  // CRAM-MD5 is done at this point.  If DIGEST-MD5, there's a bit more to go
	  if ($smtp_auth_mech == 'digest-md5')
	  {
	    // $tmp contains rspauth, but I don't store that yet. (No need yet)
		fputs($stream,"\r\n");
		$tmp = fgets($stream,1024);
		 
		if ($this->errorCheck($tmp,$stream)) {
		  return(0);
		}
	  }
    // CRAM-MD5 and DIGEST-MD5 code ends here
	} elseif ($smtp_auth_mech == 'none') {
	  // No auth at all, just send helo and then send the mail
	  fputs($stream, "HELO $domain\r\n");
      $tmp = fgets($stream, 1024);
	  if ($this->errorCheck($tmp, $stream)) {
        return(0);
	  }
	} elseif ($smtp_auth_mech == 'login') {
	  // The LOGIN method
      fputs($stream, "EHLO $domain\r\n");
      $tmp = fgets($stream, 1024);
	  if ($this->errorCheck($tmp, $stream)) {
    	return(0);
	  }
      fputs($stream, "AUTH LOGIN\r\n");
      $tmp = fgets($stream, 1024);

	  if ($this->errorCheck($tmp, $stream)) {
    	return(0);
	  }
      fputs($stream, base64_encode ($username) . "\r\n");
      $tmp = fgets($stream, 1024);
	  if ($this->errorCheck($tmp, $stream)) {
    	return(0);
	  }

      fputs($stream, base64_encode($pass) . "\r\n");
      $tmp = fgets($stream, 1024);
	  if ($this->errorCheck($tmp, $stream)) {
    	return(0);
	  }
	}
    
	/* Ok, who is sending the message? */
        fputs($stream, 'MAIL FROM: <'.$from->mailbox.'@'.$from->host.">\r\n");
        $tmp = fgets($stream, 1024);
	if ($this->errorCheck($tmp, $stream)) {
    	    return(0);
	}

	/* send who the recipients are */
	for ($i = 0, $cnt = count($to); $i < $cnt; $i++) {
	    if (!$to[$i]->host) $to[$i]->host = $domain;
	    if ($to[$i]->mailbox) {
    		fputs($stream, 'RCPT TO: <'.$to[$i]->mailbox.'@'.$to[$i]->host.">\r\n");
    		$tmp = fgets($stream, 1024);
		if ($this->errorCheck($tmp, $stream)) {
    		    return(0);
		}
	    }
	}
	
	for ($i = 0, $cnt = count($cc); $i < $cnt; $i++) {	
	    if (!$cc[$i]->host) $cc[$i]->host = $domain;
	    if ($cc[$i]->mailbox) {
    		fputs($stream, 'RCPT TO: <'.$cc[$i]->mailbox.'@'.$cc[$i]->host.">\r\n");
    		$tmp = fgets($stream, 1024);
		if ($this->errorCheck($tmp, $stream)) {
    		    return(0);
		}
	    }
	}
	for ($i = 0, $cnt = count($bcc); $i < $cnt; $i++) {
	    if (!$bcc[$i]->host) $bcc[$i]->host = $domain;
	    if ($bcc[$i]->mailbox) {
    		fputs($stream, 'RCPT TO: <'.$bcc[$i]->mailbox.'@'.$bcc[$i]->host.">\r\n");
    		$tmp = fgets($stream, 1024);
		if ($this->errorCheck($tmp, $stream)) {
    		    return(0);
		}
	    }
        }
	/* Lets start sending the actual message */
	fputs($stream, "DATA\r\n");
	$tmp = fgets($stream, 1024);
	if ($this->errorCheck($tmp, $stream)) {
    	    return(0);
	}
	return $stream;
    }
    
    function finalizeStream($stream) {
	fputs($stream, ".\r\n"); /* end the DATA part */
	$tmp = fgets($stream, 1024);
	$this->errorCheck($tmp, $stream);
	if ($this->dlv_ret_nr != 250) {
    	    return(0);
	}
	fputs($stream, "QUIT\r\n"); /* log off */
	fclose($stream);
	return true;
    }
    
    function errorCheck($line, $smtpConnection) {
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
	case 421:   $message = 'Service not available, closing channel';
    	    $status = 0;
    	    break;
	case 235:   $message = 'Authentication successful';
			$status = 5;
			break;
	case 250:   $message = 'Requested mail action okay, completed';
    	    $status = 5;
    	    break;
	case 251:   $message = 'User not local; will forward';
    	    $status = 5;
    	    break;
	case 334:   $message = 'OK - continue request';
			$status = 5;
			break;
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
	/* RFC 2554 */    
	case 432: $message = 'A password transition is needed';
	    $status = 0;
	    break;
	case 534: $message = 'Authentication mechanism is too weak';
	    $status = 0;
	    break;
	case 538: $message = 'Encryption required for requested authentication mechanism';
	    $status = 0;
	    break;
	case 454:   $message = 'Temmporary authentication failure';
	    $status = 0;
	    break;
	case 530: $message = 'Authentication required';
	    $status = 0;
	    break;
        /* end RFC2554 */	
	case 535: $message = 'Authentication failed';
		$status = 0;
        break;
	default:    $message = 'Unknown response: '. nl2br(htmlspecialchars($lines));
    	    $status = 0;
    	    $err_num = '001';
    	    break;
	}
	$this->dlv_ret_nr = $err_num;
	$this->dlv_msg = $message;
	if ($status == 5) {
	    return false;
	}
        return true;
    }
    
    function authPop($pop_server='', $pop_port='', $user, $pass) {
        if (!$pop_port) {
            $pop_port = 110;
        }
        if (!$pop_server) {
            $pop_server = 'localhost';
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
            fputs($popConnection, "USER $user\r\n");
            $tmp = fgets($popConnection, 1024);
            if (!eregi("^\+OK", $tmp, $regs)) {
                return(0);
            }
            fputs($popConnection, 'PASS ' . $pass . "\r\n");
            $tmp = fgets($popConnection, 1024);
            if (!eregi("^\+OK", $tmp, $regs)) {
                return(0);
            }
            fputs($popConnection, "QUIT\r\n"); /* log off */
            fclose($popConnection);
        }
    }
}

?>
