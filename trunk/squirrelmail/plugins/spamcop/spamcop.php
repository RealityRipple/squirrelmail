<?php
   /** 
    **  spamcop.php -- SpamCop plugin           
    **
    **  Copyright (c) 1999-2003 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **  
    **  $Id$                                                         
    **/

define('SM_PATH','../../');

 /* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');

function getMessage_RFC822_Attachment($message, $composeMessage, $passed_id, 
                                      $passed_ent_id='', $imapConnection) {
    global $attachments, $attachment_dir, $username, $data_dir, $uid_support;
    
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!$passed_ent_id) {
        $body_a = sqimap_run_command($imapConnection, 
                                    'FETCH '.$passed_id.' RFC822',
                                    TRUE, $response, $readmessage, 
                                    $uid_support);
    } else {
        $body_a = sqimap_run_command($imapConnection, 
                                     'FETCH '.$passed_id.' BODY['.$passed_ent_id.']',
                                     TRUE, $response, $readmessage, $uid_support);
        $message = $message->parent;
    }
    if ($response = 'OK') {
        $subject = encodeHeader($message->rfc822_header->subject);
        array_shift($body_a);
        $body = implode('', $body_a) . "\r\n";
                
        $localfilename = GenerateRandomString(32, 'FILE', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
        $fp = fopen( $full_localfilename, 'w');
        fwrite ($fp, $body);
        fclose($fp);
	
        /* dirty relative dir fix */
        if (substr($attachment_dir,0,3) == '../') {
	   $attachment_dir = substr($attachment_dir,3);
	   $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
        }
	$full_localfilename = "$hashed_attachment_dir/$localfilename";

	$composeMessage->initAttachment('message/rfc822','email.txt', 
	                 $full_localfilename);
    }
    return $composeMessage;
}


    /* GLOBALS */
    $username = $_SESSION['username'];
    $key  = $_COOKIE['key'];
    $onetimepad = $_SESSION['onetimepad'];
    $mailbox = $_GET['mailbox'];
    $passed_id = $_GET['passed_id'];
    if (isset($_GET['startMessage'])) {
	$startMessage = $_GET['startMessage'];
    } else {
	$startMessage = 1;
    }
    if (isset($_GET['passed_ent_id'])) {
	$passed_ent_id = $_GET['passed_ent_id'];
    } else {
	$passed_ent_id = '';
    }
    if ( isset($_SESSION['compose_messages']) ) {
        $compose_messages = &$_SESSION['compose_messages'];
    }

    if ( isset($_SESSION['composesession']) ) {
        $composesession = $_SESSION['composesession'];
    } else {
        $composesession = 0;
        sqsession_register($composesession, 'composesession');
    }
    /* END GLOBALS */

    
    displayPageHeader($color, $mailbox);

    $imap_stream = sqimap_login($username, $key, $imapServerAddress, 
       $imapPort, 0);
    sqimap_mailbox_select($imap_stream, $mailbox);

    if ($spamcop_method == 'quick_email' || 
        $spamcop_method == 'thorough_email') {
       // Use email-based reporting -- save as an attachment
       $session = "$composesession"+1;
       $composesession = $session;
       sqsession_register($composesession,'composesession');
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


        $message = sqimap_get_message($imap_stream, $passed_id, $mailbox);
        $composeMessage = getMessage_RFC822_Attachment($message, $composeMessage, $passed_id, 
                                      $passed_ent_id='', $imap_stream);

    	$compose_messages[$session] = $composeMessage;
	sqsession_register($compose_messages, 'compose_messages');

        $fn = getPref($data_dir, $username, 'full_name');
        $em = getPref($data_dir, $username, 'email_address');

        $HowItLooks = $fn . ' ';
        if ($em != '')
          $HowItLooks .= '<' . $em . '>';
     }

?>

<p>Sending this spam report will give you back a reply with URLs that you
can click on to properly report this spam message to the proper authorities.
This is a free service.  By pressing the "Send Spam Report" button, you
agree to follow SpamCop's rules/terms of service/etc.</p>

<table align=center width="75%" border=0 cellpadding=0 cellspacing=0>
<tr>
<td align=left valign=top>
<?PHP if (isset($js_web) && $js_web) {
   ?><form method=post action="javascript:return false">
  <input type=button value="Close Window" 
  onClick="window.close(); return true;">
   <?PHP
} else {
   ?><form method=post action="../../src/right_main.php">
  <input type=hidden name="mailbox" value="<?PHP echo
     htmlspecialchars($mailbox) ?>">
  <input type=hidden name="startMessage" value="<?PHP echo
     htmlspecialchars($startMessage) ?>">
  <input type=submit value="Cancel / Done">
   <?PHP
}
  ?></form>
</td>
<td align=right valign=top>
<?PHP if ($spamcop_method == 'thorough_email' ||
          $spamcop_method == 'quick_email') {
   if ($spamcop_method == 'thorough_email')
      $report_email = 'submit.' . $spamcop_id . '@spam.spamcop.net';
   else
      $report_email = 'quick.' . $spamcop_id . '@spam.spamcop.net';
   $form_action = SM_PATH . 'src/compose.php';
?>  <form method=post action="<?PHP echo $form_action?>">
  <input type=hidden name="mailbox" value="<?PHP echo
     htmlspecialchars($mailbox) ?>">
  <input type=hidden name="spamcop_is_composing" value="<?PHP echo
     htmlspecialchars($passed_id) ?>">
  <input type=hidden name="send_to" value="<?PHP echo $report_email?>">
  <input type=hidden name="send_to_cc" value="">
  <input type=hidden name="send_to_bcc" value="">
  <input type=hidden name="subject" value="reply anyway">
  <input type=hidden name="identity" value="default">
  <input type=hidden name="session" value="<?PHP echo $session?>">
  <input type=submit name="send" value="Send Spam Report">
<?PHP } else {
   $sid = sqimap_session_id($uid_support);
   fputs($imap_stream, $sid.' FETCH ' . $passed_id . ' RFC822' . "\r\n");
    
   $read = sqimap_read_data($imap_stream, $sid, true, $response, $message);
   array_shift($read);

   $Message = implode('', $read);
   if (strlen($Message) > 50000) {
      $Warning = "\n[truncated by SpamCop]\n";
      $Message = substr($Message, 0, 50000 - strlen($Warning)) . $Warning;
   }
   if (isset($js_web) && $js_web) {
?>  <form method=post action="http://spamcop.net/sc" name="submitspam"
    enctype="multipart/form-data"><?PHP
   } else {
?>  <form method=post action="http://spamcop.net/sc" name="submitspam"
    enctype="multipart/form-data" target="_blank"><?PHP
   } ?>
  <input type=hidden name=action value=submit>
  <input type=hidden name=oldverbose value=1>
  <input type=hidden name=code value="<?PHP echo $spamcop_id ?>">
  <input type=hidden name=spam value="<?PHP
          echo htmlspecialchars($Message);
  ?>">
  <input type=submit name="x1" value="Send Spam Report">
<?PHP }
?>  </form>
</td>
</tr>
</table>
  </body>
</html>
