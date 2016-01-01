<?php

/**
 * read_body.php
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the read_body page */
define('PAGE_NAME', 'read_body');

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
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/forms.php');
require_once(SM_PATH . 'functions/attachment_common.php');
require_once(SM_PATH . 'functions/compose.php');

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

function html_toggle_href ($mailbox, $passed_id, $passed_ent_id, $message) {
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
       ($type1 == 'alternative' || $type1 == 'mixed' || $type1 == 'related' || $type1=='signed')) {
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
        } else {
            $new_link .= '&amp;show_html_default=1';
        }
        return $new_link;
    }
    return '';
}

function ServerMDNSupport($aFlags) {
    /* escaping $ doesn't work -> \x36 */
    return ( in_array('$mdnsent',$aFlags,true) ||
             in_array('\\*',$aFlags,true) ) ;
}

function SendMDN ( $mailbox, $passed_id, $message, $imapConnection) {
    global $squirrelmail_language, $default_charset, $default_move_to_sent,
           $languages, $useSendmail, $domain, $sent_folder, $username,
           $data_dir;

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
    $rfc822_header->subject = _("Read:") . ' ' . decodeHeader($header->subject,true,false);

    $idents = get_identities();
    $needles = array();
    if ($header->to) {
        foreach ($header->to as $message_to) {
             $needles[] = $message_to->mailbox.'@'.$message_to->host;
        }
    }
    $identity = find_identity($needles);
    $from_addr = build_from_header($identity);
    $reply_to = isset($idents[$identity]['reply_to']) ? $idents[$identity]['reply_to'] : '';
    // FIXME: this must actually be the envelope address of the orginal message,
    // but do we have that information? For now the first identity is our best guess.
    $final_recipient = $idents[0]['email_address'];

    $rfc822_header->from = $rfc822_header->parseAddress($from_addr,true);
    if ($reply_to) {
       $rfc822_header->reply_to = $rfc822_header->parseAddress($reply_to,true);
    }

    // part 1 (RFC2298)
    $senton = getLongDateString( $header->date, $header->date_unparsed );
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
        $mime_header->encoding = '7bit';
    }
    if ($default_charset) {
        $mime_header->parameters['charset'] = $default_charset;
    }
    $part1->mime_header = $mime_header;

    // part2  (RFC2298)
    $original_recipient  = $to;
    $original_message_id = $header->message_id;

    $report = "Reporting-UA : $SERVER_NAME ; SquirrelMail (version " . SM_VERSION . ") \r\n";
    if ($original_recipient != '') {
        $report .= "Original-Recipient : $original_recipient\r\n";
    }
    $report .= "Final-Recipient: rfc822; $final_recipient\r\n" .
              "Original-Message-ID : $original_message_id\r\n" .
              "Disposition: manual-action/MDN-sent-manually; displayed\r\n";

    $part2 = new Message();
    $part2->setBody($report);
    $mime_header = new MessageHeader;
    $mime_header->type0 = 'message';
    $mime_header->type1 = 'disposition-notification';
    $mime_header->encoding = '7bit';
    $part2->mime_header = $mime_header;

    $composeMessage = new Message();
    $composeMessage->rfc822_header = $rfc822_header;
    $composeMessage->addEntity($part1);
    $composeMessage->addEntity($part2);


    if ($useSendmail) {
        require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
        global $sendmail_path, $sendmail_args;
        // Check for outdated configuration
        if (!isset($sendmail_args)) {
            if ($sendmail_path=='/var/qmail/bin/qmail-inject') {
                $sendmail_args = '';
            } else {
                $sendmail_args = '-i -t';
            }
        }
        $deliver = new Deliver_SendMail(array('sendmail_args'=>$sendmail_args));
        $stream = $deliver->initStream($composeMessage,$sendmail_path);
    } else {
        require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
        $deliver = new Deliver_SMTP();
        global $smtpServerAddress, $smtpPort, $pop_before_smtp, $pop_before_smtp_host;
        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
        if (empty($pop_before_smtp_host)) $pop_before_smtp_host = $smtpServerAddress;
        get_smtp_user($user, $pass);
        $stream = $deliver->initStream($composeMessage,$domain,0,
                                       $smtpServerAddress, $smtpPort, $user, $pass, $authPop, $pop_before_smtp_host);
    }
    $success = false;
    if ($stream) {
        $deliver->mail($composeMessage, $stream);
        $success = $deliver->finalizeStream($stream);
    }
    if (!$success) {
        $msg = _("Message not sent.") . "\n" .
            $deliver->dlv_msg;
        if (! empty($deliver->dlv_server_msg)) {
            $msg.= "\n" .
                _("Server replied:") . ' ' . $deliver->dlv_ret_nr . ' ' .
                nl2br(sm_encode_html_special_chars($deliver->dlv_server_msg));
        }
        plain_error_message($msg);
    } else {
        unset ($deliver);

        // move to sent folder
        //
        $move_to_sent = getPref($data_dir,$username,'move_to_sent');
        if (isset($default_move_to_sent) && ($default_move_to_sent != 0)) {
            $svr_allow_sent = true;
        } else {
            $svr_allow_sent = false;
        }

        if (isset($sent_folder) && (($sent_folder != '') || ($sent_folder != 'none'))
                && sqimap_mailbox_exists( $imapConnection, $sent_folder)) {
            $fld_sent = true;
        } else {
            $fld_sent = false;
        }

        if ((isset($move_to_sent) && ($move_to_sent != 0)) || (!isset($move_to_sent))) {
            $lcl_allow_sent = true;
        } else {
            $lcl_allow_sent = false;
        }

        if (($fld_sent && $svr_allow_sent && !$lcl_allow_sent) || ($fld_sent && $lcl_allow_sent)) {
            $save_reply_with_orig=getPref($data_dir,$username,'save_reply_with_orig');
            if ($save_reply_with_orig) {
                $sent_folder = $mailbox;
            }
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imapConnection, 0, 0, $imapConnection, $sent_folder);
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
    global $show_more, $show_more_cc, $show_more_bcc,
           $PHP_SELF, $oTemplate;

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

        $a = array();
        foreach ($recipients as $r) {
            $a[] = array(
                            // note: decodeHeader is htmlsafe by default
                            'Name'  => decodeHeader($r->getAddress(false)),
                            'Email' => sm_encode_html_special_chars($r->getEmail()),
                            'Full'  => decodeHeader($r->getAddress(true))
                        );
        }

        $oTemplate->assign('which_field', $item);
        $oTemplate->assign('recipients', $a);
        $oTemplate->assign('more_less_toggle_href', $url);
        $oTemplate->assign('show_more', $show);

        $string = $oTemplate->fetch('read_recipient_list.tpl');
    }
    return $string;
}

function formatEnvheader($aMailbox, $passed_id, $passed_ent_id, $message,
                         $color, $FirstTimeSee) {
    global $default_use_mdn, $default_use_priority,
           $show_xmailer_default, $mdn_user_support, $PHP_SELF,
           $squirrelmail_language, $oTemplate;

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
    $env[_("Date")] = getLongDateString($header->date, $header->date_unparsed);
    $env[_("To")] = formatRecipientString($header->to, "to");
    $env[_("Cc")] = formatRecipientString($header->cc, "cc");
    $env[_("Bcc")] = formatRecipientString($header->bcc, "bcc");
    if ($default_use_priority) {
        $oTemplate->assign('message_priority', $header->priority);
        $env[_("Priority")] = $oTemplate->fetch('read_message_priority.tpl');
    }
    if ($show_xmailer_default) {
        $oTemplate->assign('xmailer', decodeHeader($header->xmailer));
        $env[_("Mailer")] = $oTemplate->fetch('read_xmailer.tpl');
    }

    // this is used for both mdn and also general use for plugins, etc
    $oTemplate->assign('first_time_reading', $FirstTimeSee);

    if ($default_use_mdn) {
        if ($mdn_user_support) {
            if ($header->dnt) {
                $mdn_url = $PHP_SELF;
                $mdn_url = set_url_var($mdn_url, 'mailbox', urlencode($mailbox));
                $mdn_url = set_url_var($mdn_url, 'passed_id', $passed_id);
                $mdn_url = set_url_var($mdn_url, 'passed_ent_id', $passed_ent_id);
                $mdn_url = set_url_var($mdn_url, 'sendreceipt', 1);

                $oTemplate->assign('read_receipt_sent', $message->is_mdnsent);
                $oTemplate->assign('send_receipt_href', $mdn_url);

                $env[_("Read Receipt")] = $oTemplate->fetch('read_handle_receipt.tpl');
            }
        }
    }

    $statuses = array();
    if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS'])) {
        if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\deleted']) &&
                  $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\deleted'] === true) {
            $statuses[] = _("deleted");
        }
        if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\answered']) &&
                  $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\answered'] === true) {
            $statuses[] = _("answered");
        }
        if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\draft']) &&
                  $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\draft'] === true) {
            $statuses[] = _("draft");
        }
        if (isset($aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\flagged']) &&
                  $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS']['\\flagged'] === true) {
            $statuses[] = _("flagged");
        }
        if ( count($statuses) ) {
            $env[_("Status")] = implode(', ', $statuses);
        }
    }

    $env[_("Options")] = formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color);


    $oTemplate->assign('headers_to_display', $env);

    $oTemplate->display('read_headers.tpl');
}

/**
 * Format message toolbar
 *
 * @param array   $aMailbox      Current mailbox information array
 * @param int     $passed_id     UID of current message
 * @param int     $passed_ent_id Id of entity within message
 * @param object  $message       Current message object
 * @param void    $removedVar    This parameter is no longer used, but remains
 *                               so as not to break this function's prototype
 *                               (OPTIONAL)
 * @param boolean $nav_on_top    When TRUE, the menubar is being constructed
 *                               for use at the top of the page, otherwise it
 *                               will be used for page bottom (OPTIONAL;
 *                               default = TRUE)
 */
function formatMenubar($aMailbox, $passed_id, $passed_ent_id, $message,
                       $removedVar=FALSE, $nav_on_top=TRUE) {

    global $base_uri, $draft_folder, $where, $what, $sort,
           $startMessage, $PHP_SELF, $save_as_draft,
           $enable_forward_as_attachment, $imapConnection, $lastTargetMailbox,
           $delete_prev_next_display, $show_copy_buttons,
           $compose_new_win, $compose_width, $compose_height,
           $oTemplate;

    //FIXME cleanup argument list, use $aMailbox where possible
    $mailbox = $aMailbox['NAME'];

    $urlMailbox = urlencode($mailbox);

    // Create Prev & Next links
    // Handle nested entities first (i.e. Mime Attach parts)
    $prev_href = $next_href = $up_href = $del_href = $del_prev_href = $del_next_href = '';
    $msg_list_href = $search_href = $view_msg_href = '';
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

        if(isset($entities[$passed_ent_id]) && $entities[$passed_ent_id] > 1) {
            $prev_ent_id = $entity_count[$entities[$passed_ent_id] - 1];
            $prev_href = set_url_var($PHP_SELF, 'passed_ent_id', $prev_ent_id);
        }

        if(isset($entities[$passed_ent_id]) && $entities[$passed_ent_id] < $c) {
            $next_ent_id = $entity_count[$entities[$passed_ent_id] + 1];
            $next_href = set_url_var($PHP_SELF, 'passed_ent_id', $next_ent_id);
        }

        $par_ent_id = $message->parent->entity_id;
        if ($par_ent_id) {
            $par_ent_id = substr($par_ent_id,0,-2);
            if ( $par_ent_id != 0 ) {
                $up_href = set_url_var($PHP_SELF, 'passed_ent_id',$par_ent_id);
            }
        }

        $view_msg_href = $url;

    // Prev/Next links for regular messages
    } else if ( true ) { //!(isset($where) && isset($what)) ) {
        $prev = findPreviousMessage($aMailbox['UIDSET'][$what], $passed_id);
        $next = findNextMessage($aMailbox['UIDSET'][$what],$passed_id);

        if ($prev >= 0) {
            $prev_href = $base_uri . 'src/read_body.php?passed_id='.$prev.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   "&amp;where=$where&amp;what=$what" .
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
        }

        if ($next >= 0) {
            $next_href = $base_uri . 'src/read_body.php?passed_id='.$next.
                   '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                   "&amp;where=$where&amp;what=$what" .
                   '&amp;startMessage='.$startMessage.'&amp;show_more=0';
        }

        // Only bother with Delete & Prev and Delete & Next IF
        // top display is enabled.
        if ( $delete_prev_next_display == 1 &&
               in_array('\\deleted', $aMailbox['PERMANENTFLAGS'],true) ) {
            if ($prev >= 0) {
                $del_prev_href = $base_uri . 'src/read_body.php?passed_id='.$prev.
                       '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                       '&amp;startMessage='.$startMessage.'&amp;show_more=0'.
                       "&amp;where=$where&amp;what=$what" .
                       '&amp;delete_id='.$passed_id .
                       '&amp;smtoken='.sm_generate_security_token();
            }

            if ($next >= 0) {
                $del_next_href = $base_uri . 'src/read_body.php?passed_id='.$next.
                       '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
                       '&amp;startMessage='.$startMessage.'&amp;show_more=0'.
                       "&amp;where=$where&amp;what=$what" .
                       '&amp;delete_id='.$passed_id .
                       '&amp;smtoken='.sm_generate_security_token();
            }
        }
    }

    $msg_list_href = get_message_list_uri($aMailbox['NAME'], $startMessage, $what);
    if ($where == 'search.php')
        $search_href = str_replace('read_body.php', 'search.php', $msg_list_href);
    else
        $search_href = '';

    $comp_uri = $base_uri.'src/compose.php' .
                '?passed_id=' . $passed_id .
                '&amp;mailbox=' . $urlMailbox .
                '&amp;startMessage=' . $startMessage .
                 (isset($passed_ent_id) ? '&amp;passed_ent_id='.$passed_ent_id : '');

    // Start form for reply/reply all/forward..
    $target = '';
    $on_click='';
    $method='post';
    $onsubmit='';
    if ($compose_new_win == '1') {
        if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
            $compose_width = '640';
        }
        if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
            $compose_height = '550';
        }
        if ( checkForJavascript() ) {
          $on_click='comp_in_new_form(\''.$comp_uri.'\', this, this.form,'. $compose_width .',' . $compose_height .')';
          $comp_uri = 'javascript:void(0)';
          $method='get';
          $onsubmit = 'return false';
        } else {
          $target = '_blank';
        }
    }

    $oTemplate->assign('nav_on_top', $nav_on_top);

    $oTemplate->assign('prev_href', $prev_href);
    $oTemplate->assign('up_href', $up_href);
    $oTemplate->assign('next_href', $next_href);
    $oTemplate->assign('del_prev_href', $del_prev_href);
    $oTemplate->assign('del_next_href', $del_next_href);
    $oTemplate->assign('view_msg_href', $view_msg_href);

    $oTemplate->assign('message_list_href', $msg_list_href);
    $oTemplate->assign('search_href', $search_href);

    $oTemplate->assign('form_extra', '');
    $oTemplate->assign('form_method', $method);
    $oTemplate->assign('form_target', $target);
    $oTemplate->assign('form_onsubmit', $onsubmit);
    $oTemplate->assign('compose_href', $comp_uri);
    $oTemplate->assign('button_onclick', $on_click);
    $oTemplate->assign('forward_as_attachment_enabled', $enable_forward_as_attachment==1);

    //FIXME: I am surprised these aren't already given to the template; probably needs to be given at a higher level, so I have NO IDEA if this is the right place to do this...  adding them so template can construct its own API calls... we can build those herein too if preferrable
    $oTemplate->assign('mailbox', $aMailbox['NAME']);
    $oTemplate->assign('passed_id', $passed_id);
    $oTemplate->assign('what', $what);

    // If Draft folder - create Resume link
    $resume_draft = $edit_as_new = false;
    if (isDraftMailbox($mailbox) && ($save_as_draft)) {
        $resume_draft = true;
    } else if (handleAsSent($mailbox)) {
        $edit_as_new = true;
    }
    $oTemplate->assign('can_resume_draft', $resume_draft);
    $oTemplate->assign('can_edit_as_new', $edit_as_new);

    $oTemplate->assign('mailboxes', sqimap_mailbox_option_array($imapConnection));
    if (in_array('\\deleted', $aMailbox['PERMANENTFLAGS'],true)) {
        $delete_url = $base_uri . "src/$where";
        $oTemplate->assign('can_be_deleted', true);
        $oTemplate->assign('move_delete_form_action', $base_uri.'src/'.$where);
        $oTemplate->assign('delete_form_extra', addHidden('mailbox', $aMailbox['NAME'])."\n" .
                                                addHidden('msg[0]', $passed_id)."\n" .
                                                addHidden('startMessage', $startMessage)."\n" );
        if (!(isset($passed_ent_id) && $passed_ent_id)) {
            $oTemplate->assign('can_be_moved', true);
            $oTemplate->assign('move_form_extra', addHidden('mailbox', $aMailbox['NAME'])."\n" .
                                                  addHidden('msg[0]', $passed_id)."\n" );
            $oTemplate->assign('last_move_target', isset($lastTargetMailbox) && !empty($lastTargetMailbox) ? $lastTargetMailbox : '');
            $oTemplate->assign('can_be_copied', $show_copy_buttons==1);
        } else {
            $oTemplate->assign('can_be_moved', false);
            $oTemplate->assign('move_form_extra', '');
            $oTemplate->assign('last_move_target', '');
            $oTemplate->assign('can_be_copied', false);
        }
    } else {
        $oTemplate->assign('can_be_deleted', false);
        $oTemplate->assign('move_delete_form_action', '');
        $oTemplate->assign('delete_form_extra', '');
        $oTemplate->assign('can_be_moved', false);
        $oTemplate->assign('move_form_extra', '');
        $oTemplate->assign('last_move_target', '');
        $oTemplate->assign('can_be_copied', false);
    }

    // access keys... only add to the top menubar, because adding
    // them twice makes them less functional (press access key, *then*
    // press <enter> to make it work)
    //
    if ($nav_on_top) {
        global $accesskey_read_msg_reply, $accesskey_read_msg_reply_all,
               $accesskey_read_msg_forward, $accesskey_read_msg_as_attach,
               $accesskey_read_msg_delete, $accesskey_read_msg_bypass_trash,
               $accesskey_read_msg_move, $accesskey_read_msg_move_to,
               $accesskey_read_msg_copy;
    } else {
        $accesskey_read_msg_reply = $accesskey_read_msg_reply_all =
        $accesskey_read_msg_forward = $accesskey_read_msg_as_attach =
        $accesskey_read_msg_delete = $accesskey_read_msg_bypass_trash =
        $accesskey_read_msg_move = $accesskey_read_msg_move_to =
        $accesskey_read_msg_copy = 'NONE';
    }
    $oTemplate->assign('accesskey_read_msg_reply', $accesskey_read_msg_reply);
    $oTemplate->assign('accesskey_read_msg_reply_all', $accesskey_read_msg_reply_all);
    $oTemplate->assign('accesskey_read_msg_forward', $accesskey_read_msg_forward);
    $oTemplate->assign('accesskey_read_msg_as_attach', $accesskey_read_msg_as_attach);
    $oTemplate->assign('accesskey_read_msg_delete', $accesskey_read_msg_delete);
    $oTemplate->assign('accesskey_read_msg_bypass_trash', $accesskey_read_msg_bypass_trash);
    $oTemplate->assign('accesskey_read_msg_move_to', $accesskey_read_msg_move_to);
    $oTemplate->assign('accesskey_read_msg_move', $accesskey_read_msg_move);
    $oTemplate->assign('accesskey_read_msg_copy', $accesskey_read_msg_copy);

    global $null;
    do_hook('read_body_menu', $null);

    if ($nav_on_top) {
        $oTemplate->display('read_menubar_nav.tpl');
        $oTemplate->display('read_menubar_buttons.tpl');
    } else {
        $oTemplate->display('read_menubar_buttons.tpl');
        $oTemplate->display('read_menubar_nav.tpl');
    }

}

function formatToolbar($mailbox, $passed_id, $passed_ent_id, $message, $color) {
    global $base_uri, $where, $what, $show_html_default,
           $oTemplate, $download_href, $PHP_SELF,
           $unsafe_image_toggle_href, $unsafe_image_toggle_text;

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

    $links = array();
    $links[] = array (
                        'URL'   => $url,
                        'Text'  => _("View Full Header")
                     );

    if ( checkForJavaScript() ) { 
        $links[] = array (
                        'URL'   => 'javascript:printThis();',
                        'Text'  => _("Print"),
                     );
    } else {
        $links[] = array (
                        'URL'   => set_url_var($PHP_SELF, 'print', '1'),
                        'Text'  => _("Print"),
                        'Target' => '_blank'
                     );
    }

    $links[] = array (
                        'URL'   => $download_href,
                        'Text'  => _("Download this as a file")
                     );
    $toggle = html_toggle_href($mailbox, $passed_id, $passed_ent_id, $message);
    if (!empty($toggle)) {
        $links[] = array (
                            'URL'   => $toggle,
                            'Text'  => $show_html_default==1 ? _("View as plain text") : _("View as HTML")
                         );
    }
    if (!empty($unsafe_image_toggle_href)) {
        $links[] = array (
                            'URL'   => $unsafe_image_toggle_href,
                            'Text'  => $unsafe_image_toggle_text
                         );
    }

    do_hook('read_body_header_right', $links);

    $oTemplate->assign('links', $links);

    return $oTemplate->fetch('read_toolbar.tpl');
}

/***************************/
/*   Main of read_body.php */
/***************************/

/* get the globals we may need */

sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
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
sqgetGlobalVar('passed_id',     $passed_id, SQ_INORDER, NULL, SQ_TYPE_BIGINT);
sqgetGlobalVar('passed_ent_id', $passed_ent_id);
sqgetGlobalVar('mailbox',       $mailbox);

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

$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);
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
}

/**
 * update message seen status and put in cache
 */
$message->is_seen = true;
$aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'] = $message;

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

// gmail does not mark messages as read when retrieving the message body
// even though RFC 3501, section 6.4.5 (FETCH Command) says:
// "The \Seen flag is implicitly set; if this causes the flags to change,
// they SHOULD be included as part of the FETCH responses."
//
if ($imap_server_type == 'gmail') {
    sqimap_toggle_flag($imapConnection, $passed_id, '\\Seen', true, true);
}

/****************************************/
/* Block for handling incoming url vars */
/****************************************/

if (isset($sendreceipt)) {
   if ( !$message->is_mdnsent ) {
      $supportMDN = ServerMDNSupport($aMailbox["PERMANENTFLAGS"]);
      if ( SendMDN( $mailbox, $passed_id, $message, $imapConnection ) > 0 && $supportMDN ) {
         ToggleMDNflag( true, $imapConnection, $mailbox, $passed_id);
         $message->is_mdnsent = true;
         $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'] = $message;
      }
   }
}
/***********************************************/
/* End of block for handling incoming url vars */
/***********************************************/

$oTemplate->assign('aAttribs', array('class' => 'entity_sep'));
$hr = $oTemplate->fetch('horizontal_rule.tpl');
$messagebody = '';
do_hook('read_body_top', $null);
if ($show_html_default == 1) {
    $ent_ar = $message->findDisplayEntity(array());
} else {
    $ent_ar = $message->findDisplayEntity(array(), array('text/plain'));
}
$cnt = count($ent_ar);
for ($i = 0; $i < $cnt; $i++) {
   $messagebody .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox);
   if ($i != $cnt-1) {
       $messagebody .= $hr;
   }
}

/**
 * Write mailbox with updated seen flag information back to cache.
 */
$mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;
sqsession_register($mailbox_cache,'mailbox_cache');
$_SESSION['mailbox_cache'] = $mailbox_cache;

// message list URI is used in page header when on read_body
$oTemplate->assign('message_list_href', get_message_list_uri($aMailbox['NAME'], $startMessage, $what));

displayPageHeader($color, $mailbox,'','');

/* this is the non-javascript version of printer friendly */
if ( sqgetGlobalVar('print', $print, SQ_GET) ) {
    $oTemplate->display('read_message_print.tpl');
} else {
    formatMenubar($aMailbox, $passed_id, $passed_ent_id, $message,false);
}
formatEnvheader($aMailbox, $passed_id, $passed_ent_id, $message, $color, $FirstTimeSee);

$oTemplate->assign('message_body', $messagebody);
$oTemplate->display('read_message_body.tpl');

formatAttachments($message,$ent_ar,$mailbox, $passed_id);

/* show attached images inline -- if pref'fed so */
if ($attachment_common_show_images && is_array($attachment_common_show_images_list)) {
    $images = array();
    foreach ($attachment_common_show_images_list as $img) {
        $imgurl = SM_PATH . 'src/download.php' .
                '?' .
                'passed_id='     . urlencode($img['passed_id']) .
                '&amp;mailbox='       . urlencode($mailbox) .
                '&amp;ent_id=' . urlencode($img['ent_id']) .
                '&amp;absolute_dl=true';
        $a = array();
        $a['Name'] = $img['name'];
        $a['DisplayURL'] = $imgurl;
        $a['DownloadURL'] = $img['download_href'];
        $images[] = $a;
    }

    $oTemplate->assign('images', $images);
    $oTemplate->display('read_display_images_inline.tpl');
}

formatMenubar($aMailbox, $passed_id, $passed_ent_id, $message, false, FALSE);

do_hook('read_body_bottom', $null);
sqimap_logout($imapConnection);
$oTemplate->display('footer.tpl');
