<?php

/**
 * view_text.php -- Displays the main frameset
 *
 * Copyright (c) 1999-2002 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Who knows what this file does. However PUT IT HERE DID NOT PUT
 * A SINGLE FREAKING COMMENT IN! Whoever is responsible for this,
 * be very ashamed.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/html.php');
   
$mailbox = urldecode($_GET['mailbox']);
if (!isset($_GET['passed_ent_id'])) {
    $passed_ent_id = '';
} else {
    $passed_ent_id = $_GET['passed_ent_id'];
}
$passed_id = $_GET['passed_id'];
if (isset($_GET['ent_id'])) {
	$ent_id = $_GET['ent_id'];
} else {
	$ent_id = '';
}

$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];
$QUERY_STRING = $_SERVER['QUERY_STRING'];
sqextractGlobalVar('messages');

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response =  sqimap_mailbox_select($imapConnection, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']]["$passed_id"];
$message_ent = &$message->getEntity($ent_id);
if ($passed_ent_id) {
    $message = &$message->getEntity($passed_ent_id);
}
   
$header = $message_ent->header;
$charset = $header->getParameter('charset');
$type0 = $header->type0;
$type1 = $header->type1;
$encoding = strtolower($header->encoding);

$msg_url = 'read_body.php?' . $QUERY_STRING;
$msg_url = set_url_var($msg_url, 'ent_id', 0);

$body = mime_fetch_body($imapConnection, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);

displayPageHeader($color, 'None');

echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
     "<B><CENTER>".
     _("Viewing a text attachment") . " - ";
echo '<a href="'.$msg_url.'">'. _("View message") . '</a>';

$dwnld_url = '../src/download.php?'. $QUERY_STRING.'&amp;absolute_dl=true';
echo '</b></td><tr><tr><td><CENTER><A HREF="'.$dwnld_url. '">'.
     _("Download this as a file").
     "</A></CENTER><BR>".
     "</CENTER></B>".
     "</TD></TR></TABLE>".
     "<TABLE WIDTH=\"98%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
     "<TR><TD BGCOLOR=\"$color[4]\"><TT>";
if ($type1 == 'html' || (isset($override_type1) &&  $override_type1 == 'html')) {
    $body = MagicHTML( $body, $passed_id, $message, $mailbox);
} else {
    translateText($body, $wrap_at, $charset);
}
echo $body . "</TT></TD></TR></TABLE>";

?>
