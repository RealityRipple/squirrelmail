<?
   /** smtp.php
    **
    ** This contains all the functions needed to send messages through
    ** an smtp server or sendmail.
    **/


   /* These next 2 functions are stub functions for implementations of 
      attachments */

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
      global $attachments;

      while (list($localname, $remotename) = each($attachments)) {
         $fileinfo = fopen ($localname.".info", "r");
         $filetype = fgets ($fileinfo, 8192);
         fclose ($fileinfo);
         $filetype = trim ($filetype);
         if ($filetype=="")
            $filetype = "application/octet-stream";

         fputs ($fp, "--".mimeBoundary()."\n");
         fputs ($fp, "Content-Type: $filetype\n");
         fputs ($fp, "Content-Disposition: attachment; filename=\"$remotename\"\n");
         fputs ($fp, "Content-Transfer-Encoding: base64\n\n");

         $file = fopen ($localname, "r");
         while ($tmp = fread($file, 57))
            fputs ($fp, chunk_split(base64_encode($tmp)));
         fclose ($file);

         unlink ($localname);
         unlink ($localname.".info");
      }
   }

   // Return a nice MIME-boundary
   function mimeBoundary () {
      global $mimeBoundaryString, $version, $REMOTE_ADDR, $SERVER_NAME,
         $REMOTE_PORT;

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
      global $REMOTE_ADDR, $SERVER_NAME;
      global $data_dir, $username, $domain, $version, $useSendmail;

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $from_addr = "$username@$domain";
      $reply_to = getPref($data_dir, $username, "reply_to");
      $from = getPref($data_dir, $username, "full_name");

      $to_list = getLineOfAddrs($to);
      $cc_list = getLineOfAddrs($cc);
      $bcc_list = getLineOfAddrs($bcc);

      if ($from == "")
         $from = "<$from_addr>";
      else
         $from = $from . " <$from_addr>";

      /* This creates an RFC 822 date showing GMT */
      $date = date("D, j M Y H:i:s ", mktime()) . timezone();

      /* Make an RFC822 Received: line */
      fputs ($fp, "Received: from $REMOTE_ADDR by $SERVER_NAME with HTTP; ");
      fputs ($fp, "$date\n");

      /* The rest of the header */
      fputs ($fp, "Date: $date\n");
      fputs ($fp, "Subject: $subject\n"); // Subject
      fputs ($fp, "From: $from\n"); // Subject
      fputs ($fp, "To: $to_list\n");    // Who it's TO

      if ($cc_list) {
         fputs($fp, "Cc: $cc_list\n"); // Who the CCs are
      }

      if ($reply_to != "")
         fputs($fp, "Reply-To: $reply_to\n");

      if ($useSendmail) {
         if ($bcc_list) {
            // BCCs is removed from header by sendmail
            fputs($fp, "Bcc: $bcc_list\n"); 
         }
      }

      fputs($fp, "X-Mailer: SquirrelMail (version $version)\n"); // Identify SquirrelMail

      // Do the MIME-stuff
      fputs($fp, "MIME-Version: 1.0\n");

      if (isMultipart()) {
         fputs ($fp, "Content-Type: multipart/mixed; boundary=\"");
         fputs ($fp, mimeBoundary());
         fputs ($fp, "\"\n");
      } else {
         fputs($fp, "Content-Type: text/plain; charset=ISO-8859-1\n");
         fputs($fp, "Content-Transfer-Encoding: 8bit\n");
      }
   }

   // Send the body
   function writeBody ($fp, $body) {
     if (isMultipart()) {
        fputs ($fp, "--".mimeBoundary()."\n");
        fputs ($fp, "Content-Type: text/plain; charset=ISO-8859-1\n");
        fputs ($fp, "Content-Transfer-Encoding: 8bit\n\n");
        fputs ($fp, stripslashes($body) . "\n");
        attachFiles($fp);
        fputs ($fp, "\n--".mimeBoundary()."--\n");
     } else {
       fputs ($fp, stripslashes($body) . "\n");
     }
   }

   // Send mail using the sendmail command
   function sendSendmail($t, $c, $b, $subject, $body) {
      global $sendmail_path, $username, $domain;
      
      // open pipe to sendmail
      $fp = popen (escapeshellcmd("$sendmail_path -t -f$username@$domain"), "w");
      
      write822Header ($fp, $t, $c, $b, $subject);
      writeBody($fp, $body);

      pclose($fp);
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
      global $username, $domain, $version, $smtpServerAddress, $smtpPort;

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
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
      fputs($smtpConnection, "HELO $domain\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      /** Ok, who is sending the message? */
      fputs($smtpConnection, "MAIL FROM:<$from_addr>\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      /** send who the recipients are */
      for ($i = 0; $i < count($to); $i++) {
         fputs($smtpConnection, "RCPT TO:<$to[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }
      for ($i = 0; $i < count($cc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$cc[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }
      for ($i = 0; $i < count($bcc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$bcc[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
         errorCheck($tmp);
      }

      /** Lets start sending the actual message */
      fputs($smtpConnection, "DATA\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      errorCheck($tmp);

      write822Header ($smtpConnection, $t, $c, $b, $subject);

      writeBody($smtpConnection, $body); // send the body of the message

      fputs($smtpConnection, ".\n"); // end the DATA part
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      $num = errorCheck($tmp);
      if ($num != 250) {
         echo "<HTML><BODY BGCOLOR=FFFFFF>ERROR<BR>Message not sent!<BR>Reason given: $tmp<BR></BODY></HTML>";
      }

      fputs($smtpConnection, "QUIT\n"); // log off

      fclose($smtpConnection);
   }


   function errorCheck($line) {
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
         echo "<HTML><BODY BGCOLOR=FFFFFF>";
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

      if ($useSendmail==true) {  
	 sendSendmail($t, $c, $b, $subject, $body);
      } else {
	 sendSMTP($t, $c, $b, $subject, $body);
      }
    
   }

?>
