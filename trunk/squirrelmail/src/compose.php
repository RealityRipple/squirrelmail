<?php

/**
 * compose.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code sends a mail.
 *
 * There are 4 modes of operation:
 *    - Start new mail
 *    - Add an attachment
 *    - Send mail
 *    - Save As Draft
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'class/deliver/Deliver.class.php');

/* --------------------- Get globals ------------------------------------- */
$username = $_SESSION['username'];
$onetimepad = $_SESSION['onetimepad'];
$base_uri = $_SESSION['base_uri'];
$delimiter = $_SESSION['delimiter'];

if (isset($_POST['return'])) {
    $html_addr_search_done = 'Use Addresses';
}
if ( isset($_SESSION['composesession']) ) {
    $composesession = $_SESSION['composesession'];
}
sqextractGlobalVar('action');
sqextractGlobalVar('session');
sqextractGlobalVar('mailbox');
sqextractGlobalVar('identity');
sqextractGlobalVar('send_to');
sqextractGlobalVar('send_to_cc');
sqextractGlobalVar('send_to_bcc');
sqextractGlobalVar('subject');
sqextractGlobalVar('body');
sqextractGlobalVar('mailprio');
sqextractGlobalVar('request_mdn');
sqextractGlobalVar('request_dr');
sqextractGlobalVar('html_addr_search');
sqextractGlobalVar('mail_sent');
sqextractGlobalVar('passed_id');
sqextractGlobalVar('passed_ent_id');
sqextractGlobalVar('send');

if ( isset($_POST['sigappend']) ) {
    $sigappend = $_POST['sigappend'];
}
/* From addressbook search */
if ( isset($_POST['from_htmladdr_search']) ) {
    $from_htmladdr_search = $_POST['from_htmladdr_search'];
}
if ( isset($_POST['addr_search_done']) ) {
    $html_addr_search_done = $_POST['addr_search_done'];
}
if ( isset($_POST['send_to_search']) ) {
    $send_to_search = &$_POST['send_to_search'];
}

/* Attachments */
sqextractGlobalVar('attach');
if ( isset($_POST['do_delete']) ) {
    $do_delete = $_POST['do_delete'];
}
if ( isset($_POST['delete']) ) {
    $delete = &$_POST['delete'];
}
if ( isset($_SESSION['compose_messages']) ) {
    $compose_messages = &$_SESSION['compose_messages'];
}


/* Forward message as attachment */
if ( isset($_GET['attachedmessages']) ) {
    $attachedmessages = $_GET['attachedmessages'];
}

/* Drafts */
sqextractGlobalVar('draft');
sqextractGlobalVar('draft_id');
sqextractGlobalVar('ent_num');
sqextractGlobalVar('saved_draft');
sqextractGlobalVar('delete_draft');

$key = $_COOKIE['key'];

/* --------------------- Specific Functions ------------------------------ */

function replyAllString($header) {
   global $include_self_reply_all, $username, $data_dir;
   $excl_ar = array();
   /**
    * 1) Remove the addresses we'll be sending the message 'to'
    */
   $url_replytoall_avoid_addrs = '';
   if (isset($header->replyto)) {
      $excl_ar = $header->getAddr_a('replyto');
   }
   /**
    * 2) Remove our identities from the CC list (they still can be in the
    * TO list) only if $include_self_reply_all is turned off
    */
   if (!$include_self_reply_all) {
       $email_address = trim(getPref($data_dir, $username, 'email_address'));
       $excl_ar[$email_address] = '';
       $idents = getPref($data_dir, $username, 'identities');
       if ($idents != '' && $idents > 1) {
    	  $first_id = false;
          for ($i = 1; $i < $idents; $i ++) {
             $cur_email_address = getPref($data_dir, $username, 
                                         'email_address' . $i);
             $cur_email_address = strtolower($cur_email_address);
             $excl_ar[$cur_email_address] = '';
         }
       }
   }

   /** 
    * 3) get the addresses.
    */
   $url_replytoall_ar = $header->getAddr_a(array('to','cc'), $excl_ar);

   /** 
    * 4) generate the string.
    */
   $url_replytoallcc = '';
   foreach( $url_replytoall_ar as $email => $personal) {
      if ($personal) {
         $url_replytoallcc .= ", \"$personal\" <$email>";
      } else {
         $url_replytoallcc .= ', '. $email;    
      }
   }
   $url_replytoallcc = substr($url_replytoallcc,2);
   return $url_replytoallcc;
}

function getforwardHeader($orig_header) {
    global $editor_size;

   $display = array( _("Subject") => strlen(_("Subject")),
                     _("From")    => strlen(_("From")),          
                     _("Date")    => strlen(_("Date")),          
                     _("To")      => strlen(_("To")),            
                     _("Cc")      => strlen(_("Cc")) );
   $maxsize = max($display);
   $indent = str_pad('',$maxsize+2);
   foreach($display as $key => $val) {
      $display[$key] = $key .': '. str_pad('', $maxsize - $val);
   }      
   $bodyTop =  str_pad(' '._("Original Message").' ',$editor_size -2,'-',STR_PAD_BOTH) .
               "\n". $display[_("Subject")] . 
               decodeHeader($orig_header->subject) . "\n" .
               $display[_("From")] . 
               decodeHeader($orig_header->getAddr_s('from',"\n$indent")) . "\n" .
               $display[_("Date")] . 
               getLongDateString( $orig_header->date ). "\n" .
               $display[_("To")] . 
               decodeHeader($orig_header->getAddr_s('to',"\n$indent")) ."\n";
  if ($orig_header->cc != array() && $orig_header->cc !='') {
     $bodyTop .= $display[_("Cc")] . decodeHeader($orig_header->getAddr_s('cc',"\n$indent")) . "\n";
  }
  $bodyTop .= str_pad('', $editor_size -2 , '-') .
              "\n";
  return $bodyTop;
}
/* ----------------------------------------------------------------------- */

/*
 * If the session is expired during a post this restores the compose session 
 * vars.
 */
if (sqsession_is_registered('session_expired_post')) {
    $session_expired_post = $_SESSION['session_expired_post'];
    /* 
     * extra check for username so we don't display previous post data from
     * another user during this session.
     */
    if ($session_expired_post['username'] != $username) {
        unset($session_expired_post);
        sqsession_unregister('session_expired_post');
        session_write_close();
    } else {
        foreach ($session_expired_post as $postvar => $val) {
            if (isset($val)) {
                $$postvar = $val;
            } else {
                $$postvar = '';
            }
        }
        $compose_messages = unserialize(urldecode($restoremessages));
        sqsession_register($compose_messages,'compose_messages');
        sqsession_register($composesession,'composesession');
        if (isset($send)) {
            unset($send);
        }
        $session_expired = true;
    }
    unset($session_expired_post);
    sqsession_unregister('session_expired_post');
    session_write_close();
    if (!isset($mailbox)) {
        $mailbox = '';
    }

    if ($compose_new_win == '1') {
        compose_Header($color, $mailbox);
    } else {
        displayPageHeader($color, $mailbox);
    }
    showInputForm($session, false);
    exit();
}
if (!isset($composesession)) {
    $composesession = 0;
    sqsession_register(0,'composesession');
}

if (!isset($session) || (isset($newmessage) && $newmessage)) {
    sqsession_unregister('composesession');
    $session = "$composesession" +1; 
    $composesession = $session;
    sqsession_register($composesession,'composesession');
}     
if (!isset($compose_messages)) {
  $compose_messages = array();
}
if (!isset($compose_messages[$session]) || ($compose_messages[$session] == NULL)) {
/* if (!array_key_exists($session, $compose_messages)) {  /* We can only do this in PHP >= 4.1 */
  $composeMessage = new Message();
  $rfc822_header = new Rfc822Header();
  $composeMessage->rfc822_header = $rfc822_header;
  $composeMessage->reply_rfc822_header = '';
  $compose_messages[$session] = $composeMessage;
  sqsession_register($compose_messages,'compose_messages');  
} else {
  $composeMessage=$compose_messages[$session];
}

if (!isset($mailbox) || $mailbox == '' || ($mailbox == 'None')) {
    $mailbox = 'INBOX';
}

if ($draft) {
    /*
     * Set $default_charset to correspond with the user's selection
     * of language interface.
     */
    set_my_charset();
    $composeMessage=$compose_messages[$session];
    if (! deliverMessage($composeMessage, true)) {
        showInputForm($session);
        exit();
    } else {
        unset($compose_messages[$session]);
        $draft_message = _("Draft Email Saved");
        /* If this is a resumed draft, then delete the original */
        if(isset($delete_draft)) {
            Header("Location: delete_message.php?mailbox=" . urlencode($draft_folder) .
                   "&message=$delete_draft&sort=$sort&startMessage=1&saved_draft=yes");
            exit();
        }
        else {
            if ($compose_new_win == '1') {
                Header("Location: compose.php?saved_draft=yes&session=$composesession");
                exit();
            }
            else {
                Header("Location: right_main.php?mailbox=$draft_folder&sort=$sort".
                       "&startMessage=1&note=".urlencode($draft_message));
                exit();
            }
        }
    }
}

if ($send) {
    if (isset($_FILES['attachfile']) &&
        $_FILES['attachfile']['tmp_name'] &&
        $_FILES['attachfile']['tmp_name'] != 'none') {
        $AttachFailure = saveAttachedFiles($session);
    }
    if (checkInput(false) && !isset($AttachFailure)) {
        $urlMailbox = urlencode (trim($mailbox));
        if (! isset($passed_id)) {
            $passed_id = 0;
        }
        /*
         * Set $default_charset to correspond with the user's selection
         * of language interface.
         */
        set_my_charset();
        /*
         * This is to change all newlines to \n
         * We'll change them to \r\n later (in the sendMessage function)
         */
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\r", "\n", $body);

        /*
         * Rewrap $body so that no line is bigger than $editor_size
         * This should only really kick in the sqWordWrap function
         * if the browser doesn't support "VIRTUAL" as the wrap type.
         */
        $body = explode("\n", $body);
        $newBody = '';
        foreach ($body as $line) {
            if( $line <> '-- ' ) {
               $line = rtrim($line);
            }
            if (strlen($line) <= $editor_size + 1) {
                $newBody .= $line . "\n";
            } else {
                sqWordWrap($line, $editor_size);
                $newBody .= $line . "\n";
            }
        }
        $body = $newBody;
        do_hook('compose_send');
        $composeMessage=$compose_messages[$session];

	$Result = deliverMessage($composeMessage);
        if (! $Result) {
            showInputForm($session);
            exit();
        }
       unset($compose_messages[$session]);
        if ( isset($delete_draft)) {
            Header("Location: delete_message.php?mailbox=" . urlencode( $draft_folder ).
                   "&message=$delete_draft&sort=$sort&startMessage=1&mail_sent=yes");
            exit();
        }
        if ($compose_new_win == '1') {

            Header("Location: compose.php?mail_sent=yes");
        }
        else {
            Header("Location: right_main.php?mailbox=$urlMailbox&sort=$sort".
                   "&startMessage=1");
        }
    } else {
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }
        if (isset($AttachFailure)) {
             plain_error_message(_("Could not move/copy file. File not attached"),
                                 $color);
        }
        checkInput(true);
        showInputForm($session);
        /* sqimap_logout($imapConnection); */
    }
} elseif (isset($html_addr_search_done)) {
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }

    if (isset($send_to_search) && is_array($send_to_search)) {
        foreach ($send_to_search as $k => $v) {
            if (substr($k, 0, 1) == 'T') {
                if ($send_to) {
                    $send_to .= ', ';
                }
                $send_to .= $v;
            }
            elseif (substr($k, 0, 1) == 'C') {
                if ($send_to_cc) {
                    $send_to_cc .= ', ';
                }
                $send_to_cc .= $v;
            }
            elseif (substr($k, 0, 1) == 'B') {
                if ($send_to_bcc) {
                    $send_to_bcc .= ', ';
                }
                $send_to_bcc .= $v;
            }
        }
    }
    showInputForm($session);
} elseif (isset($html_addr_search)) {
    if (isset($_FILES['attachfile']) &&
        $_FILES['attachfile']['tmp_name'] &&
        $_FILES['attachfile']['tmp_name'] != 'none') {
        if(saveAttachedFiles($session)) {
            plain_error_message(_("Could not move/copy file. File not attached"), $color);
        }
    }
    /*
     * I am using an include so as to elminiate an extra unnecessary
     * click.  If you can think of a better way, please implement it.
     */
    include_once('./addrbook_search_html.php');
} elseif (isset($attach)) {
    if (saveAttachedFiles($session)) {
        plain_error_message(_("Could not move/copy file. File not attached"), $color);
    }
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }
    showInputForm($session);
}
elseif (isset($sigappend)) {
    $idents = getPref($data_dir, $username, 'identities', 0);
    if ($idents > 1) {
       if ($identity == 'default') {
          $no = 'g';
       } else {
          $no = $identity;
       }
       $signature = getSig($data_dir, $username, $no);
    }
    $body .= "\n\n".($prefix_sig==true? "-- \n":'').$signature;
    if ($compose_new_win == '1') {
         compose_Header($color, $mailbox);
    } else {
        displayPageHeader($color, $mailbox);
    }
    showInputForm($session);
} elseif (isset($do_delete)) {
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }

    if (isset($delete) && is_array($delete)) {
        $composeMessage = $compose_messages[$session];
        foreach($delete as $index) {
	    $attached_file = $composeMessage->entities[$index]->att_local_name;
            unlink ($attached_file);
	    unset ($composeMessage->entities[$index]);
        }
	$new_entities = array();
	foreach ($composeMessage->entities as $entity) {
	    $new_entities[] = $entity;
	}
	$composeMessage->entities = $new_entities;
	$compose_messages[$session] = $composeMessage;
	sqsession_register($compose_messages, 'compose_messages');
    }
    showInputForm($session);
} else {
    /*
     * This handles the default case as well as the error case
     * (they had the same code) --> if (isset($smtpErrors)) 
     */

    if ($compose_new_win == '1') {
       compose_Header($color, $mailbox);
    } else {
       displayPageHeader($color, $mailbox);
    }

    $newmail = true;

    if (!isset($passed_ent_id)) {
        $passed_ent_id = '';
    }
    if (!isset($passed_id)) {
        $passed_id = '';    
    }
    if (!isset($mailbox)) {
        $mailbox = '';
    }    
    if (!isset($action)) {
        $action = '';
    }
    
    $values = newMail($mailbox,$passed_id,$passed_ent_id, $action, $session);

    /* in case the origin is not read_body.php */
    if (isset($send_to)) {
       $values['send_to'] = $send_to;
    }
    if (isset($send_to_cc)) {
       $values['send_to_cc'] = $send_to_cc;
    }
    if (isset($send_to_bcc)) {
       $values['send_to_bcc'] = $send_to_bcc;
    }
    showInputForm($session, $values);
}

exit();

/**************** Only function definitions go below *************/


/* This function is used when not sending or adding attachments */
function newMail ($mailbox='', $passed_id='', $passed_ent_id='', $action='', $session='') {
    global $editor_size, $default_use_priority, $body,
           $use_signature, $composesession, $data_dir, $username,
           $username, $key, $imapServerAddress, $imapPort, $compose_messages,
	   $composeMessage;

    $send_to = $send_to_cc = $send_to_bcc = $subject = $identity = '';
    $mailprio = 3;

    if ($passed_id) {
        $imapConnection = sqimap_login($username, $key, $imapServerAddress,
        $imapPort, 0);

        sqimap_mailbox_select($imapConnection, $mailbox);
        $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
	
        $body = '';
        if ($passed_ent_id) {
            /* redefine the messsage in case of message/rfc822 */
            $message = $message->getEntity($passed_ent_id);
            /* message is an entity which contains the envelope and type0=message
            * and type1=rfc822. The actual entities are childs from
            * $message->entities[0]. That's where the encoding and is located
            */

            $entities = $message->entities[0]->findDisplayEntity
            (array(), $alt_order = array('text/plain'));
            if (!count($entities)) {
                $entities = $message->entities[0]->findDisplayEntity
                (array(), $alt_order = array('text/plain','html/plain'));
            }
            $orig_header = $message->rfc822_header; /* here is the envelope located */
            /* redefine the message for picking up the attachments */
            $message = $message->entities[0];

        } else {
            $entities = $message->findDisplayEntity (array(), $alt_order = array('text/plain'));
            if (!count($entities)) {
                $entities = $message->findDisplayEntity (array(), $alt_order = array('text/plain','html/plain'));
            }
            $orig_header = $message->rfc822_header;
        }
	
        $encoding = $message->header->encoding;
        $type0 = $message->type0;
        $type1 = $message->type1;
        foreach ($entities as $ent) {
            $unencoded_bodypart = mime_fetch_body($imapConnection, $passed_id, $ent);
            $body_part_entity = $message->getEntity($ent);
            $bodypart = decodeBody($unencoded_bodypart,
            $body_part_entity->header->encoding);
            if ($type1 == 'html') {
                $bodypart = strip_tags($bodypart);
            }
            $body .= $bodypart;
        }
        if ($default_use_priority) {
            $mailprio = substr($orig_header->priority,0,1);
            if (!$mailprio) {
                $mailprio = 3;
            }
        } else {
            $mailprio = '';
        }
        //ClearAttachments($session);

        $identity = '';
        $idents = getPref($data_dir, $username, 'identities');
        $from_o = $orig_header->from;
        if (is_object($from_o)) {
            $orig_from = $from_o->getAddress();
        } else {
            $orig_from = '';
        }
	$identities = array();
        if (!empty($idents) && $idents > 1) {
            $identities[]  = '"'. getPref($data_dir, $username, 'full_name') 
	      . '" <' .  getPref($data_dir, $username, 'email_address') . '>';
	    for ($i = 1; $i < $idents; $i++) {
                $enc_from_name = '"'. 
		    getPref($data_dir, $username, 'full_name' . $i) .
            	    '" <' . 
		    getPref($data_dir, $username, 'email_address' . $i) . '>';
                if ($enc_from_name == $orig_from && $i) {
                    $identity = $i;
                    break;
                }
		$identities[] = $enc_from_name;
            }
	    $identity_match = $orig_header->findAddress($identities);
	    if ($identity_match) {
		$identity = $identity_match;
	    }
        }

        switch ($action) {
        case ('draft'):
            $use_signature = FALSE;
            $send_to = decodeHeader($orig_header->getAddr_s('to'));
            $send_to_cc = decodeHeader($orig_header->getAddr_s('cc'));
            $send_to_bcc = decodeHeader($orig_header->getAddr_s('bcc'));
            $subject = decodeHeader($orig_header->subject);

            $body_ary = explode("\n", $body);
            $cnt = count($body_ary) ;
            $body = '';
            for ($i=0; $i < $cnt; $i++) {
                if (!ereg("^[>\\s]*$", $body_ary[$i])  || !$body_ary[$i]) {
                    sqWordWrap($body_ary[$i], $editor_size );
                    $body .= $body_ary[$i] . "\n";
                }
                unset($body_ary[$i]);
            }
            sqUnWordWrap($body);
            $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);
            break;
        case ('edit_as_new'):
            $send_to = decodeHeader($orig_header->getAddr_s('to'));
            $send_to_cc = decodeHeader($orig_header->getAddr_s('cc'));
            $send_to_bcc = decodeHeader($orig_header->getAddr_s('bcc'));
            $subject = decodeHeader($orig_header->subject);
            $mailprio = $orig_header->priority;
            $orig_from = '';
            $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);
            sqUnWordWrap($body);
            break;
        case ('forward'):
            $send_to = '';
            $subject = decodeHeader($orig_header->subject);
            if ((substr(strtolower($subject), 0, 4) != 'fwd:') &&
                (substr(strtolower($subject), 0, 5) != '[fwd:') &&
                (substr(strtolower($subject), 0, 6) != '[ fwd:')) {
                $subject = '[Fwd: ' . $subject . ']';
            }
            $body = getforwardHeader($orig_header) . $body;
            sqUnWordWrap($body);
            $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);
	    $body = "\n" . $body;
            break;
        case ('forward_as_attachment'):
            $composeMessage = getMessage_RFC822_Attachment($message, $composeMessage, $passed_id, $passed_ent_id, $imapConnection);
            $body = '';
            break;
        case ('reply_all'):
            $send_to_cc = replyAllString($orig_header);
        case ('reply'):
            $send_to = $orig_header->reply_to;
            if (is_array($send_to) && count($send_to)) {
                $send_to = decodeHeader($orig_header->getAddr_s('reply_to'));
            } else if (is_object($send_to)) { /* unnessecarry, just for falesafe purpose */
                $send_to = decodeHeader($orig_header->getAddr_s('reply_to'));
	    } else {
                $send_to = decodeHeader($orig_header->getAddr_s('from'));
            }
            $subject =  decodeHeader($orig_header->subject);
            $subject = str_replace('"', "'", $subject);
            $subject = trim($subject);
            if (substr(strtolower($subject), 0, 3) != 're:') {
                $subject = 'Re: ' . $subject;
            }
            /* this corrects some wrapping/quoting problems on replies */
            $rewrap_body = explode("\n", $body);
	        $from =  (is_array($orig_header->from)) ? 
	                $orig_header->from[0] : $orig_header->from;
            $body = getReplyCitation($from->getAddress(false));
            sqUnWordWrap($body);
            $cnt = count($rewrap_body);
            for ($i=0;$i<$cnt;$i++) {
              sqWordWrap($rewrap_body[$i], ($editor_size));
                if (preg_match("/^(>+)/", $rewrap_body[$i], $matches)) {
                    $gt = $matches[1];
                    $body .= '>' . str_replace("\n", "\n>$gt ", rtrim($rewrap_body[$i])) ."\n";
                } else {
                    $body .= '> ' . str_replace("\n", "\n> ", rtrim($rewrap_body[$i])) . "\n";
                }
                unset($rewrap_body[$i]);
            }
            $composeMessage->reply_rfc822_header = $orig_header;
            break;
            default:
            break;
        }
	$compose_messages[$session] = $composeMessage;
	sqsession_register($compose_messages, 'compose_messages');
        session_write_close();
        sqimap_logout($imapConnection);
    }
    $ret = array( 'send_to' => $send_to,
                  'send_to_cc' => $send_to_cc,
                  'send_to_bcc' => $send_to_bcc,
                  'subject' => $subject,
                  'mailprio' => $mailprio,
                  'body' => $body,
                  'identity' => $identity );

    return ($ret);
} /* function newMail() */

function getAttachments($message, &$composeMessage, $passed_id, $entities, $imapConnection) {
    global $attachment_dir, $username, $data_dir, $squirrelmail_language;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!count($message->entities) || 
       ($message->type0 == 'message' && $message->type1 == 'rfc822')) {
        if ( !in_array($message->entity_id, $entities) && $message->entity_id) {
	   switch ($message->type0) {
	   case 'message':
    	     if ($message->type1 == 'rfc822') {
                $filename = decodeHeader($message->rfc822_header->subject.'.eml');
                if ($filename == "") {
                    $filename = "untitled-".$message->entity_id.'.eml';
                }
	     } else {
               $filename = decodeHeader($message->getFilename());
             }
	     break;
	   default:
	     $filename = decodeHeader($message->getFilename());
	     break;
	   }
           if (isset($languages[$squirrelmail_language]['XTRA_CODE']) && 
	       function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
                $filename =  $languages[$squirrelmail_language]['XTRA_CODE']('encode', $filename);
           }
	   
           $localfilename = GenerateRandomString(32, '', 7);
           $full_localfilename = "$hashed_attachment_dir/$localfilename";
           while (file_exists($full_localfilename)) {
               $localfilename = GenerateRandomString(32, '', 7);
               $full_localfilename = "$hashed_attachment_dir/$localfilename";
           }
	   $message->att_local_name = $full_localfilename;
	   if (!$message->mime_header) { /* temporary hack */
	      $message->mime_header = $message->header;
	   }
	   
	   $composeMessage->addEntity($message);
	    
           /* Write Attachment to file */
           $fp = fopen ("$hashed_attachment_dir/$localfilename", 'wb');
           fputs($fp, decodeBody(mime_fetch_body($imapConnection,
              $passed_id, $message->entity_id),
              $message->header->encoding));
           fclose ($fp);
        }
    } else {
        for ($i=0, $entCount=count($message->entities); $i<$entCount;$i++) {
            $composeMessage=getAttachments($message->entities[$i], $composeMessage, $passed_id, $entities, $imapConnection);
        }
    }
//    setPref($data_dir, $username, 'attachments', serialize($attachments));
    return $composeMessage;
}

function getMessage_RFC822_Attachment($message, $composeMessage, $passed_id, 
                                      $passed_ent_id='', $imapConnection) {
    global $attachments, $attachment_dir, $username, $data_dir, $uid_support;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!$passed_ent_id) {
        $body_a = sqimap_run_command($imapConnection, 
                                    'FETCH '.$passed_id.' RFC822',
                                    TRUE, $response, $readmessage, 
                                    $uid_support);
    } else {
        $body_a = sqimap_run_command($imapConnection, 
                                     'FETCH '.$passed_id.' BODY['.$passed_ent_id.']',
                                     TRUE, $response, $readmessage, $uid_support);
        $message = $message->parent;
    }
    if ($response = 'OK') {
        $subject = encodeHeader($message->rfc822_header->subject);
        array_shift($body_a);
        $body = implode('', $body_a) . "\r\n";
                
        $localfilename = GenerateRandomString(32, 'FILE', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
            
        $fp = fopen( $full_localfilename, 'w');
        fwrite ($fp, $body);
        fclose($fp);
	$composeMessage->initAttachment('message/rfc822',$subject.'.eml', 
	                 $full_localfilename);
    }
    return $composeMessage;
}

function showInputForm ($session, $values=false) {
    global $send_to, $send_to_cc, $body,
           $passed_body, $color, $use_signature, $signature, $prefix_sig,
           $editor_size, $attachments, $subject, $newmail,
           $use_javascript_addr_book, $send_to_bcc, $passed_id, $mailbox,
           $from_htmladdr_search, $location_of_buttons, $attachment_dir,
           $username, $data_dir, $identity, $draft_id, $delete_draft,
           $mailprio, $default_use_mdn, $mdn_user_support, $compose_new_win,
           $saved_draft, $mail_sent, $sig_first, $edit_as_new, $action, 
           $username, $compose_messages, $composesession;

    $composeMessage = $compose_messages[$session];

    $subject = decodeHeader($subject, false);
    if ($values) {
       $send_to = $values['send_to'];
       $send_to_cc = $values['send_to_cc'];
       $send_to_bcc = $values['send_to_bcc'];
       $subject = $values['subject'];       
       $mailprio = $values['mailprio'];
       $body = $values['body'];
       $identity = $values['identity'];
    }
    
    if ($use_javascript_addr_book) {
        echo "\n". '<SCRIPT LANGUAGE=JavaScript><!--' . "\n" .
             'function open_abook() { ' . "\n" .
             '  var nwin = window.open("addrbook_popup.php","abookpopup",' .
             '"width=670,height=300,resizable=yes,scrollbars=yes");' . "\n" .
             '  if((!nwin.opener) && (document.windows != null))' . "\n" .
             '    nwin.opener = document.windows;' . "\n" .
             "}\n" .
             '// --></SCRIPT>' . "\n\n";
    }

    echo "\n" . '<FORM name=compose action="compose.php" METHOD=POST ' .
         'ENCTYPE="multipart/form-data"';
    do_hook("compose_form");
    
    echo ">\n";

    if ($action == 'draft') {
        echo '<input type="hidden" name="delete_draft" value="' . $passed_id . "\">\n";
    }
    if (isset($delete_draft)) {
        echo '<input type="hidden" name="delete_draft" value="' . $delete_draft. "\">\n";
    }
    if (isset($session)) {
        echo '<input type="hidden" name="session" value="' . $session . "\">\n";
    }
    
    if (isset($passed_id)) {
        echo '<input type="hidden" name="passed_id" value="' . $passed_id . "\">\n";
    }

    if ($saved_draft == 'yes') {
        echo '<BR><CENTER><B>'. _("Draft Saved").'</CENTER></B>';
    }
    if ($mail_sent == 'yes') {
        echo '<BR><CENTER><B>'. _("Your Message has been sent").'</CENTER></B>';
    }
    echo '<TABLE ALIGN=center CELLSPACING=0 BORDER=0>' . "\n";
    if ($compose_new_win == '1') {
        echo '<TABLE ALIGN=CENTER BGCOLOR="'.$color[0].'" WIDTH="100%" BORDER=0>'."\n" .
             '   <TR><TD></TD><TD ALIGN="RIGHT"><INPUT TYPE="BUTTON" NAME="Close" onClick="return self.close()" VALUE='._("Close").'></TD></TR>'."\n";
    }
    if ($location_of_buttons == 'top') {
        showComposeButtonRow();
    }

    $idents = getPref($data_dir, $username, 'identities', 0);
    if ($idents > 1) {
        echo '   <TR>' . "\n" .
             '      <TD BGCOLOR="' . $color[4] . '" WIDTH="10%" ALIGN=RIGHT>' .
                    _("From:") . '</TD>' . "\n" .
             '      <TD BGCOLOR="' . $color[4] . '" WIDTH="90%">' . "\n" .
             '         <select name=identity>' . "\n" .
             '         <option value=default>' .
                       htmlspecialchars(getPref($data_dir, $username, 'full_name'));
        $em = getPref($data_dir, $username, 'email_address');
        if ($em != '') {
            echo htmlspecialchars(' <' . $em . '>') . "\n";
        }
        for ($i = 1; $i < $idents; $i ++) {
            echo '<option value="' . $i . '"';
            if (isset($identity) && $identity == $i) {
                echo ' SELECTED';
            }
            echo '>' . htmlspecialchars(getPref($data_dir, $username,
                                                'full_name' . $i));
            $em = getPref($data_dir, $username, 'email_address' . $i);
            if ($em != '') {
                echo htmlspecialchars(' <' . $em . '>') . "\n";
            }
            echo '</option>';
        }
        echo '</select>' . "\n" .
             '      </TD>' . "\n" .
             '   </TR>' . "\n";
    }
    echo '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" WIDTH="10%" ALIGN=RIGHT>' .
                _("To:") . '</TD>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" WIDTH="90%">' . "\n" .
         '         <INPUT TYPE=text NAME="send_to" VALUE="' .
                   htmlspecialchars($send_to) . '" SIZE=60><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' .
                _("CC:") . '</TD>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n" .
         '         <INPUT TYPE=text NAME="send_to_cc" SIZE=60 VALUE="' .
                   htmlspecialchars($send_to_cc) . '"><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' .
                _("BCC:") . '</TD>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n" .
         '         <INPUT TYPE=text NAME="send_to_bcc" VALUE="' .
                htmlspecialchars($send_to_bcc) . '" SIZE=60><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' .
                _("Subject:") . '</TD>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n";
    echo '         <INPUT TYPE=text NAME=subject SIZE=60 VALUE="' .
                   htmlspecialchars($subject) . '">' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n\n";

    if ($location_of_buttons == 'between') {
        showComposeButtonRow();
    }

    if ($compose_new_win == '1') {
        echo '   <TR>' . "\n" .
             '      <TD BGCOLOR="' . $color[0] . '" COLSPAN=2 ALIGN=CENTER>' . "\n" .
             '         <TEXTAREA NAME=body ROWS=20 COLS="' .
                       $editor_size . '" WRAP="VIRTUAL">';
    }
    else {
        echo '   <TR>' . "\n" .
            '      <TD BGCOLOR="' . $color[4] . '" COLSPAN=2>' . "\n" .
            '         &nbsp;&nbsp;<TEXTAREA NAME=body ROWS=20 COLS="' .
                      $editor_size . '" WRAP="VIRTUAL">';
    }
    if ($use_signature == true && $newmail == true && !isset($from_htmladdr_search)) {
        if ($sig_first == '1') {
            if ($charset == 'iso-2022-jp') {
                echo "\n\n".($prefix_sig==true? "-- \n":'').mb_convert_encoding($signature, 'EUC-JP');
            } else {
            echo "\n\n".($prefix_sig==true? "-- \n":'').htmlspecialchars($signature);
            }
            echo "\n\n".htmlspecialchars($body);
        }
        else {
            echo "\n\n".htmlspecialchars($body);
            if ($charset == 'iso-2022-jp') {
                echo "\n\n".($prefix_sig==true? "-- \n":'').mb_convert_encoding($signature, 'EUC-JP');
            }else{
            echo "\n\n".($prefix_sig==true? "-- \n":'').htmlspecialchars($signature);
        }
    }
    }
    else {
       echo htmlspecialchars($body);
    }
    echo '</TEXTAREA><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n";

    if ($location_of_buttons == 'bottom') {
        showComposeButtonRow();
    } else {
        echo '   <TR>' . "\n" .
             '      <TD COLSPAN=2 ALIGN=RIGHT>' . "\n" .
             '         <INPUT TYPE=SUBMIT NAME=send VALUE="' . _("Send") . '">' . "\n" .
             '         &nbsp;&nbsp;&nbsp;&nbsp;<BR><BR>' . "\n" .
             '      </TD>' . "\n" .
             '   </TR>' . "\n";
    }

    /* This code is for attachments */
    echo '   <TR>' . "\n" .
         '      <TD COLSPAN=2>' . "\n" .
         '         <table width="100%" cellpadding="1" cellspacing="0" align="center"'.
                   ' border="0" bgcolor="'.$color[9].'">' . "\n" .
         '            <TR>' . "\n" .
         '               <TD>' . "\n" .
         '                 <table width="100%" cellpadding="3" cellspacing="0" align="center"'.
                           ' border="0">' . "\n" .
         '                    <TR>' . "\n" .
         '                       <TD VALIGN=MIDDLE ALIGN=RIGHT>' .
                                 _("Attach:") . '</TD>' . "\n" .
         '                       <TD VALIGN=MIDDLE ALIGN=LEFT>' . "\n" .
         '                          <INPUT NAME="attachfile" SIZE=48 TYPE="file">' . "\n" .
         '                          &nbsp;&nbsp;<input type="submit" name="attach"' .
                                    ' value="' . _("Add") .'">' . "\n" .
         '                       </TD>' . "\n" .
         '                    </TR>' . "\n";
    

    $s_a = array();
    if ($composeMessage->entities) {
        foreach ($composeMessage->entities as $key => $attachment) {
           $attached_file = $attachment->att_local_name;
	   if ($attachment->att_local_name || $attachment->body_part) { 
		$attached_filename = decodeHeader($attachment->mime_header->getParameter('name'));
		$type = $attachment->mime_header->type0.'/'.
		        $attachment->mime_header->type1;
        	$s_a[] = '<input type="checkbox" name="delete[]" value="' . 
		         $key . "\">\n" . $attached_filename . ' - ' . $type . 
		         ' ('.show_readable_size( filesize( $attached_file ) ) 
		         . ')<br>'."\n";
           }
        }
    }
    if (count($s_a)) {
       foreach ($s_a as $s) {
          echo '<tr><td align=left colspan="2" bgcolor="' . $color[0] . '">'.$s.'</td></tr>';
       }         
       echo '<tr><td colspan="2"><input type="submit" name="do_delete" value="' .
            _("Delete selected attachments") . "\">\n" .
            '</td></tr>';
    }
    echo '                  </table>' . "\n" .
         '               </td>' . "\n" .
         '            </tr>' . "\n" .
         '         </TABLE>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n";

    /* End of attachment code */
    if ($compose_new_win == '1') {
        echo '</TABLE>'."\n";
    }
    echo '</TABLE>' . "\n" .
         '<input type="hidden" name="username" value="'. $username . "\">\n" .   
         '<input type=hidden name=action value="' . $action . "\">\n" .
         '<INPUT TYPE=hidden NAME=mailbox VALUE="' . htmlspecialchars($mailbox) .
         "\">\n";
    /* 
       store the complete ComposeMessages array in a hidden input value 
       so we can restore them in case of a session timeout.
    */
    echo '<input type=hidden name=restoremessages value="' . urlencode(serialize($compose_messages)) . "\">\n";
    echo '<input type=hidden name=composesession value="' . $composesession . "\">\n";
    echo '<input type=hidden name=querystring value="' . $_SERVER['QUERY_STRING'] . "\">\n";
    echo '</FORM>';
    do_hook('compose_bottom');
    echo '</BODY></HTML>' . "\n";
}


function showComposeButtonRow() {
    global $use_javascript_addr_book, $save_as_draft,
           $default_use_priority, $mailprio, $default_use_mdn,
           $request_mdn, $request_dr,
           $data_dir, $username;

    echo '   <TR>' . "\n" .
         '      <TD></TD>' . "\n" .
         '      <TD>' . "\n";
    if ($default_use_priority) {
        if(!isset($mailprio)) {
            $mailprio = "3";
    }
    echo '          ' . _("Priority") .': <select name="mailprio">'.
         '<option value="1"'.($mailprio=='1'?' selected':'').'>'. _("High") .'</option>'.
         '<option value="3"'.($mailprio=='3'?' selected':'').'>'. _("Normal") .'</option>'.
         '<option value="5"'.($mailprio=='5'?' selected':'').'>'. _("Low").'</option>'.
         '</select>' . "\n";
    }
    $mdn_user_support=getPref($data_dir, $username, 'mdn_user_support',$default_use_mdn);
    if ($default_use_mdn) {
        if ($mdn_user_support) {
            echo '          ' . _("Receipt") .': '.
            '<input type="checkbox" name="request_mdn" value=1'.
        ($request_mdn=='1'?' checked':'') .'>'. _("On Read").
            ' <input type="checkbox" name="request_dr" value=1'.
        ($request_dr=='1'?' checked':'') .'>'. _("On Delivery");
        }
    }

    echo '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>'  . "\n" .
         '      <TD></TD>' . "\n" .
         '      <TD>' . "\n" .
         '         <INPUT TYPE=SUBMIT NAME="sigappend" VALUE="' . _("Signature") . '">' . "\n";
    if ($use_javascript_addr_book) {
        echo "         <SCRIPT LANGUAGE=JavaScript><!--\n document.write(\"".
             "            <input type=button value=\\\""._("Addresses").
                                 "\\\" onclick='javascript:open_abook();'>\");".
             "            // --></SCRIPT><NOSCRIPT>\n".
             "            <input type=submit name=\"html_addr_search\" value=\"".
                              _("Addresses")."\">".
             "         </NOSCRIPT>\n";
    } else {
        echo '         <input type=submit name="html_addr_search" value="'.
                                 _("Addresses").'">' . "\n";
    }

    if ($save_as_draft) {
        echo '         <input type="submit" name ="draft" value="' . _("Save Draft") . "\">\n";
    }

    echo '         <INPUT TYPE=submit NAME=send VALUE="'. _("Send") . '">' . "\n";
    do_hook('compose_button_row');

    echo '      </TD>' . "\n" .
         '   </TR>' . "\n\n";
}

function checkInput ($show) {
    /*
     * I implemented the $show variable because the error messages
     * were getting sent before the page header.  So, I check once
     * using $show=false, and then when i'm ready to display the error
     * message, show=true
     */
    global $body, $send_to, $send_to_bcc, $subject, $color;

    if ($send_to == '' && $send_to_bcc == '') {
        if ($show) {
            plain_error_message(_("You have not filled in the \"To:\" field."), $color);
        }
        return false;
    }
    return true;
} /* function checkInput() */


/* True if FAILURE */
function saveAttachedFiles($session) {
    global $_FILES, $attachment_dir, $attachments, $username,
           $data_dir, $compose_messages;

    /* get out of here if no file was attached at all */
    if (! is_uploaded_file($_FILES['attachfile']['tmp_name']) ) {
        return true;
    }

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    $localfilename = GenerateRandomString(32, '', 7);
    $full_localfilename = "$hashed_attachment_dir/$localfilename";
    while (file_exists($full_localfilename)) {
        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
    }

    if (!@rename($_FILES['attachfile']['tmp_name'], $full_localfilename)) {
	if (function_exists("move_uploaded_file")) {
            if (!@move_uploaded_file($_FILES['attachfile']['tmp_name'],$full_localfilename)) {
        	return true;
    	    }
	} else {
    	    if (!@copy($_FILES['attachfile']['tmp_name'], $full_localfilename)) {
                return true;
            }
	}
    }
    $message = $compose_messages[$session];
    $type = strtolower($_FILES['attachfile']['type']);
    $name = $_FILES['attachfile']['name'];
    $message->initAttachment($type, $name, $full_localfilename);
    $compose_messages[$session] = $message;
}

function ClearAttachments($composeMessage) {
    if ($composeMessage->att_local_name) {
        $attached_file = $composeMessage->att_local_name;
        if (file_exists($attached_file)) {
            unlink($attached_file);
        }
    }
    for ($i=0, $entCount=count($composeMessage->entities);$i< $entCount; ++$i) {
        ClearAttachments($composeMessage->entities[$i]);
    }
}



function getReplyCitation($orig_from) {
    global $reply_citation_style, $reply_citation_start, $reply_citation_end;

    /* First, return an empty string when no citation style selected. */
    if (($reply_citation_style == '') || ($reply_citation_style == 'none')) {
        return '';
    }

    /* Make sure our final value isn't an empty string. */
    if ($orig_from == '') {
        return '';
    }

    /* Otherwise, try to select the desired citation style. */
    switch ($reply_citation_style) {
    case 'author_said':
        $start = '';
        $end   = ' ' . _("said") . ':';
        break;
    case 'quote_who':
        $start = '<' . _("quote") . ' ' . _("who") . '="';
        $end   = '">';
        break;
    case 'user-defined':
        $start = $reply_citation_start . 
         ($reply_citation_start == '' ? '' : ' ');
        $end   = $reply_citation_end;
        break;
    default:
        return '';
    }

    /* Build and return the citation string. */
    return ($start . $orig_from . $end . "\n");
}

/* temporary function to make use of the deliver class.
   In the future the responsable backend should be automaticly loaded
   and conf.pl should show a list of available backends.
   The message also should be constructed by the message class.
*/

function deliverMessage($composeMessage, $draft=false) {
    global $send_to, $send_to_cc, $send_to_bcc, $mailprio, $subject, $body,
           $username, $popuser, $usernamedata, $identity, $data_dir,
	   $request_mdn, $request_dr, $default_charset, $color, $useSendmail,
	   $domain, $action;
    global $imapServerAddress, $imapPort, $sent_folder, $key;

    $rfc822_header = $composeMessage->rfc822_header;
    $rfc822_header->to = $rfc822_header->parseAddress($send_to,true, array(), '', $domain);
    $rfc822_header->cc = $rfc822_header->parseAddress($send_to_cc,true,array(), '',$domain);
    $rfc822_header->bcc = $rfc822_header->parseAddress($send_to_bcc,true, array(), '',$domain);
    $rfc822_header->priority = $mailprio;
    $rfc822_header->subject = $subject;
    $special_encoding='';
    if (strtolower($default_charset) == 'iso-2022-jp') {
        if (mb_detect_encoding($body) == 'ASCII') {
	    $special_encoding = '8bit';
        } else {
            $body = mb_convert_encoding($body, 'JIS');
            $special_encoding = '7bit';
        }
    }
    $composeMessage->setBody($body);

    if (ereg("^([^@%/]+)[@%/](.+)$", $username, $usernamedata)) {
       $popuser = $usernamedata[1];
       $domain  = $usernamedata[2];
       unset($usernamedata);
    } else {
       $popuser = $username;
    }
    $reply_to = '';
    if (isset($identity) && $identity != 'default') {
        $from_mail = getPref($data_dir, $username,'email_address' . $identity);
        $full_name = getPref($data_dir, $username,'full_name' . $identity);
        $reply_to = getPref($data_dir, $username,'reply_to' . $identity);
    } else {
        $from_mail = getPref($data_dir, $username, 'email_address');
        $full_name = getPref($data_dir, $username, 'full_name');
        $reply_to = getPref($data_dir, $username,'reply_to');
    }
    if (!$from_mail) {
       $from_mail = "$popuser@$domain";
       $full_name = '';
    }
    $rfc822_header->from = $rfc822_header->parseAddress($from_mail,true);
    if ($full_name) {
        $from = $rfc822_header->from[0];
	if (!$from->host) $from->host = $domain;
	$from_addr = '"'.$full_name .'" <'.$from->mailbox.'@'.$from->host.'>';
        $rfc822_header->from = $rfc822_header->parseAddress($from_addr,true);
    }
    if ($reply_to) {
       $rfc822_header->reply_to = $rfc822_header->parseAddress($reply_to,true);
    }
    /* Receipt: On Read */
    if (isset($request_mdn) && $request_mdn) {
       $rfc822_header->dnt = $rfc822_header->parseAddress($from_mail,true);
    }
    /* Receipt: On Delivery */
    if (isset($request_dr) && $request_dr) {
       $rfc822_header->more_headers['Return-Receipt-To'] = $from_mail; 
    }
    /* multipart messages */
    if (count($composeMessage->entities)) {
        $message_body = new Message();
	$message_body->body_part = $composeMessage->body_part;
	$composeMessage->body_part = '';
	$mime_header = new MessageHeader;
	$mime_header->type0 = 'text';
	$mime_header->type1 = 'plain';
	if ($special_encoding) {
	    $mime_header->encoding = $special_encoding;
	} else {    
	    $mime_header->encoding = 'us-ascii';
	}
	if ($default_charset) {
	    $mime_header->parameters['charset'] = $default_charset;
	}
	$message_body->mime_header = $mime_header;  
        array_unshift($composeMessage->entities, $message_body);
	$content_type = new ContentType('multipart/mixed');
    } else {
    	$content_type = new ContentType('text/plain');
    }
    if ($default_charset) {
	$content_type->properties['charset']=$default_charset;
    }
        
    $rfc822_header->content_type = $content_type;
    $composeMessage->rfc822_header = $rfc822_header;

    if (!$useSendmail && !$draft) {
	require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
	$deliver = new Deliver_SMTP();
	global $smtpServerAddress, $smtpPort, $use_authenticated_smtp, $pop_before_smtp;
	if ($use_authenticated_smtp) {
    	    global $key, $onetimepad;
    	    $user = $username;
    	    $pass = OneTimePadDecrypt($key, $onetimepad);
	} else {
    	    $user = '';
    	    $pass = '';
	}
	$authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
	$stream = $deliver->initStream($composeMessage,$domain,0,
	                  $smtpServerAddress, $smtpPort, $user, $pass, $authPop);
    } elseif (!$draft) {
       require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
       global $sendmail_path;
       $deliver = new Deliver_SendMail();
       $stream = $deliver->initStream($composeMessage,$sendmail_path);
    } elseif ($draft) {
       global $draft_folder;
       require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
       $imap_stream = sqimap_login($username, $key, $imapServerAddress,
                      $imapPort, 0);
       if (sqimap_mailbox_exists ($imap_stream, $draft_folder)) {
           require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
	   $imap_deliver = new Deliver_IMAP();
	   $length = $imap_deliver->mail($composeMessage);
	   sqimap_append ($imap_stream, $draft_folder, $length);	 
           $imap_deliver->mail($composeMessage, $imap_stream);
    	   sqimap_append_done ($imap_stream, $draft_folder);
	   sqimap_logout($imap_stream);
	   unset ($imap_deliver);
	   return $length;
        } else {
	   $msg  = '<br>Error: '._("Draft folder")." $draft_folder" . ' does not exist.';
	   plain_error_message($msg, $color);
	   return false;
	}
    }
    $succes = false;
    if ($stream) {
	$length = $deliver->mail($composeMessage, $stream);
	$succes = $deliver->finalizeStream($stream);
    }
    if (!$succes) {
        $msg  = $deliver->dlv_msg . '<br>Server replied: '.$deliver->dlv_ret_nr;
        plain_error_message($msg, $color);
    } else {
        unset ($deliver);
        $imap_stream = sqimap_login($username, $key, $imapServerAddress,
        $imapPort, 0);
	if (sqimap_mailbox_exists ($imap_stream, $sent_folder)) {
    	    sqimap_append ($imap_stream, $sent_folder, $length);
	    require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
	    $imap_deliver = new Deliver_IMAP();
	    $imap_deliver->mail($composeMessage, $imap_stream);
    	    sqimap_append_done ($imap_stream, $sent_folder);
	    unset ($imap_deliver);
	}
	global $passed_id, $mailbox, $action;
	ClearAttachments($composeMessage);
	if ($action == 'reply' || $action == 'reply_all') {
	    sqimap_mailbox_select ($imap_stream, $mailbox);
	    sqimap_messages_flag ($imap_stream, $passed_id, $passed_id, 'Answered', true);
	}
    	sqimap_logout($imap_stream);	
    }
    return $succes;
}

?>
