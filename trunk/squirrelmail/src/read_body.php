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

/**
* Given an IMAP message id number, this will look it up in the cached
* and sorted msgs array and return the index. Used for finding the next
* and previous messages.
*
* returns the index of the next valid message from the array
*/
function findNextMessage() {
    global $msort, $currentArrayIndex, $msgs, $sort, 
           $thread_sort_messages, $allow_server_sort;
    $result = -1;
		if ($thread_sort_messages == 1) {
				$sort = 0;
		}
    if ($sort == 6 && $allow_server_sort != TRUE) {
        if ($currentArrayIndex != 1) {
            $result = $currentArrayIndex - 1;
        }
    } else {
        if (!is_array($msort)) {
            return -1;
        }
        for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) {
            if ($currentArrayIndex == $msgs[$key]['ID']) {
                next($msort);
                $key = key($msort);
                if (isset($key))
                    $result = $msgs[$key]['ID'];
                    break;
            }
        }
    }
    return ($result);
}

/** Removes just one address from the list of addresses. */
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
           $allow_server_sort;
		if ($thread_sort_messages == 1) {
				$sort = 0;
		}

    $result = -1;

    if ($sort == 6 && $allow_server_sort != TRUE) {
        $numMessages = sqimap_get_num_messages($imapConnection, $mailbox);
        if ($currentArrayIndex != $numMessages) {
            $result = $currentArrayIndex + 1;
        }
    } else {
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
        $result =       '<tr bgcolor="' . $color[0] . '">' .
                        '<td class="medText" align="right" valign="top">' .
                          '&nbsp;' .
                        '</td><td class="medText" valign="top" colspan="2">'."\n";
    } else {
        /* The link is subtle, below "view full header". */
        $result = "<BR>\n";
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
                "<A HREF=\"javascript:printFormat();\">$print_text</A>\n";
    } else {
        $result .= '<A TARGET="_blank" HREF="../src/printer_friendly_bottom.php' .
                "$params\">$print_text</A>\n";
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

    return (SendMessage($MDN_to,'','', _("Read:") . ' ' . $subject, $body,$reply_id, True, 3, -1) );
}


function ToggleMDNflag ( $set ) {
    global $imapConnection, $passed_id, $mailbox;
    sqimap_mailbox_select($imapConnection, $mailbox);
    
    $sg =  $set?'+':'-';
    $cmd = 'STORE ' . $passed_id . ' ' . $sg . 'FLAGS ($MDNSent)';
    $read = sqimap_run_command ($imapConnection, $cmd, true, $response, 
                                $readmessage);
}

function ClearAttachments() {
        global $username, $attachments, $attachment_dir;

        $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

	$rem_attachments = array();
        foreach ($attachments as $info) {
	    if ($info->session == -1) {
        	$attached_file = "$hashed_attachment_dir/$info[localfilename]";
        	if (file_exists($attached_file)) {
            	    unlink($attached_file);
        	}
    	    } else {
		$rem_attachments[] = $info;
	    }
	}
        $attachments = rem_attachments;
}

function formatRecipientString($recipients, $item ) {
    global $base_uri, $passed_id, $urlMailbox, $startMessage, $show_more_cc, $echo_more, $echo_less, $show_more, $show_more_bcc, $sort;

    $i = 0;
    $url_string = '';
    
    if (isset ($recipients[0]) && trim($recipients[0])) {
	$string = '';
        $ary = $recipients;

	switch ($item) {
	    case 'to':
		$show = "&amp;show_more=1&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=$show_more_bcc";
		$show_n = "&amp;show_more=0&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=$show_more_bcc";
		break;
	    case 'cc':
		$show = "&amp;show_more=$show_more&amp;show_more_cc=1&amp;show_more_bcc=$show_more_bcc";
		$show_n = "&amp;show_more=$show_more&amp;show_more_cc=0&amp;show_more_bcc=$show_more_bcc";
		$show_more = $show_more_cc;
		break;
	    case 'bcc':
		$show = "&amp;show_more=$show_more&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=1";
		$show_n = "&amp;show_more=$show_more&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=0";
		$show_more = $show_more_bcc;
		break;
	    default:
		$break;
	}

	while ($i < count($ary)) {
    	    $ary[$i] = htmlspecialchars(decodeHeader($ary[$i]));
    	    $url_string .= $ary[$i];
    	    if ($string) {
        	$string = "$string<BR>$ary[$i]";
    	    } else {
        	$string = "$ary[$i]";
    	    }

    	    $i++;
    	    if (count($ary) > 1) {
        	if ($show_more == false) {
            	    if ($i == 1) {
                	/* From a search... */
                	$string .= '&nbsp;(<A HREF="' . $base_uri .
                                   "src/read_body.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";
                	if (isset($where) && isset($what)) {
                    	    $string .= 'what=' . urlencode($what)."&amp;where=".urlencode($where)."$show\">$echo_more</A>)";
                	} else {
                    	    $string .= "sort=$sort&amp;startMessage=$startMessage"."$show\">$echo_more</A>)";
                	}
                	$i = count($ary);
            	    }
        	} else if ($i == 1) {
            	    /* From a search... */
            	    $string .= '&nbsp;(<A HREF="' . $base_uri .
                               "src/read_body.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";
            	    if (isset($where) && isset($what)) {
                	$string .= 'what=' . urlencode($what)."&amp;where=".urlencode($where)."$show_n\">$echo_less</A>)";
            	    } else {
                	$string .= "sort=$sort&amp;startMessage=$startMessage"."$show_n\">$echo_less</A>)";
            	    }
        	}
    	    }

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

if ( isset( $mailbox ) ) {
    $mailbox = urldecode( $mailbox );
}
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$read = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);

do_hook('html_top');

/*
 * The following code sets necesarry stuff for the MDN thing
 */
if( $default_use_mdn &&
    ( $mdn_user_support = getPref($data_dir, $username, 'mdn_user_support', $default_use_mdn) ) ) {

    $supportMDN = ServerMDNSupport($read["PERMANENTFLAGS"]);
    $flags = sqimap_get_flags ($imapConnection, $passed_id);
    $FirstTimeSee = !(in_array( 'Seen', $flags ));
}

displayPageHeader($color, $mailbox);


/*
 * The following code shows the header of the message and then exit
 */
if (isset($view_hdr)) {
    $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[HEADER]", true, $a, $b);

    echo '<BR>' .
        '<TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="0" BORDER="0" ALIGN="CENTER">' . "\n" .
        "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=\"100%\" ALIGN=\"CENTER\"><B>" . _("Viewing Full Header") . '</B> - '.
        '<a href="' . $base_uri . "src/read_body.php?mailbox=".urlencode($mailbox);
    if (isset($where) && isset($what)) {
        // Got here from a search
        echo "&amp;passed_id=$passed_id&amp;where=".urlencode($where)."&amp;what=".urlencode($what).'">';
    } else {
        echo "&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more\">";
    }
    echo _("View message") . "</a></b></td></tr></table>\n" .
         "<table width=\"99%\" cellpadding=2 cellspacing=0 border=0 align=center>\n" .
         '<tr><td>';

    $cnum = 0;
    for ($i=1; $i < count($read); $i++) {
        $line = htmlspecialchars($read[$i]);
        if (eregi("^&gt;", $line)) {
            $second[$i] = $line;
            $first[$i] = '&nbsp;';
            $cnum++;
        } else if (eregi("^[ |\t]", $line)) {
            $second[$i] = $line;
            $first[$i] = '';
        } else if (eregi("^([^:]+):(.+)", $line, $regs)) {
            $first[$i] = $regs[1] . ':';
            $second[$i] = $regs[2];
            $cnum++;
        } else {
            $second[$i] = trim($line);
            $first[$i] = '';
        }
    }
    for ($i=0; $i < count($second); $i = $j) {
        if (isset($first[$i])) {
            $f = $first[$i];
        }
        if (isset($second[$i])) {
            $s = nl2br($second[$i]);
        }
        $j = $i + 1;
        while (($first[$j] == '') && ($j < count($first))) {
            $s .= '&nbsp;&nbsp;&nbsp;&nbsp;' . nl2br($second[$j]);
            $j++;
        }
        parseEmail($s);
        if (isset($f)) {
            echo "<nobr><tt><b>$f</b>$s</tt></nobr>";
        }
    }
    echo "</td></tr></table>\n" .
         '</body></html>';
    sqimap_logout($imapConnection);
    exit;
}

if (isset($msgs)) {
    $currentArrayIndex = $passed_id;
} else {
    $currentArrayIndex = -1;
}

for ($i = 0; $i < count($msgs); $i++) {
    if ($msgs[$i]['ID'] == $passed_id) {
        $msgs[$i]['FLAG_SEEN'] = true;
    }
}

// $message contains all information about the message
// including header and body
$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

/** translate the subject and mailbox into url-able text **/
$url_subj = urlencode(trim($message->header->subject));
$urlMailbox = urlencode($mailbox);
$url_replyto = '';
if (isset($message->header->replyto)) {
    $url_replyto = urlencode($message->header->replyto);
}

$url_replytoall   = $url_replyto;

// If we are replying to all, then find all other addresses and
// add them to the list.  Remove duplicates.
// This is somewhat messy, so I'll explain:
// 1) Take all addresses (from, to, cc) (avoid nasty join errors here)
$url_replytoall_extra_addrs = array_merge(
    array($message->header->from),
    $message->header->to,
    $message->header->cc
);

// 2) Make one big string out of them
$url_replytoall_extra_addrs = join(';', $url_replytoall_extra_addrs);

// 3) Parse that into an array of addresses
$url_replytoall_extra_addrs = parseAddrs($url_replytoall_extra_addrs);

// 4) Make them unique -- weed out duplicates
// (Coded for PHP 4.0.0)
$url_replytoall_extra_addrs =
    array_keys(array_flip($url_replytoall_extra_addrs));

// 5) Remove the addresses we'll be sending the message 'to'
$url_replytoall_avoid_addrs = '';
if (isset($message->header->replyto)) {
    $url_replytoall_avoid_addrs = $message->header->replyto;
}

$url_replytoall_avoid_addrs = parseAddrs($url_replytoall_avoid_addrs);
foreach ($url_replytoall_avoid_addrs as $addr) {
    RemoveAddress($url_replytoall_extra_addrs, $addr);
}

// 6) Remove our identities from the CC list (they still can be in the
// TO list) only if $include_self_reply_all is turned off
if (!$include_self_reply_all) {
    RemoveAddress($url_replytoall_extra_addrs,
                getPref($data_dir, $username, 'email_address'));
    $idents = getPref($data_dir, $username, 'identities');
    if ($idents != '' && $idents > 1) {
        for ($i = 1; $i < $idents; $i ++) {
            $cur_email_address = getPref($data_dir, $username, 'email_address' . $i);
            RemoveAddress($url_replytoall_extra_addrs, $cur_email_address);
        }
    }
}

// 7) Smoosh back into one nice line
$url_replytoallcc = getLineOfAddrs($url_replytoall_extra_addrs);

// 8) urlencode() it
$url_replytoallcc = urlencode($url_replytoallcc);

$dateString = getLongDateString($message->header->date);

// What do we reply to -- text only, if possible
$ent_num = findDisplayEntity($message);

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

$cc = formatRecipientString($message->header->cc, "cc");
$cc_string = $cc['str'];
$url_cc_string = $cc['url_str'];

/** FORMAT THE BCC STRING **/

$bcc = formatRecipientString($message->header->bcc, "bcc");
$bcc_string = $bcc['str'];
$url_bcc_string = $bcc['url_str'];

if ($default_use_priority) {
    $priority_level = substr($message->header->priority,0,1);

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
$from_name = decodeHeader(htmlspecialchars($message->header->from));
$subject = decodeHeader(htmlspecialchars($message->header->subject));

do_hook('read_body_top');
echo '<BR>' .
     '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' .
        '<TR><TD BGCOLOR="' . $color[9] . '" WIDTH="100%">' .
           '<TABLE WIDTH="100%" CELLSPACING="0" BORDER="0" CELLPADDING="3">' .
              '<TR>' .
                 '<TD ALIGN="LEFT" WIDTH="33%">' .
                    '<SMALL>' .
     '<A HREF="' . $base_uri . 'src/';

if ($where && $what) {
    if( $pos == '' ) {
        $pos = 0;
    }
    echo "search.php?where$pos=".urlencode($where)."&amp;pos=$pos&amp;what$pos=".urlencode($what)."&amp;mailbox=$urlMailbox\">";
} else {
    echo "right_main.php?sort=$sort&amp;startMessage=$startMessage&amp;mailbox=$urlMailbox\">";
}
echo _("Message List") .
     '</A>&nbsp;|&nbsp;' .
     '<A HREF="' . $base_uri . "src/delete_message.php?mailbox=$urlMailbox&amp;message=$passed_id&amp;";
if ($where && $what) {
    echo 'where=' . urlencode($where) . '&amp;what=' . urlencode($what) . '">';
} else {
    echo "sort=$sort&amp;startMessage=$startMessage\">";
}
echo _("Delete") . '</A>&nbsp;';
if (($mailbox == $draft_folder) && ($save_as_draft)) {
    echo '|&nbsp;<A HREF="' . $base_uri .
         "src/compose.php?mailbox=$mailbox&amp;send_to=$url_to_string&amp;send_to_cc=$url_cc_string&amp;send_to_bcc=$url_bcc_string&amp;subject=$url_subj&amp;draft_id=$passed_id&amp;ent_num=$ent_num" . '"';
    if ($compose_new_win == '1') {
        echo 'TARGET="compose_window" onClick="comp_in_new()"';
    }
    echo '>'.
         _("Resume Draft") . '</a>';
}

echo '&nbsp;&nbsp;' .
                   '</SMALL>' .
                '</TD>' .
                '<TD WIDTH="33%" ALIGN="CENTER">' .
                   '<SMALL>';

if ( !($where && $what) ) {

    if ($currentArrayIndex == -1) {
        echo 'Previous&nbsp;|&nbsp;Next';
    } else {
        $prev = findPreviousMessage();
        $next = findNextMessage();

        if ($prev != -1) {
            echo '<a href="' . $base_uri . "src/read_body.php?passed_id=$prev&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Previous") . "</A>&nbsp;|&nbsp;";
        } else {
            echo _("Previous") . '&nbsp;|&nbsp;';
        }

        if ($next != -1) {
            echo '<a href="' . $base_uri . "src/read_body.php?passed_id=$next&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Next") . "</A>";
        } else {
            echo _("Next");
        }
    }
}

echo                '</SMALL>' .
                '</TD><TD WIDTH="33%" ALIGN="RIGHT">' .
                   '<SMALL>' .
                   '<A HREF="' . $base_uri . "src/compose.php?forward_id=$passed_id&amp;forward_subj=$url_subj&amp;".
                    ($default_use_priority?"mailprio=$priority_level&amp;":'')
                    ."mailbox=$urlMailbox&amp;ent_num=$ent_num\"";
    if ($compose_new_win == '1') {
        echo 'TARGET="compose_window" onClick="comp_in_new()"';
    }
    echo '>'.
    _("Forward") .
    '</A>&nbsp;|&nbsp;' .
                   '<A HREF="' . $base_uri . "src/compose.php?send_to=$url_replyto&amp;reply_subj=$url_subj&amp;".
                    ($default_use_priority?"mailprio=$priority_level&amp;":'').
                    "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num\"";
    if ($compose_new_win == '1') {
        echo 'TARGET="compose_window" onClick="comp_in_new()"';
    }
    echo '>'.
    _("Reply") .
    '</A>&nbsp;|&nbsp;' .
                   '<A HREF="' . $base_uri . "src/compose.php?send_to=$url_replytoall&amp;send_to_cc=$url_replytoallcc&amp;reply_subj=$url_subj&amp;".
                    ($default_use_priority?"mailprio=$priority_level&amp;":'').
                    "reply_id=$passed_id&amp;mailbox=$urlMailbox&amp;ent_num=$ent_num\"";
    if ($compose_new_win == '1') {
        echo 'TARGET="compose_window" onClick="comp_in_new()"';
    }
    echo '>'.
    _("Reply All") .
    '</A>&nbsp;&nbsp;' .
                   '</SMALL>' .
                '</TD>' .
             '</TR>' .
          '</TABLE>' .
       '</TD></TR>' .
       '<TR><TD WIDTH="100%">' .
       '<TABLE WIDTH="100%" BORDER="0" CELLSPACING="0" CELLPADDING="3">' . "\n" .
          '<TR>' . "\n";

/** subject **/
echo          "<TD BGCOLOR=\"$color[0]\" WIDTH=\"10%\" ALIGN=\"right\" VALIGN=\"top\">\n" .
    _("Subject:") .
             "</TD><TD BGCOLOR=\"$color[0]\" WIDTH=\"80%\" VALIGN=\"top\">\n" .
                "<B>$subject</B>&nbsp;\n" .
             "</TD>\n" .
             '<TD ROWSPAN="4" width="10%" BGCOLOR="' . $color[0] .
    '" ALIGN=right VALIGN=top NOWRAP><small>'.
    '<A HREF="' . $base_uri . "src/read_body.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";

/* From a search... */
if ($where && $what) {
    echo 'where=' . urlencode($where) . '&amp;what=' . urlencode($what) .
         "&amp;view_hdr=1\">" . _("View Full Header") . "</A>\n";
} else {
    echo "startMessage=$startMessage&amp;show_more=$show_more&amp;view_hdr=1\">" .
         _("View Full Header") . "</A>\n";
}

/* Output the printer friendly link if we are in subtle mode. */
if ($pf_subtle_link) {
    echo printer_friendly_link(true);
}

do_hook("read_body_header_right");
echo '</small></TD>' .
    ' </TR>';

/** from **/
echo       '<TR>' .
             '<TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' .
    _("From:") .
             '</TD><TD BGCOLOR="' . $color[0] . '">' .
                "<B>$from_name</B>&nbsp;\n" .
             '</TD>' .
          '</TR>';
/** date **/
echo       '<TR>' . "\n" .
             '<TD BGCOLOR="' . $color[0] . '" ALIGN="RIGHT">' . "\n" .
    _("Date:") .
             "</TD><TD BGCOLOR=\"$color[0]\">\n" .
                "<B>$dateString</B>&nbsp;\n" .
             '</TD>' . "\n" .
          '</TR>' . "\n";

/** to **/
echo       "<TR>\n" .
             "<TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>\n" .
    _("To:") .
             '</TD><TD BGCOLOR="' . $color[0] . '" VALIGN="TOP">' . "\n" .
                "<B>$to_string</B>&nbsp;\n" .
             '</TD>' . "\n" .
          '</TR>' . "\n";
/** cc **/
if (isset($cc_string) && $cc_string <> '') {
    echo       '<TR>' .
                 "<TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>" .
                    'Cc:' .
                 "</TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>" .
                    "<B>$cc_string</B>&nbsp;" .
                 '</TD>' .
              '</TR>' . "\n";
}

/** bcc **/
if (isset($bcc_string) && $bcc_string <> '') {
    echo       '<TR>'.
                 "<TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>" .
                    'Bcc:' .
                 "</TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>" .
                    "<B>$bcc_string</B>&nbsp;" .
                 '</TD>' .
              '</TR>' . "\n";
}
if ($default_use_priority && isset($priority_string) && $priority_string <> '' ) {
    echo       '<TR>' .
                 "<TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>" .
                       _("Priority") . ': '.
                 "</TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>" .
                    "<B>$priority_string</B>&nbsp;" .
                 '</TD>' .
              "</TR>" . "\n";
}

if ($show_xmailer_default) {
    $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY.PEEK[HEADER.FIELDS (X-Mailer User-Agent)]", true,
                            $response, $readmessage);
    $mailer = substr($read[1], strpos($read[1], " "));
    if (trim($mailer)) {
        echo       '<TR>' .
                     "<TD BGCOLOR=\"$color[0]\" ALIGN=RIGHT VALIGN=TOP>" .
                           _("Mailer") . ': '.
                     "</TD><TD BGCOLOR=\"$color[0]\" VALIGN=TOP colspan=2>" .
                        "<B>$mailer</B>&nbsp;" .
                     '</TD>' .
                  "</TR>" . "\n";
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
        $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY.PEEK[HEADER.FIELDS (Disposition-Notification-To)]", true,
                                $response, $readmessage);
        $MDN_to = substr($read[1], strpos($read[1], ' '));
        $MDN_flag_present = false;

        $read = sqimap_run_command ($imapConnection, "FETCH $passed_id FLAGS", true,
                                $response, $readmessage);

        $MDN_flag_present = preg_match( '/.*\$MDNSent/i', $read[0]);

        if (trim($MDN_to) &&
            (!isset( $sendreceipt ) || $sendreceipt == '' )  ) {

            if ( $MDN_flag_present && $supportMDN) {
                $sendreceipt = 'removeMDN';
                $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
                $sendreceipt='';
                /*
                if ($MDNDebug ) {
                    echo       '<TR>' .
                                 "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                       _("Read receipt") . ': ' .
                                 "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                    '<B>' .
                                    _("send") .
                                    "</B> <a href=$url>[" . _("Remove MDN flag") . ']  </a>'  .
                                 '</TD>' .
                             '</TR>' . "\n";
                } else {
                */
                echo       '<TR>' .
                             "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                   _("Read receipt") . ': ' .
                             "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                '<B>'._("send").'</B>'.
                             '</TD>' .
                         '</TR>' . "\n";
                /*
                }
                */

            } // when deleted or draft flag is set don't offer to send a MDN response
            else if ( ereg('\\Draft',$read[0] || ereg('\\Deleted',$read[0])) ) {
                echo       '<TR>' .
                            "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                _("Read receipt") . ': '.
                            "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                '<B>' . _("requested") . "</B>" .
                            '</TD>' .
                        '</TR>' . "\n";
            }
            // if no MDNsupport don't use the annoying popup messages
            else if (  !$FirstTimeSee ) {
                $sendreceipt = 'send';
                $url = "\"read_body.php?mailbox=$mailbox&passed_id=$passed_id&startMessage=$startMessage&show_more=$show_more&sendreceipt=$sendreceipt\"";
                echo       '<TR>' .
                            "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                _("Read receipt") . ': ' .
                            "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                '<B>' . _("requested") .
                                "</B> &nbsp; <a href=$url>[" . _("Send read receipt now") . "]</a>" .
                            '</TD>' .
                        '</TR>' . "\n";
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
                    "                       window.location=($url); \n" .
                    '                       window.reload()' . "\n" .
                    '               }' . "\n" .
                    '// -->' . "\n" .
                    '</script>' . "\n";
                }
                echo       '<TR>' .
                            "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                    _("Read receipt") . ': ' .
                            "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                '<B>' . _("requested") . "&nbsp&nbsp</B><a href=$url>" . '[' .
                                _("Send read receipt now") . ']  </a>' ." \n" .
                            '</TD>' .
                            '</TR>' . "\n";
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

                if ( SendMDN( $MDN_to, $final_recipient ) > 0 && $supportMDN ) {
                    ToggleMDNflag( true);
                }
            }
            $sendreceipt = 'removeMDN';
            $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
            $sendreceipt='';
            /*
            if ($MDNDebug && $supportMDN) {
            echo "      <TR>\n" .
                    "         <TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>\n" .
                    "            "._("Read receipt").": \n".
                    "         </TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>\n" .
                    '            <B>'._("send").'</B>'." <a href=$url>" . '[' . _("Remove MDN flag") . ']  </a>'  . "\n" .
                    '         </TD>' . "\n" .
                    '     </TR>' . "\n";
            } else {
            */
            echo "      <TR>\n" .
                    "         <TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>\n" .
                    "            "._("Read receipt").": \n".
                    "         </TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>\n" .
                    '            <B>'._("send").'</B>'. "\n" .
                    '         </TD>' . "\n" .
                    '     </TR>' . "\n";
            /*
            }
            */
        }
        elseif ($sendreceipt == 'removeMDN' ) {
            ToggleMDNflag ( false );

            $sendreceipt = 'send';
                $url = "\"read_body.php?mailbox=$mailbox&amp;passed_id=$passed_id&amp;startMessage=$startMessage&amp;show_more=$show_more&amp;sendreceipt=$sendreceipt\"";
                echo       '<TR>'.
                              "<TD BGCOLOR=\"$color[9]\"  ALIGN=RIGHT VALIGN=TOP>" .
                                    _("Read receipt") . ': ' .
                              "</TD><TD BGCOLOR=\"$color[9]\" VALIGN=TOP colspan=2>" .
                                 '<B>' . _("requested") .
                                 "</B> &nbsp; <a href=$url>[" . _("Send read receipt now") . "]</a>" .
                              '</TD>' .
                            '</TR>' . "\n";
            $sendreceipt = '';

        }
    }
}

do_hook('read_body_header');

echo '</TABLE>' .
    '   </TD></TR>' .
    '</TABLE>';
flush();

echo "<TABLE CELLSPACING=0 WIDTH=\"97%\" BORDER=0 ALIGN=CENTER CELLPADDING=0>\n" .
    "   <TR><TD BGCOLOR=\"$color[4]\" WIDTH=\"100%\">\n" .
    '<BR>'.
    formatBody($imapConnection, $message, $color, $wrap_at).
    '</TD></TR></TABLE>' .
    '<TABLE CELLSPACING="0" WIDTH="100%" BORDER="0" ALIGN="CENTER" CELLPADDING="0">' . "\n" .
    "   <TR><TD BGCOLOR=\"$color[9]\">&nbsp;</TD></TR>" .
    '</TABLE>' . "\n";

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

        echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>\n" .
              '<TR>' .
                '<TD>' .
                  "<img src=\"$imgurl\">\n" .
                "</TD>\n" .
              "</TR>\n" .
            "</TABLE>\n";

    }
}


do_hook('read_body_bottom');
do_hook('html_bottom');
sqimap_logout($imapConnection);
?>
</body>
</html>
