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

	/* If $_SERVER['HTTP_HOST'] is set, use that in our HELO to the SMTP
	   server.  This should fix the DNS issues some people have had */
	if (sqgetGlobalVar('HTTP_HOST', $HTTP_HOST, SQ_SERVER)) { // HTTP_HOST is set
		$helohost = $HTTP_HOST;
	} else { // For some reason, HTTP_HOST is not set - revert to old behavior
		$helohost = $domain;
	}
	
	/* Lets introduce ourselves */
	if (( $smtp_auth_mech == 'cram-md5') or ( $smtp_auth_mech == 'digest-md5' )) {
	  // Doing some form of non-plain auth
	  fputs($stream, "EHLO $helohost\r\n");
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
	  fputs($stream, "HELO $helohost\r\n");
      $tmp = fgets($stream, 1024);
	  if ($this->errorCheck($tmp, $stream)) {
        return(0);
	  }
	} elseif ($smtp_auth_mech == 'login') {
	  // The LOGIN method
      fputs($stream, "EHLO $helohost\r\n");
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
	} else {
		/* Right here, they've reached an unsupported auth mechanism.
		   This is the ugliest hack I've ever done, but it'll do till I can fix
		   things up better tomorrow.  So tired... */
		if ($this->errorCheck("535 Unable to use this auth type",$stream)) {
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
    
    /* check if an SMTP reply is an error and set an error message) */
    function errorCheck($line, $smtpConnection) {

        $err_num = substr($line, 0, 3);
        $this->dlv_ret_nr = $err_num;
        $server_msg = substr($line, 4);

        while(substr($line, 0, 4) == ($err_num.'-')) {
            $line = fgets($smtpConnection, 1024);
            $server_msg .= substr($line, 4);
        }

        if ( ((int) $err_num{0}) < 4)
        {
        	return false;
        }

        switch ($err_num) {
        case '421': $message = _("Service not available, closing channel");
            break;
        case '432': $message = _("A password transition is needed");
            break;
        case '450': $message = _("Requested mail action not taken: mailbox unavailable");
            break;
        case '451': $message = _("Requested action aborted: error in processing");
            break;
        case '452': $message = _("Requested action not taken: insufficient system storage");
            break;
        case '454': $message = _("Temporary authentication failure");
            break;
        case '500': $message = _("Syntax error; command not recognized");
            break;
        case '501': $message = _("Syntax error in parameters or arguments");
            break;
        case '502': $message = _("Command not implemented");
            break;
        case '503': $message = _("Bad sequence of commands");
            break;
        case '504': $message = _("Command parameter not implemented");
            break;    
        case '530': $message = _("Authentication required");
            break;
        case '534': $message = _("Authentication mechanism is too weak");
            break;
        case '535': $message = _("Authentication failed");
            break;
        case '538': $message = _("Encryption required for requested authentication mechanism");
            break;
        case '550': $message = _("Requested action not taken: mailbox unavailable");
            break;
        case '551': $message = _("User not local; please try forwarding");
            break;
        case '552': $message = _("Requested mail action aborted: exceeding storage allocation");
            break;
        case '553': $message = _("Requested action not taken: mailbox name not allowed");
            break;
        case '554': $message = _("Transaction failed");
            break;
        default:    $message = _("Unknown response");
            break;
        }

        $this->dlv_msg = $message;
        $this->dlv_server_msg = nl2br(htmlspecialchars($server_msg));

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
