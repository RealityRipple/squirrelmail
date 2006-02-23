<?php

/**
 * mime.php
 *
 * This contains the functions necessary to detect and decode MIME
 * messages.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** The typical includes... */
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/attachment_common.php');
/** add sqm_baseuri()*/
include_once(SM_PATH . 'functions/display_messages.php');

/* -------------------------------------------------------------------------- */
/* MIME DECODING                                                              */
/* -------------------------------------------------------------------------- */

/**
 * Get the MIME structure
 *
 * This function gets the structure of a message and stores it in the "message" class.
 * It will return this object for use with all relevant header information and
 * fully parsed into the standard "message" object format.
 */
function mime_structure ($bodystructure, $flags=array()) {

    /* Isolate the body structure and remove beginning and end parenthesis. */
    $read = trim(substr ($bodystructure, strpos(strtolower($bodystructure), 'bodystructure') + 13));
    $read = trim(substr ($read, 0, -1));
    $i = 0;
    $msg = Message::parseStructure($read,$i);
    if (!is_object($msg)) {
        include_once(SM_PATH . 'functions/display_messages.php');
        global $color, $mailbox;
        /* removed urldecode because $_GET is auto urldecoded ??? */
        displayPageHeader( $color, $mailbox );
        $errormessage  = _("SquirrelMail could not decode the bodystructure of the message");
        $errormessage .= '<br />'._("The bodystructure provided by your IMAP server:").'<br /><br />';
        $errormessage .= '<pre>' . htmlspecialchars($read) . '</pre>';
        plain_error_message( $errormessage, $color );
        echo '</body></html>';
        exit;
    }
    if (count($flags)) {
        foreach ($flags as $flag) {
            $char = strtoupper($flag{1});
            switch ($char) {
                case 'S':
                    if (strtolower($flag) == '\\seen') {
                        $msg->is_seen = true;
                    }
                    break;
                case 'A':
                    if (strtolower($flag) == '\\answered') {
                        $msg->is_answered = true;
                    }
                    break;
                case 'D':
                    if (strtolower($flag) == '\\deleted') {
                        $msg->is_deleted = true;
                    }
                    break;
                case 'F':
                    if (strtolower($flag) == '\\flagged') {
                        $msg->is_flagged = true;
                    }
                    break;
                case 'M':
                    if (strtolower($flag) == '$mdnsent') {
                        $msg->is_mdnsent = true;
                    }
                    break;
                default:
                    break;
            }
        }
    }
    //    listEntities($msg);
    return $msg;
}



/* This starts the parsing of a particular structure.  It is called recursively,
 * so it can be passed different structures.  It returns an object of type
 * $message.
 * First, it checks to see if it is a multipart message.  If it is, then it
 * handles that as it sees is necessary.  If it is just a regular entity,
 * then it parses it and adds the necessary header information (by calling out
 * to mime_get_elements()
 */

function mime_fetch_body($imap_stream, $id, $ent_id=1, $fetch_size=0) {
    /* Do a bit of error correction.  If we couldn't find the entity id, just guess
     * that it is the first one.  That is usually the case anyway.
     */

    if (!$ent_id) {
        $cmd = "FETCH $id BODY[]";
    } else {
        $cmd = "FETCH $id BODY[$ent_id]";
    }

    if ($fetch_size!=0) $cmd .= "<0.$fetch_size>";

    $data = sqimap_run_command ($imap_stream, $cmd, true, $response, $message, TRUE);
    do {
        $topline = trim(array_shift($data));
    } while($topline && ($topline[0] == '*') && !preg_match('/\* [0-9]+ FETCH.*/i', $topline)) ;

    $wholemessage = implode('', $data);
    if (ereg('\\{([^\\}]*)\\}', $topline, $regs)) {
        $ret = substr($wholemessage, 0, $regs[1]);
        /* There is some information in the content info header that could be important
         * in order to parse html messages. Let's get them here.
         */
//        if ($ret{0} == '<') {
//            $data = sqimap_run_command ($imap_stream, "FETCH $id BODY[$ent_id.MIME]", true, $response, $message, TRUE);
//        }
    } else if (ereg('"([^"]*)"', $topline, $regs)) {
        $ret = $regs[1];
    } else {
        global $where, $what, $mailbox, $passed_id, $startMessage;
        $par = 'mailbox=' . urlencode($mailbox) . '&amp;passed_id=' . $passed_id;
        if (isset($where) && isset($what)) {
            $par .= '&amp;where=' . urlencode($where) . '&amp;what=' . urlencode($what);
        } else {
            $par .= '&amp;startMessage=' . $startMessage . '&amp;show_more=0';
        }
        $par .= '&amp;response=' . urlencode($response) .
            '&amp;message='  . urlencode($message)  .
            '&amp;topline='  . urlencode($topline);

        echo '<tt><br />' .
            '<table width="80%"><tr>' .
            '<tr><td colspan="2">' .
            _("Body retrieval error. The reason for this is most probably that the message is malformed.") .
            '</td></tr>' .
            '<tr><td><b>' . _("Command:") . "</td><td>$cmd</td></tr>" .
            '<tr><td><b>' . _("Response:") . "</td><td>$response</td></tr>" .
            '<tr><td><b>' . _("Message:") . "</td><td>$message</td></tr>" .
            '<tr><td><b>' . _("FETCH line:") . "</td><td>$topline</td></tr>" .
            "</table><br /></tt></font><hr />";

        $data = sqimap_run_command ($imap_stream, "FETCH $passed_id BODY[]", true, $response, $message, TRUE);
        array_shift($data);
        $wholemessage = implode('', $data);

        $ret = $wholemessage;
    }
    return $ret;
}

function mime_print_body_lines ($imap_stream, $id, $ent_id=1, $encoding, $rStream='php://stdout') {

    /* Don't kill the connection if the browser is over a dialup
     * and it would take over 30 seconds to download it.
     * Don't call set_time_limit in safe mode.
     */

    if (!ini_get('safe_mode')) {
        set_time_limit(0);
    }
    /* in case of base64 encoded attachments, do not buffer them.
       Instead, echo the decoded attachment directly to screen */
    if (strtolower($encoding) == 'base64') {
        if (!$ent_id) {
            $query = "FETCH $id BODY[]";
        } else {
            $query = "FETCH $id BODY[$ent_id]";
        }
        sqimap_run_command($imap_stream,$query,true,$response,$message,TRUE,'sqimap_base64_decode',$rStream,true);
    } else {
        $body = mime_fetch_body ($imap_stream, $id, $ent_id);
        if (is_resource($rStream)) {
            fputs($rStream,decodeBody($body,$encoding));
        } else {
            echo decodeBody($body, $encoding);
        }
    }

    /*
       TODO, use the same method for quoted printable.
       However, I assume that quoted printable attachments aren't that large
       so the performancegain / memory usage drop will be minimal.
       If we decide to add that then we need to adapt sqimap_fread because
       we need to split te result on \n and fread doesn't stop at \n. That
       means we also should provide $results from sqimap_fread (by ref) to
       te function and set $no_return to false. The $filter function for
       quoted printable should handle unsetting of $results.
     */
    /*
       TODO 2: find out how we write to the output stream php://stdout. fwrite
       doesn't work because 'php://stdout isn't a stream.
     */

    return;
}

/* -[ END MIME DECODING ]----------------------------------------------------------- */

/* This is here for debugging purposes.  It will print out a list
 * of all the entity IDs that are in the $message object.
 */
function listEntities ($message) {
    if ($message) {
        echo "<tt>" . $message->entity_id . ' : ' . $message->type0 . '/' . $message->type1 . ' parent = '. $message->parent->entity_id. '<br />';
        for ($i = 0; isset($message->entities[$i]); $i++) {
            echo "$i : ";
            $msg = listEntities($message->entities[$i]);

            if ($msg) {
                echo "return: ";
                return $msg;
            }
        }
    }
}

function getPriorityStr($priority) {
    $priority_level = substr($priority,0,1);

    switch($priority_level) {
        /* Check for a higher then normal priority. */
        case '1':
        case '2':
            $priority_string = _("High");
            break;

        /* Check for a lower then normal priority. */
        case '4':
        case '5':
            $priority_string = _("Low");
            break;

        /* Check for a normal priority. */
        case '3':
        default:
            $priority_level = '3';
            $priority_string = _("Normal");
            break;

    }
    return $priority_string;
}

/* returns a $message object for a particular entity id */
function getEntity ($message, $ent_id) {
    return $message->getEntity($ent_id);
}

/* translateText
 * Extracted from strings.php 23/03/2002
 */

function translateText(&$body, $wrap_at, $charset) {
    global $where, $what;   /* from searching */
    global $color;          /* color theme */

    require_once(SM_PATH . 'functions/url_parser.php');

    $body_ary = explode("\n", $body);
    for ($i=0; $i < count($body_ary); $i++) {
        $line = $body_ary[$i];
        if (strlen($line) - 2 >= $wrap_at) {
            sqWordWrap($line, $wrap_at, $charset);
        }
        $line = charset_decode($charset, $line);
        $line = str_replace("\t", '        ', $line);

        parseUrl ($line);

        $quotes = 0;
        $pos = 0;
        $j = strlen($line);

        while ($pos < $j) {
            if ($line[$pos] == ' ') {
                $pos++;
            } else if (strpos($line, '&gt;', $pos) === $pos) {
                $pos += 4;
                $quotes++;
            } else {
                break;
            }
        }

        if ($quotes % 2) {
            if (!isset($color[13])) {
                $color[13] = '#800000';
            }
            $line = '<font color="' . $color[13] . '">' . $line . '</font>';
        } elseif ($quotes) {
            if (!isset($color[14])) {
                $color[14] = '#FF0000';
            }
            $line = '<font color="' . $color[14] . '">' . $line . '</font>';
        }

        $body_ary[$i] = $line;
    }
    $body = '<pre>' . implode("\n", $body_ary) . '</pre>';
}

/**
 * This returns a parsed string called $body. That string can then
 * be displayed as the actual message in the HTML. It contains
 * everything needed, including HTML Tags, Attachments at the
 * bottom, etc.
 *
 * Since 1.2.0 function uses message_body hook.
 * Till 1.3.0 function included output of formatAttachments().
 *
 * @param resource $imap_stream imap connection resource
 * @param object $message squirrelmail message object
 * @param array $color squirrelmail color theme array
 * @param integer $wrap_at number of characters per line
 * @param string $ent_num (since 1.3.0) message part id
 * @param integer $id (since 1.3.0) message id
 * @param string $mailbox (since 1.3.0) imap folder name
 * @param boolean $clean (since 1.5.1) Do not output stuff that's irrelevant for the printable version.
 * @return string html formated message text
 */
function formatBody($imap_stream, $message, $color, $wrap_at, $ent_num, $id, $mailbox='INBOX', $clean=FALSE) {
    /* This if statement checks for the entity to show as the
     * primary message. To add more of them, just put them in the
     * order that is their priority.
     */
    global $startMessage, $languages, $squirrelmail_language,
           $show_html_default, $sort, $has_unsafe_images, $passed_ent_id,
           $use_iframe, $iframe_height, $download_and_unsafe_link;

    // workaround for not updated config.php
    if (! isset($use_iframe)) $use_iframe = false;

    if( !sqgetGlobalVar('view_unsafe_images', $view_unsafe_images, SQ_GET) ) {
        $view_unsafe_images = false;
    }

    $body = '';
    $urlmailbox = urlencode($mailbox);
    $body_message = getEntity($message, $ent_num);
    if (($body_message->header->type0 == 'text') ||
            ($body_message->header->type0 == 'rfc822')) {
        $body = mime_fetch_body ($imap_stream, $id, $ent_num);
        $body = decodeBody($body, $body_message->header->encoding);

        if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
                function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode')) {
            if (mb_detect_encoding($body) != 'ASCII') {
                $body = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode',$body);
            }
        }
        $hookResults = do_hook("message_body", $body);
        $body = $hookResults[1];

        /* If there are other types that shouldn't be formatted, add
         * them here.
         */

        if ($body_message->header->type1 == 'html') {
            if ($show_html_default <> 1) {
                $entity_conv = array('&nbsp;' => ' ',
                                     '<p>'    => "\n",
                                     '<P>'    => "\n",
                                     '<br>'   => "\n",
                                     '<BR>'   => "\n",
                                     '<br />' => "\n",
                                     '<BR />' => "\n",
                                     '&gt;'   => '>',
                                     '&lt;'   => '<');
                $body = strtr($body, $entity_conv);
                $body = strip_tags($body);
                $body = trim($body);
                translateText($body, $wrap_at,
                        $body_message->header->getParameter('charset'));
            } elseif ($use_iframe && ! $clean) {
                // $clean is used to remove iframe in printable view.

                /**
                 * If we don't add html message between iframe tags,
                 * we must detect unsafe images and modify $has_unsafe_images.
                 */
                $html_body = magicHTML($body, $id, $message, $mailbox);
                // Convert character set in order to display html mails in different character set
                $html_body = charset_decode($body_message->header->getParameter('charset'),$html_body,false,true);

                // creating iframe url
                $iframeurl=sqm_baseuri().'src/view_html.php?'
                    . 'mailbox=' . $urlmailbox
                    . '&amp;passed_id=' . $id
                    . '&amp;ent_id=' . $ent_num
                    . '&amp;view_unsafe_images=' . (int) $view_unsafe_images;

                // adding warning message
                $body = html_tag('div',_("Viewing HTML formatted email"),'center');

                /**
                 * height can't be set to 100%, because it does not work as expected when
                 * iframe is inside the table. Browsers do not create full height objects
                 * even when iframe is not nested. Maybe there is some way to get full size
                 * with CSS. Tested in firefox 1.02 and opera 7.53
                 *
                 * width="100%" does not work as expected, when table width is not set (automatic)
                 *
                 * tokul: I think <iframe> are safer sandbox than <object>. Objects might
                 * need special handling for IE and IE6SP2.
                 */
                $body.= "<div><iframe name=\"message_frame\" width=\"100%\" height=\"$iframe_height\" src=\"$iframeurl\""
                    .' frameborder="1" marginwidth="0" marginheight="0" scrolling="auto">' . "\n";

                // Message for browsers without iframe support
                //$body.= _("Your browser does not support inline frames.
                // You can view HTML formated message by following below link.");
                //$body.= "<br /><a href=\"$iframeurl\">"._("View HTML Message")."</a>";

                // if browser can't render iframe, it renders html message.
                $body.= $html_body;

                // close iframe
                $body.="</iframe></div>\n";
            } else {
                // old way of html rendering
                $body = magicHTML($body, $id, $message, $mailbox);
                /**
                 * convert character set. charset_decode does not remove html special chars
                 * applied by magicHTML functions and does not sanitize them second time if
                 * fourth argument is true.
                 */
                $body = charset_decode($body_message->header->getParameter('charset'),$body,false,true);
            }
        } else {
            translateText($body, $wrap_at,
                    $body_message->header->getParameter('charset'));
        }

        // if this is the clean display (i.e. printer friendly), stop here.
        if ( $clean ) {
            return $body;
        }

        $download_and_unsafe_link = '';

        $link = 'passed_id=' . $id . '&amp;ent_id='.$ent_num.
            '&amp;mailbox=' . $urlmailbox .'&amp;sort=' . $sort .
            '&amp;startMessage=' . $startMessage . '&amp;show_more=0';
        if (isset($passed_ent_id)) {
            $link .= '&amp;passed_ent_id='.$passed_ent_id;
        }
        $download_and_unsafe_link .= '&nbsp;|&nbsp;<a href="download.php?absolute_dl=true&amp;' .
            $link . '">' . _("Download this as a file") .  '</a>';
        if ($view_unsafe_images) {
            $text = _("Hide Unsafe Images");
        } else {
            if (isset($has_unsafe_images) && $has_unsafe_images) {
                $link .= '&amp;view_unsafe_images=1';
                $text = _("View Unsafe Images");
            } else {
                $text = '';
            }
        }
        if($text != '') {
            $download_and_unsafe_link .= '&nbsp;|&nbsp;<a href="read_body.php?' . $link . '">' . $text . '</a>';
        }
    }
    return $body;
}

/**
 * Displays attachment links and information
 *
 * Since 1.3.0 function is not included in formatBody() call.
 *
 * Since 1.0.2 uses attachment $type0/$type1 hook.
 * Since 1.2.5 uses attachment $type0/* hook.
 * Since 1.5.0 uses attachments_bottom hook.
 *
 * @param object $message SquirrelMail message object
 * @param array $exclude_id message parts that are not attachments.
 * @param string $mailbox mailbox name
 * @param integer $id message id
 * @return string html formated attachment information.
 */
function formatAttachments($message, $exclude_id, $mailbox, $id) {
    global $where, $what, $startMessage, $color, $passed_ent_id;

    $att_ar = $message->getAttachments($exclude_id);

    if (!count($att_ar)) return '';

    $attachments = '';

    $urlMailbox = urlencode($mailbox);

    foreach ($att_ar as $att) {
        $ent = $att->entity_id;
        $header = $att->header;
        $type0 = strtolower($header->type0);
        $type1 = strtolower($header->type1);
        $name = '';
        $links['download link']['text'] = _("Download");
        $links['download link']['href'] = sqm_baseuri() .
            "src/download.php?absolute_dl=true&amp;passed_id=$id&amp;mailbox=$urlMailbox&amp;ent_id=$ent";
        if ($type0 =='message' && $type1 == 'rfc822') {
            $default_page = sqm_baseuri() . 'src/read_body.php';
            $rfc822_header = $att->rfc822_header;
            $filename = $rfc822_header->subject;
            if (trim( $filename ) == '') {
                $filename = 'untitled-[' . $ent . ']' ;
            }
            $from_o = $rfc822_header->from;
            if (is_object($from_o)) {
                $from_name = decodeHeader($from_o->getAddress(false));
            } elseif (is_array($from_o) && count($from_o) && is_object($from_o[0])) {
                // something weird happens when a digest message is opened and you return to the digest
                // now the from object is part of an array. Probably the parseHeader call overwrites the info
                // retrieved from the bodystructure in a different way. We need to fix this later.
                // possible starting point, do not fetch header we already have and inspect how
                // the rfc822_header object behaves.
                $from_name = decodeHeader($from_o[0]->getAddress(false));
            } else {
                $from_name = _("Unknown sender");
            }
            $description = $from_name;
        } else {
            $default_page = sqm_baseuri() . 'src/download.php';
            $filename = $att->getFilename();
            if ($header->description) {
                $description = decodeHeader($header->description);
            } else {
                $description = '';
            }
        }

        $display_filename = $filename;
        if (isset($passed_ent_id)) {
            $passed_ent_id_link = '&amp;passed_ent_id='.$passed_ent_id;
        } else {
            $passed_ent_id_link = '';
        }
        $defaultlink = $default_page . "?startMessage=$startMessage"
            . "&amp;passed_id=$id&amp;mailbox=$urlMailbox"
            . '&amp;ent_id='.$ent.$passed_ent_id_link;
        if ($where && $what) {
            $defaultlink .= '&amp;where='. urlencode($where).'&amp;what='.urlencode($what);
        }

        /* This executes the attachment hook with a specific MIME-type.
         * If that doesn't have results, it tries if there's a rule
         * for a more generic type.
         */
        $hookresults = do_hook("attachment $type0/$type1", $links,
                $startMessage, $id, $urlMailbox, $ent, $defaultlink,
                $display_filename, $where, $what);
        if(count($hookresults[1]) <= 1) {
            $hookresults = do_hook("attachment $type0/*", $links,
                    $startMessage, $id, $urlMailbox, $ent, $defaultlink,
                    $display_filename, $where, $what);
        }

        $links = $hookresults[1];
        $defaultlink = $hookresults[6];

        $attachments .= '<tr><td>' .
            '<a href="'.$defaultlink.'">'.decodeHeader($display_filename).'</a>&nbsp;</td>' .
            '<td><small><b>' . show_readable_size($header->size) .
            '</b>&nbsp;&nbsp;</small></td>' .
            '<td><small>[ '.htmlspecialchars($type0).'/'.htmlspecialchars($type1).' ]&nbsp;</small></td>' .
            '<td><small>';
        $attachments .= '<b>' . $description . '</b>';
        $attachments .= '</small></td><td><small>&nbsp;';

        $skipspaces = 1;
        foreach ($links as $val) {
            if ($skipspaces) {
                $skipspaces = 0;
            } else {
                $attachments .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
            }
            $attachments .= '<a href="' . $val['href'] . '">'
                . (isset($val['text']) && !empty($val['text']) ? $val['text'] : '')
                . (isset($val['extra']) && !empty($val['extra']) ? $val['extra'] : '')
                . '</a>';
        }
        unset($links);
        $attachments .= "</td></tr>\n";
    }
    $attachmentadd = do_hook_function('attachments_bottom',$attachments);
    if ($attachmentadd != '')
        $attachments = $attachmentadd;
    return $attachments;
}

function sqimap_base64_decode(&$string) {

    // Base64 encoded data goes in pairs of 4 bytes. To achieve on the
    // fly decoding (to reduce memory usage) you have to check if the
    // data has incomplete pairs

    // Remove the noise in order to check if the 4 bytes pairs are complete
    $string = str_replace(array("\r\n","\n", "\r", " "),array('','','',''),$string);

    $sStringRem = '';
    $iMod = strlen($string) % 4;
    if ($iMod) {
        $sStringRem = substr($string,-$iMod);
        // Check if $sStringRem contains padding characters
        if (substr($sStringRem,-1) != '=') {
            $string = substr($string,0,-$iMod);
        } else {
            $sStringRem = '';
        }
    }
    $string = base64_decode($string);
    return $sStringRem;
}

/**
 * Decodes encoded message body
 *
 * This function decodes the body depending on the encoding type.
 * Currently quoted-printable and base64 encodings are supported.
 * decode_body hook was added to this function in 1.4.2/1.5.0
 * @param string $body encoded message body
 * @param string $encoding used encoding
 * @return string decoded string
 * @since 1.0
 */
function decodeBody($body, $encoding) {

    $body = str_replace("\r\n", "\n", $body);
    $encoding = strtolower($encoding);

    $encoding_handler = do_hook_function('decode_body', $encoding);


    // plugins get first shot at decoding the body
    //
    if (!empty($encoding_handler) && function_exists($encoding_handler)) {
        $body = $encoding_handler('decode', $body);

    } elseif ($encoding == 'quoted-printable' ||
            $encoding == 'quoted_printable') {
        /**
         * quoted_printable_decode() function is broken in older
         * php versions. Text with \r\n decoding was fixed only
         * in php 4.3.0. Minimal code requirement 4.0.4 +
         * str_replace("\r\n", "\n", $body); call.
         */
        $body = quoted_printable_decode($body);
    } elseif ($encoding == 'base64') {
        $body = base64_decode($body);
    }

    // All other encodings are returned raw.
    return $body;
}

/**
 * Decodes headers
 *
 * This functions decode strings that is encoded according to
 * RFC1522 (MIME Part Two: Message Header Extensions for Non-ASCII Text).
 * Patched by Christian Schmidt <christian@ostenfeld.dk>  23/03/2002
 *
 * @param string $string header string that has to be made readable
 * @param boolean $utfencode change message in order to be readable on user's charset. defaults to true
 * @param boolean $htmlsave preserve spaces and sanitize html special characters. defaults to true
 * @param boolean $decide decide if string can be utfencoded. defaults to false
 * @return string decoded header string
 */
function decodeHeader ($string, $utfencode=true,$htmlsave=true,$decide=false) {
    global $languages, $squirrelmail_language,$default_charset;
    if (is_array($string)) {
        $string = implode("\n", $string);
    }

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
            function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_decodeheader')) {
        $string = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decodeheader', $string);
        // Do we need to return at this point?
        // return $string;
    }
    $i = 0;
    $iLastMatch = -2;
    $encoded = true;

    $aString = explode(' ',$string);
    $ret = '';
    foreach ($aString as $chunk) {
        if ($encoded && $chunk === '') {
            continue;
        } elseif ($chunk === '') {
            $ret .= ' ';
            continue;
        }
        $encoded = false;
        /* if encoded words are not separated by a linear-space-white we still catch them */
        $j = $i-1;

        while ($match = preg_match('/^(.*)=\?([^?]*)\?(Q|B)\?([^?]*)\?=(.*)$/Ui',$chunk,$res)) {
            /* if the last chunk isn't an encoded string then put back the space, otherwise don't */
            if ($iLastMatch !== $j) {
                if ($htmlsave) {
                    $ret .= '&#32;';
                } else {
                    $ret .= ' ';
                }
            }
            $iLastMatch = $i;
            $j = $i;
            if ($htmlsave) {
                $ret .= htmlspecialchars($res[1]);
            } else {
                $ret .= $res[1];
            }
            $encoding = ucfirst($res[3]);

            /* decide about valid decoding */
            if ($decide && is_conversion_safe($res[2])) {
                $utfencode=true;
                $can_be_encoded=true;
            } else {
                $can_be_encoded=false;
            }
            switch ($encoding)
            {
                case 'B':
                    $replace = base64_decode($res[4]);
                    if ($utfencode) {
                        if ($can_be_encoded) {
                            /* convert string to different charset,
                             * if functions asks for it (usually in compose)
                             */
                            $ret .= charset_convert($res[2],$replace,$default_charset);
                        } else {
                            // convert string to html codes in order to display it
                            $ret .= charset_decode($res[2],$replace);
                        }
                    } else {
                        if ($htmlsave) {
                            $replace = htmlspecialchars($replace);
                        }
                        $ret.= $replace;
                    }
                    break;
                case 'Q':
                    $replace = str_replace('_', ' ', $res[4]);
                    $replace = preg_replace('/=([0-9a-f]{2})/ie', 'chr(hexdec("\1"))',
                            $replace);
                    if ($utfencode) {
                        if ($can_be_encoded) {
                            /* convert string to different charset,
                             * if functions asks for it (usually in compose)
                             */
                            $replace = charset_convert($res[2], $replace,$default_charset);
                        } else {
                            // convert string to html codes in order to display it
                            $replace = charset_decode($res[2], $replace);
                        }
                    } else {
                        if ($htmlsave) {
                            $replace = htmlspecialchars($replace);
                        }
                    }
                    $ret .= $replace;
                    break;
                default:
                    break;
            }
            $chunk = $res[5];
            $encoded = true;
        }
        if (!$encoded) {
            if ($htmlsave) {
                $ret .= '&#32;';
            } else {
                $ret .= ' ';
            }
        }

        if (!$encoded && $htmlsave) {
            $ret .= htmlspecialchars($chunk);
        } else {
            $ret .= $chunk;
        }
        ++$i;
    }
    /* remove the first added space */
    if ($ret) {
        if ($htmlsave) {
            $ret = substr($ret,5);
        } else {
            $ret = substr($ret,1);
        }
    }

    return $ret;
}

/**
 * Encodes header
 *
 * Function uses XTRA_CODE _encodeheader function, if such function exists.
 *
 * Function uses Q encoding by default and encodes a string according to RFC
 * 1522 for use in headers if it contains 8-bit characters or anything that
 * looks like it should be encoded.
 *
 * Function switches to B encoding and encodeHeaderBase64() function, if
 * string is 8bit and multibyte character set supported by mbstring extension
 * is used. It can cause E_USER_NOTICE errors, if interface is used with
 * multibyte character set unsupported by mbstring extension.
 *
 * @param string $string header string, that has to be encoded
 * @return string quoted-printable encoded string
 * @todo make $mb_charsets system wide constant
 */
function encodeHeader ($string) {
    global $default_charset, $languages, $squirrelmail_language;

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
            function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_encodeheader')) {
        return  call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_encodeheader', $string);
    }

    // Use B encoding for multibyte charsets
    $mb_charsets = array('utf-8','big5','gb2313','euc-kr');
    if (in_array($default_charset,$mb_charsets) &&
        in_array($default_charset,sq_mb_list_encodings()) &&
        sq_is8bit($string)) {
        return encodeHeaderBase64($string,$default_charset);
    } elseif (in_array($default_charset,$mb_charsets) &&
              sq_is8bit($string) &&
              ! in_array($default_charset,sq_mb_list_encodings())) {
        // Add E_USER_NOTICE error here (can cause 'Cannot add header information' warning in compose.php)
        // trigger_error('encodeHeader: Multibyte character set unsupported by mbstring extension.',E_USER_NOTICE);
    }

    // Encode only if the string contains 8-bit characters or =?
    $j = strlen($string);
    $max_l = 75 - strlen($default_charset) - 7;
    $aRet = array();
    $ret = '';
    $iEncStart = $enc_init = false;
    $cur_l = $iOffset = 0;
    for($i = 0; $i < $j; ++$i) {
        switch($string{$i})
        {
            case '=':
            case '<':
            case '>':
            case ',':
            case '?':
            case '_':
                if ($iEncStart === false) {
                    $iEncStart = $i;
                }
                $cur_l+=3;
                if ($cur_l > ($max_l-2)) {
                    /* if there is an stringpart that doesn't need encoding, add it */
                    $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
                    $aRet[] = "=?$default_charset?Q?$ret?=";
                    $iOffset = $i;
                    $cur_l = 0;
                    $ret = '';
                    $iEncStart = false;
                } else {
                    $ret .= sprintf("=%02X",ord($string{$i}));
                }
                break;
            case '(':
            case ')':
                if ($iEncStart !== false) {
                    $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
                    $aRet[] = "=?$default_charset?Q?$ret?=";
                    $iOffset = $i;
                    $cur_l = 0;
                    $ret = '';
                    $iEncStart = false;
                }
                break;
            case ' ':
                if ($iEncStart !== false) {
                    $cur_l++;
                    if ($cur_l > $max_l) {
                        $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
                        $aRet[] = "=?$default_charset?Q?$ret?=";
                        $iOffset = $i;
                        $cur_l = 0;
                        $ret = '';
                        $iEncStart = false;
                    } else {
                        $ret .= '_';
                    }
                }
                break;
            default:
                $k = ord($string{$i});
                if ($k > 126) {
                    if ($iEncStart === false) {
                        // do not start encoding in the middle of a string, also take the rest of the word.
                        $sLeadString = substr($string,0,$i);
                        $aLeadString = explode(' ',$sLeadString);
                        $sToBeEncoded = array_pop($aLeadString);
                        $iEncStart = $i - strlen($sToBeEncoded);
                        $ret .= $sToBeEncoded;
                        $cur_l += strlen($sToBeEncoded);
                    }
                    $cur_l += 3;
                    /* first we add the encoded string that reached it's max size */
                    if ($cur_l > ($max_l-2)) {
                        $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
                        $aRet[] = "=?$default_charset?Q?$ret?= "; /* the next part is also encoded => separate by space */
                        $cur_l = 3;
                        $ret = '';
                        $iOffset = $i;
                        $iEncStart = $i;
                    }
                    $enc_init = true;
                    $ret .= sprintf("=%02X", $k);
                } else {
                    if ($iEncStart !== false) {
                        $cur_l++;
                        if ($cur_l > $max_l) {
                            $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
                            $aRet[] = "=?$default_charset?Q?$ret?=";
                            $iEncStart = false;
                            $iOffset = $i;
                            $cur_l = 0;
                            $ret = '';
                        } else {
                            $ret .= $string{$i};
                        }
                    }
                }
                break;
        }
    }

    if ($enc_init) {
        if ($iEncStart !== false) {
            $aRet[] = substr($string,$iOffset,$iEncStart-$iOffset);
            $aRet[] = "=?$default_charset?Q?$ret?=";
        } else {
            $aRet[] = substr($string,$iOffset);
        }
        $string = implode('',$aRet);
    }
    return $string;
}

/**
 * Encodes string according to rfc2047 B encoding header formating rules
 *
 * It is recommended way to encode headers with character sets that store
 * symbols in more than one byte.
 *
 * Function requires mbstring support. If required mbstring functions are missing,
 * function returns false and sets E_USER_WARNING level error message.
 *
 * Minimal requirements - php 4.0.6 with mbstring extension. Please note,
 * that mbstring functions will generate E_WARNING errors, if unsupported
 * character set is used. mb_encode_mimeheader function provided by php
 * mbstring extension is not used in order to get better control of header
 * encoding.
 *
 * Used php code functions - function_exists(), trigger_error(), strlen()
 * (is used with charset names and base64 strings). Used php mbstring
 * functions - mb_strlen and mb_substr.
 *
 * Related documents: rfc 2045 (BASE64 encoding), rfc 2047 (mime header
 * encoding), rfc 2822 (header folding)
 *
 * @param string $string header string that must be encoded
 * @param string $charset character set. Must be supported by mbstring extension.
 * Use sq_mb_list_encodings() to detect supported charsets.
 * @return string string encoded according to rfc2047 B encoding formating rules
 * @since 1.5.1
 * @todo First header line can be wrapped to $iMaxLength - $HeaderFieldLength - 1
 * @todo Do we want to control max length of header?
 * @todo Do we want to control EOL (end-of-line) marker?
 * @todo Do we want to translate error message?
 */
function encodeHeaderBase64($string,$charset) {
    /**
     * Check mbstring function requirements.
     */
    if (! function_exists('mb_strlen') ||
        ! function_exists('mb_substr')) {
        // set E_USER_WARNING
        trigger_error('encodeHeaderBase64: Required mbstring functions are missing.',E_USER_WARNING);
        // return false
        return false;
    }

    // initial return array
    $aRet = array();

    /**
     * header length = 75 symbols max (same as in encodeHeader)
     * remove $charset length
     * remove =? ? ?= (5 chars)
     * remove 2 more chars (\r\n ?)
     */
    $iMaxLength = 75 - strlen($charset) - 7;

    // set first character position
    $iStartCharNum = 0;

    // loop through all characters. count characters and not bytes.
    for ($iCharNum=1; $iCharNum<=mb_strlen($string,$charset); $iCharNum++) {
        // encode string from starting character to current character.
        $encoded_string = base64_encode(mb_substr($string,$iStartCharNum,$iCharNum-$iStartCharNum,$charset));

        // Check encoded string length
        if(strlen($encoded_string)>$iMaxLength) {
            // if string exceeds max length, reduce number of encoded characters and add encoded string part to array
            $aRet[] = base64_encode(mb_substr($string,$iStartCharNum,$iCharNum-$iStartCharNum-1,$charset));

            // set new starting character
            $iStartCharNum = $iCharNum-1;

            // encode last char (in case it is last character in string)
            $encoded_string = base64_encode(mb_substr($string,$iStartCharNum,$iCharNum-$iStartCharNum,$charset));
        } // if string is shorter than max length - add next character
    }

    // add last encoded string to array
    $aRet[] = $encoded_string;

    // set initial return string
    $sRet = '';

    // loop through encoded strings
    foreach($aRet as $string) {
        // TODO: Do we want to control EOL (end-of-line) marker
        if ($sRet!='') $sRet.= " ";

        // add header tags and encoded string to return string
        $sRet.= '=?'.$charset.'?B?'.$string.'?=';
    }

    return $sRet;
}

/* This function trys to locate the entity_id of a specific mime element */
function find_ent_id($id, $message) {
    for ($i = 0, $ret = ''; $ret == '' && $i < count($message->entities); $i++) {
        if ($message->entities[$i]->header->type0 == 'multipart')  {
            $ret = find_ent_id($id, $message->entities[$i]);
        } else {
            if (strcasecmp($message->entities[$i]->header->id, $id) == 0) {
//                if (sq_check_save_extension($message->entities[$i])) {
                return $message->entities[$i]->entity_id;
//                }
            } elseif (!empty($message->entities[$i]->header->parameters['name'])) {
                /**
                 * This is part of a fix for Outlook Express 6.x generating
                 * cid URLs without creating content-id headers
                 * @@JA - 20050207
                 */
                if (strcasecmp($message->entities[$i]->header->parameters['name'], $id) == 0) {
                    return $message->entities[$i]->entity_id;
                }
            }
        }
    }
    return $ret;
}

function sq_check_save_extension($message) {
    $filename = $message->getFilename();
    $ext = substr($filename, strrpos($filename,'.')+1);
    $save_extensions = array('jpg','jpeg','gif','png','bmp');
    return in_array($ext, $save_extensions);
}


/**
 ** HTMLFILTER ROUTINES
 */

/**
 * This function checks attribute values for entity-encoded values
 * and returns them translated into 8-bit strings so we can run
 * checks on them.
 *
 * @param  $attvalue A string to run entity check against.
 * @return           Nothing, modifies a reference value.
 */
function sq_defang(&$attvalue){
    $me = 'sq_defang';
    /**
     * Skip this if there aren't ampersands or backslashes.
     */
    if (strpos($attvalue, '&') === false
        && strpos($attvalue, '\\') === false){
        return;
    }
    $m = false;
    do {
        $m = false;
        $m = $m || sq_deent($attvalue, '/\&#0*(\d+);*/s');
        $m = $m || sq_deent($attvalue, '/\&#x0*((\d|[a-f])+);*/si', true);
        $m = $m || sq_deent($attvalue, '/\\\\(\d+)/s', true);
    } while ($m == true);
    $attvalue = stripslashes($attvalue);
}

/**
 * Kill any tabs, newlines, or carriage returns. Our friends the
 * makers of the browser with 95% market value decided that it'd
 * be funny to make "java[tab]script" be just as good as "javascript".
 *
 * @param  attvalue  The attribute value before extraneous spaces removed.
 * @return attvalue  Nothing, modifies a reference value.
 */
function sq_unspace(&$attvalue){
    $me = 'sq_unspace';
    if (strcspn($attvalue, "\t\r\n\0 ") != strlen($attvalue)){
        $attvalue = str_replace(Array("\t", "\r", "\n", "\0", " "),
                                Array('',   '',   '',   '',   ''), $attvalue);
    }
}

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
    $me = 'sq_tagprint';

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
            $fulltag .= ' /';
        }
        $fulltag .= '>';
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
    $me = 'sq_skipspace';
    preg_match('/^(\s*)/s', substr($body, $offset), $matches);
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
    $me  = 'sq_findnxstr';
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
    $me = 'sq_findnxreg';
    $matches = Array();
    $retarr = Array();
    preg_match("%^(.*?)($reg)%si", substr($body, $offset), $matches);
    if (!isset($matches{0}) || !$matches{0}){
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
    $me = 'sq_getnxtag';
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
     *    <img src="blah" />
     */
    $tagtype = false;
    switch (substr($body, $pos, 1)){
        case '/':
            $tagtype = 2;
            $pos++;
            break;
        case '!':
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
        case '/':
            /**
             * This is an xhtml-style tag with a closing / at the
             * end, like so: <img src="blah" />. Check if it's followed
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
        case '>':
            return Array($tagname, false, $tagtype, $lt, $pos);
            break;
        default:
            /**
             * Check if it's whitespace
             */
            if (!preg_match('/\s/', $match)){
                /**
                 * This is an invalid tag! Look for the next closing ">".
                 */
                $gt = sq_findnxstr($body, $lt, ">");
                return Array(false, false, false, $lt, $gt);
            }
            break;
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
        $regary = sq_findnxreg($body, $pos, "[^:\w\-_]");
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
            case '/':
                /**
                 * This is an xhtml-style tag with a closing / at the
                 * end, like so: <img src="blah" />. Check if it's followed
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
            case '>':
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
                break;
        }
    }
    /**
     * The fact that we got here indicates that the tag end was never
     * found. Return invalid tag indication so it gets stripped.
     */
    return Array(false, false, false, $lt, strlen($body));
}

/**
 * Translates entities into literal values so they can be checked.
 *
 * @param $attvalue the by-ref value to check.
 * @param $regex    the regular expression to check against.
 * @param $hex      whether the entites are hexadecimal.
 * @return          True or False depending on whether there were matches.
 */
function sq_deent(&$attvalue, $regex, $hex=false){
    $me = 'sq_deent';
    $ret_match = false;
    preg_match_all($regex, $attvalue, $matches);
    if (is_array($matches) && sizeof($matches[0]) > 0){
        $repl = Array();
        for ($i = 0; $i < sizeof($matches[0]); $i++){
            $numval = $matches[1][$i];
            if ($hex){
                $numval = hexdec($numval);
            }
            $repl{$matches[0][$i]} = chr($numval);
        }
        $attvalue = strtr($attvalue, $repl);
        return true;
    } else {
        return false;
    }
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
                    $id,
                    $mailbox
                    ){
    $me = 'sq_fixatts';
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
         * Remove any backslashes, entities, and extraneous whitespace.
         */
        sq_defang($attvalue);
        sq_unspace($attvalue);

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
         * Replace empty src tags with the blank image.  src is only used
         * for frames, images, and image inputs.  Doing a replace should
         * not affect them working as should be, however it will stop
         * IE from being kicked off when src for img tags are not set
         */
        if (($attname == 'src') && ($attvalue == '""')) {
            $attary{$attname} = '"' . SM_PATH . 'images/blank.png"';
        }

        /**
         * Turn cid: urls into http-friendly ones.
         */
        if (preg_match("/^[\'\"]\s*cid:/si", $attvalue)){
            $attary{$attname} = sq_cid2http($message, $id, $attvalue, $mailbox);
        }

        /**
         * "Hack" fix for Outlook using propriatary outbind:// protocol in img tags.
         * One day MS might actually make it match something useful, for now, falling
         * back to using cid2http, so we can grab the blank.png.
         */
        if (preg_match("/^[\'\"]\s*outbind:\/\//si", $attvalue)) {
            $attary{$attname} = sq_cid2http($message, $id, $attvalue, $mailbox);
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
 * usable in SquirrelMail.
 *
 * @param  $message  the message object
 * @param  $id       the message id
 * @param  $content  a string with whatever is between <style> and </style>
 * @param  $mailbox  the message mailbox
 * @return           a string with edited content.
 */
function sq_fixstyle($body, $pos, $message, $id, $mailbox){
    global $view_unsafe_images;
    $me = 'sq_fixstyle';
    $ret = sq_findnxreg($body, $pos, '</\s*style\s*>');
    if ($ret == FALSE){
        return array(FALSE, strlen($body));
    }
    $newpos = $ret[0] + strlen($ret[2]);
    $content = $ret[1];
    /**
    * First look for general BODY style declaration, which would be
    * like so:
    * body {background: blah-blah}
    * and change it to .bodyclass so we can just assign it to a <div>
    */
    $content = preg_replace("|body(\s*\{.*?\})|si", ".bodyclass\\1", $content);
    $secremoveimg = '../images/' . _("sec_remove_eng.png");
    /**
    * Fix url('blah') declarations.
    */
    //   $content = preg_replace("|url\s*\(\s*([\'\"])\s*\S+script\s*:.*?([\'\"])\s*\)|si",
    //                           "url(\\1$secremoveimg\\2)", $content);
    // remove NUL
    $content = str_replace("\0", "", $content);
    // translate ur\l and variations (IE parses that)
    $content = preg_replace("/(\\\\)?u(\\\\)?r(\\\\)?l(\\\\)?/i", 'url', $content);
    // NB I insert NUL characters to keep to avoid an infinite loop. They are removed after the loop.
    while (preg_match("/url\s*\(\s*[\'\"]?([^:]+):(.*)?[\'\"]?\s*\)/si", $content, $matches)) {
        $sProto = strtolower($matches[1]);
        switch ($sProto) {
            /**
             * Fix url('https*://.*) declarations but only if $view_unsafe_images
             * is false.
             */
            case 'https':
            case 'http':
                if (!$view_unsafe_images){

                    $sExpr = "/url\s*\(\s*[\'\"]?\s*$sProto*:.*[\'\"]?\s*\)/si";
                    $content = preg_replace($sExpr, "u\0r\0l(\\1$secremoveimg\\2)", $content);

                } else {
                    $content = preg_replace('/url/i',"u\0r\0l",$content);
                }
                break;
            /**
             * Fix urls that refer to cid:
             */
            case 'cid':
                $cidurl = 'cid:'. $matches[2];
                $httpurl = sq_cid2http($message, $id, $cidurl, $mailbox);
                // escape parentheses that can modify the regular expression
                $cidurl = str_replace(array('(',')'),array('\\(','\\)'),$cidurl);
                $content = preg_replace("|url\s*\(\s*$cidurl\s*\)|si",
                                        "u\0r\0l($httpurl)", $content);
                break;
            default:
                /**
                 * replace url with protocol other then the white list
                 * http,https and cid by an empty string.
                 */
                $content = preg_replace("/url\s*\(\s*[\'\"]?([^:]+):(.*)?[\'\"]?\s*\)/si",
                                 "", $content);
                break;
        }
    }
    // remove NUL
    $content = str_replace("\0", "", $content);
   /**
    * Remove any backslashes, entities, and extraneous whitespace.
    */
    $contentTemp = $content;
    sq_defang($contentTemp);
    sq_unspace($contentTemp);

    /**
     * Fix stupid css declarations which lead to vulnerabilities
     * in IE.
     */
    $match   = Array('/\/\*.*\*\//',
                    '/expression/i',
                    '/behaviou*r/i',
                    '/binding/i',
                    '/include-source/i');
    $replace = Array('','idiocy', 'idiocy', 'idiocy', 'idiocy');
    $contentNew = preg_replace($match, $replace, $contentTemp);
    if ($contentNew !== $contentTemp) {
        // insecure css declarations are used. From now on we don't care
        // anymore if the css is destroyed by sq_deent, sq_unspace or sq_unbackslash
        $content = $contentNew;
    }
    return array($content, $newpos);
}


/**
 * This function converts cid: url's into the ones that can be viewed in
 * the browser.
 *
 * @param  $message  the message object
 * @param  $id       the message id
 * @param  $cidurl   the cid: url.
 * @param  $mailbox  the message mailbox
 * @return           a string with a http-friendly url
 */
function sq_cid2http($message, $id, $cidurl, $mailbox){
    /**
     * Get rid of quotes.
     */
    $quotchar = substr($cidurl, 0, 1);
    if ($quotchar == '"' || $quotchar == "'"){
        $cidurl = str_replace($quotchar, "", $cidurl);
    } else {
        $quotchar = '';
    }
    $cidurl = substr(trim($cidurl), 4);

    $match_str = '/\{.*?\}\//';
    $str_rep = '';
    $cidurl = preg_replace($match_str, $str_rep, $cidurl);

    $linkurl = find_ent_id($cidurl, $message);
    /* in case of non-save cid links $httpurl should be replaced by a sort of
       unsave link image */
    $httpurl = '';

    /**
     * This is part of a fix for Outlook Express 6.x generating
     * cid URLs without creating content-id headers. These images are
     * not part of the multipart/related html mail. The html contains
     * <img src="cid:{some_id}/image_filename.ext"> references to
     * attached images with as goal to render them inline although
     * the attachment disposition property is not inline.
     */

    if (empty($linkurl)) {
        if (preg_match('/{.*}\//', $cidurl)) {
            $cidurl = preg_replace('/{.*}\//','', $cidurl);
            if (!empty($cidurl)) {
                $linkurl = find_ent_id($cidurl, $message);
            }
        }
    }

    if (!empty($linkurl)) {
        $httpurl = $quotchar . SM_PATH . 'src/download.php?absolute_dl=true&amp;' .
            "passed_id=$id&amp;mailbox=" . urlencode($mailbox) .
            '&amp;ent_id=' . $linkurl . $quotchar;
    } else {
        /**
         * If we couldn't generate a proper img url, drop in a blank image
         * instead of sending back empty, otherwise it causes unusual behaviour
         */
        $httpurl = $quotchar . SM_PATH . 'images/blank.png' . $quotchar;
    }

    return $httpurl;
}

/**
 * This function changes the <body> tag into a <div> tag since we
 * can't really have a body-within-body.
 *
 * @param  $attary   an array of attributes and values of <body>
 * @param  $mailbox  mailbox we're currently reading (for cid2http)
 * @param  $message  current message (for cid2http)
 * @param  $id       current message id (for cid2http)
 * @return           a modified array of attributes to be set for <div>
 */
function sq_body2div($attary, $mailbox, $message, $id){
    $me = 'sq_body2div';
    $divattary = Array('class' => "'bodyclass'");
    $text = '#000000';
    $has_bgc_stl = $has_txt_stl = false;
    $styledef = '';
    if (is_array($attary) && sizeof($attary) > 0){
        foreach ($attary as $attname=>$attvalue){
            $quotchar = substr($attvalue, 0, 1);
            $attvalue = str_replace($quotchar, "", $attvalue);
            switch ($attname){
                case 'background':
                    $attvalue = sq_cid2http($message, $id, $attvalue, $mailbox);
                    $styledef .= "background-image: url('$attvalue'); ";
                    break;
                case 'bgcolor':
                    $has_bgc_stl = true;
                    $styledef .= "background-color: $attvalue; ";
                    break;
                case 'text':
                    $has_txt_stl = true;
                    $styledef .= "color: $attvalue; ";
                    break;
            }
        }
        // Outlook defines a white bgcolor and no text color. This can lead to
        // white text on a white bg with certain themes.
        if ($has_bgc_stl && !$has_txt_stl) {
            $styledef .= "color: $text; ";
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
 * http://linux.duke.edu/projects/mini/htmlfilter/
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
                     $id,
                     $mailbox
                     ){
    $me = 'sq_sanitize';
    $rm_tags = array_shift($tag_list);
    /**
     * Normalize rm_tags and rm_tags_with_content.
     */
    @array_walk($tag_list, 'sq_casenormalize');
    @array_walk($rm_tags_with_content, 'sq_casenormalize');
    @array_walk($self_closing_tags, 'sq_casenormalize');
    /**
     * See if tag_list is of tags to remove or tags to allow.
     * false  means remove these tags
     * true   means allow these tags
     */
    $curpos = 0;
    $open_tags = Array();
    $trusted = "\n<!-- begin sanitized html -->\n";
    $skip_content = false;
    /**
     * Take care of netscape's stupid javascript entities like
     * &{alert('boo')};
     */
    $body = preg_replace("/&(\{.*?\};)/si", "&amp;\\1", $body);

    while (($curtag = sq_getnxtag($body, $curpos)) != FALSE){
        list($tagname, $attary, $tagtype, $lt, $gt) = $curtag;
        $free_content = substr($body, $curpos, $lt-$curpos);
        /**
         * Take care of <style>
         */
        if ($tagname == "style" && $tagtype == 1){
            list($free_content, $curpos) =
                sq_fixstyle($body, $gt+1, $message, $id, $mailbox);
            if ($free_content != FALSE){
                $trusted .= sq_tagprint($tagname, $attary, $tagtype);
                $trusted .= $free_content;
                $trusted .= sq_tagprint($tagname, false, 2);
            }
            continue;
        }
        if ($skip_content == false){
            $trusted .= $free_content;
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
                        }
                        if (isset($open_tags{$tagname}) &&
                                $open_tags{$tagname} > 0){
                            $open_tags{$tagname}--;
                        } else {
                            $tagname = false;
                        }
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
                        $tagtype = 3;
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
                            /**
                             * Convert body into div.
                             */
                            if ($tagname == "body"){
                                $tagname = "div";
                                $attary = sq_body2div($attary, $mailbox,
                                        $message, $id);
                            }
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
                                                     $id,
                                                     $mailbox
                                                     );
                            }
                        }
                    }
                }
            }
            if ($tagname != false && $skip_content == false){
                $trusted .= sq_tagprint($tagname, $attary, $tagtype);
            }
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

 * @param  $message
 * @param  $mailbox
 * @param  boolean $take_mailto_links When TRUE, converts mailto: links
 *                                    into internal SM compose links
 *                                    (optional; default = TRUE)
 * @return        a string with html safe to display in the browser.
 */
function magicHTML($body, $id, $message, $mailbox = 'INBOX', $take_mailto_links =true) {

    require_once(SM_PATH . 'functions/url_parser.php');  // for $MailTo_PReg_Match

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
            "base",
            "link",
            "frame",
            "iframe",
            "plaintext",
            "marquee"
            );

    $rm_tags_with_content = Array(
            "script",
            "applet",
            "embed",
            "title",
            "frameset",
            "xmp",
            "xml"
            );

    $self_closing_tags =  Array(
            "img",
            "br",
            "hr",
            "input",
            "outbind"
            );

    $force_tag_closing = true;

    $rm_attnames = Array(
            "/.*/" =>
            Array(
                "/target/i",
                "/^on.*/i",
                "/^dynsrc/i",
                "/^data.*/i",
                "/^lowsrc.*/i"
                )
            );

    $secremoveimg = "../images/" . _("sec_remove_eng.png");
    $bad_attvals = Array(
            "/.*/" =>
            Array(
                "/^src|background/i" =>
                Array(
                    Array(
                        "/^([\'\"])\s*\S+script\s*:.*([\'\"])/si",
                        "/^([\'\"])\s*mocha\s*:*.*([\'\"])/si",
                        "/^([\'\"])\s*about\s*:.*([\'\"])/si"
                        ),
                    Array(
                        "\\1$secremoveimg\\2",
                        "\\1$secremoveimg\\2",
                        "\\1$secremoveimg\\2",
                        )
                    ),
                "/^href|action/i" =>
                Array(
                    Array(
                        "/^([\'\"])\s*\S+script\s*:.*([\'\"])/si",
                        "/^([\'\"])\s*mocha\s*:*.*([\'\"])/si",
                        "/^([\'\"])\s*about\s*:.*([\'\"])/si"
                        ),
                    Array(
                        "\\1#\\1",
                        "\\1#\\1",
                        "\\1#\\1"
                        )
                    ),
        "/^style/i" =>
            Array(
                Array(
                    "/\/\*.*\*\//",
                    "/expression/i",
                    "/binding/i",
                    "/behaviou*r/i",
                    "/include-source/i",
                    "/position\s*:\s*absolute/i",
                    "/(\\\\)?u(\\\\)?r(\\\\)?l(\\\\)?/i",
                    "/url\s*\(\s*([\'\"])\s*\S+script\s*:.*([\'\"])\s*\)/si",
                    "/url\s*\(\s*([\'\"])\s*mocha\s*:.*([\'\"])\s*\)/si",
                    "/url\s*\(\s*([\'\"])\s*about\s*:.*([\'\"])\s*\)/si",
                    "/(.*)\s*:\s*url\s*\(\s*([\'\"]*)\s*\S+script\s*:.*([\'\"]*)\s*\)/si"
                    ),
                Array(
                    "",
                    "idiocy",
                    "idiocy",
                    "idiocy",
                    "idiocy",
                    "",
                    "url",
                    "url(\\1#\\1)",
                    "url(\\1#\\1)",
                    "url(\\1#\\1)",
                    "\\1:url(\\2#\\3)"
                    )
                )
            )
        );
    if( !sqgetGlobalVar('view_unsafe_images', $view_unsafe_images, SQ_GET) ) {
        $view_unsafe_images = false;
    }
    if (!$view_unsafe_images){
        /**
         * Remove any references to http/https if view_unsafe_images set
         * to false.
         */
        array_push($bad_attvals{'/.*/'}{'/^src|background/i'}[0],
                '/^([\'\"])\s*https*:.*([\'\"])/si');
        array_push($bad_attvals{'/.*/'}{'/^src|background/i'}[1],
                "\\1$secremoveimg\\1");
        array_push($bad_attvals{'/.*/'}{'/^style/i'}[0],
                '/url\([\'\"]?https?:[^\)]*[\'\"]?\)/si');
        array_push($bad_attvals{'/.*/'}{'/^style/i'}[1],
                "url(\\1$secremoveimg\\1)");
    }

    $add_attr_to_tag = Array(
            "/^a$/i" =>
            Array('target'=>'"_blank"',
                'title'=>'"'._("This external link will open in a new window").'"'
                )
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
                           $id,
                           $mailbox
                           );
    if (preg_match("|$secremoveimg|i", $trusted)){
        $has_unsafe_images = true;
    }

    // we want to parse mailto's in HTML output, change to SM compose links
    // this is a modified version of code from url_parser.php... but Marc is
    // right: we need a better filtering implementation; adding this randomly
    // here is not a great solution
    //
    if ($take_mailto_links) {
        // parseUrl($trusted);   // this even parses URLs inside of tags... too aggressive
        global $MailTo_PReg_Match;
        $MailTo_PReg_Match = '/mailto:' . substr($MailTo_PReg_Match, 1);
        if ((preg_match_all($MailTo_PReg_Match, $trusted, $regs)) && ($regs[0][0] != '')) {
            foreach ($regs[0] as $i => $mailto_before) {
                $mailto_params = $regs[10][$i];
                // get rid of any tailing quote since we have to add send_to to the end
                //
                if (substr($mailto_before, strlen($mailto_before) - 1) == '"')
                    $mailto_before = substr($mailto_before, 0, strlen($mailto_before) - 1);
                if (substr($mailto_params, strlen($mailto_params) - 1) == '"')
                    $mailto_params = substr($mailto_params, 0, strlen($mailto_params) - 1);

                if ($regs[1][$i]) {    //if there is an email addr before '?', we need to merge it with the params
                    $to = 'to=' . $regs[1][$i];
                    if (strpos($mailto_params, 'to=') > -1)    //already a 'to='
                        $mailto_params = str_replace('to=', $to . '%2C%20', $mailto_params);
                    else {
                        if ($mailto_params)    //already some params, append to them
                            $mailto_params .= '&amp;' . $to;
                        else
                            $mailto_params .= '?' . $to;
                    }
                }

                $url_str = preg_replace(array('/to=/i', '/(?<!b)cc=/i', '/bcc=/i'), array('send_to=', 'send_to_cc=', 'send_to_bcc='), $mailto_params);

                // we'll already have target=_blank, no need to allow comp_in_new
                // here (which would be a lot more work anyway)
                //
                global $compose_new_win;
                $temp_comp_in_new = $compose_new_win;
                $compose_new_win = 0;
                $comp_uri = makeComposeLink('src/compose.php' . $url_str, $mailto_before);
                $compose_new_win = $temp_comp_in_new;

                // remove <a href=" and anything after the next quote (we only
                // need the uri, not the link HTML) in compose uri
                //
                $comp_uri = substr($comp_uri, 9);
                $comp_uri = substr($comp_uri, 0, strpos($comp_uri, '"', 1));
                $trusted = str_replace($mailto_before, $comp_uri, $trusted);
            }
        }
    }

    return $trusted;
}

/**
 * function SendDownloadHeaders - send file to the browser
 *
 * Original Source: SM core src/download.php
 * moved here to make it available to other code, and separate
 * front end from back end functionality.
 *
 * @param string $type0 first half of mime type
 * @param string $type1 second half of mime type
 * @param string $filename filename to tell the browser for downloaded file
 * @param boolean $force whether to force the download dialog to pop
 * @param optional integer $filesize send the Content-Header and length to the browser
 * @return void
 */
function SendDownloadHeaders($type0, $type1, $filename, $force, $filesize=0) {
    global $languages, $squirrelmail_language;
    $isIE = $isIE6plus = false;

    sqgetGlobalVar('HTTP_USER_AGENT', $HTTP_USER_AGENT, SQ_SERVER);

    if (strstr($HTTP_USER_AGENT, 'compatible; MSIE ') !== false &&
            strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE = true;
    }

    if (preg_match('/compatible; MSIE ([0-9]+)/', $HTTP_USER_AGENT, $match) &&
        ((int)$match[1]) >= 6 && strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE6plus = true;
    }

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
            function_exists($languages[$squirrelmail_language]['XTRA_CODE'] . '_downloadfilename')) {
        $filename =
            call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_downloadfilename', $filename, $HTTP_USER_AGENT);
    } else {
        $filename = ereg_replace('[\\/:\*\?"<>\|;]', '_', str_replace('&nbsp;', ' ', $filename));
    }

    // A Pox on Microsoft and it's Internet Explorer!
    //
    // IE has lots of bugs with file downloads.
    // It also has problems with SSL.  Both of these cause problems
    // for us in this function.
    //
    // See this article on Cache Control headers and SSL
    // http://support.microsoft.com/default.aspx?scid=kb;en-us;323308
    //
    // The best thing you can do for IE is to upgrade to the latest
    // version
    //set all the Cache Control Headers for IE
    if ($isIE) {
        $filename=rawurlencode($filename);
        header ("Pragma: public");
        header ("Cache-Control: no-store, max-age=0, no-cache, must-revalidate"); // HTTP/1.1
        header ("Cache-Control: post-check=0, pre-check=0", false);
        header ("Cache-Control: private");

        //set the inline header for IE, we'll add the attachment header later if we need it
        header ("Content-Disposition: inline; filename=$filename");
    }

    if (!$force) {
        // Try to show in browser window
        header ("Content-Disposition: inline; filename=\"$filename\"");
        header ("Content-Type: $type0/$type1; name=\"$filename\"");
    } else {
        // Try to pop up the "save as" box

        // IE makes this hard.  It pops up 2 save boxes, or none.
        // http://support.microsoft.com/support/kb/articles/Q238/5/88.ASP
        // http://support.microsoft.com/default.aspx?scid=kb;EN-US;260519
        // But, according to Microsoft, it is "RFC compliant but doesn't
        // take into account some deviations that allowed within the
        // specification."  Doesn't that mean RFC non-compliant?
        // http://support.microsoft.com/support/kb/articles/Q258/4/52.ASP

        // all browsers need the application/octet-stream header for this
        header ("Content-Type: application/octet-stream; name=\"$filename\"");

        // http://support.microsoft.com/support/kb/articles/Q182/3/15.asp
        // Do not have quotes around filename, but that applied to
        // "attachment"... does it apply to inline too?
        header ("Content-Disposition: attachment; filename=\"$filename\"");

        if ($isIE && !$isIE6plus) {
            // This combination seems to work mostly.  IE 5.5 SP 1 has
            // known issues (see the Microsoft Knowledge Base)

            // This works for most types, but doesn't work with Word files
            header ("Content-Type: application/download; name=\"$filename\"");

            // These are spares, just in case.  :-)
            //header("Content-Type: $type0/$type1; name=\"$filename\"");
            //header("Content-Type: application/x-msdownload; name=\"$filename\"");
            //header("Content-Type: application/octet-stream; name=\"$filename\"");
        } else {
            // another application/octet-stream forces download for Netscape
            header ("Content-Type: application/octet-stream; name=\"$filename\"");
        }
    }

    //send the content-length header if the calling function provides it
    if ($filesize > 0) {
        header("Content-Length: $filesize");
    }

}  // end fn SendDownloadHeaders

?>
