<?php

/**
 * view_text.php -- Displays the main frameset
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Who knows what this file does. However PUT IT HERE DID NOT PUT
 * A SINGLE FREAKING COMMENT IN! Whoever is responsible for this,
 * be very ashamed.
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/html.php');

sqgetGlobalVar('key',        $key,          SQ_COOKIE);
sqgetGlobalVar('username',   $username,     SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad,   SQ_SESSION);
sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);
if (sqgetGlobalVar('passed_id', $temp, SQ_GET)) {
    $passed_id = (int) $temp;
}

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response   = sqimap_mailbox_select($imapConnection, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
if (!is_object($message)) {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
}
$message_ent = &$message->getEntity($ent_id);
if ($passed_ent_id) {
    $message = &$message->getEntity($passed_ent_id);
}
$header   = $message_ent->header;
$type0    = $header->type0;
$type1    = $header->type1;
$charset  = $header->getParameter('charset');
$encoding = strtolower($header->encoding);

$msg_url   = 'read_body.php?' . $QUERY_STRING;
$msg_url   = set_url_var($msg_url, 'ent_id', 0);
$dwnld_url = '../src/download.php?'. $QUERY_STRING . '&amp;absolute_dl=true';

$body = mime_fetch_body($imapConnection, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);

if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
    function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
    if (mb_detect_encoding($body) != 'ASCII') {
        $body = $languages[$squirrelmail_language]['XTRA_CODE']('decode', $body);
    }
}

if ($type1 == 'html' || (isset($override_type1) &&  $override_type1 == 'html')) {
    $body = MagicHTML( $body, $passed_id, $message, $mailbox);
} else {
    translateText($body, $wrap_at, $charset);
}

displayPageHeader($color, 'None');

echo '<BR><TABLE WIDTH="100%" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR="' . $color[0] . '">' .
     '<B><CENTER>' .
     _("Viewing a text attachment") . ' - ' .
     '<a href="'.$msg_url.'">'. _("View message") . '</a>' .
     '</b></td><tr><tr><td><CENTER><A HREF="' . $dwnld_url . '">' .
     _("Download this as a file") .
     '</A></CENTER><BR>' .
     '</CENTER></B>' .
     '</TD></TR></TABLE>' .
     '<TABLE WIDTH="98%" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR="' . $color[0] . '">' .
     '<TR><TD BGCOLOR="' . $color[4] . '"><TT>' .
     $body . '</TT></TD></TR></TABLE>' .
     '</body></html>';
?>
