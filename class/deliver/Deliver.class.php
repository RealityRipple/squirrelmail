<?php

/**
 * Deliver.class.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains all the functions needed to send messages through
 * a delivery backend.
 *
 * $Id$
 */

class Deliver {

    function mail($message, $stream=false) {
       $rfc822_header = $message->rfc822_header;
       if (count($message->entities)) {
          $boundary = $this->mimeBoundary();
	  $rfc822_header->content_type->properties['boundary']='"'.$boundary.'"';
       } else {
          $boundary='';
       }
       $raw_length = 0;
       $reply_rfc822_header = (isset($message->reply_rfc822_header) 
        	             ? $message->reply_rfc822_header : '');
       $header = $this->prepareRFC822_Header($rfc822_header, $reply_rfc822_header, $raw_length);

       if ($stream) {
            $this->preWriteToStream($header);
            $this->writeToStream($stream, $header);
       }
       $this->writeBody($message, $stream, $raw_length, $boundary);
       return $raw_length;
    }
    
    function writeBody($message, $stream, &$length_raw, $boundary='') {
        if ($boundary && !$message->rfc822_header) {
	    $s = '--'.$boundary."\r\n";
	    $s .= $this->prepareMIME_Header($message, $boundary);
	    $length_raw += strlen($s);
	    if ($stream) {
                $this->preWriteToStream($s);
		$this->writeToStream($stream, $s);
	    }
        }
	$this->writeBodyPart($message, $stream, $length_raw);
	$boundary_depth = substr_count($message->entity_id,'.');
	if ($boundary_depth) {
	   $boundary .= '_part'.$boundary_depth;
	}
	$last = false;
	for ($i=0, $entCount=count($message->entities);$i<$entCount;$i++) {
    	    $msg = $this->writeBody($message->entities[$i], $stream, $length_raw, $boundary);
	    if ($i == $entCount-1) $last = true;
	}
        if ($boundary && $last) {
	    $s = "--".$boundary."--\r\n\r\n";
	    $length_raw += strlen($s);
	    if ($stream) {
                $this->preWriteToStream($s);
		$this->writeToStream($stream, $s);
	    }
	}
    }

    function writeBodyPart($message, $stream, &$length) {
        if ($message->mime_header) {
	   $type0 = $message->mime_header->type0;
	} else {
	   $type0 = $message->rfc822_header->content_type->type0;
	}
	
	$body_part_trailing = $last = '';
	switch ($type0) {
	case 'text':
	case 'message':
	    if ($message->body_part) {
	       $body_part = $message->body_part;
	       $length += $this->clean_crlf($body_part);
	       if ($stream) {
                  $this->preWriteToStream($body_part);     
	          $this->writeToStream($stream, $body_part);
	       }
	       $last = $body_part;
	    } elseif ($message->att_local_name) {
	        $filename = $message->att_local_name;
		$file = fopen ($filename, 'rb');
		while ($body_part = fgets($file, 4096)) {
		   $length += $this->clean_crlf($body_part);
		   if ($stream) {
		      $this->preWriteToStream($body_part);
		      $this->writeToStream($stream, $body_part);
                   }
		   $last = $body_part;
		}
		fclose($file);
            }
	    break;
	default:
	    if ($message->body_part) {
	       $body_part = $message->body_part;
	       $length += $this->clean_crlf($body_part);
	       if ($stream) {
	          $this->writeToStream($stream, $body_part);
    	       }
	    } elseif ($message->att_local_name) {
	        $filename = $message->att_local_name;
		$file = fopen ($filename, 'rb');
		$encoded = '';
		while ($tmp = fread($file, 570)) {
		   $body_part = chunk_split(base64_encode($tmp));
		   $length += $this->clean_crlf($body_part);
		   if ($stream) {
		      $this->writeToStream($stream, $body_part);
		   }
		}
		fclose($file);
	    }
	    break;
	}
	$body_part_trailing = '';
	if ($last && substr($last,-1) != "\n") {
	   $body_part_trailing = "\r\n";
	}
	if ($body_part_trailing) {
	    $length += strlen($body_part_trailing);
	    if ($stream) {
        	$this->preWriteToStream($body_part_trailing);     
		$this->writeToStream($stream, $body_part_trailing);
	    }
	}
    }
    
    function clean_crlf(&$s) {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        $s = str_replace("\n", "\r\n", $s);
	return strlen($s);
    }
    
    function strip_crlf(&$s) {
        $s = str_replace("\r\n ", '', $s);
	$s = str_replace("\r", '', $s);
	$s = str_replace("\n", '', $s);
    }

    function preWriteToStream(&$s) {
    }
    
    function writeToStream($stream, $data) {
       fputs($stream, $data);
    }
    
    function initStream($message, $length=0, $host='', $port='', $user='', $pass='') {
       return $stream;
    }
    
    function getBcc() {
       return false;
    }

    function prepareMIME_Header($message, $boundary) {
	$mime_header = $message->mime_header;
	$rn="\r\n";
	$header = array();
	
	$contenttype = 'Content-Type: '. $mime_header->type0 .'/'.
    	               $mime_header->type1;
	if (count($message->entities)) {
	    $contenttype .= ";\r\n " . 'boundary="'.$boundary.'"';
	}		       
	if (isset($mime_header->parameters['name'])) {
    	    $contenttype .= '; name="'.
        	encodeHeader($mime_header->parameters['name']). '"';
	}
	if (isset($mime_header->parameters['charset'])) {
	    $charset = $mime_header->parameters['charset'];
    	    $contenttype .= '; charset="'.
        	encodeHeader($charset). '"';
	}


	$header[] = $contenttype . $rn;
	if ($mime_header->description) {
    	    $header[] .= 'Content-Description: ' . $mime_header->description . $rn;
	}
	if ($mime_header->encoding) {
	    $encoding = $mime_header->encoding;
    	    $header[] .= 'Content-Transfer-Encoding: ' . $mime_header->encoding . $rn;
	} else {
	    if ($mime_header->type0 == 'text' || $mime_header->type0 == 'message') {
		$header[] .= 'Content-Transfer-Encoding: 8bit' .  $rn;
    	    } else {
		$header[] .= 'Content-Transfer-Encoding: base64' .  $rn;
	    }
	}
	if ($mime_header->id) {
    	    $header[] .= 'Content-ID: ' . $mime_header->id . $rn;
	}
	if ($mime_header->disposition) {
	    $disposition = $mime_header->disposition;
    	    $contentdisp = 'Content-Disposition: ' . $disposition->name;
    	    if ($disposition->getProperty('filename')) {
        	$contentdisp .= '; filename="'.
        	    encodeHeader($disposition->getProperty('filename')). '"';
    	    }
    	    $header[] = $contentdisp . $rn;       
	}
	if ($mime_header->md5) {
    	    $header[] .= 'Content-MD5: ' . $mime_header->md5 . $rn;
	}
	if ($mime_header->language) {
    	    $header[] .= 'Content-Language: ' . $mime_header->language . $rn;
	}

	$cnt = count($header);
	$hdr_s = '';
	for ($i = 0 ; $i < $cnt ; $i++)	{
    	    $hdr_s .= $this->foldLine($header[$i], 78,str_pad('',4));
	}
	$header = $hdr_s;
	$header .= $rn; /* One blank line to separate mimeheader and body-entity */
	return $header;
    }    

    function prepareRFC822_Header($rfc822_header, $reply_rfc822_header, &$raw_length) {
        $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        $SERVER_NAME = $_SERVER['SERVER_NAME'];
        $REMOTE_PORT = $_SERVER['REMOTE_PORT'];
        if(isset($_SERVER['REMOTE_HOST'])) {
            $REMOTE_HOST = $_SERVER['REMOTE_HOST'];
        }
        if(isset($_SERVER['HTTP_VIA'])) {
            $HTTP_VIA = $_SERVER['HTTP_VIA'];
        }
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
	global $version, $username;
	$rn = "\r\n";
	/* This creates an RFC 822 date */
	$date = date("D, j M Y H:i:s ", mktime()) . $this->timezone();
	/* Create a message-id */
	$message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
	$message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';
	/* Make an RFC822 Received: line */
	if (isset($REMOTE_HOST)) {
    	    $received_from = "$REMOTE_HOST ([$REMOTE_ADDR])";
	} else {
    	    $received_from = $REMOTE_ADDR;
	}
	if (isset($HTTP_VIA) || isset ($HTTP_X_FORWARDED_FOR)) {
    	    if ($HTTP_X_FORWARDED_FOR == '') {
        	$HTTP_X_FORWARDED_FOR = 'unknown';
    	    }
    	    $received_from .= " (proxying for $HTTP_X_FORWARDED_FOR)";
	}
	$header = array();
	$header[] = "Received: from $received_from" . $rn;
        $header[] = "        (SquirrelMail authenticated user $username)" . $rn;
        $header[] = "        by $SERVER_NAME with HTTP;" . $rn;
	$header[] = "        $date" . $rn;
        /* Insert the rest of the header fields */
        $header[] = 'Message-ID: '. $message_id . $rn;
        if ($reply_rfc822_header->message_id) {
	    $rep_message_id = $reply_rfc822_header->message_id;
//	    $this->strip_crlf($message_id);
	    $header[] = 'In-Reply-To: '.$rep_message_id . $rn;
    	    $references = $this->calculate_references($reply_rfc822_header);
	    $header[] = 'References: '.$references . $rn;
	}	
	$header[] = "Date: $date" . $rn;
        $header[] = 'Subject: '.encodeHeader($rfc822_header->subject) . $rn;
        $header[] = 'From: '. encodeHeader($rfc822_header->getAddr_s('from')) . $rn;
	/* RFC2822 if from contains more then 1 address */	
        if (count($rfc822_header->from) > 1) {
	    $header[] = 'Sender: '. encodeHeader($rfc822_header->getAddr_s('sender')) . $rn;
	}
	if (count($rfc822_header->to)) {
	    $header[] = 'To: '. encodeHeader($rfc822_header->getAddr_s('to')) . $rn;
        }
	if (count($rfc822_header->cc)) {
	    $header[] = 'Cc: '. encodeHeader($rfc822_header->getAddr_s('cc')) . $rn;
	}
	if (count($rfc822_header->reply_to)) {
	    $header[] = 'Reply-To: '. encodeHeader($rfc822_header->getAddr_s('reply_to')) . $rn;
	}
	/* Sendmail should return true. Default = false */
	$bcc = $this->getBcc();
	if (count($rfc822_header->bcc)) {
	    $s = 'Bcc: '. encodeHeader($rfc822_header->getAddr_s('bcc')) . $rn;
	    if (!$bcc) {
	       $s = $this->foldLine($s, 78, str_pad('',4));
	       $raw_length += strlen($s);
	    } else {
	       $header[] = $s;
	    }
	}
	/* Identify SquirrelMail */	
	$header[] = "X-Mailer: SquirrelMail (version $version)" . $rn; 
	/* Do the MIME-stuff */
	$header[] = "MIME-Version: 1.0" . $rn;
	$contenttype = 'Content-Type: '. $rfc822_header->content_type->type0 .'/'.
                                         $rfc822_header->content_type->type1;
	if (count($rfc822_header->content_type->properties)) {
    	    foreach ($rfc822_header->content_type->properties as $k => $v) {
	        if ($k && $v) {
        	    $contenttype .= ';' .$k.'='.$v; 
		}
    	    }
	}
        $header[] = $contenttype . $rn;
        if ($rfc822_header->dnt) {
	    $dnt = $rfc822_header->getAddr_s('dnt'); 
    	    /* Pegasus Mail */
    	    $header[] = 'X-Confirm-Reading-To: '.$dnt. $rn;
    	    /* RFC 2298 */
    	    $header[] = 'Disposition-Notification-To: '.$dnt. $rn;
	}
	if ($rfc822_header->priority) {
    	    $prio = $rfc822_header->priority;
	    $header[] = 'X-Priority: '.$prio. $rn;
    	    switch($prio) {
    	      case 1: $header[] = 'Importance: High'. $rn; break;
    	      case 3: $header[] = 'Importance: Normal'. $rn; break;
    	      case 5: $header[] = 'Importance: Low'. $rn; break;
	      default: break;
    	    }
	}
	/* Insert headers from the $more_headers array */	
	if(count($rfc822_header->more_headers)) {
    	    reset($rfc822_header->more_headers);
    	    foreach ($rfc822_header->more_headers as $k => $v) {
    		$header[] = $k.': '.$v .$rn;
    	    }	       
	}        
	$cnt = count($header);
	$hdr_s = '';
	for ($i = 0 ; $i < $cnt ; $i++) {
    	    $hdr_s .= $this->foldLine($header[$i], 78, str_pad('',4));
	}
//	$debug = "Debug: <123456789012345678901234567890123456789012345678901234567890123456789>\r\n";
//	$this->foldLine($debug, 78, str_pad('',4));
	$header = $hdr_s;
	$header .= $rn; /* One blank line to separate header and body */
	$raw_length += strlen($header);
	return $header;
    }

    /*
    * function for cleanly folding of headerlines
    */
    function foldLine($line, $length, $pre='') {
        $line = substr($line,0, -2);
	$length -= 2; /* don not fold between \r and \n */
	$cnt = strlen($line);
	$res = '';
	$fold=false;
	if ($cnt > $length) {
	    $fold_string = "\r\n " . $pre;
	    if ($fold) {
	      $length -=(strlen($fold_string)+2);
	    }  
    	    for ($i=0;$i<($cnt-$length);$i++) {
        	$fold_pos = 0;
		/* first try to fold at delimiters */
        	for ($j=($i+$length); $j>$i; --$j) {
		    switch ($line{$j}) {
	    	      case (','):
	    	      case (';'): $fold_pos = $i = $j; break;
	    	      default: break;
	    	    }
		}
		if (!$fold_pos) { /* not succeed yet so we try at spaces & = */
            	    for ($j=($i+$length); $j>$i; $j--) {
                	switch ($line{$j}) {
	        	  case (' '):
	        	  case ('='): $fold_pos = $i = $j; break;
	        	  default: break;
	        	}
	    	    }
		}
		if (!$fold_pos) { /* clean folding didn't work */
	    	    $i = $j = $fold_pos = $i+$length;
		}
		$line = substr_replace($line,$line{$fold_pos}.$fold_string,
		                       $fold_pos,1);
		$cnt += strlen($fold_string);
		if (!$fold) {
	    	    $length -=(strlen($fold_string)+2);
		}  
	    	$fold = true;
		$i = $j + strlen($fold_string)+1;
    	    }	    
	}
	/* debugging code
	$debug = $line;
	$debug = str_replace("\r","\\r", $debug);
	$debug = str_replace("\n","\\n", $debug);
	*/
	return $line."\r\n";
    }	   


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
	} else {
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

    function calculate_references($hdr) {
        $refer = $hdr->references;
	$message_id = $hdr->message_id;
	$in_reply_to = $hdr->in_reply_to;
	if (strlen($refer) > 2) {
    	    $refer .= ' ' . $message_id;
	} else {
    	    if ($in_reply_to) {
        	$refer .= $in_reply_to . ' ' . $message_id;
    	    } else {
        	$refer .= $message_id;
    	    }                        
	}
	trim($refer);
	return $refer;
    }
}
?>
