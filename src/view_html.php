<?php
/**
 * $Source$
 * Displays html message parts
 *
 * File is used to display html message parts. Usually inside iframe.
 * It should be called with passed_id, ent_id and mailbox options in 
 * GET request. passed_ent_id and view_unsafe_images options are 
 * optional. User must be authenticated ($key in cookie. $username and 
 * $onetimepad in session).
 * 
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * This file is part of SquirrelMail webmail interface.
 * 
 * SquirrelMail is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * SquirrelMail is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SquirrelMail; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id$
 * @package squirrelmail
 * @todo Integrate it in formatBody() function after template stuff is 
 *  in. original html display code has to be wrapped with iframe code. 
 *  View Unsafe Images (target=iframe frame) and Download this as a file 
 *  should be part of read_body page. Iframe code should be activated 
 *  only for show_html_default=true
 * @todo fix formating of read_body.php in order to get iframe width=100% 
 *  working.
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/** SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/imap.php');

/** Get globals */
sqgetGlobalVar('key',        $key,          SQ_COOKIE);
sqgetGlobalVar('username',   $username,     SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad,   SQ_SESSION);
sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
if (sqgetGlobalVar('passed_id', $temp, SQ_GET)) {
    $passed_id = (int) $temp;
}

global $view_unsafe_images;
if (sqgetGlobalVar('view_unsafe_images', $temp, SQ_GET)) {
    $view_unsafe_images = (bool) $temp;
} else {
    $view_unsafe_images = false;
}

// TODO: add required var checks here.

$imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imap_stream, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
if (!is_object($message)) {
    $message = sqimap_get_message($imap_stream, $passed_id, $mailbox);
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

$body = mime_fetch_body($imap_stream, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);

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
?>