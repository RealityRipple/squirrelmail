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

   function sendMessage($smtpServerAddress, $smtpPort, $to, $subject, $body) {
      $to = addslashes($to);
      $body = addslashes($body);
      $from = "$username@$domain";

      echo "<FONT FACE=\"Arial,Helvetica\">";
      $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "MAIL FROM:<$from>\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "RCPT TO:<$to>\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "DATA\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "Subject: $subject\n");
      fputs($smtpConnection, "Date: " . date() . "\n");
      fputs($smtpConnection, "To: <$to>\n");
      fputs($smtpConnection, "From: <$from>\n");
      fputs($smtpConnection, "$body\n");
      fputs($smtpConnection, ".\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "QUIT\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";
      echo "</FONT>";
   }
?>