<?php

/**
 * read_body.php
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */


require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/imap_asearch.php'); // => move to mailbox_display
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/url_parser.php');
require_once(SM_PATH . 'functions/identity.php');
require_once(SM_PATH . 'functions/arrays.php');
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/forms.php');
require_once(SM_PATH . 'functions/attachment_common.php');

/**
 * Given an IMAP message id number, this will look it up in the cached
 * and sorted msgs array and return the index of the next message
 *
 * @param int $passed_id The current message UID
 * @return the index of the next valid message from the array
 */
function findNextMessage($uidset,$passed_id='backwards') {
    if (!is_array($uidset)) {
        return -1;
    }
    if ($passed_id=='backwards' || !is_array($uidset)) { // check for backwards compattibilty gpg plugin
        $passed_id = $uidset;
    }
    $result = sqm_array_get_value_by_offset($uidset,$passed_id,1);
    if ($result === false) {
        return -1;
    } else {
        return $result;
    }
}

/**
 * Given an IMAP message id number, this will look it up in the cached
 * and sorted msgs array and return the index of the previous message
 *
 * @param int $passed_id The current message UID
 * @return the index of the next valid message from the array
 */

function findPreviousMessage($uidset, $passed_id) {
    if (!is_array($uidset)) {
        return -1;
    }
    $result = sqm_array_get_value_by_offset($uidset,$passed_id,-1);
    if ($result === false) {
        return -1;
    } else {
        return $result;
    }
}

/**
 * Displays a link to a page where the message is displayed more
 * "printer friendly".
 * @param string $mailbox Name of current mailbox
 * @param int $passed_id
 */
function printer_friendly_link($mailbox, $passed_id, $passed_ent_id) {
    global $javascript_on, $show_html_default;

    /* hackydiehack */
    if( !sqgetGlobalVar('view_unsafe_images', $view_unsafe_images, SQ_GET) ) {
        $view_unsafe_images = false;
    } else {
        $view_unsafe_images = true;
    }
    $params = '?passed_ent_id=' . urlencode($passed_ent_id) .
              '&mailbox=' . urlencode($mailbox) .
              '&passed_id=' . urlencode($passed_id) .
              '&view_unsafe_images='. (bool) $view_unsafe_images .
              '&show_html_default=' . $show_html_default;

    $print_text = _("View Printable Version");

    $result = '';
    /* Output the link. */
    if ($javascript_on) {
        $result = '<script type="text/javascript">' . "\n" .
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

function view_as_html_link($mailbox, $passed_id, $passed_ent_id, $message) {
    global $base_uri, $show_html_default;

    $has_html = false;
    if ($message->header->type0 == 'message' && $message->header->type1 == 'rfc822') {
        $type0 = $message->rfc822_header->content_type->type0;
        $type1 = $message->rfc822_header->content_type->type1;
    } else {
        $type0 = $message->header->type0;
        $type1 = $message->header->type1;
    }
    if($type0 == 'multipart' &&
       ($type1 == 'alternative' || $type1 == 'mixed' || $type1 == 'related')) {
        if ($message->findDisplayEntity(array(), array('text/html'), true)) {
            $has_html = true;
        }
    }
    /*
     * Normal single part message so check its type.
     */
    else {
        if($type0 == 'text' && $type1 == 'html') {
            $has_html = true;
        }
    }
    if($has_html == true) {
        $vars = array('passed_ent_id', 'show_more', 'show_more_cc', 'override_type0', 'override_type1', 'startMessage','where', 'what');

        $new_link = $base_uri . 'src/read_body.php?passed_id=' . urlencode($passed_id) .
                    '&amp;passed_ent_id=' . urlencode($passed_ent_id) .
                    '&amp;mailbox=' . urlencode($mailbox);
        foreach($vars as $var) {
            if(sqgetGlobalVar($var, $temp)) {
                $new_link .= '&amp;' . $var . '=' . urlencode($temp);
            }
        }

        if($show_html_default == 1) {
            $new_link .= '&amp;show_html_default=0';
            $link      = _("View as plain text");
        } else {
            $new_link .= '&amp;show_html_default=1';
            $link      = _("View as HTML");
        }
        return '&nbsp;|&nbsp<a href="' . $new_link . '">' . $link . '</a>';
    }
    return '';
}

function ServerMDNSupport($aFlags) {
    /* escaping $ doesn't work -> \x36 */
    return ( in_array('$mdnsent',$aFlags,true) ||
             in_array('\\*',$aFlags,true) ) ;
}

function SendMDN ( $mailbox, $passed_id, $sender, $message, $imapConnection) {
    global $username, $attachment_dir, $popuser, $username, $color,
           $version, $squirrelmail_language, $default_charset,
           $languages, $useSendmail, $domain, $sent_folder;

    sqgetGlobalVar('SERVER_NAME', $SERVER_NAME, SQ_SERVER);

    $header = $message->rfc822_header;

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
            "\t" . _("To") . ': ' . decodeHeader($to,false,false) . "\r\n" .
            "\t" . _("Subject") . ': ' . decodeHeader($header->subject,false,false) . "\r\n" .
            "\t" . _("Sent") . ': ' . $senton . "\r\n" .
            "\r\n" .
            sprintf( _("Was displayed on %s"), $now );

    $special_encoding = '';
    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_encode')) {
        $body = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_encode', $body);
        if (strtolower($default_charset) == 'iso-2022-jp') {
            if (mb_detect_encoding($body) == 'ASCII') {
                $special_encoding = '8bit';
            } else {
                $body = mb_convert_encoding($body, 'JIS');
                $special_encoding = '7bit';
            }
        }
    } elseif (sq_is8bit($body)) {
        $special_encoding = '8bit';
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
        global $smtpServerAddress, $smtpPort, $pop_before_smtp;
        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
        get_smtp_user($user, $pass);
        $stream = $deliver->initStream($composeMessage,$domain,0,
                                       $smtpServerAddress, $smtpPort, $user, $pass, $authPop);
    }
    $success = false;
    if ($stream) {
        $length  = $deliver->mail($composeMessage, $stream);
        $success = $deliver->finalizeStream($stream);
    }
    if (!$success) {
        $msg = $deliver->dlv_msg;
        if (! empty($deliver->dlv_server_msg)) {
            $msg.= '<br />' .
                _("Server replied:") . ' ' . $deliver->dlv_ret_nr . ' ' .
                nl2br(htmlspecialchars($deliver->dlv_server_msg));
        }
        plain_error_message($msg, $color);
    } else {
        unset ($deliver);
        if (sqimap_mailbox_exists ($imapConnection, $sent_folder)) {
            $sid = sqimap_append ($imapConnection, $sent_folder, $length);
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imapConnection);
            sqimap_append_done ($imapConnection, $sent_folder);
            unset ($imap_deliver);
        }
    }
    return $success;
}

function ToggleMDNflag ($set ,$imapConnection, $mailbox, $passed_id) {
    $sg   =  $set?'+':'-';
    $cmd  = 'STORE ' . $passed_id . ' ' . $sg . 'FLAGS ($MDNSent)';
    $read = sqimap_run_command ($imapConnection, $cmd, true, $response,
                                $readmessage, TRUE);
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
                $string .= '<br />' . $add;
            } else {
                $string = $add;
                if ($cnt > 1) {
                    $string .= '&nbsp;(<a href="'.$url;
                    if ($show) {
                       $string .= '">'._("less").'</a>)';
                    } else {
                       $string .= '">'._("more").'</a>)';
                       break;
                    }
                }
            }
        }
    }
    return $string;
}

function formatEnvheader($aMailbox, $passed_id, $passed_ent_id, $message,
                         $color, $FirstTimeSee) {
    global $default_use_mdn, $default_use_priority,
           $show_xmailer_default, $mdn_user_support, $PHP_SELF, $javascript_on,
           $squirrelmail_language;

    $mailbox = $aMailbox['NAME'];

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
                        $mdn_url = $PHP_SELF;
                        $mdn_url = set_url_var($PHP_SELF, 'mailbox', urlencode($mailbox));
                        $mdn_url = set_url_var($PHP_SELF, 'passed_id', $passed_id);
                        $mdn_url = set_url_var($PHP_SELF, 'passed_ent_id', $passed_ent_id);
                        $mdn_url = set_url_var($PHP_SELF, 'sendreceipt', 1);
                        if ($FirstTimeSee && $javascript_on) {
                            $script  = '<script type="text/javascript">' . "\n";
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

    $s  = '<table width="100%" cellpadding="0" cellspacing="2" border="0"';
    $s .= ' align="center" bgcolor="'.$color[0].'">';
    foreach ($env as $key => $val) {
        if ($val) {
            $s .= '<tr>';
            $s .= html_tag('td', '<b>' . $key . ':&nbsp;&nbsp;</b>', 'right', '', 'valign="top" width="20%"') . "\n";
            $s .= html_tag('td', $val, 'left', '', 'valign="top" width="80%"') . "\n";
            $s .= '</tr>';
        }
    }
    echo '<table bgcolor="'.$color[9].'" width="100%" cellpadding="1"'.
         ' cellspacing="0" border="0" align="center">'."\n";
    echo '<tr><td height="5" colspan="2" bgcolor="'.
          $color[4].'"></td></tr><tr><td align="center">'."\n";
    echo $s;
    do_hook('read_body_header');
    formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color);
    echo '</table>';
    echo '</td></tr><tr><td height="5" colspan="2" bgcolor="'.$color[4].'"></td></tr>'."\n";
    echo '</table>';
}

/**
 * Format message toolbar
 *
 * @param string $mailbox Name of current mailbox
 * @param int $passed_id UID of current message
 * @param int $passed_ent_id Id of entity within message
 * @param object $message Current message object
 * @param object $mbx_response
 */
function formatMenubar($aMailbox, $passed_id, $passed_ent_id, $message, $removedVar, $nav_on_top = TRUE) {
    global $base_uri, $draft_folder, $where, $what, $color, $sort,
           $startMessage, $PHP_SELF, $save_as_draft,
           $enable_forward_as_attachment, $imapConnection, $lastTargetMailbox,
           $username, $delete_prev_next_display, $show_copy_buttons,
           $compose_new_win, $javascript_on, $compose_width, $compose_height;

    //FIXME cleanup argument list, use $aMailbox where possible
    $mailbox = $aMailbox['NAME'];

    $topbar_delimiter = '&nbsp;|&nbsp;';
    $double_delimiter = '&nbsp;&nbsp;&nbsp;&nbsp;';
    $urlMailbox = urlencode($mailbox);

    $msgs_url = $base_uri . 'src/';

    // BEGIN NAV ROW - PREV/NEXT, DEL PREV/NEXT, LINKS TO INDEX, etc.
    $nav_row = '<tr><td align="left" colspan="2" style="border: 1px solid '.$color[9].';"><small>';

    // Create Prev & Next links
    // Handle nested entities first (i.e. Mime Attach parts)
    if (isset($passed_ent_id) && $passed_ent_id) {
        // code for navigating through attached message/rfc822 messages
        $url = set_url_var($PHP_SELF, 'passed_ent_id',0);
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
        if(isset($entities[$passed_ent_id]) && $entities[$passed_ent_id] > 1) {
            $prev_ent_id = $entity_count[$entities[$passed_ent_id] - 1];
            $prev_link   = '<a href="'
                         . set_url_var($PHP_SELF, 'passed_ent_id', $prev_ent_id)
                         . '">' . $prev_link . '</a>';
        }

        $next_link = _("Next");
        if(isset($entities[$passed_ent_id]) && $entities[$passed_ent_id] < $c) {
            $next_ent_id = $entity_count[$entities[$passed_ent_id] + 1];
            $next_link   = '<a href="'
                         . set_url_var($PHP_SELF, 'passed_ent_id', $next_ent_id)
                         . '">' . $next_link . '</a>';
        }

        $par_ent_id = $message->parent->entity_id;
        $up_link = '';
        if ($par_ent_id) {
            $par_ent_id = substr($par_ent_id,0,-2);
            if ( $par_ent_id != 0 ) {
                $up_link = $topbar_delimiter;
                $url = set_url_var($PHP_SELF, 'passed_ent_id',$par_ent_id);
                $up_link .= '<a href="'.$url.'">'._("Up").'</a>';
            }
        }

        $nav_row .= $prev_link . $up_link . $topbar_delimiter . $next_link;
        $nav_row .= $double_delimiter . '[<a href="'.$url.'">'._("View Message").'</a>]';

    // Prev/Next links for regular messages
    } else if ( true ) { //!(isset($where) && isset($what)) ) {
        $prev = findPreviousMessage($aMailbox['UIDSET'][$what], $passed_id);
        $next = findNextMessage($aMailbox['UIDSET'][$what],$passed_id);

        $prev_link = _("Previous");
        if ($prev >= 0) {
            $uri = $base_uri . 'src/read_body.php?passed_id='.$prev.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   "&amp;where=$where&amp;what=$what" .
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
            $prev_link = '<a href="'.$uri.'">'.$prev_link.'</a>';
        }

        $next_link = _("Next");
        if ($next >= 0) {
            $uri = $base_uri . 'src/read_body.php?passed_id='.$next.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   "&amp;where=$where&amp;what=$what" .
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
            $next_link = '<a href="'.$uri.'">'.$next_link.'</a>';
        }

        // Only bother with Delete & Prev and Delete & Next IF
        // top display is enabled.
        if ( $delete_prev_next_display == 1 &&
               in_array('\\deleted', $aMailbox['PERMANENTFLAGS'],true) ) {
            $del_prev_link = _("Delete &amp; Prev");
            if ($prev >= 0) {
                $uri = $base_uri . 'src/read_body.php?passed_id='.$prev.
                       '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                       '&amp;startMessage='.$startMessage.'&amp;show_more=0'.
                       "&amp;where=$where&amp;what=$what" .
                       '&amp;delete_id='.$passed_id;
                $del_prev_link = '<a href="'.$uri.'">'.$del_prev_link.'</a>';
            }

            $del_next_link = _("Delete &amp; Next");
            if ($next >= 0) {
                $uri = $base_uri . 'src/read_body.php?passed_id='.$next.
                       '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                       '&amp;startMessage='.$startMessage.'&amp;show_more=0'.
                       "&amp;where=$where&amp;what=$what" .
                       '&amp;delete_id='.$passed_id;
                $del_next_link = '<a href="'.$uri.'">'.$del_next_link.'</a>';
            }
        }

        $nav_row .= '['.$prev_link.$topbar_delimiter.$next_link.']';
        if ( isset($del_prev_link) && isset($del_next_link) )
            $nav_row .= $double_delimiter.'['.$del_prev_link.$topbar_delimiter.$del_next_link.']';
    }

    // Start with Search Results or Message List link.
    $msgs_url .= "$where?where=read_body.php&amp;what=$what&amp;mailbox=" . $urlMailbox.
                 "&amp;startMessage=$startMessage";
    if ($where == 'search.php') {
        $msgs_str  = _("Search Results");
    } else {
        $msgs_str  = _("Message List");
    }
    $nav_row .= $double_delimiter .
                '[<a href="' . $msgs_url . '">' . $msgs_str . '</a>]';

    $nav_row .= '</small></td></tr>';


    // BEGIN MENU ROW - DELETE/REPLY/FORWARD/MOVE/etc.
    $menu_row = '<tr bgcolor="'.$color[0].'"><td><small>';
    $comp_uri = $base_uri.'src/compose.php' .
                '?passed_id=' . $passed_id .
                '&amp;mailbox=' . $urlMailbox .
                '&amp;startMessage=' . $startMessage .
                 (isset($passed_ent_id) ? '&amp;passed_ent_id='.$passed_ent_id : '');

    // Start form for reply/reply all/forward..
    $target = '';
    $on_click='';
    $method='method="post" ';
    $onsubmit='';
    if ($compose_new_win == '1') {
        if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
            $compose_width = '640';
        }
        if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
            $compose_height = '550';
        }
        if ( $javascript_on ) {
          $on_click=' onclick="comp_in_new_form(\''.$comp_uri.'\', this, this.form,'. $compose_width .',' . $compose_height .')"';
          $comp_uri = 'javascript:void(0)';
          $method='method="get" ';
          $onsubmit = 'onsubmit="return false" ';
        } else {
          $target = 'target="_blank"';
        }
    }

    $menu_row .= "\n".'<form name="composeForm" action="'.$comp_uri.'" '
              . $method.$target.$onsubmit.' style="display: inline">'."\n";

    // If Draft folder - create Resume link
    if (($mailbox == $draft_folder) && ($save_as_draft)) {
        $new_button = 'smaction_draft';
        $comp_alt_string = _("Resume Draft");
    } else if (handleAsSent($mailbox)) {
    // If in Sent folder, edit as new
        $new_button = 'smaction_edit_new';
        $comp_alt_string = _("Edit Message as New");
    }
    // Show Alt URI for Draft/Sent
    if (isset($comp_alt_string))
        $menu_row .= getButton('submit', $new_button, $comp_alt_string, $on_click) . "\n";

    $menu_row .= getButton('submit', 'smaction_reply', _("Reply"), $on_click) . "\n";
    $menu_row .= getButton('submit', 'smaction_reply_all', _("Reply All"), $on_click) ."\n";
    $menu_row .= getButton('submit', 'smaction_forward', _("Forward"), $on_click);
    if ($enable_forward_as_attachment)
        $menu_row .= '<input type="checkbox" name="smaction_attache" />' . _("As Attachment") .'&nbsp;&nbsp;'."\n";

    $menu_row .= '</form>&nbsp;';

    if ( in_array('\\deleted', $aMailbox['PERMANENTFLAGS'],true) ) {
    // Form for deletion. Form is handled by the originating display in $where. This is right_main.php or search.php
        $delete_url = $base_uri . "src/$where";
        $menu_row .= '<form name="deleteMessageForm" action="'.$delete_url.'" method="post" style="display: inline">';

        if (!(isset($passed_ent_id) && $passed_ent_id)) {
            $menu_row .= addHidden('mailbox', $aMailbox['NAME']);
            $menu_row .= addHidden('msg[0]', $passed_id);
            $menu_row .= addHidden('startMessage', $startMessage);
            $menu_row .= getButton('submit', 'delete', _("Delete"));
            $menu_row .= '<input type="checkbox" name="bypass_trash" />' . _("Bypass Trash");
        } else {
            $menu_row .= getButton('submit', 'delete', _("Delete"), '', FALSE) . "\n"; // delete button is disabled
        }

        $menu_row .= '</form>';
    }

    // Add top move link
    $menu_row .= '</small></td><td align="right">';
    if ( !(isset($passed_ent_id) && $passed_ent_id) &&
        in_array('\\deleted', $aMailbox['PERMANENTFLAGS'],true) ) {

        $menu_row .= '<form name="moveMessageForm" action="'.$base_uri.'src/'.$where.'?'.'" method="post" style="display: inline">'.
              '<small>'.

          addHidden('mailbox',$aMailbox['NAME']) .
          addHidden('msg[0]', $passed_id) . _("Move to:") .
              '<select name="targetMailbox" style="padding: 0px; margin: 0px">';

        if (isset($lastTargetMailbox) && !empty($lastTargetMailbox)) {
            $menu_row .= sqimap_mailbox_option_list($imapConnection, array(strtolower($lastTargetMailbox)));
        } else {
            $menu_row .= sqimap_mailbox_option_list($imapConnection);
        }
        $menu_row .= '</select> ';

        $menu_row .= getButton('submit', 'moveButton',_("Move")) . "\n";

        // Add msg copy button
        if ($show_copy_buttons) {
            $menu_row .= getButton('submit', 'copyButton', _("Copy"));
        }

        $menu_row .= '</form>';
    }
    $menu_row .= '</td></tr>';

    // echo rows, with hooks
    $ret = do_hook_function('read_body_menu_top', array($nav_row, $menu_row));
    if (is_array($ret)) {
        if (isset($ret[0]) && !empty($ret[0])) {
            $nav_row = $ret[0];
        }
        if (isset($ret[1]) && !empty($ret[1])) {
            $menu_row = $ret[1];
        }
    }
    echo '<table width="100%" cellpadding="3" cellspacing="0" align="center" border="0">';
    echo $nav_on_top ? $nav_row . $menu_row : $menu_row . $nav_row;
    echo '</table>'."\n";
    do_hook('read_body_menu_bottom');
}

function formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color) {
    global $base_uri, $where, $what, $download_and_unsafe_link;

    $urlMailbox = urlencode($mailbox);
    $urlPassed_id = urlencode($passed_id);
    $urlPassed_ent_id = urlencode($passed_ent_id);

    $query_string = 'mailbox=' . $urlMailbox . '&amp;passed_id=' . $urlPassed_id . '&amp;passed_ent_id=' . $urlPassed_ent_id;

    if (!empty($where)) {
        $query_string .= '&amp;where=' . urlencode($where);
    }

    if (!empty($what)) {
        $query_string .= '&amp;what=' . urlencode($what);
    }

    $url = $base_uri.'src/view_header.php?'.$query_string;

    $s  = "<tr>\n" .
          html_tag( 'td', '', 'right', '', 'valign="middle" width="20%"' ) . '<b>' . _("Options") . ":&nbsp;&nbsp;</b></td>\n" .
          html_tag( 'td', '', 'left', '', 'valign="middle" width="80%"' ) . '<small>' .
          '<a href="'.$url.'">'._("View Full Header").'</a>';

    /* Output the printer friendly link if we are in subtle mode. */
    $s .= '&nbsp;|&nbsp;' .
          printer_friendly_link($mailbox, $passed_id, $passed_ent_id);
    echo $s;
    echo view_as_html_link($mailbox, $passed_id, $passed_ent_id, $message);

    /* Output the download and/or unsafe images link/-s, if any. */
    if ($download_and_unsafe_link) {
	echo $download_and_unsafe_link;
    }

    do_hook("read_body_header_right");
    $s = "</small></td>\n" .
         "</tr>\n";
    echo $s;

}

/**
 * Creates button
 *
 * @deprecated see form functions available in 1.5.1 and 1.4.3.
 * @param string $type
 * @param string $name
 * @param string $value
 * @param string $js
 * @param bool $enabled
 */
function getButton($type, $name, $value, $js = '', $enabled = TRUE) {
    $disabled = ( $enabled ? '' : 'disabled ' );
    $js = ( $js ? $js.' ' : '' );
    return '<input '.$disabled.$js.
            'type="'.$type.
            '" name="'.$name.
            '" value="'.$value .
            '" style="padding: 0px; margin: 0px" />';
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
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);
if (!sqgetGlobalVar('messages', $messages, SQ_SESSION) ) {
    $messages = array();
}
sqgetGlobalVar('delayed_errors',  $delayed_errors,  SQ_SESSION);
if (is_array($delayed_errors)) {
    $oErrorHandler->AssignDelayedErrors($delayed_errors);
    sqsession_unregister("delayed_errors");
}
/** GET VARS */
sqgetGlobalVar('sendreceipt',   $sendreceipt,   SQ_GET);
if (!sqgetGlobalVar('where',         $where,         SQ_GET) ) {
    $where = 'right_main.php';
}
/*
 * Used as entry key to the list of uid's cached in the mailbox cache
 * we use the cached uid's to get the next and prev  message.
 */
if (!sqgetGlobalVar('what',          $what,          SQ_GET) ){
    $what = 0;
}
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

if ( sqgetGlobalVar('account', $temp,  SQ_GET) ) {
    $iAccount = (int) $temp;
} else {
    $iAccount = 0;
}

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
} else {
    $startMessage = 1;
}
if(sqgetGlobalVar('show_html_default', $temp)) {
    $show_html_default = (int) $temp;
}

if(sqgetGlobalVar('view_unsafe_images', $temp)) {
    $view_unsafe_images = (int) $temp;
    if($view_unsafe_images == 1) {
        $show_html_default = 1;
    }
} else {
    $view_unsafe_images = 0;
}
/**
 * Retrieve mailbox cache
 */
sqgetGlobalVar('mailbox_cache',$mailbox_cache,SQ_SESSION);

/* end of get globals */
global $sqimap_capabilities, $lastTargetMailbox;

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$aMailbox = sqm_api_mailbox_select($imapConnection, $iAccount, $mailbox,array('setindex' => $what, 'offset' => $startMessage),array());


/**
 Start code to set the columns to fetch in case of hitting the next/prev link
 The reason for this is the fact that the cache can be invalidated which means that the headers
 to fetch aren't there anymore. Before they got calculated when the messagelist was shown.

 Todo, better central handling of setting the mailbox options so we do not need to do the stuff below
*/

/**
 * Replace From => To  in case it concerns a draft or sent folder
 */
$aColumns = array();
if (($mailbox == $sent_folder || $mailbox == $draft_folder) &&
    !in_array(SQM_COL_TO,$index_order)) {
    $aNewOrder = array(); // nice var name ;)
    foreach($index_order as $iCol) {
        if ($iCol == SQM_COL_FROM) {
            $iCol = SQM_COL_TO;
        }
        $aColumns[$iCol] = array();
   }
} else {
   foreach ($index_order as $iCol) {
       $aColumns[$iCol] = array();
   }
}

$aProps = array(
    'columns' => $aColumns, // columns bound settings
    'config'  => array(
                        'highlight_list'        => $message_highlight_list, // row highlighting rules
                        'trash_folder'          => $trash_folder,
                        'sent_folder'           => $sent_folder,
                        'draft_folder'          => $draft_folder));

calcFetchColumns($aMailbox,$aProps);

/**
 End code to set the columns to fetch in case of hitting the next/prev link
*/



/**
 * Check if cache is still valid, $what contains the key
 * which gives us acces to the array with uid's. At this moment
 * 0 is used for a normal message list and search uses 1 as key. This can be
 * changed / extended in the future.
 * If on a select of a mailbox we detect that the cache should be invalidated due to
 * the delete of messages or due to new messages we empty the list with uid's and
 * that's what we detect below.
 */
if (!is_array($aMailbox['UIDSET'][$what])) {
    fetchMessageHeaders($imapConnection, $aMailbox);
}

$iSetIndex = $aMailbox['SETINDEX'];
$aMailbox['CURRENT_MSG'][$iSetIndex] = $passed_id;

/**
 * Update the seen state
 * and ignore in_array('\\seen',$aMailbox['PERMANENTFLAGS'],true)
 */
if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS'])) {
    $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\seen'] = true;
}

/**
 * Process Delete from delete-move-next
 * but only if delete_id was set
 */
if ( sqgetGlobalVar('delete_id', $delete_id, SQ_GET) ) {
    handleMessageListForm($imapConnection,$aMailbox,$sButton='setDeleted', array($delete_id));
}

/**
 * $message contains all information about the message
 * including header and body
 */

if (isset($aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'])) {
    $message = $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'];
    $FirstTimeSee = !$message->is_seen;
} else {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
    $FirstTimeSee = !$message->is_seen;
    $message->is_seen = true;
    $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'] = $message;
}
if (isset($passed_ent_id) && $passed_ent_id) {
    $message = $message->getEntity($passed_ent_id);
    if ($message->type0 != 'message'  && $message->type1 != 'rfc822') {
        $message = $message->parent;
    }
    $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[$passed_ent_id.HEADER]", true, $response, $msg, TRUE);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader($read);
    $message->rfc822_header = $rfc822_header;
} else if ($message->type0 == 'message'  && $message->type1 == 'rfc822' && isset($message->entities[0])) {
    $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[1.HEADER]", true, $response, $msg, TRUE);
    $rfc822_header = new Rfc822Header();
    $rfc822_header->parseHeader($read);
    $message->rfc822_header = $rfc822_header;
} else {
    $passed_ent_id = 0;
}
$header = $message->header;


/****************************************/
/* Block for handling incoming url vars */
/****************************************/

if (isset($sendreceipt)) {
   if ( !$message->is_mdnsent ) {
      $final_recipient = '';
      if ((isset($identity)) && ($identity != 0)) //Main identity
         $final_recipient = trim(getPref($data_dir, $username, 'email_address' . $identity, '' ));
      if ($final_recipient == '' )
         $final_recipient = trim(getPref($data_dir, $username, 'email_address', '' ));
      $supportMDN = ServerMDNSupport($aMailbox["PERMANENTFLAGS"]);
      if ( SendMDN( $mailbox, $passed_id, $final_recipient, $message, $imapConnection ) > 0 && $supportMDN ) {
         ToggleMDNflag( true, $imapConnection, $mailbox, $passed_id);
         $message->is_mdnsent = true;
         $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'] = $message;
      }
   }
}
/***********************************************/
/* End of block for handling incoming url vars */
/***********************************************/

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
       $messagebody .= '<hr style="height: 1px;" />';
   }
}

/**
 * Write mailbox with updated seen flag information back to cache.
 */
$mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;
sqsession_register($mailbox_cache,'mailbox_cache');
$_SESSION['mailbox_cache'] = $mailbox_cache;
$oTemplate->display('footer.tpl');

displayPageHeader($color, $mailbox,'','');
formatMenuBar($aMailbox, $passed_id, $passed_ent_id, $message,false);
formatEnvheader($aMailbox, $passed_id, $passed_ent_id, $message, $color, $FirstTimeSee);
echo '<table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">';
echo '  <tr><td>';
echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[9].'">';
echo '      <tr><td>';
echo '        <table width="100%" cellpadding="3" cellspacing="0" align="center" border="0">';
echo '          <tr bgcolor="'.$color[4].'"><td>';
// echo '            <table cellpadding="1" cellspacing="5" align="left" border="0">';
echo html_tag( 'table' ,'' , 'left', '', 'width="100%" cellpadding="1" cellspacing="5" border="0"' );
echo '              <tr>' . html_tag( 'td', '<br />'. $messagebody."\n", 'left')
                        . '</tr>';
echo '            </table>';
echo '          </td></tr>';
echo '        </table></td></tr>';
echo '    </table>';
echo '  </td></tr>';

echo '<tr><td height="5" colspan="2" bgcolor="'.
          $color[4].'"></td></tr>'."\n";

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
   echo '<tr><td height="5" colspan="2" bgcolor="'.
          $color[4].'"></td></tr>';
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
                        html_tag( 'td', '<img src="' . $imgurl . '" />' ."\n", 'left'
                        )
                    ) ,
        'center', '', 'cellspacing="0" border="0" cellpadding="2"');
    }
}

formatMenuBar($aMailbox, $passed_id, $passed_ent_id, $message, false, FALSE);

do_hook('read_body_bottom');
sqimap_logout($imapConnection);

?>
