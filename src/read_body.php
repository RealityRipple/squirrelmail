<?php

/**
 * read_body.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/url_parser.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/identity.php');
require_once(SM_PATH . 'functions/mailbox_display.php');

/**
 * Given an IMAP message id number, this will look it up in the cached
 * and sorted msgs array and return the index. Used for finding the next
 * and previous messages.
 *
 * @return the index of the next valid message from the array
 */
function findNextMessage($passed_id) {
    global $msort, $msgs, $sort, 
           $thread_sort_messages, $allow_server_sort,
           $server_sort_array;
    if (!is_array($server_sort_array)) {
        $thread_sort_messages = 0;
        $allow_server_sort = FALSE;
    }
    $result = -1;
    if ($thread_sort_messages || $allow_server_sort) {
        $count = count($server_sort_array) - 1;
        foreach($server_sort_array as $key=>$value) {
            if ($passed_id == $value) {
                if ($key == $count) {
                    break;
                }
                $result = $server_sort_array[$key + 1];
                break; 
            }
        }
    } else {
        if (is_array($msort)) {
            for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) {
                if ($passed_id == $msgs[$key]['ID']) {
                    next($msort);
                    $key = key($msort);
                    if (isset($key)){
                        $result = $msgs[$key]['ID'];
                        break;
                    }
                }
            }
        }
    }
    return $result;
}

/** returns the index of the previous message from the array. */
function findPreviousMessage($numMessages, $passed_id) {
    global $msort, $sort, $msgs,
           $thread_sort_messages,
           $allow_server_sort, $server_sort_array;
    $result = -1;
    if (!is_array($server_sort_array)) {
        $thread_sort_messages = 0;
        $allow_server_sort = FALSE;
    }
    if ($thread_sort_messages || $allow_server_sort ) {
        foreach($server_sort_array as $key=>$value) {
            if ($passed_id == $value) {
                if ($key == 0) {
                    break;
                }
                $result = $server_sort_array[$key - 1];
                break;
            }
        }
    } else {
        if (is_array($msort)) {
            for (reset($msort); ($key = key($msort)), (isset($key)); next($msort)) {
                if ($passed_id == $msgs[$key]['ID']) {
                    prev($msort);
                    $key = key($msort);
                    if (isset($key)) {
                        $result = $msgs[$key]['ID'];
                        break;
                    }
                }
            }
        }
    }
    return $result;
}

/**
 * Displays a link to a page where the message is displayed more
 * "printer friendly".
 */
function printer_friendly_link($mailbox, $passed_id, $passed_ent_id, $color) {
    global $javascript_on;

    $params = '?passed_ent_id=' . $passed_ent_id .
              '&mailbox=' . urlencode($mailbox) .
              '&passed_id=' . $passed_id;

    $print_text = _("View Printable Version");

    $result = '';
    /* Output the link. */
    if ($javascript_on) {
        $result = '<script language="javascript" type="text/javascript">' . "\n" .
                  '<!--' . "\n" .
                  "  function printFormat() {\n" .
                  '    window.open("../src/printer_friendly_main.php' .
                  $params . '","Print","width=800,height=600");' . "\n".
                  "  }\n" .
                  "// -->\n" .
                  "</script>\n" .
                  "<a href=\"javascript:printFormat();\">$print_text</a>\n";
    } else {
        $result = '<a target="_blank" href="../src/printer_friendly_bottom.php' .
                  "$params\">$print_text</a>\n";
    }
    return $result;
}

function ServerMDNSupport($read) {
    /* escaping $ doesn't work -> \x36 */    
    $ret = preg_match('/(\x36MDNSent|\\\\\*)/i', $read);
    return $ret;
}

function SendMDN ( $mailbox, $passed_id, $sender, $message, $imapConnection) {
    global $username, $attachment_dir, 
           $version, $attachments, $squirrelmail_language, $default_charset,
           $languages, $useSendmail, $domain, $sent_folder,
           $popuser, $data_dir, $username;

    sqgetGlobalVar('SERVER_NAME', $SERVER_NAME, SQ_SERVER);

    $header = $message->rfc822_header;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $rfc822_header = new Rfc822Header();
    $content_type  = new ContentType('multipart/report');
    $content_type->properties['report-type']='disposition-notification';

    set_my_charset();
    if ($default_charset) {
        $content_type->properties['charset']=$default_charset;
    }
    $rfc822_header->content_type = $content_type;
    $rfc822_header->to[] = $header->dnt;
    $rfc822_header->subject = _("Read:") . ' ' . encodeHeader($header->subject);

    // Patch #793504 Return Receipt Failing with <@> from Tim Craig (burny_md)
    // This merely comes from compose.php and only happens when there is no
    // email_addr specified in user's identity (which is the startup config)
    if (ereg("^([^@%/]+)[@%/](.+)$", $username, $usernamedata)) {
       $popuser = $usernamedata[1];
       $domain  = $usernamedata[2];
       unset($usernamedata);
    } else {
       $popuser = $username;
    }

    $reply_to = '';
    $ident = get_identities();
    if(!isset($identity)) $identity = 0;
    $full_name = $ident[$identity]['full_name'];
    $from_mail = $ident[$identity]['email_address'];
    $from_addr = '"'.$full_name.'" <'.$from_mail.'>';
    $reply_to  = $ident[$identity]['reply_to'];

    if (!$from_mail) {
       $from_mail = "$popuser@$domain";
       $from_addr = $from_mail;
    }
    $rfc822_header->from = $rfc822_header->parseAddress($from_addr,true);
    if ($reply_to) {
       $rfc822_header->reply_to = $rfc822_header->parseAddress($reply_to,true);
    }

    // part 1 (RFC2298)
    $senton = getLongDateString( $header->date );
    $to_array = $header->to;
    $to = '';
    foreach ($to_array as $line) {
        $to .= ' '.$line->getAddress();
    }
    $now = getLongDateString( time() );
    set_my_charset();
    $body = _("Your message") . "\r\n\r\n" .
            "\t" . _("To:") . ' ' . decodeHeader($to,false,false) . "\r\n" .
            "\t" . _("Subject:") . ' ' . decodeHeader($header->subject,false,false) . "\r\n" .
            "\t" . _("Sent:") . ' ' . $senton . "\r\n" .
            "\r\n" .
            sprintf( _("Was displayed on %s"), $now );

    $special_encoding = '';
    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) && 
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        $body = $languages[$squirrelmail_language]['XTRA_CODE']('encode', $body);
        if (strtolower($default_charset) == 'iso-2022-jp') {
            if (mb_detect_encoding($body) == 'ASCII') {
                $special_encoding = '8bit';
            } else {
                $body = mb_convert_encoding($body, 'JIS');
                $special_encoding = '7bit';
            }
        }
    }
    $part1 = new Message();
    $part1->setBody($body);
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
    $part1->mime_header = $mime_header;

    // part2  (RFC2298)
    $original_recipient  = $to;
    $original_message_id = $header->message_id;

    $report = "Reporting-UA : $SERVER_NAME ; SquirrelMail (version $version) \r\n";
    if ($original_recipient != '') {
        $report .= "Original-Recipient : $original_recipient\r\n";
    }
    $final_recipient = $sender;
    $report .= "Final-Recipient: rfc822; $final_recipient\r\n" .
              "Original-Message-ID : $original_message_id\r\n" .
              "Disposition: manual-action/MDN-sent-manually; displayed\r\n";

    $part2 = new Message();
    $part2->setBody($report);
    $mime_header = new MessageHeader;
    $mime_header->type0 = 'message';
    $mime_header->type1 = 'disposition-notification';
    $mime_header->encoding = 'us-ascii';
    $part2->mime_header = $mime_header;

    $composeMessage = new Message();
    $composeMessage->rfc822_header = $rfc822_header;
    $composeMessage->addEntity($part1);
    $composeMessage->addEntity($part2);


    if ($useSendmail) {
        require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
        global $sendmail_path;
        $deliver = new Deliver_SendMail();
        $stream = $deliver->initStream($composeMessage,$sendmail_path);
    } else {
        require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
        $deliver = new Deliver_SMTP();
        global $smtpServerAddress, $smtpPort, $smtp_auth_mech, $pop_before_smtp;
        if ($smtp_auth_mech == 'none') {
            $user = '';
            $pass = '';
        } else {
            global $key, $onetimepad;
            $user = $username;
            $pass = OneTimePadDecrypt($key, $onetimepad);
        }
        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
        $stream = $deliver->initStream($composeMessage,$domain,0,
                                       $smtpServerAddress, $smtpPort, $user, $pass, $authPop);
    }
    $success = false;
    if ($stream) {
        $length  = $deliver->mail($composeMessage, $stream);
        $success = $deliver->finalizeStream($stream);
    }
    if (!$success) {
        $msg  = $deliver->dlv_msg . '<br>' .
                _("Server replied: ") . $deliver->dlv_ret_nr . ' '.
                $deliver->dlv_server_msg;
        require_once(SM_PATH . 'functions/display_messages.php');
        plain_error_message($msg, $color);
    } else {
        unset ($deliver);
        if (sqimap_mailbox_exists ($imapConnection, $sent_folder)) {
            sqimap_append ($imapConnection, $sent_folder, $length);
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imapConnection);
            sqimap_append_done ($imapConnection);
            unset ($imap_deliver);
        }
    }
    return $success;
}

function ToggleMDNflag ($set ,$imapConnection, $mailbox, $passed_id, $uid_support) {
    $sg   =  $set?'+':'-';
    $cmd  = 'STORE ' . $passed_id . ' ' . $sg . 'FLAGS ($MDNSent)';
    $read = sqimap_run_command ($imapConnection, $cmd, true, $response, 
                                $readmessage, $uid_support);
}

function ClearAttachments() {
    global $username, $attachments, $attachment_dir;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $rem_attachments = array();
    if (isset($attachments)) {
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
    }
    $attachments = $rem_attachments;
}

function formatRecipientString($recipients, $item ) {
    global $show_more_cc, $show_more, $show_more_bcc,
           $PHP_SELF;

    $string = '';
    if ((is_array($recipients)) && (isset($recipients[0]))) {
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
                $show = true;
                $url = set_url_var($PHP_SELF, 'show_more_cc',0);
            } else {
                $url = set_url_var($PHP_SELF, 'show_more_cc',1);
            }
        } else if ($item == 'bcc') {
            if ($show_more_bcc) {
                $show = true;
                $url = set_url_var($PHP_SELF, 'show_more_bcc',0);
            } else {
                $url = set_url_var($PHP_SELF, 'show_more_bcc',1);
            }
        }

        $cnt = count($recipients);
        foreach($recipients as $r) {
            $add = decodeHeader($r->getAddress(true));
            if ($string) {
                $string .= '<BR>' . $add;
            } else {
                $string = $add;
                if ($cnt > 1) {
                    $string .= '&nbsp;(<A HREF="'.$url;
                    if ($show) {
                       $string .= '">'._("less").'</A>)';
                    } else {
                       $string .= '">'._("more").'</A>)';
                       break;
                    }
                }
            }
        }
    }
    return $string;
}

function formatEnvheader($mailbox, $passed_id, $passed_ent_id, $message, 
                         $color, $FirstTimeSee) {
    global $msn_user_support, $default_use_mdn, $default_use_priority,
           $show_xmailer_default, $mdn_user_support, $PHP_SELF, $javascript_on,
	   $squirrelmail_language;

    $header = $message->rfc822_header;
    $env = array();
    $env[_("Subject")] = str_replace("&nbsp;"," ",decodeHeader($header->subject));

    $from_name = $header->getAddr_s('from');
    if (!$from_name)
        $from_name = $header->getAddr_s('sender');
    if (!$from_name)
        $env[_("From")] = _("Unknown sender");
    else
        $env[_("From")] = decodeHeader($from_name);
    $env[_("Date")] = getLongDateString($header->date);
    $env[_("To")] = formatRecipientString($header->to, "to");
    $env[_("Cc")] = formatRecipientString($header->cc, "cc");
    $env[_("Bcc")] = formatRecipientString($header->bcc, "bcc");
    if ($default_use_priority) {
        $env[_("Priority")] = htmlspecialchars(getPriorityStr($header->priority));
    }
    if ($show_xmailer_default) {
        $env[_("Mailer")] = decodeHeader($header->xmailer);
    }
    if ($default_use_mdn) {
        if ($mdn_user_support) {
            if ($header->dnt) {
                if ($message->is_mdnsent) {
                    $env[_("Read receipt")] = _("sent");
                } else {
                    $env[_("Read receipt")] = _("requested"); 
                    if (!(handleAsSent($mailbox) || 
                          $message->is_deleted ||
                          $passed_ent_id)) {
                        $mdn_url = $PHP_SELF . '&sendreceipt=1';
                        if ($FirstTimeSee && $javascript_on) {
                            $script  = '<script language="JavaScript" type="text/javascript">' . "\n";
                            $script .= '<!--'. "\n";
                            $script .= 'if(window.confirm("' .
                                       _("The message sender has requested a response to indicate that you have read this message. Would you like to send a receipt?") .
                                       '")) {  '."\n" .
                                       '    sendMDN()'.
                                       '}' . "\n";
                            $script .= '// -->'. "\n";
                            $script .= '</script>'. "\n";
                            echo $script;
                        }
                        $env[_("Read receipt")] .= '&nbsp;<a href="' . $mdn_url . '">[' .
                                                   _("Send read receipt now") . ']</a>';
                    }
                }
            }
        }
    }

    $s  = '<TABLE WIDTH="100%" CELLPADDING="0" CELLSPACING="2" BORDER="0"';
    $s .= ' ALIGN="center" BGCOLOR="'.$color[0].'">';
    foreach ($env as $key => $val) {
        if ($val) {
            $s .= '<TR>';
            $s .= html_tag('TD', '<B>' . $key . ':&nbsp;&nbsp;</B>', 'RIGHT', '', 'VALIGN="TOP" WIDTH="20%"') . "\n";
            $s .= html_tag('TD', $val, 'left', '', 'VALIGN="TOP" WIDTH="80%"') . "\n";
            $s .= '</TR>';
        }
    }
    echo '<TABLE BGCOLOR="'.$color[9].'" WIDTH="100%" CELLPADDING="1"'.
         ' CELLSPACING="0" BORDER="0" ALIGN="center">'."\n";
    echo '<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'.
          $color[4].'"></TD></TR><TR><TD align=center>'."\n";
    echo $s;
    do_hook('read_body_header');
    formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color);
    echo '</TABLE>';
    echo '</TD></TR><TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'.$color[4].'"></TD></TR>'."\n";
    echo '</TABLE>';
}

function formatMenubar($mailbox, $passed_id, $passed_ent_id, $message, $mbx_response) {
    global $base_uri, $draft_folder, $where, $what, $color, $sort,
           $startMessage, $PHP_SELF, $save_as_draft,
           $enable_forward_as_attachment;

    $topbar_delimiter = '&nbsp;|&nbsp;';
    $urlMailbox = urlencode($mailbox);
    $s = '<table width="100%" cellpadding="3" cellspacing="0" align="center"'.
         ' border="0" bgcolor="'.$color[9].'"><tr>' .
         html_tag( 'td', '', 'left', '', 'width="33%"' ) . '<small>';

    $msgs_url = $base_uri . 'src/';
    if (isset($where) && isset($what)) {
        $msgs_url .= 'search.php?where=' . urlencode($where) .
                     '&amp;what=' . urlencode($what) . '&amp;mailbox=' . $urlMailbox;
        $msgs_str  = _("Search results");
    } else {
        $msgs_url .= 'right_main.php?sort=' . $sort . '&amp;startMessage=' .
                     $startMessage . '&amp;mailbox=' . $urlMailbox;
        $msgs_str  = _("Message List");
    }
    $s .= '<a href="' . $msgs_url . '">' . $msgs_str . '</a>';

    $delete_url = $base_uri . 'src/delete_message.php?mailbox=' . $urlMailbox .
                  '&amp;message=' . $passed_id . '&amp;';
    if (!(isset($passed_ent_id) && $passed_ent_id)) {
        if ($where && $what) {
            $delete_url .= 'where=' . urlencode($where) . '&amp;what=' . urlencode($what);
        } else {
            $delete_url .= 'sort=' . $sort . '&amp;startMessage=' . $startMessage;
        }
        $s .= $topbar_delimiter;
        $s .= '<a href="' . $delete_url . '">' . _("Delete") . '</a>';
    }

    $comp_uri = 'src/compose.php' .
                            '?passed_id=' . $passed_id .
                            '&amp;mailbox=' . $urlMailbox .
                            '&amp;startMessage=' . $startMessage .
                            (isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'');

    if (($mailbox == $draft_folder) && ($save_as_draft)) {
        $comp_alt_uri = $comp_uri . '&amp;smaction=draft';
        $comp_alt_string = _("Resume Draft");
    } else if (handleAsSent($mailbox)) {
        $comp_alt_uri = $comp_uri . '&amp;smaction=edit_as_new';
        $comp_alt_string = _("Edit Message as New");
    }
    if (isset($comp_alt_uri)) {
        $s .= $topbar_delimiter;
        $s .= makeComposeLink($comp_alt_uri, $comp_alt_string);
    }

    $s .= '</small></td><td align="center" width="33%"><small>';

    if (!(isset($where) && isset($what)) && !$passed_ent_id) {
        $prev = findPreviousMessage($mbx_response['EXISTS'], $passed_id);
        $next = findNextMessage($passed_id);
        if ($prev != -1) {
            $uri = $base_uri . 'src/read_body.php?passed_id='.$prev.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
            $s .= '<a href="'.$uri.'">'._("Previous").'</a>';       
        } else {
            $s .= _("Previous");
        }
        $s .= $topbar_delimiter;
        if ($next != -1) {
            $uri = $base_uri . 'src/read_body.php?passed_id='.$next.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
            $s .= '<a href="'.$uri.'">'._("Next").'</a>';
        } else {
            $s .= _("Next");
        }
    } else if (isset($passed_ent_id) && $passed_ent_id) {
        /* code for navigating through attached message/rfc822 messages */
        $url = set_url_var($PHP_SELF, 'passed_ent_id',0);
        $s .= '<a href="'.$url.'">'._("View Message").'</a>';
        $entities     = array();
        $entity_count = array();
        $c = 0;

        foreach($message->parent->entities as $ent) {
            if ($ent->type0 == 'message' && $ent->type1 == 'rfc822') {
                $c++;
                $entity_count[$c] = $ent->entity_id;
                $entities[$ent->entity_id] = $c;
            }
        }
        $prev_link = _("Previous");
        $next_link = _("Next");
        if($entities[$passed_ent_id] > 1) {
            $prev_ent_id = $entity_count[$entities[$passed_ent_id] - 1];
            $prev_link   = '<a href="'
                         . set_url_var($PHP_SELF, 'passed_ent_id', $prev_ent_id)
                         . '">' . $prev_link . '</a>';
        }
        if($entities[$passed_ent_id] < $c) {
            $next_ent_id = $entity_count[$entities[$passed_ent_id] + 1];
            $next_link   = '<a href="'
                         . set_url_var($PHP_SELF, 'passed_ent_id', $next_ent_id)
                         . '">' . $next_link . '</a>';
        }
        $s .= $topbar_delimiter . $prev_link;
        $par_ent_id = $message->parent->entity_id;
        if ($par_ent_id) {
            $par_ent_id = substr($par_ent_id,0,-2);
            $s .= $topbar_delimiter;
            $url = set_url_var($PHP_SELF, 'passed_ent_id',$par_ent_id);
            $s .= '<a href="'.$url.'">'._("Up").'</a>';
        }
        $s .= $topbar_delimiter . $next_link;
    }

    $s .= '</small></td>' . "\n" . 
          html_tag( 'td', '', 'right', '', 'width="33%" nowrap' ) . '<small>';
    $comp_action_uri = $comp_uri . '&amp;smaction=forward';
    $s .= makeComposeLink($comp_action_uri, _("Forward"));

    if ($enable_forward_as_attachment) {
        $comp_action_uri = $comp_uri . '&amp;smaction=forward_as_attachment';
        $s .= $topbar_delimiter;
        $s .= makeComposeLink($comp_action_uri, _("Forward as Attachment"));
    }

    $comp_action_uri = $comp_uri . '&amp;smaction=reply';
    $s .= $topbar_delimiter;
    $s .= makeComposeLink($comp_action_uri, _("Reply"));

    $comp_action_uri = $comp_uri . '&amp;smaction=reply_all';
    $s .= $topbar_delimiter;
    $s .= makeComposeLink($comp_action_uri, _("Reply All"));
    $s .= '</small></td></tr></table>';
    do_hook('read_body_menu_top');
    echo $s;
    do_hook('read_body_menu_bottom');
}

function formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color) {
    global $base_uri;

    $urlMailbox = urlencode($mailbox);
    sqgetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER);
    $url = $base_uri.'src/view_header.php?'.$query_string;

    $s  = "<TR>\n" .
          html_tag( 'td', '', 'right', '', 'VALIGN="MIDDLE" WIDTH="20%"' ) . '<B>' . _("Options") . ":&nbsp;&nbsp;</B></TD>\n" .
          html_tag( 'td', '', 'left', '', 'VALIGN="MIDDLE" WIDTH="80%"' ) . '<SMALL>' .
          '<a href="'.$url.'">'._("View Full Header").'</a>';

    /* Output the printer friendly link if we are in subtle mode. */
    $s .= '&nbsp;|&nbsp;' .
          printer_friendly_link($mailbox, $passed_id, $passed_ent_id, $color);
    echo $s;
    do_hook("read_body_header_right");
    $s = "</SMALL></TD>\n" .
         "</TR>\n";
    echo $s;

}

/***************************/
/*   Main of read_body.php */
/***************************/

/* get the globals we may need */

sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('base_uri',  $base_uri,      SQ_SESSION);

sqgetGlobalVar('msgs',      $msgs,          SQ_SESSION);
sqgetGlobalVar('msort',     $msort,         SQ_SESSION);
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);
sqgetGlobalVar('server_sort_array', $server_sort_array, SQ_SESSION);
if (!sqgetGlobalVar('messages', $messages, SQ_SESSION) ) {
    $messages = array();
}

/** GET VARS */
sqgetGlobalVar('sendreceipt',   $sendreceipt,   SQ_GET);
sqgetGlobalVar('where',         $where,         SQ_GET);
sqgetGlobalVar('what',          $what,          SQ_GET);
if ( sqgetGlobalVar('show_more', $temp,  SQ_GET) ) {
    $show_more = (int) $temp;
}
if ( sqgetGlobalVar('show_more_cc', $temp,  SQ_GET) ) {
    $show_more_cc = (int) $temp;
}
if ( sqgetGlobalVar('show_more_bcc', $temp,  SQ_GET) ) {
    $show_more_bcc = (int) $temp;
}
if ( sqgetGlobalVar('view_hdr', $temp,  SQ_GET) ) {
    $view_hdr = (int) $temp;
}

/** POST VARS */
sqgetGlobalVar('move_id',       $move_id,       SQ_POST);

/** GET/POST VARS */
sqgetGlobalVar('passed_ent_id', $passed_ent_id);
sqgetGlobalVar('mailbox',       $mailbox);

if ( sqgetGlobalVar('passed_id', $temp) ) {
    $passed_id = (int) $temp;
}
if ( sqgetGlobalVar('sort', $temp) ) {
    $sort = (int) $temp;
}
if ( sqgetGlobalVar('startMessage', $temp) ) {
    $startMessage = (int) $temp;
}

/* end of get globals */
global $uid_support, $sqimap_capabilities;

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response   = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);


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
   $FirstTimeSee = !$message->is_seen;
   $message->is_seen = true;
   $messages[$uidvalidity][$passed_id] = $message;
} else {
//   $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
   $message = $messages[$uidvalidity][$passed_id];
   $FirstTimeSee = !$message->is_seen;
}

if (isset($passed_ent_id) && $passed_ent_id) {
   $message = $message->getEntity($passed_ent_id);
   if ($message->type0 != 'message'  && $message->type1 != 'rfc822') {
      $message = $message->parent;
   }
   $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[$passed_ent_id.HEADER]", true, $response, $msg, $uid_support);
   $rfc822_header = new Rfc822Header();
   $rfc822_header->parseHeader($read);
   $message->rfc822_header = $rfc822_header;
} else {
   $passed_ent_id = 0;
}
$header = $message->header;

do_hook('html_top');

/****************************************/
/* Block for handling incoming url vars */
/****************************************/

if (isset($sendreceipt)) {
   if ( !$message->is_mdnsent ) {
      $final_recipient = '';
      if ((isset($identity)) && ($identity != 0))	//Main identity
         $final_recipient = trim(getPref($data_dir, $username, 'email_address' . $identity, '' ));
      if ($final_recipient == '' )
         $final_recipient = trim(getPref($data_dir, $username, 'email_address', '' ));
      $supportMDN = ServerMDNSupport($mbx_response["PERMANENTFLAGS"]);
      if ( SendMDN( $mailbox, $passed_id, $final_recipient, $message, $imapConnection ) > 0 && $supportMDN ) {
         ToggleMDNflag( true, $imapConnection, $mailbox, $passed_id, $uid_support);
         $message->is_mdnsent = true;
         $messages[$uidvalidity][$passed_id]=$message;
      }
      ClearAttachments();
   }
}
/***********************************************/
/* End of block for handling incoming url vars */
/***********************************************/

$msgs[$passed_id]['FLAG_SEEN'] = true;

$messagebody = '';
do_hook('read_body_top');
if ($show_html_default == 1) {
    $ent_ar = $message->findDisplayEntity(array());
} else {
    $ent_ar = $message->findDisplayEntity(array(), array('text/plain'));
}
$cnt = count($ent_ar);
for ($i = 0; $i < $cnt; $i++) {
   $messagebody .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox);
   if ($i != $cnt-1) {
       $messagebody .= '<hr noshade size=1>';
   }
}

displayPageHeader($color, $mailbox);
formatMenuBar($mailbox, $passed_id, $passed_ent_id, $message, $mbx_response);
formatEnvheader($mailbox, $passed_id, $passed_ent_id, $message, $color, $FirstTimeSee);
echo '<table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">';
echo '  <tr><td>';
echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[9].'">';
echo '      <tr><td>';
echo '        <table width="100%" cellpadding="3" cellspacing="0" align="center" border="0">';
echo '          <tr bgcolor="'.$color[4].'"><td>';
// echo '            <table cellpadding="1" cellspacing="5" align="left" border="0">';
echo html_tag( 'table' ,'' , 'left', '', 'cellpadding="1" cellspacing="5" border="0"' );
echo '              <tr>' . html_tag( 'td', '<br>'. $messagebody."\n", 'left')
                        . '</tr>';
echo '            </table>';
echo '          </td></tr>';      
echo '        </table></td></tr>';
echo '    </table>';
echo '  </td></tr>';

echo '<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'.
          $color[4].'"></TD></TR>'."\n";

$attachmentsdisplay = formatAttachments($message,$ent_ar,$mailbox, $passed_id);
if ($attachmentsdisplay) {
   echo '  </table>';
   echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
   echo '     <tr><td>';
   echo '       <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0" bgcolor="'.$color[4].'">';
   echo '        <tr>' . html_tag( 'td', '', 'left', $color[9] );              
   echo '           <b>' . _("Attachments") . ':</b>';
   echo '        </td></tr>';
   echo '        <tr><td>';
   echo '          <table width="100%" cellpadding="2" cellspacing="2" align="center"'.' border="0" bgcolor="'.$color[0].'"><tr><td>';
   echo              $attachmentsdisplay;
   echo '          </td></tr></table>';
   echo '       </td></tr></table>';
   echo '  </td></tr>';
   echo '<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'.
          $color[4].'"></TD></TR>';
}
echo '</table>';

/* show attached images inline -- if pref'fed so */
if (($attachment_common_show_images) &&
    is_array($attachment_common_show_images_list)) {
    foreach ($attachment_common_show_images_list as $img) {
        $imgurl = SM_PATH . 'src/download.php' .
                '?' .
                'passed_id='     . urlencode($img['passed_id']) .
                '&amp;mailbox='       . urlencode($mailbox) .
                '&amp;ent_id=' . urlencode($img['ent_id']) .
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
/* sessions are written at the end of the script. it's better to register 
   them at the end so we avoid double session_register calls */
sqsession_register($messages,'messages');

?>
</body>
</html>
