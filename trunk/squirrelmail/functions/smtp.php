<?
   /** smtp.php
    **
    ** This contains all the functions needed to send messages through
    ** an smtp server or sendmail.
    **/

   $smtp_php = true;

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
            $header .= "Content-Type: $filetype\r\n";
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
      global $version, $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;

      static $mimeBoundaryString;

      if ($mimeBoundaryString == "") {
         $temp = "SquirrelMail".$version.$REMOTE_ADDR.$SERVER_NAME.
            $REMOTE_PORT;
         $mimeBoundaryString = "=-_+".substr(md5($temp),1,20);
      }

      return $mimeBoundaryString;
   }

   /* Time offset for correct timezone */
   function timezone () {
      $diff_second = date("Z");
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
   function write822Header ($fp, $t, $c, $b, $subject) {
      global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
      global $data_dir, $username, $domain, $version, $useSendmail;
      global $default_charset;

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
            $from_addr = "$username@$domain";
         
         $to_list = getLineOfAddrs($to);
         $cc_list = getLineOfAddrs($cc);
         $bcc_list = getLineOfAddrs($bcc);
         
         if ($from == "")
            $from = "<$from_addr>";
         else
            $from = $from . " <$from_addr>";
         
         /* This creates an RFC 822 date */
         $date = date("D, j M Y H:i:s ", mktime()) . timezone();

         /* Create a message-id */
         $message_id = "<" . $REMOTE_PORT . "." . $REMOTE_ADDR . ".";
         $message_id .= time() . "@" . $SERVER_NAME .">";
         
         /* Make an RFC822 Received: line */
         $header = "Received: from $REMOTE_ADDR by $SERVER_NAME with HTTP; ";
         $header .= "$date\r\n";
         
         /* Insert the rest of the header fields */
         $header .= "Message-ID: $message_id\r\n";
         $header .= "Date: $date\r\n";
         $header .= "Subject: $subject\r\n";
         $header .= "From: $from\r\n";
         $header .= "To: $to_list \r\n";    // Who it's TO
         
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
         $body .= stripslashes($passedBody) . "\r\n";
         fputs ($fp, $body);

         $attachmentlength = attachFiles($fp);

         $postbody .= "\r\n--".mimeBoundary()."--\r\n\r\n";
         fputs ($fp, $postbody);
      } else {
         $body = stripslashes($passedBody) . "\r\n";
         fputs ($fp, $body);
         $postbody = "\r\n";
         fputs ($fp, $postbody);
      }

      return (strlen($body) + strlen($postbody) + $attachmentlength);
   }

   // Send mail using the sendmail command
   function sendSendmail($t, $c, $b, $subject, $body) {
      global $sendmail_path, $username, $domain;

      // open pipe to sendmail
      $fp = popen (escapeshellcmd("$sendmail_path -t -f$username@$domain"), "w");
      
      $headerlength = write822Header ($fp, $t, $c, $b, $subject);
      $bodylength = writeBody($fp, $body);

      pclose($fp);

      return ($headerlength + $bodylenght);
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

   function sendSMTP($t, $c, $b, $subject, $body) {
      global $username, $domain, $version, $smtpServerAddress, $smtpPort,
         $data_dir, $color;

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $from_addr = getPref($data_dir, $username, "email_address");

      if ($from_addr == "")
         $from_addr = "$username@$domain";

      $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      $to_list = getLineOfAddrs($to);
      $cc_list = getLineOfAddrs($cc);

      /** Lets introduce ourselves */
      fputs($smtpConnection, "HELO $domain\r\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      /** Ok, who is sending the message? */
      fputs($smtpConnection, "MAIL FROM:<$from_addr>\r\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      /** send who the recipients are */
      for ($i = 0; $i < count($to); $i++) {
         fputs($smtpConnection, "RCPT TO:<$to[$i]>\r\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }
      for ($i = 0; $i < count($cc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$cc[$i]>\r\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }
      for ($i = 0; $i < count($bcc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$bcc[$i]>\r\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }

      /** Lets start sending the actual message */
      fputs($smtpConnection, "DATA\r\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      // Send the message
      $headerlength = write822Header ($smtpConnection, $t, $c, $b, $subject);
      $bodylength = writeBody($smtpConnection, $body);

      fputs($smtpConnection, ".\r\n"); // end the DATA part
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      $num = errorCheck($tmp);
      if ($num != 250) {
         echo "ERROR<BR>Message not sent!<BR>Reason given: $tmp<BR></BODY></HTML>";
      }

      fputs($smtpConnection, "QUIT\r\n"); // log off

      fclose($smtpConnection);

      return ($headerlength + $bodylength);
   }


   function errorCheck($line) {
      global $color;
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
         default:    $message = "Unknown response: $line";
                     $status = 0;
                     $error_num = "001";
                     break;
      }

      if ($status == 0) {
         echo "<HTML><BODY BGCOLOR=ffffff>";
         echo "<TT>";
         echo "<BR><B>ERROR</B><BR><BR>";
         echo "&nbsp;&nbsp;&nbsp;<B>Error Number: </B>$err_num<BR>";
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>Reason: </B>$message<BR>";
         echo "<B>Server Response: </B>$line<BR>";
         echo "<BR>MAIL NOT SENT";
         echo "</TT></BODY></HTML>";
         exit;
      }
      return $err_num;
   }

   function sendMessage($t, $c, $b, $subject, $body) {
      global $useSendmail;
      global $data_dir, $username, $domain, $key, $version, $sent_folder, $imapServerAddress, $imapPort;

      if ($useSendmail==true) {  
         $length = sendSendmail($t, $c, $b, $subject, $body);
      } else {
         $length = sendSMTP($t, $c, $b, $subject, $body);
      }

      $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 1);
      sqimap_append ($imap_stream, $sent_folder, $length);
      write822Header ($imap_stream, $t, $c, $b, $subject);
      writeBody ($imap_stream, $body); 
      sqimap_append_done ($imap_stream);


      // Delete the files uploaded for attaching (if any).
      deleteAttachments();

   }

?>
