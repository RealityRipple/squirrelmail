<?php

/**
 * mime.class
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *
 * This contains functions needed to handle mime messages.
 *
 * $Id$
 */

class msg_header {
    /** msg_header contains generic variables for values that **/
    /** could be in a header.                                 **/

    var $type0 = '', $type1 = '', $boundary = '', $charset = '',
        $encoding='', $size = 0, $to = array(), $from = '', $date = '',
        $cc = array(), $bcc = array(), $reply_to = '', $subject = '',
        $id = 0, $mailbox = '', $description = '', $filename = '',
        $entity_id = 0, $message_id = 0, $name = '', $priority = 3, $type = '',
	$disposition = '', $md5='', $language='',$dnt = '', $xmailer = '';
    
    /* 
     * returns addres_list of supplied argument
     * arguments: array('to', 'from', ...) or just a string like 'to'.
     * result: string: address1, addres2, ....
     */ 
    
    function setVar($var, $value) {
        $this->{$var} = $value;
    }
    /* 
    * function to get the addres strings out of the header.
    * Arguments: string or array of strings !
    * example1: header->getAddr_s('to').
    * example2: header->getAddr_s(array('to','cc','bcc'))
    */
    function getAddr_s($arr) {
	if (is_array($arr)) {
	    $s = '';
	    foreach($arr as $arg ) {
	       $result = $this->getAddr_s($arg);
	       if ($result) {
	          $s .= ', ' . $result;
	       }
	    }
	    if ($s) $s = substr($s,2);
	    return $s;
	} else {
	    $s = '';
	    eval('$addr = $this->'.$arr.';') ;
	    if (is_array($addr)) {
               foreach ($addr as $addr_o) {
                  if (is_object($addr_o)) {
                     $s .= $addr_o->getAddress() . ', ';
                  }
               }
	       $s = substr($s,0,-2);
	    } else {
	       if (is_object($addr)) {
	          $s .= $addr->getAddress();
	       }
	    }
	    return $s;
	}
    }
    
    function getAddr_a($arg, $excl_arr=array(), $arr = array()) {
	if (is_array($arg)) {
	    foreach($arg as $argument ) {
	       $arr = $this->getAddr_a($argument, $excl_arr, $arr);
	    }
	    return $arr;
	} else {
	    eval('$addr = $this->'.$arg.';') ;
	    if (is_array($addr)) {
               foreach ($addr as $addr_o) {
                  if (is_object($addr_o)) {
    		     if (isset($addr_o->host) && $addr_o->host !='') {
    			$email = $addr_o->mailbox.'@'.$addr_o->host;
    		     } else {
        	        $email = $addr_o->mailbox;
    		     }
		     $email = strtolower($email);
		     if ($email && !isset($arr[$email]) && !isset($excl_arr[$email])) {
		        $arr[$email] = $addr_o->personal;
		     }
                  }
               }
	    } else {
	       if (is_object($addr)) {
    		  if (isset($addr->host)) {
    		     $email = $addr->mailbox.'@'.$addr->host;
    		  } else {
        	     $email = $addr->mailbox;
    		  }
		  $email = strtolower($email);		  
		  if ($email && !isset($arr[$email]) && !isset($excl_arr[$email])) {
		     $arr[$email] = $addr->personal;
		  }
	       }
	    }
	    return $arr;
	}
    }
}

class address_structure {
   var $personal = '', $adl = '', $mailbox = '', $host = '', $group = '';

   function getAddress($full=true) {
     if (is_object($this)) {
        if (isset($this->host) && $this->host !='') {
    	   $email = '<'.$this->mailbox.'@'.$this->host.'>';
        } else {
           $email = $this->mailbox;
        }
        if (trim($this->personal) !='') {
	   if ($email) {
        	$addr = '"' . $this->personal . '" ' .$email;
	   } else {
	        $addr = $this->personal;
	   }
	   $best_dpl = $this->personal;
        } else {
           $addr = $email;
	   $best_dpl = $email;
        }
	if ($full) {
    	   return $addr;
	} else {
	   return $best_dpl;
	}    
     } else return '';
   }
}

class message {
    /** message is the object that contains messages.  It is a recursive
      object in that through the $entities variable, it can contain
      more objects of type message.  See documentation in mime.txt for
      a better description of how this works.
    **/
    var $header = '', $entities = array(), $mailbox = 'INBOX', $id = 0,
        $envelope = '', $parent_ent, $entity, $type0='', $type1='',
	$parent = '', $decoded_body='',
	$is_seen = 0, $is_answered = 0, $is_deleted = 0, $is_flagged = 0,
	$is_mdnsent = 0;

    function setEnt($ent) {
        $this->entity_id= $ent;
    }
    
    function setBody($body) {
        $this->decoded_body = $body;
    }
    function addEntity ($msg) {
        $msg->parent = &$this;
        $this->entities[] = $msg;
    }

    function addRFC822Header($read) {
	$header = new msg_header();
	$this->header = sqimap_parse_RFC822Header($read,$header);
    }

    function getEntity($ent) {
        
        $cur_ent = $this->entity_id;
	$msg = $this;
	if ($cur_ent == '' || $cur_ent == '0') {
	   $cur_ent_a = array();
	} else {
	   $cur_ent_a = explode('.',$this->entity_id);
	}
	$ent_a = explode('.',$ent);
        
	$cnt = count($ent_a);

	for ($i=0;$i<$cnt -1;$i++) {
	   if (isset($cur_ent_a[$i]) && $cur_ent_a[$i] != $ent_a[$i]) {
	      $msg = $msg->parent;
	      $cur_ent_a = explode('.',$msg->entity_id);
	      $i--;
           } else if (!isset($cur_ent_a[$i])) {
	      if (isset($msg->entities[($ent_a[$i]-1)])) {
	        $msg = $msg->entities[($ent_a[$i]-1)];
	      } else {
	        $msg = $msg->entities[0];
	      }
	   }
	   if ($msg->type0 == 'message' && $msg->type1 == 'rfc822') { 
	      /*this is a header for a message/rfc822 entity */
	      $msg = $msg->entities[0];
	   }
	}

	if ($msg->type0 == 'message' && $msg->type1 == 'rfc822') { 
	   /*this is a header for a message/rfc822 entity */
	   $msg = $msg->entities[0];
	}

	if (isset($msg->entities[($ent_a[$cnt-1])-1])) {
	    $msg = $msg->entities[($ent_a[$cnt-1]-1)];
        }

        return $msg;
    }
    
    function getMailbox() {
      $msg = $this;
      while (is_object($msg->parent)) {
          $msg = $msg->parent;
      }
      return $msg->mailbox;
    }
    
    /* 
     * Bodystructure parser, a recursive function for generating the 
     * entity-tree with all the mime-parts.
     * 
     * It follows RFC2060 and stores all the described fields in the
     * message object. 
     *
     * Question/Bugs:
     * 
     * Ask for me (Marc Groot Koerkamp, stekkel@users.sourceforge.net.
     *
     */
    function &parseStructure($read, $i=0, $message = false) {
      $arg_no = 0;
      $arg_a = array();
      $cnt = strlen($read);
      while ($i < $cnt) {
	$char = strtoupper($read{$i});   
        switch ($char) {
	case '(':
           if ($arg_no == 0 ) {
	      if (!isset($msg)) {
	         $msg = new message();
		 $hdr = new msg_header();
		 $hdr->type0 = 'text';
		 $hdr->type1 = 'plain';
		 $hdr->encoding = 'us-ascii';
		 
		 if ($this->type0 == 'message' && $this->type1 == 'rfc822') {
		    $msg->entity_id = $this->entity_id .'.0'; /* header of message/rfc822 */
		 } else if (isset($this->entity_id) && $this->entity_id !='')  {
		    $ent_no = count($this->entities)+1;
		    $par_ent = substr($this->entity_id,-2);
		    if ($par_ent{0} == '.') {
		       $par_ent = $par_ent{1};
		    }
		    if ($par_ent == '0') {
			$ent_no = count($this->entities)+1;
			if ($ent_no > 0) {
			   $ent = substr($this->entity_id,0,strrpos($this->entity_id,'.'));
			   if ($ent) {
			      $ent = $ent . ".$ent_no";
			   } else {
			      $ent = $ent_no;
			   }
			   $msg->entity_id = $ent;
			} else {
	    		   $msg->entity_id = $ent_no;
			}   
		    } else {
	    		$ent = $this->entity_id . ".$ent_no";
	    		$msg->entity_id = $ent;
		    }
		 } else { 
		    $msg->entity_id = '0';
		 }
	      } else {
	      	   $msg->header->type0 = 'multipart';
		   $msg->type0 = 'multipart';
	           while ($read{$i} == '(') {
	              $msg->addEntity($msg->parseStructure($read,&$i));
		   }
	      }
	   } else {
	      switch ($arg_no) {
	       case 1:
	         /* multipart properties */
		 $i++;
		 $arg_a[] = $this->parseProperties($read,&$i);
		 $arg_no++;
		 break;
	       case 2:
	         if (isset($msg->type0) &&  $msg->type0 == 'multipart') {
		    $i++;
		    $arg_a[]= $msg->parseDisposition($read,&$i);
		 } else { /* properties */
	            /* properties */
		    $arg_a[] = $msg->parseProperties($read,&$i);
		 }
 	         $arg_no++;
                 break;
	       case 3:
	         if (isset($msg->type0) &&  $msg->type0 == 'multipart') {
		    $i++;
		    $arg_a[]= $msg->parseLanguage($read,&$i);
		 }
	       case 7:
	         if ($arg_a[0] == 'message' && $arg_a[1] == 'rfc822') {

		     $msg->header->type0 = $arg_a[0];
		     $msg->type0 = $arg_a[0];

		     $msg->header->type1 = $arg_a[1];
		     $msg->type1 = $arg_a[1];
		 
		     $msg->parseEnvelope($read,&$i,&$hdr);
		     $i++;
		     while ($i < $cnt && $read{$i} != '(') {
		       $i++;
		     }
	             $msg->addEntity($msg->parseStructure($read,&$i));
		 }
		 break;
	       case 8:
	         $i++;
		 $arg_a[] = $msg->parseDisposition($read,&$i);
		 $arg_no++;    
		 break;
	       case 9:
	         if ($arg_a[0] == 'text' || 
		       ($arg_a[0] == 'message' && $arg_a[1] == 'rfc822')) {
	            $i++;
		    $arg_a[] = $msg->parseDisposition($read,&$i);
		 } else {
		    $i++;
		    $arg_a[] = $msg->parseLanguage($read,&$i);
		 }
		 $arg_no++;    
		 break;
	       case 10:
	         if ($arg_a[0] == 'text' ||
		       ($arg_a[0] == 'message' && $arg_a[1] == 'rfc822')) {
		    $i++;		       
 	    	    $arg_a[] = $msg->parseLanguage($read,&$i);
		 }  else {
		    $msg->parseParenthesis($read,&$i);
		    $arg_a[] = ''; /* not yet desribed in rfc2060 */
		 }
	       	 $arg_no++;
		 break;  
	       default:
	         /* unknown argument, skip this part */
	         $msg->parseParenthesis($read,&$i);
		 $arg_a[] = '';
	       	 $arg_no++;		 
	         break;
	      } /* switch */
	   }
	   break;
	case '"':         
	   /* inside an entity -> start processing */
           $debug = substr($read,$i,20);
	   $arg_s = $msg->parseQuote($read,&$i);
	   $arg_no++;
	   if ($arg_no < 3) $arg_s = strtolower($arg_s); /* type0 and type1 */
	   $arg_a[] = $arg_s;
	   break;
	case 'N':
	   /* probably NIL argument */
	   if (strtoupper(substr($read,$i,4)) == 'NIL ' || 
	       strtoupper(substr($read,$i,4)) == 'NIL)') {
	      $arg_a[] = '';
	      $arg_no++;
	      $i = $i+2;
	   }      
	   break;
	case '{':
	   /* process the literal value */
	   $arg_a[] = $msg->parseLiteral($read,&$i);
	   $arg_no++;
	   break;
	case (is_numeric($read{$i}) ):
	   /* process integers */
	   if ($read{$i} == ' ') break; 
	   $arg_s = $read{$i};;
	   $i++;
	   while (preg_match('/\d+/',$read{$i})) { // != ' ') {
	      $arg_s .= $read{$i};
	      $i++;
	   }
	   $arg_no++;
	   $arg_a[] = $arg_s;
	   break;
	case ')':
	   if (isset($msg->type0) && $msg->type0 == 'multipart') {
	      $multipart = true;
	   } else {
	      $multipart = false;
	   }
	   if (!$multipart) {
	      if ($arg_a[0] == 'text' ||
	           ($arg_a[0] == 'message' && $arg_a[1] == 'rfc822')) {
	         $shifted_args = true;
	      } else { 
	         $shifted_args = false;
	      }
	      $hdr->type0 = $arg_a[0];
	      $hdr->type1 = $arg_a[1];	      

	      $msg->type0 = $arg_a[0];
	      $msg->type1 = $arg_a[1];

	      $arr = $arg_a[2];
	      if (is_array($arr)) {
    	         foreach($arr as $name => $value) {
        	    $hdr->{$name} = $value;
    	         }
	      }
	      $hdr->id = str_replace( '<', '', str_replace( '>', '', $arg_a[3] ) );
	      $hdr->description = $arg_a[4];
	      $hdr->encoding = strtolower($arg_a[5]);
	      $hdr->entity_id = $msg->entity_id;
	      $hdr->size = $arg_a[6];	      		 	      	 	      
	      if ($shifted_args) {
	         $hdr->lines = $arg_a[7];	      
	         if (isset($arg_a[8])) {
		    $hdr->md5 = $arg_a[8];
		 }
	         if (isset($arg_a[9])) {
		    $hdr->disposition = $arg_a[9];
		 }
	         if (isset($arg_a[10])) {
		    $hdr->language = $arg_a[10];
		 }
	      } else {
	         if (isset($arg_a[7])) {
		    $hdr->md5 = $arg_a[7];		    
		 }
	         if (isset($arg_a[8])) {
		    $hdr->disposition = $arg_a[8];
		 }
	         if (isset($arg_a[9])) {
		    $hdr->language = $arg_a[9];
		 }
	      }
	      $msg->header = $hdr;
	      $arg_no = 0;
	      $i++;
              if (substr($msg->entity_id,-2) == '.0' && $msg->type0 !='multipart') {
	         $msg->entity_id++;
	      }
	      return $msg;
	   } else {
	        $hdr->type0 = 'multipart';
	        $hdr->type1 = $arg_a[0];

	        $msg->type0 = 'multipart';
	        $msg->type1 = $arg_a[0];
	        if (isset($arg_a[1])) {
	           $arr = $arg_a[1];
	           if (is_array($arr)) {
    	           foreach($arr as $name => $value) {
        	      $hdr->{$name} = $value;
    	           }
		 }  
                 }
	         if (isset($arg_a[2])) {
		    $hdr->disposition = $arg_a[2];
	         }
	         if (isset($arg_a[3])) {
		    $hdr->language = $arg_a[3];
	         }
		 $msg->header = $hdr;
	         return $msg;
	      }
	default:
	   break;
        } /* switch */
        $i++;
      } /* while */
    } /* parsestructure */
    
    function parseProperties($read, $i) {
      $properties = array();
      $arg_s = '';
      $prop_name = '';
      while ($read{$i} != ')') {
         if ($read{$i} == '"') {
	    $arg_s = $this->parseQuote($read,&$i);
	 } else if ($read{$i} == '{') {
	    $arg_s = $this->parseLiteral($read,&$i);
         }
	 if ($prop_name == '' && $arg_s) {
	    $prop_name = strtolower($arg_s);	 
	    $properties[$prop_name] = '';
	    $arg_s = '';	    
	 } elseif ($prop_name != '' && $arg_s != '') {
	    $properties[$prop_name] = $arg_s;
	    $prop_name = '';
	    $arg_s = '';
	 }
	 $i++;
      }
      return $properties;
    }
    
    function parseEnvelope($read, $i, $hdr) {
        $arg_no = 0;
	$arg_a = array();
	$cnt = strlen($read);
        while ($i< $cnt && $read{$i} != ')') {
	   $i++;
	   $char = strtoupper($read{$i});
	   switch ($char) {
	     case '"':
	       $arg_a[] = $this->parseQuote($read,&$i);
	       $arg_no++;
	       break;
	     case '{':
	       $arg_a[] = $this->parseLiteral($read,&$i);
	       $arg_no++;
	       break;
	     case 'N':   
	       /* probably NIL argument */
	       if (strtoupper(substr($read,$i,3)) == 'NIL') {
	          $arg_a[] = '';
	          $arg_no++;
	          $i = $i+2;
	       }      
	       break;
	     case '(':
	       /* Address structure 
	        * With group support.
		* Note: Group support is useless on SMTP connections
		* because the protocol doesn't support it
		*/
	       $addr_a = array();
	       $group = '';
	       $a=0;
	       while ($i < $cnt && $read{$i} != ')') {
	          if ($read{$i} == '(') {
		     $addr = $this->parseAddress($read,&$i);
		     if ($addr->host == '' && $addr->mailbox != '') { 
		        /* start of group */
		        $group = $addr->mailbox;
			$group_addr = $addr;
			$j = $a;
		     } elseif ($group && $addr->host == '' && $addr->mailbox == '') {
		        /* end group */
			if ($a == $j+1) { /* no group members */
			   $group_addr->group = $group;
			   $group_addr->mailbox = '';
			   $group_addr->personal = "$group: Undisclosed recipients;";
			   $addr_a[] = $group_addr;
			   $group ='';
			}
		     } else {
		        $addr->group = $group;
		        $addr_a[] = $addr;
		     }
		     $a++;
		  }
		  $i++;
	       }
	       $arg_a[] = $addr_a;
	       break;
	     default:
	       break;
	   }
	   $i++;
	}
	if (count($arg_a) > 9) {
	    /* argument 1: date */
            $d = strtr($arg_a[0], array('  ' => ' '));
            $d = explode(' ', $d);
            $hdr->date = getTimeStamp($d);
	    /* argument 2: subject */
	    if (!trim($arg_a[1])) {
	        $arg_a[1]= _("(no subject)");
	    }	
	    $hdr->subject = $arg_a[1];
	    /* argument 3: from */
	    $hdr->from = $arg_a[2][0];
	    /* argument 4: sender */	    
	    $hdr->sender = $arg_a[3][0];
	    /* argument 5: reply-to */
	    $hdr->replyto = $arg_a[4][0];
	    /* argument 6: to */
	    $hdr->to = $arg_a[5];
	    /* argument 7: cc */
	    $hdr->cc = $arg_a[6];
	    /* argument 8: bcc */
	    $hdr->bcc = $arg_a[7];
	    /* argument 9: in-reply-to */
	    $hdr->inreplyto = $arg_a[8];
	    /* argument 10: message-id */
	    $hdr->message_id = $arg_a[9];
	} 
    }
    
    function parseLiteral($read, $i) {
	$lit_cnt = '';
	$i++;
	while ($read{$i} != '}') {
	   $lit_cnt .= $read{$i};
	   $i++;
	}
	$lit_cnt +=2; /* add the { and } characters */
	$s = '';
	for ($j = 0; $j < $lit_cnt; $j++) {
	   $i++;
	   $s .= $read{$i};
	}
	return $s;
    }
    
    function parseQuote($read, $i) {
	$i++;
	$s = '';
	while ($read{$i} != '"') {
	   if ($read{$i} == '\\') {
	       $i++;
	   } 
	   $s .= $read{$i};
	   $i++;
	}
        return $s;
    }
    
    function parseAddress($read, $i) {
	$arg_a = array();
        while ($read{$i} != ')' ) { //&& $i < count($read)) {
	   $char = strtoupper($read{$i});
	   switch ($char) {
	     case '"':
	       $arg_a[] = $this->parseQuote($read,&$i);
	       break;
	     case '{': 
	       $arg_a[] = $this->parseLiteral($read,&$i);
	       break;
	     case 'N':   
	       if (strtolower(substr($read,$i,3)) == 'nil') {
	          $arg_a[] = '';
	          $i = $i+2;
	       }
	       break;
	     default:
	       break;
	   }
	   $i++;
	}
	if (count($arg_a) == 4) {
    	    $adr = new address_structure();
	    $adr->personal = $arg_a[0];
	    $adr->adl = $arg_a[1];
	    $adr->mailbox = $arg_a[2];
	    $adr->host = $arg_a[3];
	} else {
	   $adr = '';
	}
	return $adr;
    }
    
    function parseDisposition($read,&$i) {
        $arg_a = array();
        while ($read{$i} != ')') {
	   switch ($read{$i}) {
	     case '"':
	       $arg_a[] = $this->parseQuote($read,&$i);
	       break;
	     case '{': 
	       $arg_a[] = $this->parseLiteral($read,&$i);
	       break;
	     case '(':
	       $arg_a[] = $this->parseProperties($read,&$i);
	       break;
	     default:
	       break;
	   }
	   $i++;
        }
	if (isset($arg_a[0])) {
	   $disp = new disposition($arg_a[0]);
	   if (isset($arg_a[1])) {
	      $disp->properties = $arg_a[1];
	   }
	}
	if (is_object($disp)) { 
	   return $disp;
	}
    }
    
    function parseLanguage($read,&$i) {
        /* no idea how to process this one without examples */
        $arg_a = array();
        while ($read{$i} != ')') {
	   switch ($read{$i}) {
	     case '"':
	       $arg_a[] = $this->parseQuote($read,&$i);
	       break;
	     case '{': 
	       $arg_a[] = $this->parseLiteral($read,&$i);
	       break;
	     case '(':
	       $arg_a[] = $this->parseProperties($read,&$i);
	       break;
	     default:
	       break;
	   }
	   $i++;
        }
	if (isset($arg_a[0])) {
	   $lang = new language($arg_a[0]);
	   if (isset($arg_a[1])) {
	      $lang->properties = $arg_a[1];
	   }
	}
	if (is_object($lang)) { 
	   return $lang;
	} else {
	   return '';
        }
    }
    
    function parseParenthesis($read,&$i) {
        while ($read{$i} != ')') {
	   switch ($read{$i}) {
	     case '"':
	       $this->parseQuote($read,&$i);
	       break;
	     case '{': 
	       $this->parseLiteral($read,&$i);
	       break;
	     case '(':
	       $this->parseParenthesis($read,&$i);
	       break;
	     default:
	       break;
	   }
	   $i++;
        } 
    }

    function findDisplayEntity ($entity = array(), $alt_order = array('text/plain','text/html')) {
       $found = false;    
       $type = $this->type0.'/'.$this->type1;
       if ( $type == 'multipart/alternative') {
	    $msg = $this->findAlternativeEntity($alt_order);
	    if (count($msg->entities) == 0) {
        	$entity[] = $msg->entity_id;
	    } else {
	        $msg->findDisplayEntity(&$entity, $alt_order);
	    }
	    $found = true;	    
	} else 	if ( $type == 'multipart/related') {
            $msgs = $this->findRelatedEntity();
	    for ($i = 0; $i < count($msgs); $i++) {
	        $msg = $msgs[$i];
		if (count($msg->entities) == 0) {
        	    $entity[] = $msg->entity_id;
		} else {
	    	    $msg->findDisplayEntity(&$entity,$alt_order);
		}
		$found = true;		
	    }
	} else if ( $this->type0 == 'text' &&
             ( $this->type1 == 'plain' ||
               $this->type1 == 'html' ||
	       $this->type1 == 'message') &&
             isset($this->entity_id) ) {
	     if (count($this->entities) == 0) {
	        if (!$this->header->disposition->name == 'attachment') {
        	   $entity[] = $this->entity_id;
		}
	     }
        } 
    	$i = 0;
    	while ( isset($this->entities[$i]) &&  !$found &&
	        !($this->entities[$i]->header->disposition->name 
	        == 'attachment') &&
	        !($this->entities[$i]->type0 == 'message' && 
		  $this->entities[$i]->type1 == 'rfc822' )
		)
	        {
    	    $this->entities[$i]->findDisplayEntity(&$entity, $alt_order);
    	    $i++;
    	}
    
        if ( !isset($entity[0]) ) {
            $entity[]="";
        }
        return( $entity );
    }

    function findAlternativeEntity ($alt_order) {
       /* if we are dealing with alternative parts then we choose the best 
        * viewable message supported by SM.
        */
        $best_view = 0;
        $ent_id = 0;
        $k = 0; 
        for ($i = 0; $i < count($this->entities); $i ++) {
            $type = $this->entities[$i]->header->type0.'/'.$this->entities[$i]->header->type1;
	    if ($type == 'multipart/related') {
	       $type = $this->entities[$i]->header->type;
	    }
	    for ($j = $k; $j < count($alt_order); $j++) {
	        if ($alt_order[$j] == $type && $j > $best_view) {
		    $best_view = $j;
		    $ent_id = $i;
		    $k = $j;
	        }
	    }
        }
        return $this->entities[$ent_id];
    }
    
    function findRelatedEntity () {
        $msgs = array(); 
        for ($i = 0; $i < count($this->entities); $i ++) {
            $type = $this->entities[$i]->header->type0.'/'.$this->entities[$i]->header->type1;
            if ($this->header->type == $type) {
	        $msgs[] = $this->entities[$i];
	    }
        }
        return $msgs;
    }
    
    function getAttachments($exclude_id=array(), $result = array()) {
       if ($this->type0 == 'message' && $this->type1 == 'rfc822') {
          $this = $this->entities[0];
       }
       if (count($this->entities)) {
          foreach ($this->entities as $entity) {
	    $exclude = false;
	    foreach ($exclude_id as $excl) {
               if ($entity->entity_id == $excl) {
	          $exclude = true;
	       }
	    }
    	    if (!$exclude) {
	       if ($entity->type0 == 'multipart' && !$entity->type1 == 'related') {
	          $result = $entity->getAttachments($exclude_id, $result);
	       } else if ($entity->type0 != 'multipart') {
	          $result[] = $entity;
	       }
	    }
          }
       } else {
            $exclude = false;
	    foreach ($exclude_id as $excl) {
               if ($this->entity_id == $excl) {
	          $exclude = true;
	       }
	    }
            if (!$exclude) {
        	$result[] = $this;
	    }
       }
       return $result;
    }   	    
    
}

class disposition {
  function disposition($name) {
     $this->name = $name;
     $this->properties = array();
  }
}

class language {
  function language($name) {
     $this->name = $name;
     $this->properties = array();
  }
}

?>
