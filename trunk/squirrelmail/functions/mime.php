<?php
   /** mime.php
    **
    ** This contains the functions necessary to detect and decode MIME
    ** messages.
    **
    ** $Id$
    **/

   if (defined('mime_php'))
      return;
   define('mime_php', true);

   require_once('../functions/imap.php');

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

      sqimap_messages_flag ($imap_stream, $header->id, $header->id, 'Seen');
      $ssid = sqimap_session_id();
      $lsid = strlen( $ssid );
      $id = $header->id;
      fputs ($imap_stream, "$ssid FETCH $id BODYSTRUCTURE\r\n");
      //
      // This should use sqimap_read_data instead of reading it itself
      //
      $read = fgets ($imap_stream, 10000);
      $bodystructure = '';
      while( substr($read, 0, $lsid) <> $ssid && 
             !feof( $imap_stream ) ) {
         $bodystructure .= $read;
         $read = fgets ($imap_stream, 10000);
      }
      $read = $bodystructure;

      // isolate the body structure and remove beginning and end parenthesis
      $read = trim(substr ($read, strpos(strtolower($read), 'bodystructure') + 13));
      $read = trim(substr ($read, 0, -1));
      $end = mime_match_parenthesis(0, $read);
      while ($end == strlen($read)-1) {
         $read = trim(substr ($read, 0, -1));
         $read = trim(substr ($read, 1));
         $end = mime_match_parenthesis(0, $read);
      }

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
   
      $msg = new message();
      if ($structure{0} == '(') {
         $ent_id = mime_new_element_level($ent_id);
         $start = $end = -1;
         do {
            $start = $end+1;
            $end = mime_match_parenthesis ($start, $structure);

            $element = substr($structure, $start+1, ($end - $start)-1);
            $ent_id = mime_increment_id ($ent_id);
            $newmsg = mime_parse_structure ($element, $ent_id);
            $msg->addEntity ($newmsg);
         } while ($structure{$end+1} == '(');
      } else {
         // parse the elements
         $msg = mime_get_element ($structure, $msg, $ent_id);
      }
      return $msg;
   }

   // Increments the element ID.  An element id can look like any of
   // the following:  1, 1.2, 4.3.2.4.1, etc.  This function increments
   // the last number of the element id, changing 1.2 to 1.3.
   function mime_increment_id ($id) {

      if (strpos($id, ".")) {
         $first = substr($id, 0, strrpos($id, "."));
         $last = substr($id, strrpos($id, ".")+1);
         $last++;
         $new = $first . "." .$last;
      } else {
         $new = $id + 1;
      }

      return $new;
   }

   // See comment for mime_increment_id().
   // This adds another level on to the entity_id changing 1.3 to 1.3.0
   // NOTE:  1.3.0 is not a valid element ID.  It MUST be incremented
   //        before it can be used.  I left it this way so as not to have
   //        to make a special case if it is the first entity_id.  It
   //        always increments it, and that works fine.
   function mime_new_element_level ($id) {

      if (!$id) {
          $id = 0;
      } else {
          $id = $id . '.0';
      }

      return( $id );
   }

   function mime_get_element (&$structure, $msg, $ent_id) {

      $elem_num = 1;
      $msg->header = new msg_header();
      $msg->header->entity_id = $ent_id;
      $properties = array();

      while (strlen($structure) > 0) {
         $structure = trim($structure);
         $char = $structure{0};

         if (strtolower(substr($structure, 0, 3)) == 'nil') {
            $text = '';
            $structure = substr($structure, 3);
         } else if ($char == '"') {
            // loop through until we find the matching quote, and return that as a string
            $pos = 1;
            $text = '';
            while ( ($char = $structure{$pos} ) <> '"' && $pos < strlen($structure)) {
               $text .= $char;
               $pos++;
            }
            $structure = substr($structure, strlen($text) + 2);
         } else if ($char == '(') {
            // comment me
            $end = mime_match_parenthesis (0, $structure);
            $sub = substr($structure, 1, $end-1);
            $properties = mime_get_props($properties, $sub);
            $structure = substr($structure, strlen($sub) + 2);
         } else {
            // loop through until we find a space or an end parenthesis
            $pos = 0;
            $char = $structure{$pos};
            $text = '';
            while ($char != ' ' && $char != ')' && $pos < strlen($structure)) {
               $text .= $char;
               $pos++;
               $char = $structure{$pos};
            }
            $structure = substr($structure, strlen($text));
         }

         // This is where all the text parts get put into the header
         switch ($elem_num) {
            case 1:
               $msg->header->type0 = strtolower($text);
               break;
            case 2:
               $msg->header->type1 = strtolower($text);
               break;
            case 4: // Id
               // Invisimail enclose images with <>
               $msg->header->id = str_replace( '<', '', str_replace( '>', '', $text ) );
               break;               
            case 5:
               $msg->header->description = $text;
               break;
            case 6:
               $msg->header->encoding = strtolower($text);
               break;
            case 7:
               $msg->header->size = $text;
               break;
            default:
               if ($msg->header->type0 == 'text' && $elem_num == 8) {
                  // This is a plain text message, so lets get the number of lines
                  // that it contains.
                  $msg->header->num_lines = $text;

               } else if ($msg->header->type0 == 'message' && $msg->header->type1 == 'rfc822' && $elem_num == 8) {
                  // This is an encapsulated message, so lets start all over again and
                  // parse this message adding it on to the existing one.
                  $structure = trim($structure);
                  if ( $structure{0} == '(' ) {
                     $e = mime_match_parenthesis (0, $structure);
                     $structure = substr($structure, 0, $e);
                     $structure = substr($structure, 1);
                     $m = mime_parse_structure($structure, $msg->header->entity_id);

                     // the following conditional is there to correct a bug that wasn't
                     // incrementing the entity IDs correctly because of the special case
                     // that message/rfc822 is.  This fixes it fine.
                     if (substr($structure, 1, 1) != '(')
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
      if ($msg->header->type0 != 'message') {
         for ($i=0; $i < count($properties); $i++) {
            $msg->header->{$properties[$i]['name']} = $properties[$i]['value'];
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
   
      while (strlen($structure) > 0) {
         $structure = trim($structure);
         $char = $structure{0};

         if ($char == '"') {
            $pos = 1;
            $tmp = '';
            while ( ( $char = $structure{$pos} ) != '"' && 
                    $pos < strlen($structure)) {
               $tmp .= $char;
               $pos++;
            }
            $structure = trim(substr($structure, strlen($tmp) + 2));
            $char = $structure{0};

            if ($char == '"') {
               $pos = 1;
               $value = '';
               while ( ( $char = $structure{$pos} ) != '"' &&
                       $pos < strlen($structure) ) {
                  $value .= $char;
                  $pos++;
               }
               $structure = trim(substr($structure, strlen($tmp) + 2));

               $k = count($props);
               $props[$k]['name'] = strtolower($tmp);
               $props[$k]['value'] = $value;
            } else if ($char == '(') {
               $end = mime_match_parenthesis (0, $structure);
               $sub = substr($structure, 1, $end-1);
           if (! isset($props))
               $props = array();
               $props = mime_get_props($props, $sub);
               $structure = substr($structure, strlen($sub) + 2);
            }
            return $props;
         } else if ($char == '(') {
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

      $j = strlen( $structure );

      // ignore all extra characters
      // If inside of a string, skip string -- Boundary IDs and other
      // things can have ) in them.
      if( $structure{$pos} != '(' )
         return( $j );

      while( $pos < $j ) {
         $pos++;
         if ($structure{$pos} == ')') {
            return $pos;
         } elseif ($structure{$pos} == '"') {
            $pos++;
            while( $structure{$pos} != '"' &&
               $pos < $j ) {
               if (substr($structure, $pos, 2) == '\\"')
                  $pos++;
               elseif (substr($structure, $pos, 2) == '\\\\')
                  $pos++;
               $pos++;
            }
         } elseif ( $structure{$pos} == '(' ) {
            $pos = mime_match_parenthesis ($pos, $structure);
         }
      }
      echo "Error decoding mime structure.  Report this as a bug!<br>\n";
      return( $pos );
   }

    function mime_fetch_body ($imap_stream, $id, $ent_id ) {
        // do a bit of error correction.  If we couldn't find the entity id, just guess
        // that it is the first one.  That is usually the case anyway.
        if (!$ent_id) 
            $ent_id = 1;
        $sid = sqimap_session_id();
        fputs ($imap_stream, "$sid FETCH $id BODY[$ent_id]\r\n");
        $data = sqimap_read_data ($imap_stream, $sid, true, $response, $message);
        $topline = array_shift($data);
        while (! ereg('\\* [0-9]+ FETCH ', $topline) && $data)
            $topline = array_shift($data);
        $wholemessage = implode('', $data);
        if (ereg('\\{([^\\}]*)\\}', $topline, $regs)) {
            $ret = substr( $wholemessage, 0, $regs[1] );
            /*
                There is some information in the content info header that could be important
                in order to parse html messages. Let's get them here.
            */
            if( $ret{0} == '<' ) {
                fputs ($imap_stream, "$sid FETCH $id BODY[$ent_id.MIME]\r\n");
                $data = sqimap_read_data ($imap_stream, $sid, true, $response, $message);
                $base = '';
                $k = 10;
                foreach( $data as $d ) {
                    if( substr( $d, 0, 13 ) == 'Content-Base:' ) {
                        $j = strlen( $d );
                        $i = 13;
                        $base = '';
                        while( $i < $j &&
                               ( !isNoSep( $d{$i} ) || $d{$i} == '"' )  )
                            $i++;
                        while( $i < $j ) {
                            if( isNoSep( $d{$i} ) )
                                $base .= $d{$i};
                            $i++;
                        }
                        $k = 0;
                    } elseif( $k == 1 && !isnosep( $d{0} ) ) {
                        $base .= substr( $d, 1 );
                    }
                    $k++;
                }
                if( $base <> '' )
                    $ret = "<base href=\"$base\">" . $ret;
            }
        } else if (ereg('"([^"]*)"', $topline, $regs)) {
            $ret = $regs[1];
        } else {
            $ret = "Body retrieval error.  Please report this bug!\n" .
                   "Response:  $response\n" .
                   "Message:  $message\n" .
                   "FETCH line:  $topline" .
                   "---------------\n$wholemessage";
    
            foreach ($data as $d) {
              $ret .= htmlspecialchars($d) . "\n";
            }
        }
        return( $ret );
    }

   function mime_print_body_lines ($imap_stream, $id, $ent_id, $encoding) {
      // do a bit of error correction.  If we couldn't find the entity id, just guess
      // that it is the first one.  That is usually the case anyway.
      if (!$ent_id) $ent_id = 1;
      $sid = sqimap_session_id();
      // Don't kill the connection if the browser is over a dialup
      // and it would take over 30 seconds to download it.
      set_time_limit(0);
      
      fputs ($imap_stream, "$sid FETCH $id BODY[$ent_id]\r\n");
      $cnt = 0;
      $continue = true;
      $read = fgets ($imap_stream,4096);
      // This could be bad -- if the section has sqimap_session_id() . ' OK'
      // or similar, it will kill the download.
      while (!ereg("^".$sid." (OK|BAD|NO)(.*)$", $read, $regs)) {
          if (trim($read) == ')==') {
              $read1 = $read;
              $read = fgets ($imap_stream,4096);
              if (ereg("^".$sid." (OK|BAD|NO)(.*)$", $read, $regs)) {
                  return;
              } else {
                  echo decodeBody($read1, $encoding) .
                       decodeBody($read, $encoding);
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
   /*
   function listEntities ($message) {
      if ($message) {
         if ($message->header->entity_id)
         echo "<tt>" . $message->header->entity_id . ' : ' . $message->header->type0 . '/' . $message->header->type1 . '<br>';
         for ($i = 0; $message->entities[$i]; $i++) {
            $msg = listEntities($message->entities[$i], $ent_id);
            if ($msg)
               return $msg;
         }
      }
   }
   */

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
    function findDisplayEntity ($message, $textOnly = 1)   {
        global $show_html_default;
        
        $entity = 0;
        
        if ($message) {
            if ( $message->header->type0 == 'multipart' &&
                 ( $message->header->type1 == 'alternative' ||
                   $message->header->type1 == 'related' ) &&
                 $show_html_default && ! $textOnly ) {
                $entity = findDisplayEntityHTML($message);
            }
            
            // Show text/plain or text/html -- the first one we find.
            if ( $entity == 0 &&
                 $message->header->type0 == 'text' &&
                 ( $message->header->type1 == 'plain' ||
                   $message->header->type1 == 'html' ) &&
                 isset($message->header->entity_id) ) {
                $entity = $message->header->entity_id;
            }
            
            $i = 0;
            while ($entity == 0 && isset($message->entities[$i]) ) {
                $entity = findDisplayEntity($message->entities[$i], $textOnly);
                $i++;
            }
        }
      
        return( $entity );
    }

   // Shows the HTML version
   function findDisplayEntityHTML ($message) {
      if ($message->header->type0 == 'text' &&
          $message->header->type1 == 'html' &&
      isset($message->header->entity_id))
     return $message->header->entity_id;
      for ($i = 0; isset($message->entities[$i]); $i ++) {
         $entity = findDisplayEntityHTML($message->entities[$i]);
     if ($entity != 0)
        return $entity;
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
      global $startMessage, $username, $key, $imapServerAddress, $imapPort,
          $show_html_default;

      $id = $message->header->id;
      $urlmailbox = urlencode($message->header->mailbox);

      // Get the right entity and redefine message to be this entity
      // Pass the 0 to mean that we want the 'best' viewable one
      $ent_num = findDisplayEntity ($message, 0);
      $body_message = getEntity($message, $ent_num);
      if (($body_message->header->type0 == 'text') ||
          ($body_message->header->type0 == 'rfc822')) {

         $body = mime_fetch_body ($imap_stream, $id, $ent_num);
         $body = decodeBody($body, $body_message->header->encoding);
         $hookResults = do_hook("message_body", $body);
         $body = $hookResults[1];

         // If there are other types that shouldn't be formatted, add
         // them here
         if ($body_message->header->type1 == 'html') {
            if( $show_html_default <> 1 ) {
                $body = strip_tags( $body );
                translateText($body, $wrap_at, $body_message->header->charset);
            } else {
                $body = MagicHTML( $body, $id );
            }
         } else {
            translateText($body, $wrap_at, $body_message->header->charset);
         }

         $body .= "<SMALL><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$ent_num&mailbox=$urlmailbox&showHeaders=1\">". _("Download this as a file") ."</A></CENTER><BR></SMALL>";

         /** Display the ATTACHMENTS: message if there's more than one part **/
         $body .= "</TD></TR></TABLE>";
         if (isset($message->entities[0])) {
            $body .= formatAttachments ($message, $ent_num, $message->header->mailbox, $id);
         }
         $body .= "</TD></TR></TABLE>";
      } else {
         $body = formatAttachments ($message, -1, $message->header->mailbox, $id);
      }
      return( $body );
   }

   // A recursive function that returns a list of attachments with links
   // to where to download these attachments
   function formatAttachments ($message, $ent_id, $mailbox, $id) {
      global $where, $what;
      global $startMessage, $color;
      static $ShownHTML = 0;

      $body = "";
      if ($ShownHTML == 0) {
            $ShownHTML = 1;

            $body .= "<TABLE WIDTH=100% CELLSPACING=0 CELLPADDING=2 BORDER=0 BGCOLOR=\"$color[0]\"><TR>\n" .
                     "<TH ALIGN=\"left\" BGCOLOR=\"$color[9]\"><B>\n" .
                     _("Attachments") . ':' .
                     "</B></TH></TR><TR><TD>\n" .
                     "<TABLE CELLSPACING=0 CELLPADDING=1 BORDER=0>\n" .
                     formatAttachments ($message, $ent_id, $mailbox, $id) .
                     "</TABLE></TD></TR></TABLE>";

            return( $body );
      }

      if ($message) {
         if (!$message->entities) {
            $type0 = strtolower($message->header->type0);
            $type1 = strtolower($message->header->type1);
            $name = decodeHeader($message->header->name);

            if ($message->header->entity_id != $ent_id) {
               $filename = decodeHeader($message->header->filename);
               if (trim($filename) == '') {
                  if (trim($name) == '') {
                     if( trim( $message->header->id ) == '' )
                        $display_filename = 'untitled-[' . $message->header->entity_id . ']' ;
                     else
                        $display_filename = 'cid: ' . $message->header->id;
                     // $display_filename = 'untitled-[' . $message->header->entity_id . ']' ;
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

               $body .= '<TR><TD>&nbsp;&nbsp;</TD><TD>' .
                        "<A HREF=\"$DefaultLink\">$display_filename</A>&nbsp;</TD>" .
                        '<TD><SMALL><b>' . show_readable_size($message->header->size) .
                        '</b>&nbsp;&nbsp;</small></TD>' .
                        "<TD><SMALL>[ $type0/$type1 ]&nbsp;</SMALL></TD>" .
                        '<TD><SMALL>';
               if ($message->header->description)
                  $body .= '<b>' . htmlspecialchars($message->header->description) . '</b>';
               $body .= '</SMALL></TD><TD><SMALL>&nbsp;';


               $SkipSpaces = 1;
               foreach ($Links as $Val) {
                  if ($SkipSpaces) {
                     $SkipSpaces = 0;
                  } else {
                     $body .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
                  }
                  $body .= '<a href="' . $Val['href'] . '">' .  $Val['text'] . '</a>';
               }

               unset($Links);

               $body .= "</SMALL></TD></TR>\n";
            }
         } else {
            for ($i = 0; $i < count($message->entities); $i++) {
               $body .= formatAttachments ($message->entities[$i], $ent_id, $mailbox, $id);
            }
         }
         return( $body );
      }
   }


   /** this function decodes the body depending on the encoding type. **/
   function decodeBody($body, $encoding) {
      $body = str_replace("\r\n", "\n", $body);
      $encoding = strtolower($encoding);

      global $show_html_default;

      if ($encoding == 'quoted-printable') {
         $body = quoted_printable_decode($body);
         
         
         /*
            Following code has been comented as I see no reason for it.
            If there is any please tell me a mingo@rotedic.com
            
         while (ereg("=\n", $body))
            $body = ereg_replace ("=\n", "", $body);
        */
      } else if ($encoding == 'base64') {
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
     $j = strlen( $string  );
     $l = FALSE;                             // Must be encoded ?
     $ret = '';
     for( $i=0; $i < $j; ++$i) {
        switch( $string{$i} ) {
           case '=':
          $ret .= '=3D';
          break;
       case '?':
          $l = TRUE;
          $ret .= '=3F';
          break;
       case '_':
          $ret .= '=5F';
          break;
       case ' ':
          $ret .= '_';
          break;
      default:
          $k = ord( $string{$i} );
          if( $k > 126 ) {
             $ret .= sprintf("=%02X", $k);
             $l = TRUE;
          } else
             $ret .= $string{$i};
        }
     }

     if( $l )
        $string = "=?$default_charset?Q?$ret?=";

     return( $string );
 }

   /*
    Strips dangerous tags from html messages.
   */

   function MagicHTML( $body, $id ) {

        global $message, $PHP_SELF, $HTTP_SERVER_VARS;

        $j = strlen( $body );   // Legnth of the HTML
        $ret = '';              // Returned string
        $bgcolor = '#ffffff';   // Background style color (defaults to white)
        $leftmargin = '';       // Left margin style
        $title = '';            // HTML title if any

        $i = 0;
        while( $i < $j ) {
            if( $body{$i} == '<' ) {
                $tag = $body{$i+1}.$body{$i+2}.$body{$i+3}.$body{$i+4};
                switch( strtoupper( $tag ) ) {
                    // Strips the entire tag and contents
                    case 'APPL':
                    case 'EMBB':
                    case 'FRAM':
                    case 'SCRI':
                    case 'OBJE':
                        $etg = '/' . $tag;
                        while( $body{$i+1}.$body{$i+2}.$body{$i+3}.$body{$i+4}.$body{$i+5} <> $etg  &&
                               $i < $j  ) $i++;
                        while( $i < $j && $body{++$i} <> '>' );
                        // $ret .= "<!-- $tag removed -->";
                        break;
                    // Substitute Title
                    case 'TITL':
                        $i += 5;
                        while( $body{$i} <> '>' &&  // </title>
                               $i < $j )
                                $i++;
                        $i++;
                        $title = '';
                        while( $body{$i} <> '<' &&  // </title>
                               $i < $j ) {
                            $title .= $body{$i};
                            $i++;
                        }
                        $i += 7;
                        break;
                    // Destroy these tags
                    case 'HTML':
                    case 'HEAD':
                    case '/HTM':
                    case '/HEA':
                    case '!DOC':
                    case 'META':
                    case 'DIV ':
                    case '/DIV':
                    case '!-- ':
                        $i += 4;
                        while( $body{$i}  <> '>' &&
                               $i < $j )
                            $i++;
                        // $i++;
                        break;
                    case 'STYL':
                        $i += 5;
                        while( $body{$i} <> '>' &&  // </title>
                               $i < $j )
                                $i++;
                        $i++;
                        // We parse the style to look for interesting stuff
                        $styleblk = '';
                        while( $body{$i} <> '>' &&
                               $i < $j ) {
                            // First we get the name of the style
                            $style = '';
                            while( $body{$i} <> '>' &&
                                   $body{$i} <> '<' &&
                                   $body{$i} <> '{' &&
                                   $i < $j ) {
                               if( isnoSep( $body{$i} ) )
                                   $style .= $body{$i};
                               $i++;
                            }
                            stripComments( &$i, $j, &$body );
                            $style = strtoupper( trim( $style ) );
                            if( $style == 'BODY' ) {
                                // Next we look into the definitions of the body style
                                while( $body{$i} <> '>' &&
                                       $body{$i} <> '}' &&
                                       $i < $j ) {
                                    // We look for the background color if any.
                                    if( substr( $body, $i, 17 ) == 'BACKGROUND-COLOR:' ) {
                                        $i += 17;
                                        $bgcolor = getStyleData( $i, $j, $body );
                                    } elseif ( substr( $body, $i, 12 ) == 'MARGIN-LEFT:' ) {
                                        $i += 12;
                                        $leftmargin = getStyleData( $i, $j, $body );
                                    }
                                    $i++;
                                }
                            } else {
                                // Other style are mantained
                                $styleblk .= "$style ";
                                while( $body{$i} <> '>' &&
                                       $body{$i} <> '<' &&
                                       $body{$i} <> '}' &&
                                       $i < $j ) {
                                    $styleblk .= $body{$i};
                                    $i++;
                                }
                                $styleblk .= $body{$i};
                            }
                            stripComments( &$i, $j, &$body );
                            if( $body{$i} <> '>' )
                                $i++;
                        }
                        if( $styleblk <> '' )
                            $ret .= "<style>$styleblk";
                        break;
                    case 'BODY':
                        if( $title <> '' )
                            $ret .= '<b>' . _("Title:") . " </b>$title<br>\n";
                        $ret .= "<TABLE";
                        $i += 5;
                        $ret .= stripEvent( $i, $j, $body, $id, $base );
                        //if( $bgcolor <> '' )
                            $ret .= " bgcolor=$bgcolor";
                        $ret .= ' width=100%><tr>';
                        if( $leftmargin <> '' )
                            $ret .= "<td width=$leftmargin>&nbsp;</td>";
                        $ret .= '<td>';
                        break;
                    case 'BASE':
                        $i += 5;
                        $base = '';
                        while( !isNoSep( $body{$i} ) &&
                               $i < $j )
                                $i++;
                        if( strcasecmp( substr( $base, 0, 4 ), 'href'  ) ) {
                                $i += 5;
                                while( !isNoSep( $body{$i} ) &&
                                       $i < $j )
                                        $i++;
                                while( $body{$i} <> '>' &&
                                       $i < $j ) {
                                    if( $body{$i} <> '"' )
                                        $base .= $body{$i};
                                        $i++;
                                }
                                // Debuging $ret .= "<!-- base == $base -->";
                                if( strcasecmp( substr( $base, 0, 4 ), 'file' ) <> 0 )
                                        $ret .= "\n<BASE HREF=\"$base\">\n";
                        }
                        break;
                    case '/BOD':
                        $ret .= '</td></tr></TABLE>';
                        $i += 6;
                        break;
                    default:
                        // Following tags can contain some event handler, lets search it
                        stripComments( $i, $j, $body );
                        $ret .= stripEvent( $i, $j, $body, $id, $base ) . '>';
                        // $ret .= "<!-- $tag detected -->";
                }
            } else {
                $ret .= $body{$i};
            }
            $i++;
        }

        return( "\n\n<!-- HTML Output ahead -->\n" .
                $ret .
                "\n<!-- END of HTML Output --><base href=\"".
                $HTTP_SERVER_VARS["SERVER_NAME"] . substr( $PHP_SELF, 0, strlen( $PHP_SELF ) - 13 ) .
                "\">\n\n" );
   }

   function isNoSep( $char ) {

        switch( $char ) {
            case ' ':
            case "\n":
            case "\t":
            case "\r":
            case '>':
            case '"':
                return( FALSE );
                break;
            default:
                return( TRUE );
        }

   }

   /*
      The following function is usefull to remove extra data that can cause
      html not to display properly. Especialy with MS stuff.
   */

   function stripComments( &$i, $j, &$body ) {

        while( $body{$i}.$body{$i+1}.$body{$i+2}.$body{$i+3} == '<!--' &&
               $i < $j ) {
            $i += 5;
            while( $body{$i-2}.$body{$i-1}.$body{$i} <> '-->' &&
                   $i < $j )
                $i++;
            $i++;
        }

        return;

   }

   /* Gets the style data of a specific style */

   function getStyleData( &$i, $j, &$body ) {

        // We skip spaces
        while( $body{$i} <> '>' && !isNoSep( $body{$i} ) &&
               $i < $j ) {
            $i++;
        }
        // And get the color
        $ret = '';
        while( isNoSep( $body{$i} ) &&
               $i < $j ) {
            $ret .= $body{$i};
            $i++;
        }

        return( $ret );
   }

   /*
   Private function for strip_dangerous_tag. Look for event based coded and "remove" it
   change on with no (onload -> noload)
   */

   function stripEvent( &$i, $j, &$body, $id, $base ) {

        global $message;

        $ret = '';

        while( $body{$i} <> '>' &&
               $i < $j ) {
            $etg = strtolower($body{$i}.$body{$i+1}.$body{$i+2});
            switch( $etg ) {
                case '../':
                        // Retrolinks are not allowed without a base because they mess with SM security
                        if( $base == '' ) {
                                $i += 2;
                        } else {
                                $ret .= '.';
                        }
                        break;
                case 'cid':
                    // Internal link
                    $k = $i-1;
                    if( $body{$i+3} == ':') {
                        $i +=4;
                        $name = '';
                        while( isNoSep( $body{$i} ) &&
                               $i < $j  )
                            $name .= $body{$i++};
                        if( $name <> '' ) {
                            $ret .= "../src/download.php?absolute_dl=true&passed_id=$id&mailbox=" .
                                        urlencode( $message->header->mailbox ) .
                                        "&passed_ent_id=" . find_ent_id( $name, $message );
                            if( $body{$k} == '"' )
                                $ret .= '" ';
                            else
                                $ret .= ' ';
                        }
                        if( $body{$i} == '>' )
                            $i -= 1;
                    }
                    break;
                case ' on':
                case "\non":
                case "\ron":
                case "\ton":
                    $ret .= ' no';
                    $i += 2;
                    break;
                case 'pt:':
                    if( strcasecmp( $body{$i-4}.$body{$i-3}.$body{$i-2}.$body{$i-1}.$body{$i}.$body{$i+1}.$body{$i+2}, 'script:') == 0 ) {
                        $ret .= '_no/';
                    } else {
                        $ret .= $etg;
                    }
                    $i += 2;
                    break;
                default:
                    $ret .= $body{$i};
            }
            $i++;
        }
        return( $ret );
    }


    /* This function trys to locate the entity_id of a specific mime element */

    function find_ent_id( $id, $message ) {

        $ret = '';
        for ($i=0; $ret == '' && $i < count($message->entities); $i++) {

            if( $message->entities[$i]->header->entity_id == '' ) {
                $ret = find_ent_id( $id, $message->entities[$i] );
            } else {
                if( strcasecmp( $message->entities[$i]->header->id, $id ) == 0 )
                    $ret = $message->entities[$i]->header->entity_id;
            }

        }

        return( $ret );

    }
?>