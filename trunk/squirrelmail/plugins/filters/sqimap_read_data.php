   /******************************************************************************
    **  Reads the output from the IMAP stream.  If handle_errors is set to true,
    **  this will also handle all errors that are received.  If it is not set,
    **  the errors will be sent back through $response and $message
    ******************************************************************************/
   function sqimap_read_data ($imap_stream, $pre, $handle_errors, &$response, &$message) {
      global $color, $squirrelmail_language, $imap_general_debug;

      $data = array();
      $size = 0;

      do {
         $read = fgets($imap_stream, 9096);
         if (ereg("^$pre (OK|BAD|NO)(.*)$", $read, $regs)) {
            break;  // found end of reply
         }

         // Continue if needed for this single line
         while (strpos($read, "\n") === false) {
            $read .= fgets($imap_stream, 9096);
         }

         $data[] = $read;

         if (ereg("^\\* [0-9]+ FETCH.*\\{([0-9]+)\\}", $read, $regs)) {
            $size = $regs[1];
            if ($imap_general_debug) {
               echo "<small><tt><font color=\"#CC0000\">Size is $size</font></tt></small><br>\n";
            }

            $total_size = 0;
            do {
               $read = fgets($imap_stream, 9096);
               if ($imap_general_debug) {
                  echo "<small><tt><font color=\"#CC0000\">$read</font></tt></small><br>\n";
                  flush();
               }
               $data[] = $read;
               $total_size += strlen($read);
            } while ($total_size < $size);

            $size = 0;
         }
         // For debugging purposes
         if ($imap_general_debug) {
            echo "<small><tt><font color=\"#CC0000\">$read</font></tt></small><br>\n";
            flush();
         }
      } while (true);

      $response = $regs[1];
      $message = trim($regs[2]);

      if ($imap_general_debug) echo '--<br>';

      if ($handle_errors == false)
          return $data;
 
      if ($response == 'NO') {
         // ignore this error from m$ exchange, it is not fatal (aka bug)
         if (strstr($message, 'command resulted in') === false) {
            set_up_language($squirrelmail_language);
            echo "<br><b><font color=$color[2]>\n";
            echo _("ERROR : Could not complete request.");
            echo "</b><br>\n";
            echo _("Reason Given: ");
            echo $message . "</font><br>\n";
            exit;
         }
      } else if ($response == 'BAD') {
         set_up_language($squirrelmail_language);
         echo "<br><b><font color=$color[2]>\n";
         echo _("ERROR : Bad or malformed request.");
         echo "</b><br>\n";
         echo _("Server responded: ");
         echo $message . "</font><br>\n";
         exit;
      }

      return $data;
   }