<?php

/**
 * retrievalerror.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Submits a message which Squirrelmail couldn't handle
 * because of malformedness of the message
 * sends it to retrievalerror@squirrelmail.org
 * Of course, this only happens when the end user has chosen to do so
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/smtp.php');
require_once('../functions/page_header.php');
require_once('../src/load_prefs.php');

$destination = 'retrievalerror@squirrelmail.org';
$attachments = array();

function ClearAttachments() {
    global $attachments, $attachment_dir, $username;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    foreach ($attachments as $info) {
        $attached_file = "$hashed_attachment_dir/$info[localfilename]";
        if (file_exists($attached_file)) {
            unlink($attached_file);
        }
    }

    $attachments = array();
}

$imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imap_stream, $mailbox);
$sid = sqimap_session_id();
fputs ($imap_stream, "$sid FETCH $passed_id BODY[]\r\n");
$data = sqimap_read_data ($imap_stream, $sid, true, $response, $message);
sqimap_logout($imap_stream);
$topline2 = array_shift($data);
$thebastard = implode('', $data);

$hashed_attachment_dir = getHashedDir($username, $attachment_dir);
$localfilename = GenerateRandomString(32, '', 7);
$full_localfilename = "$hashed_attachment_dir/$localfilename";
while (file_exists($full_localfilename)) {
    $localfilename = GenerateRandomString(32, '', 7);
    $full_localfilename = "$hashed_attachment_dir/$localfilename";
}

/* Write Attachment to file */
$fp = fopen ($full_localfilename, 'w');
fputs ($fp, $thebastard);
fclose ($fp);

$newAttachment = array();
$newAttachment['localfilename'] = $localfilename;
$newAttachment['remotefilename'] = 'message.duh';
$newAttachment['type'] = 'application/octet-stream';
$attachments[] = $newAttachment;

$body = "Response: $response\n" .
        "Message: $message\n" .
        "FETCH line: $topline\n" .
        "Server Type: $imap_server_type\n";

$imap_stream = fsockopen ($imapServerAddress, $imapPort, &$error_number, &$error_string);
$server_info = fgets ($imap_stream, 1024);
if ($imap_stream) {
    $body .=  "  Server info:  $server_info";
    fputs ($imap_stream, "a001 CAPABILITY\r\n");
    $read = fgets($imap_stream, 1024);
    $list = explode(' ', $read);
    array_shift($list);
    array_shift($list);
    $read = implode(' ', $list);
    $body .= "  Capabilities:  $read";
    fputs ($imap_stream, "a002 LOGOUT\r\n");
    fclose($imap_stream);
}

$body .= "\nFETCH line for gathering the whole message: $topline2\n";

sendMessage($destination, '', '', 'submitted message', $body, False, 0);

displayPageHeader($color, $mailbox);

$par = 'mailbox='.urlencode($mailbox)."&amp;passed_id=$passed_id";
if (isset($where) && isset($what)) {
    $par .= '&amp;where='.urlencode($where).'&amp;what='.urlencode($what);
} else {
    $par .= "&amp;startMessage=$startMessage&amp;show_more=0";
}

echo '<BR>The message has been submitted to the developers knowledgebase!<BR>' .
     'Thank you very much<BR>' .
     'Please submit every message only once<BR>' .
     "<A HREF=\"../src/read_body.php?$par\">View the message</A><BR>";

?>
