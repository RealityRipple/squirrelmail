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

/**
 * Removes just one address from the list of addresses. 
 * 
 * @param  &$addr_list  a by-ref array of addresses
 * @param  $addr        an address to remove
 * @return              void, since it operates on a by-ref param
 */
function RemoveAddress(&$addr_list, $addr) {
    if ($addr != '') {
        foreach (array_keys($addr_list, $addr) as $key_to_delete) {
            unset($addr_list[$key_to_delete]);
        }
    }
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
        $result = "<br>\n";
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

function SendMDN ( $recipient , $sender) {
    global $imapConnection, $mailbox, $username, $attachment_dir, $SERVER_NAME,
           $version, $attachments, $identity, $data_dir, $passed_id;

    $header = sqimap_get_message_header($imapConnection, $passed_id, $mailbox);
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

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
    $MDN_to = trim($recipient);
    $reply_id = 0;

    return (SendMessage($MDN_to, '', '', _("Read:") . ' ' . $subject, 
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
if (!isset($messages[$uidvalidity][$passed_id])) {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
    $messages[$uidvalidity][$passed_id] = $message;
    $header = $message->header;
} else {
    $message = $messages[$uidvalidity][$passed_id];
//    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
    if (isset($passed_ent_id)) {
       $message = $message->getEntity($passed_ent_id);
       $message->id = $passed_id;
       $message->mailbox = $mailbox;
    }
    $header = $message->header;
}

do_hook('html_top');

/*
 * The following code sets necesarry stuff for the MDN thing
 */
if($default_use_mdn &&
   ($mdn_user_support = getPref($data_dir, $username, 'mdn_user_support', 
                                $default_use_mdn))) {
    $supportMDN = ServerMDNSupport($mbx_response["PERMANENTFLAGS"]);
    $FirstTimeSee = !$message->is_seen;
}

displayPageHeader($color, $mailbox);

/*
 * The following code shows the header of the message and then exit
 */
if (isset($view_hdr)) {
   $template_vars = array();
   parse_viewheader($imapConnection,$passed_id,&$template_vars);
   $template_vars['return_address'] = set_url_var($PHP_SELF, 'view_hdr');
   view_header($template_vars, '', '</body></html>');
   exit;
}

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
 
$body = ''; 
    
/* first step in displaying multiple entities */

    $ent_ar = findDisplayEntity($message, false);
    $i = 0;
    for ($i = 0; $i < count($ent_ar); $i++) {
	$body .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i]);
    }


$ent_ar = findDisplayEntity($message,true);

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

do_hook('read_body_top');

echo '<br>' .
     html_tag( 'table', '', 'center', '', 'width="100%" cellpadding="0" cellspacing="0" border="0"' ) . "\n" .
     html_tag( 'tr' ) .
     html_tag( 'td', '', 'left', $color[9], 'width="100%"' ) .
     html_tag( 'table', '', '', '', 'width="100%" cellpadding="3" cellspacing="0" border="0"' ) . "\n" .
     html_tag( 'tr' ) .
     html_tag( 'td', '', 'left', '', 'width="33%"' ) .
     '<small>' .
     '<a href="' . $base_uri . 'src/';

if ($where && $what) {
    if ($pos == '') {
        $pos=0;
    }
    echo "search.php?where=".urlencode($where)."&amp;pos=$pos&amp;what=".urlencode($what)."&amp;mailbox=$urlMailbox\">";
} else {
    echo "right_main.php?sort=$sort&amp;startMessage=$startMessage&amp;mailbox=$urlMailbox\">";
}
echo _("Message List") .
     '</a>&nbsp;|&nbsp;' .
     '<a href="' . $base_uri . "src/delete_message.php?mailbox=$urlMailbox&amp;message=$passed_id&amp;";
if ($where && $what) {
    echo 'where=' . urlencode($where) . '&amp;what=' . urlencode($what) . '">';
} else {
    echo "sort=$sort&amp;startMessage=$startMessage\">";
}
echo _("Delete") . '</a>&nbsp;';
if (($mailbox == $draft_folder) && ($save_as_draft)) {
    $comp_uri = $base_uri . "src/compose.php?mailbox=$mailbox&amp;".
                "identity=$identity&amp;send_to=$url_to_string&amp;".
                "send_to_cc=$url_cc_string&amp;send_to_bcc=$url_bcc_string&amp;".
                "subject=$url_subj&amp;mailprio=$priority_level&amp;".
                "draft_id=$passed_id&amp;ent_num=$ent_num";

    if ($compose_new_win == '1') {
        echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(false,'$comp_uri')\"";
    } else {
        echo '|&nbsp;<a href="' . $comp_uri .'"';
    }
    echo '>'.
         _("Resume Draft") . '</a>';
}
if ($mailbox == $sent_folder) {
    $comp_uri = $base_uri . "src/compose.php?mailbox=$mailbox&amp;".
                "identity=$identity&amp;send_to=$url_to_string&amp;".
                "send_to_cc=$url_cc_string&amp;send_to_bcc=$url_bcc_string&amp;".
                "subject=$url_subj&amp;mailprio=$priority_level&amp;".
                "ent_num=$ent_num&amp;passed_id=$passed_id&amp;edit_as_new=1";

    if ($compose_new_win == '1') {
        echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(false,'$comp_uri')\"";
    } else {
        echo '|&nbsp;<a href="' . $comp_uri .'"';
    }
    echo '>'.
          _("Edit Message as New") . '</a>';
}

echo '&nbsp;&nbsp;' .
                   '</small>' .
                '</td>' .
                html_tag( 'td', '', 'center', '', 'width="33%"' ) .
                   '<small>';

if ( !($where && $what) ) {
    if ($currentArrayIndex == -1) {
        echo 'Previous&nbsp;|&nbsp;Next';
    } else {
        $prev = findPreviousMessage($mbx_response['EXISTS']);
        $next = findNextMessage();

        if ($prev != -1) {
            echo '<a href="' . $base_uri . "src/read_body.php?passed_id=$prev&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Previous") . "</a>&nbsp;|&nbsp;";
        } else {
            echo _("Previous") . '&nbsp;|&nbsp;';
        }

        if ($next != -1) {
            echo '<a href="' . $base_uri . "src/read_body.php?passed_id=$next&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Next") . "</a>";
        } else {
            echo _("Next");
        }
    }
}

echo                '</small>' .
                '</td>' .
                html_tag( 'td', '', 'right', '', 'width="33%"' ) .
                   '<small>' ;
$comp_uri = $base_uri . "src/compose.php?forward_id=$passed_id&amp;".
            "forward_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "mailbox=$urlMailbox&amp;ent_num=$ent_num";

if ($compose_new_win == '1') {
    echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(false,'$comp_uri')\"";
} else {
    echo '|&nbsp;<a href="' . $comp_uri .'"';
}

    echo '>'.
    _("Forward") .
    '</a>&nbsp;|&nbsp;';

$comp_uri = $base_uri . "src/compose.php?send_to=$url_replyto&amp;".
            "reply_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num";

if ($compose_new_win == '1') {
    echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(false,'$comp_uri')\"";
} else {
    echo '|&nbsp;<a href="' . $comp_uri .'"';
}

    echo '>'.
    _("Reply") .
    '</a>&nbsp;|&nbsp;';

$comp_uri = $base_uri . "src/compose.php?send_to=$url_replytoall&amp;".
            "send_to_cc=$url_replytoallcc&amp;reply_subj=$url_subj&amp;".
            ($default_use_priority?"mailprio=$priority_level&amp;":'').
            "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num";

if ($compose_new_win == '1') {
    echo "<a href=\"javascript:void(0)\" onclick=\"comp_in_new(false,'$comp_uri')\"";
} else {
    echo '|&nbsp;<a href="' . $comp_uri .'"';
}

    echo '>'.
    _("Reply All") .
    '</a>&nbsp;&nbsp;' .
                   '</small>' .
                '</td>' .
             '</tr>' .
          '</table>' .
       '</td></tr>' .
       html_tag( 'tr' ) .
       html_tag( 'td', '', 'left', '', 'width="100%"' ) .
       html_tag( 'table', '', '', '', 'width="100%" border="0" cellspacing="0" cellpadding="3"' ) .
       html_tag( 'tr' ) . "\n";

/** subject **/
echo html_tag( 'td', _("Subject:"), 'right', $color[0], 'width="10%" valign="top"' ) .
        html_tag( 'td', '<b>' . $subject . '</b>&nbsp;' . "\n", 'left', $color[0], 'width="80%" valign="top"' ) .
        html_tag( 'td', '', 'right', $color[0], 'rowspan="4" width="10%" valign="top" nowrap' ) .
             '<a href="' . $base_uri . "src/read_body.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";

/* From a search... */
if ($where && $what) {
    echo 'where=' . urlencode($where) . '&amp;what=' . urlencode($what) .
         "&amp;view_hdr=1\">" . _("View Full Header") . "</a>\n";
} else {
    echo "startMessage=$startMessage&amp;show_more=$show_more&amp;view_hdr=1\">" .
         _("View Full Header") . "</a>\n";
}

/* Output the printer friendly link if we are in subtle mode. */
if ($pf_subtle_link) {
    echo printer_friendly_link(true);
}

do_hook("read_body_header_right");
echo '</small></td>' .
    ' </tr>';

/** from **/
echo html_tag( 'tr') . "\n" .
    html_tag( 'td', _("From:"), 'right', $color[0], 'valign="top"' ) .
    html_tag( 'td', '', 'left', $color[0] ) .
        '<b>' . $from_name . '</b>&nbsp;&nbsp;';
   do_hook("read_body_after_from");
echo "&nbsp;\n" . '</td></tr>';
/** date **/
echo html_tag( 'tr', "\n" .
            html_tag( 'td', _("Date:"), 'right', $color[0], 'valign="top"' ) .
            html_tag( 'td',
                '<b>' . $dateString . '</b>&nbsp;' . "\n" ,
            'left', $color[0] )
       ) . "\n";
/** to **/
echo html_tag( 'tr', "\n" .
            html_tag( 'td', _("To:"), 'right', $color[0], 'valign="top"' ) .
            html_tag( 'td',
                '<b>' . $to_string . '</b>&nbsp;' . "\n" ,
            'left', $color[0] )
       ) . "\n";
/** cc **/
if (isset($cc_string) && $cc_string <> '') {
    echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("Cc:"), 'right', $color[0], 'valign="top"' ) .
                html_tag( 'td',
                    '<b>' . $cc_string . '</b>&nbsp;' . "\n" ,
                'left', $color[0], 'colspan="2" valign="top"' )
           ) . "\n";
}

/** bcc **/
if (isset($bcc_string) && $bcc_string <> '') {
    echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("Bcc:"), 'right', $color[0], 'valign="top"' ) .
                html_tag( 'td',
                    '<b>' . $bcc_string . '</b>&nbsp;' . "\n" ,
                'left', $color[0], 'colspan="2" valign="top"' )
           ) . "\n";
}
if ($default_use_priority && isset($priority_string) && $priority_string <> '' ) {
    echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("Priority") . ':', 'right', $color[0], 'valign="top"' ) .
                html_tag( 'td',
                    '<b>' . $priority_string . '</b>&nbsp;' . "\n" ,
                'left', $color[0], 'colspan="2" valign="top"' )
           ) . "\n";
}

if ($show_xmailer_default) {
    $mailer = $header->xmailer;
    if (trim($mailer)) {
       echo html_tag( 'tr', "\n" .
                   html_tag( 'td', _("Mailer") . ':', 'right', $color[0], 'valign="top"' ) .
                   html_tag( 'td',
                       '<b>' . $mailer . '</b>&nbsp;' ,
                   'left', $color[0], 'colspan="2" valign="top"' )
              ) . "\n";
    }
}

/* Output the printer friendly link if we are not in subtle mode. */
if (!$pf_subtle_link) {
    echo printer_friendly_link(true);
}

if ($default_use_mdn) {
    if ($mdn_user_support) {

        // debug gives you the capability to remove mdn-flags
        // $MDNDebug = false;

        if ($header->dnt) {
	   $MDN_to_o = $header->dnt;
	   $MDN_to = $MDN_to_o->getAddress();
	} else {
	   $MDN_to = '';   
        }

        if ($MDN_to && (!isset( $sendreceipt ) || $sendreceipt == '' )  ) {
            if ( $message->is_mdnsent && $supportMDN) {
                $sendreceipt = 'removeMDN';

                $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
                $sendreceipt='';
                /*
                if ($MDNDebug ) {
                    echo html_tag( 'tr', "\n" .
                                html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                                html_tag( 'td',
                                    '<b>' . _("send") . '</b>&nbsp;<a href="' . $url . '">[' . _("Remove MDN flag") . ']  </a>&nbsp;' ,
                                'left', $color[9], 'colspan="2" valign="top"' )
                            ) . "\n";
                } else {
                */
                echo html_tag( 'tr', "\n" .
                            html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                                html_tag( 'td',
                                    '<b>' . _("send") . '</b>&nbsp;' ,
                                'left', $color[9], 'colspan="2" valign="top"' )
                            ) . "\n";
                /*
                }
                */

            } // when deleted or draft flag is set don't offer to send a MDN response
            else if ( $message->is_draft || $message->is_deleted)  {
                echo html_tag( 'tr', "\n" .
                            html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                                html_tag( 'td',
                                    '<b>' . _("requested") . '</b>&nbsp;' ,
                                'left', $color[9], 'colspan="2" valign="top"' )
                            ) . "\n";
            }
            // if no MDNsupport don't use the annoying popup messages
            else if (  !$FirstTimeSee ) {
                $sendreceipt = 'send';
                $url = "\"read_body.php?mailbox=$mailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&sendreceipt=$sendreceipt\"";
                echo html_tag( 'tr', "\n" .
                            html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                                html_tag( 'td',
                                    '<b>' . _("requested") . '</b> &nbsp; <a href="' . $url . '">[' . _("Send read receipt now") . ']</a>',
                                'left', $color[9], 'colspan="2" valign="top"' )
                            ) . "\n";
                $sendreceipt='';
            }
            else {
                $sendreceipt = 'send';
                $url = "\"read_body.php?mailbox=$mailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&sendreceipt=$sendreceipt\"";
                if ($javascript_on) {
                echo "<script language=\"javascript\" type=\"text/javascript\">  \n" .
                    '<!-- ' . "\n" .
                    "               if (window.confirm(\"" .
                    _("The message sender has requested a response to indicate that you have read this message. Would you like to send a receipt?") .
                    "\")) {  \n" .
                    '                       window.open('.$url.',"right");' . "\n" .
                    '               }' . "\n" .
                    '// -->' . "\n" .
                    '</script>' . "\n";
                }
                echo html_tag( 'tr', "\n" .
                            html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                                html_tag( 'td',
                                    '<b>' . _("requested") . '</b>&nbsp&nbsp<a href="' . $url . '">[' . _("Send read receipt now") . ']</a>',
                                'left', $color[9], 'colspan="2" valign="top"' )
                            ) . "\n";
                $sendreceipt = '';
            }
        }

        if ( !isset( $sendreceipt ) || $sendreceipt == '' ) {
        } else if ( $sendreceipt == 'send' ) {
            if ( !$MDN_flag_present) {
                if (isset($identity) ) {
                    $final_recipient = getPref($data_dir, $username, 'email_address' . '0', '' );
                } else {
                    $final_recipient = getPref($data_dir, $username, 'email_address', '' );
                }


                $final_recipient = trim($final_recipient);
                if ($final_recipient == '' ) {
                    $final_recipient = getPref($data_dir, $username, 'email_address', '' );
                }

                if ( SendMDN( $MDN_to, $final_recipient, $message ) > 0 && $supportMDN ) {
                    ToggleMDNflag( true);
                }
	        ClearAttachments();
            }
            $sendreceipt = 'removeMDN';
            $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
            $sendreceipt='';
            /*
            if ($MDNDebug && $supportMDN) {
            echo html_tag( 'tr', "\n" .
                       html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                           html_tag( 'td',
                               '<b>' . _("send") . '</b>&nbsp&nbsp<a href="' . $url . '">[' . _("Remove MDN flag") . ']</a>',
                           'left', $color[9], 'colspan="2" valign="top"' )
                       ) . "\n";
            } else {
            */
            echo html_tag( 'tr', "\n" .
                       html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                           html_tag( 'td',
                               '<b>' . _("send") . '</b>&nbsp',
                           'left', $color[9], 'colspan="2" valign="top"' )
                       ) . "\n";
            /*
            }
            */
        }
        elseif ($sendreceipt == 'removeMDN' ) {
            ToggleMDNflag ( false );

            $sendreceipt = 'send';
                $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
                echo html_tag( 'tr', "\n" .
                           html_tag( 'td', _("Read receipt") . ':', 'right', $color[9], 'valign="top"' ) .
                           html_tag( 'td',
                               '<b>' . _("requested") . '</b> &nbsp; <a href="' . $url . '">[' . _("Send read receipt now") . ']</a>',
                           'left', $color[9], 'colspan="2" valign="top"' )
                       ) . "\n";
            $sendreceipt = '';

        }
    }
}

do_hook('read_body_header');

echo '</table>' .
    '   </td></tr>' .
    '</table>';
flush();
echo html_tag( 'table', "\n" .
            html_tag( 'tr', "\n" .
                html_tag( 'td', '<br>' . "\n" . $body . "\n", 'left', $color[4]
                )
            ) ,
        'center', '', 'cellspacing=0 width="97%" border="0" cellpadding="0"') .

        html_tag( 'table', "\n" .
	            html_tag( 'tr', "\n" .
	                html_tag( 'td', '&nbsp;', 'left', $color[9]
	                )
	            ) ,
        'center', '', 'cellspacing=0 width="100%" border="0" cellpadding="0"');

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


do_hook('read_body_bottom');
do_hook('html_bottom');
sqimap_logout($imapConnection);
?>
</body>
</html>
