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
      global $color;

      //$imap_general_debug = true;
      $imap_general_debug = false;

      $read = fgets ($imap_stream, 1024);
		if ($imap_general_debug) echo "<small><tt><font color=cc0000>$read</font></tt></small><br>";
      $counter = 0;
      while ((substr($read, 0, strlen("$pre OK")) != "$pre OK") &&
             (substr($read, 0, strlen("$pre BAD")) != "$pre BAD") &&
             (substr($read, 0, strlen("$pre NO")) != "$pre NO")) {
         $data[$counter] = $read;
         $read = fgets ($imap_stream, 1024);
			if ($imap_general_debug) echo "<small><tt><font color=cc0000>$read</font></tt></small><br>";
         $counter++;
      }       
      if ($imap_general_debug) echo "--<br>";
      if (substr($read, 0, strlen("$pre OK")) == "$pre OK") {
         $response = "OK";
         $message = trim(substr($read, strlen("$pre OK"), strlen($read)));
      }
      else if (substr($read, 0, strlen("$pre BAD")) == "$pre BAD") {
         $response = "BAD";
         $message = trim(substr($read, strlen("$pre BAD"), strlen($read)));
      }
      else {   
         $response = "NO";
         $message = trim(substr($read, strlen("$pre NO"), strlen($read)));
      }

      if ($handle_errors == true) {
         if ($response == "NO") {
            echo "<br><b><font color=$color[2]>\n";
            echo _("ERROR : Could not complete request.");
            echo "</b><br>\n";
            echo _("Reason Given: ");
            echo "$message</font><br>\n";
            exit;
         } else if ($response == "BAD") {
            echo "<br><b><font color=$color[2]>\n";
            echo _("ERROR : Bad or malformed request.");
            echo "</b><br>\n";
            echo _("Server responded: ");
            echo "$message</font><br>\n";
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

      // This function can sometimes be called before the check for
      // gettext is done.
      if (!function_exists("_")) {
         function _($string) {
            return $string;
         }
      }

      /** Do some error correction **/
      if (!$imap_stream) {
         if (!$hide) {
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
               
               // Use HTTP content language negotiation if cookie
               // not set
               if (!isset($squirrelmail_language) && isset($HTTP_ACCEPT_LANGUAGE)) {
                  $squirrelmail_language = substr($HTTP_ACCEPT_LANGUAGE, 0, 2);
               }
               
               if (isset($squirrelmail_language) && function_exists("bindtextdomain")) {
                  if ($squirrelmail_language != "en" && $squirrelmail_language != "") {
                     putenv("LC_ALL=".$squirrelmail_language);
                     bindtextdomain("squirrelmail", "../locale/");
                     textdomain("squirrelmail");
                     header ("Content-Type: text/html; charset=".$languages[$squirrelmail_language]["CHARSET"]);
                  }
               }
               
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
      fputs ($imap_stream, ". LIST \"INBOX\" \"\"\r\n");
      $read = sqimap_read_data($imap_stream, ".", true, $a, $b);
      $quote_position = strpos ($read[0], "\"");
      $delim = substr ($read[0], $quote_position+1, 1);

      return $delim;
   }




   /******************************************************************************
    **  Gets the number of messages in the current mailbox. 
    ******************************************************************************/
   function sqimap_get_num_messages ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 EXAMINE \"$mailbox\"\r\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $result, $message);
      for ($i = 0; $i < count($read_ary); $i++) {
         if (substr(trim($read_ary[$i]), -6) == EXISTS) {
            $array = explode (" ", $read_ary[$i]);
            $num = $array[1];
         }
      }
      return $num;
   }

   
   /******************************************************************************
    **  Returns a displayable email address 
    ******************************************************************************/
   function sqimap_find_email ($string) {
      /** Luke Ehresman <lehresma@css.tayloru.edu>
       ** <lehresma@css.tayloru.edu>
       ** lehresma@css.tayloru.edu
       **/

      if (strpos($string, "<") && strpos($string, ">")) {
         $string = substr($string, strpos($string, "<")+1);
         $string = substr($string, 0, strpos($string, ">"));
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
      $string = " ".trim($string);
      $orig_string = $string;
      if (strpos($string, "<") && strpos($string, ">")) {
         if (strpos($string, "<") == 1) {
            $string = sqimap_find_email($string);
         } else {
            $string = trim($string);
            $string = substr($string, 0, strpos($string, "<"));
            $string = ereg_replace ("\"", "", $string);   
         }   

         if (trim($string) == "") {
            $string = sqimap_find_email($orig_string);
         }
      }
      return $string; 
   }


   /******************************************************************************
    **  Returns the number of unseen messages in this folder 
    ******************************************************************************/
   function sqimap_unseen_messages ($imap_stream, &$num_unseen, $mailbox) {
      //fputs ($imap_stream, "a001 SEARCH UNSEEN NOT DELETED\r\n");
      fputs ($imap_stream, "a001 STATUS \"$mailbox\" (UNSEEN)\r\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $result, $message);
      $unseen = false;
      
		$read_ary[0] = trim($read_ary[0]);
		return substr($read_ary[0], strrpos($read_ary[0], " ")+1, (strlen($read_ary[0]) - strrpos($read_ary[0], " ") - 2)); 
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
