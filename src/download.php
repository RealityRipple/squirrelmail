<?php

/**
 * download.php
 *
 * Handles attachment downloads to the users computer.
 * Also allows displaying of attachments when possible.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the download page */
define('PAGE_NAME', 'download');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require(SM_PATH . 'functions/imap_general.php');
require(SM_PATH . 'functions/mailbox_display.php');
require(SM_PATH . 'functions/mime.php');

/**
 * If a message is viewed from the search page, $aMailbox[$passed_id]['MESSAGE_OBJECT']
 * is not initialized, which makes this page error out on line 65 with an 
 * undefined function.  We need to include some additional files in case the
 * object has not been initialized.
 * 
 * TODO: Determine why the object in question is not initialized when coming from
 *       a search page and correct.  Once that is done, we can remove these
 *       includes.
 */
require(SM_PATH . 'functions/imap_messages.php');
require(SM_PATH . 'functions/date.php');

header('Pragma: ');
header('Cache-Control: cache');

/* globals */
sqgetGlobalVar('mailbox_cache',$mailbox_cache,SQ_SESSION);
sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('absolute_dl',$absolute_dl,  SQ_GET);
sqgetGlobalVar('force_crlf', $force_crlf,   SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
if (!sqgetGlobalVar('account', $account, SQ_GET) ) {
    $account = 0;
}

global $default_charset;
set_my_charset();

/* end globals */

global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
$aMailbox = sqm_api_mailbox_select($imapConnection, $account, $mailbox,array(),array());

if (isset($aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT']) &&
    is_object($aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT']) ) {
    $message = $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'];
} else {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
    $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'] = $message;
}

$subject = $message->rfc822_header->subject;
if ($ent_id) {
    // replace message with message part, if message part is requested.
    $message = $message->getEntity($ent_id);
    $header = $message->header;

    if ($message->rfc822_header) {
       $subject = $message->rfc822_header->subject;
    } else {
       $header = $message->header;
    }
    $type0 = $header->type0;
    $type1 = $header->type1;
    $encoding = strtolower($header->encoding);
} else {
    /* raw message */
    $type0 = 'message';
    $type1 = 'rfc822';
    $encoding = '7bit';
    $header = $message->header;
}

/*
 * lets redefine message as this particular entity that we wish to display.
 * it should hold only the header for this entity.  We need to fetch the body
 * yet before we can display anything.
 */

if (isset($override_type0)) {
    $type0 = $override_type0;
}
if (isset($override_type1)) {
    $type1 = $override_type1;
}
$filename = '';
if (is_object($message->header->disposition)) {
    $filename = $header->disposition->getProperty('filename');
    if (!$filename) {
        $filename = $header->disposition->getProperty('name');
    }
    if (!$filename) {
        $filename = $header->getParameter('name');
    }
} else {
    $filename = $header->getParameter('name');
}

$filename = decodeHeader($filename,true,false);
$filename = charset_encode($filename,$default_charset,false);

// If name is not set, use subject of email
if (strlen($filename) < 1) {
    $filename = decodeHeader($subject, true, true);
    $filename = charset_encode($filename,$default_charset,false);
    if ($type1 == 'plain' && $type0 == 'text')
        $suffix = 'txt';
    else if ($type1 == 'richtext' && $type0 == 'text')
        $suffix = 'rtf';
    else if ($type1 == 'postscript' && $type0 == 'application')
        $suffix = 'ps';
    else if ($type1 == 'rfc822' && $type0 == 'message')
        $suffix = 'eml';
    else
        $suffix = $type1;

    if ($filename == '')
        $filename = 'untitled' . strip_tags($ent_id);
    $filename = $filename . '.' . $suffix;
}

/**
 * Update mailbox_cache and close session in order to prevent
 * script locking on larger downloads. SendDownloadHeaders() and 
 * mime_print_body_lines() don't write information to session.
 * mime_print_body_lines() call duration depends on size of 
 * attachment and script can cause interface lockups, if session 
 * is not closed.
 */
$mailbox_cache[$aMailbox['NAME']] = $aMailbox;
sqsession_register($mailbox_cache,'mailbox_cache');
session_write_close();

/*
 * Note:
 *    The following sections display the attachment in different
 *    ways depending on how they choose.  The first way will download
 *    under any circumstance.  This sets the Content-type to be
 *    applicatin/octet-stream, which should be interpreted by the
 *    browser as "download me".
 *      The second method (view) is used for images or other formats
 *    that should be able to be handled by the browser.  It will
 *    most likely display the attachment inline inside the browser.
 *      And finally, the third one will be used by default.  If it
 *    is displayable (text or html), it will load them up in a text
 *    viewer (built in to SquirrelMail).  Otherwise, it sets the
 *    content-type as application/octet-stream
 */
if (isset($absolute_dl) && $absolute_dl) {
    SendDownloadHeaders($type0, $type1, $filename, 1);
} else {
    SendDownloadHeaders($type0, $type1, $filename, 0);
}
/* be aware that any warning caused by download.php will corrupt the
 * attachment in case of ERROR reporting = E_ALL and the output is the screen */
mime_print_body_lines ($imapConnection, $passed_id, $ent_id, $encoding, 'php://stdout', $force_crlf);

