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

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/date.php');
require_once('../functions/mime.php');
require_once('../functions/smtp.php');
require_once('../functions/plugin.php');
require_once('../functions/display_messages.php');

/* --------------------- Specific Functions ------------------------------ */

function replyAllString($header) {
   global $include_self_reply_all, $username, $data_dir;
   $excl_arr = array();
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

   $display = array(
                     _("Subject") => strlen(_("Subject")),
                     _("From")    => strlen(_("From")),		     
                     _("Date")    => strlen(_("Date")),		     
                     _("To")      => strlen(_("To")),		     
                     _("Cc")      => strlen(_("Cc"))		     
                    );
   $maxsize = max($display);
   $indent = str_pad('',$maxsize+2);
   foreach($display as $key => $val) {
      $display[$key] = $key .': '. str_pad('', $maxsize - $val);
   }      
   $bodyTop =  str_pad(' '._("Original Message").' ',$editor_size -2,'-',STR_PAD_BOTH);
   $bodyTop .=  "\n". $display[_("Subject")] . decodeHeader($orig_header->subject) . "\n" .
        $display[_("From")] . decodeHeader($orig_header->getAddr_s('from',"\n$indent")) . "\n" .
        $display[_("Date")] . getLongDateString( $orig_header->date ). "\n" .
        $display[_("To")] . decodeHeader($orig_header->getAddr_s('to',"\n$indent")) ."\n";
  if ($orig_header->cc != array() && $orig_header->cc !='') {
     $bodyTop .= $display[_("Cc")] . decodeHeader($orig_header->getAddr_s('cc',"\n$indent")) . "\n";
  }
  $bodyTop .= str_pad('', $editor_size -2 , '-');
  $bodyTop .= "\n";
  return $bodyTop;
}
/* ----------------------------------------------------------------------- */

/*
 * If the session is expired during a post this restores the compose session 
 * vars.
 */
//$session_expired = false; 
if (session_is_registered('session_expired_post')) {
   global $session_expired_post, $session_expired;
   /* 
    * extra check for username so we don't display previous post data from
    * another user during this session.
    */
   if ($session_expired_post['username'] != $username) {
      session_unregister('session_expired_post');
      session_unregister('session_expired');      
   } else {
      foreach ($session_expired_post as $postvar => $val) {
         if (isset($val)) {
            $$postvar = $val;
         } else {
            $$postvar = '';
         }
      }
      if (isset($send)) {
         unset($send);
      }
      $session_expired = true;
   }
   session_unregister('session_expired_post');
   session_unregister('session_expired');
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

if (!isset($attachments)) {
    $attachments = array();
    session_register('attachments');
}

if (!isset($composesession)) {
    $composesession = 0;
    session_register('composesession');
}

if (!isset($session) || (isset($newmessage) && $newmessage)) {
    $session = "$composesession" +1; 
    $composesession = $session;
    session_register('composesession');            
}     

if (!isset($mailbox) || $mailbox == '' || ($mailbox == 'None')) {
    $mailbox = 'INBOX';
}

if (isset($draft)) {
    include_once ('../src/draft_actions.php');
    if (! isset($passed_id)) {
         $passed_id = 0;
    }
    if (! isset($MDN)) {
        $MDN = 'False';
    }
    if (! isset($mailprio)) {
        $mailprio = '';
    }
    if (!saveMessageAsDraft($send_to, $send_to_cc, $send_to_bcc, $subject, $body, $passed_id, $mailprio, $session)) {
        showInputForm($session);
        exit();
    } else {
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

if (isset($send)) {
    if (isset($HTTP_POST_FILES['attachfile']) &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] != 'none') {
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

        $MDN = False;  // we are not sending a mdn response
        if (! isset($mailprio)) {
            $Result = sendMessage($send_to, $send_to_cc, $send_to_bcc,
                                  $subject, $body, $passed_id, $MDN, '', $session);
        } else {
            $Result = sendMessage($send_to, $send_to_cc, $send_to_bcc,
                                  $subject, $body, $passed_id, $MDN, $mailprio, $session);
        }
        if (! $Result) {
            showInputForm($session);
            exit();
        }
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
        /*
         *$imapConnection = sqimap_login($username, $key, $imapServerAddress,
         *                               $imapPort, 0);
         */
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
    if (isset($HTTP_POST_FILES['attachfile']) &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] != 'none') {
        if (saveAttachedFiles($session)) {
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

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (isset($delete) && is_array($delete)) {
        foreach($delete as $index) {
            $attached_file = $hashed_attachment_dir . '/'
                           . $attachments[$index]['localfilename'];
    	    unlink ($attached_file);
    	    unset ($attachments[$index]);
        }
        setPref($data_dir, $username, 'attachments', serialize($attachments));
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

    if (!isset($passed_ent_id)) $passed_ent_id = '';
    if (!isset($passed_id)) $passed_id = '';    
    if (!isset($mailbox)) $mailbox = '';
    if (!isset($action)) $action = '';
    
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
	   $username, $key, $imapServerAddress, $imapPort;

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
        ClearAttachments($session);

        $identity = '';
        $idents = getPref($data_dir, $username, 'identities');
        $from_o = $orig_header->from;
        if (is_object($from_o)) {
            $orig_from = $from_o->getAddress();
        } else {
            $orig_from = '';
        }    
        if (!empty($idents) && $idents > 1) {
           for ($i = 1; $i < $idents; $i++) {
              $enc_from_name = '"'. 
                              getPref($data_dir, 
                                      $username, 
                                      'full_name' . $i) .
                 '" <' . getPref($data_dir, $username, 
                                 'email_address' . $i) . '>';
              if ($enc_from_name == $orig_from) {
                  $identity = $i;
                  break;
              }
           }
        }
	
	switch ($action) {
	case ('draft'):
           $use_signature = FALSE;
	   $send_to = $orig_header->getAddr_s('to');	
	   $send_to_cc = $orig_header->getAddr_s('cc');
	   $send_to_bcc = $orig_header->getAddr_s('bcc');
           $subject = $orig_header->subject;

           $body_ary = explode("\n", $body);
           $cnt = count($body_ary) ;
	   $body = '';
           for ($i=0; $i < $cnt; $i++) {
	      if (!ereg("^[>\\s]*$", $body_ary[$i])) {
                 sqWordWrap($body_ary[$i], $editor_size );
                 $body .= $body_ary[$i] . "\n";
	      }
              unset($body_ary[$i]);
           }
	   sqUnWordWrap($body);
           getAttachments($message, $session, $passed_id, $entities, $imapConnection);
	   break;
        case ('edit_as_new'):
           $send_to = $orig_header->getAddr_s('to');	
           $send_to_cc = $orig_header->getAddr_s('cc');
           $send_to_bcc = $orig_header->getAddr_s('bcc');
           $subject = $orig_header->subject;
           $mailprio = $orig_header->priority;
           $orig_from = '';
           getAttachments($message, $session, $passed_id, $entities, $imapConnection);
	   sqUnWordWrap($body);
	   break;
	case ('forward'):
	   $send_to = '';
           $subject = $orig_header->subject;
           if ((substr(strtolower($subject), 0, 4) != 'fwd:') &&
              (substr(strtolower($subject), 0, 5) != '[fwd:') &&
              (substr(strtolower($subject), 0, 6) != '[ fwd:')) {
              $subject = '[Fwd: ' . $subject . ']';
           }
	   $body = getforwardHeader($orig_header) . $body;
	   sqUnWordWrap($body);
           getAttachments($message, $session, $passed_id, $entities, $imapConnection);
	   break;
	case ('forward_as_attachment'):
           getMessage_RFC822_Attachment($message, $session, $passed_id, $passed_ent_id, $imapConnection);
	   $body = '';
	   break;
        case ('reply_all'):
	   $send_to_cc = replyAllString($orig_header);
	case ('reply'):
           $send_to = $orig_header->reply_to;
           if (is_object($send_to)) {
              $send_to = $send_to->getAddr_s('reply_to');
           } else {
              $send_to = $orig_header->getAddr_s('from');
           }
	   $subject = $orig_header->subject;
           $subject = str_replace('"', "'", $subject);
           $subject = trim($subject);
           if (substr(strtolower($subject), 0, 3) != 're:') {
              $subject = 'Re: ' . $subject;
           }
           /* this corrects some wrapping/quoting problems on replies */	     
           $rewrap_body = explode("\n", $body);
	   $body = getReplyCitation($orig_header->from->personal);
	   $cnt = count($rewrap_body);
           for ($i=0;$i<$cnt;$i++) {
              sqWordWrap($rewrap_body[$i], ($editor_size - 2));
              if (preg_match("/^(>+)/", $rewrap_body[$i], $matches)) {
                 $gt = $matches[1];
		 $body .= '>' . str_replace("\n", "\n$gt ", $rewrap_body[$i]) ."\n";
              } else {
                 $body .= '> ' . $rewrap_body[$i] . "\n";
	      }
	      unset($rewrap_body[$i]);
           }
	   break;
	default:
	   break;
        }
	sqimap_logout($imapConnection);
    }
    $ret = array(
            'send_to' => $send_to, 
	    'send_to_cc' => $send_to_cc,
	    'send_to_bcc' => $send_to_bcc,	     
	    'subject' => $subject,
	    'mailprio' => $mailprio,
	    'body' => $body,
	    'identity' => $identity
	    );
    
    return ($ret);
} /* function newMail() */


function getAttachments($message, $session, $passed_id, $entities, $imapConnection) {
    global $attachments, $attachment_dir, $username, $data_dir;
    
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!count($message->entities) || 
       ($message->type0 == 'message' && $message->type1 == 'rfc822')) {
        if ( !in_array($message->entity_id, $entities) && $message->entity_id) {
	    if ($message->type0 == 'message' && $message->type1 == 'rfc822') {
	       $filename = decodeHeader($message->rfc822_header->subject.'.eml');
               if ($filename == "") {
                  $filename = "untitled-".$message->entity_id.'.eml';
               }
	    } else {
               $filename = decodeHeader($message->header->disposition->getProperty('filename'));
               if ($filename == '') {
	          $name = decodeHeader($message->header->disposition->getProperty('name'));
		  if ($name == '') {
                     $filename = "untitled-".$message->entity_id;
		  } else {
		     $filename = $name;
		  }
               }
            }
            $localfilename = GenerateRandomString(32, '', 7);
            $full_localfilename = "$hashed_attachment_dir/$localfilename";
            while (file_exists($full_localfilename)) {
                $localfilename = GenerateRandomString(32, '', 7);
                $full_localfilename = "$hashed_attachment_dir/$localfilename";
            }

            $newAttachment = array();
            $newAttachment['localfilename'] = $localfilename;
            $newAttachment['remotefilename'] = $filename;
            $newAttachment['type'] = strtolower($message->type0 .
                                                '/' . $message->type1);
	    $newAttachment['id'] = strtolower($message->header->id);
	    $newAttachment['session'] = $session;

            /* Write Attachment to file */
            $fp = fopen ("$hashed_attachment_dir/$localfilename", 'w');
            fputs($fp, decodeBody(mime_fetch_body($imapConnection,
                $passed_id, $message->entity_id),
                $message->header->encoding));
            fclose ($fp);
            $attachments[] = $newAttachment;
        }
    } else {
        for ($i = 0; $i < count($message->entities); $i++) {
            getAttachments($message->entities[$i], $session, $passed_id, $entities, $imapConnection);
        }
    }
    setPref($data_dir, $username, 'attachments', serialize($attachments));    
    return;
}

function getMessage_RFC822_Attachment($message, $session, $passed_id, 
                                      $passed_ent_id='', $imapConnection) {
    global $attachments, $attachment_dir, $username, $data_dir, $uid_support;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!$passed_ent_id) {
	$body_a = sqimap_run_command($imapConnection, 
	          'FETCH '.$passed_id.' RFC822',
		  true, $response, $readmessage, $uid_support);
    } else {
        $body_a = sqimap_run_command($imapConnection, 
	          'FETCH '.$passed_id.' BODY['.$passed_ent_id.']',
		  true, $response, $readmessage, $uid_support);
	$message = $message->parent;
    }
    if ($response = 'OK') {
	$subject = encodeHeader($message->rfc822_header->subject);
    	array_shift($body_a);
    	$body = implode('', $body_a);
    	$body .= "\r\n";
        		
    	$localfilename = GenerateRandomString(32, 'FILE', 7);
    	$full_localfilename = "$hashed_attachment_dir/$localfilename";
        	
    	$fp = fopen( $full_localfilename, 'w');
    	fwrite ($fp, $body);
    	fclose($fp);
	$newAttachment = array();
    	$newAttachment['localfilename'] = $localfilename;
    	$newAttachment['type'] = "message/rfc822";
    	$newAttachment['remotefilename'] = $subject.'.eml';
    	$newAttachment['session'] = $session;
    	$attachments[] = $newAttachment;
    }
    setPref($data_dir, $username, 'attachments', serialize($attachments));
    return;
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
	   $username;

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
    echo '<TABLE WIDTH="100%" ALIGN=center CELLSPACING=0 BORDER=0>' . "\n";
    if ($compose_new_win == '1') {
        echo '<TABLE ALIGN=CENTER BGCOLOR="'.$color[0].'" WIDTH="100%" BORDER=0>'."\n";
        echo '   <TR><TD></TD><TD ALIGN="RIGHT"><INPUT TYPE="BUTTON" NAME="Close" onClick="return self.close()" VALUE='._("Close").'></TD></TR>'."\n";
    }
    if ($location_of_buttons == 'top') {
        showComposeButtonRow();
    }

    $idents = getPref($data_dir, $username, 'identities', 0);
    if ($idents > 1) {
        echo '   <TR>' . "\n" .
             '      <TD BGCOLOR="' . $color[4] . '" WIDTH="10%" ALIGN=RIGHT>' .
             "\n" .
             _("From:") .
             '      </TD><TD BGCOLOR="' . $color[4] . '" WIDTH="90%">' . "\n" .
             '<select name=identity>' . "\n" .
             '<option value=default>' .
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
         '      <TD BGCOLOR="' . $color[4] . '" WIDTH="10%" ALIGN=RIGHT>' . "\n" .
         _("To:") .
         '      </TD><TD BGCOLOR="' . $color[4] . '" WIDTH="90%">' . "\n" .
         '         <INPUT TYPE=text NAME="send_to" VALUE="' .
         htmlspecialchars($send_to) . '" SIZE=60><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' . "\n" .
         _("CC:") .
         '      </TD><TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n" .
         '         <INPUT TYPE=text NAME="send_to_cc" SIZE=60 VALUE="' .
         htmlspecialchars($send_to_cc) . '"><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' . "\n" .
         _("BCC:") .
         '      </TD><TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n" .
         '         <INPUT TYPE=text NAME="send_to_bcc" VALUE="' .
         htmlspecialchars($send_to_bcc) . '" SIZE=60><BR>' . "\n" .
         '</TD></TR>' . "\n" .
         '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' . "\n" .
         _("Subject:") .
         '      </TD><TD BGCOLOR="' . $color[4] . '" ALIGN=LEFT>' . "\n";
        echo '         <INPUT TYPE=text NAME=subject SIZE=60 VALUE="' .
             htmlspecialchars($subject) . '">';
    echo '</td></tr>' . "\n\n";

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
            echo "\n\n".($prefix_sig==true? "-- \n":'').htmlspecialchars($signature);
            echo "\n\n".htmlspecialchars($body);
        }
        else {
            echo "\n\n".htmlspecialchars($body);
            echo "\n\n".($prefix_sig==true? "-- \n":'').htmlspecialchars($signature);
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
        echo '   <TR><TD COLSPAN=2 ALIGN=LEFT>';
        echo ' &nbsp; <INPUT TYPE=SUBMIT NAME=send VALUE="' . _("Send") . '"></TD></TR>' . "\n";
    }

    /* This code is for attachments */
    echo '<table width="100%" cellpadding="0" cellspacing="4" align="center" border="0">';
    echo '   <tr><td>';
    echo '   <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
    echo '      <tr><td>';
    echo '      <table width="100%" cellpadding="3" cellspacing="0" align="center" border="0">';

    
    echo '   <TR>' . "\n" .
         '     <TD VALIGN=MIDDLE ALIGN=RIGHT>' . "\n" .
                _("Attach:") .
         '      </TD>' . "\n" .
         '      <TD VALIGN=MIDDLE ALIGN=LEFT>' . "\n" .
         '      <INPUT NAME="attachfile" SIZE=48 TYPE="file">' . "\n" .
         '      &nbsp;&nbsp;<input type="submit" name="attach"' .
         ' value="' . _("Add") .'">' . "\n" .
         '     </TD>' . "\n" .
         '   </TR>' . "\n";
    

    $s_a = array();
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    foreach ($attachments as $key => $info) {
        if ($info['session'] == $session) { 
           $attached_file = "$hashed_attachment_dir/$info[localfilename]";
           $s_a[] = '<input type="checkbox" name="delete[]" value="' . $key . "\">\n" .
                    $info['remotefilename'] . ' - ' . $info['type'] . ' (' .
                    show_readable_size( filesize( $attached_file ) ) . ")<br>\n";
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
    echo '      </table></td></tr>';
    echo '   </table>';
    echo '   </td></tr>';

    /* End of attachment code */
    if ($compose_new_win == '1') {
        echo '</TABLE>'."\n";
    }
    echo '</TABLE>' . "\n";

    echo '<input type="hidden" name="username" value="'. $username . "\">\n";    
    echo '<input type=hidden name=action value=' . $action . ">\n";
    echo '<INPUT TYPE=hidden NAME=mailbox VALUE="' . htmlspecialchars($mailbox) .
         "\">\n" .
         '</FORM>';
    do_hook('compose_bottom');
    echo '</BODY></HTML>' . "\n";
}


function showComposeButtonRow() {
    global $use_javascript_addr_book, $save_as_draft,
        $default_use_priority, $mailprio, $default_use_mdn,
	$request_mdn, $request_dr,
        $data_dir, $username;

    echo "  <TR><TD>\n</TD><TD>\n";
    if ($default_use_priority) {
        if(!isset($mailprio)) {
            $mailprio = "3";
    }
    echo _("Priority") .': <select name="mailprio">'.
         '<option value="1"'.($mailprio=='1'?' selected':'').'>'. _("High") .'</option>'.
         '<option value="3"'.($mailprio=='3'?' selected':'').'>'. _("Normal") .'</option>'.
         '<option value="5"'.($mailprio=='5'?' selected':'').'>'. _("Low").'</option>'.
         "</select>";
    }
    $mdn_user_support=getPref($data_dir, $username, 'mdn_user_support',$default_use_mdn);
    if ($default_use_mdn) {
        if ($mdn_user_support) {
            echo "\n\t". _("Receipt") .': '.
            '<input type="checkbox" name="request_mdn" value=1'.
		($request_mdn=='1'?' checked':'') .'>'. _("On read").
            ' <input type="checkbox" name="request_dr" value=1'.
		($request_dr=='1'?' checked':'') .'>'. _("On Delivery");
        }
    }

    echo "   </td></tr>\n   <TR><td>\n   </td><td>\n";
    echo "\n    <INPUT TYPE=SUBMIT NAME=\"sigappend\" VALUE=\"". _("Signature") . "\">\n";
    if ($use_javascript_addr_book) {
        echo "      <SCRIPT LANGUAGE=JavaScript><!--\n document.write(\"".
             "         <input type=button value=\\\""._("Addresses").
                              "\\\" onclick='javascript:open_abook();'>\");".
             "         // --></SCRIPT><NOSCRIPT>\n".
             "         <input type=submit name=\"html_addr_search\" value=\"".
                              _("Addresses")."\">".
             "      </NOSCRIPT>\n";
    } else {
        echo "      <input type=submit name=\"html_addr_search\" value=\"".
                              _("Addresses")."\">";
    }
    echo "\n    <INPUT TYPE=SUBMIT NAME=send VALUE=\"". _("Send") . "\">\n";

    if ($save_as_draft) {
        echo '<input type="submit" name ="draft" value="' . _("Save Draft") . "\">\n";
    }

    do_hook('compose_button_row');

    echo "   </TD></TR>\n\n";
}

function checkInput ($show) {
    /*
     * I implemented the $show variable because the error messages
     * were getting sent before the page header.  So, I check once
     * using $show=false, and then when i'm ready to display the error
     * message, show=true
     */
    global $body, $send_to, $subject, $color;

    if ($send_to == "") {
        if ($show) {
            plain_error_message(_("You have not filled in the \"To:\" field."), $color);
        }
        return false;
    }
    return true;
} /* function checkInput() */


/* True if FAILURE */
function saveAttachedFiles($session) {
    global $HTTP_POST_FILES, $attachment_dir, $attachments, $username,
           $data_dir;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    $localfilename = GenerateRandomString(32, '', 7);
    $full_localfilename = "$hashed_attachment_dir/$localfilename";
    while (file_exists($full_localfilename)) {
        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
    }

    if (!@rename($HTTP_POST_FILES['attachfile']['tmp_name'], $full_localfilename)) {
	if (function_exists("move_uploaded_file")) {
        	if (!@move_uploaded_file($HTTP_POST_FILES['attachfile']['tmp_name'], $full_localfilename)) {
            return true;
        	}
	} else {
		if (!@copy($HTTP_POST_FILES['attachfile']['tmp_name'], $full_localfilename)) {
	            return true;
       		}
	}

    }
    $newAttachment['localfilename'] = $localfilename;
    $newAttachment['remotefilename'] = $HTTP_POST_FILES['attachfile']['name'];
    $newAttachment['type'] = strtolower($HTTP_POST_FILES['attachfile']['type']);
    $newAttachment['session'] = $session;

    if ($newAttachment['type'] == "") {
         $newAttachment['type'] = 'application/octet-stream';
    }
    $attachments[] = $newAttachment;
    setPref($data_dir, $username, 'attachments', serialize($attachments));
}


function ClearAttachments($session)
{
    global $username, $attachments, $attachment_dir, $data_dir;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $rem_attachments = array();
    if (is_array($attachments)) {
        foreach ($attachments as $info) {
	        if ($info['session'] == $session) {
    	        $attached_file = "$hashed_attachment_dir/$info[localfilename]";
    	        if (file_exists($attached_file)) {
        	        unlink($attached_file);
    	        }
	        } 
            else {
	            $rem_attachments[] = $info;
	        }
        }
    }
    $attachments = $rem_attachments;
    setPref($data_dir, $username, 'attachments', serialize($attachments));    
}


function getReplyCitation($orig_from)
{
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

?>
