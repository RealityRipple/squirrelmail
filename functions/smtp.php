<?php
   /** smtp.php
    **
    ** This contains all the functions needed to send messages through
    ** an smtp server or sendmail.
    **/

   $smtp_php = true;

   // This should most probably go to some initialization...
   if (ereg("^([^@%/]+)[@%/](.+)$", $username, $usernamedata)) {
      $popuser = $usernamedata[1];
      $domain  = $usernamedata[2];
	  unset($usernamedata);
   } else {
      $popuser = $username;
   }
   // We need domain for smtp
   if (!$domain)
      $domain = getenv("HOSTNAME");

   // Returns true only if this message is multipart
   function isMultipart () {
      global $attachments;
      
      if (count($attachments)>0)
         return true;
      else
         return false;
   }

   // Attach the files that are due to be attached
   function attachFiles ($fp) {
      global $attachments, $attachment_dir;

      $length = 0;

      if (isMultipart()) {
         reset($attachments);
         while (list($localname, $remotename) = each($attachments)) {
            // This is to make sure noone is giving a filename in another
            // directory
            $localname = ereg_replace ("\\/", "", $localname);
            
            $fileinfo = fopen ($attachment_dir.$localname.".info", "r");
            $filetype = fgets ($fileinfo, 8192);
            fclose ($fileinfo);
            $filetype = trim ($filetype);
            if ($filetype=="")
               $filetype = "application/octet-stream";
            
            $header = "--".mimeBoundary()."\r\n";
            $header .= "Content-Type: $filetype;name=\"$remotename\"\r\n";
            $header .= "Content-Disposition: attachment; filename=\"$remotename\"\r\n";
            $header .= "Content-Transfer-Encoding: base64\r\n\r\n";
            fputs ($fp, $header);
            $length += strlen($header);
            
            $file = fopen ($attachment_dir.$localname, "r");
            while ($tmp = fread($file, 570)) {
               $encoded = chunk_split(base64_encode($tmp));
               $length += strlen($encoded);
               fputs ($fp, $encoded);
            }
            fclose ($file);
         }
      }

      return $length;
   }

   // Delete files that are uploaded for attaching
   function deleteAttachments() {
      global $attachments, $attachment_dir;

      if (isMultipart()) {
         reset($attachments);
         while (list($localname, $remotename) = each($attachments)) {
            if (!ereg ("\\/", $localname)) {
               unlink ($attachment_dir.$localname);
               unlink ($attachment_dir.$localname.".info");
            }
         }
      }
   }

   // Return a nice MIME-boundary
   function mimeBoundary () {
      static $mimeBoundaryString;

      if ($mimeBoundaryString == "") {
         $mimeBoundaryString = GenerateRandomString(70, '\'()+,-./:=?_', 7);
      }

      return $mimeBoundaryString;
   }

   /* Time offset for correct timezone */
   function timezone () {
      global $invert_time;
      
      $diff_second = date("Z");
      if ($invert_time)
          $diff_second = - $diff_second;
      if ($diff_second > 0)
         $sign = "+";
      else
         $sign = "-";

      $diff_second = abs($diff_second);

      $diff_hour = floor ($diff_second / 3600);
      $diff_minute = floor (($diff_second-3600*$diff_hour) / 60);

      $zonename = "(".strftime("%Z").")";
      $result = sprintf ("%s%02d%02d %s", $sign, $diff_hour, $diff_minute, $zonename);
      return ($result);
   }

   /* Print all the needed RFC822 headers */
   function write822Header ($fp, $t, $c, $b, $subject, $more_headers) {
      global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
      global $data_dir, $username, $popuser, $domain, $version, $useSendmail;
      global $default_charset, $HTTP_VIA, $HTTP_X_FORWARDED_FOR;
      global $REMOTE_HOST;

      // Storing the header to make sure the header is the same
      // everytime the header is printed.
      static $header, $headerlength;

      if ($header == "") {
         $to = parseAddrs($t);
         $cc = parseAddrs($c);
         $bcc = parseAddrs($b);
         $reply_to = getPref($data_dir, $username, "reply_to");
         $from = getPref($data_dir, $username, "full_name");
         $from_addr = getPref($data_dir, $username, "email_address");

         if ($from_addr == "")
            $from_addr = $popuser."@".$domain;
         
         $to_list = getLineOfAddrs($to);
         $cc_list = getLineOfAddrs($cc);
         $bcc_list = getLineOfAddrs($bcc);

         /* Encoding 8-bit characters and making from line */
         $subject = sqStripSlashes(encodeHeader($subject));
         if ($from == "")
            $from = "<$from_addr>";
         else
            $from = "\"" . encodeHeader($from) . "\" <$from_addr>";
         
         /* This creates an RFC 822 date */
         $date = date("D, j M Y H:i:s ", mktime()) . timezone();

         /* Create a message-id */
         $message_id = "<" . $REMOTE_PORT . "." . $REMOTE_ADDR . ".";
         $message_id .= time() . ".squirrel@" . $SERVER_NAME .">";
         
         /* Make an RFC822 Received: line */
         if (isset($REMOTE_HOST))
            $received_from = "$REMOTE_HOST ([$REMOTE_ADDR])";
         else
            $received_from = $REMOTE_ADDR;
    
         if (isset($HTTP_VIA) || isset ($HTTP_X_FORWARDED_FOR)) {
            if ($HTTP_X_FORWARDED_FOR == "")
               $HTTP_X_FORWARDED_FOR = "unknown";
            $received_from .= " (proxying for $HTTP_X_FORWARDED_FOR)";
         }            
    
         $header  = "Received: from $received_from\r\n";
         $header .= "        (SquirrelMail authenticated user $username)\r\n";
         $header .= "        by $SERVER_NAME with HTTP;\r\n";
         $header .= "        $date\r\n";
         
         /* Insert the rest of the header fields */
         $header .= "Message-ID: $message_id\r\n";
         $header .= "Date: $date\r\n";
         $header .= "Subject: $subject\r\n";
         $header .= "From: $from\r\n";
         $header .= "To: $to_list \r\n";    // Who it's TO

	 /* Insert headers from the $more_headers array */
	 if(is_array($more_headers)) {
	    reset($more_headers);
	    while(list($h_name, $h_val) = each($more_headers)) {
	       $header .= sprintf("%s: %s\r\n", $h_name, $h_val);
	    }
	 }

         if ($cc_list) {
            $header .= "Cc: $cc_list\r\n"; // Who the CCs are
         }
         
         if ($reply_to != "")
            $header .= "Reply-To: $reply_to\r\n";
         
         if ($useSendmail) {
            if ($bcc_list) {
               // BCCs is removed from header by sendmail
               $header .= "Bcc: $bcc_list\r\n"; 
            }
         }
         
         $header .= "X-Mailer: SquirrelMail (version $version)\r\n"; // Identify SquirrelMail
         
         // Do the MIME-stuff
         $header .= "MIME-Version: 1.0\r\n";
         
         if (isMultipart()) {
            $header .= "Content-Type: multipart/mixed; boundary=\"";
            $header .= mimeBoundary();
            $header .= "\"\r\n";
         } else {
            if ($default_charset != "")
               $header .= "Content-Type: text/plain; charset=$default_charset\r\n";
            else
               $header .= "Content-Type: text/plain;\r\n";
            $header .= "Content-Transfer-Encoding: 8bit\r\n";
         }
         $header .= "\r\n"; // One blank line to separate header and body

         $headerlength = strlen($header);
      }     
      
      // Write the header
      fputs ($fp, $header);

      return $headerlength;
   }

   // Send the body
   function writeBody ($fp, $passedBody) {
      global $default_charset;

      $attachmentlength = 0;
      
      if (isMultipart()) {
         $body = "--".mimeBoundary()."\r\n";

         if ($default_charset != "")
            $body .= "Content-Type: text/plain; charset=$default_charset\r\n";
         else 
            $body .= "Content-Type: text/plain\r\n";

         $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
         $body .= sqStripSlashes($passedBody) . "\r\n";
         fputs ($fp, $body);

         $attachmentlength = attachFiles($fp);

         $postbody .= "\r\n--".mimeBoundary()."--\r\n\r\n";
         fputs ($fp, $postbody);
      } else {
         $body = sqStripSlashes($passedBody) . "\r\n";
         fputs ($fp, $body);
         $postbody = "\r\n";
         fputs ($fp, $postbody);
      }

      return (strlen($body) + strlen($postbody) + $attachmentlength);
   }

   // Send mail using the sendmail command
   function sendSendmail($t, $c, $b, $subject, $body, $more_headers) {
      global $sendmail_path, $popuser, $username, $domain;

      // Build envelope sender address. Make sure it doesn't contain 
      // spaces or other "weird" chars that would allow a user to
      // exploit the shell/pipe it is used in.
      $envelopefrom = "$popuser@$domain";
      $envelopefrom = ereg_replace("[[:blank:]]","", $envelopefrom);
      $envelopefrom = ereg_replace("[[:space:]]","", $envelopefrom);
      $envelopefrom = ereg_replace("[[:cntrl:]]","", $envelopefrom);

      // open pipe to sendmail
      $fp = popen (escapeshellcmd("$sendmail_path -t -f$envelopefrom"), "w");
      
      $headerlength = write822Header ($fp, $t, $c, $b, $subject, $more_headers);
      $bodylength = writeBody($fp, $body);

      pclose($fp);

      return ($headerlength + $bodylength);
   }

   function smtpReadData($smtpConnection) {
      $read = fgets($smtpConnection, 1024);
      $counter = 0;
      while ($read) {
         echo $read . "<BR>";
         $data[$counter] = $read;
         $read = fgets($smtpConnection, 1024);
         $counter++;
      }
   }

   function sendSMTP($t, $c, $b, $subject, $body, $more_headers) {
      global $username, $popuser, $domain, $version, $smtpServerAddress, $smtpPort,
         $data_dir, $color;

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $from_addr = getPref($data_dir, $username, "email_address");

      if (!$from_addr)
         $from_addr = "$popuser@$domain";

      $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      $tmp = fgets($smtpConnection, 1024);
      errorCheck($tmp, $smtpConnection);

      $to_list = getLineOfAddrs($to);
      $cc_list = getLineOfAddrs($cc);

      /** Lets introduce ourselves */
      fputs($smtpConnection, "HELO $domain\r\n");
      $tmp = fgets($smtpConnection, 1024);
      errorCheck($tmp, $smtpConnection);

      /** Ok, who is sending the message? */
      fputs($smtpConnection, "MAIL FROM:<$from_addr>\r\n");
      $tmp = fgets($smtpConnection, 1024);
      errorCheck($tmp, $smtpConnection);

      /** send who the recipients are */
      for ($i = 0; $i < count($to); $i++) {
         fputs($smtpConnection, "RCPT TO:<$to[$i]>\r\n");
         $tmp = fgets($smtpConnection, 1024);
         errorCheck($tmp, $smtpConnection);
      }
      for ($i = 0; $i < count($cc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$cc[$i]>\r\n");
         $tmp = fgets($smtpConnection, 1024);
         errorCheck($tmp, $smtpConnection);
      }
      for ($i = 0; $i < count($bcc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$bcc[$i]>\r\n");
         $tmp = fgets($smtpConnection, 1024);
         errorCheck($tmp, $smtpConnection);
      }

      /** Lets start sending the actual message */
      fputs($smtpConnection, "DATA\r\n");
      $tmp = fgets($smtpConnection, 1024);
      errorCheck($tmp, $smtpConnection);

      // Send the message
      $headerlength = write822Header ($smtpConnection, $t, $c, $b, $subject, $more_headers);
      $bodylength = writeBody($smtpConnection, $body);

      fputs($smtpConnection, ".\r\n"); // end the DATA part
      $tmp = fgets($smtpConnection, 1024);
      $num = errorCheck($tmp, $smtpConnection);
      if ($num != 250) {
	 $tmp = nl2br(htmlspecialchars($tmp));
         echo "ERROR<BR>Message not sent!<BR>Reason given: $tmp<BR></BODY></HTML>";
      }

      fputs($smtpConnection, "QUIT\r\n"); // log off

      fclose($smtpConnection);

      return ($headerlength + $bodylength);
   }


   function errorCheck($line, $smtpConnection) {
      global $page_header_php;
      global $color;
      if (!isset($page_header_php)) {
         include "../functions/page_header.php";
      }
      
      // Read new lines on a multiline response
      $lines = $line;
      while(ereg("^[0-9]+-", $line)) {
	 $line = fgets($smtpConnection, 1024);
	 $lines .= $line;
      }

      // Status:  0 = fatal
      //          5 = ok

      $err_num = substr($line, 0, strpos($line, " "));
      switch ($err_num) {
         case 500:   $message = "Syntax error; command not recognized";
                     $status = 0;
                     break;
         case 501:   $message = "Syntax error in parameters or arguments";
                     $status = 0;
                     break;
         case 502:   $message = "Command not implemented";
                     $status = 0;
                     break;
         case 503:   $message = "Bad sequence of commands";
                     $status = 0;
                     break;
         case 504:   $message = "Command parameter not implemented";
                     $status = 0;
                     break;


         case 211:   $message = "System status, or system help reply";
                     $status = 5;
                     break;
         case 214:   $message = "Help message";
                     $status = 5;
                     break;


         case 220:   $message = "Service ready";
                     $status = 5;
                     break;
         case 221:   $message = "Service closing transmission channel";
                     $status = 5;
                     break;
         case 421:   $message = "Service not available, closing chanel";
                     $status = 0;
                     break;


         case 250:   $message = "Requested mail action okay, completed";
                     $status = 5;
                     break;
         case 251:   $message = "User not local; will forward";
                     $status = 5;
                     break;
         case 450:   $message = "Requested mail action not taken:  mailbox unavailable";
                     $status = 0;
                     break;
         case 550:   $message = "Requested action not taken:  mailbox unavailable";
                     $status = 0;
                     break;
         case 451:   $message = "Requested action aborted:  error in processing";
                     $status = 0;
                     break;
         case 551:   $message = "User not local; please try forwarding";
                     $status = 0;
                     break;
         case 452:   $message = "Requested action not taken:  insufficient system storage";
                     $status = 0;
                     break;
         case 552:   $message = "Requested mail action aborted:  exceeding storage allocation";
                     $status = 0;
                     break;
         case 553:   $message = "Requested action not taken: mailbox name not allowed";
                     $status = 0;
                     break;
         case 354:   $message = "Start mail input; end with .";
                     $status = 5;
                     break;
         case 554:   $message = "Transaction failed";
                     $status = 0;
                     break;
         default:    $message = "Unknown response: ". nl2br(htmlspecialchars($lines));
                     $status = 0;
                     $error_num = "001";
                     break;
      }

      if ($status == 0) {
         displayPageHeader($color, "None");
         echo "<TT>";
         echo "<br><b><font color=\"$color[1]\">ERROR</font></b><br><br>";
         echo "&nbsp;&nbsp;&nbsp;<B>Error Number: </B>$err_num<BR>";
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Reason: </B>$message<BR>";
         $lines = nl2br(htmlspecialchars($lines));
         echo "<B>Server Response: </B>$lines<BR>";
         echo "<BR>MAIL NOT SENT";
         echo "</TT></BODY></HTML>";
         exit;
      }
      return $err_num;
   }

   function sendMessage($t, $c, $b, $subject, $body, $reply_id) {
      global $useSendmail, $msg_id, $is_reply, $mailbox;
      global $data_dir, $username, $domain, $key, $version, $sent_folder, $imapServerAddress, $imapPort;
      $more_headers = Array();

      $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 1);

      if ($reply_id) {
         sqimap_mailbox_select ($imap_stream, $mailbox);
         sqimap_messages_flag ($imap_stream, $reply_id, $reply_id, "Answered");

         // Insert In-Reply-To and References headers if the 
         // message-id of the message we reply to is set (longer than "<>")
         // The References header should really be the old Referenced header
         // with the message ID appended, but it can be only the message ID too.
         $hdr = sqimap_get_small_header ($imap_stream, $reply_id, false);
         if(strlen($hdr->message_id) > 2) {
            $more_headers["In-Reply-To"] = $hdr->message_id;
            $more_headers["References"]  = $hdr->message_id;
         }
         sqimap_mailbox_close($imap_stream);
      }

      // this is to catch all plain \n instances and
      // replace them with \r\n.  
      $body = ereg_replace("\r\n", "\n", $body);
      $body = ereg_replace("\n", "\r\n", $body);

      if ($useSendmail) {
         $length = sendSendmail($t, $c, $b, $subject, $body, $more_headers);
      } else {
         $length = sendSMTP($t, $c, $b, $subject, $body, $more_headers);
      }

      if (sqimap_mailbox_exists ($imap_stream, $sent_folder)) {
         sqimap_append ($imap_stream, $sent_folder, $length);
         write822Header ($imap_stream, $t, $c, $b, $subject, $more_headers);
         writeBody ($imap_stream, $body);
         sqimap_append_done ($imap_stream);
      }
      sqimap_logout($imap_stream);
      // Delete the files uploaded for attaching (if any).
      deleteAttachments();
   }

?>
