<?php

/**
 * mime.class
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
class rfc822_header {
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
                $value = trim(substr($line, $pos+1));
                if(!preg_match('/^X.*/i', $field) &&
                   !preg_match('/^Subject/i', $field)) {
                    $value = $this->stripComments($value);
                }
                $this->parseField($field, $value);
            }
        }
        if ($this->content_type == '') {
            $this->parseContentType('text/plain; charset=us-ascii');
        }
    }

    function stripComments($value) {
        $result = '';

        $cnt = strlen($value);
        for ($i = 0; $i < $cnt; $i++) {
            switch ($value{$i}) {
                case '"':
                    $result .= '"';
                    while ((++$i < $cnt) && ($value{$i} != '"')) {
                        if ($value{$i} == '\\') {
                            $result .= '\\';
                            $i++;
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
                                $i++;
                                break;
                            case '(':
                                $depth++;
                                break;
                            case ')':
                                $depth--;
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
    ($address, $ar=false, $addr_ar = array(), $group = '') {
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
                    for (++$pos; $pos < $j && $address{$pos} != ')'; ++$pos) {
                        $addr .= $address{$pos};
                    }
                    $address_start = substr($address, 0, $addr_start);
                    $address_end   = substr($address, $pos + 1);
                    $address       = $address_start . $address_end;
                    $j = strlen($address);
                    $pos = $addr_start + 1;
                    break;
                case ',':  /* we reached a delimiter */
                    if ($addr == '') {
                        $addr = substr($address, 0, $pos);
                    } else if ($name == '') {
                        $name = trim(substr($address, 0, $addr_start));
                    }

                    $at = strpos($addr, '@');
                    $addr_structure = new address_structure();
                    $addr_structure->personal = $name;
                    $addr_structure->group = $group;
                    if ($at) {
                        $addr_structure->mailbox = substr($addr, 0, $at);
                        $addr_structure->host = substr($addr, $at+1);
                    } else {
                        $addr_structure->mailbox = $addr;
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
        $addr_structure = new address_structure();
        $addr_structure->group = $group;
        if ($at) {
            $addr_structure->mailbox = trim(substr($addr, 0, $at));
            $addr_structure->host = trim(substr($addr, $at+1));
        } else {
            $addr_structure->mailbox = trim($addr);
        }
        if ($group && $addr == '') { /* no addresses found in group */
            $name = "$group: Undisclosed recipients;";
            $addr_structure->personal = $name;
            $addr_ar[] = $addr_structure;
            return (array($addr_ar, $pos+1));
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
        $content_type = new content_type($type);
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
            $props = trim(substr($type, $pos+1));
        } else {
            $name = $value;
        }
        $props_a = $this->parseProperties($props);
        $disp = new disposition($name);
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

    function getContentType($type0, $type1) {
        $type0 = $this->content_type->type0;
        $type1 = $this->content_type->type1;
        return $this->content_type->properties;
    }
}

class msg_header {
    /** msg_header contains all variables available in a bodystructure **/
    /** entity like described in rfc2060                               **/

    var $type0 = '',
        $type1 = '',
        $parameters = array(),
        $id = 0,
        $description = '',
        $encoding='',
        $size = 0,
        $md5='',
        $disposition = '',
        $language='';

    /*
     * returns addres_list of supplied argument
     * arguments: array('to', 'from', ...) or just a string like 'to'.
     * result: string: address1, addres2, ....
     */

    function setVar($var, $value) {
        $this->{$var} = $value;
    }

    function getParameter($p) {
        $value = strtolower($p);
        return (isset($this->parameters[$p]) ? $this->parameters[$p] : '');
    }

    function setParameter($parameter, $value) {
        $this->parameters[strtolower($parameter)] = $value;
    }
}



class address_structure {
    var $personal = '',
        $adl      = '',
        $mailbox  = '',
        $host     = '',
        $group    = '';

    function getAddress($full = true) {
        $result = '';

        if (is_object($this)) {
            if (isset($this->host) && ($this->host != '')) {
                $email = $this->mailbox.'@'.$this->host;
            } else {
                $email = $this->mailbox;
            }
            if (trim($this->personal) != '') {
                if ($email) {
                    $addr = '"' . $this->personal . '" <' .$email.'>';
                } else {
                    $addr = $this->personal;
                }
                $best_dpl = $this->personal;
            } else {
                $addr = $email;
                $best_dpl = $email;
            }
            $result = ($full ? $addr : $best_dpl);
        }
        return $result;
    }
}

class message {
    /** message is the object that contains messages.  It is a recursive
        object in that through the $entities variable, it can contain
        more objects of type message.  See documentation in mime.txt for
        a better description of how this works.
    **/
    var $rfc822_header = '',
        $mime_header = '',
        $flags = '',
        $type0='',
        $type1='',
        $entities = array(),
        $parent_ent, $entity,
        $parent = '', $decoded_body='',
        $is_seen = 0, $is_answered = 0, $is_deleted = 0, $is_flagged = 0,
        $is_mdnsent = 0,
        $body_part = '',
        $offset = 0,  /* for fetching body parts out of raw messages */
        $length = 0;  /* for fetching body parts out of raw messages */

    function setEnt($ent) {
        $this->entity_id= $ent;
    }

    function addEntity ($msg) {
        $msg->parent = &$this;
        $this->entities[] = $msg;
    }

    function getFilename() {
        $filename = '';
        $filename = $this->header->getParameter('filename');	
        if (!$filename) {
            $filename = $this->header->getParameter('name');
        }

        if (!$filename) {
            $filename = 'untitled-'.$this->entity_id;
        }
        return $filename;
    }


    function addRFC822Header($read) {
        $header = new rfc822_header();
        $this->rfc822_header = $header->parseHeader($read);
    }

    function getEntity($ent) {
        $cur_ent = $this->entity_id;
        $msg = $this;
        if (($cur_ent == '') || ($cur_ent == '0')) {
            $cur_ent_a = array();
        } else {
            $cur_ent_a = explode('.', $this->entity_id);
        }
        $ent_a = explode('.', $ent);

        $cnt = count($ent_a);

        for ($i = 0; $i < $cnt -1; ++$i) {
            if (isset($cur_ent_a[$i]) && ($cur_ent_a[$i] != $ent_a[$i])) {
                $msg = $msg->parent;
                $cur_ent_a = explode('.', $msg->entity_id);
                --$i;
            } else if (!isset($cur_ent_a[$i])) {
                if (isset($msg->entities[($ent_a[$i]-1)])) {
                    $msg = $msg->entities[($ent_a[$i]-1)];
                } else {
                    $msg = $msg->entities[0];
                }
            }
            if (($msg->type0 == 'message') && ($msg->type1 == 'rfc822')) {
                /*this is a header for a message/rfc822 entity */
                $msg = $msg->entities[0];
            }
        }

        if (($msg->type0 == 'message') && ($msg->type1 == 'rfc822')) {
            /*this is a header for a message/rfc822 entity */
            $msg = $msg->entities[0];
        }

        if (isset($msg->entities[($ent_a[$cnt-1])-1])) {
            if (is_object($msg->entities[($ent_a[$cnt-1])-1])) {
                $msg = $msg->entities[($ent_a[$cnt-1]-1)];
            }
        }

        return $msg;
    }

    function setBody($s) {
        $this->body_part = $s;
    }

    function clean_up() {
        $msg = $this;
        $msg->body_part = '';

        foreach ($msg->entities as $m) {
            $m->clean_up();
        }
    }

    function getMailbox() {
        $msg = $this;
        while (is_object($msg->parent)) {
            $msg = $msg->parent;
        }
        return $msg->mailbox;
    }

    function calcEntity($msg) {
        if (($this->type0 == 'message') && ($this->type1 == 'rfc822')) {
            $msg->entity_id = $this->entity_id .'.0'; /* header of message/rfc822 */
        } else if (isset($this->entity_id) && ($this->entity_id != '')) {
            $ent_no = count($this->entities)+1;
            $par_ent = substr($this->entity_id, -2);
            if ($par_ent{0} == '.') {
                $par_ent = $par_ent{1};
            }
            if ($par_ent == '0') {
                $ent_no = count($this->entities) + 1;
                if ($ent_no > 0) {
                    $ent = substr($this->entity_id, 0, strrpos($this->entity_id, '.'));
                    $ent = ($ent ? $ent . ".$ent_no" : $ent_no);
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

        return $msg->entity_id;
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
     * Ask for me (Marc Groot Koerkamp, stekkel@users.sourceforge.net)
     *
     */
    function parseStructure($read, $i = 0) {
        $arg_no = 0;
        $arg_a  = array();
        $cnt    = strlen($read);

        for (; $i < $cnt; ++$i) {
            $char = strtoupper($read{$i});
            switch ($char) {
                case '(':
                    switch($arg_no) {
                        case 0:
                            if (!isset($msg)) {
                                $msg = new message();
                                $hdr = new msg_header();
                                $hdr->type0 = 'text';
                                $hdr->type1 = 'plain';
                                $hdr->encoding = 'us-ascii';
                                $msg->entity_id = $this->calcEntity($msg);
                            } else {
                                $msg->header->type0 = 'multipart';
                                $msg->type0 = 'multipart';
                                while ($read{$i} == '(') {
                                    $res = $msg->parseStructure($read, $i);
                                    $i = $res[1];
                                    $msg->addEntity($res[0]);
                                }
                            }
                            break;
                        case 1:
                            /* multipart properties */
                            ++$i;
                            $res = $this->parseProperties($read, $i);
                            $arg_a[] = $res[0];
                            $i = $res[1];
                            ++$arg_no;
                            break;
                        case 2:
                            if (isset($msg->type0) && ($msg->type0 == 'multipart')) {
                                ++$i;
                                $res = $msg->parseDisposition($read, $i);
                            } else { /* properties */
                                $res = $msg->parseProperties($read, $i);
                            }
                            $arg_a[] = $res[0];
                            $i = $res[1];
                            ++$arg_no;
                            break;
                        case 3:
                            if (isset($msg->type0) && ($msg->type0 == 'multipart')) {
                                ++$i;
                                $res= $msg->parseLanguage($read, $i);
                                $arg_a[] = $res[0];
                                $i = $res[1];
                            }
                        case 7:
                            if (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822')) {
                                $msg->header->type0 = $arg_a[0];
                                $msg->header->type1 = $arg_a[1];
                                $msg->type0 = $arg_a[0];
                                $msg->type1 = $arg_a[1];
                                $rfc822_hdr = new rfc822_header();
                                $res = $msg->parseEnvelope($read, $i, $rfc822_hdr);
                                $msg->rfc822_header = $res[0];
                                $i = $res[1] + 1;
                                while (($i < $cnt) && ($read{$i} != '(')) {
                                    ++$i;
                                }
                                $res = $msg->parseStructure($read, $i);
                                $i = $res[1];
                                $msg->addEntity($res[0]);
                            }
                            break;
                        case 8:
                            ++$i;
                            $res = $msg->parseDisposition($read, $i);
                            $arg_a[] = $res[0];
                            $i = $res[1];
                            ++$arg_no;
                            break;
                        case 9:
                            ++$i;
                            if (($arg_a[0] == 'text') || (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822'))) {
                                $res = $msg->parseDisposition($read, $i);
                            } else {
                                $res = $msg->parseLanguage($read, $i);
                            }
                            $arg_a[] = $res[0];
                            $i = $res[1];
                            ++$arg_no;
                            break;
                       case 10:
                           if (($arg_a[0] == 'text') || (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822'))) {
                               ++$i;
                               $res = $msg->parseLanguage($read, $i);
                               $arg_a[] = $res[0];
                               $i = $res[1];
                           } else {
                               $i = $msg->parseParenthesis($read, $i);
                               $arg_a[] = ''; /* not yet described in rfc2060 */
                           }
                           ++$arg_no;
                           break;
                       default:
                           /* unknown argument, skip this part */
                           $i = $msg->parseParenthesis($read, $i);
                           $arg_a[] = '';
                           ++$arg_no;
                           break;
                   } /* switch */
                   break;
                case '"':
                    /* inside an entity -> start processing */
                    $debug = substr($read, $i, 20);
                    $res = $msg->parseQuote($read, $i);
                    $arg_s = $res[0];
                    $i = $res[1];
                    ++$arg_no;
                    if ($arg_no < 3) {
                        $arg_s = strtolower($arg_s); /* type0 and type1 */
                    }
                    $arg_a[] = $arg_s;
                    break;
                case 'n':
                case 'N':
                    /* probably NIL argument */
                    if (strtoupper(substr($read, $i, 4)) == 'NIL ') {
                        $arg_a[] = '';
                        ++$arg_no;
                        $i += 2;
                    }
                    break;
                case '{':
                    /* process the literal value */
                    $res = $msg->parseLiteral($read, $i);
                    $arg_s = $res[0];
                    $i = $res[1];
                    ++$arg_no;
                    break;
                case is_numeric($read{$i}):
                    /* process integers */
                    if ($read{$i} == ' ') { break; }
                    $arg_s = $read{$i};;
                    for (++$i; preg_match('/^[0-9]{1}$/', $read{$i}); ++$i) {
                        $arg_s .= $read{$i};
                    }
                    ++$arg_no;
                    $arg_a[] = $arg_s;
                    break;
                case ')':
                    $multipart = (isset($msg->type0) && ($msg->type0 == 'multipart'));
                    if (!$multipart) {
                        $shifted_args = (($arg_a[0] == 'text') || (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822')));
                        $hdr->type0 = $arg_a[0];
                        $hdr->type1 = $arg_a[1];

                        $msg->type0 = $arg_a[0];
                        $msg->type1 = $arg_a[1];
                        $arr = $arg_a[2];
                        if (is_array($arr)) {
                            $hdr->parameters = $arg_a[2];
                        }
                        $hdr->id = str_replace('<', '', str_replace('>', '', $arg_a[3]));
                        $hdr->description = $arg_a[4];
                        $hdr->encoding = strtolower($arg_a[5]);
                        $hdr->entity_id = $msg->entity_id;
                        $hdr->size = $arg_a[6];
                        if ($shifted_args) {
                            $hdr->lines = $arg_a[7];
                            $s = 1;
                        } else {
                            $s = 0;
                        }
                        $hdr->md5 = (isset($arg_a[7+$s]) ? $arg_a[7+$s] : $hdr->md5);
                        $hdr->disposition = (isset($arg_a[8+$s]) ? $arg_a[8+$s] : $hdr->disposition);
                        $hdr->language = (isset($arg_a[9+$s]) ? $arg_a[9+$s] : $hdr->language);
                        $msg->header = $hdr;
//                        $arg_no = 0;
                        ++$i;
                        if ((substr($msg->entity_id, -2) == '.0') && ($msg->type0 !='multipart')) {
                           $msg->entity_id++;
                        }
                    } else {
                        $hdr->type0 = 'multipart';
                        $hdr->type1 = $arg_a[0];
                        $msg->type0 = 'multipart';
                        $msg->type1 = $arg_a[0];
                        $hdr->parameters = (isset($arg_a[1]) ? $arg_a[1] : $hdr->parameters);
                        $hdr->disposition = (isset($arg_a[2]) ? $arg_a[2] : $hdr->disposition);
                        $hdr->language = (isset($arg_a[3]) ? $arg_a[3] : $hdr->language);
                        $msg->header = $hdr;
			++$i;
                    }
                    return (array($msg, $i));
                default: break;
            } /* switch */

        } /* for */
    } /* parsestructure */

    function parseProperties($read, $i) {
        $properties = array();
        $prop_name = '';

        for (; $read{$i} != ')'; ++$i) {
            $arg_s = '';
            if ($read{$i} == '"') {
                $res = $this->parseQuote($read, $i);
                $arg_s = $res[0];
                $i = $res[1];
            } else if ($read{$i} == '{') {
                $res = $this->parseLiteral($read, $i);
                $arg_s = $res[0];
                $i = $res[1];
            }

            if ($arg_s != '') {
                if ($prop_name == '') {
                    $prop_name = strtolower($arg_s);
                    $properties[$prop_name] = '';
                } else if ($prop_name != '') {
                    $properties[$prop_name] = $arg_s;
                    $prop_name = '';
                }
            }
        }
        return array($properties, $i);
    }

    function parseEnvelope($read, $i, $hdr) {
        $arg_no = 0;
        $arg_a = array();
        $cnt = strlen($read);

        for (; ($i < $cnt) && ($read{$i} != ')'); ++$i) {
            ++$i;
            $char = strtoupper($read{$i});
            switch ($char) {
                case '"':
                    $res = $this->parseQuote($read, $i);
                    $arg_a[] = $res[0];
                    $i = $res[1];
                    ++$arg_no;
                    break;
                case '{':
                    $res = $this->parseLiteral($read, $i);
                    $arg_a[] = $res[0];
                    $i = $res[1];
                    ++$arg_no;
                    break;
                case 'N':
                    /* probably NIL argument */
                    if (strtoupper(substr($read, $i, 3)) == 'NIL') {
                        $arg_a[] = '';
                        ++$arg_no;
                        $i += 2;
                    }
                    break;
                case '(':
                    /* Address structure (with group support)
                     * Note: Group support is useless on SMTP connections
                     *       because the protocol doesn't support it
                     */
                    $addr_a = array();
                    $group = '';
                    $a=0;
                    for (; $i < $cnt && $read{$i} != ')'; ++$i) {
                        if ($read{$i} == '(') {
                            $res = $this->parseAddress($read, $i);
                            $addr = $res[0];
                            $i = $res[1];
                            if (($addr->host == '') && ($addr->mailbox != '')) {
                                /* start of group */
                                $group = $addr->mailbox;
                                $group_addr = $addr;
                                $j = $a;
                            } else if ($group && ($addr->host == '') && ($addr->mailbox == '')) {
                               /* end group */
                                if ($a == ($j+1)) { /* no group members */
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
                            ++$a;
                        }
                    }
                    $arg_a[] = $addr_a;
                    break;
                default: break;
            }
        }

        if (count($arg_a) > 9) {
            /* argument 1: date */
            $d = strtr($arg_a[0], array('  ' => ' '));
            $d = explode(' ', $d);
            $hdr->date = getTimeStamp($d);

            /* argument 2: subject */
            $arg_a[1] = (!trim($arg_a[1]) ? _("(no subject)") : $arg_a[1]);
            $hdr->subject = $arg_a[1];

            $hdr->from = $arg_a[2][0];     /* argument 3: from        */
            $hdr->sender = $arg_a[3][0];   /* argument 4: sender      */
            $hdr->replyto = $arg_a[4][0];  /* argument 5: reply-to    */
            $hdr->to = $arg_a[5];          /* argument 6: to          */
            $hdr->cc = $arg_a[6];          /* argument 7: cc          */
            $hdr->bcc = $arg_a[7];         /* argument 8: bcc         */
            $hdr->inreplyto = $arg_a[8];   /* argument 9: in-reply-to */
            $hdr->message_id = $arg_a[9];  /* argument 10: message-id */
        }
        return (array($hdr, $i));
    }

    function parseLiteral($read, $i) {
        $lit_cnt = '';
        for (++$i; $read{$i} != '}'; ++$i) {
            $lit_cnt .= $read{$i};
        }

        $lit_cnt +=2; /* add the { and } characters */
        $s = '';
        for ($j = 0; $j < $lit_cnt; ++$j) {
            $s .= $read{++$i};
        }
        return (array($s, $i));
    }

    function parseQuote($read, $i) {
        $s = '';
        for (++$i; $read{$i} != '"'; ++$i) {
            if ($read{$i} == '\\') {
                ++$i;
             }
             $s .= $read{$i};
        }
        return (array($s, $i));
    }

    function parseAddress($read, $i) {
        $arg_a = array();

        for (; $read{$i} != ')'; ++$i) {
            $char = strtoupper($read{$i});
            switch ($char) {
                case '"':
                case '{':
                    $res = ($char == '"' ? $this->parseQuote($read, $i) : $this->parseLiteral($read, $i));
                    $arg_a[] = $res[0];
                    $i = $res[1];
                    break;
                case 'n':
                case 'N':
                    if (strtoupper(substr($read, $i, 3)) == 'NIL') {
                        $arg_a[] = '';
                        $i += 2;
                    }
                    break;
                default: break;
            }
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
        return (array($adr, $i));
    }

    function parseDisposition($read, $i) {
        $arg_a = array();

        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"':
                case '{':
                case '(':
                    switch ($read{$i}) {
                        case '"': $res = $this->parseQuote($read, $i); break;
                        case '{': $res = $this->parseLiteral($read, $i); break;
                        case '(': $res = $this->parseProperties($read, $i); break;
                    }
                    $arg_a[] = $res[0];
                    $i = $res[1];
                    break;
                default: break;
            }
        }

        if (isset($arg_a[0])) {
            $disp = new disposition($arg_a[0]);
            if (isset($arg_a[1])) {
                $disp->properties = $arg_a[1];
            }
        }

        return (is_object($disp) ? array($disp, $i) : array('', $i));
    }

    function parseLanguage($read, $i) {
        /* no idea how to process this one without examples */
        $arg_a = array();

        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"':
                case '{':
                case '(':
                    switch ($read{$i}) {
                        case '"': $res = $this->parseQuote($read, $i); break;
                        case '{': $res = $this->parseLiteral($read, $i); break;
                        case '(': $res = $this->parseProperties($read, $i); break;
                    }
                    $arg_a[] = $res[0];
                    $i = $res[1];
                    break;
                default: break;
            }
        }

        if (isset($arg_a[0])) {
            $lang = new language($arg_a[0]);
            if (isset($arg_a[1])) {
                $lang->properties = $arg_a[1];
            }
        }

        return (is_object($lang) ? array($lang, $i) : array('', $i));
    }

    function parseParenthesis($read, $i) {
        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"':
                case '{':
                case '(':
                    switch ($read{$i}) {
                        case '"': $res = $this->parseQuote($read, $i); break;
                        case '{': $res = $this->parseLiteral($read, $i); break;
                        case '(': $res = $this->parseProperties($read, $i); break;
                    }
                    $i = $res[1];
                    break;
                default: break;
            }
        }
        return $i;
    }

    /* Function to fill the message structure in case the */
    /* bodystructure is not available NOT FINISHED YET    */
    function parseMessage($read, $type0, $type1) {
        switch ($type0) {
            case 'message':
                $rfc822_header = true;
                $mime_header = false;
                break;
            case 'multipart':
                $rfc822_header = false;
                $mime_header = true;
                break;
            default: return $read;
        }

        for ($i = 1; $i < $count; ++$i) {
            $line = trim($body[$i]);
            if (($mime_header || $rfc822_header) &&
                (preg_match("/^.*boundary=\"?(.+(?=\")|.+).*/i", $line, $reg))) {
                $bnd = $reg[1];
                $bndreg = $bnd;
                $bndreg = str_replace("\\", "\\\\", $bndreg);
                $bndreg = str_replace("?", "\\?", $bndreg);
                $bndreg = str_replace("+", "\\+", $bndreg);
                $bndreg = str_replace(".", "\\.", $bndreg);
                $bndreg = str_replace("/", "\\/", $bndreg);
                $bndreg = str_replace("-", "\\-", $bndreg);
                $bndreg = str_replace("(", "\\(", $bndreg);
                $bndreg = str_replace(")", "\\)", $bndreg);
            } else if ($rfc822_header && $line == '') {
                $rfc822_header = false;
                if ($msg->type0 == 'multipart') {
                    $mime_header = true;
                }
            }

            if ((($line{0} == '-') || $rfc822_header)  && isset($boundaries[0])) {
                $cnt=count($boundaries)-1;
                $bnd = $boundaries[$cnt]['bnd'];
                $bndreg = $boundaries[$cnt]['bndreg'];

                $regstr = '/^--'."($bndreg)".".*".'/';
                if (preg_match($regstr, $line, $reg)) {
                    $bndlen = strlen($reg[1]);
                    $bndend = false;
                    if (strlen($line) > ($bndlen + 3)) {
                        if (($line{$bndlen+2} == '-') && ($line{$bndlen+3} == '-')) {
                            $bndend = true;
                        }
                    }
                    if ($bndend) {
                        /* calc offset and return $msg */
                        //$entStr = CalcEntity("$entStr", -1);
                        array_pop($boundaries);
                        $mime_header = true;
                        $bnd_end = true;
                    } else {
                        $mime_header = true;
                         $bnd_end = false;
                        //$entStr = CalcEntity("$entStr", 0);
                        ++$content_indx;
                    }
                } else {
                    if ($header) { }
                }
            }
        }
    }

    function findDisplayEntity($entity = array(), $alt_order = array('text/plain', 'text/html'), $strict=false) {
        $found = false;
        if ($this->type0 == 'multipart') {
            if($this->type1 == 'alternative') {
                $msg = $this->findAlternativeEntity($alt_order);
                if (count($msg->entities) == 0) {
                    $entity[] = $msg->entity_id;
                } else {
                    $entity = $msg->findDisplayEntity($entity, $alt_order, $strict);
                }
                $found = true;
            } else if ($this->type1 == 'related') { /* RFC 2387 */
                $msgs = $this->findRelatedEntity();
                foreach ($msgs as $msg) {
                    if (count($msg->entities) == 0) {
                        $entity[] = $msg->entity_id;
                    } else {
                        $entity = $msg->findDisplayEntity($entity, $alt_order, $strict);
                    }
                }
                if (count($msgs) > 0) {
                    $found = true;
                }
            } else { /* Treat as multipart/mixed */
                foreach ($this->entities as $ent) {
                    if(strtolower($ent->header->disposition->name) != 'attachment' &&
                       ($ent->type0 != 'message' && $ent->type1 != 'rfc822'))
                    {
                        $entity = $ent->findDisplayEntity($entity, $alt_order, $strict);
                        $found = true;
                    }
                }
            }
        } else { /* If not multipart, then just compare with each entry from $alt_order */
            $type = $this->type0.'/'.$this->type1;
            foreach ($alt_order as $alt) {
                if( ($alt == $type) && isset($this->entity_id) ) {
                    if ( (count($this->entities) == 0) && 
                         (strtolower($this->header->disposition->name) != 'attachment') )
                    {
                            $entity[] = $this->entity_id;
                            $found = true;
                    }
                }
            }
        }
        if(!$found) {
            foreach ($this->entities as $ent) {
                if((strtolower($ent->header->disposition->name) != 'attachment') &&
                   (($ent->type0 != 'message') && ($ent->type1 != 'rfc822'))) {
                    $entity = $ent->findDisplayEntity($entity, $alt_order, $strict);
                    $found = true;
                }
            }
        }
        if(!$strict && !$found) {
            if ($this->type0 == 'text' &&
                ($this->type1 == 'plain' ||
                 $this->type1 == 'html'  ||
                 $this->type1 == 'message') &&
                isset($this->entity_id) )
            {
                if (count($this->entities) == 0) {
                    if (strtolower($this->header->disposition->name) != 'attachment') {
                        $entity[] = $this->entity_id;
                    }
                }
            }
        }

        return $entity;
    }

    function findAlternativeEntity($alt_order) {
        /* If we are dealing with alternative parts then we  */
        /* choose the best viewable message supported by SM. */
        $best_view = 0;
        $entity = array();
        $altcount = count($alt_order);

        foreach($this->entities as $ent) {
            $type = $ent->header->type0 . '/' . $ent->header->type1;
            if ($type == 'multipart/related') {
                $type = $ent->header->getParameter('type');
            }
            for ($j = $best_view; $j < $altcount; ++$j) {
                if (($alt_order[$j] == $type) && ($j >= $best_view)) {
                    $best_view = $j;
                    $entity = $ent;
                }
            }
        }

        return $entity;
    }

    function findRelatedEntity() {
        $msgs = array();
        $entcount = count($this->entities);

        for ($i = 0; $i < $entcount; ++$i) {
            $type = $this->entities[$i]->header->type0.'/'.$this->entities[$i]->header->type1;
            if ($this->header->getParameter('type') == $type) {
                $msgs[] = $this->entities[$i];
            }
        }

        return $msgs;
    }

    function getAttachments($exclude_id=array(), $result = array()) {
        if (($this->type0 == 'message') && ($this->type1 == 'rfc822')) {
            $this = $this->entities[0];
        }

        if (count($this->entities)) {
            foreach ($this->entities as $entity) {
                $exclude = false;

                foreach ($exclude_id as $excl) {
                    if ($entity->entity_id === $excl) {
                        $exclude = true;
                    }
                }

                if (!$exclude) {
                    if (($entity->type0 == 'multipart') &&
                        ($entity->type1 != 'related')) {
                        $result = $entity->getAttachments($exclude_id, $result);
                    } else if ($entity->type0 != 'multipart') {
                        $result[] = $entity;
                    }
                }
            }
        } else {
            $exclude = false;
            foreach ($exclude_id as $excl) {
                $exclude = $exclude || ($this->entity_id == $excl);
            }

            if (!$exclude) {
                $result[] = $this;
            }
        }

        return $result;
    }
}

class smime_message {

}

class disposition {
    function disposition($name) {
       $this->name = $name;
       $this->properties = array();
    }

    function getProperty($par) {
        $value = strtolower($par);
        if (isset($this->properties[$par])) {
            return $this->properties[$par];
        }
        return '';
    }
}

class language {
    function language($name) {
       $this->name = $name;
       $this->properties = array();
    }
}

class content_type {
    var $type0      = 'text',
        $type1      = 'plain',
        $properties = '';

    function content_type($type) {
        $pos = strpos($type, '/');
        if ($pos > 0) {
            $this->type0 = substr($type, 0, $pos);
            $this->type1 = substr($type, $pos+1);
        } else {
            $this->type0 = $type;
        }
        $this->properties = array();
    }
}

?>
