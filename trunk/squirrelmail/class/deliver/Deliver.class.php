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
	  $rfc822_header->contenttype->properties['boundary']=$boundary;
       } else {
          $boundary='';
       }
       $header = $this->prepareRFC822_Header($rfc822_header);
       $raw_length = strlen($header);
       if ($stream) {
            $this->preWriteToStream($header);
            $this->writeToStream($stream, $header);
       } else {
       	        /* DEBUG */
        	$out =  htmlspecialchars($header);
		$out = str_replace("\r\n",'<BR>',$out);
		echo $out;
       }
       $this->writeBody($message, $stream, $raw_length, $boundary);
       exit;
       return $raw_length;
       
    }
    
    function writeBody($message, $stream, &$length_raw, $boundary='') {
        if ($boundary) {
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
	for ($i=0, $entCount=count($message->entities);$i<$entCount;$i++) {
    	    $msg = $this->writeBody($message->entities[$i], $stream, $length_raw, $boundary);
	}
        if ($boundary) {
	    $s = '--'.$boundary."--\r\n";
	    $length_raw += strlen($s);
	    if ($stream) {
                $this->preWriteToStream($s);
		$this->writeToStream($stream, $s);
	    }
	}
    }

    function writeBodyPart($message, $stream, &$length) {
	switch ($message->type0) {
	case 'text':
	case 'message':
	    if ($message->body_part) {
	       $body_part = $message->body_part;
	       $length += $this->clean_crlf($body_part);
	       if ($stream) {
                  $this->preWriteToStream($body_part);     
	          $this->writeToStream($stream, $body_part);
		   } else {
		        /* DEBUG */
		       	$out =  htmlspecialchars($tmp);
			$out = str_replace("\r\n",'<BR>',$out);
		        echo $out;

	       }
	    } elseif ($message->att_local_name) {
	        $filename = $message->att_local_name;
		$file = fopen ($filename, 'rb');
		while ($tmp = fgets($file, 4096)) {
		   $length += $this->clean_crlf($tmp);
		   if ($stream) {
		      $this->preWriteToStream($tmp);
		      $this->writeToStream($stream, $tmp);
		   } else {
		        /* DEBUG */
		       	$out =  htmlspecialchars($tmp);
			$out = str_replace("\r\n",'<BR>',$out);
		        echo $out;
                   }
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
	       } else {
	            /* DEBUG */
	       	    $out =  htmlspecialchars($body_part);
		    $out = str_replace("\r\n",'<BR>',$out);
		    echo $out;
    	       }
	    } elseif ($message->att_local_name) {
	        $filename = $message->att_local_name;
		$file = fopen ($filename, 'rb');
		while ($tmp = fread($file, 1520)) {
		   $encoded = chunk_split(base64_encode($tmp));
		   $length += strlen($encoded);
		   if ($stream) {
		      $this->writeToStream($stream, $encoded);
		   } else {
		        /* DEBUG */		   
		       	$out =  htmlspecialchars($encoded);
			$out = str_replace("\r\n",'<BR>',$out);
		        echo $out;
		   }
		}
		fclose($file);
	    }
	    break;
	}
    }
    
    function clean_crlf(&$s) {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        $s = str_replace("\n", "\r\n", $s);
	return strlen($s);
    }

    function preWriteToStream(&$s) {
    }
    
    function writeToStream($stream, $data) {
    }
    
    function initStream($message, $length=0, $host='', $port='', $user='', $pass='') {
       return $stream;
    }
    
    function getBcc($bcc) {
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
    	    $contenttype .= ";\r\n " . 'name="'.
        	encodeHeader($mime_header->parameters['name']). '"';
	}
	$header[] = $contenttype . $rn;
	if ($mime_header->description) {
    	    $header[] .= 'Content-Description: ' . $mime_header->description . $rn;
	}
	if ($mime_header->encoding) {
    	    $header[] .= 'Content-Transfer-Encoding: ' . $mime_header->encoding . $rn;
	}
	if ($mime_header->id) {
    	    $header[] .= 'Content-ID: ' . $mime_header->id . $rn;
	}
	if ($mime_header->disposition) {
    	    $contentdisp .= 'Content-Disposition: ' . $mime_header->disposition;
    	    if (isset($mime_header->parameters['filename'])) {
        	$contentdisp .= ";\r\n " . 'filename="'.
        	    encodeHeader($mime_header->parameters['filename']). '"';
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
    	    $hdr_s .= $this->foldLine($header[$i], 78, '    ');
	}
	$header = $hdr_s;
	$header .= $rn; /* One blank line to separate mimeheader and body-entity */
	return $header;
    }    

    function prepareRFC822_Header($rfc822_header) {
	global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
	global $version, $useSendmail, $username;
	global $HTTP_VIA, $HTTP_X_FORWARDED_FOR;
	global $REMOTE_HOST;
	$rn = "\r\n";
	/* This creates an RFC 822 date */
	$date = date("D, j M Y H:i:s ", mktime()) . $this->timezone();
	/* Create a message-id */
	$message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
	$message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';
	$old_message_id = $rfc822_header->message_id;
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
        if ($old_message_id) {
	    $header[] = 'In-Reply-To: '.$old_message_id . $rn;
    	    $references = $this->calculate_references($rfc822_header->references,
	               $old_message_id, $rfc822_header->in_reply_to);
		       
	    $header[] = 'References: '.$references . $rn;
	}	
	$header[] = "Date: $date" . $rn;
        $header[] = 'Subject: '.encodeHeader($rfc822_header->subject) . $rn;
        $header[] = 'From: '. encodeHeader($rfc822_header->getAddr_s('from')) . $rn;
	/* RFC2822 if from contains more then 1 address */	
        if (count($rfc822_header->from) > 1) {
	    $header[] = 'Sender: '. encodeHeader($rfc822_header->getAddr_s('sender')) . $rn;
	}
	$header[] = 'To: '. encodeHeader($rfc822_header->getAddr_s('to')) . $rn;    // Who it's TO
	if (count($rfc822_header->cc)) {
	    $header[] = 'Cc: '. encodeHeader($rfc822_header->getAddr_s('cc')) . $rn;
	}
	if (count($rfc822_header->reply_to)) {
	    $header[] = 'Reply-To: '. encodeHeader($rfc822_header->getAddr_s('reply_to')) . $rn;
	}
	/* Sendmail should return true. Default = false */
	$bcc = $this->getBcc($rfc822_header->bcc);
	if ($bcc && count($rfc822_header->bcc)) {
	    $header[] = 'Bcc: '. encodeHeader($rfc822_header->getAddr_s('bcc')) . $rn;
	}
	/* Identify SquirrelMail */	
	$header[] = "X-Mailer: SquirrelMail (version $version)" . $rn; 
	/* Do the MIME-stuff */
	$header[] = "MIME-Version: 1.0" . $rn;
	$contenttype = 'Content-Type: '. $rfc822_header->content_type->type0 .'/'.
                                         $rfc822_header->content_type->type1;
	if (count($rfc822_header->content_type->properties)) {
    	    foreach ($rfc822_header->contenttype->properties as $k => $v) {
        	$contenttype .= ';'. "\r\n " .$k.'='.$v; /* FOLDING */
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
    	    $hdr_s .= $this->foldLine($header[$i], 78, '    ');
	}
	$header = $hdr_s;
	$header .= $rn; /* One blank line to separate header and body */
	return $header;
    }

    /*
    * function for cleanly folding of headerlines
    */
    function foldLine($line, $length, $pre) {
    $cnt = strlen($line);
    $res = '';
    if ($cnt > $length)
    {
        $fold_string = $pre.' '."\r\n";
        for ($i=0;$i<($cnt-$length);$i++)
	{
            $fold_pos = 0;
	    /* first try to fold at delimiters */
            for ($j=($i+$length); $j>$i; $j--)
            {
                switch ($line{$j})
	        {
	        case (','):
	        case (';'):
                    $fold_pos = $j;
		    break;
	        default:
	            break;
	        }
		if ($fold_pos)
		{
		    $j=$i;
		}
	    }
	    if (!$fold_pos)
	    {
                /* not succeed yet so we try at spaces and = */
                for ($j=($i+$length); $j>$i; $j--)
                {
                    switch ($line{$j})
	            {
	            case (' '):
	            case ('='):
                        $fold_pos = $j;
		        break;
	            default:
	                break;
	            }
		    if ($fold_pos)
		    {
		        $j=$i;
		    }
	        }
	    }
	    if (!$fold_pos)
	    {
	       /* clean folding didn't work */
	       $fold_pos = $i+$length;
	    }
	    $line = substr_replace($line,$line{$fold_pos}.$fold_string,$fold_pos,1);
	    $cnt += strlen($fold_string);
	    $i = $j + strlen($fold_string);
        }	    
    }
    return $line;
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

    function calculate_references($refer, $old_message_id, $old_in_reply_to) {
	if (strlen($refer) > 2) {
    	    $refer .= ' ' . $old_message_id;
	} else {
    	    if ($old_in_reply_to) {
        	$refer .= $old_in_reply_to . ' ' . $old_message_id;
    	    } else {
        	$refer .= $old_message_id;
    	    }                        
	}
	trim($refer);
	return $refer;
    }
}
?>
