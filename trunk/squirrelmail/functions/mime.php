<?php

/**
 * mime.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains the functions necessary to detect and decode MIME
 * messages.
 *
 * $Id$
 */

require_once('../functions/imap.php');
require_once('../functions/attachment_common.php');

/** Setting up the objects that have the structure for the message **/
class msg_header {
    /** msg_header contains generic variables for values that **/
    /** could be in a header.                                 **/

    var $type0 = '', $type1 = '', $boundary = '', $charset = '',
        $encoding = '', $size = 0, $to = array(), $from = '', $date = '',
        $cc = array(), $bcc = array(), $reply_to = '', $subject = '',
        $id = 0, $mailbox = '', $description = '', $filename = '',
        $entity_id = 0, $message_id = 0, $name = '', $priority = 3, $type = '';
}

class message {
    /** message is the object that contains messages.  It is a recursive
      object in that through the $entities variable, it can contain
      more objects of type message.  See documentation in mime.txt for
      a better description of how this works.
    **/
    var $header = '', $entities = array();

    function addEntity ($msg) {
        $this->entities[] = $msg;
    }
}

/* --------------------------------------------------------------------------------- */
/* MIME DECODING                                                                     */
/* --------------------------------------------------------------------------------- */

/* This function gets the structure of a message and stores it in the "message" class.
 * It will return this object for use with all relevant header information and
 * fully parsed into the standard "message" object format.
 */
function mime_structure ($imap_stream, $header) {

    $ssid = sqimap_session_id();
    $lsid = strlen( $ssid );
    $id = $header->id;
    fputs ($imap_stream, "$ssid FETCH $id BODYSTRUCTURE\r\n");
    //
    // This should use sqimap_read_data instead of reading it itself
    //
    $read = fgets ($imap_stream, 9216);
    $bodystructure = '';
    while ( substr($read, 0, $lsid) <> $ssid &&
         !feof( $imap_stream ) ) {
        $bodystructure .= $read;
        $read = fgets ($imap_stream, 9216);
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

    return( $msg );
}

/* this starts the parsing of a particular structure.  It is called recursively,
 * so it can be passed different structures.  It returns an object of type
 * $message.
 * First, it checks to see if it is a multipart message.  If it is, then it
 * handles that as it sees is necessary.  If it is just a regular entity,
 * then it parses it and adds the necessary header information (by calling out
 * to mime_get_elements()
 */
function mime_parse_structure ($structure, $ent_id) {
  global $mailbox;
  $properties = array();
  $msg = new message();
  if ($structure{0} == '(') {
     $old_ent_id = $ent_id;
     $ent_id = mime_new_element_level($ent_id);
     $start = $end = -1;
     do {
        $start = $end+1;
        $end = mime_match_parenthesis ($start, $structure);

	/* check if we are dealing with a new entity-level */
	$i = strrpos($ent_id,'.');
	if ($i>0) {
	    $ent = substr($ent_id, $i+1);
	} else {
	    $ent = '';
	}
        /* add "forgotten"  parent entities (alternative and relative) */
	if ($ent == '0') {
	    /* new entity levels have information about the type (type1) and 
	    *  the properties. This information is situated at the end of the 
	    *  structure string like for example (example between the brackets) 
	    *  [ "RELATED" ("BOUNDARY" "myboundary" "TYPE" "plain/html") ]
	    */
	    
	    /* get the involved properties for parsing to mime_get_properties */
	    $startprop = strrpos($structure,'(');
	    $properties_str = substr($structure,$startprop);
	    $endprop = mime_match_parenthesis ($startprop, $structure);
	    $propstr = substr($structure, $startprop + 1, ($endprop - $startprop)-1);
	    /* cut off the used properties */
	    if ($startprop) { 
		$structure_end = substr($structure, $endprop+2);
		$structure = trim(substr($structure,0,$startprop));
	    }
	    /* get type1 */
	    $pos = strrpos($structure,' ');
	    if ($structure{$pos+1} =='(') $pos++;
	    
	    $type1 = strtolower(substr($structure, $pos+2, (count($structure)-2)));
	    /* cut off  type1 */
	    if ($pos && $startprop) {
		$structure = trim(substr($structure, 0, $pos));
	    }

	    /* process the found information */
            $properties = mime_get_props($properties, $properties_str);
	    if (count($properties)>0) {
		$msg->header->entity_id = $old_ent_id;
		$msg->header->type0 = 'multipart';
		$msg->header->type1 = $type1;
		for ($i=0; $i < count($properties); $i++) {
    		    $msg->header->{$properties[$i]['name']} = $properties[$i]['value'];
		}
	    }
	    $structure = $structure . ' ' . $structure_end;
	} 
    	$element = substr($structure, $start+1, ($end - $start)-1);
    	$ent_id = mime_increment_id ($ent_id);
    	$newmsg = mime_parse_structure ($element, $ent_id);
	/* set mailbox in case of message/rfc822 entities */
	if (isset($newmsg->header->type0) && isset($newmsg->header->type1)) {
	    if ($newmsg->header->type0 == 'message' && $newmsg->header->type1 == 'rfc822') {
		$newmsg->header->mailbox=$mailbox;
	    }
	}
    	$msg->addEntity ($newmsg);

     } while ($structure{$end+1} == '(');
  } else {
     // parse the elements
    $msg = mime_get_element ($structure, $msg, $ent_id);
  }
  return $msg;
}


/* Increments the element ID.  An element id can look like any of
 * the following:  1, 1.2, 4.3.2.4.1, etc.  This function increments
 * the last number of the element id, changing 1.2 to 1.3.
 */
function mime_increment_id ($id) {

    if (strpos($id, '.')) {
        $first = substr($id, 0, strrpos($id, '.'));
        $last = substr($id, strrpos($id, '.')+1);
        $last++;
        $new = $first . '.' .$last;
    } else {
        $new = $id + 1;
    }

    return $new;
}

/*
 * See comment for mime_increment_id().
 * This adds another level on to the entity_id changing 1.3 to 1.3.0
 * NOTE:  1.3.0 is not a valid element ID.  It MUST be incremented
 *        before it can be used.  I left it this way so as not to have
 *        to make a special case if it is the first entity_id.  It
 *        always increments it, and that works fine.
 */
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
     } else if ($char == '{') {
         /**
          * loop through until we find the matching quote, 
          * and return that as a string
          */
         $pos = 1;
         $len = '';
         while (($char = $structure{$pos}) != '}' 
                && $pos < strlen($structure)) {
             $len .= $char;
             $pos++;
         }
         $structure = substr($structure, strlen($len) + 4);
         $text = substr($structure, 0, $len);
         $structure = substr($structure, $len + 1);
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
  for ($i=0; $i < count($properties); $i++) {
     $msg->header->{$properties[$i]['name']} = $properties[$i]['value'];
  }

  return $msg;
}

/*
 * I did most of the MIME stuff yesterday (June 20, 2000), but I couldn't
 * figure out how to do this part, so I decided to go to bed.  I woke up
 * in the morning and had a flash of insight.  I went to the white-board
 * and scribbled it out, then spent a bit programming it, and this is the
 * result.  Nothing complicated, but I think my brain was fried yesterday.
 * Funny how that happens some times.
 *
 * This gets properties in a nested parenthesisized list.  For example,
 * this would get passed something like:  ("attachment" ("filename" "luke.tar.gz"))
 * This returns an array called $props with all paired up properties.
 * It ignores the "attachment" for now, maybe that should change later
 * down the road.  In this case, what is returned is:
 *    $props[0]["name"] = "filename";
 *    $props[0]["value"] = "luke.tar.gz";
 */
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
           $structure = trim(substr($structure, strlen($value) + 2));
           $k = count($props);
           $props[$k]['name'] = strtolower($tmp);
           $props[$k]['value'] = $value;
	   if ($structure != '') {
		mime_get_props($props, $structure);
	   } else {
	     return $props;
	   }     	
        } else if ($char == '(') {
           $end = mime_match_parenthesis (0, $structure);
           $sub = substr($structure, 1, $end-1);
    	   if (! isset($props))
              $props = array();
              $props = mime_get_props($props, $sub);
              $structure = substr($structure, strlen($sub) + 2);
	   return $props;      
        }
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

/*
 *  Matches parenthesis.  It will return the position of the matching
 *  parenthesis in $structure.  For instance, if $structure was:
 *     ("text" "plain" ("val1name", "1") nil ... )
 *     x                                         x
 *  then this would return 42 to match up those two.
 */
function mime_match_parenthesis ($pos, $structure) {

    $j = strlen( $structure );
    
    // ignore all extra characters
    // If inside of a string, skip string -- Boundary IDs and other
    // things can have ) in them.
    if ( $structure{$pos} != '(' ) {
        return( $j );
    }
    
    while ( $pos < $j ) {
        $pos++;
        if ($structure{$pos} == ')') {
            return $pos;
        } elseif ($structure{$pos} == '"') {
            $pos++;
            while ( $structure{$pos} != '"' &&
                    $pos < $j ) {
               if (substr($structure, $pos, 2) == '\\"') {
                  $pos++;
               } elseif (substr($structure, $pos, 2) == '\\\\') {
                  $pos++;
               }
               $pos++;
            }
        } elseif ( $structure{$pos} == '(' ) {
            $pos = mime_match_parenthesis ($pos, $structure);
        }
    }
    echo _("Error decoding mime structure.  Report this as a bug!") . '<br>';
    return( $pos );
}

function mime_fetch_body($imap_stream, $id, $ent_id) {

    /*
     * do a bit of error correction.  If we couldn't find the entity id, just guess
     * that it is the first one.  That is usually the case anyway.
     */
    if (!$ent_id) {
        $ent_id = 1;
    }
    $cmd = "FETCH $id BODY[$ent_id]";

    $data = sqimap_run_command ($imap_stream, $cmd, true, $response, $message);
    do {
        $topline = trim(array_shift( $data ));
    } while( $topline && $topline[0] == '*' && !preg_match( '/\* [0-9]+ FETCH.*/i', $topline )) ;
    $wholemessage = implode('', $data);
    if (ereg('\\{([^\\}]*)\\}', $topline, $regs)) {

        $ret = substr( $wholemessage, 0, $regs[1] );
        /*
            There is some information in the content info header that could be important
            in order to parse html messages. Let's get them here.
        */
        if ( $ret{0} == '<' ) {
            $data = sqimap_run_command ($imap_stream, "FETCH $id BODY[$ent_id.MIME]", true, $response, $message);
            /* BASE within HTML documents is illegal (see w3 spec)
*            $base = '';
*            $k = 10;
*            foreach( $data as $d ) {
*                if ( substr( $d, 0, 13 ) == 'Content-Base:' ) {
*                    $j = strlen( $d );
*                    $i = 13;
*                    $base = '';
*                    while ( $i < $j &&
*                           ( !isNoSep( $d{$i} ) || $d{$i} == '"' )  )
*                        $i++;
*                    while ( $i < $j ) {
*                        if ( isNoSep( $d{$i} ) )
*                            $base .= $d{$i};
*                        $i++;
*                    }
*                    $k = 0;
*                } elseif ( $k == 1 && !isnosep( $d{0} ) ) {
*                    $base .= substr( $d, 1 );
*                }
*                $k++;
*            }
*            if ( $base <> '' ) {
*                $ret = "<base href=\"$base\">" . $ret;
*            }
*           */
        }
    } else if (ereg('"([^"]*)"', $topline, $regs)) {
        $ret = $regs[1];
    } else {
        global $where, $what, $mailbox, $passed_id, $startMessage;
        $par = 'mailbox=' . urlencode($mailbox) . "&amp;passed_id=$passed_id";
        if (isset($where) && isset($what)) {
            $par .= '&amp;where='. urlencode($where) . "&amp;what=" . urlencode($what);
        } else {
            $par .= "&amp;startMessage=$startMessage&amp;show_more=0";
        }
        $par .= '&amp;response=' . urlencode($response) .
                '&amp;message=' . urlencode($message).
                '&amp;topline=' . urlencode($topline);

        echo   '<tt><br>' .
               '<table width="80%"><tr>' .
               '<tr><td colspan=2>' .
               _("Body retrieval error. The reason for this is most probably that the message is malformed. Please help us making future versions better by submitting this message to the developers knowledgebase!") .
               " <A HREF=\"../src/retrievalerror.php?$par\"><br>" .
               _("Submit message") . '</A><BR>&nbsp;' .
               '</td></tr>' .
               '<td><b>' . _("Command:") . "</td><td>$cmd</td></tr>" .
               '<td><b>' . _("Response:") . "</td><td>$response</td></tr>" .
               '<td><b>' . _("Message:") . "</td><td>$message</td></tr>" .
               '<td><b>' . _("FETCH line:") . "</td><td>$topline</td></tr>" .
               "</table><BR></tt></font><hr>";

        $data = sqimap_run_command ($imap_stream, "FETCH $passed_id BODY[]", true, $response, $message);
        array_shift($data);
        $wholemessage = implode('', $data);

        $ret = $wholemessage;
    }
    return( $ret );
}

function mime_print_body_lines ($imap_stream, $id, $ent_id, $encoding) {
    // do a bit of error correction.  If we couldn't find the entity id, just guess
    // that it is the first one.  That is usually the case anyway.
    if (!$ent_id) {
        $ent_id = 1;
    }
    $sid = sqimap_session_id();
    // Don't kill the connection if the browser is over a dialup
    // and it would take over 30 seconds to download it.

    // don´t call set_time_limit in safe mode.
    if (!ini_get("safe_mode")) {
        set_time_limit(0);
    }

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



/* This is the first function called.  It decides if this is a multipart
   message or if it should be handled as a single entity
 */
function decodeMime ($imap_stream, &$header) {
    global $username, $key, $imapServerAddress, $imapPort;
    return mime_structure ($imap_stream, $header);
}

// This is here for debugging purposese.  It will print out a list
// of all the entity IDs that are in the $message object.

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


/* returns a $message object for a particular entity id */
function getEntity ($message, $ent_id) {
    if ($message) {
        if ($message->header->entity_id == $ent_id && strlen($ent_id) == strlen($message->header->entity_id))
	{
            return $message;
        } else {
            for ($i = 0; isset($message->entities[$i]); $i++) {
                $msg = getEntity ($message->entities[$i], $ent_id);
                if ($msg) {
                    return $msg;
		}
            }
        }
    }
}

/*
 * figures out what entity to display and returns the $message object
 * for that entity.
 */
function findDisplayEntity ($msg, $textOnly = true, $entity = array() )   {
    global $show_html_default;
    
    $found = false;    
    if ($msg) {
        $type = $msg->header->type0.'/'.$msg->header->type1;
        if ( $type == 'multipart/alternative') {
	    $msg = findAlternativeEntity($msg, $textOnly);
	    if (count($msg->entities) == 0) {
        	$entity[] = $msg->header->entity_id;
	    } else {
		$found = true;
	         $entity =findDisplayEntity($msg,$textOnly, $entity);
	    }
	} else 	if ( $type == 'multipart/related') {
            $msgs = findRelatedEntity($msg);
	    for ($i = 0; $i < count($msgs); $i++) {
	        $msg = $msgs[$i];
		if (count($msg->entities) == 0) {
        	    $entity[] = $msg->header->entity_id;
		} else {
		    $found = true;
	    	     $entity =findDisplayEntity($msg,$textOnly, $entity);
		}
	    }
	} else if ( count($entity) == 0 &&
             $msg->header->type0 == 'text' &&
             ( $msg->header->type1 == 'plain' ||
               $msg->header->type1 == 'html' ) &&
             isset($msg->header->entity_id) ) {
	     if (count($msg->entities) == 0) {
        	$entity[] = $msg->header->entity_id;
	     } 
        } 
    	$i = 0;
    	while ( isset($msg->entities[$i]) && count($entity) == 0 && !$found )  {
    	    $entity = findDisplayEntity($msg->entities[$i], $textOnly, $entity);
    	    $i++;
    	}
    }
    if ( !isset($entity[0]) ) {
        $entity[]="";
    }
    return( $entity );
}

/* Shows the HTML version */
function findDisplayEntityHTML ($message) {

    if ( $message->header->type0 == 'text' &&
         $message->header->type1 == 'html' &&
         isset($message->header->entity_id)) {
        return $message->header->entity_id;
    }
    for ($i = 0; isset($message->entities[$i]); $i ++) {
	if ( $message->header->type0 == 'message' &&
    	    $message->header->type1 == 'rfc822' &&
            isset($message->header->entity_id)) {
    	    return 0;
	}
	
        $entity = findDisplayEntityHTML($message->entities[$i]);
        if ($entity != 0) {
            return $entity;
        }
    }

    return 0;
}

function findAlternativeEntity ($message, $textOnly) {
    global $show_html_default;
    /* if we are dealing with alternative parts then we choose the best 
     * viewable message supported by SM.
     */
    if ($show_html_default && !$textOnly) {     
	$alt_order = array ('text/plain','text/html');
    } else {
	$alt_order = array ('text/plain');
    }
    $best_view = 0;
    $ent_id = 0;
    $k = 0; 
    for ($i = 0; $i < count($message->entities); $i ++) {
        $type = $message->entities[$i]->header->type0.'/'.$message->entities[$i]->header->type1;
	if ($type == 'multipart/related') {
	   $type = $message->entities[$i]->header->type;
	}
	for ($j = $k; $j < count($alt_order); $j++) {
	    if ($alt_order[$j] == $type && $j > $best_view) {
		$best_view = $j;
		$ent_id = $i;
		$k = $j;
	    }
	}
    }
    return $message->entities[$ent_id];
}

function findRelatedEntity ($message) {
    $msgs = array(); 
    for ($i = 0; $i < count($message->entities); $i ++) {
        $type = $message->entities[$i]->header->type0.'/'.$message->entities[$i]->header->type1;
        if ($message->header->type == $type) {
	    $msgs[] = $message->entities[$i];
	}
    }
    return $msgs;
}    

/*
 * translateText
 * Extracted from strings.php 23/03/2002
 */

function translateText(&$body, $wrap_at, $charset) {
    global $where, $what; /* from searching */
    global $color; /* color theme */

    require_once('../functions/url_parser.php');

    $body_ary = explode("\n", $body);
    $PriorQuotes = 0;
    for ($i=0; $i < count($body_ary); $i++) {
        $line = $body_ary[$i];
        if (strlen($line) - 2 >= $wrap_at) {
            sqWordWrap($line, $wrap_at);
        }
        $line = charset_decode($charset, $line);
        $line = str_replace("\t", '        ', $line);

        parseUrl ($line);

        $Quotes = 0;
        $pos = 0;
        $j = strlen( $line );

        while ( $pos < $j ) {
            if ($line[$pos] == ' ') {
                $pos ++;
            } else if (strpos($line, '&gt;', $pos) === $pos) {
                $pos += 4;
                $Quotes ++;
            } else {
                break;
            }
        }
        
        if ($Quotes > 1) {
            if (! isset($color[14])) {
                $color[14] = '#FF0000';
            }
            $line = '<FONT COLOR="' . $color[14] . '">' . $line . '</FONT>';
        } elseif ($Quotes) {
            if (! isset($color[13])) {
                $color[13] = '#800000';
            }
            $line = '<FONT COLOR="' . $color[13] . '">' . $line . '</FONT>';
        }
        
        $body_ary[$i] = $line;
    }
    $body = '<pre>' . implode("\n", $body_ary) . '</pre>';
}

/* debugfunction for looping through entities and displaying correct entities */
function listMyEntities ($message) {

if ($message) {
    if ($message->header->entity_id) {
	echo "<tt>" . $message->header->entity_id . ' : ' . $message->header->type0 . '/' . $message->header->type1 . '<br>';
    } 
    if (!($message->header->type0 == 'message' &&  $message->header->type1 == 'rfc822')) {
	if (isset($message->header->boundary) ) {
	    $ent_id = $message->header->entity_id;
	    $var = $message->header->boundary;
	    if ($var !='')
	    echo "<b>$ent_id boundary = $var</b><br>";
	} 
	if (isset($message->header->type) ) {
	    $var = $message->header->type;
	    if ($var !='')
	    echo "<b>$ent_id type = $var</b><br>";
	} 
	for ($i = 0; $message->entities[$i]; $i++) {
	    $msg = listMyEntities($message->entities[$i]);
	}

	if ($msg )  return $msg;
    }
}

}



/* This returns a parsed string called $body. That string can then
be displayed as the actual message in the HTML. It contains
everything needed, including HTML Tags, Attachments at the
bottom, etc.
*/
function formatBody($imap_stream, $message, $color, $wrap_at, $ent_num) {
    // this if statement checks for the entity to show as the
    // primary message. To add more of them, just put them in the
    // order that is their priority.
    global $startMessage, $username, $key, $imapServerAddress, $imapPort,
           $show_html_default, $has_unsafe_images, $view_unsafe_images, $sort;

    $has_unsafe_images = 0;

    $id = $message->header->id;

    $urlmailbox = urlencode($message->header->mailbox);

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
            if ( $show_html_default <> 1 ) {
                $body = strip_tags( $body );
                translateText($body, $wrap_at, $body_message->header->charset);
            } else {
                $body = magicHTML( $body, $id, $message );
            }
        } else {
            translateText($body, $wrap_at, $body_message->header->charset);
        }
        $body .= "<CENTER><SMALL><A HREF=\"../src/download.php?absolute_dl=true&amp;passed_id=$id&amp;passed_ent_id=$ent_num&amp;mailbox=$urlmailbox&amp;showHeaders=1\">". _("Download this as a file") ."</A></SMALL></CENTER><BR>";
        if ($has_unsafe_images) {
            if ($view_unsafe_images) {
                $body .= "<CENTER><SMALL><A HREF=\"read_body.php?passed_id=$id&amp;mailbox=$urlmailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">". _("Hide Unsafe Images") ."</A></SMALL></CENTER><BR>\n";
            } else {
                $body .= "<CENTER><SMALL><A HREF=\"read_body.php?passed_id=$id&amp;mailbox=$urlmailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0&amp;view_unsafe_images=1\">". _("View Unsafe Images") ."</A></SMALL></CENTER><BR>\n";
            }
        }

        /** Display the ATTACHMENTS: message if there's more than one part **/
        if (isset($message->entities[1])) {
	    /* Header-type alternative means we choose the best one to display 
	       so don't show the alternatives as attachment. Header-type related
	       means that the attachments are already part of the related message.
	    */   
	    if ($message->header->type1 !='related' && $message->header->type1 !='alternative') {
        	$body .= formatAttachments ($message, $ent_num, $message->header->mailbox, $id);
	    }
        }
    } else {
        $body = formatAttachments ($message, -1, $message->header->mailbox, $id);
    }
    return ($body);
}

/*
 * A recursive function that returns a list of attachments with links
 * to where to download these attachments
 */
function formatAttachments($message, $ent_id, $mailbox, $id) {
    global $where, $what;
    global $startMessage, $color;
    static $ShownHTML = 0;

    $body = '';
    if ($ShownHTML == 0) {

        $ShownHTML = 1;
        $body .= "<TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2 BORDER=0 BGCOLOR=\"$color[0]\"><TR>\n" .
                "<TH ALIGN=\"left\" BGCOLOR=\"$color[9]\"><B>\n" .
                _("Attachments") . ':' .
                "</B></TH></TR><TR><TD>\n" .
                "<TABLE CELLSPACING=0 CELLPADDING=1 BORDER=0>\n" .
                formatAttachments($message, $ent_id, $mailbox, $id) .
                "</TABLE></TD></TR></TABLE>";

    } else if ($message) {
	$header = $message->header;
        $type0 = strtolower($header->type0);
        $type1 = strtolower($header->type1);
	$name = '';
	if (isset($header->name)) {
    	    $name = decodeHeader($header->name);
	}
	if ($type0 =='message' && $type1 == 'rfc822') {
	 
            $filename = decodeHeader($message->header->filename);
            if (trim($filename) == '') {
                if (trim($name) == '') {
                    $display_filename = 'untitled-[' . $message->header->entity_id . ']' ;
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
                "../src/download.php?startMessage=$startMessage&amp;passed_id=$id&amp;mailbox=$urlMailbox&amp;passed_ent_id=$ent";
            if ($where && $what) {
                $DefaultLink .= '&amp;where=' . urlencode($where) . '&amp;what=' . urlencode($what);
            }
            $Links['download link']['text'] = _("download");
            $Links['download link']['href'] =
                "../src/download.php?absolute_dl=true&amp;passed_id=$id&amp;mailbox=$urlMailbox&amp;passed_ent_id=$ent";
            $ImageURL = '';

            /* this executes the attachment hook with a specific MIME-type.
                * if that doens't have results, it tries if there's a rule
                * for a more generic type. */
            $HookResults = do_hook("attachment $type0/$type1", $Links,
                $startMessage, $id, $urlMailbox, $ent, $DefaultLink, $display_filename, $where, $what);
            if(count($HookResults[1]) <= 1) {
                $HookResults = do_hook("attachment $type0/*", $Links,
                $startMessage, $id, $urlMailbox, $ent, $DefaultLink,
                $display_filename, $where, $what);
            }

            $Links = $HookResults[1];
            $DefaultLink = $HookResults[6];

            $body .= '<TR><TD>&nbsp;&nbsp;</TD><TD>' .
                        "<A HREF=\"$DefaultLink\">$display_filename</A>&nbsp;</TD>" .
                        '<TD><SMALL><b>' . show_readable_size($message->header->size) .
                        '</b>&nbsp;&nbsp;</small></TD>' .
                        "<TD><SMALL>[ $type0/$type1 ]&nbsp;</SMALL></TD>" .
                        '<TD><SMALL>';
            if ($message->header->description) {
                $body .= '<b>' . htmlspecialchars(_($message->header->description)) . '</b>';
            }
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
            
	    return( $body );	
    	
        } elseif (!$message->entities) {

            $type0 = strtolower($message->header->type0);
            $type1 = strtolower($message->header->type1);
            $name = decodeHeader($message->header->name);

            if ($message->header->entity_id != $ent_id) {
            $filename = decodeHeader($message->header->filename);
            if (trim($filename) == '') {
                if (trim($name) == '') {
                    if ( trim( $message->header->id ) == '' )
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
                "../src/download.php?startMessage=$startMessage&amp;passed_id=$id&amp;mailbox=$urlMailbox&amp;passed_ent_id=$ent";
            if ($where && $what) {
	       $DefaultLink = '&amp;where='. urlencode($where).'&amp;what='.urlencode($what);
            }
            $Links['download link']['text'] = _("download");
            $Links['download link']['href'] =
                "../src/download.php?absolute_dl=true&amp;passed_id=$id&amp;mailbox=$urlMailbox&amp;passed_ent_id=$ent";
            $ImageURL = '';

            /* this executes the attachment hook with a specific MIME-type.
                * if that doens't have results, it tries if there's a rule
                * for a more generic type. */
            $HookResults = do_hook("attachment $type0/$type1", $Links,
                $startMessage, $id, $urlMailbox, $ent, $DefaultLink,
                $display_filename, $where, $what);
            if(count($HookResults[1]) <= 1) {
                $HookResults = do_hook("attachment $type0/*", $Links,
                $startMessage, $id, $urlMailbox, $ent, $DefaultLink,
                $display_filename, $where, $what);
            }

            $Links = $HookResults[1];
            $DefaultLink = $HookResults[6];

            $body .= '<TR><TD>&nbsp;&nbsp;</TD><TD>' .
                        "<A HREF=\"$DefaultLink\">$display_filename</A>&nbsp;</TD>" .
                        '<TD><SMALL><b>' . show_readable_size($message->header->size) .
                        '</b>&nbsp;&nbsp;</small></TD>' .
                        "<TD><SMALL>[ $type0/$type1 ]&nbsp;</SMALL></TD>" .
                        '<TD><SMALL>';
            if ($message->header->description) {
                $body .= '<b>' . htmlspecialchars(_($message->header->description)) . '</b>';
            }
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
                $body .= formatAttachments($message->entities[$i], $ent_id, $mailbox, $id);
            }
        }
    }
    return( $body );
}


/** this function decodes the body depending on the encoding type. **/
function decodeBody($body, $encoding) {
  $body = str_replace("\r\n", "\n", $body);
  $encoding = strtolower($encoding);

  global $show_html_default;

  if ($encoding == 'quoted-printable' ||
      $encoding == 'quoted_printable') {
     $body = quoted_printable_decode($body);


     while (ereg("=\n", $body))
        $body = ereg_replace ("=\n", "", $body);

  } else if ($encoding == 'base64') {
     $body = base64_decode($body);
  }

  // All other encodings are returned raw.
  return $body;
}

/*
 * This functions decode strings that is encoded according to
 * RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
 * Patched by Christian Schmidt <christian@ostenfeld.dk>  23/03/2002
 */
function decodeHeader ($string, $utfencode=true) {
    if (is_array($string)) {
        $string = implode("\n", $string);
    }
    $i = 0;
    while (preg_match('/^(.{' . $i . '})(.*)=\?([^?]*)\?(Q|B)\?([^?]*)\?=/Ui', 
                      $string, $res)) {
        $prefix = $res[1];
        // Ignore white-space between consecutive encoded-words
        if (strspn($res[2], " \t") != strlen($res[2])) {
            $prefix .= $res[2];
        }

        if (ucfirst($res[4]) == 'B') {
            $replace = base64_decode($res[5]);
        } else {
            $replace = str_replace('_', ' ', $res[5]);
            $replace = preg_replace('/=([0-9a-f]{2})/ie', 'chr(hexdec("\1"))', 
                                    $replace);
            /* Only encode into entities by default. Some places
               don't need the encoding, like the compose form. */
            if ($utfencode) {
                $replace = charset_decode($res[3], $replace);
            }
        }
        $string = $prefix . $replace . substr($string, strlen($res[0]));
        $i = strlen($prefix) + strlen($replace);
    }
    return( $string );
}

/*
 * Encode a string according to RFC 1522 for use in headers if it
 * contains 8-bit characters or anything that looks like it should
 * be encoded.
 */
function encodeHeader ($string) {
    global $default_charset;

    // Encode only if the string contains 8-bit characters or =?
    $j = strlen( $string  );
    $l = strstr($string, '=?');         // Must be encoded ?
    $ret = '';
    for( $i=0; $i < $j; ++$i) {
        switch( $string{$i} ) {
           case '=':
          $ret .= '=3D';
          break;
        case '?':
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
          if ( $k > 126 ) {
             $ret .= sprintf("=%02X", $k);
             $l = TRUE;
          } else
             $ret .= $string{$i};
        }
    }

    if ( $l ) {
        $string = "=?$default_charset?Q?$ret?=";
    }

    return( $string );
}

/* This function trys to locate the entity_id of a specific mime element */

function find_ent_id( $id, $message ) {
    $ret = '';
    for ($i=0; $ret == '' && $i < count($message->entities); $i++) {
	if (( $message->entities[$i]->header->type1 == 'alternative') ||	 
	    ( $message->entities[$i]->header->type1 == 'related') ||	 
	    ( $message->entities[$i]->header->type1 == 'mixed')) { 	 
    	    $ret = find_ent_id( $id, $message->entities[$i] );
        } else {
            if ( strcasecmp( $message->entities[$i]->header->id, $id ) == 0 )
                $ret = $message->entities[$i]->header->entity_id;
        }

    }
    return( $ret );
}

/**
 ** HTMLFILTER ROUTINES
 */

/**
 * This function returns the final tag out of the tag name, an array
 * of attributes, and the type of the tag. This function is called by 
 * sq_sanitize internally.
 *
 * @param  $tagname  the name of the tag.
 * @param  $attary   the array of attributes and their values
 * @param  $tagtype  The type of the tag (see in comments).
 * @return           a string with the final tag representation.
 */
function sq_tagprint($tagname, $attary, $tagtype){
    $me = "sq_tagprint";
    if ($tagtype == 2){
        $fulltag = '</' . $tagname . '>';
    } else {
        $fulltag = '<' . $tagname;
        if (is_array($attary) && sizeof($attary)){
            $atts = Array();
            while (list($attname, $attvalue) = each($attary)){
                array_push($atts, "$attname=$attvalue");
            }
            $fulltag .= ' ' . join(" ", $atts);
        }
        if ($tagtype == 3){
            $fulltag .= " /";
        }
        $fulltag .= ">";
    }
    return $fulltag;
}

/**
 * A small helper function to use with array_walk. Modifies a by-ref
 * value and makes it lowercase.
 *
 * @param  $val a value passed by-ref.
 * @return      void since it modifies a by-ref value.
 */
function sq_casenormalize(&$val){
    $val = strtolower($val);
}

/**
 * This function skips any whitespace from the current position within
 * a string and to the next non-whitespace value.
 * 
 * @param  $body   the string
 * @param  $offset the offset within the string where we should start
 *                 looking for the next non-whitespace character.
 * @return         the location within the $body where the next
 *                 non-whitespace char is located.
 */
function sq_skipspace($body, $offset){
    $me = "sq_skipspace";
    preg_match("/^(\s*)/s", substr($body, $offset), $matches);
    if (sizeof($matches{1})){
        $count = strlen($matches{1});
        $offset += $count;
    }
    return $offset;
}

/**
 * This function looks for the next character within a string.  It's
 * really just a glorified "strpos", except it catches if failures
 * nicely.
 *
 * @param  $body   The string to look for needle in.
 * @param  $offset Start looking from this position.
 * @param  $needle The character/string to look for.
 * @return         location of the next occurance of the needle, or
 *                 strlen($body) if needle wasn't found.
 */
function sq_findnxstr($body, $offset, $needle){
    $me = "sq_findnxstr";
    $pos = strpos($body, $needle, $offset);
    if ($pos === FALSE){
        $pos = strlen($body);
    }
    return $pos;
}

/**
 * This function takes a PCRE-style regexp and tries to match it
 * within the string.
 *
 * @param  $body   The string to look for needle in.
 * @param  $offset Start looking from here.
 * @param  $reg    A PCRE-style regex to match.
 * @return         Returns a false if no matches found, or an array
 *                 with the following members:
 *                 - integer with the location of the match within $body
 *                 - string with whatever content between offset and the match
 *                 - string with whatever it is we matched
 */
function sq_findnxreg($body, $offset, $reg){
    $me = "sq_findnxreg";
    $matches = Array();
    $retarr = Array();
    preg_match("%^(.*?)($reg)%s", substr($body, $offset), $matches);
    if (!$matches{0}){
        $retarr = false;
    } else {
        $retarr{0} = $offset + strlen($matches{1});
        $retarr{1} = $matches{1};
        $retarr{2} = $matches{2};
    }
    return $retarr;
}

/**
 * This function looks for the next tag.
 *
 * @param  $body   String where to look for the next tag.
 * @param  $offset Start looking from here.
 * @return         false if no more tags exist in the body, or
 *                 an array with the following members:
 *                 - string with the name of the tag
 *                 - array with attributes and their values
 *                 - integer with tag type (1, 2, or 3)
 *                 - integer where the tag starts (starting "<")
 *                 - integer where the tag ends (ending ">")
 *                 first three members will be false, if the tag is invalid.
 */
function sq_getnxtag($body, $offset){
    $me = "sq_getnxtag";
    if ($offset > strlen($body)){
        return false;
    }
    $lt = sq_findnxstr($body, $offset, "<");
    if ($lt == strlen($body)){
        return false;
    }
    /**
     * We are here:
     * blah blah <tag attribute="value">
     * \---------^
     */
    $pos = sq_skipspace($body, $lt+1);
    if ($pos >= strlen($body)){
        return Array(false, false, false, $lt, strlen($body));
    }
    /**
     * There are 3 kinds of tags:
     * 1. Opening tag, e.g.:
     *    <a href="blah">
     * 2. Closing tag, e.g.:
     *    </a>
     * 3. XHTML-style content-less tag, e.g.:
     *    <img src="blah"/>
     */
    $tagtype = false;
    switch (substr($body, $pos, 1)){
    case "/":
        $tagtype = 2;
        $pos++;
        break;
    case "!":
        /**
         * A comment or an SGML declaration.
         */
        if (substr($body, $pos+1, 2) == "--"){
            $gt = strpos($body, "-->", $pos);
            if ($gt === false){
                $gt = strlen($body);
            } else {
	        $gt += 2;
	    }
            return Array(false, false, false, $lt, $gt);
        } else {
            $gt = sq_findnxstr($body, $pos, ">");
            return Array(false, false, false, $lt, $gt);
        }
        break;
    default:
        /**
         * Assume tagtype 1 for now. If it's type 3, we'll switch values
         * later.
         */
        $tagtype = 1;
        break;
    }

    $tag_start = $pos;
    $tagname = '';
    /**
     * Look for next [\W-_], which will indicate the end of the tag name.
     */
    $regary = sq_findnxreg($body, $pos, "[^\w\-_]");
    if ($regary == false){
        return Array(false, false, false, $lt, strlen($body));
    }
    list($pos, $tagname, $match) = $regary;
    $tagname = strtolower($tagname);

    /**
     * $match can be either of these:
     * '>'  indicating the end of the tag entirely.
     * '\s' indicating the end of the tag name.
     * '/'  indicating that this is type-3 xhtml tag.
     * 
     * Whatever else we find there indicates an invalid tag.
     */
    switch ($match){
    case "/":
        /**
         * This is an xhtml-style tag with a closing / at the
         * end, like so: <img src="blah"/>. Check if it's followed
         * by the closing bracket. If not, then this tag is invalid
         */
        if (substr($body, $pos, 2) == "/>"){
            $pos++;
            $tagtype = 3;
        } else {
            $gt = sq_findnxstr($body, $pos, ">");
            $retary = Array(false, false, false, $lt, $gt);
            return $retary;
        }
    case ">":
        return Array($tagname, false, $tagtype, $lt, $pos);
        break;
    default:
        /**
         * Check if it's whitespace
         */
        if (preg_match("/\s/", $match)){
        } else {
            /**
             * This is an invalid tag! Look for the next closing ">".
             */
            $gt = sq_findnxstr($body, $offset, ">");
            return Array(false, false, false, $lt, $gt);
        }
    }
    
    /**
     * At this point we're here:
     * <tagname  attribute='blah'>
     * \-------^
     *
     * At this point we loop in order to find all attributes.
     */
    $attname = '';
    $atttype = false;
    $attary = Array();

    while ($pos <= strlen($body)){
        $pos = sq_skipspace($body, $pos);
        if ($pos == strlen($body)){
            /**
             * Non-closed tag.
             */
            return Array(false, false, false, $lt, $pos);
        }
        /**
         * See if we arrived at a ">" or "/>", which means that we reached
         * the end of the tag.
         */
        $matches = Array();
        if (preg_match("%^(\s*)(>|/>)%s", substr($body, $pos), $matches)) {
            /**
             * Yep. So we did.
             */
            $pos += strlen($matches{1});
            if ($matches{2} == "/>"){
                $tagtype = 3;
                $pos++;
            }
            return Array($tagname, $attary, $tagtype, $lt, $pos);
        }

        /**
         * There are several types of attributes, with optional
         * [:space:] between members.
         * Type 1:
         *   attrname[:space:]=[:space:]'CDATA'
         * Type 2:
         *   attrname[:space:]=[:space:]"CDATA"
         * Type 3:
         *   attr[:space:]=[:space:]CDATA
         * Type 4:
         *   attrname
         *
         * We leave types 1 and 2 the same, type 3 we check for
         * '"' and convert to "&quot" if needed, then wrap in
         * double quotes. Type 4 we convert into:
         * attrname="yes".
         */
        $regary = sq_findnxreg($body, $pos, "[^\w\-_]");
        if ($regary == false){
            /**
             * Looks like body ended before the end of tag.
             */
            return Array(false, false, false, $lt, strlen($body));
        }
        list($pos, $attname, $match) = $regary;
        $attname = strtolower($attname);
        /**
         * We arrived at the end of attribute name. Several things possible
         * here:
         * '>'  means the end of the tag and this is attribute type 4
         * '/'  if followed by '>' means the same thing as above
         * '\s' means a lot of things -- look what it's followed by.
         *      anything else means the attribute is invalid.
         */
        switch($match){
        case "/":
            /**
             * This is an xhtml-style tag with a closing / at the
             * end, like so: <img src="blah"/>. Check if it's followed
             * by the closing bracket. If not, then this tag is invalid
             */
            if (substr($body, $pos, 2) == "/>"){
                $pos++;
                $tagtype = 3;
            } else {
                $gt = sq_findnxstr($body, $pos, ">");
                $retary = Array(false, false, false, $lt, $gt);
                return $retary;
            }
        case ">":
            $attary{$attname} = '"yes"';
            return Array($tagname, $attary, $tagtype, $lt, $pos);
            break;
        default:
            /**
             * Skip whitespace and see what we arrive at.
             */
            $pos = sq_skipspace($body, $pos);
            $char = substr($body, $pos, 1);
            /**
             * Two things are valid here:
             * '=' means this is attribute type 1 2 or 3.
             * \w means this was attribute type 4.
             * anything else we ignore and re-loop. End of tag and
             * invalid stuff will be caught by our checks at the beginning
             * of the loop.
             */
            if ($char == "="){
                $pos++;
                $pos = sq_skipspace($body, $pos);
                /**
                 * Here are 3 possibilities:
                 * "'"  attribute type 1
                 * '"'  attribute type 2
                 * everything else is the content of tag type 3
                 */
                $quot = substr($body, $pos, 1);
                if ($quot == "'"){
                    $regary = sq_findnxreg($body, $pos+1, "\'");
                    if ($regary == false){
                        return Array(false, false, false, $lt, strlen($body));
                    }
                    list($pos, $attval, $match) = $regary;
                    $pos++;
                    $attary{$attname} = "'" . $attval . "'";
                } else if ($quot == '"'){
                    $regary = sq_findnxreg($body, $pos+1, '\"');
                    if ($regary == false){
                        return Array(false, false, false, $lt, strlen($body));
                    }
                    list($pos, $attval, $match) = $regary;
                    $pos++;
                    $attary{$attname} = '"' . $attval . '"';
                } else {
                    /**
                     * These are hateful. Look for \s, or >.
                     */
                    $regary = sq_findnxreg($body, $pos, "[\s>]");
                    if ($regary == false){
                        return Array(false, false, false, $lt, strlen($body));
                    }
                    list($pos, $attval, $match) = $regary;
                    /**
                     * If it's ">" it will be caught at the top.
                     */
                    $attval = preg_replace("/\"/s", "&quot;", $attval);
                    $attary{$attname} = '"' . $attval . '"';
                }
            } else if (preg_match("|[\w/>]|", $char)) {
                /**
                 * That was attribute type 4.
                 */
                $attary{$attname} = '"yes"';
            } else {
                /**
                 * An illegal character. Find next '>' and return.
                 */
                $gt = sq_findnxstr($body, $pos, ">");
                return Array(false, false, false, $lt, $gt);
            }
        }
    }
    /**
     * The fact that we got here indicates that the tag end was never
     * found. Return invalid tag indication so it gets stripped.
     */
    return Array(false, false, false, $lt, strlen($body));
}

/**
 * This function checks attribute values for entity-encoded values
 * and returns them translated into 8-bit strings so we can run
 * checks on them.
 *
 * @param  $attvalue A string to run entity check against.
 * @return           Translated value.
 */
function sq_deent($attvalue){
    $me="sq_deent";
    /**
     * See if we have to run the checks first. All entities must start
     * with "&".
     */
    if (strpos($attvalue, "&") === false){
        return $attvalue;
    }
    /**
     * Check named entities first.
     */
    $trans = get_html_translation_table(HTML_ENTITIES);
    /**
     * Leave &quot; in, as it can mess us up.
     */
    $trans = array_flip($trans);
    unset($trans{"&quot;"});
    while (list($ent, $val) = each($trans)){
        $attvalue = preg_replace("/$ent*(\W)/si", "$val\\1", $attvalue);
    }
    /**
     * Now translate numbered entities from 1 to 255 if needed.
     */
    if (strpos($attvalue, "#") !== false){
        $omit = Array(34, 39);
        for ($asc=1; $asc<256; $asc++){
            if (!in_array($asc, $omit)){
                $chr = chr($asc);
                $attvalue = preg_replace("/\&#0*$asc;*(\D)/si", "$chr\\1", 
                                         $attvalue);
                $attvalue = preg_replace("/\&#x0*".dechex($asc).";*(\W)/si",
                                         "$chr\\1", $attvalue);
            }
        }
    }
    return $attvalue;
}

/**
 * This function runs various checks against the attributes.
 *
 * @param  $tagname         String with the name of the tag.
 * @param  $attary          Array with all tag attributes.
 * @param  $rm_attnames     See description for sq_sanitize
 * @param  $bad_attvals     See description for sq_sanitize
 * @param  $add_attr_to_tag See description for sq_sanitize
 * @param  $message         message object
 * @param  $id              message id
 * @return                  Array with modified attributes.
 */
function sq_fixatts($tagname, 
                    $attary, 
                    $rm_attnames,
                    $bad_attvals,
                    $add_attr_to_tag,
                    $message,
                    $id
                    ){
    $me = "sq_fixatts";
    while (list($attname, $attvalue) = each($attary)){
        /**
         * See if this attribute should be removed.
         */
        foreach ($rm_attnames as $matchtag=>$matchattrs){
            if (preg_match($matchtag, $tagname)){
                foreach ($matchattrs as $matchattr){
                    if (preg_match($matchattr, $attname)){
                        unset($attary{$attname});
                        continue;
                    }
                }
            }
        }
        /**
         * Remove any entities.
         */
        $attvalue = sq_deent($attvalue);

        /**
         * Now let's run checks on the attvalues.
         * I don't expect anyone to comprehend this. If you do,
         * get in touch with me so I can drive to where you live and
         * shake your hand personally. :)
         */
        foreach ($bad_attvals as $matchtag=>$matchattrs){
            if (preg_match($matchtag, $tagname)){
                foreach ($matchattrs as $matchattr=>$valary){
                    if (preg_match($matchattr, $attname)){
                        /**
                         * There are two arrays in valary.
                         * First is matches.
                         * Second one is replacements
                         */
                        list($valmatch, $valrepl) = $valary;
                        $newvalue = 
                            preg_replace($valmatch, $valrepl, $attvalue);
                        if ($newvalue != $attvalue){
                            $attary{$attname} = $newvalue;
                        }
                    }
                }
            }
        }
        /**
         * Turn cid: urls into http-friendly ones.
         */
        if (preg_match("/^[\'\"]\s*cid:/si", $attvalue)){
            $attary{$attname} = sq_cid2http($message, $id, $attvalue);
        }
    }
    /**
     * See if we need to append any attributes to this tag.
     */
    foreach ($add_attr_to_tag as $matchtag=>$addattary){
        if (preg_match($matchtag, $tagname)){
            $attary = array_merge($attary, $addattary);
        }
    }
    return $attary;
}

/**
 * This function edits the style definition to make them friendly and
 * usable in squirrelmail.
 * 
 * @param  $message  the message object
 * @param  $id       the message id
 * @param  $content  a string with whatever is between <style> and </style>
 * @return           a string with edited content.
 */
function sq_fixstyle($message, $id, $content){
    global $view_unsafe_images;
    $me = "sq_fixstyle";
    /**
     * First look for general BODY style declaration, which would be
     * like so:
     * body {background: blah-blah}
     * and change it to .bodyclass so we can just assign it to a <div>
     */
    $content = preg_replace("|body(\s*\{.*?\})|si", ".bodyclass\\1", $content);
    $secremoveimg = "../images/" . _("sec_remove_eng.png");
    /**
     * Fix url('blah') declarations.
     */
    $content = preg_replace("|url\(([\'\"])\s*\S+script\s*:.*?([\'\"])\)|si",
                            "url(\\1$secremoveimg\\2)", $content);
    /**
     * Fix url('https*://.*) declarations but only if $view_unsafe_images
     * is false.
     */
    if (!$view_unsafe_images){
        $content = preg_replace("|url\(([\'\"])\s*https*:.*?([\'\"])\)|si",
                                "url(\\1$secremoveimg\\2)", $content);
    }
    
    /**
     * Fix urls that refer to cid:
     */
    while (preg_match("|url\(([\'\"]\s*cid:.*?[\'\"])\)|si", $content, 
                      $matches)){
        $cidurl = $matches{1};
        $httpurl = sq_cid2http($message, $id, $cidurl);
        $content = preg_replace("|url\($cidurl\)|si",
                                "url($httpurl)", $content);
    }

    /**
     * Fix stupid css declarations which lead to vulnerabilities
     * in IE.
     */
    $match   = Array('/expression/si',
		     '/behaviou*r/si',
		     '/binding/si');
    $replace = Array('idiocy', 'idiocy', 'idiocy');
    $content = preg_replace($match, $replace, $content);
    return $content;
}

/**
 * This function converts cid: url's into the ones that can be viewed in
 * the browser.
 *
 * @param  $message  the message object
 * @param  $id       the message id
 * @param  $cidurl   the cid: url.
 * @return           a string with a http-friendly url
 */
function sq_cid2http($message, $id, $cidurl){
    /**
     * Get rid of quotes.
     */
    $quotchar = substr($cidurl, 0, 1);
    $cidurl = str_replace($quotchar, "", $cidurl);
    $cidurl = substr(trim($cidurl), 4);
    $httpurl = $quotchar . "../src/download.php?absolute_dl=true&amp;" .
        "passed_id=$id&amp;mailbox=" . urlencode($message->header->mailbox) .
        "&amp;passed_ent_id=" . find_ent_id($cidurl, $message) . $quotchar;
    return $httpurl;
}

/**
 * This function changes the <body> tag into a <div> tag since we
 * can't really have a body-within-body.
 *
 * @param  $attary  an array of attributes and values of <body>
 * @return          a modified array of attributes to be set for <div>
 */
function sq_body2div($attary){
    $me = "sq_body2div";
    $divattary = Array("class"=>"'bodyclass'");
    $bgcolor="#ffffff";
    $text="#000000";
    $styledef="";
    if (is_array($attary) && sizeof($attary) > 0){
        foreach ($attary as $attname=>$attvalue){
            $quotchar = substr($attvalue, 0, 1);
            $attvalue = str_replace($quotchar, "", $attvalue);
            switch ($attname){
            case "background":
                $styledef .= "background-image: url('$attvalue'); ";
                break;
            case "bgcolor":
                $styledef .= "background-color: $attvalue; ";
                break;
            case "text":
                $styledef .= "color: $attvalue; ";
            }
        }
        if (strlen($styledef) > 0){
            $divattary{"style"} = "\"$styledef\"";
        }
    }
    return $divattary;
}

/**
 * This is the main function and the one you should actually be calling.
 * There are several variables you should be aware of an which need
 * special description.
 *
 * Since the description is quite lengthy, see it here:
 * http://www.mricon.com/html/phpfilter.html
 *
 * @param $body                 the string with HTML you wish to filter
 * @param $tag_list             see description above
 * @param $rm_tags_with_content see description above
 * @param $self_closing_tags    see description above
 * @param $force_tag_closing    see description above
 * @param $rm_attnames          see description above
 * @param $bad_attvals          see description above
 * @param $add_attr_to_tag      see description above
 * @param $message              message object
 * @param $id                   message id
 * @return                      sanitized html safe to show on your pages.
 */
function sq_sanitize($body, 
                     $tag_list, 
                     $rm_tags_with_content,
                     $self_closing_tags,
                     $force_tag_closing,
                     $rm_attnames,
                     $bad_attvals,
                     $add_attr_to_tag,
                     $message,
                     $id
                     ){
    $me = "sq_sanitize";
    /**
     * Normalize rm_tags and rm_tags_with_content.
     */
    @array_walk($rm_tags, 'sq_casenormalize');
    @array_walk($rm_tags_with_content, 'sq_casenormalize');
    @array_walk($self_closing_tags, 'sq_casenormalize');
    /**
     * See if tag_list is of tags to remove or tags to allow.
     * false  means remove these tags
     * true   means allow these tags
     */
    $rm_tags = array_shift($tag_list);
    $curpos = 0;
    $open_tags = Array();
    $trusted = "<!-- begin sanitized html -->\n";
    $skip_content = false;
    /**
     * Take care of netscape's stupid javascript entities like
     * &{alert('boo')};
     */
    $body = preg_replace("/&(\{.*?\};)/si", "&amp;\\1", $body);

    while (($curtag=sq_getnxtag($body, $curpos)) != FALSE){
        list($tagname, $attary, $tagtype, $lt, $gt) = $curtag;
        $free_content = substr($body, $curpos, $lt-$curpos);
        /**
         * Take care of <style>
         */
        if ($tagname == "style" && $tagtype == 2){
            /**
             * This is a closing </style>. Edit the
             * content before we apply it.
             */
            $free_content = sq_fixstyle($message, $id, $free_content);
        }
        if ($skip_content == false){
            $trusted .= $free_content;
        } else {
        }
        if ($tagname != FALSE){
            if ($tagtype == 2){
                if ($skip_content == $tagname){
                    /**
                     * Got to the end of tag we needed to remove.
                     */
                    $tagname = false;
                    $skip_content = false;
                } else {
                    if ($skip_content == false){
                        if ($tagname == "body"){
                            $tagname = "div";
                        } else {
                            if (isset($open_tags{$tagname}) && 
                                $open_tags{$tagname} > 0){
                                $open_tags{$tagname}--;
                            } else {
                                $tagname = false;
                            }
                        }
                    } else {
                    }
                }
            } else {
                /**
                 * $rm_tags_with_content
                 */
                if ($skip_content == false){
                    /**
                     * See if this is a self-closing type and change
                     * tagtype appropriately.
                     */
                    if ($tagtype == 1
                        && in_array($tagname, $self_closing_tags)){
                        $tagtype=3;
                    }
                    /**
                     * See if we should skip this tag and any content
                     * inside it.
                     */
                    if ($tagtype == 1 &&
                        in_array($tagname, $rm_tags_with_content)){
                        $skip_content = $tagname;
                    } else {
                        if (($rm_tags == false 
                             && in_array($tagname, $tag_list)) ||
                            ($rm_tags == true &&
                             !in_array($tagname, $tag_list))){
                            $tagname = false;
                        } else {
                            if ($tagtype == 1){
                                if (isset($open_tags{$tagname})){
                                    $open_tags{$tagname}++;
                                } else {
                                    $open_tags{$tagname}=1;
                                }
                            }
                            /**
                             * This is where we run other checks.
                             */
                            if (is_array($attary) && sizeof($attary) > 0){
                                $attary = sq_fixatts($tagname,
                                                     $attary,
                                                     $rm_attnames,
                                                     $bad_attvals,
                                                     $add_attr_to_tag,
                                                     $message,
                                                     $id
                                                     );
                            }
                            /**
                             * Convert body into div.
                             */
                            if ($tagname == "body"){
                                $tagname = "div";
                                $attary = sq_body2div($attary, $message, $id);
                            }
                        }
                    }
                } else {
                }
            }
            if ($tagname != false && $skip_content == false){
                $trusted .= sq_tagprint($tagname, $attary, $tagtype);
            }
        } else {
        }
        $curpos = $gt+1;
    }
    $trusted .= substr($body, $curpos, strlen($body)-$curpos);
    if ($force_tag_closing == true){
        foreach ($open_tags as $tagname=>$opentimes){
            while ($opentimes > 0){
                $trusted .= '</' . $tagname . '>';
                $opentimes--;
            }
        }
        $trusted .= "\n";
    }
    $trusted .= "<!-- end sanitized html -->\n";
    return $trusted;
}

/**
 * This is a wrapper function to call html sanitizing routines.
 *
 * @param  $body  the body of the message
 * @param  $id    the id of the message
 * @return        a string with html safe to display in the browser.
 */
function magicHTML($body, $id, $message){
    global $attachment_common_show_images, $view_unsafe_images,
        $has_unsafe_images;
    /**
     * Don't display attached images in HTML mode.
     */
    $attachment_common_show_images = false;
    $tag_list = Array(
                      false,
                      "object",
                      "meta",
                      "html",
                      "head",
                      "base"
                      );

    $rm_tags_with_content = Array(
                                  "script",
                                  "applet",
                                  "embed",
                                  "title"
                                  );

    $self_closing_tags =  Array(
                                "img",
                                "br",
                                "hr",
                                "input"
                                );

    $force_tag_closing = false;

    $rm_attnames = Array(
                         "/.*/" =>
                         Array(
                               "/target/si",
                               "/^on.*/si",
			       "/^dynsrc/si",
			       "/^data.*/si"
                               )
                         );

    $secremoveimg = "../images/" . _("sec_remove_eng.png");
    $bad_attvals = Array(
        "/.*/" =>
            Array(
                "/^src|background|href|action/i" =>
                    Array(
                          Array(
                                "|^([\'\"])\s*\.\./.*([\'\"])|si",
                                "/^([\'\"])\s*\S+script\s*:.*([\'\"])/si",
				"/^([\'\"])\s*mocha\s*:*(.*)([\'\"])/si",
				"/^([\'\"])\s*about\s*:(.*)([\'\"])/si"
                                ),
                          Array(
                                "\\1$secremoveimg\\2",
                                "\\1$secremoveimg\\2",
				"\\1$secremoveimg\\2",
				"\\1$secremoveimg\\2"
                                )
                        ),
                "/^style/si" =>
                    Array(
                          Array(
                                "/expression/si",
				"/binding/si",
				"/behaviou*r/si",
                                "|url\(([\'\"])\s*\.\./.*([\'\"])\)|si",
                                "/url\(([\'\"])\s*\S+script:.*([\'\"])\)/si"
                               ),
                          Array(
                                "idiocy",
				"idiocy",
				"idiocy",
                                "url(\\1$secremoveimg\\2)",
                                "url(\\1$secremoveimg\\2)"
                               )
                          )
                )
        );
    if (!$view_unsafe_images){
        /**
         * Remove any references to http/https if view_unsafe_images set
         * to false.
         */
         array_push($bad_attvals{'/.*/'}{'/^src|background|href|action/i'}[0],
                    '/^([\'\"])\s*https*:.*([\'\"])/si');
         array_push($bad_attvals{'/.*/'}{'/^src|background|href|action/i'}[1],
                    "\\1$secremoveimg\\2");
         array_push($bad_attvals{'/.*/'}{'/^style/si'}[0],
                    '/url\(([\'\"])\s*https*:.*([\'\"])\)/si');
         array_push($bad_attvals{'/.*/'}{'/^style/si'}[1],
                    "url(\\1$secremoveimg\\2)");
    }

    $add_attr_to_tag = Array(
                             "/^a$/si" => Array('target'=>'"_new"')
                             );
    $trusted = sq_sanitize($body, 
                           $tag_list, 
                           $rm_tags_with_content,
                           $self_closing_tags,
                           $force_tag_closing,
                           $rm_attnames,
                           $bad_attvals,
                           $add_attr_to_tag,
                           $message,
                           $id
                           );
    if (preg_match("|$secremoveimg|si", $trusted)){
        $has_unsafe_images = true;
    }
    return $trusted;
}
?>