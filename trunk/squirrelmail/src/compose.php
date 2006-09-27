<?php
/**
 * compose.php
 *
 * This code sends a mail.
 *
 * There are 4 modes of operation:
 *    - Start new mail
 *    - Add an attachment
 *    - Send mail
 *    - Save As Draft
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
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'class/deliver/Deliver.class.php');
require_once(SM_PATH . 'functions/addressbook.php');
require_once(SM_PATH . 'functions/forms.php');
require_once(SM_PATH . 'functions/identity.php');

/* --------------------- Get globals ------------------------------------- */

/** SESSION VARS */
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);

sqgetGlobalVar('composesession',    $composesession,    SQ_SESSION);
sqgetGlobalVar('compose_messages',  $compose_messages,  SQ_SESSION);
sqgetGlobalVar('delayed_errors',  $delayed_errors,  SQ_SESSION);

// Turn on delayed error handling in case we wind up redirecting below
$oErrorHandler->setDelayedErrors(true);

/** SESSION/POST/GET VARS */
sqgetGlobalVar('session',$session);
sqgetGlobalVar('mailbox',$mailbox);
if(!sqgetGlobalVar('identity',$identity)) {
    $identity=0;
}
sqgetGlobalVar('send_to',$send_to);
sqgetGlobalVar('send_to_cc',$send_to_cc);
sqgetGlobalVar('send_to_bcc',$send_to_bcc);
sqgetGlobalVar('subject',$subject);
sqgetGlobalVar('body',$body);
sqgetGlobalVar('mailprio',$mailprio);
sqgetGlobalVar('request_mdn',$request_mdn);
sqgetGlobalVar('request_dr',$request_dr);
sqgetGlobalVar('html_addr_search',$html_addr_search);
sqgetGlobalVar('mail_sent',$mail_sent);
sqgetGlobalVar('passed_id',$passed_id);
sqgetGlobalVar('passed_ent_id',$passed_ent_id);
sqgetGlobalVar('send',$send);

sqgetGlobalVar('attach',$attach);

sqgetGlobalVar('draft',$draft);
sqgetGlobalVar('draft_id',$draft_id);
sqgetGlobalVar('ent_num',$ent_num);
sqgetGlobalVar('saved_draft',$saved_draft);
sqgetGlobalVar('delete_draft',$delete_draft);
if ( sqgetGlobalVar('startMessage',$startMessage) ) {
    $startMessage = (int)$startMessage;
} else {
    $startMessage = 1;
}


/** POST VARS */
sqgetGlobalVar('sigappend',             $sigappend,                 SQ_POST);
sqgetGlobalVar('from_htmladdr_search',  $from_htmladdr_search,      SQ_POST);
sqgetGlobalVar('addr_search_done',      $html_addr_search_done,     SQ_POST);
sqgetGlobalVar('addr_search_cancel',    $html_addr_search_cancel,   SQ_POST);
sqgetGlobalVar('send_to_search',        $send_to_search,            SQ_POST);
sqgetGlobalVar('do_delete',             $do_delete,                 SQ_POST);
sqgetGlobalVar('delete',                $delete,                    SQ_POST);
sqgetGlobalVar('restoremessages',       $restoremessages,           SQ_POST);
if ( sqgetGlobalVar('return', $temp, SQ_POST) ) {
    $html_addr_search_done = 'Use Addresses';
}

/** GET VARS */
sqgetGlobalVar('attachedmessages', $attachedmessages, SQ_GET);
if ( sqgetGlobalVar('account', $temp,  SQ_GET) ) {
    $iAccount = (int) $temp;
} else {
    $iAccount = 0;
}


/** get smaction */
if ( !sqgetGlobalVar('smaction',$action) )
{
    if ( sqgetGlobalVar('smaction_reply',$tmp) )      $action = 'reply';
    if ( sqgetGlobalVar('smaction_reply_all',$tmp) )  $action = 'reply_all';
    if ( sqgetGlobalVar('smaction_forward',$tmp) )    $action = 'forward';
    if ( sqgetGlobalVar('smaction_attache',$tmp) )    $action = 'forward_as_attachment';
    if ( sqgetGlobalVar('smaction_draft',$tmp) )      $action = 'draft';
    if ( sqgetGlobalVar('smaction_edit_new',$tmp) )   $action = 'edit_as_new';
}

/* Location (For HTTP 1.1 Header("Location: ...") redirects) */
$location = get_location();
/* Identities (fetch only once) */
$idents = get_identities();

/* --------------------- Specific Functions ------------------------------ */

function replyAllString($header) {
    global $include_self_reply_all, $idents;
    $excl_ar = array();
    /**
     * 1) Remove the addresses we'll be sending the message 'to'
     */
    if (isset($header->replyto)) {
        $excl_ar = $header->getAddr_a('replyto');
    }
    /**
     * 2) Remove our identities from the CC list (they still can be in the
     * TO list) only if $include_self_reply_all is turned off
     */
    if (!$include_self_reply_all) {
        foreach($idents as $id) {
            $excl_ar[strtolower(trim($id['email_address']))] = '';
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
            // if personal name contains address separator then surround
            // the personal name with double quotes.
            if (strpos($personal,',') !== false) {
                $personal = '"'.$personal.'"';
            }
            $url_replytoallcc .= ", $personal <$email>";
        } else {
            $url_replytoallcc .= ', '. $email;
        }
    }
    $url_replytoallcc = substr($url_replytoallcc,2);

    return $url_replytoallcc;
}

/**
 * creates top line in reply citations
 *
 * Line style depends on user preferences.
 * $orig_date argument is available only from 1.4.3 and 1.5.1 version.
 * @param object $orig_from From: header object.
 * @param integer $orig_date email's timestamp
 * @return string reply citation
 */
function getReplyCitation($orig_from, $orig_date) {
    global $reply_citation_style, $reply_citation_start, $reply_citation_end;

    if (!is_object($orig_from)) {
        $sOrig_from = '';
    } else {
        $sOrig_from = decodeHeader($orig_from->getAddress(false),false,false,true);
    }

    /* First, return an empty string when no citation style selected. */
    if (($reply_citation_style == '') || ($reply_citation_style == 'none')) {
        return '';
    }

    /* Make sure our final value isn't an empty string. */
    if ($sOrig_from == '') {
        return '';
    }

    /* Otherwise, try to select the desired citation style. */
    switch ($reply_citation_style) {
    case 'author_said':
        /**
         * To translators: %s is for author's name
         */
        $full_reply_citation = sprintf(_("%s wrote:"),$sOrig_from);
        break;
    case 'quote_who':
        $start = '<quote who="';
        $end   = '">';
        $full_reply_citation = $start . $sOrig_from . $end;
        break;
    case 'date_time_author':
        /**
         * To translators:
         *  first %s is for date string, second %s is for author's name. Date uses
         *  formating from "D, F j, Y g:i a" and "D, F j, Y H:i" translations.
         * Example string:
         *  "On Sat, December 24, 2004 23:59, Santa wrote:"
         * If you have to put author's name in front of date string, check comments about
         * argument swapping at http://www.php.net/sprintf
         */
        $full_reply_citation = sprintf(_("On %s, %s wrote:"), getLongDateString($orig_date), $sOrig_from);
        break;
    case 'user-defined':
        $start = $reply_citation_start .
            ($reply_citation_start == '' ? '' : ' ');
        $end   = $reply_citation_end;
        $full_reply_citation = $start . $sOrig_from . $end;
        break;
    default:
        return '';
    }

    /* Add line feed and return the citation string. */
    return ($full_reply_citation . "\n");
}

/**
 * Creates header fields in forwarded email body
 *
 * $default_charset global must be set correctly before you call this function.
 * @param object $orig_header
 * @return $string
 */
function getforwardHeader($orig_header) {
    global $editor_size, $default_charset;

    // using own strlen function in order to detect correct string length
    $display = array( _("Subject") => sq_strlen(_("Subject"),$default_charset),
            _("From")    => sq_strlen(_("From"),$default_charset),
            _("Date")    => sq_strlen(_("Date"),$default_charset),
            _("To")      => sq_strlen(_("To"),$default_charset),
            _("Cc")      => sq_strlen(_("Cc"),$default_charset) );
    $maxsize = max($display);
    $indent = str_pad('',$maxsize+2);
    foreach($display as $key => $val) {
        $display[$key] = $key .': '. str_pad('', $maxsize - $val);
    }
    $from = decodeHeader($orig_header->getAddr_s('from',"\n$indent"),false,false,true);
    $from = str_replace('&nbsp;',' ',$from);
    $to = decodeHeader($orig_header->getAddr_s('to',"\n$indent"),false,false,true);
    $to = str_replace('&nbsp;',' ',$to);
    $subject = decodeHeader($orig_header->subject,false,false,true);
    $subject = str_replace('&nbsp;',' ',$subject);

    // using own str_pad function in order to create correct string pad
    $bodyTop =  sq_str_pad(' '._("Original Message").' ',$editor_size -2,'-',STR_PAD_BOTH,$default_charset) .
        "\n". $display[_("Subject")] . $subject . "\n" .
        $display[_("From")] . $from . "\n" .
        $display[_("Date")] . getLongDateString( $orig_header->date ). "\n" .
        $display[_("To")] . $to . "\n";
    if ($orig_header->cc != array() && $orig_header->cc !='') {
        $cc = decodeHeader($orig_header->getAddr_s('cc',"\n$indent"),false,false,true);
        $cc = str_replace('&nbsp;',' ',$cc);
        $bodyTop .= $display[_("Cc")] .$cc . "\n";
    }
    $bodyTop .= str_pad('', $editor_size -2 , '-') .
        "\n\n";
    return $bodyTop;
}
/* ----------------------------------------------------------------------- */

/*
 * If the session is expired during a post this restores the compose session
 * vars.
 */
if (sqsession_is_registered('session_expired_post')) {
    sqgetGlobalVar('session_expired_post', $session_expired_post, SQ_SESSION);
    /*
     * extra check for username so we don't display previous post data from
     * another user during this session.
     */
    if ($session_expired_post['username'] != $username) {
        unset($session_expired_post);
        sqsession_unregister('session_expired_post');
        session_write_close();
    } else {
        // these are the vars that we can set from the expired composed session   
        $compo_var_list = array ( 'send_to', 'send_to_cc','body','startMessage',
            'passed_body','use_signature','signature','attachments','subject','newmail',
            'send_to_bcc', 'passed_id', 'mailbox', 'from_htmladdr_search', 'identity',
            'draft_id', 'delete_draft', 'mailprio', 'edit_as_new', 'compose_messsages',
            'composesession', 'request_mdn', 'request_dr');

        foreach ($compo_var_list as $var) {
            if ( isset($session_expired_post[$var]) && !isset($$var) ) {
               $$var = $session_expired_post[$var];
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
        $sHeaderJs = (isset($sHeaderJs)) ? $sHeaderJs : '';
        if (strpos($action, 'reply') !== false && $reply_focus) {
            $sBodyTagJs = 'onload="checkForm(\''.$replyfocus.'\');"';
        } else {
            $sBodyTagJs = 'onload="checkForm();"';
        }
        displayPageHeader($color, $mailbox,$sHeaderJs,$sBodyTagJs);
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
            $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, false);
            sqimap_mailbox_select($imap_stream, $draft_folder);
            // force bypass_trash=true because message should be saved when deliverMessage() returns true.
            // in current implementation of sqimap_msgs_list_flag() single message id can
            // be submitted as string. docs state that it should be array.
            sqimap_msgs_list_delete($imap_stream, $draft_folder, $delete_draft, true);
            if ($auto_expunge) {
                sqimap_mailbox_expunge($imap_stream, $draft_folder, true);
            }
            sqimap_logout($imap_stream);
        }
        
        $oErrorHandler->saveDelayedErrors();
        session_write_close();

        if ($compose_new_win == '1') {
            if ( !isset($pageheader_sent) || !$pageheader_sent ) {
                Header("Location: $location/compose.php?saved_draft=yes&session=$composesession");
            } else {
                echo '   <br><br><div style="text-align: center;"><a href="' . $location
                    . '/compose.php?saved_sent=yes&amp;session=' . $composesession . '">'
                    . _("Return") . '</a></div>';
            }
            exit();
        } else {
            if ( !isset($pageheader_sent) || !$pageheader_sent ) {
                Header("Location: $location/right_main.php?mailbox=" . urlencode($draft_folder) .
                   "&startMessage=1&note=".urlencode($draft_message));
            } else {
                echo '   <br><br><div style="text-align: center;"><a href="' . $location
                    . '/right_main.php?mailbox=' . urlencode($draft_folder)
                    . '&amp;startMessage=1&amp;note=' . urlencode($draft_message) .'">'
                    . _("Return") . '</a></div>';
            }
            exit();
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
        if ($mailbox == "All Folders") {
            /* We entered compose via the search results page */
            $mailbox = 'INBOX'; /* Send 'em to INBOX, that's safe enough */
        }
        $urlMailbox = urlencode (trim($mailbox));
        if (! isset($passed_id)) {
            $passed_id = 0;
        }
        /**
         * Set $default_charset to correspond with the user's selection
         * of language interface.
         */
        set_my_charset();
        /**
         * This is to change all newlines to \n
         * We'll change them to \r\n later (in the sendMessage function)
         */
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\r", "\n", $body);

        /**
         * Rewrap $body so that no line is bigger than $editor_size
         */
        $body = explode("\n", $body);
        $newBody = '';
        foreach ($body as $line) {
            if( $line <> '-- ' ) {
                $line = rtrim($line);
            }
            if (sq_strlen($line,$default_charset) <= $editor_size + 1) {
                $newBody .= $line . "\n";
            } else {
                sqWordWrap($line, $editor_size,$default_charset);
                $newBody .= $line . "\n";

            }

        }
        $body = $newBody;

        $composeMessage=$compose_messages[$session];

        $Result = deliverMessage($composeMessage);

        do_hook('compose_send_after', $Result, $composeMessage);
        if (! $Result) {
            showInputForm($session);
            exit();
        }
        unset($compose_messages[$session]);

        /* if it is resumed draft, delete draft message */
        if ( isset($delete_draft)) {
            $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, false);
            sqimap_mailbox_select($imap_stream, $draft_folder);
            // bypass_trash=true because message should be saved when deliverMessage() returns true.
            // in current implementation of sqimap_msgs_list_flag() single message id can
            // be submitted as string. docs state that it should be array.
            sqimap_msgs_list_delete($imap_stream, $draft_folder, $delete_draft, true);
            if ($auto_expunge) {
                sqimap_mailbox_expunge($imap_stream, $draft_folder, true);
            }
            sqimap_logout($imap_stream);
        }
        /*
         * Store the error array in the session because they will be lost on a redirect
         */
        $oErrorHandler->saveDelayedErrors();
        session_write_close();

        if ($compose_new_win == '1') {
            if ( !isset($pageheader_sent) || !$pageheader_sent ) {
                Header("Location: $location/compose.php?mail_sent=yes");
            } else {
                echo '   <br><br><div style="text-align: center;"><a href="' . $location
                    . '/compose.php?mail_sent=yes">'
                    . _("Return") . '</a></div>';
            }
            exit();
        } else {
            if ( !isset($pageheader_sent) || !$pageheader_sent ) {
                Header("Location: $location/right_main.php?mailbox=$urlMailbox".
                    "&startMessage=$startMessage&mail_sent=yes");
            } else {
                echo '   <br><br><div style="text-align: center;"><a href="' . $location
                    . "/right_main.php?mailbox=$urlMailbox"
                    . "&amp;startMessage=$startMessage&amp;mail_sent=yes\">"
                    . _("Return") . '</a></div>';
            }
            exit();
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
} elseif (isset($html_addr_search) && !isset($html_addr_search_cancel)) {
    if (isset($_FILES['attachfile']) &&
            $_FILES['attachfile']['tmp_name'] &&
            $_FILES['attachfile']['tmp_name'] != 'none') {
        if(saveAttachedFiles($session)) {
            plain_error_message(_("Could not move/copy file. File not attached"));
        }
    }
    /*
     * I am using an include so as to elminiate an extra unnecessary
     * click.  If you can think of a better way, please implement it.
     */
    include_once('./addrbook_search_html.php');
} elseif (isset($attach)) {
    if ($compose_new_win == '1') {
        compose_Header($color, $mailbox);
    } else {
        displayPageHeader($color, $mailbox);
    }
    if (saveAttachedFiles($session)) {
        plain_error_message(_("Could not move/copy file. File not attached"));
    }
    showInputForm($session);
}
elseif (isset($sigappend)) {
    $signature = $idents[$identity]['signature'];

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
    } else {
        displayPageHeader($color, $mailbox);
    }

    if (isset($delete) && is_array($delete)) {
        $composeMessage = $compose_messages[$session];
        foreach($delete as $index) {
            if (!empty($composeMessage->entities) && isset($composeMessage->entities[$index])) {
                $composeMessage->entities[$index]->purgeAttachments();
                unset ($composeMessage->entities[$index]);
            }
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
    if (isset($subject)) {
        $values['subject'] = $subject;
    }
    showInputForm($session, $values);
}

exit();

/**************** Only function definitions go below *************/

function getforwardSubject($subject)
{
    if ((substr(strtolower($subject), 0, 4) != 'fwd:') &&
            (substr(strtolower($subject), 0, 5) != '[fwd:') &&
            (substr(strtolower($subject), 0, 6) != '[ fwd:')) {
        $subject = '[Fwd: ' . $subject . ']';
    }
    return $subject;
}

/* This function is used when not sending or adding attachments */
function newMail ($mailbox='', $passed_id='', $passed_ent_id='', $action='', $session='') {
    global $editor_size, $default_use_priority, $body, $idents,
        $use_signature, $data_dir, $username,
        $key, $imapServerAddress, $imapPort, $compose_messages,
        $composeMessage, $body_quotem, $request_mdn, $request_dr,
        $default_use_mdn, $mdn_user_support;
    global $languages, $squirrelmail_language, $default_charset;

    /*
     * Set $default_charset to correspond with the user's selection
     * of language interface. $default_charset global is not correct,
     * if message is composed in new window.
     */
    set_my_charset();

    $send_to = $send_to_cc = $send_to_bcc = $subject = $identity = '';
    $mailprio = 3;

    if ($passed_id) {
        $imapConnection = sqimap_login($username, false, $imapServerAddress,
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

        $type0 = $message->type0;
        $type1 = $message->type1;
        foreach ($entities as $ent) {
            $msg = $message->getEntity($ent);
            $type0 = $msg->type0;
            $type1 = $msg->type1;
            $unencoded_bodypart = mime_fetch_body($imapConnection, $passed_id, $ent);
            $body_part_entity = $message->getEntity($ent);
            $bodypart = decodeBody($unencoded_bodypart, 
                    $body_part_entity->header->encoding);
            if ($type1 == 'html') {
                $bodypart = str_replace("\n", ' ', $bodypart);
                $bodypart = preg_replace(array('/<\/?p>/i','/<div><\/div>/i','/<br\s*(\/)*>/i','/<\/?div>/i'), "\n", $bodypart);
                $bodypart = str_replace(array('&nbsp;','&gt;','&lt;'),array(' ','>','<'),$bodypart);
                $bodypart = strip_tags($bodypart);
            }
            if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
                    function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode')) {
                if (mb_detect_encoding($bodypart) != 'ASCII') {
                    $bodypart = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode', $bodypart);
                }
            }

            // charset encoding in compose form stuff
            if (isset($body_part_entity->header->parameters['charset'])) {
                $actual = $body_part_entity->header->parameters['charset'];
            } else {
                $actual = 'us-ascii';
            }

            if ( $actual && is_conversion_safe($actual) && $actual != $default_charset){
                $bodypart = charset_convert($actual,$bodypart,$default_charset,false);
            }
            // end of charset encoding in compose

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

        $identity = '';
        $from_o = $orig_header->from;
        if (is_array($from_o)) {
            if (isset($from_o[0])) {
                $from_o = $from_o[0];
            }
        }
        if (is_object($from_o)) {
            $orig_from = $from_o->getAddress();
        } else {
            $orig_from = '';
        }

        $identities = array();
        if (count($idents) > 1) {
            foreach($idents as $nr=>$data) {
                $enc_from_name = '"'.$data['full_name'].'" <'. $data['email_address'].'>';
                if($enc_from_name == $orig_from) {
                    $identity = $nr;
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
                $composeMessage->rfc822_header = $orig_header;
                $send_to = decodeHeader($orig_header->getAddr_s('to'),false,false,true);
                $send_to_cc = decodeHeader($orig_header->getAddr_s('cc'),false,false,true);
                $send_to_bcc = decodeHeader($orig_header->getAddr_s('bcc'),false,false,true);
                $send_from = $orig_header->getAddr_s('from');
                $send_from_parts = new AddressStructure();
                $send_from_parts = $orig_header->parseAddress($send_from);
                $send_from_add = $send_from_parts->mailbox . '@' . $send_from_parts->host;
                $identities = get_identities();
                if (count($identities) > 0) {
                    foreach($identities as $iddata) {
                        if ($send_from_add == $iddata['email_address']) {
                            $identity = $iddata['index'];
                            break;
                        }
                    }
                }
                $subject = decodeHeader($orig_header->subject,false,false,true);
                
                // Remember the receipt settings
                $request_mdn = $mdn_user_support && !empty($orig_header->dnt) ? '1' : '0';
                $request_dr = $mdn_user_support && !empty($orig_header->drnt) ? '1' : '0';
                
                /* remember the references and in-reply-to headers in case of an reply */
                $composeMessage->rfc822_header->more_headers['References'] = $orig_header->references;
                $composeMessage->rfc822_header->more_headers['In-Reply-To'] = $orig_header->in_reply_to;
                // rewrap the body to clean up quotations and line lengths
                sqBodyWrap($body, $editor_size);
                $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);
                break;
            case ('edit_as_new'):
                $send_to = decodeHeader($orig_header->getAddr_s('to'),false,false,true);
                $send_to_cc = decodeHeader($orig_header->getAddr_s('cc'),false,false,true);
                $send_to_bcc = decodeHeader($orig_header->getAddr_s('bcc'),false,false,true);
                $subject = decodeHeader($orig_header->subject,false,false,true);
                $mailprio = $orig_header->priority;
                $orig_from = '';
                $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);
                // rewrap the body to clean up quotations and line lengths
                sqBodyWrap($body, $editor_size);
                break;
            case ('forward'):
                $send_to = '';
                $subject = getforwardSubject(decodeHeader($orig_header->subject,false,false,true));
                $body = getforwardHeader($orig_header) . $body;
                // the logic for calling sqUnWordWrap here would be to allow the browser to wrap the lines
                // forwarded message text should be as undisturbed as possible, so commenting out this call
                // sqUnWordWrap($body);
                $composeMessage = getAttachments($message, $composeMessage, $passed_id, $entities, $imapConnection);

                //add a blank line after the forward headers
                $body = "\n" . $body;
                break;
            case ('forward_as_attachment'):
                $subject = getforwardSubject(decodeHeader($orig_header->subject,false,false,true));
                $composeMessage = getMessage_RFC822_Attachment($message, $composeMessage, $passed_id, $passed_ent_id, $imapConnection);
                $body = '';
                break;
            case ('reply_all'):
                if(isset($orig_header->mail_followup_to) && $orig_header->mail_followup_to) {
                    $send_to = $orig_header->getAddr_s('mail_followup_to');
                } else {
                    $send_to_cc = replyAllString($orig_header);
                    $send_to_cc = decodeHeader($send_to_cc,false,false,true);
                }
            case ('reply'):
                // skip this if send_to was already set right above here
                if(!$send_to) {
                    $send_to = $orig_header->reply_to;
                    if (is_array($send_to) && count($send_to)) {
                        $send_to = $orig_header->getAddr_s('reply_to');
                    } else if (is_object($send_to)) { /* unneccesarry, just for failsafe purpose */
                        $send_to = $orig_header->getAddr_s('reply_to');
                    } else {
                        $send_to = $orig_header->getAddr_s('from');
                    }
                }
                $send_to = decodeHeader($send_to,false,false,true);
                $subject = decodeHeader($orig_header->subject,false,false,true);
                $subject = str_replace('"', "'", $subject);
                $subject = trim($subject);
                if (substr(strtolower($subject), 0, 3) != 're:') {
                    $subject = 'Re: ' . $subject;
                }
                /* this corrects some wrapping/quoting problems on replies */
                $rewrap_body = explode("\n", $body);
                $from =  (is_array($orig_header->from)) ? $orig_header->from[0] : $orig_header->from;
                $body = '';
                $strip_sigs = getPref($data_dir, $username, 'strip_sigs');
                foreach ($rewrap_body as $line) {
                    if ($strip_sigs && substr($line,0,3) == '-- ') {
                        break;
                    }
                    if (preg_match("/^(>+)/", $line, $matches)) {
                        $gt = $matches[1];
                        $body .= $body_quote . str_replace("\n", "\n$body_quote$gt ", rtrim($line)) ."\n";
                    } else {
                        $body .= $body_quote . (!empty($body_quote) ? ' ' : '') . str_replace("\n", "\n$body_quote" . (!empty($body_quote) ? ' ' : ''), rtrim($line)) . "\n";
                    }
                }

                //rewrap the body to clean up quotations and line lengths
                $body = sqBodyWrap ($body, $editor_size);

                $body = getReplyCitation($from , $orig_header->date) . $body;
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

/**
 * downloads attachments from original message, stores them in attachment directory and adds
 * them to composed message.
 * @param object $message
 * @param object $composeMessage
 * @param integer $passed_id
 * @param mixed $entities
 * @param mixed $imapConnection
 * @return object
 */
function getAttachments($message, &$composeMessage, $passed_id, $entities, $imapConnection) {
    global $attachment_dir, $username, $data_dir, $squirrelmail_language, $languages;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!count($message->entities) ||
            ($message->type0 == 'message' && $message->type1 == 'rfc822')) {
        if ( !in_array($message->entity_id, $entities) && $message->entity_id) {
            switch ($message->type0) {
                case 'message':
                    if ($message->type1 == 'rfc822') {
                        $filename = $message->rfc822_header->subject;
                        if ($filename == "") {
                            $filename = "untitled-".$message->entity_id;
                        }
                        $filename .= '.msg';
                    } else {
                        $filename = $message->getFilename();
                    }
                    break;
                default:
                    if (!$message->mime_header) { /* temporary hack */
                        $message->mime_header = $message->header;
                    }
                    $filename = $message->getFilename();
                    break;
            }
            $filename = str_replace('&#32;', ' ', decodeHeader($filename));
            if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
                    function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_encode')) {
                $filename =  call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_encode', $filename);
            }
            $localfilename = GenerateRandomString(32, '', 7);
            $full_localfilename = "$hashed_attachment_dir/$localfilename";
            while (file_exists($full_localfilename)) {
                $localfilename = GenerateRandomString(32, '', 7);
                $full_localfilename = "$hashed_attachment_dir/$localfilename";
            }
            $message->att_local_name = $full_localfilename;

            $composeMessage->initAttachment($message->type0.'/'.$message->type1,$filename,
                    $full_localfilename);

            /* Write Attachment to file */
            $fp = fopen ("$hashed_attachment_dir/$localfilename", 'wb');
            mime_print_body_lines ($imapConnection, $passed_id, $message->entity_id, $message->header->encoding, $fp);
            fclose ($fp);
        }
    } else {
        for ($i=0, $entCount=count($message->entities); $i<$entCount;$i++) {
            $composeMessage=getAttachments($message->entities[$i], $composeMessage, $passed_id, $entities, $imapConnection);
        }
    }
    return $composeMessage;
}

function getMessage_RFC822_Attachment($message, $composeMessage, $passed_id,
        $passed_ent_id='', $imapConnection) {
    global $attachment_dir, $username, $data_dir;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!$passed_ent_id) {
        $body_a = sqimap_run_command($imapConnection,
                'FETCH '.$passed_id.' RFC822',
                TRUE, $response, $readmessage,
                TRUE);
    } else {
        $body_a = sqimap_run_command($imapConnection,
                'FETCH '.$passed_id.' BODY['.$passed_ent_id.']',
                TRUE, $response, $readmessage, TRUE);
        $message = $message->parent;
    }
    if ($response == 'OK') {
        $subject = encodeHeader($message->rfc822_header->subject);
        array_shift($body_a);
        array_pop($body_a);
        $body = implode('', $body_a) . "\r\n";

        $localfilename = GenerateRandomString(32, 'FILE', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";

        $fp = fopen($full_localfilename, 'w');
        fwrite ($fp, $body);
        fclose($fp);
        $composeMessage->initAttachment('message/rfc822',$subject.'.msg',
                $full_localfilename);
    }
    return $composeMessage;
}

function showInputForm ($session, $values=false) {
    global $send_to, $send_to_cc, $body, $startMessage, $action,
        $color, $use_signature, $signature, $prefix_sig,
        $editor_size, $editor_height, $subject, $newmail,
        $use_javascript_addr_book, $send_to_bcc, $passed_id, $mailbox,
        $from_htmladdr_search, $location_of_buttons, $attachment_dir,
        $username, $data_dir, $identity, $idents, $delete_draft,
        $mailprio, $compose_new_win, $saved_draft, $mail_sent, $sig_first,
        $compose_messages, $composesession, $default_charset,
        $compose_onsubmit, $oTemplate, $oErrorHandler;

    if (checkForJavascript()) {
        $onfocus = ' onfocus="alreadyFocused=true;"';
        $onfocus_array = array('onfocus' => 'alreadyFocused=true;');
    }
    else {
        $onfocus = '';
        $onfocus_array = array();
    }

    $composeMessage = $compose_messages[$session];
    if ($values) {
        $send_to = $values['send_to'];
        $send_to_cc = $values['send_to_cc'];
        $send_to_bcc = $values['send_to_bcc'];
        $subject = $values['subject'];
        $mailprio = $values['mailprio'];
        $body = $values['body'];
        $identity = (int) $values['identity'];
    } else {
        $send_to = decodeHeader($send_to, true, false);
        $send_to_cc = decodeHeader($send_to_cc, true, false);
        $send_to_bcc = decodeHeader($send_to_bcc, true, false);
    }

    if ($use_javascript_addr_book) {
        echo "\n". '<script type="text/javascript">'."\n<!--\n" .
            'function open_abook() { ' . "\n" .
            '  var nwin = window.open("addrbook_popup.php","abookpopup",' .
            '"width=670,height=300,resizable=yes,scrollbars=yes");' . "\n" .
            '  if((!nwin.opener) && (document.windows != null))' . "\n" .
            '    nwin.opener = document.windows;' . "\n" .
            "}\n" .
            "// -->\n</script>\n\n";
    }

    echo "\n" . '<form name="compose" action="compose.php" method="post" ' .
        'enctype="multipart/form-data"';

    $compose_onsubmit = array();
    do_hook('compose_form');

    // Plugins that use compose_form hook can add an array entry
    // to the globally scoped $compose_onsubmit; we add them up
    // here and format the form tag's full onsubmit handler.
    // Each plugin should use "return false" if they need to
    // stop form submission but otherwise should NOT use "return
    // true" to give other plugins the chance to do what they need
    // to do; SquirrelMail itself will add the final "return true".
    // Onsubmit text is enclosed inside of double quotes, so plugins
    // need to quote accordingly.
    if (checkForJavascript()) {
        $onsubmit_text = ' onsubmit="';
        if (empty($compose_onsubmit))
            $compose_onsubmit = array();
        else if (!is_array($compose_onsubmit))
            $compose_onsubmit = array($compose_onsubmit);

        foreach ($compose_onsubmit as $text) {
            $text = trim($text);
            if (substr($text, -1) != ';' && substr($text, -1) != '}')
                $text .= '; ';
            $onsubmit_text .= $text;
        }

        echo $onsubmit_text . ' return true;"';
    }


    echo ">\n";

    echo addHidden('startMessage', $startMessage);

    if ($action == 'draft') {
        echo addHidden('delete_draft', $passed_id);
    }
    if (isset($delete_draft)) {
        echo addHidden('delete_draft', $delete_draft);
    }
    if (isset($session)) {
        echo addHidden('session', $session);
    }

    if (isset($passed_id)) {
        echo addHidden('passed_id', $passed_id);
    }

    if ($saved_draft == 'yes') {
        $oTemplate->assign('note', _("Draft Saved"));
        $oTemplate->display('note.tpl');
    }
    if ($mail_sent == 'yes') {
        $oTemplate->assign('note', _("Your message has been sent."));
        $oTemplate->display('note.tpl');
    }
    if ($compose_new_win == '1') {
        $oTemplate->display('compose_newwin_close.tpl');
    }
    
    if ($location_of_buttons == 'top') {
        showComposeButtonRow();
    }

    $identities = array();
    if (count($idents) > 1) {
        reset($idents);
        foreach($idents as $id => $data) {
            $identities[$id] = $data['full_name'].' &lt;'.$data['email_address'].'&gt;';
        }
    }
    
    $oTemplate->assign('identities', $identities);
    $oTemplate->assign('identity_def', $identity);
    $oTemplate->assign('input_onfocus', 'onfocus="'.join(' ', $onfocus_array).'"');
    
    $oTemplate->assign('to', htmlspecialchars($send_to));
    $oTemplate->assign('cc', htmlspecialchars($send_to_cc));
    $oTemplate->assign('bcc', htmlspecialchars($send_to_bcc));
    $oTemplate->assign('subject', htmlspecialchars($subject));
        
    $oTemplate->display('compose_header.tpl');

    if ($location_of_buttons == 'between') {
        showComposeButtonRow();
    }

    $body_str = '';
    if ($use_signature == true && $newmail == true && !isset($from_htmladdr_search)) {
        $signature = $idents[$identity]['signature'];

        if ($sig_first == '1') {
            /*
             * FIXME: test is specific to ja_JP translation implementation.
             * This test might apply incorrect conversion to other translations, but
             * use of 7bit iso-2022-jp charset in other translations might have other
             * issues too.
             */
            if ($default_charset == 'iso-2022-jp') {
                $body_str = "\n\n".($prefix_sig==true? "-- \n":'').mb_convert_encoding($signature, 'EUC-JP');
            } else {
                $body_str = "\n\n".($prefix_sig==true? "-- \n":'').decodeHeader($signature,false,false);
            }
            $body_str .= "\n\n".htmlspecialchars(decodeHeader($body,false,false));
        } else {
            $body_str = "\n\n".htmlspecialchars(decodeHeader($body,false,false));
            // FIXME: test is specific to ja_JP translation implementation. See above comments.
            if ($default_charset == 'iso-2022-jp') {
                $body_str .= "\n\n".($prefix_sig==true? "-- \n":'').mb_convert_encoding($signature, 'EUC-JP');
            } else {
                $body_str .= "\n\n".($prefix_sig==true? "-- \n":'').decodeHeader($signature,false,false);
            }
        }
    } else {
        $body_str = htmlspecialchars(decodeHeader($body,false,false));
    }

    $oTemplate->assign('editor_width', (int)$editor_size);
    $oTemplate->assign('editor_height', (int)$editor_height);
    $oTemplate->assign('input_onfocus', 'onfocus="'.join(' ', $onfocus_array).'"');
    $oTemplate->assign('body', $body_str);
    $oTemplate->assign('show_bottom_send', $location_of_buttons!='bottom');
    
    $oTemplate->display ('compose_body.tpl');
    
    if ($location_of_buttons == 'bottom') {
        showComposeButtonRow();
    }

    /* This code is for attachments */
    if ((bool) ini_get('file_uploads')) {

        /* Calculate the max size for an uploaded file.
         * This is advisory for the user because we can't actually prevent
         * people to upload too large files. */
        $sizes = array();
        /* php.ini vars which influence the max for uploads */
        $configvars = array('post_max_size', 'memory_limit', 'upload_max_filesize');
        foreach($configvars as $var) {
            /* skip 0 or empty values, and -1 which means 'unlimited' */
            if( $size = getByteSize(ini_get($var)) ) {
                if ( $size != '-1' ) {
                    $sizes[] = $size;
                }
            }
        }

        $attach = array();
        if ($composeMessage->entities) {
            foreach ($composeMessage->entities as $key => $attachment) {
                $attached_file = $attachment->att_local_name;
                if ($attachment->att_local_name || $attachment->body_part) {
                    $attached_filename = decodeHeader($attachment->mime_header->getParameter('name'));
                    $type = $attachment->mime_header->type0.'/'.
                        $attachment->mime_header->type1;
    
                    $a = array();
                    $a['Key'] = $key;
                    $a['FileName'] = $attached_filename;
                    $a['ContentType'] = $type;
                    $a['Size'] = filesize($attached_file);
                    $attach[$key] = $a;
                }
            }
        }
    
        $max = min($sizes);
        $oTemplate->assign('max_file_size', empty($max) ? -1 : $max);
        $oTemplate->assign('attachments', $attach);
        
        $oTemplate->display('compose_attachments.tpl');
    } // End of file_uploads if-block
    /* End of attachment code */

    echo addHidden('username', $username).
         addHidden('smaction', $action).
         addHidden('mailbox', $mailbox);
    /*
       store the complete ComposeMessages array in a hidden input value
       so we can restore them in case of a session timeout.
     */
    sqgetGlobalVar('QUERY_STRING', $queryString, SQ_SERVER);
    echo addHidden('restoremessages', serialize($compose_messages)).
        addHidden('composesession', $composesession).
        addHidden('querystring', $queryString).
        "</form>\n";
    if (!(bool) ini_get('file_uploads')) {
        /* File uploads are off, so we didn't show that part of the form.
           To avoid bogus bug reports, tell the user why. */
        echo '<p style="text-align:center">'
            . _("Because PHP file uploads are turned off, you can not attach files to this message. Please see your system administrator for details.")
            . "</p>\r\n";
    }

    do_hook('compose_bottom');

    if ($compose_new_win=='1') {
        $oTemplate->display('compose_newwin_close.tpl');
    }
    
    $oErrorHandler->setDelayedErrors(false);
    $oTemplate->display('footer.tpl');
}


function showComposeButtonRow() {
    global $use_javascript_addr_book, $save_as_draft,
        $default_use_priority, $mailprio, $default_use_mdn,
        $request_mdn, $request_dr,
        $data_dir, $username;

    global $oTemplate, $buffer_hook;
    
    if ($default_use_priority) {    
        $priorities = array('1'=>_("High"), '3'=>_("Normal"), '5'=>_("Low"));
        $priority = isset($mailprio) ? $mailprio : 3;
    } else {
        $priorities = array();
        $priority = NULL;
    }
    
    $mdn_user_support=getPref($data_dir, $username, 'mdn_user_support',$default_use_mdn);

    if ($use_javascript_addr_book) {
        $addr_book = "         <script type=\"text/javascript\"><!--\n document.write(\"".
            "            <input type=button value=\\\""._("Addresses").
            "\\\" onclick=\\\"javascript:open_abook();\\\" />\");".
            "            // --></script><noscript>\n".
            '            <input type="submit" name="html_addr_search" value="'.
            _("Addresses").'" />'.
            "         </noscript>\n";
    } else {
        $addr_book = '         <input type="submit" name="html_addr_search" value="'.
            _("Addresses").'" />' . "\n";
    }

    $oTemplate->assign('allow_priority', $default_use_priority==1);
    $oTemplate->assign('priority_list', $priorities);
    $oTemplate->assign('current_priority', $priority);
    
    $oTemplate->assign('notifications_enabled', $mdn_user_support==1);
    $oTemplate->assign('read_receipt', $request_mdn=='1');
    $oTemplate->assign('delivery_receipt', $request_dr=='1');
    
    $oTemplate->assign('drafts_enabled', $save_as_draft);
    $oTemplate->assign('address_book_button', $addr_book);

    $oTemplate->display('compose_buttons.tpl');
}

function checkInput ($show) {
    /*
     * I implemented the $show variable because the error messages
     * were getting sent before the page header.  So, I check once
     * using $show=false, and then when i'm ready to display the error
     * message, show=true
     */
    global $send_to, $send_to_bcc;

    if ($send_to == '' && $send_to_bcc == '') {
        if ($show) {
            plain_error_message(_("You have not filled in the \"To:\" field."));
        }
        return false;
    }
    return true;
} /* function checkInput() */


/* True if FAILURE */
function saveAttachedFiles($session) {
    global $_FILES, $attachment_dir, $username,
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

    // m_u_f works better with restricted PHP installs (safe_mode, open_basedir),
    // if that doesn't work, try a simple rename.
    if (!@move_uploaded_file($_FILES['attachfile']['tmp_name'],$full_localfilename)) {
        if (!@rename($_FILES['attachfile']['tmp_name'], $full_localfilename)) {
            return true;
        }
    }
    $message = $compose_messages[$session];
    $type = strtolower($_FILES['attachfile']['type']);
    $name = $_FILES['attachfile']['name'];
    $message->initAttachment($type, $name, $full_localfilename);
    $compose_messages[$session] = $message;
    sqsession_register($compose_messages , 'compose_messages');
}

/* parse values like 8M and 2k into bytes */
function getByteSize($ini_size) {

    if(!$ini_size) {
        return FALSE;
    }

    $ini_size = trim($ini_size);

    // if there's some kind of letter at the end of the string we need to multiply.
    if(!is_numeric(substr($ini_size, -1))) {

        switch(strtoupper(substr($ini_size, -1))) {
            case 'G':
                $bytesize = 1073741824;
                break;
            case 'M':
                $bytesize = 1048576;
                break;
            case 'K':
                $bytesize = 1024;
                break;
        }

        return ($bytesize * (int)substr($ini_size, 0, -1));
    }

    return $ini_size;
}


/**
 * temporary function to make use of the deliver class.
 * In the future the responsible backend should be automaticly loaded
 * and conf.pl should show a list of available backends.
 * The message also should be constructed by the message class.
 */
function deliverMessage($composeMessage, $draft=false) {
    global $send_to, $send_to_cc, $send_to_bcc, $mailprio, $subject, $body,
        $username, $popuser, $usernamedata, $identity, $idents, $data_dir,
        $request_mdn, $request_dr, $default_charset, $color, $useSendmail,
        $domain, $action, $default_move_to_sent, $move_to_sent;
    global $imapServerAddress, $imapPort, $sent_folder, $key;

    $rfc822_header = $composeMessage->rfc822_header;

    $abook = addressbook_init(false, true);
    $rfc822_header->to = $rfc822_header->parseAddress($send_to,true, array(), '', $domain, array(&$abook,'lookup'));
    $rfc822_header->cc = $rfc822_header->parseAddress($send_to_cc,true,array(), '',$domain, array(&$abook,'lookup'));
    $rfc822_header->bcc = $rfc822_header->parseAddress($send_to_bcc,true, array(), '',$domain, array(&$abook,'lookup'));
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
    $from_mail = $idents[$identity]['email_address'];
    $full_name = $idents[$identity]['full_name'];
    $reply_to  = $idents[$identity]['reply_to'];
    if (!$from_mail) {
        $from_mail = "$popuser@$domain";
    }
    $rfc822_header->from = $rfc822_header->parseAddress($from_mail,true);
    if ($full_name) {
        $from = $rfc822_header->from[0];
        if (!$from->host) $from->host = $domain;
        $full_name_encoded = encodeHeader($full_name);
        if ($full_name_encoded != $full_name) {
            $from_addr = $full_name_encoded .' <'.$from->mailbox.'@'.$from->host.'>';
        } else {
            $from_addr = '"'.$full_name .'" <'.$from->mailbox.'@'.$from->host.'>';
        }
        $rfc822_header->from = $rfc822_header->parseAddress($from_addr,true);
    }
    if ($reply_to) {
        $rfc822_header->reply_to = $rfc822_header->parseAddress($reply_to,true);
    }
    /* Receipt: On Read */
    if (isset($request_mdn) && $request_mdn) {
        $rfc822_header->dnt = $rfc822_header->parseAddress($from_mail,true);
    } elseif (isset($rfc822_header->dnt)) {
        unset($rfc822_header->dnt);
    }
    
    /* Receipt: On Delivery */
    if (isset($request_dr) && $request_dr) {
        $rfc822_header->more_headers['Return-Receipt-To'] = $from_mail;
    } elseif (isset($rfc822_header->more_headers['Return-Receipt-To'])) {
        unset($rfc822_header->more_headers['Return-Receipt-To']);
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
            $mime_header->encoding = '8bit';
        }
        if ($default_charset) {
            $mime_header->parameters['charset'] = $default_charset;
        }
        $message_body->mime_header = $mime_header;
        array_unshift($composeMessage->entities, $message_body);
        $content_type = new ContentType('multipart/mixed');
    } else {
        $content_type = new ContentType('text/plain');
        if ($special_encoding) {
            $rfc822_header->encoding = $special_encoding;
        } else {
            $rfc822_header->encoding = '8bit';
        }
        if ($default_charset) {
            $content_type->properties['charset']=$default_charset;
        }
    }

    $rfc822_header->content_type = $content_type;
    $composeMessage->rfc822_header = $rfc822_header;
    
    /* Here you can modify the message structure just before we hand
       it over to deliver */
    $hookReturn = do_hook('compose_send', $composeMessage);
    /* Get any changes made by plugins to $composeMessage. */
    if ( is_object($hookReturn[1]) ) {
        $composeMessage = $hookReturn[1];
    }

    if (!$useSendmail && !$draft) {
        require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
        $deliver = new Deliver_SMTP();
        global $smtpServerAddress, $smtpPort, $pop_before_smtp;

        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
        get_smtp_user($user, $pass);
        $stream = $deliver->initStream($composeMessage,$domain,0,
                $smtpServerAddress, $smtpPort, $user, $pass, $authPop);
    } elseif (!$draft) {
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
    } elseif ($draft) {
        global $draft_folder;
        require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
        $imap_stream = sqimap_login($username, false, $imapServerAddress,
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
            $composeMessage->purgeAttachments();
            return $length;
        } else {
            $msg  = '<br />'.sprintf(_("Error: Draft folder %s does not exist."), htmlspecialchars($draft_folder));
            plain_error_message($msg);
            return false;
        }
    }
    $success = false;
    if ($stream) {
        $length = $deliver->mail($composeMessage, $stream);
        $success = $deliver->finalizeStream($stream);
    }
    if (!$success) {
        // $deliver->dlv_server_msg is not always server's reply
        $msg  = $deliver->dlv_msg;
        if (!empty($deliver->dlv_server_msg)) {
            // add 'server replied' part only when it is not empty.
            // Delivery error can be generated by delivery class itself
            $msg.='<br />' .
                _("Server replied:") . ' ' . $deliver->dlv_ret_nr . ' ' .
                nl2br(htmlspecialchars($deliver->dlv_server_msg));
        }
        plain_error_message($msg);
    } else {
        unset ($deliver);
        $move_to_sent = getPref($data_dir,$username,'move_to_sent');
        $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);

        /* Move to sent code */
        if (isset($default_move_to_sent) && ($default_move_to_sent != 0)) {
            $svr_allow_sent = true;
        } else {
            $svr_allow_sent = false;
        }

        if (isset($sent_folder) && (($sent_folder != '') || ($sent_folder != 'none'))
                && sqimap_mailbox_exists( $imap_stream, $sent_folder)) {
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
            global $passed_id, $mailbox, $action;
            if ($action == 'reply' || $action == 'reply_all') {
                $save_reply_with_orig=getPref($data_dir,$username,'save_reply_with_orig');
                if ($save_reply_with_orig) {
                    $sent_folder = $mailbox;
                }
            }
            sqimap_append ($imap_stream, $sent_folder, $length);
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imap_stream);
            sqimap_append_done ($imap_stream, $sent_folder);
            unset ($imap_deliver);
        }

        global $passed_id, $mailbox, $action, $what, $iAccount,$startMessage;

        $composeMessage->purgeAttachments();
        if ($action == 'reply' || $action == 'reply_all') {
            require(SM_PATH . 'functions/mailbox_display.php');
            $aMailbox = sqm_api_mailbox_select($imap_stream, $iAccount, $mailbox,array('setindex' => $what, 'offset' => $startMessage),array());
            // check if we are allowed to set the \\Answered flag
            if (in_array('\\answered',$aMailbox['PERMANENTFLAGS'], true)) {
                $aUpdatedMsgs = sqimap_toggle_flag($imap_stream, array($passed_id), '\\Answered', true, false);
                if (isset($aUpdatedMsgs[$passed_id]['FLAGS'])) {
                    /**
                     * Only update the cached headers if the header is
                     * cached.
                     */
                    if (isset($aMailbox['MSG_HEADERS'][$passed_id])) {
                        $aMailbox['MSG_HEADERS'][$passed_id]['FLAGS'] = $aMsg['FLAGS'];
                    }
                }
            }
            /**
             * Write mailbox with updated seen flag information back to cache.
             */
            $mailbox_cache[$iAccount.'_'.$aMailbox['NAME']] = $aMailbox;
            sqsession_register($mailbox_cache,'mailbox_cache');
        }
        sqimap_logout($imap_stream);
    }
    return $success;
}
?>