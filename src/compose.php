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
require_once('../functions/display_messages.php');
require_once('../functions/plugin.php');

/* --------------------- Specific Functions ------------------------------ */



/**
 * Does the opposite of sqWordWrap()
 */
function sqUnWordWrap(&$body) {
    $lines = explode("\n", $body);
    $body = '';
    $PreviousSpaces = '';
    for ($i = 0; $i < count($lines); $i ++) {
        ereg("^([\t >]*)([^\t >].*)?$", $lines[$i], $regs);
        $CurrentSpaces = $regs[1];
        if (isset($regs[2])) {
            $CurrentRest = $regs[2];
        }
        
        if ($i == 0) {
            $PreviousSpaces = $CurrentSpaces;
            $body = $lines[$i];
        } else if (($PreviousSpaces == $CurrentSpaces) /* Do the beginnings match */
                   && (strlen($lines[$i - 1]) > 65)    /* Over 65 characters long */
                   && strlen($CurrentRest)) {          /* and there's a line to continue with */
            $body .= ' ' . $CurrentRest;
        } else {
            $body .= "\n" . $lines[$i];
            $PreviousSpaces = $CurrentSpaces;
        }
    }
    $body .= "\n";
}

/* ----------------------------------------------------------------------- */

if (!isset($attachments)) {
    $attachments = array();
    session_register('attachments');
}

if (!isset($composesession)) {
    $composesession = 0;
    session_register('composesession');
}

if (!isset($session)) {
    $session = "$composesession" +1; 
    $composesession = $session;        
}    

if (!isset($mailbox) || $mailbox == '' || ($mailbox == 'None')) {
    $mailbox = 'INBOX';
}

if (isset($draft)) {
    include_once ('../src/draft_actions.php');
    if (! isset($reply_id)) {
         $reply_id = 0;
    }
    if (! isset($MDN)) {
        $MDN = 'False';
    }
    if (! isset($mailprio)) {
        $mailprio = '';
    }
    if (!saveMessageAsDraft($send_to, $send_to_cc, $send_to_bcc, $subject, $body, $reply_id, $mailprio, $session)) {
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
                   "&startMessage=1&note=$draft_message");
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
        if (! isset($reply_id)) {
            $reply_id = 0;
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
                                  $subject, $body, $reply_id, $MDN, '', $session);
        } else {
            $Result = sendMessage($send_to, $send_to_cc, $send_to_bcc,
                                  $subject, $body, $reply_id, $MDN, $mailprio, $session);
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
            Header("Location: compose.php?mail_sent=yes&session=$composesession");
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
    }

    showInputForm($session);
    
} elseif (isset($attachedmessages)) {

    /*
     * This handles the case if we attache message 
     */
    $imapConnection = sqimap_login($username, $key, $imapServerAddress,
                                   $imapPort, 0);
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }

    $newmail = true;

    newMail();
    showInputForm($session);
    sqimap_logout($imapConnection);

} else {
    /*
     * This handles the default case as well as the error case
     * (they had the same code) --> if (isset($smtpErrors)) 
     */
    $imapConnection = sqimap_login($username, $key, $imapServerAddress,
                                   $imapPort, 0);
        if ($compose_new_win == '1') {
            compose_Header($color, $mailbox);
        }
        else {
            displayPageHeader($color, $mailbox);
        }

    $newmail = true;

    ClearAttachments($session);

    if (isset($forward_id) && $forward_id && isset($ent_num) && $ent_num) {
        getAttachments(0, $session);
    }

    if (isset($draft_id) && $draft_id && isset($ent_num) && $ent_num) {
        getAttachments(0, $session);
    }

    newMail($session);
    showInputForm($session);
    sqimap_logout($imapConnection);
}

exit();


/**************** Only function definitions go below *************/


/* This function is used when not sending or adding attachments */
function newMail () {
    global $forward_id, $imapConnection, $msg, $ent_num, $body_ary, $body,
           $reply_id, $send_to, $send_to_cc, $mailbox, $send_to_bcc, $editor_size,
           $draft_id, $use_signature, $composesession, $forward_cc;

    $send_to = decodeHeader($send_to, false);
    $send_to_cc = decodeHeader($send_to_cc, false);
    $send_to_bcc = decodeHeader($send_to_bcc, false);
    $send_to = str_replace('&lt;', '<', str_replace('&gt;', '>', str_replace('&amp;', '&', str_replace('&quot;', '"', $send_to))));
    $send_to_cc = str_replace('&lt;', '<', str_replace('&gt;', '>', str_replace('&amp;', '&', str_replace('&quot;', '"', $send_to_cc))));
    $send_to_bcc = str_replace('&lt;', '<', str_replace('&gt;', '>', str_replace('&amp;', '&', str_replace('&quot;', '"', $send_to_bcc))));

    if ($forward_id) {
        $id = $forward_id;
    } elseif ($reply_id) {
        $id = $reply_id;
    }

    if ($draft_id){
        $id = $draft_id;
        $use_signature = FALSE;
    }

    if (isset($id)) {
        sqimap_mailbox_select($imapConnection, $mailbox);
        $message = sqimap_get_message($imapConnection, $id, $mailbox);
        $orig_header = $message->header;
        if ($ent_num) {
            $message = getEntity($message, $ent_num);
        }
        if ($message->header->type0 == 'text' ||
            $message->header->type1 == 'message') {
            if ($ent_num) {
                $body = decodeBody(
                    mime_fetch_body($imapConnection, $id, $ent_num),
                    $message->header->encoding);
            } else {
                $body = decodeBody(
                    mime_fetch_body($imapConnection, $id, 1),
                    $message->header->encoding);
            }
        } else {
            $body = '';
        }

        if ($message->header->type1 == 'html') {
            $body = strip_tags($body);
        }

        sqUnWordWrap($body);
        
        /* this corrects some wrapping/quoting problems on replies */
        if ($reply_id) {
            $rewrap_body = explode("\n", $body);
            for ($i=0;$i<count($rewrap_body);$i++) {
                sqWordWrap($rewrap_body[$i], ($editor_size - 2));
                if (preg_match("/^(>+)/", $rewrap_body[$i], $matches)) {
                    $gt = $matches[1];
                    $rewrap_body[$i] = str_replace("\n", "\n$gt ", $rewrap_body[$i]);
                }
                $rewrap_body[$i] .= "\n";
            }
            $body = implode("", $rewrap_body);
        }

        $body_ary = explode("\n", $body);
        $i = count($body_ary) - 1;
        while ($i >= 0 && ereg("^[>\\s]*$", $body_ary[$i])) {
            unset($body_ary[$i]);
            $i --;
        }
        $body = '';
        for ($i=0; isset($body_ary[$i]); $i++) {
            if ($reply_id) {
                if (preg_match("/^(>){1,}/", $body_ary[$i])) {
                    $body_ary[$i] = '>' . $body_ary[$i];
                } else {
                    $body_ary[$i] = '> ' . $body_ary[$i];
                }
            }
            if ($draft_id) {
                sqWordWrap($body_ary[$i], $editor_size );
            }
            $body .= $body_ary[$i] . "\n";
            unset($body_ary[$i]);
        }
        if ($forward_id) {
            $bodyTop =  '-------- ' . _("Original Message") . " --------\n" .
                        _("Subject") . ': ' . $orig_header->subject . "\n" .
                        _("From")    . ': ' . $orig_header->from    . "\n" .
                        _("Date")      . ': ' .
                                 getLongDateString( $orig_header->date ). "\n" .
                        _("To")      . ': ' . $orig_header->to[0]   . "\n";
            if (count($orig_header->to) > 1) {
                for ($x=1; $x < count($orig_header->to); $x++) {
                    $bodyTop .= '         ' . $orig_header->to[$x] . "\n";
                }
            }
            if (isset($forward_cc) && $forward_cc) {
                $bodyTop .= _("Cc") . ': ' . $orig_header->cc[0] . "\n";
                if (count($orig_header->cc) > 1) {
                    for ($x = 1; $x < count($orig_header->cc); $x++) {
                        $bodyTop .= '         ' . $orig_header->cc[$x] . "\n";
                    }
                }
            }
            $bodyTop .= "\n";
            $body = $bodyTop . $body;
        }
        elseif ($reply_id) {
            $orig_from = decodeHeader($orig_header->from, false);
            $body = getReplyCitation($orig_from) . $body;
        }

        return;
    }

    if (!$send_to) {
        $send_to = sqimap_find_email($send_to);
    }

    /* This formats a CC string if they hit "reply all" */
    if ($send_to_cc != '') {
        $send_to_cc = ereg_replace('"[^"]*"', '', $send_to_cc);
        $send_to_cc = str_replace(';', ',', $send_to_cc);
        $sendcc = explode(',', $send_to_cc);
        $send_to_cc = '';

        for ($i = 0; $i < count($sendcc); $i++) {
            $sendcc[$i] = trim($sendcc[$i]);
            if ($sendcc[$i] == '') {
                continue;
            }

            $sendcc[$i] = sqimap_find_email($sendcc[$i]);
            $whofrom = sqimap_find_displayable_name($msg['HEADER']['FROM']);
            $whoreplyto = sqimap_find_email($msg['HEADER']['REPLYTO']);

            if ((strtolower(trim($sendcc[$i])) != strtolower(trim($whofrom))) &&
                (strtolower(trim($sendcc[$i])) != strtolower(trim($whoreplyto))) &&
                (trim($sendcc[$i]) != '')) {
                $send_to_cc .= trim($sendcc[$i]) . ', ';
            }
        }
        $send_to_cc = trim($send_to_cc);
        if (substr($send_to_cc, -1) == ',') {
            $send_to_cc = substr($send_to_cc, 0, strlen($send_to_cc) - 1);
        }
    }
} /* function newMail() */


function getAttachments($message, $session) {
    global $mailbox, $attachments, $attachment_dir, $imapConnection,
           $ent_num, $forward_id, $draft_id, $username;

    if (isset($draft_id)) {
        $id = $draft_id;
    } else {
        $id = $forward_id;
    }

    if (!$message) {
        sqimap_mailbox_select($imapConnection, $mailbox);
        $message = sqimap_get_message($imapConnection, $id, $mailbox);
    }

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (count($message->entities) == 0) {
        if ($message->header->entity_id != $ent_num) {
            $filename = decodeHeader($message->header->filename);

            if ($filename == "") {
                $filename = "untitled-".$message->header->entity_id;
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
            $newAttachment['type'] = strtolower($message->header->type0 .
                                                '/' . $message->header->type1);
	    $newAttachment['id'] = strtolower($message->header->id);
	    $newAttachment['session'] = $session;

            /* Write Attachment to file */
            $fp = fopen ("$hashed_attachment_dir/$localfilename", 'w');
            fputs($fp, decodeBody(mime_fetch_body($imapConnection,
                $id, $message->header->entity_id),
                $message->header->encoding));
            fclose ($fp);

            $attachments[] = $newAttachment;
        }
    } else {
        for ($i = 0; $i < count($message->entities); $i++) {
            getAttachments($message->entities[$i], $session);
        }
    }
    return;
}

function showInputForm ($session) {
    global $send_to, $send_to_cc, $reply_subj, $forward_subj, $body,
           $passed_body, $color, $use_signature, $signature, $prefix_sig,
           $editor_size, $attachments, $subject, $newmail,
           $use_javascript_addr_book, $send_to_bcc, $reply_id, $mailbox,
           $from_htmladdr_search, $location_of_buttons, $attachment_dir,
           $username, $data_dir, $identity, $draft_id, $delete_draft,
           $mailprio, $default_use_mdn, $mdn_user_support, $compose_new_win,
           $saved_draft, $mail_sent, $sig_first, $edit_as_new;

    $subject = decodeHeader($subject, false);
    $reply_subj = decodeHeader($reply_subj, false);
    $forward_subj = decodeHeader($forward_subj, false);

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

    if (isset($draft_id) && !$edit_as_new) {
        echo '<input type="hidden" name="delete_draft" value="' . $draft_id . "\">\n";
    }
    if (isset($delete_draft)) {
        echo '<input type="hidden" name="delete_draft" value="' . $delete_draft. "\">\n";
    }
    if (isset($session)) {
        echo '<input type="hidden" name="session" value="' . "$session" . "\">\n";
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
    if ($reply_subj) {
        $reply_subj = str_replace('"', "'", $reply_subj);
        $reply_subj = trim($reply_subj);
        if (substr(strtolower($reply_subj), 0, 3) != 're:') {
            $reply_subj = 'Re: ' . $reply_subj;
        }
        echo '         <INPUT TYPE=text NAME=subject SIZE=60 VALUE="' .
             htmlspecialchars($reply_subj) . '">';
    }
    elseif ($forward_subj) {
        $forward_subj = trim($forward_subj);
        if ((substr(strtolower($forward_subj), 0, 4) != 'fwd:') &&
            (substr(strtolower($forward_subj), 0, 5) != '[fwd:') &&
            (substr(strtolower($forward_subj), 0, 6) != '[ fwd:')) {
            $forward_subj = '[Fwd: ' . $forward_subj . ']';
        }
        echo '         <INPUT TYPE=text NAME=subject SIZE=60 VALUE="' .
             htmlspecialchars($forward_subj) . '">';
    } else {
        echo '         <INPUT TYPE=text NAME=subject SIZE=60 VALUE="' .
             htmlspecialchars($subject) . '">';
    }
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

    if (count($attachments)) {
        $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
        echo '<tr><td bgcolor="' . $color[0] . '" align=right>' . "\n" .
             '&nbsp;' .
             '</td><td align=left bgcolor="' . $color[0] . '">';
        foreach ($attachments as $key => $info) {
	    if ($info['session'] == $session) { 
        	$attached_file = "$hashed_attachment_dir/$info[localfilename]";
        	echo '<input type="checkbox" name="delete[]" value="' . $key . "\">\n" .
                    $info['remotefilename'] . ' - ' . $info['type'] . ' (' .
                    show_readable_size(filesize($attached_file)) . ")<br>\n";
	    }
        }

        echo '<input type="submit" name="do_delete" value="' .
             _("Delete selected attachments") . "\">\n" .
             '</td></tr>';
    }
    /* End of attachment code */
    if ($compose_new_win == '1') {
        echo '</TABLE>'."\n";
    }
    echo '</TABLE>' . "\n";
    if ($reply_id) {
        echo '<input type=hidden name=reply_id value=' . $reply_id . ">\n";
    }
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

    echo "   <TR><td>\n   </td><td>\n";
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
    global $HTTP_POST_FILES, $attachment_dir, $attachments, $username;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    $localfilename = GenerateRandomString(32, '', 7);
    $full_localfilename = "$hashed_attachment_dir/$localfilename";
    while (file_exists($full_localfilename)) {
        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
    }

    if (!@rename($HTTP_POST_FILES['attachfile']['tmp_name'], $full_localfilename)) {
        if (!@copy($HTTP_POST_FILES['attachfile']['tmp_name'], $full_localfilename)) {
            return true;
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
}


function ClearAttachments($session)
{
    global $username, $attachments, $attachment_dir;
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
}


function getReplyCitation($orig_from)
{
    global $reply_citation_style, $reply_citation_start, $reply_citation_end;

    /* First, return an empty string when no citation style selected. */
    if (($reply_citation_style == '') || ($reply_citation_style == 'none')) {
        return '';
    }

    /* Decode the users name. */
    $parpos = strpos($orig_from, '(');
    if ($parpos === false) {
        $orig_from = trim(substr($orig_from, 0, strpos($orig_from, '<')));
        $orig_from = str_replace('"', '', $orig_from);
        $orig_from = str_replace("'", '', $orig_from);
    } else {
        $end_parpos = strrpos($orig_from, ')');
        $end_parpos -= ($end_parpos === false ? $end_parpos : $parpos + 1);
        $orig_from = trim(substr($orig_from, $parpos + 1, $end_parpos));
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
        $start = $reply_citation_start . ' ';
        $end   = $reply_citation_end;
        break;
    default:
        return '';
    }

    /* Build and return the citation string. */
    return ($start . $orig_from . $end . "\n");
}

?>
