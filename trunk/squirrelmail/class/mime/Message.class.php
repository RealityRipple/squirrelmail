<?php

/**
 * Message.class.php
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * $Id$
 */

class Message {
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
        $length = 0,  /* for fetching body parts out of raw messages */
	$att_local_name = ''; /* location where the tempory attachment
	                         is stored. For future usage in smtp.php */

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
        $header = new Rfc822Header();
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
        
        for ($i = 0,$entCount = count($ent_a) - 1; $i < $entCount; ++$i) {
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

        if (isset($msg->entities[($ent_a[$entCount])-1])) {
            if (is_object($msg->entities[($ent_a[$entCount])-1])) {
                $msg = $msg->entities[($ent_a[$entCount]-1)];
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
            $entCount = count($this->entities) + 1;
            $par_ent = substr($this->entity_id, -2);
            if ($par_ent{0} == '.') {
                $par_ent = $par_ent{1};
            }
            if ($par_ent == '0') {
                if ($entCount > 0) {
                    $ent = substr($this->entity_id, 0, strrpos($this->entity_id, '.'));
                    $ent = ($ent ? $ent . '.' : '') . $entCount;
                    $msg->entity_id = $ent;
                } else {
                    $msg->entity_id = $entCount;
                }
            } else {
                $ent = $this->entity_id . '.' . $entCount;
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
        for ($cnt = strlen($read); $i < $cnt; ++$i) {
            $char = strtoupper($read{$i});
            switch ($char) {
                case '(':
                    switch($arg_no) {
                        case 0:
                            if (!isset($msg)) {
                                $msg = new Message();
                                $hdr = new MessageHeader();
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
                            $arg_a[] = $this->parseProperties($read, $i);
                            ++$arg_no;
                            break;
                        case 2:
                            if (isset($msg->type0) && ($msg->type0 == 'multipart')) {
                                ++$i;
                                $arg_a[] = $msg->parseDisposition($read, $i);
                            } else { /* properties */
                                $arg_a[] = $msg->parseProperties($read, $i);
                            }
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
                                $rfc822_hdr = new Rfc822Header();
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
                            $arg_a[] = $msg->parseDisposition($read, $i);
                            ++$arg_no;
                            break;
                        case 9:
                            ++$i;
                            if (($arg_a[0] == 'text') || (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822'))) {
                                $arg_a[] = $msg->parseDisposition($read, $i);
                            } else {
                                $arg_a[] = $msg->parseLanguage($read, $i);
                            }
                            ++$arg_no;
                            break;
                       case 10:
                           if (($arg_a[0] == 'text') || (($arg_a[0] == 'message') && ($arg_a[1] == 'rfc822'))) {
                               ++$i;
                               $arg_a[] = $msg->parseLanguage($read, $i);
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
                    $arg_s = $msg->parseQuote($read, $i);
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
                    $arg_s = $msg->parseLiteral($read, $i);
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
                        if ((strrchr($msg->entity_id, '.') == '.0') && ($msg->type0 !='multipart')) {
                           $msg->entity_id = $this->entity_id . '.1';
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
                    }
                    ++$i;
                    return (array($msg, $i));
                default: break;
            } /* switch */

        } /* for */
    } /* parsestructure */

    function parseProperties($read, &$i) {
        $properties = array();
        $prop_name = '';

        for (; $read{$i} != ')'; ++$i) {
            $arg_s = '';
            if ($read{$i} == '"') {
                $arg_s = $this->parseQuote($read, $i);
            } else if ($read{$i} == '{') {
                $arg_s = $this->parseLiteral($read, $i);
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
        return $properties;
    }

    function parseEnvelope($read, $i, $hdr) {
        $arg_no = 0;
        $arg_a = array();

        for ($cnt = strlen($read); ($i < $cnt) && ($read{$i} != ')'); ++$i) {
            ++$i;
            $char = strtoupper($read{$i});
            switch ($char) {
                case '"':
                    $arg_a[] = $this->parseQuote($read, $i);
                    ++$arg_no;
                    break;
                case '{':
                    $arg_a[] = $this->parseLiteral($read, $i);
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

    function parseLiteral($read, &$i) {
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

    function parseQuote($read, &$i) {
        $s = '';
        for (++$i; $read{$i} != '"'; ++$i) {
            if ($read{$i} == '\\') {
                ++$i;
             }
             $s .= $read{$i};
        }
        return $s;
    }

    function parseAddress($read, $i) {
        $arg_a = array();

        for (; $read{$i} != ')'; ++$i) {
            $char = strtoupper($read{$i});
            switch ($char) {
                case '"':
                case '{':
                    $arg_a[] = ($char == '"' ? $this->parseQuote($read, $i) : $this->parseLiteral($read, $i));
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
            $adr = new AddressStructure();
            $adr->personal = $arg_a[0];
            $adr->adl = $arg_a[1];
            $adr->mailbox = $arg_a[2];
            $adr->host = $arg_a[3];
        } else {
            $adr = '';
        }
        return (array($adr, $i));
    }

    function parseDisposition($read, &$i) {
        $arg_a = array();
        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"': $arg_a[] = $this->parseQuote($read, $i); break;
                case '{': $arg_a[] = $this->parseLiteral($read, $i); break;
                case '(': $arg_a[] = $this->parseProperties($read, $i); break;
                default: break;
            }
        }

        if (isset($arg_a[0])) {
            $disp = new Disposition($arg_a[0]);
            if (isset($arg_a[1])) {
                $disp->properties = $arg_a[1];
            }
        }

        return (is_object($disp) ? $disp : '');
    }

    function parseLanguage($read, &$i) {
        /* no idea how to process this one without examples */
        $arg_a = array();

        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"': $arg_a[] = $this->parseQuote($read, $i); break;
                case '{': $arg_a[] = $this->parseLiteral($read, $i); break;
                case '(': $arg_a[] = $this->parseProperties($read, $i); break;
                default: break;
            }
        }

        if (isset($arg_a[0])) {
            $lang = new Language($arg_a[0]);
            if (isset($arg_a[1])) {
                $lang->properties = $arg_a[1];
            }
        }

        return (is_object($lang) ? $lang : '');
    }

    function parseParenthesis($read, $i) {
        for (; $read{$i} != ')'; ++$i) {
            switch ($read{$i}) {
                case '"': $this->parseQuote($read, $i); break;
                case '{': $this->parseLiteral($read, $i); break;
                case '(': $this->parseProperties($read, $i); break;
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
                $cnt = count($boundaries)-1;
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
                    if((strtolower($ent->header->disposition->name) != 'attachment') &&
                       (($ent->type0 != 'message') && ($ent->type1 != 'rfc822'))) {
                        $entity = $ent->findDisplayEntity($entity, $alt_order, $strict);
                        $found = true;
                    }
                }
            }
        } else { /* If not multipart, then just compare with each entry from $alt_order */
            $type = $this->type0.'/'.$this->type1;
            foreach ($alt_order as $alt) {
                if( ($alt == $type) && isset($this->entity_id) ) {
                    if ((count($this->entities) == 0) && 
                        (strtolower($this->header->disposition->name) != 'attachment')) {
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
            if (($this->type0 == 'text') &&
                in_array($this->type1, array('plain', 'html', 'message')) &&
                isset($this->entity_id)) {
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
        foreach($this->entities as $ent) {
            $type = $ent->header->type0 . '/' . $ent->header->type1;
            if ($type == 'multipart/related') {
                $type = $ent->header->getParameter('type');
            }
            $altCount = count($alt_order);
            for ($j = $best_view; $j < $altCount; ++$j) {
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

        $entCount = count($this->entities);
        for ($i = 0; $i < $entCount; ++$i) {
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

?>
