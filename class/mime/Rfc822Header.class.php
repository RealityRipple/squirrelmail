<?php

/**
 * Rfc822Header.class.php
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * $Id$
 */

/*
 * rdc822_header class
 * input: header_string or array
 */
class Rfc822Header {
    var $date = '',
        $subject = '',
        $from = array(),
        $sender = '',
        $reply_to = array(),
        $to = array(),
        $cc = array(),
        $bcc = array(),
        $in_reply_to = '',
        $message_id = '',
	$references = '',
        $mime = false,
        $content_type = '',
        $disposition = '',
        $xmailer = '',
        $priority = 3,
        $dnt = '',
        $mlist = array(),
        $more_headers = array(); /* only needed for constructing headers
                                    in smtp.php */
    function parseHeader($hdr) {
        if (is_array($hdr)) {
            $hdr = implode('', $hdr);
        }

        /* First we unfold the header */
        $hdr = trim(str_replace(array("\r\n\t", "\r\n "),array('', ''), $hdr));

        /* Now we can make a new header array with */
        /* each element representing a headerline  */
        $hdr = explode("\r\n" , $hdr);
        foreach ($hdr as $line) {
            $pos = strpos($line, ':');
            if ($pos > 0) {
                $field = substr($line, 0, $pos);
		if (!strstr($field,' ')) { /* valid field */
            	    $value = trim(substr($line, $pos+1));
            	    if(!preg_match('/^X.*/i', $field) &&
                       !preg_match('/^Subject/i', $field)) {
                       $value = $this->stripComments($value);
                    }
            	    $this->parseField($field, $value);
		}
            }
        }
        if ($this->content_type == '') {
            $this->parseContentType('text/plain; charset=us-ascii');
        }
    }

    function stripComments($value) {
        $result = '';

        $cnt = strlen($value);
        for ($i = 0; $i < $cnt; ++$i) {
            switch ($value{$i}) {
                case '"':
                    $result .= '"';
                    while ((++$i < $cnt) && ($value{$i} != '"')) {
                        if ($value{$i} == '\\') {
                            $result .= '\\';
                            ++$i;
                        }
                        $result .= $value{$i};
                    }
                    $result .= $value{$i};
                    break;
                case '(':
                    $depth = 1;
                    while (($depth > 0) && (++$i < $cnt)) {
                        switch($value{$i}) {
                            case '\\':
                                ++$i;
                                break;
                            case '(':
                                ++$depth;
                                break;
                            case ')':
                                --$depth;
                                break;
                            default:
                                break;
                        }
                    }
                    break;
                default:
                    $result .= $value{$i};
                    break;
            }
        }
        return $result;
    }

    function parseField($field, $value) {
        $field = strtolower($field);
        switch($field) {
            case 'date':
                $d = strtr($value, array('  ' => ' '));
                $d = explode(' ', $d);
                $this->date = getTimeStamp($d);
                break;
            case 'subject':
                $this->subject = $value;
                break;
            case 'from':
                $this->from = $this->parseAddress($value,true);
                break;
            case 'sender':
                $this->sender = $this->parseAddress($value);
                break;
            case 'reply-to':
                $this->reply_to = $this->parseAddress($value, true);
                break;
            case 'to':
                $this->to = $this->parseAddress($value, true);
                break;
            case 'cc':
                $this->cc = $this->parseAddress($value, true);
                break;
            case 'bcc':
                $this->bcc = $this->parseAddress($value, true);
                break;
            case 'in-reply-to':
                $this->in_reply_to = $value;
                break;
            case 'message-id':
                $this->message_id = $value;
                break;
	    case 'references':
	        $this->references = $value;
		break;
            case 'disposition-notification-to':
                $this->dnt = $this->parseAddress($value);
                break;
            case 'mime-version':
                $value = str_replace(' ', '', $value);
                $this->mime = ($value == '1.0' ? true : $this->mime);
                break;
            case 'content-type':
                $this->parseContentType($value);
                break;
            case 'content-disposition':
                $this->parseDisposition($value);
                break;
            case 'user-agent':
            case 'x-mailer':
                $this->xmailer = $value;
                break;
            case 'x-priority':
                $this->priority = $value;
                break;
            case 'list-post':
                $this->mlist('post', $value);
                break;
            case 'list-reply':
                $this->mlist('reply', $value);
                break;
            case 'list-subscribe':
                $this->mlist('subscribe', $value);
                break;
            case 'list-unsubscribe':
                $this->mlist('unsubscribe', $value);
                break;
            case 'list-archive':
                $this->mlist('archive', $value);
                break;
            case 'list-owner':
                $this->mlist('owner', $value);
                break;
            case 'list-help':
                $this->mlist('help', $value);
                break;
            case 'list-id':
                $this->mlist('id', $value);
                break;
            default:
                break;
        }
    }

    function parseAddress
    ($address, $ar=false, $addr_ar = array(), $group = '', $host='') {
        $pos = 0;
        $j = strlen($address);
        $name = '';
        $addr = '';
        while ($pos < $j) {
            switch ($address{$pos}) {
                case '"': /* get the personal name */
                    if ($address{++$pos} == '"') {
                        ++$pos;
                    } else {
                        while ($pos < $j && $address{$pos} != '"') {
                            if ((substr($address, $pos, 2) == '\\"') ||
                                (substr($address, $pos, 2) == '\\\\')) {
                                $name .= $address{$pos++};
                            }
                            $name .= $address{$pos++};
                        }
                    }
                    ++$pos;
                    break;
                case '<':  /* get email address */
                    $addr_start = $pos++;
                    while ($pos < $j && $address{$pos} != '>') {
                        $addr .= $address{$pos++};
                    }
                    ++$pos;
                    break;
                case '(':  /* rip off comments */
                    $addr_start = $pos;
                    for (++$pos; ($pos < $j) && ($address{$pos} != ')'); ++$pos) {
                        $addr .= $address{$pos};
                    }
                    $address_start = substr($address, 0, $addr_start);
                    $address_end   = substr($address, $pos + 1);
                    $address       = $address_start . $address_end;
                    $j = strlen($address);
                    $pos = $addr_start + 1;
                    break;
                case ',':  /* we reached a delimiter */
//case ';':
                    if ($addr == '') {
                        $addr = substr($address, 0, $pos);
                    } else if ($name == '') {
                        $name = trim(substr($address, 0, $addr_start));
                    }

                    $at = strpos($addr, '@');
                    $addr_structure = new AddressStructure();
                    $addr_structure->personal = $name;
                    $addr_structure->group = $group;
                    if ($at) {
                        $addr_structure->mailbox = substr($addr, 0, $at);
                        $addr_structure->host = substr($addr, $at+1);
                    } else {
                        $addr_structure->mailbox = $addr;
			if ($host) {
			   $addr_structure->host = $host;
			}
                    }
                    $address = trim(substr($address, $pos+1));
                    $j = strlen($address);
                    $pos = 0;
                    $name = '';
                    $addr = '';
                    $addr_ar[] = $addr_structure;
                    break;
                case ':':  /* process the group addresses */
                    /* group marker */
                    $group = substr($address, 0, $pos);
                    $address = substr($address, $pos+1);
                    $result = $this->parseAddress($address, $ar, $addr_ar, $group);
                    $addr_ar = $result[0];
                    $pos = $result[1];
                    $address = substr($address, $pos++);
                    $j = strlen($address);
                    $group = '';
                    break;
                case ';':
                    if ($group) {
                        $address = substr($address, 0, $pos - 1);
                    }
                    ++$pos;
                    break;
                default:
                    ++$pos;
                    break;
            }
        }
        if ($addr == '') {
            $addr = substr($address, 0, $pos);
        } else if ($name == '') {
            $name = trim(substr($address, 0, $addr_start));
        }
        $at = strpos($addr, '@');
        $addr_structure = new AddressStructure();
        $addr_structure->group = $group;
        if ($at) {
            $addr_structure->mailbox = trim(substr($addr, 0, $at));
            $addr_structure->host = trim(substr($addr, $at+1));
        } else {
            $addr_structure->mailbox = trim($addr);
            if ($host) {
	       $addr_structure->host = $host;
	    }
        }
        if ($group && $addr == '') { /* no addresses found in group */
            $name = "$group";
            $addr_structure->personal = $name;
            $addr_ar[] = $addr_structure;
            return (array($addr_ar,$pos+1 ));
	} elseif ($group) {
            $addr_structure->personal = $name;
            $addr_ar[] = $addr_structure;
	    return (array($addr_ar,$pos+1 ));
        } else {
            $addr_structure->personal = $name;
            if ($name || $addr) {
                $addr_ar[] = $addr_structure;
            }
        }
        if ($ar) {
            return ($addr_ar);
        }
        return ($addr_ar[0]);
    }

    function parseContentType($value) {
        $pos = strpos($value, ';');
        $props = '';
        if ($pos > 0) {
           $type = trim(substr($value, 0, $pos));
           $props = trim(substr($type, $pos+1));
        } else {
           $type = $value;
        }
        $content_type = new ContentType($type);
        if ($props) {
            $properties = $this->parseProperties($props);
            if (!isset($properties['charset'])) {
                $properties['charset'] = 'us-ascii';
            }
            $content_type->properties = $this->parseProperties($props);
        }
        $this->content_type = $content_type;
    }

    function parseProperties($value) {
        $propArray = explode(';', $value);
        $propResultArray = array();
        foreach ($propArray as $prop) {
            $prop = trim($prop);
            $pos = strpos($prop, '=');
            if ($pos > 0)  {
                $key = trim(substr($prop, 0, $pos));
                $val = trim(substr($prop, $pos+1));
                if ($val{0} == '"') {
                    $val = substr($val, 1, -1);
                }
                $propResultArray[$key] = $val;
            }
        }
        return $propResultArray;
    }

    function parseDisposition($value) {
        $pos = strpos($value, ';');
        $props = '';
        if ($pos > 0) {
            $name = trim(substr($value, 0, $pos));
            $props = trim(substr($value, $pos+1));
        } else {
            $name = $value;
        }
        $props_a = $this->parseProperties($props);
        $disp = new Disposition($name);
        $disp->properties = $props_a;
        $this->disposition = $disp;
    }

    function mlist($field, $value) {
        $res_a = array();
        $value_a = explode(',', $value);
        foreach ($value_a as $val) {
            $val = trim($val);
            if ($val{0} == '<') {
                $val = substr($val, 1, -1);
            }
            if (substr($val, 0, 7) == 'mailto:') {
                $res_a['mailto'] = substr($val, 7);
            } else {
                $res_a['href'] = $val;
            }
        }
        $this->mlist[$field] = $res_a;
    }

    /*
     * function to get the addres strings out of the header.
     * Arguments: string or array of strings !
     * example1: header->getAddr_s('to').
     * example2: header->getAddr_s(array('to', 'cc', 'bcc'))
     */
    function getAddr_s($arr, $separator = ',') {
        $s = '';

        if (is_array($arr)) {
            foreach($arr as $arg) {
                if ($this->getAddr_s($arg)) {
                    $s .= $separator . $result;
                }
            }
            $s = ($s ? substr($s, 2) : $s);
        } else {
            eval('$addr = $this->' . $arr . ';') ;
            if (is_array($addr)) {
                foreach ($addr as $addr_o) {
                    if (is_object($addr_o)) {
                        $s .= $addr_o->getAddress() . $separator;
                    }
                }
                $s = substr($s, 0, -strlen($separator));
            } else {
                if (is_object($addr)) {
                    $s .= $addr->getAddress();
                }
            }
        }
        return $s;
    }

    function getAddr_a($arg, $excl_arr = array(), $arr = array()) {
        if (is_array($arg)) {
            foreach($arg as $argument) {
                $arr = $this->getAddr_a($argument, $excl_arr, $arr);
            }
        } else {
            eval('$addr = $this->' . $arg . ';') ;
            if (is_array($addr)) {
                foreach ($addr as $next_addr) {
                    if (is_object($next_addr)) {
                        if (isset($next_addr->host) && ($next_addr->host != '')) {
                            $email = $next_addr->mailbox . '@' . $next_addr->host;
                        } else {
                            $email = $next_addr->mailbox;
                        }
                        $email = strtolower($email);
                        if ($email && !isset($arr[$email]) && !isset($excl_arr[$email])) {
                            $arr[$email] = $next_addr->personal;
                        }
                    }
                }
            } else {
                if (is_object($addr)) {
                    $email  = $addr->mailbox;
                    $email .= (isset($addr->host) ? '@' . $addr->host : '');
                    $email  = strtolower($email);
                    if ($email && !isset($arr[$email]) && !isset($excl_arr[$email])) {
                        $arr[$email] = $addr->personal;
                    }
                }
            }
        }
        return $arr;
    }
    
    function findAddress($address, $recurs = false) {
	$result = false;
        if (is_array($address)) {
	    $i=0;
            foreach($address as $argument) {
                $match = $this->findAddress($argument, true);
		$last = end($match);
		if ($match[1]) {
		    return $i;
		} else {
		    if (count($match[0]) && !$result) {
			$result = $i;
		    }
		}
		++$i;	
            }
	} else {
	    $srch_addr = $this->parseAddress($address);
	    $results = array();
	    foreach ($this->to as $to) {
		if ($to->host == $srch_addr->host) {
		    if ($to->mailbox == $srch_addr->mailbox) {
			$results[] = $srch_addr;
			if ($to->personal == $srch_addr->personal) {
			    if ($recurs) {
				return array($results, true);
			    } else {
				return true;
			    }
			}
		    }
		}
	    }
 	    foreach ($this->cc as $cc) {
	        if ($cc->host == $srch_addr->host) {
		    if ($cc->mailbox == $srch_addr->mailbox) {
		        $results[] = $srch_addr;
		        if ($cc->personal == $srch_addr->personal) {
			    if ($recurs) {
			        return array($results, true);
			    } else {
			        return true;
			    }
		        }
		    }
		}
	    }
	    if ($recurs) {
		return array($results, false);
	    } elseif (count($result)) {
		return true;
	    } else {
		return false;
	    }	
	}
	return $result;
    }

    function getContentType($type0, $type1) {
        $type0 = $this->content_type->type0;
        $type1 = $this->content_type->type1;
        return $this->content_type->properties;
    }
}

?>
