<?php
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME
    ** messages.
    **
    ** $Id$
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
      
      var $type0 = '', $type1 = '', $boundary = '', $charset = '';
      var $encoding = '', $size = 0, $to = array(), $from = '', $date = '';
      var $cc = array(), $bcc = array(), $reply_to = '', $subject = '';
      var $id = 0, $mailbox = '', $description = '', $filename = '';
      var $entity_id = 0, $message_id = 0, $name = '';
   }
   
   class message {
      /** message is the object that contains messages.  It is a recursive
          object in that through the $entities variable, it can contain 
          more objects of type message.  See documentation in mime.txt for
          a better description of how this works.
       **/   
      var $header = '';
      var $entities = array();
      
      function addEntity ($msg) {
         $this->entities[] = $msg;
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
      //
      // This should use sqimap_read_data instead of reading it itself
      //
      $read = fgets ($imap_stream, 10000);
      $response = substr($read, 0, 4);
      $bodystructure = "";
      while ($response != "a001") {
         $bodystructure .= $read;
         $read = fgets ($imap_stream, 10000);
         $response = substr($read, 0, 4);
      }
      $read = $bodystructure;

      if ($debug_mime) echo "<tt>$read</tt><br><br>\n";
      // isolate the body structure and remove beginning and end parenthesis
      $read = trim(substr ($read, strpos(strtolower($read), "bodystructure") + 13));
      $read = trim(substr ($read, 0, -1));
      $end = mime_match_parenthesis(0, $read);
      while ($end == strlen($read)-1) {
         $read = trim(substr ($read, 0, -1));
         $read = trim(substr ($read, 1));
         $end = mime_match_parenthesis(0, $read);
      }

      if ($debug_mime) echo "<tt>$read</tt><br><br>\n";

      $msg = mime_parse_structure ($read, 0);
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
      if ($debug_mime) echo "<font color=008800><tt>START: mime_parse_structure()</tt></font><br>\n";
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
         $msg = mime_get_element ($structure, $msg, $ent_id);
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
      $properties = array();
      
      while (strlen($structure) > 0) {
         $structure = trim($structure);
         $char = substr($structure, 0, 1);

         if (strtolower(substr($structure, 0, 3)) == "nil") {
            $text = "";
            $structure = substr($structure, 3);
         } else if ($char == "\"") {
            // loop through until we find the matching quote, and return that as a string
            $pos = 1;
            $char = substr($structure, $pos, 1);
	    $text = "";
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
            $text = "";
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
               $msg->header->type0 = strtolower($text);
               if ($debug_mime) echo "<tt>type0 = ".strtolower($text)."</tt><br>";
               break;
            case 2: 
               $msg->header->type1 = strtolower($text);
               if ($debug_mime) echo "<tt>type1 = ".strtolower($text)."</tt><br>";
               break;
            case 5:
               $msg->header->description = $text;
               if ($debug_mime) echo "<tt>description = $text</tt><br>";
               break;
            case 6:
               $msg->header->encoding = strtolower($text);
               if ($debug_mime) echo "<tt>encoding = ".strtolower($text)."</tt><br>";
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
	    $tmp = "";
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
	       $value = "";
               while ($char != "\"" && $pos < strlen($structure)) {
                  $value .= $char;
                  $pos++;
                  $char = substr($structure, $pos, 1);
               }   
               $structure = trim(substr($structure, strlen($tmp) + 2));
               
               $k = count($props);
               $props[$k]["name"] = strtolower($tmp);
               $props[$k]["value"] = $value;
            } else if ($char == "(") {
               $end = mime_match_parenthesis (0, $structure);
               $sub = substr($structure, 1, $end-1);
	       if (! isset($props))
	           $props = array();
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
      // If inside of a string, skip string -- Boundary IDs and other
      // things can have ) in them.
      if ($char != '(')
          return strlen($structure);
      while ($pos < strlen($structure)) {
         $pos++;
         $char = substr($structure, $pos, 1); 
         if ($char == ")") {
            return $pos;
         } else if ($char == '"') {
            $pos ++;
            while (substr($structure, $pos, 1) != '"' && 
               $pos < strlen($structure)) {
	       if (substr($structure, $pos, 2) == '\\"')
	           $pos ++;
	       elseif (substr($structure, $pos, 2) == '\\\\')
	           $pos ++;
               $pos ++;
            }
         } else if ($char == "(") {
            $pos = mime_match_parenthesis ($pos, $structure);
         }
      }
      echo "Error decoding mime structure.  Report this as a bug!<br>\n";
      return $pos;
   }

   function mime_fetch_body ($imap_stream, $id, $ent_id) {
      // do a bit of error correction.  If we couldn't find the entity id, just guess
      // that it is the first one.  That is usually the case anyway.
      if (!$ent_id) $ent_id = 1;

      fputs ($imap_stream, "a010 FETCH $id BODY[$ent_id]\r\n");
      $data = sqimap_read_data ($imap_stream, 'a010', true, $response, $message);
      $topline = array_shift($data);
      while (! ereg('\\* [0-9]+ FETCH ', $topline) && data)
          $topline = array_shift($data);
      $wholemessage = implode('', $data);

      if (ereg('\\{([^\\}]*)\\}', $topline, $regs)) {
         return substr($wholemessage, 0, $regs[1]);
      }
      else if (ereg('"([^"]*)"', $topline, $regs)) {
         return $regs[1];
      }
      
      $str = "Body retrival error.  Please report this bug!\n";
      $str .= "Response:  $response\n";
      $str .= "Message:  $message\n";
      $str .= "FETCH line:  $topline";
      $str .= "---------------\n$wholemessage";
      foreach ($data as $d)
      {
          $str .= htmlspecialchars($d) . "\n";
      }
      return $str;
      
      return "Body retrival error, please report this bug!\n\nTop line is \"$topline\"\n";
   }

   function mime_print_body_lines ($imap_stream, $id, $ent_id, $encoding) {
      // do a bit of error correction.  If we couldn't find the entity id, just guess
      // that it is the first one.  That is usually the case anyway.
      if (!$ent_id) $ent_id = 1;

      fputs ($imap_stream, "a001 FETCH $id BODY[$ent_id]\r\n");
	  $cnt = 0;
	  $continue = true;
	  	$read = fgets ($imap_stream,4096);
		while (!ereg("^a001 (OK|BAD|NO)(.*)$", $read, $regs)) {
			if (trim($read) == ")==") {
				$read1 = $read;
	  			$read = fgets ($imap_stream,4096);
				if (ereg("^a001 (OK|BAD|NO)(.*)$", $read, $regs)) {
					return;
				} else {
					echo decodeBody($read1, $encoding);
					echo decodeBody($read, $encoding);
				}
			} else if ($cnt) {
				echo decodeBody($read, $encoding);
			}
	  		$read = fgets ($imap_stream,4096);
			$cnt++;
		}
   }

   /* -[ END MIME DECODING ]----------------------------------------------------------- */



   /** This is the first function called.  It decides if this is a multipart
       message or if it should be handled as a single entity
    **/
   function decodeMime ($imap_stream, &$header) {
      global $username, $key, $imapServerAddress, $imapPort;
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
            for ($i = 0; isset($message->entities[$i]); $i++) {
               $msg = getEntity ($message->entities[$i], $ent_id);
               if ($msg)
                  return $msg;
            }
         }   
      }
   }

   // figures out what entity to display and returns the $message object
   // for that entity.
   function findDisplayEntity ($message, $textOnly = 1, $next = 'none')
   {
      global $show_html_default;
      
      if (! $message)
	return;

      // Show text/plain or text/html -- the first one we find.
      if ($message->header->type0 == 'text' && 
	  ($message->header->type1 == 'plain' ||
	   $message->header->type2 == 'html'))
	{
	   // If the next part is an HTML version, this will
	   // all be true.  Show it, if the user so desires.
	   // HTML mails this way all have entity_id of 2.  1 = text/plain
	   if ($next != 'none' &&
	       $textOnly == 0 &&
	       $next->header->type0 == "text" &&
	       $next->header->type1 == "html" &&
	       $next->header->entity_id == 2 &&
	       $message->header->type1 == "plain" &&
	       isset($show_html_default) &&
	       $show_html_default)
	     $message = $next;
	   
	   if (isset($message->header->entity_id))
	     return $message->header->entity_id;
	} 
      else 
	{
	   for ($i=0; $message->entities[$i]; $i++) 
	     {
		$next = 'none';
		if (isset($message->entities[$i + 1]))
		  $next = $message->entities[$i + 1];
		$entity = findDisplayEntity($message->entities[$i],
                  $textOnly, $next);
		if ($entity != 0)
		  return $entity;
	     }   
	}   
      return 0;
   }

   /** This returns a parsed string called $body. That string can then
       be displayed as the actual message in the HTML. It contains
       everything needed, including HTML Tags, Attachments at the
       bottom, etc.
    **/
   function formatBody($imap_stream, $message, $color, $wrap_at) {
      // this if statement checks for the entity to show as the
      // primary message. To add more of them, just put them in the
      // order that is their priority.
      global $startMessage, $username, $key, $imapServerAddress, $imapPort;

      $id = $message->header->id;
      $urlmailbox = urlencode($message->header->mailbox);

      // Get the right entity and redefine message to be this entity
      // Pass the 0 to mean that we want the 'best' viewable one
      $ent_num = findDisplayEntity ($message, 0);
      $body_message = getEntity($message, $ent_num);
      if (($body_message->header->type0 == "text") || 
          ($body_message->header->type0 == "rfc822")) {
   
         $body = mime_fetch_body ($imap_stream, $id, $ent_num);
         $body = decodeBody($body, $body_message->header->encoding);
   
         // If there are other types that shouldn't be formatted, add
         // them here 
         if ($body_message->header->type1 != "html") {   
            translateText($body, $wrap_at, $body_message->header->charset);
         }   
   
         $body .= "<SMALL><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$ent_num&mailbox=$urlmailbox\">". _("Download this as a file") ."</A></CENTER><BR></SMALL>";
   
         /** Display the ATTACHMENTS: message if there's more than one part **/
         $body .= "</TD></TR></TABLE>";
         if (isset($message->entities[0])) {
            $body .= formatAttachments ($message, $ent_num, $message->header->mailbox, $id);
         }
      } else {
         $body .= formatAttachments ($message, -1, $message->header->mailbox, $id);
      }
      return $body;
   }

   // A recursive function that returns a list of attachments with links
   // to where to download these attachments
   function formatAttachments ($message, $ent_id, $mailbox, $id) {
      global $where, $what;
      global $startMessage, $color;
      static $ShownHTML = 0;
      
	  $body = "";
      if ($ShownHTML == 0)
      {
            $ShownHTML = 1;
            
            $body .= "<TABLE WIDTH=100% CELLSPACING=0 CELLPADDING=2 BORDER=0 BGCOLOR=\"$color[0]\"><TR>\n";
            $body .= "<TH ALIGN=\"left\" BGCOLOR=\"$color[9]\"><B>\n";
            $body .= _("Attachments") . ':';
            $body .= "</B></TH></TR><TR><TD>\n";
            
            $body .= "<TABLE CELLSPACING=0 CELLPADDING=1 BORDER=0>\n";
            
            $body .= formatAttachments ($message, $ent_id, $mailbox, $id);
            
            $body .= "</TABLE></TD></TR></TABLE>";
            
            return $body;
      }
      
      if ($message) {
         if (!$message->entities) {
            $type0 = strtolower($message->header->type0);
            $type1 = strtolower($message->header->type1);
            $name = decodeHeader($message->header->name);
            
            if ($message->header->entity_id != $ent_id) {
               $filename = decodeHeader($message->header->filename);
               if (trim($filename) == "") {
                  if (trim($name) == "") { 
                     $display_filename = "untitled-".$message->header->entity_id; 
                  } else { 
                     $display_filename = $name; 
                     $filename = $name; 
                  } 
               } else {
                  $display_filename = $filename;
               }
   
               $urlMailbox = urlencode($mailbox);
               $ent = urlencode($message->header->entity_id);
               
               $DefaultLink = 
                  "../src/download.php?startMessage=$startMessage&passed_id=$id&mailbox=$urlMailbox&passed_ent_id=$ent";
               if ($where && $what)
                  $DefaultLink .= '&where=' . urlencode($where) . '&what=' . urlencode($what);
               $Links['download link']['text'] = _("download");
               $Links['download link']['href'] = 
                   "../src/download.php?absolute_dl=true&passed_id=$id&mailbox=$urlMailbox&passed_ent_id=$ent";
               $ImageURL = '';
               
               $HookResults = do_hook("attachment $type0/$type1", $Links,
                   $startMessage, $id, $urlMailbox, $ent, $DefaultLink, 
                   $display_filename, $where, $what);

               $Links = $HookResults[1];
               $DefaultLink = $HookResults[6];

               $body .= '<TR><TD>&nbsp;&nbsp;</TD><TD>';
               $body .= "<A HREF=\"$DefaultLink\">$display_filename</A>&nbsp;</TD>";
               $body .= '<TD><SMALL><b>' . show_readable_size($message->header->size) . 
                   '</b>&nbsp;&nbsp;</small></TD>';
               $body .= "<TD><SMALL>[ $type0/$type1 ]&nbsp;</SMALL></TD>";
               $body .= '<TD><SMALL>';
               if ($message->header->description)
                  $body .= '<b>' . htmlspecialchars($message->header->description) . '</b>';
               $body .= '</SMALL></TD><TD><SMALL>&nbsp;';
               
               
               $SkipSpaces = 1;
               foreach ($Links as $Val)
               {
                  if ($SkipSpaces)
                  {
                     $SkipSpaces = 0;
                  }
                  else
                  {
                     $body .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
                  }
                  $body .= '<a href="' . $Val['href'] . '">' .  $Val['text'] . '</a>';
               }
               
               unset($Links);
               
               $body .= "</SMALL></TD></TR>\n";
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
      $body = str_replace("\r\n", "\n", $body);
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
      if (eregi('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=', 
                $string, $res)) {
         if (ucfirst($res[2]) == "B") {
            $replace = base64_decode($res[3]);
         } else {
            $replace = ereg_replace("_", " ", $res[3]);
	    // Convert lowercase Quoted Printable to uppercase for
	    // quoted_printable_decode to understand it.
	    while (ereg("(=(([0-9][abcdef])|([abcdef][0-9])|([abcdef][abcdef])))", $replace, $res)) {
	       $replace = str_replace($res[1], strtoupper($res[1]), $replace);
	    }
            $replace = quoted_printable_decode($replace);
         }

         $replace = charset_decode ($res[1], $replace);

         // Remove the name of the character set.
         $string = eregi_replace ('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=',
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
      if (ereg("([\200-\377]|=\\?)", $string)) {
         
         // First the special characters
         $string = str_replace("=", "=3D", $string);
         $string = str_replace("?", "=3F", $string);
         $string = str_replace("_", "=5F", $string);
         $string = str_replace(" ", "_", $string);

	 for ( $ch = 127 ; $ch <= 255 ; $ch++ ) {
	    $replace = chr($ch);
	    $insert = sprintf("=%02X", $ch);
            $string = str_replace($replace, $insert, $string);
         }

         $newstring = "=?$default_charset?Q?".$string."?=";
         
         return $newstring;
      }

      return $string;
   }

?>
