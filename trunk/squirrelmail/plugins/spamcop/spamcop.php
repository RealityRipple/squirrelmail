<?php

    chdir('..');
    include_once ('../src/validate.php');
    include_once ('../functions/imap.php');
    
    displayPageHeader($color, $mailbox);

    $imap_stream = sqimap_login($username, $key, $imapServerAddress, 
       $imapPort, 0);
    sqimap_mailbox_select($imap_stream, $mailbox);
    fputs($imap_stream, 'a010 FETCH ' . $passed_id . ' RFC822' . "\r\n");
    $sid = 'a010';
    if ($uid_support) $sid .= ' UID';
    
    $read = sqimap_read_data($imap_stream, $sid, true, $response, $message);
    array_shift($read);

    if ($spamcop_method == 'quick_email' || 
        $spamcop_method == 'thorough_email') {
       // Use email-based reporting -- save as an attachment
       if(!isset($composesession)) {
        $composesession = 0;
        session_register('composesession');
       }
       if (!isset($session)) {
         $session = "$composesession" +1;
           $composesession = $session;
       }

       if (!isset($attachments)) {
          $attachments = array();
          session_register('attachments');
       }
    
       foreach ($attachments as $info) {
          if (file_exists($attachment_dir . $info['localfilename']))
             unlink($attachment_dir . $info['localfilename']);
       }
       $attachments = array();

       $file = GenerateRandomString(32, '', 7);
       while (file_exists($attachment_dir . $file))
           $file = GenerateRandomString(32, '', 7);
       $newAttachment['localfilename'] = $file;
       $newAttachment['remotefilename'] = 'email.txt';
       $newAttachment['type'] = 'message/rfc822';
       $newAttachment['session'] = $session;
       $fp = fopen($attachment_dir . $file, 'w');
       foreach ($read as $line) {
          fputs($fp, $line);
       }
       $attachments[] = $newAttachment;
    
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
?>  <form method=post action="../../src/compose.php">
  <input type=hidden name="mailbox" value="<?PHP echo
     htmlspecialchars($mailbox) ?>">
  <input type=hidden name="spamcop_is_composing" value="<?PHP echo
     htmlspecialchars($passed_id) ?>">
  <input type=hidden name="send_to" value="<?PHP echo $report_email ?>">
  <input type=hidden name="send_to_cc" value="">
  <input type=hidden name="send_to_bcc" value="">
  <input type=hidden name="subject" value="reply anyway">
  <input type=hidden name="identity" value="default">
  <input type=hidden name="session" value="<?PHP echo $session?>">
  <input type=submit name="send" value="Send Spam Report">
<?PHP } else {
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
