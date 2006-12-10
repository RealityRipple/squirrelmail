<?php

/**
 * printer_friendly_bottom.php
 *
 * with javascript on, it is the bottom frame of printer_friendly_main.php
 * else, it is alone in a new window
 *
 * - this is the page that does all the work, really.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/url_parser.php');

/* get some of these globals */
sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

if (! sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET) ) {
    $passed_ent_id = '';
}
sqgetGlobalVar('show_html_default', $show_html_default, SQ_FORM);
/* end globals */

$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);
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
     if ($i < count($ent_ar)-1) {
        $body .= '<hr />';
     }
  }
  /* Note that $body is passed to this hook (and modified) by reference as of 1.5.2 */
  do_hook('message_body', $body);
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

// --end display setup--


/* --start browser output-- */
displayHtmlHeader($subject);

$aHeaders = array();
$aHeaders[ _("From") ] = $from;
$aHeaders[ _("Subject") ] = $subject;
$aHeaders[ _("Date") ] = htmlspecialchars($date);
$aHeaders[ _("To") ] = $to;
$aHeaders[ _("Cc") ] = $cc;

$attachments_ar = buildAttachmentArray($message, $ent_ar, $mailbox, $passed_id);

$oTemplate->assign('headers', $aHeaders);
$oTemplate->assign('message_body', $body);
$oTemplate->assign('attachments', $attachments_ar);

$oTemplate->display('printer_friendly_bottom.tpl');
$oTemplate->display('footer.tpl');

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
