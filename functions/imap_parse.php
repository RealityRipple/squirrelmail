<?php

function sqimap_parse_RFC822Header ($read, $hdr) {
  $i = 0;
  /* Set up some defaults */
  $hdr->type0 = "text";
  $hdr->type1 = "plain";
  $hdr->charset = "us-ascii";

  $count = count($read);
  while ($i < $count) {
     /* unfold multi-line headers */
     while (($i + 1 < $count) && (strspn($read[$i + 1], "\t ") > 0) ) {
        $read[$i + 1] = substr($read[$i], 0, -2) . ' ' . ltrim($read[$i+1]);
        array_splice($read, $i, 1);
        $count--;
     }
     $line = $read[$i];
     $c = strtolower($line{0});
     switch ($c) {
       case 'm':
         $c2 = strtolower($line{1});
         switch ($c2) {
           case 'i':
             if (substr($line, 0, 17) == "MIME-Version: 1.0") {
	        $hdr->mime = true;
             }
	     $i++;
             break;
           case 'e': 
             /* MESSAGE ID */
             if (strtolower(substr($line, 0, 11)) == "message-id:") {
	        $hdr->message_id = trim(substr($line, 11));
    	     }
	     $i++;
	     break;
	  default:
	     $i++;    
	     break;
	 }   
	 break;
       case 'c':
         $c2 = strtolower($line{1});
         switch ($c2) {
	   case 'o': 
	     /* Content-Transfer-Encoding */
    	     if (substr(strtolower($line), 0, 26) == "content-transfer-encoding:") {
        	$hdr->encoding = strtolower(trim(substr($line, 26)));
        	$i++;
    	     }
    	     /* Content-Type */
    	     else if (strtolower(substr($line, 0, 13)) == "content-type:") {
    		$cont = strtolower(trim(substr($line, 13)));
        	if (strpos($cont, ";")) {
            	   $cont = substr($cont, 0, strpos($cont, ";"));
        	}
        	if (strpos($cont, "/")) {
            	   $hdr->type0 = substr($cont, 0, strpos($cont, "/"));
            	   $hdr->type1 = substr($cont, strpos($cont, "/")+1);
        	} else {
            	   $hdr->type0 = $cont;
        	}
        	$line = $read[$i];
        	$i++;
        	while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
            	   str_replace("\n", "", $line);
            	   str_replace("\n", "", $read[$i]);
            	   $line = "$line $read[$i]";
            	   $i++;
        	}

        	/* Detect the boundary of a multipart message */
        	if (eregi('boundary="([^"]+)"', $line, $regs)) {
            	   $hdr->boundary = $regs[1];
        	}

        	/* Detect the charset */
        	if (strpos(strtolower(trim($line)), "charset=")) {
            	   $pos = strpos($line, "charset=") + 8;
            	   $charset = trim($line);
            	   if (strpos($line, ";", $pos) > 0) {
                      $charset = substr($charset, $pos, strpos($line, ";", $pos)-$pos);
            	   } else {
                      $charset = substr($charset, $pos);
            	   }
            	   $charset = str_replace("\"", "", $charset);
            	   $hdr->charset = $charset;
        	} else {
            	   $hdr->charset = "us-ascii";
        	}
		/* Detect type in case of multipart/related */
		if (strpos(strtolower(trim($line)), "type=")) {
		   $pos = strpos($line, "type=") + 6;
		   $type = trim($line);
            	   if (strpos($line, ";", $pos) > 0) {
                      $type = substr($type, $pos, strpos($line, ";", $pos)-$pos);
            	   } else {
                      $type = substr($type, $pos);
            	   }
		   $hdr->type = $type;
		}
    	     }
    	     else if (strtolower(substr($line, 0, 20)) == "content-disposition:") {
                /* Add better content-disposition support */
                $i++;
                while ( (substr(substr($read[$i], 0, strpos($read[$i], " ")), -1) != ":") && (trim($read[$i]) != "") && (trim($read[$i]) != ")")) {
                   str_replace("\n", "", $line);
                   str_replace("\n", "", $read[$i]);
                   $line = "$line $read[$i]";
                   $i++;
        	}

        	/* Detects filename if any */
        	if (strpos(strtolower(trim($line)), "filename=")) {
            	   $pos = strpos($line, "filename=") + 9;
            	   $name = trim($line);
            	   if (strpos($line, " ", $pos) > 0) {
                      $name = substr($name, $pos, strpos($line, " ", $pos));
            	   } else {
                      $name = substr($name, $pos);
            	   }
            	   $name = str_replace("\"", "", $name);
            	   $hdr->filename = $name;
        	}
    	     } else $i++;
	     break;
	   case 'c': /* Cc */
    	     if (strtolower(substr($line, 0, 3)) == "cc:") {
        	$hdr->cc = sqimap_parse_address(trim(substr($line, 3, strlen($line) - 4)), true);
    	     }
	     $i++;
	     break;
	   default:
	     $i++;    
	     break;
	 }
         break;
       case 'r': /* Reply-To */
         if (strtolower(substr($line, 0, 9)) == "reply-to:") {
            $hdr->replyto = sqimap_parse_address(trim(substr($line, 9, strlen($line) - 10)), false);
    	 }
	 $i++;
	 break;
       case 'f': /* From */
    	 if (strtolower(substr($line, 0, 5)) == "from:") {
	    $hdr->from=sqimap_parse_address(trim(substr($line, 5, strlen($line) - 6)), false);
    	    if (! isset($hdr->replyto) || $hdr->replyto == "") {
               $hdr->replyto = $hdr->from;
            }
    	 }
	 $i++;
	 break;
       case 'd':
         $c2 = strtolower($line{1});
	 switch ($c2) {
	   case 'a': /* Date */
             if (strtolower(substr($line, 0, 5)) == "date:") {
                $d = substr($read[$i], 5);
                $d = trim($d);
                $d = strtr($d, array('  ' => ' '));
                $d = explode(' ', $d);
                $hdr->date = getTimeStamp($d);
             }
	     $i++;
             break;
	   case 'i': /* Disposition-Notification-To */
             if (strtolower(substr($line, 0, 28)) == "disposition-notification-to:") {
	        $dnt = trim(substr($read[$i], 28));
	        $hdr->dnt = sqimap_parse_address($dnt, false);
	     }
	     $i++;
	     break;
	   default:
	     $i++;
	     break;
	 }
	 break;		      
       case 's':
         /* SUBJECT */
         if (strtolower(substr($line, 0, 8)) == "subject:") {
            $hdr->subject = trim(substr($line, 8, strlen($line) - 9));
            if (strlen(Chop($hdr->subject)) == 0) {
               $hdr->subject = _("(no subject)");
            }
         }
	 $i++;
         break;
       case 'b':
         /* BCC */
         if (strtolower(substr($line, 0, 4)) == "bcc:") {
            $hdr->bcc = sqimap_parse_address(trim(substr($line, 4, strlen($line) - 5)), true);
         }
	 $i++;
         break;
       case 't':
         /* TO */
        if (strtolower(substr($line, 0, 3)) == "to:") {
           $hdr->to = sqimap_parse_address(trim(substr($line, 3, strlen($line) - 4)), true);
        }
	$i++;
        break;
      case ')':
        /* ERROR CORRECTION */
        if (strlen(trim($hdr->subject)) == 0) {
           $hdr->subject = _("(no subject)");
        }
        if (!is_object($hdr->from) && strlen(trim($hdr->from)) == 0) {
           $hdr->from = _("(unknown sender)");
        }
        if (strlen(trim($hdr->date)) == 0) {
           $hdr->date = time();
        }
        $i++;
        break;
      case 'x':
        /* X-PRIORITY */
        if (strtolower(substr($line, 0, 11)) == 'x-priority:') {
    	   $hdr->priority = trim(substr($line, 11));
        } else if (strtolower(substr($line,0,9)) == 'x-mailer:') {
    	   $hdr->xmailer = trim(substr($line, 9));
	}
        $i++;
	break;
     case 'u':
         /* User-Agent */
         if (strtolower(substr($line,0,10)) == 'user-agent') {
             $hdr->xmailer = trim(substr($line, 10));
         }
         $i++;
         break;
      default:
        $i++;
        break;
     }
  }
  return $hdr;
}

/**
 * function to process addresses.
 */
function sqimap_parse_address($address, $ar, $addr_ar = array(), $group = '') {
  $pos = 0;
  $j = strlen( $address );
  $name = '';
  $addr = '';

  while ( $pos < $j ) {
     if ($address{$pos} == '"') { /* get the personal name */
        $pos++;
        while ( $address{$pos} != '"' &&
                $pos < $j ) {
           if (substr($address, $pos, 2) == '\\"') {
	      $name .= $address{$pos};
              $pos++;
           } elseif (substr($address, $pos, 2) == '\\\\') {
	      $name .= $address{$pos};
              $pos++;
           }
           $name .= $address{$pos};
           $pos++;
        }
     } elseif ($address{$pos} == '<') { /* get email address */
        $addr_start=$pos;
        $pos++;
        while ( $address{$pos} != '>' &&
                $pos < $j ) {
	   $addr .= $address{$pos};	
           $pos++;
        }
     } elseif ($address{$pos} == '(') { /* rip off comments */
        $addr_start=$pos;
        $pos++;
        while ( $address{$pos} != ')' &&
                $pos < $j ) {
	   $addr .= $address{$pos};	
           $pos++;
        }
	$address_start = substr($address,0,$addr_start);
	$address_end = substr($address,$pos+1);
	$address = $address_start . $address_end; 
	$j = strlen( $address );
	$pos = $addr_start;
     } elseif ( $address{$pos} == ',' ) { /* we reached a delimiter */
        if ($addr == '') {
	    $addr = substr($address,0,$pos);
	} elseif ($name == '') {
	    $name = substr($address,0,$addr_start);
	}
	$at = strpos($addr, '@');
	$addr_structure = new AddressStructure();
	$addr_structure->personal = $name;
	$addr_structure->group = $group;

	if ($at) {
	    $addr_structure->mailbox = substr($addr,0,$at);
	    $addr_structure->host = substr($addr,$at+1);
	} else  {
	    $addr_structure->mailbox = $addr;
	}
	$address = substr($address,$pos+1);
	$j = strlen( $address );
	$pos = 0;
        $name = '';
        $addr = '';
        $addr_ar[] = $addr_structure;
	
     } elseif ( $address{$pos} == ":" ) { /* process the group addresses */
        /* group marker */
        $group = substr($address,0,$pos);
	$address = substr($address,$pos+1);
        $result = sqimap_parse_address($address, $ar, $addr_ar, $group);
	$addr_ar = $result[0];
	$pos = $result[1];
	$address = substr($address,$pos);
	$j = strlen( $address );	
	$group = '';
     } elseif ($address{$pos} == ';' && $group ) {
        $address = substr($address, 0, $pos-1);
        break;
     }	     
     $pos++;
  }
  if ($addr == '') {
     $addr = substr($address,0,$pos);
  } elseif ($name == '') {
     $name = substr($address,0,$addr_start);
  }
  $at = strpos($addr, '@');
  $addr_structure = new AddressStructure();
  $addr_structure->group = $group;
  if ($at) {
     $addr_structure->mailbox = trim(substr($addr,0,$at));
     $addr_structure->host = trim(substr($addr,$at+1));
  } else {
     $addr_structure->mailbox = trim($addr);
  }

  if ($group && $addr == '') { /* no addresses found in group */
     $name = "$group: Undisclosed recipients;";
     $addr_structure->personal = $name;
     $addr_ar[] = $addr_structure;     
     return (array($addr_ar,$pos+1)); 
  } else {
      $addr_structure->personal = $name;
      if ($name || $addr) {
         $addr_ar[] = $addr_structure;
      } 
  }
  if ($ar) {
    return ($addr_ar);
  } else {
    return ($addr_ar[0]);
  }          

}
?>
