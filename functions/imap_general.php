<?
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

      $read = fgets ($imap_stream, 1024);
      $counter = 0;
      while ((substr($read, 0, strlen("$pre OK")) != "$pre OK") &&
             (substr($read, 0, strlen("$pre BAD")) != "$pre BAD") &&
             (substr($read, 0, strlen("$pre NO")) != "$pre NO")) {
         $data[$counter] = $read;
         $read = fgets ($imap_stream, 1024);
         $counter++;
      }       
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
            echo "<br><b><font color=$color[2]>";
            echo _("ERROR : Could not complete request.");
            echo "</b><br>";
            echo _("Reason Given: ");
            echo "$message</font><br>";
            exit;
         } else if ($response == "BAD") {
            echo "<br><b><font color=$color[2]>";
            echo _("ERROR : Bad or malformed request.");
            echo "</b><br>";
            echo _("Server responded: ");
            echo "$message</font><br>";
            exit;
         }
      }
      
      return $data;
   }
   


   
   /******************************************************************************
    **  Logs the user into the imap server.  If $hide is set, no error messages
    **  will be displayed.  This function returns the imap connection handle.
    ******************************************************************************/
   function sqimap_login ($username, $password, $imap_server_address, $hide) {
      global $color;
      $imap_stream = fsockopen ($imap_server_address, 143, &$error_number, &$error_string);
      $server_info = fgets ($imap_stream, 1024);
      
      /** Do some error correction **/
      if (!$imap_stream) {
         if (!$hide) {
            echo "Error connecting to IMAP server: $imap_server_address.<br>\n";
            echo "$error_number : $error_string<br>\n";
         }
         exit;
      }

      fputs ($imap_stream, "a001 LOGIN \"$username\" \"$password\"\n");
      $read = fgets ($imap_stream, 1024);

      /** If the connection was not successful, lets see why **/
      if (substr($read, 0, 7) != "a001 OK") {
         if (!$hide) {
            if (substr($read, 0, 8) == "a001 BAD") {
               echo "Bad request: $read<br>\n";
               exit;
            } else if (substr($read, 0, 7) == "a001 NO") {
               ?>
                  <html>
                     <body bgcolor=<? echo $color[4] ?>>
                        <br>
                        <table width=70% noborder bgcolor=<? echo $color[4] ?> align=center>
                           <tr>
                              <td bgcolor=<? echo $color[0] ?>>
                                 <font color=<? echo $color[2] ?>>
                                 <center>
                                 <? echo _("ERROR") ?>
                                 </center>
                                 </font>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <font color=<? echo $color[2] ?>>
                                 <center>
                                 <? echo _("Unknown user or password incorrect.") ?><br>
                                 <a href="login.php"><? echo _("Click here to try again") ?></a>
                                 </center>
                                 </font>
                              </td>
                           </tr>
                        </table>
                     </body>
                  </html>
               <?
               exit;
            } else {
               echo "Unknown error: $read<br>";
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
      fputs ($imap_stream, "a001 LOGOUT\n");
   }



   /******************************************************************************
    **  Returns the delimeter between mailboxes:  INBOX/Test, or INBOX.Test... 
    ******************************************************************************/
   function sqimap_get_delimiter ($imap_stream) {
      fputs ($imap_stream, ". LIST \"\" *\n");
      $read = sqimap_read_data($imap_stream, ".", true, $a, $b);
      $quote_position = strpos ($read[0], "\"");
      $delim = substr ($read[0], $quote_position+1, 1);

      return $delim;
   }




   /******************************************************************************
    **  Gets the number of messages in the current mailbox. 
    ******************************************************************************/
   function sqimap_get_num_messages ($imap_stream, $mailbox) {
      fputs ($imap_stream, "a001 EXAMINE \"$mailbox\"\n");
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
      return $string; 
   }

   
   /******************************************************************************
    **  Takes the From: field, and creates a displayable name.
    **    Luke Ehresman <lkehresman@yahoo.com>
    **           becomes:   Luke Ehresman
    **    <lkehresman@yahoo.com>
    **           becomes:   lkehresman@yahoo.com
    ******************************************************************************/
   function sqimap_find_displayable_name ($string) {
      if (strpos($string, "<") && strpos($string, ">")) {
         if (strpos($string, "<") == 0) {
            $string = sqimap_find_email($string);
         } else {
            $string = substr($string, 0, strpos($string, "<"));
         }   
      }
      return $string; 
   }


   
   /******************************************************************************
    **  Returns the number of unseen messages in this folder 
    ******************************************************************************/
   function sqimap_unseen_messages ($imap_stream, &$num_unseen) {
      fputs ($imap_stream, "a001 SEARCH UNSEEN NOT DELETED\n");
      $read_ary = sqimap_read_data ($imap_stream, "a001", true, $result, $message);
      $unseen = false;
      
      if (strlen($read_ary[0]) > 10) {
         $unseen = true;
         $ary = explode (" ", $read_ary[0]);
         $num_unseen = count($ary) - 2;
      } else {
         $unseen = false;
         $num_unseen = 0;
      }

      return $unseen;
   }
?>
