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
      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $body = stripslashes($body);
      $from = "$username@$domain";

      echo "<FONT FACE=\"Arial,Helvetica\">";
      $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }

      $to_list = getLineOfAddrs($to);
      $cc_list = getLineOfAddrs($cc);

      /** Lets introduce ourselves */
      fputs($smtpConnection, "HELO $domain\n");
      /** Ok, who is sending the message? */
      fputs($smtpConnection, "MAIL FROM:<$from>\n");

      /** send who the recipients are */
      for ($i = 0; $i < count($to); $i++) {
         fputs($smtpConnection, "RCPT TO:<$to[$i]>\n");
      }
      for ($i = 0; $i < count($cc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$cc[$i]>\n");
      }
      for ($i = 0; $i < count($bcc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$bcc[$i]>\n");
      }

      /** Lets start sending the actual message */
      fputs($smtpConnection, "DATA\n");
      fputs($smtpConnection, "Subject: $subject\n"); // Subject
      fputs($smtpConnection, "To: <$to_list>\n");    // Who it's TO
      if ($cc_list) {
         fputs($smtpConnection, "Cc: <$cc_list>\n"); // Who the CCs are
      }
      fputs($smtpConnection, "X-Mailer: SquirrelMail (version $version)\n"); // Identify SquirrelMail
      fputs($smtpConnection, "Reply-To: $from\n");
      fputs($smtpConnection, "MIME-Version: 1.0\n");
      fputs($smtpConnection, "Content-Type: text/plain; charset=us-ascii\n");

      fputs($smtpConnection, "$body\n"); // send the body of the message
      fputs($smtpConnection, ".\n"); // end the DATA part
      fputs($smtpConnection, "QUIT\n"); // log off

      echo "</FONT>";
   }
?>