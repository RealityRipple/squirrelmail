<?php

   /**
    **  retrievalerror.php
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Submits a message which Squirrelmail couldn't handle
    **  because of malformedness of the message
    **  sends it to retrievalerror@squirrelmail.org
    **  Of course, this only happens when the end user has chosen to do so
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once("../functions/imap.php");
   require_once("../functions/smtp.php");
   require_once("../functions/page_header.php");
   require_once("../src/load_prefs.php");

   $destination = "retrievalerror@squirrelmail.org";

   $attachments = array();


   function ClearAttachments() {
       global $attachments, $attachment_dir;

       foreach ($attachments as $info) {
           if (file_exists($attachment_dir . $info['localfilename'])) {
               unlink($attachment_dir . $info['localfilename']);
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



   $localfilename = GenerateRandomString(32, '', 7);
   while (file_exists($attachment_dir . $localfilename))
       $localfilename = GenerateRandomString(32, '', 7);
   // Write Attachment to file
   $fp = fopen ($attachment_dir.$localfilename, 'w');
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
   if ($imap_stream)
   {
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


   sendMessage($destination, "", "", "submitted message", $body, 0);




   displayPageHeader($color, $mailbox);

   $par = "mailbox=".urlencode($mailbox)."&passed_id=$passed_id";
   if (isset($where) && isset($what)) {
       $par .= "&where=".urlencode($where)."&what=".urlencode($what);
   } else {
       $par .= "&startMessage=$startMessage&show_more=0";
   }

   echo '<BR>The message has been submitted to the developers knowledgebase!<BR>' .
        'Thank you very much<BR>' .
        'Please submit every message only once<BR>' .
        "<A HREF=\"../src/read_body.php?$par\">View the message</A><BR>";

?>
