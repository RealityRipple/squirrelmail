<?php

/**
 * printer_friendly_bottom.php
 *
 * with javascript on, it is the bottom frame of printer_friendly_main.php
 * else, it is alone in a new window
 *
 * - this is the page that does all the work, really.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');

/* get some of these globals */
sqgetGlobalVar('username', $username, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

if (! sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET) ) {
    $passed_ent_id = '';
}
sqgetGlobalVar('show_html_default', $show_html_default, SQ_FORM);
/* end globals */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);
if (isset($messages[$mbx_response['UIDVALIDITY']][$passed_id])) {
    $message = $messages[$mbx_response['UIDVALIDITY']][$passed_id];
} else {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
}
if ($passed_ent_id) {
    $message = $message->getEntity($passed_ent_id);
}

/* --start display setup-- */

$rfc822_header = $message->rfc822_header;
/* From and Date are usually fine as they are... */
$from = $rfc822_header->getAddr_s('from');
$date = getLongDateString($rfc822_header->date);
$subject = trim($rfc822_header->subject);

/* we can clean these up if the list is too long... */
$cc = $rfc822_header->getAddr_s('cc');
$to = $rfc822_header->getAddr_s('to');

if ($show_html_default == 1) {
    $ent_ar = $message->findDisplayEntity(array());
} else {
    $ent_ar = $message->findDisplayEntity(array(), array('text/plain'));
}
$body = '';
if ($ent_ar[0] != '') {
  for ($i = 0; $i < count($ent_ar); $i++) {
     $body .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox, TRUE);
     $body .= '<hr style="height: 1px;" />';
  }
  $hookResults = do_hook('message_body', $body);
  $body = $hookResults[1];
} else {
  $body = _("Message not printable");
}

/* now we clean up the display a bit... */

$num_leading_spaces = 9; // nine leading spaces for indentation

// sometimes I see ',,' instead of ',' separating addresses *shrug*
$cc = pf_clean_string(str_replace(',,', ',', $cc), $num_leading_spaces);
$to = pf_clean_string(str_replace(',,', ',', $to), $num_leading_spaces);

// clean up everything else...
$subject = pf_clean_string($subject, $num_leading_spaces);
$from = pf_clean_string($from, $num_leading_spaces);
$date = pf_clean_string($date, $num_leading_spaces);

// end cleanup

$to = decodeHeader($to);
$cc = decodeHeader($cc);
$from = decodeHeader($from);
$subject = decodeHeader($subject);

$attachments = pf_show_attachments($message,$ent_ar,$mailbox,$passed_id);

// --end display setup--


/* --start browser output-- */
displayHtmlHeader( $subject, '', FALSE );

echo '<body text="#000000" bgcolor="#FFFFFF" link="#000000" vlink="#000000" alink="#000000">'."\n" .
     /* headers (we use table because translations are not all the same width) */
     html_tag( 'table', '', 'center', '', 'cellspacing="0" cellpadding="0" border="0" width="100%"' ) .
     html_tag( 'tr',
         html_tag( 'td', '<b>'._("From").':</b>&nbsp;', 'left' ,'','valign="top"') .
         html_tag( 'td', $from, 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', '<b>'._("Subject").':</b>&nbsp;', 'left','','valign="top"' ) .
         html_tag( 'td', $subject, 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', '<b>'._("Date").':</b>&nbsp;', 'left' ) .
         html_tag( 'td', htmlspecialchars($date), 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', '<b>'._("To").':</b>&nbsp;', 'left','','valign="top"' ) .
         html_tag( 'td', $to, 'left' )
    ) . "\n";
    if ( strlen($cc) > 0 ) { /* only show Cc: if it's there... */
         echo html_tag( 'tr',
             html_tag( 'td', '<b>'._("Cc").':</b>&nbsp;', 'left','','valign="top"' ) .
             html_tag( 'td', $cc, 'left' )
         );
     }
     /* body */
     echo html_tag( 'tr',
         html_tag( 'td', '<hr style="height: 1px;" /><br />' . "\n" . $body, 'left', '', 'colspan="2"' )
     ) . "\n" ;
     
     if (! empty($attachments)) {
         // attachments title
         echo html_tag( 'tr',
             html_tag( 'td','<b>'._("Attachments:").'</b>', 'left', '', 'colspan="2"' )
         ) . "\n" ;
         // list of attachments
         echo html_tag( 'tr',
             html_tag( 'td',$attachments, 'left', '', 'colspan="2"' )
         ) . "\n" ;
         // add separator line
         echo html_tag( 'tr',
             html_tag( 'td', '<hr style="height: 1px;" />', 'left', '', 'colspan="2"' )
         ) . "\n" ;
     }

     echo '</table>' . "\n" .
     '</body></html>';

/* --end browser output-- */


/* --start pf-specific functions-- */

/**
 * Function should clean layout of printed messages when user
 * enables "Printer Friendly Clean Display" option.
 * For example: $string = pf_clean_string($string, 9);
 *
 * @param string unclean_string
 * @param integer num_leading_spaces
 * @return string
 * @access private
 */
function pf_clean_string ( $unclean_string, $num_leading_spaces ) {
    global $data_dir, $username;
    $unclean_string = str_replace('&nbsp;',' ',$unclean_string);
    $wrap_at = getPref($data_dir, $username, 'wrap_at', 86);
    $wrap_at = $wrap_at - $num_leading_spaces; /* header stuff */

    $leading_spaces = '';
    while ( strlen($leading_spaces) < $num_leading_spaces )
        $leading_spaces .= ' ';

    $clean_string = '';
    while ( strlen($unclean_string) > $wrap_at )
    {
        $this_line = substr($unclean_string, 0, $wrap_at);
        if ( strrpos( $this_line, "\n" ) ) /* this should NEVER happen with anything but the $body */
        {
            $clean_string .= substr( $this_line, 0, strrpos( $this_line, "\n" ));
            $clean_string .= $leading_spaces;
            $unclean_string = substr($unclean_string, strrpos( $this_line, "\n" ));
        }
        else
        {
            $i = strrpos( $this_line, ' ');
            $clean_string .= substr( $this_line, 0, $i);
            $clean_string .= "\n" . $leading_spaces;
            $unclean_string = substr($unclean_string, 1+$i);
        }
    }
    $clean_string .= $unclean_string;

    return $clean_string;
} /* end pf_clean_string() function */

/**
 * Displays attachment information
 *
 * Stripped version of formatAttachments() function from functions/mime.php.
 * @param object $message SquirrelMail message object
 * @param array $exclude_id message parts that are not attachments.
 * @param string $mailbox mailbox name
 * @param integer $id message id
 * @return string html formated attachment information.
 */
function pf_show_attachments($message, $exclude_id, $mailbox, $id) {
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

        if ($type0 =='message' && $type1 == 'rfc822') {
            $rfc822_header = $att->rfc822_header;
            $filename = $rfc822_header->subject;
            if (trim( $filename ) == '') {
                $filename = 'untitled-[' . $ent . ']' ;
            }
            $from_o = $rfc822_header->from;
            if (is_object($from_o)) {
                $from_name = decodeHeader($from_o->getAddress(true));
            } else {
                $from_name = _("Unknown sender");
            }
            $description = '<tr>'.
                html_tag( 'td',_("From:"), 'right') .
                html_tag( 'td',$from_name, 'left') .
                '</tr>';
        } else {
            $filename = $att->getFilename();
            if ($header->description) {
                $description = '<tr>'.
                    html_tag( 'td',_("Info:"), 'right') .
                    html_tag( 'td',decodeHeader($header->description), 'left') .
                    '</tr>';
            } else {
                $description = '';
            }
        }

        $display_filename = $filename;

        // TODO: maybe make it nicer?
        $attachments .= '<table cellpadding="1" cellspacing="0" width="100%" border="1"><tr><th colspan="2">'.decodeHeader($display_filename).'</th></tr>' .
            '<tr>'. 
            html_tag( 'td',_("Size:"), 'right', '', 'width="25%"') . 
            html_tag( 'td',show_readable_size($header->size), 'left', '', 'width="75%"') .
            '</tr><tr>' . "\n" .
            html_tag( 'td',_("Type:"), 'right', '', 'width="25%"') .
            html_tag( 'td',htmlspecialchars($type0).'/'.htmlspecialchars($type1), 'left', '', 'width="75%"') . 
            '</tr>';
        if (! empty($description)) {
            $attachments .= $description;
        }
        $attachments .= "</table>\n";
    }
    return $attachments;
}


/* --end pf-specific functions */

?>
