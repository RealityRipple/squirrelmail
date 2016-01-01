<?php

/**
 * view_text.php -- View a text attachment
 *
 * Used by attachment_common code.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the view_text page */
define('PAGE_NAME', 'view_text');

/** SquirrelMail required files. */
include('../include/init.php');
include(SM_PATH . 'functions/imap_general.php');
include(SM_PATH . 'functions/imap_messages.php');
include(SM_PATH . 'functions/mime.php');
include(SM_PATH . 'functions/date.php');
include(SM_PATH . 'functions/url_parser.php');

sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);

global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
if (!is_object($message)) {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
}
$message_ent = $message->getEntity($ent_id);
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
$dwnld_url = '../src/download.php?' . $QUERY_STRING . '&amp;absolute_dl=true';
$unsafe_url = 'view_text.php?' . $QUERY_STRING;
$unsafe_url = set_url_var($unsafe_url, 'view_unsafe_images', 1);


$body = mime_fetch_body($imapConnection, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);
do_hook('message_body', $body);

if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
    function_exists($languages[$squirrelmail_language]['XTRA_CODE'].'_decode')) {
    if (mb_detect_encoding($body) != 'ASCII') {
        $body = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode', $body);
    }
}

if ($type1 == 'html' || (isset($override_type1) &&  $override_type1 == 'html')) {
    $ishtml = TRUE;
    // html attachment with character set information
    if (! empty($charset)) {
        $body = charset_decode($charset,$body,false,true);
    }
    $body = MagicHTML( $body, $passed_id, $message, $mailbox);
} else {
    $ishtml = FALSE;
    translateText($body, $wrap_at, $charset);
}

displayPageHeader($color);

$oTemplate->assign('view_message_href', $msg_url);
$oTemplate->assign('download_href', $dwnld_url);
$oTemplate->assign('view_unsafe_image_href', $ishtml ? $unsafe_url : '');
$oTemplate->assign('body', $body);

$oTemplate->display('view_text.tpl');

$oTemplate->display('footer.tpl');
