<?php

/**
 * read_body.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/mime.php');
require_once('../functions/date.php');
require_once('../functions/url_parser.php');
require_once('../functions/smtp.php');
require_once('../functions/html.php');
require_once('../class/html.class');
require_once('../src/view_header.php');

/**
 * Given an IMAP message id number, this will look it up in the cached
 * and sorted msgs array and return the index. Used for finding the next
 * and previous messages.
 *
 * @return the index of the next valid message from the array
 */
function findNextMessage() {
    global $msort, $currentArrayIndex, $msgs, $sort, 
           $thread_sort_messages, $allow_server_sort,
           $server_sort_array;
    if (!is_array($server_sort_array)) {
        $thread_sort_messages = 0;
        $allow_server_sort = FALSE;
    }
    $result = -1;
    if ($thread_sort_messages == 1 || $allow_server_sort == TRUE) {
        reset($server_sort_array);
        while(list($key, $value) = each ($server_sort_array)) {
            if ($currentArrayIndex == $value) {
                if ($key == (count($server_sort_array) - 1)) {
                    $result = -1;
                    break;
                }
                $result = $server_sort_array[$key + 1];
                break; 
            }
        }
    } 
    elseif ($sort == 6 && $allow_server_sort != TRUE &&
            $thread_sort_messages != 1) {
        if ($currentArrayIndex != 1) {
            $result = $currentArrayIndex - 1;
        }
    }
    elseif ($allow_server_sort != TRUE && $thread_sort_messages != 1 ) {
        if (!is_array($msort)) {
            return -1;
        }
        for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) {
            if ($currentArrayIndex == $msgs[$key]['ID']) {
                next($msort);
                $key = key($msort);
                if (isset($key)){
                    $result = $msgs[$key]['ID'];
                    break;
                }
            }
        }
    }
    return ($result);
}

/** returns the index of the previous message from the array. */
function findPreviousMessage() {
    global $msort, $currentArrayIndex, $sort, $msgs, $imapConnection,
           $mailbox, $data_dir, $username, $thread_sort_messages,
           $allow_server_sort, $server_sort_array;
    $result = -1;
    if (!is_array($server_sort_array)) {
        $thread_sort_messages = 0;
        $allow_server_sort = FALSE;
    }
    if ($thread_sort_messages == 1 || $allow_server_sort == TRUE) {
        reset($server_sort_array);
        while(list($key, $value) = each ($server_sort_array)) {
            if ($currentArrayIndex == $value) {
                if ($key == 0) {
                    $result = -1;
                    break;
                }
                $result = $server_sort_array[$key -1];
                break;
            }
        }
    }
    elseif ($sort == 6 && $allow_server_sort != TRUE && 
            $thread_sort_messages != 1) {
        $numMessages = sqimap_get_num_messages($imapConnection, $mailbox);
        if ($currentArrayIndex != $numMessages) {
            $result = $currentArrayIndex + 1;
        }
    } 
    elseif ($thread_sort_messages != 1 && $allow_server_sort != TRUE) {
        if (!is_array($msort)) {
            return -1;
        }
        for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) {
            if ($currentArrayIndex == $msgs[$key]['ID']) {
                prev($msort);
                $key = key($msort);
                if (isset($key)) {
                    $result = $msgs[$key]['ID'];
                    break;
                }
            }
        }
    }
    return ($result);
}

/**
 * Displays a link to a page where the message is displayed more
 * "printer friendly".
 */
function printer_friendly_link() {
    global $passed_id, $mailbox, $ent_num, $color,
           $pf_subtle_link,
           $javascript_on;

    if (strlen(trim($mailbox)) < 1) {
        $mailbox = 'INBOX';
    }

    $params = '?passed_ent_id=' . $ent_num .
        '&mailbox=' . urlencode($mailbox) .
        '&passed_id=' . $passed_id;

    $print_text = _("View Printable Version");

    if (!$pf_subtle_link) {
        /* The link is large, on the bottom of the header panel. */
        $result = html_tag( 'tr', '', '', $color[0] ) .
            html_tag( 'td', '&nbsp;', 'right', '', 'class="medText" valign="top"' ) .
            html_tag( 'td', '', 'left', '', 'class="medText" valign="top" colspan="2"' ) . "\n";
    } else {
        /* The link is subtle, below "view full header". */
        $result = "\n";
    }

    /* Output the link. */
    if ($javascript_on) {
        $result .= '<script language="javascript" type="text/javascript">' . "\n" .
                '<!--' . "\n" .
                "  function printFormat() {\n" .
                '    window.open("../src/printer_friendly_main.php' .
                        $params . '","Print","width=800,height=600");' . "\n".
                "  }\n" .
                "// -->\n" .
                "</script>\n" .
                "<a href=\"javascript:printFormat();\">$print_text</a>\n";
    } else {
        $result .= '<A target="_blank" HREF="../src/printer_friendly_bottom.php' .
                "$params\">$print_text</a>\n";
    }

    if (!$pf_subtle_link) {
        /* The link is large, on the bottom of the header panel. */
        $result .=         '</td></tr>' . "\n";
    }

    return ($result);
}

function ServerMDNSupport( $read ) {
    /* escaping $ doesn't work -> \x36 */    
    $ret = preg_match( '/(\x36MDNSent|\\\*)/i', $read );
    return ( $ret );
}

function SendMDN ( $sender, $message) {
    global $imapConnection, $mailbox, $username, $attachment_dir, $SERVER_NAME,
           $version, $attachments, $identity, $data_dir, $passed_id;

    $header = $message->header;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    
    $recipient_o = $header->dnt;
    $recipient = $recipient_o->getAddress(true);

    // part 1 (RFC2298)

    $senton = getLongDateString( $header->date );
    $to_array = $header->to;
    $to = '';
    foreach ($to_array as $line) {
        $to .= " $line ";
    }

    $subject = $header->subject;
    $now = getLongDateString( time() );

    set_my_charset();

    $body = _("Your message") . "\r\n\r\n" .
            "\t" . _("To:") . ' ' . $to . "\r\n" .
            "\t" . _("Subject:") . ' ' . $subject . "\r\n" .
            "\t" . _("Sent:") . ' ' . $senton . "\r\n" .
            "\r\n" .
            sprintf( _("Was displayed on %s"), $now );

    // part2  (RFC2298)

    $original_recipient = $to;
    $original_message_id = $header->message_id;

    $part2 = "Reporting-UA : $SERVER_NAME ; SquirrelMail (version $version) \r\n";
    if ($original_recipient != '') {
        $part2 .= "Original-Recipient : $original_recipient\r\n";
    }
    $final_recipient = $sender;
    $part2 .= "Final-Recipient: rfc822; $final_recipient\r\n" .
              "Original-Message-ID : $original_message_id\r\n" .
              "Disposition: manual-action/MDN-sent-manually; displayed\r\n";


    $localfilename = GenerateRandomString(32, 'FILE', 7);
    $full_localfilename = "$hashed_attachment_dir/$localfilename";

    $fp = fopen( $full_localfilename, 'w');
    fwrite ($fp, $part2);
    fclose($fp);

    $newAttachment = array();
    $newAttachment['localfilename'] = $localfilename;
    $newAttachment['type'] = "message/disposition-notification";
    $newAttachment['session']=-1;
    $attachments[] = $newAttachment;

    $reply_id = 0;

    return (SendMessage($recipient, '', '', _("Read:") . ' ' . $subject, 
                        $body, $reply_id, True, 3, -1) );
}


function ToggleMDNflag ( $set ) {
    global $imapConnection, $passed_id, $mailbox, $uid_support;
    sqimap_mailbox_select($imapConnection, $mailbox);
    $sg =  $set?'+':'-';
    $cmd = 'STORE ' . $passed_id . ' ' . $sg . 'FLAGS ($MDNSent)';
    $read = sqimap_run_command ($imapConnection, $cmd, true, $response, 
                                $readmessage, $uid_support);
}

function ClearAttachments() {
        global $username, $attachments, $attachment_dir;

        $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

	$rem_attachments = array();
        foreach ($attachments as $info) {
	    if ($info['session'] == -1) {
        	$attached_file = "$hashed_attachment_dir/$info[localfilename]";
        	if (file_exists($attached_file)) {
            	    unlink($attached_file);
        	}
    	    } else {
		$rem_attachments[] = $info;
	    }
	}
        $attachments = $rem_attachments;
}

function formatRecipientString($recipients, $item ) {
    global $base_uri, $passed_id, $urlMailbox, $startMessage, $show_more_cc, 
           $echo_more, $echo_less, $show_more, $show_more_bcc, $sort, $passed_ent_id,
	   $PHP_SELF;

    $i = 0;
    $url_string = '';    
    if ((is_array($recipients)) && (isset($recipients[0]))) {
        $string = '';
        $ary = $recipients;
	$show = false;

        if ($item == 'to') {
	   if ($show_more) {
	       $show = true;
	       $url = set_url_var($PHP_SELF, 'show_more',0);
	   } else {
	       $url = set_url_var($PHP_SELF, 'show_more',1);
	   }       
	} else if ($item == 'cc') {
	   if ($show_more_cc) {
	       $url = set_url_var($PHP_SELF, 'show_more_cc',0);
	       $show = true;
	   } else {
	      $url = set_url_var($PHP_SELF, 'show_more_cc',1);
	   }
	} else if ($item == 'bcc') {
	   if ($show_more_bcc) {
	      $url = set_url_var($PHP_SELF, 'show_more_bcc',0);
	      $show = true;
	   } else {
	      $url = set_url_var($PHP_SELF, 'show_more_bcc',1);
	   }       
	}

	$cnt = count($ary);
        while ($i < $cnt) {
	    $addr_o = $ary[$i];
	    $ary[$i] = $addr_o->getAddress();
    	    $ary[$i] = decodeHeader(htmlspecialchars($ary[$i]));
    	    $url_string .= $ary[$i];
    	    if ($string) {
                $string .= '<BR>'.$ary[$i];
    	    } else {
                $string = $ary[$i];
		if ($cnt>1) {
                    $string .= '&nbsp;(<A HREF="'.$url;
		    if ($show) {
		       $string .= '">'.$echo_less.'</A>)';
		    } else {
		       $string .= '">'.$echo_more.'</A>)';
		       break;
		    }
		} 
    	    }
    	    $i++;
        }
    }
    else {
        $string = '';
    }
    $url_string = urlencode($url_string);
    $result = array();
    $result['str'] = $string;
    $result['url_str'] = $url_string;
    return $result;
}



/*
 *   Main of read_boby.php  --------------------------------------------------
 */

/*
    Urled vars
    ----------
    $passed_id
*/

global $uid_support, $sqimap_capabilities;

if (isset($mailbox)){
    $mailbox = urldecode( $mailbox );
}

$imapConnection = sqimap_login($username, $key, $imapServerAddress, 
                               $imapPort, 0);

$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);

if (!isset($messages)) {
    $messages = array();
    session_register('messages');
}

/**
 * $message contains all information about the message
 * including header and body
 */

$uidvalidity = $mbx_response['UIDVALIDITY'];
 
if (!isset($messages[$uidvalidity])) {
   $messages[$uidvalidity] = array();
}  
if (!isset($messages[$uidvalidity][$passed_id]) || !$uid_support) {
   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
   $messages[$uidvalidity][$passed_id] = $message;
} else {
   $message = $messages[$uidvalidity][$passed_id];
}
if (isset($passed_ent_id)) {
   $message = $message->getEntity($passed_ent_id);
   $message->id = $passed_id;
   $message->mailbox = $mailbox;
}
$header = $message->header;

//do_hook('html_top');

/*
 * The following code sets necesarry stuff for the MDN thing
 */
if($default_use_mdn &&
   ($mdn_user_support = getPref($data_dir, $username, 'mdn_user_support', 
                                $default_use_mdn))) {
    $supportMDN = ServerMDNSupport($mbx_response["PERMANENTFLAGS"]);
    $FirstTimeSee = !$message->is_seen;
}

$xtra = '';
$xtra =  "<link rel=\"stylesheet\" href=\"../css/read_body.css\" type=\"text/css\">";
//displayPageHeader($color, $mailbox);

/* ============================================================================= 
 *   block for handling incoming url vars 
 *
 * =============================================================================
 */


/*
 * The following code shows the header of the message and then exit
 */
if (isset($view_hdr)) {
   $template_vars = array();
   parse_viewheader($imapConnection,$passed_id, $passed_ent_id, &$template_vars);
   $template_vars['return_address'] = set_url_var($PHP_SELF, 'view_hdr');
   view_header($template_vars, '', '</body></html>');
   exit;
}

if (isset($sendreceipt)) {
   if ( !$message->is_mdnsent ) {
      if (isset($identity) ) {
         $final_recipient = getPref($data_dir, $username, 'email_address' . '0', '' );
      } else {
         $final_recipient = getPref($data_dir, $username, 'email_address', '' );
      }

      $final_recipient = trim($final_recipient);
      if ($final_recipient == '' ) {
         $final_recipient = getPref($data_dir, $username, 'email_address', '' );
      }

      if ( SendMDN( $final_recipient, $message ) > 0 && $supportMDN ) {
         ToggleMDNflag( true);
         $message->is_mdnsent = true;
      }
      ClearAttachments();
   }
}

/* ============================================================================= 
 *   end block for handling incoming url vars 
 *
 * =============================================================================
 */


if (isset($msgs)) {
    $currentArrayIndex = $passed_id;
} else {
    $currentArrayIndex = -1;
}
$msgs[$passed_id]['FLAG_SEEN'] = true;

/** translate the subject and mailbox into url-able text **/
$url_subj = urlencode(trim($header->subject));
$urlMailbox = urlencode($mailbox);
$url_replyto = '';
if (isset($header->replyto)) {
    $addr_o = $header->replyto;
    $addr_s = $addr_o->getAddress();
    $url_replyto = urlencode($addr_s);
}

$url_replytoall = $url_replyto;

/**
 * If we are replying to all, then find all other addresses and
 * add them to the list.  Remove duplicates.
 */

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
$url_replytoall_ar = $header->getAddr_a(array('from','to','cc'), $excl_ar);

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

/**
 * 5) urlencode() it
 */
$url_replytoallcc = urlencode($url_replytoallcc);

$dateString = getLongDateString($header->date);

/**
 * What do we reply to -- text only, if possible
 */
 
$messagebody = ''; 
    
/* first step in displaying multiple entities */

    $ent_ar = $message->findDisplayEntity(array());
    $i = 0;
    for ($i = 0; $i < count($ent_ar); $i++) {
	$messagebody .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox);
    }


//$ent_ar = findDisplayEntity($message,true);

$ent_num = $ent_ar[0];
for ($i = 1 ; $i < count($ent_ar); $i++) {
    $ent_num .= '_'.$ent_ar[$i];
}
/** TEXT STRINGS DEFINITIONS **/
$echo_more = _("more");
$echo_less = _("less");

if (!isset($show_more_cc)) {
    $show_more_cc = FALSE;
}

if (!isset($show_more_bcc)) {
    $show_more_bcc = FALSE;
}

$use_css = false;

/** FORMAT THE TO STRING **/
$to = formatRecipientString($message->header->to, "to");
$to_string = $to['str'];
$url_to_string = $to['url_str'];


/** FORMAT THE CC STRING **/

$cc = formatRecipientString($header->cc, "cc");
$cc_string = $cc['str'];
$url_cc_string = $cc['url_str'];

/** FORMAT THE BCC STRING **/

$bcc = formatRecipientString($header->bcc, "bcc");
$bcc_string = $bcc['str'];
$url_bcc_string = $bcc['url_str'];

if ($default_use_priority) {
    $priority_level = substr($header->priority,0,1);

    switch($priority_level) {
        /* check for a higher then normal priority. */
        case '1':
        case '2':
            $priority_string = _("High");
            break;

        /* check for a lower then normal priority. */
        case '4':
        case '5':
            $priority_string = _("Low");
            break;

        /* check for a normal priority. */
        case '3':
        default:
            $priority_level = '3';
            $priority_string = _("Normal");
            break;

    }
}

/** make sure everything will display in HTML format **/

$from_o = $header->from;
if (is_object($from_o)) {
    $from_name = $from_o->getAddress();
} else {
    $from_name = _("Unknown sender");
}
$from_name = decodeHeader(htmlspecialchars($from_name));
$subject = decodeHeader(htmlspecialchars($message->header->subject));
$identity = '';
$idents = getPref($data_dir, $username, 'identities');
if (!empty($idents) && $idents > 1) {
    for ($i = 1; $i < $idents; $i++) {
        $enc_from_name = '"'. 
            encodeHeader(getPref($data_dir, 
                                 $username, 
                                 'full_name' . $i)) .
            '" <' . getPref($data_dir, $username, 
                            'email_address' . $i) . '>';
        if (htmlspecialchars($enc_from_name) == $from_name) {
            $identity = $i;
            break;
        }
    }
}
/* start of prepare html fase */

$page = initPage();
$head = initHead();
$body = initBody($color);
$top = getTop($color,$mailbox);
$menu = getMenu($color,$mailbox);

GLOBAL $languages, $squirrelmail_language;

if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
    $dir = $languages[$squirrelmail_language]['DIR'];
} else {
    $dir = 'ltr';
}

if ( $dir == 'ltr' ) {
    $rgt = 'right';
    $lft = 'left';
} else {
    $rgt = 'left';
    $lft = 'right';
}



//do_hook('read_body_top');
/* topbar */
if ($use_css) {
   $table_ar = array('cellpadding' => 3);
} else {
   $table_ar = array( 'width' => '100%', 'cellpadding' => 3,
                      'cellspacing' => 0, 'align'=> 'center',
                      'border' => 0, 'bgcolor' => $color[9]);
}
		      
$topbar = new html('table','','','rb_tb','',$table_ar);
$topbar_row = new html('tr','','','rb_tbr');
$topbar_delimiter = new html ('','&nbsp;|&nbsp;');

$msgs_url = $base_uri . 'src/';
if (isset($where) && isset($what)) {
    if ($pos == '') {
        $pos=0;
    }
    $msgs_url .= 'search.php?where='.urlencode($where).'&amp;pos='.$pos.
                 '&amp;what='.urlencode($what).'&amp;mailbox='.$urlMailbox;
} else {
    $msgs_url .= 'right_main.php?sort='.$sort.'&amp;startMessage='.
                  $startMessage.'&amp;mailbox='.$urlMailbox;
}

$topbar_col = new html('td','',array('small'=> true),'rb_tbc','',array('align' => $lft,
                            'width' => '33%'));

$topbar_col->addChild('a', _("Message List"),'','','',
		            array('href' => $msgs_url));
			    
$delete_url = $base_uri . 'src/delete_message.php?mailbox='.$urlMailbox.
              '&amp;message='.$passed_id.'&amp;';
if ($where && $what) {
    $delete_url .= 'where=' . urlencode($where) . '&amp;what=' . urlencode($what);
} else {
    $delete_url .= 'sort='. $sort . '&amp;startMessage='. $startMessage;
}

$topbar_col->htmlAdd($topbar_delimiter);
$topbar_col->addChild('a', _("Delete") ,'','','',
		            array('href' => $delete_url));

if (($mailbox == $draft_folder) && ($save_as_draft)) {
   $comp_alt_uri = $base_uri . "src/compose.php?mailbox=$mailbox&amp;".
                "identity=$identity&amp;send_to=$url_to_string&amp;".
                "send_to_cc=$url_cc_string&amp;send_to_bcc=$url_bcc_string&amp;".
                "subject=$url_subj&amp;mailprio=$priority_level&amp;".
                "draft_id=$passed_id&amp;ent_num=$ent_num";
   $comp_alt_string = _("Resume Draft");
}
else if ($mailbox == $sent_folder) {
   $comp_alt_uri = $base_uri . "src/compose.php?mailbox=$mailbox&amp;".
                "identity=$identity&amp;send_to=$url_to_string&amp;".
                "send_to_cc=$url_cc_string&amp;send_to_bcc=$url_bcc_string&amp;".
                "subject=$url_subj&amp;mailprio=$priority_level&amp;".
                "ent_num=$ent_num&amp;passed_id=$passed_id&amp;edit_as_new=1";
   $comp_alt_string = _("Edit Message as New");
}
if (isset($comp_alt_uri)) {
    $topbar_col->htmlAdd($topbar_delimiter);
    if ($compose_new_win == '1') {
        $topbar_col->addChild('a', $comp_alt_string ,'','','',
		               array('href' => 'javascript:void(0)'),
			       array('onclick'=> 'comp_in_new(false,'.$comp_alt_uri.')'));
    } else {
        $topbar_col->addChild('a', $comp_alt_string ,'','','',
		               array('href' => $comp_alt_uri));
    }
}
$topbar_row->htmlAdd($topbar_col);

if (!(isset($where) && isset($what))) {
    $topbar_col = new html('td','',array('small'=> true),'rb_tbc','',array('align' => 'center',
                            'width' => '33%'));

    if ($currentArrayIndex == -1) {
        $topbar_col->addChild('',_("Previous"));
	$topbar_col->htmlAdd($topbar_delimiter);
        $topbar_col->addChild('',_("Next"));
    } else {
        $prev = findPreviousMessage($mbx_response['EXISTS']);
        $next = findNextMessage();

        if ($prev != -1) {
	   $uri = $base_uri . 'src/read_body.php?passed_id='.$prev.
	         '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
		 '&amp;startMessage='.$startMessage.'&amp;show_more=0';
	   $topbar_col->addChild('a',_("Previous") , '','','',
		               array('href' => $uri));
        } else {
           $topbar_col->addChild('',_("Previous"));
        }
	$topbar_col->htmlAdd($topbar_delimiter);
        if ($next != -1) {
	   $uri = $base_uri . 'src/read_body.php?passed_id='.$next.
	         '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
		 '&amp;startMessage='.$startMessage.'&amp;show_more=0';
           $topbar_col->addChild('a',_("Next") ,'','','',
		               array('href' => $uri));
        } else {
           $topbar_col->addChild('',_("Next"));
        }
    }
    $topbar_row->htmlAdd($topbar_col);    
}

$topbar_col = new html('td','',array('small'=>true),'rb_tbc','',array('align' => $rgt,
                            'width' => '33%'));

$comp_uri = $base_uri . "src/compose.php?forward_id=$passed_id&amp;".
            "forward_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "mailbox=$urlMailbox&amp;ent_num=$ent_num";

if ($compose_new_win == '1') {
    $topbar_col->addChild('a',_("Forward") ,'','','',
		               array('href' => 'javascript:void(0)'),
			       array('onclick'=> 'comp_in_new(false,'.$comp_uri.')'));
} else {
    $topbar_col->addChild('a', _("Forward") ,'','','',
		               array('href' => $comp_uri));
}

$topbar_col->htmlAdd($topbar_delimiter);
$comp_uri = $base_uri . "src/compose.php?send_to=$url_replyto&amp;".
            "reply_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num";

if ($compose_new_win == '1') {
    $topbar_col->addChild('a',_("Reply") ,'','','',
		               array('href' => 'javascript:void(0)'),
			       array('onclick'=> 'comp_in_new(false,'.$comp_uri.')'));
} else {
    $topbar_col->addChild('a', _("Reply") ,'','','',
		               array('href' => $comp_uri));
}

$comp_uri = $base_uri . "src/compose.php?send_to=$url_replytoall&amp;".
            "send_to_cc=$url_replytoallcc&amp;reply_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num";

$topbar_col->htmlAdd($topbar_delimiter);
if ($compose_new_win == '1') {
    $topbar_col->addChild('a',_("Reply All") ,'','','',
		               array('href' => 'javascript:void(0)'),
			       array('onclick'=> 'comp_in_new(false,'.$comp_uri.')'));
} else {
    $topbar_col->addChild('a', _("Reply All") ,'','','',
		               array('href' => $comp_uri));
}
$topbar_row->htmlAdd($topbar_col);
$topbar->htmlAdd($topbar_row);


//$topbar->echoHtml();
//echo '<table><tr><td></td></tr></table>';

/* read_body envelope */

/* init some formatting arrays */
$use_css = false;
if (!$use_css) {
   $ar_key = array( 'width' => '20%',
                 'valign' => 'top',
		 'bgcolor' => $color[0],
		 'align' => 'right');

   $ar_val = array( 'width' => '80%',
                 'valign' => 'top',
		 'bgcolor' => $color[0],
		 'align' => 'left');
   $ar_table = array( 'width' => '100%',
                      'cellpadding' => '0',
		      'cellspacing' => '0',
                      'border' => '0',
		      'align' =>'center');
} else {
   $ar_key = '';
   $ar_val = '';
   $ar_table = array( 'cellpadding' => '0',
		      'cellspacing' => '0');
}

//echo '</table></table>';

$envtable = new html('table','','','rb_env','',$ar_table);

/* subject */						     
$row_s = new html('tr','','','rb_r','rb_sc');
$col = new html('td',_("Subject").':&nbsp;','','rb_hk','rb_sk',$ar_key);
$row_s->htmlAdd($col);
$col = new html('td',$subject,array('b'=> true),'rb_hv','rb_sv', $ar_val);
$row_s->htmlAdd($col);
$envtable->htmlAdd($row_s);

/* from */
$row_f = new html('tr','','','rb_r','rb_fc');
$col = new html('td',_("From").':&nbsp;','','rb_hk','rb_fk', $ar_key);
$row_f->htmlAdd($col);
$col = new html('td',$from_name,array('b'=> true),'rb_hv','rb_fv',$ar_val);
$row_f->htmlAdd($col);
$envtable->htmlAdd($row_f);

/* date */
$row_d = new html('tr','','','rb_r','rb_dc');
$col = new html('td',_("Date").':&nbsp;','','rb_hk','rb_dk', $ar_key);
$row_d->htmlAdd($col);
$col = new html('td',$dateString,array('b'=> true),'rb_hv','rb_dv',$ar_val);
$row_d->htmlAdd($col);
$envtable->htmlAdd($row_d);

/* to */
$row_t = new html('tr','','','rb_r','rb_tc');
$col = new html('td',_("To").':&nbsp;','','rb_hk','rb_tk', $ar_key);
$row_t->htmlAdd($col);
$col = new html('td',$to_string,array('b'=> true),'rb_hv','rb_tv',$ar_val);
$row_t->htmlAdd($col);
$envtable->htmlAdd($row_t);

/* cc */
if (isset($cc_string) && $cc_string <> '') {
   $row_c = new html('tr','','','rb_r','rb_cc');
   $col = new html('td',_("Cc").':&nbsp;','','rb_hk','rb_ck', $ar_key);
   $row_c->htmlAdd($col);
   $col = new html('td',$cc_string,array('b'=> true),'rb_hv','rb_cv',$ar_val);
   $row_c->htmlAdd($col);
   $envtable->htmlAdd($row_c);
}

/* bcc */
if (isset($bcc_string) && $bcc_string <> '') {
   $row_b = new html('tr','','','rb_r','rb_bc');
   $col = new html('td',_("Bcc"). ':&nbsp;','','rb_hk','rb_bk', $ar_key);
   $row_b->htmlAdd($col);
   $col = new html('td',$bcc_string,array('b'=> true),'rb_hv','rb_bv',$ar_val);
   $row_b->htmlAdd($col);
   $envtable->htmlAdd($row_b);
}
/* priority */
if ($default_use_priority && isset($priority_string) && $priority_string <> '' ) {
   $row_p = new html('tr','','','rb_r','rb_pc');
   $col = new html('td',_("Priority") . ':&nbsp;','','rb_hk','rb_pk', $ar_key);
   $row_p->htmlAdd($col);
   $col = new html('td',$priority_string ,array('b'=> true),'rb_hv','rb_pv',$ar_val);
   $row_p->htmlAdd($col);
   $envtable->htmlAdd($row_p);
}

/* xmailer */
if ($show_xmailer_default) {
    $mailer = $header->xmailer;
    if (trim($mailer)) {
       $row_xm = new html('tr','','','rb_r','rb_xmc');
       $col = new html('td',_("Mailer") . ':&nbsp;','','rb_hk','rb_xmk', $ar_key);
       $row_xm->htmlAdd($col);
       $col = new html('td',$mailer ,array('b'=> true),'rb_hv','rb_xmv',$ar_val);
       $row_xm->htmlAdd($col);
       $envtable->htmlAdd($row_xm);
    }
}

if ($default_use_mdn) {
    if ($mdn_user_support) {
        if ($header->dnt) {
           $row_mdn = new html('tr','','','rb_r','rb_mdnc');
           $col = new html('td',_("Read receipt") . ':','','rb_hk','rb_mdnk', $ar_key);
	   $row_mdn->htmlAdd($col);
	   if ($message->is_mdnsent) {
              $mdn_string = _("send");
	   } else {
	      $mdn_string = _("requested");
	      global $draftfolder;
	      if ( !($mailbox == $draftfolder || $message->is_deleted)) {
                $mdn_url = 'read_body.php?mailbox='.$mailbox.'&passed_id='.
		            $passed_id.'&startMessage='.$startMessage.
			    '&show_more='.$show_more.'&sendreceipt=1';
		if ($FirstTimeSee && $javascript_on) {
                   $script = 'if (window.confirm("' .
                       _("The message sender has requested a response to indicate that you have read this message. Would you like to send a receipt?") .
                       '")) {  '."\n" .
                       '    window.open('.$mdn_url.',"right");' . "\n" .
                       '}' . "\n";
		   $body->scriptAdd($script);
		}
		$mdn_link = new html('a','[' . _("Send read receipt now") . ']','','','',
		            array('href' => $mdn_url));
	      }		    
	   }
           $col = new html('td',$mdn_string ,
	           array('b'=> true),'rb_hv','rb_mdnv',$ar_val);
	   if (isset($mdn_link)) {
	      $col->htmlAdd($mdn_link);
	   }
	   $row_mdn->htmlAdd($col);
	   $envtable->htmlAdd($row_mdn);	   
	}
    }	         
}	     

//$envtable->echoHtml($use_css);

$rb_tools_table = new html('table','','','rb_tools','',$ar_table);
$row = new html('tr','','','rb_rt','',array('width'=> '100%',
                                            'valign'=> 'top',
                                            'align'=> 'right',					    
					    'nowrap'));
/* view header */
$viewheader_url = $base_uri . 'src/read_body.php?mailbox=' . $urlMailbox . 
                  '&amp;passed_id='. $passed_id. '&amp;';
if ($where && $what) {
    $viewheader_url .= 'where=' . urlencode($where) . '&amp;what=' . urlencode($what) .
                       '&amp;view_hdr=1';
} else {
    $viewheader_url .= 'startMessage=' .$startMessage. '&amp;show_more='.
                       $show_more .'&amp;view_hdr=1';
}

$link = new html('a',_("View Full Header") .' | ','','','',array (
            'href' => $viewheader_url));
$col = new html('td','',array('small'=>true),'rb_ht','rb_vht');
$col->htmlAdd($link);

/* Output the printer friendly link if we are in subtle mode. */
if ($pf_subtle_link) {
   $link = new html('span',printer_friendly_link(true),'','rb_ht','rb_pft');
   $col->htmlAdd($link);
}
$row->htmlAdd($col);

//do_hook("read_body_header_right");

$rb_tools_table->htmlAdd($row);

//$rb_tools_table->echoHtml($use_css);

//do_hook('read_body_header');

if ($use_css) {
   $ar_row = array('align'=>$lft);
} else {
   $ar_row = array('align'=>$lft, 'bgcolor'=> $color[4]);
}


$rb_message_table =  new html('table','','','rb_body','',$ar_table);
$row_body = new html('tr','','','rb_bd','rb_bdr');
$col_body = new html('td',$messagebody,array('br'=>false),'rb_bd','rb_bdr',$ar_row);
$row_body->htmlAdd($col_body);
$rb_message_table->htmlAdd($row_body);

$row_body = new html('tr','','','rb_bd','rb_bdr');
$attachements = formatAttachments($message,$ent_ar,$mailbox, $passed_id);
$col_body = new html('td',$attachements,array('br'=>false),'rb_bd','rb_bdr',$ar_row);
$row_body->htmlAdd($col_body);
$rb_message_table->htmlAdd($row_body);

if ($use_css) {
   $ar_row = array('align'=>$lft);
} else {
   $ar_row = array('align'=>$lft, 'bgcolor'=> $color[4], 'cellpadding' =>3);
}

$body->htmlAdd($top);
$body->htmlAdd($menu);
$body->htmlAdd($topbar);
$body->htmlAdd($envtable);
$body->htmlAdd($rb_tools_table);
$body->htmlAdd($rb_message_table);

$page->html_el[0]->htmlAdd($head);
$page->html_el[0]->htmlAdd($body);

$page->echoHtml();




/* show attached images inline -- if pref'fed so */
if (($attachment_common_show_images) &&
    is_array($attachment_common_show_images_list)) {

    foreach ($attachment_common_show_images_list as $img) {
        $imgurl = '../src/download.php' .
                '?' .
                'passed_id='     . urlencode($img['passed_id']) .
                '&amp;mailbox='       . urlencode($mailbox) .
                '&amp;passed_ent_id=' . urlencode($img['ent_id']) .
                '&amp;absolute_dl=true';

        echo html_tag( 'table', "\n" .
	            html_tag( 'tr', "\n" .
	                html_tag( 'td', '<img src="' . $imgurl . '">' ."\n", 'left'
	                )
	            ) ,
        'center', '', 'cellspacing=0 border="0" cellpadding="2"');
    }
}


//do_hook('read_body_bottom');
//do_hook('html_bottom');
sqimap_logout($imapConnection);
?>

