<?php
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME
    ** messages.
    **
    **/

   $debug_mime = false;
   $mime_php = true;

   if (!isset($i18n_php))
      include "../functions/i18n.php";
   if (!isset($imap_php))
      include "../functions/imap.php";
   if (!isset($config_php))
      include "../config/config.php";


   /** Setting up the objects that have the structure for the message **/

   class msg_header {
      /** msg_header contains generic variables for values that **/
      /** could be in a header.                                 **/
      
      var $type0, $type1, $boundary, $charset, $encoding;
      var $to, $from, $date, $cc, $bcc, $reply_to, $subject;
      var $id, $mailbox, $description;
      var $entity_id, $message_id;
   }
   
   class message {
      /** message is the object that contains messages.  It is a recursive
          object in that through the $entities variable, it can contain 
          more objects of type message.  See documentation in mime.txt for
          a better description of how this works.
       **/   
      var $header;
      var $entities;
      
      function addEntity ($msg) {
         $this->entities[count($this->entities)] = $msg;
      }
   }



   /* --------------------------------------------------------------------------------- */
   /* MIME DECODING                                                                     */
   /* --------------------------------------------------------------------------------- */
   
   // This function gets the structure of a message and stores it in the "message" class.
   // It will return this object for use with all relevant header information and
   // fully parsed into the standard "message" object format.
   function mime_structure ($imap_stream, $header) {
      global $debug_mime;
      sqimap_messages_flag ($imap_stream, $header->id, $header->id, "Seen");
      
      $id = $header->id;
      fputs ($imap_stream, "a001 FETCH $id BODYSTRUCTURE\r\n");
      $read = fgets ($imap_stream, 10000);
      $read = strtolower($read);

      if ($debug_mime) echo "<tt>$read</tt><br><br>";
      // isolate the body structure and remove beginning and end parenthesis
      $read = trim(substr ($read, strpos($read, "bodystructure") + 13));
      $read = trim(substr ($read, 0, -1));
      $end = mime_match_parenthesis(0, $read);
      while ($end == strlen($read)-1) {
         $read = trim(substr ($read, 0, -1));
         $read = trim(substr ($read, 1));
         $end = mime_match_parenthesis(0, $read);
      }

      if ($debug_mime) echo "<tt>$read</tt><br><br>";

      $msg = mime_parse_structure ($read);
      $msg->header = $header;
      return $msg;
   }

   // this starts the parsing of a particular structure.  It is called recursively,
   // so it can be passed different structures.  It returns an object of type
   // $message.
   // First, it checks to see if it is a multipart message.  If it is, then it
   // handles that as it sees is necessary.  If it is just a regular entity,
   // then it parses it and adds the necessary header information (by calling out
   // to mime_get_elements()
   function mime_parse_structure ($structure, $ent_id) {
      global $debug_mime;
      if ($debug_mime) echo "<font color=008800><tt>START: mime_parse_structure()</tt></font><br>";
      $msg = new message();
      if (substr($structure, 0, 1) == "(") {
         $ent_id = mime_new_element_level($ent_id);
         $start = $end = -1;
         if ($debug_mime) echo "<br><font color=0000aa><tt>$structure</tt></font><br>";
         do {
            if ($debug_mime) echo "<font color=008800><tt>Found entity...</tt></font><br>";
            $start = $end+1;
            $end = mime_match_parenthesis ($start, $structure);
            
            $element = substr($structure, $start+1, ($end - $start)-1);
            $ent_id = mime_increment_id ($ent_id);
            $newmsg = mime_parse_structure ($element, $ent_id);
            $msg->addEntity ($newmsg);
         } while (substr($structure, $end+1, 1) == "(");
      } else {
         // parse the elements
         if ($debug_mime) echo "<br><font color=0000aa><tt>$structure</tt></font><br>";
         $msg = mime_get_element (&$structure, $msg, $ent_id);
         if ($debug_mime) echo "<br>";
      }
      return $msg;
      if ($debug_mime) echo "<font color=008800><tt>&nbsp;&nbsp;END: mime_parse_structure()</tt></font><br>";
   }

   // Increments the element ID.  An element id can look like any of
   // the following:  1, 1.2, 4.3.2.4.1, etc.  This function increments
   // the last number of the element id, changing 1.2 to 1.3.
   function mime_increment_id ($id) {
      global $debug_mime;
      if (strpos($id, ".")) {
         $first = substr($id, 0, strrpos($id, "."));
         $last = substr($id, strrpos($id, ".")+1);
         $last++;
         $new = $first . "." .$last;
      } else {
         $new = $id + 1;
      }
      if ($debug_mime) echo "<b>INCREMENT: $new</b><br>";
      return $new;
   }

   // See comment for mime_increment_id().
   // This adds another level on to the entity_id changing 1.3 to 1.3.0
   // NOTE:  1.3.0 is not a valid element ID.  It MUST be incremented 
   //        before it can be used.  I left it this way so as not to have
   //        to make a special case if it is the first entity_id.  It
   //        always increments it, and that works fine.
   function mime_new_element_level ($id) {
      if (!$id) $id = 0;
      else      $id = $id . ".0";

      return $id;   
   }

   function mime_get_element (&$structure, $msg, $ent_id) {
      global $debug_mime;
      $elem_num = 1;
      $msg->header = new msg_header();
      $msg->header->entity_id = $ent_id;
      
      while (strlen($structure) > 0) {
         $structure = trim($structure);
         $char = substr($structure, 0, 1);

         if (substr($structure, 0, 3) == "nil") {
            $text = "";
            $structure = substr($structure, 3);
         } else if ($char == "\"") {
            // loop through until we find the matching quote, and return that as a string
            $pos = 1;
            $char = substr($structure, $pos, 1);
            while ($char != "\"" && $pos < strlen($structure)) {
               $text .= $char;
               $pos++;
               $char = substr($structure, $pos, 1);
            }   
            $structure = substr($structure, strlen($text) + 2);
         } else if ($char == "(") {
            // comment me
            $end = mime_match_parenthesis (0, $structure);
            $sub = substr($structure, 1, $end-1);
            $properties = mime_get_props($properties, $sub);
            $structure = substr($structure, strlen($sub) + 2);
         } else {
            // loop through until we find a space or an end parenthesis
            $pos = 0;
            $char = substr($structure, $pos, 1);
            while ($char != " " && $char != ")" && $pos < strlen($structure)) {
               $text .= $char;
               $pos++;
               $char = substr($structure, $pos, 1);
            }
            $structure = substr($structure, strlen($text));
         }
         if ($debug_mime) echo "<tt>$elem_num : $text</tt><br>";

         // This is where all the text parts get put into the header
         switch ($elem_num) {
            case 1: 
               $msg->header->type0 = $text;
               if ($debug_mime) echo "<tt>type0 = $text</tt><br>";
               break;
            case 2: 
               $msg->header->type1 = $text;
               if ($debug_mime) echo "<tt>type1 = $text</tt><br>";
               break;
            case 5:
               $msg->header->description = $text;
               if ($debug_mime) echo "<tt>description = $text</tt><br>";
               break;
            case 6:
               $msg->header->encoding = $text;
               if ($debug_mime) echo "<tt>encoding = $text</tt><br>";
               break;
            case 7:
               $msg->header->size = $text;
               if ($debug_mime) echo "<tt>size = $text</tt><br>";
               break;
            default:
               if ($msg->header->type0 == "text" && $elem_num == 8) {
                  // This is a plain text message, so lets get the number of lines
                  // that it contains.
                  $msg->header->num_lines = $text;
                  if ($debug_mime) echo "<tt>num_lines = $text</tt><br>";

               } else if ($msg->header->type0 == "message" && $msg->header->type1 == "rfc822" && $elem_num == 8) {
                  // This is an encapsulated message, so lets start all over again and 
                  // parse this message adding it on to the existing one.
                  $structure = trim($structure);
                  if (substr($structure, 0, 1) == "(") {
                     $e = mime_match_parenthesis (0, $structure);
                     $structure = substr($structure, 0, $e);
                     $structure = substr($structure, 1);
                     $m = mime_parse_structure($structure, $msg->header->entity_id);
                     
                     // the following conditional is there to correct a bug that wasn't
                     // incrementing the entity IDs correctly because of the special case
                     // that message/rfc822 is.  This fixes it fine.
                     if (substr($structure, 1, 1) != "(") 
                        $m->header->entity_id = mime_increment_id(mime_new_element_level($ent_id));
                        
                     // Now we'll go through and reformat the results.
                     if ($m->entities) {
                        for ($i=0; $i < count($m->entities); $i++) {
                           $msg->addEntity($m->entities[$i]);
                        }
                     } else {
                        $msg->addEntity($m);
                     }
                     $structure = ""; 
                  }
               }
               break;
         }
         $elem_num++;
         $text = "";
      }
      // loop through the additional properties and put those in the various headers
      if ($msg->header->type0 != "message") {
         for ($i=0; $i < count($properties); $i++) {
            $msg->header->{$properties[$i]["name"]} = $properties[$i]["value"];
            if ($debug_mime) echo "<tt>".$properties[$i]["name"]." = " . $properties[$i]["value"] . "</tt><br>";
         }
      }
      return $msg;
   }

   // I did most of the MIME stuff yesterday (June 20, 2000), but I couldn't
   // figure out how to do this part, so I decided to go to bed.  I woke up
   // in the morning and had a flash of insight.  I went to the white-board
   // and scribbled it out, then spent a bit programming it, and this is the
   // result.  Nothing complicated, but I think my brain was fried yesterday.
   // Funny how that happens some times.
   //
   // This gets properties in a nested parenthesisized list.  For example,
   // this would get passed something like:  ("attachment" ("filename" "luke.tar.gz"))
   // This returns an array called $props with all paired up properties.
   // It ignores the "attachment" for now, maybe that should change later 
   // down the road.  In this case, what is returned is:
   //    $props[0]["name"] = "filename";
   //    $props[0]["value"] = "luke.tar.gz";
   function mime_get_props ($props, $structure) {
      global $debug_mime;
      while (strlen($structure) > 0) {
         $structure = trim($structure);
         $char = substr($structure, 0, 1);

         if ($char == "\"") {
            $pos = 1;
            $char = substr($structure, $pos, 1);
            while ($char != "\"" && $pos < strlen($structure)) {
               $tmp .= $char;
               $pos++;
               $char = substr($structure, $pos, 1);
            }   
            $structure = trim(substr($structure, strlen($tmp) + 2));
            $char = substr($structure, 0, 1);

            if ($char == "\"") {
               $pos = 1;
               $char = substr($structure, $pos, 1);
               while ($char != "\"" && $pos < strlen($structure)) {
                  $value .= $char;
                  $pos++;
                  $char = substr($structure, $pos, 1);
               }   
               $structure = trim(substr($structure, strlen($tmp) + 2));
               
               $k = count($props);
               $props[$k]["name"] = $tmp;
               $props[$k]["value"] = $value;
            } else if ($char == "(") {
               $end = mime_match_parenthesis (0, $structure);
               $sub = substr($structure, 1, $end-1);
               $props = mime_get_props($props, $sub);
               $structure = substr($structure, strlen($sub) + 2);
            }
            return $props;
         } else if ($char == "(") {
            $end = mime_match_parenthesis (0, $structure);
            $sub = substr($structure, 1, $end-1);
            $props = mime_get_props($props, $sub);
            $structure = substr($structure, strlen($sub) + 2);
            return $props;
         } else {
            return $props;
         }
      }
   }

   //  Matches parenthesis.  It will return the position of the matching
   //  parenthesis in $structure.  For instance, if $structure was:
   //     ("text" "plain" ("val1name", "1") nil ... )
   //     x                                         x
   //  then this would return 42 to match up those two.
   function mime_match_parenthesis ($pos, $structure) {
      $char = substr($structure, $pos, 1); 

      // ignore all extra characters
      while ($pos < strlen($structure)) {
         $pos++;
         $char = substr($structure, $pos, 1); 
         if ($char == ")") {
            return $pos;
         } else if ($char == "(") {
            $pos = mime_match_parenthesis ($pos, $structure);
         }
      }
   }

   function mime_fetch_body ($imap_stream, $id, $ent_id) {
      // do a bit of error correction.  If we couldn't find the entity id, just guess
      // that it is the first one.  That is usually the case anyway.
      if (!$ent_id) $ent_id = 1;

      fputs ($imap_stream, "a001 FETCH $id BODY[$ent_id]\r\n");
      $topline = fgets ($imap_stream, 1024);
      $size = substr ($topline, strpos($topline, "{")+1); 
      $size = substr ($size, 0, strpos($size, "}"));
      $read = fread ($imap_stream, $size);
      return $read;
   }

   /* -[ END MIME DECODING ]----------------------------------------------------------- */



   /** This is the first function called.  It decides if this is a multipart
       message or if it should be handled as a single entity
    **/
   function decodeMime ($body, $header) {
      global $username, $key, $imapServerAddress, $imapPort;
      $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
      sqimap_mailbox_select($imap_stream, $header->mailbox);

      return mime_structure ($imap_stream, $header);
   }

   // This is here for debugging purposese.  It will print out a list
   // of all the entity IDs that are in the $message object.
   function listEntities ($message) {
      if ($message) {
         if ($message->header->entity_id)
         echo "<tt>" . $message->header->entity_id . " : " . $message->header->type0 . "/" . $message->header->type1 . "<br>";
         for ($i = 0; $message->entities[$i]; $i++) {
            $msg = listEntities($message->entities[$i], $ent_id);
            if ($msg)
               return $msg;
         }
      }
   }

   // returns a $message object for a particular entity id
   function getEntity ($message, $ent_id) {
      if ($message) {
         if ($message->header->entity_id == $ent_id && strlen($ent_id) == strlen($message->header->entity_id)) {
            return $message;
         } else {
            for ($i = 0; $message->entities[$i]; $i++) {
               $msg = getEntity ($message->entities[$i], $ent_id);
               if ($msg)
                  return $msg;
            }
         }   
      }
   }

   // figures out what entity to display and returns the $message object
   // for that entity.
   function findDisplayEntity ($message) {
      if ($message) {
         if ($message->header->type0 == "text") {
            if ($message->header->type1 == "plain" ||
                $message->header->type1 == "html") {
               return $message->header->entity_id; 
            }
         } else {
            for ($i=0; $message->entities[$i]; $i++) {
               return findDisplayEntity($message->entities[$i]);
            }   
         }   
      }
   }

   /** This returns a parsed string called $body. That string can then
       be displayed as the actual message in the HTML. It contains
       everything needed, including HTML Tags, Attachments at the
       bottom, etc.
    **/
   function formatBody($message, $color, $wrap_at) {
      // this if statement checks for the entity to show as the
      // primary message. To add more of them, just put them in the
      // order that is their priority.
      global $username, $key, $imapServerAddress, $imapPort;

      $id = $message->header->id;
      $urlmailbox = urlencode($message->header->mailbox);

      $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
      sqimap_mailbox_select($imap_stream, $message->header->mailbox);

      $ent_num = findDisplayEntity ($message);
      $body = mime_fetch_body ($imap_stream, $id, $ent_num); 

      // If there are other types that shouldn't be formatted, add
      // them here 
      if ($message->header->type1 != "html") {   
         $body = translateText($body, $wrap_at, $charset);
      }   

      $body .= "<BR><SMALL><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$ent_num&mailbox=$urlmailbox\">". _("Download this as a file") ."</A></CENTER><BR></SMALL>";

      /** Display the ATTACHMENTS: message if there's more than one part **/
      if ($message->entities) {
         $body .= "<TABLE WIDTH=100% CELLSPACING=0 CELLPADDING=4 BORDER=0><TR><TD BGCOLOR=\"$color[0]\">";
         $body .= "<TT><B>ATTACHMENTS:</B></TT>";
         $body .= "</TD></TR><TR><TD BGCOLOR=\"$color[0]\">";
         $num = 0;

         /** make this recurisve at some point **/
         $body .= formatAttachments ($message, $ent_num, $message->header->mailbox, $id);
         $body .= "</TD></TR></TABLE>";
      }
      return $body;
   }

   // A recursive function that returns a list of attachments with links
   // to where to download these attachments
   function formatAttachments ($message, $ent_id, $mailbox, $id) {
      if ($message) {
         if (!$message->entities) {
            $type0 = strtolower($message->header->type0);
            $type1 = strtolower($message->header->type1);
            
            if ($message->header->entity_id != $ent_id) {
               $filename = $message->header->filename;
               if (trim($filename) == "") {
                  $display_filename = "untitled-".$message->header->entity_id;
               } else {
                  $display_filename = $filename;
               }
   
               $urlMailbox = urlencode($mailbox);
               $ent = urlencode($message->header->entity_id);
               $body .= "<TT>&nbsp;&nbsp;&nbsp;<A HREF=\"../src/download.php?passed_id=$id&mailbox=$urlMailbox&passed_ent_id=$ent\">" . $display_filename . "</A>&nbsp;&nbsp;(TYPE: $type0/$type1)";
               if ($message->header->description)
                  $body .= "&nbsp;&nbsp;<b>" . htmlspecialchars($message->header->description)."</b>";
               if ($message->header->type0 == "image" &&
                   ($message->header->type1 == "jpg" ||
                    $message->header->type1 == "jpeg" ||
                    $message->header->type1 == "gif" ||
                    $message->header->type1 == "png"))
                  $body .= "&nbsp;(<a href=\"../src/download.php?passed_id=$id&mailbox=$urlMailbox&passed_ent_id=$ent&view=true\">"._("view")."</a>)\n";     
               $body .= "</TT><BR>";
               $num++;
            }
            return $body;
         } else {
            for ($i = 0; $i < count($message->entities); $i++) {
               $body .= formatAttachments ($message->entities[$i], $ent_id, $mailbox, $id);
            }
            return $body;
         }
      }
   }


   /** this function decodes the body depending on the encoding type. **/
   function decodeBody($body, $encoding) {
      $encoding = strtolower($encoding);

      if ($encoding == "quoted-printable") {
         $body = quoted_printable_decode($body);

         while (ereg("=\n", $body))
            $body = ereg_replace ("=\n", "", $body);
      } else if ($encoding == "base64") {
         $body = base64_decode($body);
      }

      // All other encodings are returned raw.
      return $body;
   }


   // This functions decode strings that is encoded according to 
   // RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
   function decodeHeader ($string) {
      if (eregi('=\?([^?]+)\?(q|b)\?([^?]+)\?=', 
                $string, $res)) {
         if (ucfirst($res[2]) == "B") {
            $replace = base64_decode($res[3]);
         } else {
            $replace = ereg_replace("_", " ", $res[3]);
            $replace = quoted_printable_decode($replace);
         }

         $replace = charset_decode ($res[1], $replace);

         $string = eregi_replace
            ('=\?([^?]+)\?(q|b)\?([^?]+)\?=',
             $replace, $string);
         // In case there should be more encoding in the string: recurse
         return (decodeHeader($string));
      } else         
         return ($string);
   }

   // Encode a string according to RFC 1522 for use in headers if it
   // contains 8-bit characters or anything that looks like it should
   // be encoded.
   function encodeHeader ($string) {
      global $default_charset;

      // Encode only if the string contains 8-bit characters or =?
      if (ereg("([\200-\377])|=\\?", $string)) {
         $newstring = "=?$default_charset?Q?";
         
         // First the special characters
         $string = str_replace("=", "=3D", $string);
         $string = str_replace("?", "=3F", $string);
         $string = str_replace("_", "=5F", $string);
         $string = str_replace(" ", "_", $string);


         while (ereg("([\200-\377])", $string, $regs)) {
            $replace = $regs[1];
            $insert = "=" . strtoupper(bin2hex($replace));
            $string = str_replace($replace, $insert, $string);
         }

         $newstring = "=?$default_charset?Q?".$string."?=";
         
         return $newstring;
      }

      return $string;
   }

?>
