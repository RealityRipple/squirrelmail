<?php
   /**
    **  imap.php
    **
    **  This implements all functions that do general imap functions.
    **/

   /******************************************************************************
    **  Reads the output from the IMAP stream.  If handle_errors is set to true,
    **  this will also handle all errors that are received.  If it is not set,
    **  the errors will be sent back through $response and $message
    ******************************************************************************/
   function sqimap_read_data ($imap_stream, $pre, $handle_errors, $response, $message) {
      global $color, $squirrelmail_language;

      //$imap_general_debug = true;
      $imap_general_debug = false;

      $read = fgets ($imap_stream, 1024);
		if ($imap_general_debug) echo "<small><tt><font color=cc0000>$read</font></tt></small><br>";
      $counter = 0;
      while (! ereg("^$pre (OK|BAD|NO)(.*)$", $read, $regs)) {
         $data[$counter] = $read;
         $read = fgets ($imap_stream, 1024);
			if ($imap_general_debug) echo "<small><tt><font color=cc0000>$read</font></tt></small><br>";
         $counter++;
      }       
      if ($imap_general_debug) echo "--<br>";

      if ($handle_errors == true) {
         if ($regs[1] == "NO") {
            set_up_language($squirrelmail_language);
            echo "<br><b><font color=$color[2]>\n";
            echo _("ERROR : Could not complete request.");
            echo "</b><br>\n";
            echo _("Reason Given: ");
            echo trim($regs[2]) . "</font><br>\n";
            exit;
         } else if ($regs[1] == "BAD") {
            set_up_language($squirrelmail_language);
            echo "<br><b><font color=$color[2]>\n";
            echo _("ERROR : Bad or malformed request.");
            echo "</b><br>\n";
            echo _("Server responded: ");
            echo trim($regs[2]) . "</font><br>\n";
            exit;
         }
      }
      
      return $data;
   }
   


   
   /******************************************************************************
    **  Logs the user into the imap server.  If $hide is set, no error messages
    **  will be displayed.  This function returns the imap connection handle.
    ******************************************************************************/
   function sqimap_login ($username, $password, $imap_server_address, $imap_port, $hide) {
      global $color, $squirrelmail_language, $HTTP_ACCEPT_LANGUAGE, $onetimepad;

      $imap_stream = fsockopen ($imap_server_address, $imap_port, &$error_number, &$error_string);
      $server_info = fgets ($imap_stream, 1024);
      
      // Decrypt the password
      $password = OneTimePadDecrypt($password, $onetimepad);

      /** Do some error correction **/
      if (!$imap_stream) {
         if (!$hide) {
            set_up_language($squirrelmail_language, true);
            printf (_("Error connecting to IMAP server: %s.")."<br>\r\n", $imap_server_address);
            echo "$error_number : $error_string<br>\r\n";
         }
         exit;
      }

      fputs ($imap_stream, "a001 LOGIN \"$username\" \"$password\"\r\n");
      $read = fgets ($imap_stream, 1024);

      /** If the connection was not successful, lets see why **/
      if (substr($read, 0, 7) != "a001 OK") {
         if (!$hide) {
            if (substr($read, 0, 8) == "a001 BAD") {
               set_up_language($squirrelmail_language, true);
               printf (_("Bad request: %s")."<br>\r\n", $read);
               exit;
            } else if (substr($read, 0, 7) == "a001 NO") {
               // If the user does not log in with the correct
               // username and password it is not possible to get the
               // correct locale from the user's preferences.
               // Therefore, apply the same hack as on the login
               // screen.

               // $squirrelmail_language is set by a cookie when
               // the user selects language and logs out
               
               set_up_language($squirrelmail_language, true);
               
               ?>
                  <html>
                     <body bgcolor=ffffff>
                        <br>
                        <center>
                        <table width=70% noborder bgcolor=ffffff align=center>
                           <tr>
                              <td bgcolor=dcdcdc>
                                 <font color=cc0000>
                                 <center>
                                 <?php echo _("ERROR") ?>
                                 </center>
                                 </font>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <center>
                                 <?php echo _("Unknown user or password incorrect.") ?><br>
                                 <a href="login.php" target="_top"><?php echo _("Click here to try again") ?></a>
                                 </center>
                              </td>
                           </tr>
                        </table>
                        </center>
                     </body>
                  </html>
               <?php
               session_destroy();
               exit;
            } else {
               set_up_language($squirrelmail_language, true);
               printf (_("Unknown error: %s")."<br>", $read);
               exit;
            }
         } else {
            exit;
         }
      }

      return $imap_stream;
   }


   
   
   /******************************************************************************
    **  Simply logs out the imap session
    ******************************************************************************/
   function sqimap_logout ($imap_stream) {
      fputs ($imap_stream, "a001 LOGOUT\r\n");
   }



   /******************************************************************************
    **  Returns the delimeter between mailboxes:  INBOX/Test, or INBOX.Test... 
    ******************************************************************************/
   function sqimap_get_delimiter ($imap_stream) {
      fputs ($imap_stream, "a001 NAMESPACE\r\n");
      $read = sqimap_read_data($imap_stream, "a001", true, $a, $b);
      eregi("\"\" \"(.)\"", $read[0], $regs);
      return $regs[1];
   }




   /******************************************************************************
    **  Gets the number of messages in the current mailbox. 
    ******************************************************************************/
   function sqimap_get_num_messages ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 EXAMINE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $result, $message);
      for ($i = 0; $i < count($read_ary); $i++) {
         if (ereg("[^ ]+ +([^ ]+) +EXISTS", $read_ary[$i], $regs)) {
	    return $regs[1];
         }
      }
      return "BUG!  Couldn't get number of messages in $mailbox!";
   }

   
   /******************************************************************************
    **  Returns a displayable email address 
    ******************************************************************************/
   function sqimap_find_email ($string) {
      /** Luke Ehresman <lehresma@css.tayloru.edu>
       ** <lehresma@css.tayloru.edu>
       ** lehresma@css.tayloru.edu
       **/

      if (ereg("<([^>]+)>", $string, $regs)) {
          $string = $regs[1];
      }
      return trim($string); 
   }

   
   /******************************************************************************
    **  Takes the From: field, and creates a displayable name.
    **    Luke Ehresman <lkehresman@yahoo.com>
    **           becomes:   Luke Ehresman
    **    <lkehresman@yahoo.com>
    **           becomes:   lkehresman@yahoo.com
    ******************************************************************************/
   function sqimap_find_displayable_name ($string) {
      ereg("^\"?([^\"<]*)[\" <]*([^>]+)>?$", trim($string), $regs);
      if ($regs[1] == '')
          return $regs[2];
      return $regs[1];
   }


   /******************************************************************************
    **  Returns the number of unseen messages in this folder 
    ******************************************************************************/
   function sqimap_unseen_messages ($imap_stream, &$num_unseen, $mailbox) {
      //fputs ($imap_stream, "a001 SEARCH UNSEEN NOT DELETED\r\n");
      fputs ($imap_stream, "a001 STATUS \"$mailbox\" (UNSEEN)\r\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $result, $message);
      ereg("UNSEEN ([0-9]+)", $read_ary[0], $regs);
      return $regs[1];
   }
 
  
   /******************************************************************************
    **  Saves a message to a given folder -- used for saving sent messages
    ******************************************************************************/
   function sqimap_append ($imap_stream, $sent_folder, $length) {
      fputs ($imap_stream, "a001 APPEND \"$sent_folder\" (\\Seen) \{$length}\r\n");
      $tmp = fgets ($imap_stream, 1024);
   } 

   function sqimap_append_done ($imap_stream) {
      fputs ($imap_stream, "\r\n");
      $tmp = fgets ($imap_stream, 1024);
   }
?>
