<?php

/**
 * view_html
 * Displays html message parts
 *
 * File is used to display html message parts. Usually inside iframe.
 * It should be called with passed_id, ent_id and mailbox options in
 * GET request. passed_ent_id and view_unsafe_images options are
 * optional. User must be authenticated ($key in cookie. $username and
 * $onetimepad in session).
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the view_html page */
define('PAGE_NAME', 'view_html');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/** SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/date.php');

/** Get globals */
sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);

// TODO: add required var checks here.

global $imap_stream_options; // in case not defined in config
$imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
$mbx_response = sqimap_mailbox_select($imap_stream, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
if (!is_object($message)) {
    $message = sqimap_get_message($imap_stream, $passed_id, $mailbox);
}
$message_ent = $message->getEntity($ent_id);
if ($passed_ent_id) {
    $message = $message->getEntity($passed_ent_id);
}
$header   = $message_ent->header;
$type0    = $header->type0;
$type1    = $header->type1;
$charset  = $header->getParameter('charset');
$encoding = strtolower($header->encoding);

$body = mime_fetch_body($imap_stream, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);
do_hook('message_body', $body);

/**
 * TODO: check if xtra_code is needed.
if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
    function_exists($languages[$squirrelmail_language]['XTRA_CODE'].'_decode')) {
    if (mb_detect_encoding($body) != 'ASCII') {
        $body = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode', $body);
    }
}
*/

/** TODO: provide reduced version of MagicHTML() */
$body = MagicHTML( $body, $passed_id, $message, $mailbox);

/** TODO: charset might be part of html code. */
header('Content-Type: text/html; charset=' . $charset);
echo $body;
