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

   function sendMessage($to, $subject, $body) {
      echo "<FONT FACE=\"Arial,Helvetica\">";
      $smtpConnection = fsockopen("10.4.1.1", 25, $errorNumber, $errorString);
      if (!$smtpConnection) {
         echo "Error connecting to SMTP Server.<br>";
         echo "$errorNumber : $errorString<br>";
         exit;
      }
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "MAIL FROM:<luke@usa.om.org>\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "RCPT TO:<$to>\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "DATA\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "Subject: $subject\n");
      fputs($smtpConnection, "$body\n");
      fputs($smtpConnection, ".\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";

      fputs($smtpConnection, "QUIT\n");
      echo htmlspecialchars(fgets($smtpConnection, 1024)) . "<BR>";
      echo "</FONT>";
   }
?>