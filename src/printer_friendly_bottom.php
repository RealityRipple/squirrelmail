<?php

/**
 * printer_friendly_bottom.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * with javascript on, it is the bottom frame of printer_friendly_main.php
 * else, it is alone in a new window
 *
 * - this is the page that does all the work, really.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');

/* get some of these globals */
$key = $_COOKIE['key'];
$username = $_SESSION['username'];
$onetimepad = $_SESSION['onetimepad'];

$passed_id = (int) $_GET['passed_id'];
$mailbox = $_GET['mailbox'];

if (!isset($_GET['passed_ent_id'])) {
    $passed_ent_id = '';
} else {
    $passed_ent_id = $_GET['passed_ent_id'];
}
/* end globals */

$pf_cleandisplay = getPref($data_dir, $username, 'pf_cleandisplay');
$mailbox = urldecode($mailbox);
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);
if (isset($messages[$mbx_response['UIDVALIDITY']][$passed_id])) {
    $message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
} else {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
}
if ($passed_ent_id) {
    $message = &$message->getEntity($passed_ent_id);
}

/* --start display setup-- */

$rfc822_header = $message->rfc822_header; 
/* From and Date are usually fine as they are... */
$from = decodeHeader($rfc822_header->getAddr_s('from'));
$date = getLongDateString($rfc822_header->date);
$subject = trim(decodeHeader($rfc822_header->subject));

/* we can clean these up if the list is too long... */
$cc = decodeHeader($rfc822_header->getAddr_s('cc'));
$to = decodeHeader($rfc822_header->getAddr_s('to'));

if ($show_html_default == 1) {
    $ent_ar = $message->findDisplayEntity(array());
} else {
    $ent_ar = $message->findDisplayEntity(array(), array('text/plain'));
}
$body = '';
if ($ent_ar[0] != '') {
  for ($i = 0; $i < count($ent_ar); $i++) {
     $body .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox);
     $body .= '<hr noshade size="1" />';
  }
  $hookResults = do_hook('message_body', $body);
  $body = $hookResults[1];
} else {
  $body = _("Message not printable");
}

 /* now, if they choose to, we clean up the display a bit... */
 
if ( empty($pf_cleandisplay) || $pf_cleandisplay != 'no' ) {

    $num_leading_spaces = 9; // nine leading spaces for indentation

     // sometimes I see ',,' instead of ',' seperating addresses *shrug*
    $cc = pf_clean_string(str_replace(',,', ',', $cc), $num_leading_spaces);
    $to = pf_clean_string(str_replace(',,', ',', $to), $num_leading_spaces);

     // the body should have no leading zeros
    // disabled because it destroys html mail

//    $body = pf_clean_string($body, 0);

     // clean up everything else...
    $subject = pf_clean_string($subject, $num_leading_spaces);
    $from = pf_clean_string($from, $num_leading_spaces);
    $date = pf_clean_string($date, $num_leading_spaces);

} // end cleanup

// --end display setup--


/* --start browser output-- */
displayHtmlHeader( _("Printer Friendly"), '', FALSE );

echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
     /* headers (we use table because translations are not all the same width) */
     html_tag( 'table', '', 'center', '', 'cellspacing="0" cellpadding="0" border="0" width="100%"' ) .
     html_tag( 'tr',
         html_tag( 'td', _("From").'&nbsp;', 'left' ,'','valign="top"') .
         html_tag( 'td', htmlspecialchars($from), 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', _("Subject").'&nbsp;', 'left','','valign="top"' ) .
         html_tag( 'td', htmlspecialchars($subject), 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', _("Date").'&nbsp;', 'left' ) .
         html_tag( 'td', htmlspecialchars($date), 'left' )
     ) . "\n" .
     html_tag( 'tr',
         html_tag( 'td', _("To").'&nbsp;', 'left','','valign="top"' ) .
         html_tag( 'td', htmlspecialchars($to), 'left' )
     ) . "\n";
    if ( strlen($cc) > 0 ) { /* only show CC: if it's there... */
         echo html_tag( 'tr',
             html_tag( 'td', _("CC").'&nbsp;', 'left','','valign="top"' ) .
             html_tag( 'td', htmlspecialchars($cc), 'left' )
         );
     }
     /* body */
     echo html_tag( 'tr',
         html_tag( 'td', '<hr noshade size="1" /><br>' . "\n" . $body, 'left', '', 'colspan="2"' )
     ) . "\n" .

     '</table>' . "\n" .
     '</body></html>';

/* --end browser output-- */


/* --start pf-specific functions-- */

/* $string = pf_clean_string($string, 9); */
function pf_clean_string ( $unclean_string, $num_leading_spaces ) {
    global $data_dir, $username;

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
            $clean_string .= substr( $this_line, 0, strrpos( $this_line, ' ' ));
            $clean_string .= "\n" . $leading_spaces;
            $unclean_string = substr($unclean_string, (1+strrpos( $this_line, ' ' )));
        }
    }

    $clean_string .= $unclean_string;

    return $clean_string;
} /* end pf_clean_string() function */

/* --end pf-specific functions */

?>
