<?
   /** smtp.php
    **
    ** This contains all the functions needed to send messages through
    ** an smtp server.
    **/

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

   function sendMessage($smtpServerAddress, $smtpPort, $username, $domain, $t, $c, $b, $subject, $body, $version) {
      include("../config/config.php");

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $body = stripslashes($body);
      $from_addr = "$username@$domain";
      $reply_to = getPref($data_dir, $username, "reply_to");
      $from = getPref($data_dir, $username, "full_name");

      if ($from == "")
         $from = "<$from_addr>";
      else
         $from = $from . " <$from_addr>";

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

      fputs($smtpConnection, "Subject: $subject\n"); // Subject
      fputs($smtpConnection, "From: $from\n"); // Subject
      fputs($smtpConnection, "To: $to_list\n");    // Who it's TO

      if ($cc_list) {
         fputs($smtpConnection, "Cc: $cc_list\n"); // Who the CCs are
      }
      fputs($smtpConnection, "X-Mailer: SquirrelMail (version $version)\n"); // Identify SquirrelMail
      fputs($smtpConnection, "MIME-Version: 1.0\n");
      fputs($smtpConnection, "Content-Type: text/plain\n");
      if ($reply_to != "")
         fputs($smtpConnection, "Reply-To: $reply_to\n");


      fputs($smtpConnection, "$body\n"); // send the body of the message

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
?>