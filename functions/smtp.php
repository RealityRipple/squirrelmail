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
      include("../functions/prefs.php");

      $to = parseAddrs($t);
      $cc = parseAddrs($c);
      $bcc = parseAddrs($b);
      $body = stripslashes($body);
      $from_addr = "$username@$domain";
      $reply_to = getPref($username, "reply_to");
      $from = getPref($username, "full_name");
      if ($from == "")
         $from = "<$username@$domain>";
      else
         $from = $from . " <$username@$domain>";


      $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));

      $to_list = getLineOfAddrs($to);
      $cc_list = getLineOfAddrs($cc);

      /** Lets introduce ourselves */
      fputs($smtpConnection, "HELO $domain\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));

      /** Ok, who is sending the message? */
      fputs($smtpConnection, "MAIL FROM:$from_addr\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));

      /** send who the recipients are */
      for ($i = 0; $i < count($to); $i++) {
         fputs($smtpConnection, "RCPT TO:<$to[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      }
      for ($i = 0; $i < count($cc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$cc[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      }
      for ($i = 0; $i < count($bcc); $i++) {
         fputs($smtpConnection, "RCPT TO:<$bcc[$i]>\n");
         $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));
      }

      /** Lets start sending the actual message */
      fputs($smtpConnection, "DATA\n");
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));

      fputs($smtpConnection, "Subject: $subject\n"); // Subject
      fputs($smtpConnection, "From: $from\n"); // Subject
      fputs($smtpConnection, "To: <$to_list>\n");    // Who it's TO

      if ($cc_list) {
         fputs($smtpConnection, "Cc: <$cc_list>\n"); // Who the CCs are
      }
      fputs($smtpConnection, "X-Mailer: SquirrelMail (version $version)\n"); // Identify SquirrelMail
      fputs($smtpConnection, "Reply-To: $reply_to\n");
      fputs($smtpConnection, "MIME-Version: 1.0\n");
      fputs($smtpConnection, "Content-Type: text/plain\n");

      fputs($smtpConnection, "$body\n"); // send the body of the message

      fputs($smtpConnection, ".\n"); // end the DATA part
      $tmp = nl2br(htmlspecialchars(fgets($smtpConnection, 1024)));

      fputs($smtpConnection, "QUIT\n"); // log off

      fclose($smtpConnection);
   }
?>