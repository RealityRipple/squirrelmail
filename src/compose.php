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

if (!isset($attachments)) {
    $attachments = array();
    session_register('attachments');
}

if (!isset($mailbox) || $mailbox == '' || ($mailbox == 'None')) {
    $mailbox = 'INBOX';
}

if (isset($draft)) {
    include_once ('../src/draft_actions.php');
    if (!saveMessageAsDraft($send_to, $send_to_cc, $send_to_bcc, $subject, $body, $reply_id, $MDN)) {
        showInputForm();
        exit();
    } else {
        $draft_message = _("Draft Email Saved");
        /* If this is a resumed draft, then delete the original */
        if(isset($delete_draft)) {
            Header("Location: delete_message.php?mailbox=$draft_folder".
                   "&message=$delete_draft&sort=$sort&startMessage=1");
            exit();
        } else {
            Header("Location: right_main.php?mailbox=$draft_folder&sort=$sort".
                   "&startMessage=1&note=$draft_message");
            exit();
        }
    }
}

if (isset($send)) {
    if (isset($HTTP_POST_FILES['attachfile']) &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] != 'none') {
        $AttachFailure = saveAttachedFiles();
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
         * if the browser doesn't support "HARD" as the wrap type
         * Or, in Opera's case, something goes wrong. 
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
                sqWordWrap($line, $editor_size) . "\n";
                $newBody .= $line;
            }
        }
        $body = $newBody;

        do_hook("compose_send");
  
        $MDN = False;  // we are not sending a mdn response
        if (! isset($mailprio)) {
            $Result = sendMessage($send_to, $send_to_cc, $send_to_bcc,
                                  $subject, $body, $reply_id, $MDN);
        } else {
            $Result = sendMessage($send_to, $send_to_cc, $send_to_bcc,
                                  $subject, $body, $reply_id, $MDN, $mailprio);
        }
        if (! $Result) {
            showInputForm();
            exit();
        }
        if ( isset($delete_draft)) {
            Header("Location: delete_message.php?mailbox=$draft_folder".
                   "&message=$delete_draft&sort=$sort&startMessage=1");
            exit();
        }

        Header("Location: right_main.php?mailbox=$urlMailbox&sort=$sort".
               "&startMessage=1");
    } else {
        /*
         *$imapConnection = sqimap_login($username, $key, $imapServerAddress,
         *                               $imapPort, 0);
         */
        displayPageHeader($color, $mailbox);

        if (isset($AttachFailure)) {
             plain_error_message(_("Could not move/copy file. File not attached"),
                                 $color);
        }

        checkInput(true);
        showInputForm();
        /* sqimap_logout($imapConnection); */
    }
}
elseif (isset($html_addr_search_done)) {
    displayPageHeader($color, $mailbox);

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
    showInputForm();
}
elseif (isset($html_addr_search)) {
    if (isset($HTTP_POST_FILES['attachfile']) &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] &&
        $HTTP_POST_FILES['attachfile']['tmp_name'] != 'none') {
        if (saveAttachedFiles()) {
            plain_error_message(_("Could not move/copy file. File not attached"), $color);
        }
    }
    /*
     * I am using an include so as to elminiate an extra unnecessary
     * click.  If you can think of a better way, please implement it.
     */
    include_once('./addrbook_search_html.php');
}
elseif (isset($attach)) {
    if (saveAttachedFiles()) {
        plain_error_message(_("Could not move/copy file. File not attached"), $color);
    }
    displayPageHeader($color, $mailbox);
    showInputForm();
}
elseif (isset($do_delete)) {
    displayPageHeader($color, $mailbox);

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (isset($delete) && is_array($delete)) {
        foreach($delete as $index) {
            $attached_file = $hashed_attachment_dir . '/'
                           . $attachments[$index]['localfilename'];
            unlink ($attached_file);
            unset ($attachments[$index]);
        }
    }

    showInputForm();
} else {
    /*
     * This handles the default case as well as the error case
     * (they had the same code) --> if (isset($smtpErrors)) 
     */
    $imapConnection = sqimap_login($username, $key, $imapServerAddress,
                                   $imapPort, 0);
    displayPageHeader($color, $mailbox);

    $newmail = true;

    ClearAttachments();

    if (isset($forward_id) && $forward_id && isset($ent_num) && $ent_num) {
        getAttachments(0);
    }

    if (isset($draft_id) && $draft_id && isset($ent_num) && $ent_num) {
        getAttachments(0);
    }

    newMail();
    showInputForm();
    sqimap_logout($imapConnection);
}

exit();


/**************** Only function definitions go below *************/


/* This function is used when not sending or adding attachments */
function newMail () {
    global $forward_id, $imapConnection, $msg, $ent_num, $body_ary, $body,
           $reply_id, $send_to, $send_to_cc, $mailbox, $send_to_bcc, $editor_size,
           $draft_id, $use_signature;

    $send_to = decodeHeader($send_to, false);
    $send_to_cc = decodeHeader($send_to_cc, false);
    $send_to_bcc = decodeHeader($send_to_bcc, false);

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
        $body_ary = explode("\n", $body);
        $i = count($body_ary) - 1;
        while ($i >= 0 && ereg("^[>\\s]*$", $body_ary[$i])) {
            unset($body_ary[$i]);
            $i --;
        }
        $body = '';
        for ($i=0; isset($body_ary[$i]); $i++) {
            if ($reply_id) {
                if (ereg('^[ >]+', $body_ary[$i])) {
                    $body_ary[$i] = '>' . $body_ary[$i];
                } else {
                    $body_ary[$i] = '> ' . $body_ary[$i];
                }
            }
            if (!$draft_id) {
                sqWordWrap($body_ary[$i], $editor_size - 1);
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


function getAttachments($message) {
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
            getAttachments($message->entities[$i]);
        }
    }
    return;
}

function showInputForm () {
    global $send_to, $send_to_cc, $reply_subj, $forward_subj, $body,
           $passed_body, $color, $use_signature, $signature, $prefix_sig,
           $editor_size, $attachments, $subject, $newmail,
           $use_javascript_addr_book, $send_to_bcc, $reply_id, $mailbox,
           $from_htmladdr_search, $location_of_buttons, $attachment_dir,
           $username, $data_dir, $identity, $draft_id, $delete_draft,
           $mailprio, $default_use_mdn, $mdn_user_support;

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

    if (isset($draft_id)) {
        echo '<input type="hidden" name="delete_draft" value="' . $draft_id . "\">\n";
    }
    if (isset($delete_draft)) {
        echo '<input type="hidden" name="delete_draft" value="' . $delete_draft. "\">\n";
    }

    echo '<TABLE WIDTH="100%" ALIGN=center CELLSPACING=0 BORDER=0>' . "\n";

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

    echo '   <TR>' . "\n" .
         '      <TD BGCOLOR="' . $color[4] . '" COLSPAN=2>' . "\n" .
         '         &nbsp;&nbsp;<TEXTAREA NAME=body ROWS=20 COLS="' .
         $editor_size . '" WRAP=HARD>' . htmlspecialchars($body);
    if ($use_signature == true && $newmail == true && !isset($from_htmladdr_search)) {
        echo "\n\n".($prefix_sig==true? "-- \n":'').htmlspecialchars($signature);
    }
    echo '</TEXTAREA><BR>' . "\n" .
         '      </TD>' . "\n" .
         '   </TR>' . "\n";

    if ($location_of_buttons == 'bottom') {
        showComposeButtonRow();
    } else {
        echo '   <TR><TD>&nbsp;</TD><TD ALIGN=LEFT><INPUT TYPE=SUBMIT ' .
             'NAME=send VALUE="' . _("Send") . '"></TD></TR>' . "\n";
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
            $attached_file = "$hashed_attachment_dir/$info[localfilename]";
            echo '<input type="checkbox" name="delete[]" value="' . $key . "\">\n" .
                 $info['remotefilename'] . ' - ' . $info['type'] . ' (' .
                 show_readable_size(filesize($attached_file)) . ")<br>\n";
        }

        echo '<input type="submit" name="do_delete" value="' .
             _("Delete selected attachments") . "\">\n" .
             '</td></tr>';
    }
    /* End of attachment code */

    echo '</TABLE>' . "\n";
    if ($reply_id) {
        echo '<input type=hidden name=reply_id value=' . $reply_id . ">\n";
    }
    echo '<INPUT TYPE=hidden NAME=mailbox VALUE="' . htmlspecialchars($mailbox) .
         "\">\n" .
         '</FORM>';
    do_hook("compose_bottom");
    echo '</BODY></HTML>' . "\n";
}


function showComposeButtonRow()
{
    global $use_javascript_addr_book, $save_as_draft,
           $default_use_priority, $mailprio, $default_use_mdn;

    echo "   <TR><td>\n   </td><td>\n";
    if ($use_javascript_addr_book) {
        echo "      <SCRIPT LANGUAGE=JavaScript><!--\n document.write(\"";
        echo "         <input type=button value=\\\""._("Addresses").
                              "\\\" onclick='javascript:open_abook();'>\");";
        echo "         // --></SCRIPT><NOSCRIPT>\n";
        echo "         <input type=submit name=\"html_addr_search\" value=\"".
                              _("Addresses")."\">";
        echo "      </NOSCRIPT>\n";
    } else {
        echo "      <input type=submit name=\"html_addr_search\" value=\"".
                              _("Addresses")."\">";
    }
    echo "\n    <INPUT TYPE=SUBMIT NAME=send VALUE=\"". _("Send") . "\">\n";

    if ($save_as_draft) {
        echo '<input type="submit" name ="draft" value="' . _("Save Draft") . "\">\n";
    }
    if ($default_use_priority) {
        if(!isset($mailprio)) {
            $mailprio = "3";
        }
        echo "\n\t". _("Priority") .':<select name="mailprio">'.
            "\n\t\t<option value=1".($mailprio=='1'?' selected':'').'>'. _("High") .'</option>'.
            "\n\t\t<option value=3".($mailprio=='3'?' selected':'').'>'. _("Normal") .'</option>'.
            "\n\t\t<option value=5".($mailprio=='5'?' selected':'').'>'. _("Low").'</option>'.
            "\n\t</select>";
    }

    $mdn_user_support=getPref($data_dir, $username, 'mdn_user_support',$default_use_mdn);
    if ($default_use_mdn) {
        if ($mdn_user_support) {    
            echo _("Confirm reading:").
                "<input type=\"checkbox\" name=\"request_mdn\" value=1>";
        }
    }

    do_hook('compose_button_row');

    echo "   </TD>\n";
    echo "   </TR>\n\n";
}

function checkInput ($show)
{
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
function saveAttachedFiles() {
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

    if ($newAttachment['type'] == "") {
         $newAttachment['type'] = 'application/octet-stream';
    }

    $attachments[] = $newAttachment;
}




function ClearAttachments()
{
    global $username, $attachments, $attachment_dir;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    foreach ($attachments as $info) {
        $attached_file = "$hashed_attachment_dir/$info[localfilename]";
        if (file_exists($attached_file)) {
            unlink($attached_file);
        }
    }

    $attachments = array();
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
        $start = $reply_citation_start;
        $end   = $reply_citation_end;
        break;
    default:
        return '';
    }

    /* Build and return the citation string. */
    return ($start . $orig_from . $end . "\n");
}

?>
