<?php

/**
 * draft_actions.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

require_once ('../src/validate.php');

/* Print all the needed RFC822 headers */
function write822HeaderForDraft ($fp, $t, $c, $b, $subject, $more_headers, $session) {
    global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
    global $data_dir, $username, $popuser, $domain, $version, $useSendmail;
    global $default_charset, $HTTP_VIA, $HTTP_X_FORWARDED_FOR;
    global $REMOTE_HOST, $identity;

    /* Storing the header to make sure the header is the same */
    /* everytime the header is printed. */
    static $header, $headerlength;

    if ($header == '') {
        if (isset($identity) && ($identity != 'default')) {
            $reply_to = getPref($data_dir, $username, 'reply_to' . $identity);
            $from = getPref($data_dir, $username, 'full_name' . $identity);
            $from_addr = getPref($data_dir, $username, 'email_address' . $identity);
        } else {
            $reply_to = getPref($data_dir, $username, 'reply_to');
            $from = getPref($data_dir, $username, 'full_name');
            $from_addr = getPref($data_dir, $username, 'email_address');
        }

        if ($from_addr == '') {
            $from_addr = $popuser.'@'.$domain;
        }

        /* Encoding 8-bit characters and making from line */
        $subject = encodeHeader($subject);
        if ($from == '') {
            $from = "<$from_addr>";
        } else {
            $from = '"' . encodeHeader($from) . "\" <$from_addr>";
        }

        /* This creates an RFC 822 date */
        $date = date("D, j M Y H:i:s ", mktime()) . timezone();

        /* Create a message-id */
        $message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
        $message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';

        /* Insert header fields */
        $header = "Message-ID: $message_id\r\n";
        $header .= "Date: $date\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "From: $from\r\n";
        $header .= "To: $t\r\n";    // Who it's TO

        /* Insert headers from the $more_headers array */
        if(is_array($more_headers)) {
            reset($more_headers);
            while(list($h_name, $h_val) = each($more_headers)) {
               $header .= sprintf("%s: %s\r\n", $h_name, $h_val);
            }
        }

        if ($c) {
            $header .= "Cc: $c\r\n"; // Who the CCs are
        }

        if ($b) {
            $header .= "Bcc: $b\r\n"; // Who the BCCs are
        }

        if ($reply_to != '') {
            $header .= "Reply-To: $reply_to\r\n";
        }

        $header .= "X-Mailer: SquirrelMail (version $version)\r\n"; // Identify SquirrelMail

        /* Do the MIME-stuff */
        $header .= "MIME-Version: 1.0\r\n";

        if (isMultipart($session)) {
            $header .= 'Content-Type: multipart/mixed; boundary="';
            $header .= mimeBoundary();
            $header .= "\"\r\n";
        } else {
            if ($default_charset != '')
                $header .= "Content-Type: text/plain; charset=$default_charset\r\n";
            else
                $header .= "Content-Type: text/plain;\r\n";
            $header .= "Content-Transfer-Encoding: 8bit\r\n";
        }
        $header .= "\r\n"; // One blank line to separate header and body

        $headerlength = strlen($header);
    }

    /* Write the header */
    fputs ($fp, $header);

    return $headerlength;
}

/* Send the body */
function writeBodyForDraft ($fp, $passedBody, $session) {
    global $default_charset;

    $attachmentlength = 0;

    if (isMultipart($session)) {
        $body = '--'.mimeBoundary()."\r\n";

        if ($default_charset != ""){
            $body .= "Content-Type: text/plain; charset=$default_charset\r\n";
        } else {
            $body .= "Content-Type: text/plain\r\n";
        }

        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $passedBody . "\r\n\r\n";
        fputs ($fp, $body);

        $attachmentlength = attachFiles($fp, $session);

        if (!isset($postbody)) $postbody = "";
        $postbody .= "\r\n--".mimeBoundary()."--\r\n\r\n";
        fputs ($fp, $postbody);
    } else {
        $body = $passedBody . "\r\n";
        fputs ($fp, $body);
        $postbody = "\r\n";
        fputs ($fp, $postbody);
    }

    return (strlen($body) + strlen($postbody) + $attachmentlength);
}


function saveMessageAsDraft($t, $c, $b, $subject, $body, $reply_id, $prio = 3, $session) {
    global $useSendmail, $msg_id, $is_reply, $mailbox, $onetimepad,
           $data_dir, $username, $domain, $key, $version, $sent_folder,
           $imapServerAddress, $imapPort, $draft_folder, $attachment_dir,
           $default_use_priority;
    $more_headers = Array();

    if ($default_use_priority) {
        $more_headers = array_merge($more_headers, createPriorityHeaders($prio));
    }

    $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 1);

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $tmpDraftFile = "draft-" . GenerateRandomString(32, '', 7);
    $full_tmpDraftFile = "$hashed_attachment_dir/$tmpDraftFile";
    while (file_exists($full_tmpDraftFile)){
         $tmpDraftFile = "draft-" . GenerateRandomString(32, '', 7);
         $full_tmpDraftFile = "$hashed_attachment_dir/$tmpDraftFile";
    }
    $fp = fopen($full_tmpDraftFile, 'wb');

    $headerlength = write822HeaderForDraft
        ($fp, $t, $c, $b, $subject, $more_headers, $session);
    $bodylength = writeBodyForDraft ($fp, $body, $session);
    fclose($fp);

    $length = ($headerlength + $bodylength);

    if (sqimap_mailbox_exists ($imap_stream, $draft_folder)) {
        sqimap_append ($imap_stream, $draft_folder, $length);
        write822HeaderForDraft
            ($imap_stream, $t, $c, $b, $subject, $more_headers, $session);
        writeBodyForDraft ($imap_stream, $body, $session);
        sqimap_append_done ($imap_stream);
    }
    sqimap_logout($imap_stream);
    if ($length){
        ClearAttachments($session);
    }
    if (file_exists($full_tmpDraftFile)){
        unlink ($full_tmpDraftFile);
    }
    return $length;
}

?>
