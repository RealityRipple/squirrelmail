<?php
require_once ('../src/validate.php');

   /* Print all the needed RFC822 headers */
   function write822HeaderForDraft ($fp, $t, $c, $b, $subject, $more_headers) {
      global $REMOTE_ADDR, $SERVER_NAME, $REMOTE_PORT;
      global $data_dir, $username, $popuser, $domain, $version, $useSendmail;
      global $default_charset, $HTTP_VIA, $HTTP_X_FORWARDED_FOR;
      global $REMOTE_HOST, $identity;

      // Storing the header to make sure the header is the same
      // everytime the header is printed.
      static $header, $headerlength;

      if ($header == '') {
         if (isset($identity) && ($identity != 'default')) {
            $reply_to = getPref($data_dir, $username, 'reply_to' . $identity);
            $from = getPref($data_dir, $username, 'full_name' . $identity);
            $from_addr = getPref($data_dir, $username, 'email_address' . $identity);
         } else {
            $reply_to = getPref($data_dir, $username, 'reply_to');
            $from = getPref($data_dir, $username, 'full_name');
            $from_addr = getPref($data_dir, $username, 'email_address');
         }

         if ($from_addr == '') {
            $from_addr = $popuser.'@'.$domain;
         }

         /* Encoding 8-bit characters and making from line */
         $subject = encodeHeader($subject);
         if ($from == '') {
            $from = "<$from_addr>";
         } else {
            $from = '"' . encodeHeader($from) . "\" <$from_addr>";
         }

         /* This creates an RFC 822 date */
         $date = date("D, j M Y H:i:s ", mktime()) . timezone();

         /* Create a message-id */
         $message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
         $message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';

         /* Insert header fields */
         $header = "Message-ID: $message_id\r\n";
         $header .= "Date: $date\r\n";
         $header .= "Subject: $subject\r\n";
         $header .= "From: $from\r\n";
         $header .= "To: $t\r\n";    // Who it's TO

         /* Insert headers from the $more_headers array */
         if(is_array($more_headers)) {
            reset($more_headers);
            while(list($h_name, $h_val) = each($more_headers)) {
               $header .= sprintf("%s: %s\r\n", $h_name, $h_val);
            }
         }

         if ($c) {
            $header .= "Cc: $c\r\n"; // Who the CCs are
         }

         if ($b) {
            $header .= "Bcc: $b\r\n"; // Who the BCCs are
         }

         if ($reply_to != '')
            $header .= "Reply-To: $reply_to\r\n";

         $header .= "X-Mailer: SquirrelMail (version $version)\r\n"; // Identify SquirrelMail

         /* Do the MIME-stuff */
         $header .= "MIME-Version: 1.0\r\n";

         if (isMultipart()) {
            $header .= 'Content-Type: multipart/mixed; boundary="';
            $header .= mimeBoundary();
            $header .= "\"\r\n";
         } else {
            if ($default_charset != '')
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

   function saveMessageAsDraft($t, $c, $b, $subject, $body, $reply_id) {
      global $useSendmail, $msg_id, $is_reply, $mailbox, $onetimepad;
      global $data_dir, $username, $domain, $key, $version, $sent_folder, $imapServerAddress, $imapPort;
      global $draft_folder;
      $more_headers = Array();

      $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 1);

      $fp = fopen("/dev/null", a);
      $headerlength = write822HeaderForDraft ($fp, $t, $c, $b, $subject, $more_headers);
      $bodylength = writeBody ($fp, $body);
      fclose ($fp);

      $length = ($headerlength + $bodylength);

      if (sqimap_mailbox_exists ($imap_stream, $draft_folder)) {
         sqimap_append ($imap_stream, $draft_folder, $length);
         write822HeaderForDraft ($imap_stream, $t, $c, $b, $subject, $more_headers);
         writeBody ($imap_stream, $body);
         sqimap_append_done ($imap_stream);
      }
      sqimap_logout($imap_stream);
      if ($length)
         ClearAttachments();
      return $length;
}
?>
