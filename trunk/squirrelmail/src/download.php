<?php

/**
 * download.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handles attachment downloads to the users computer.
 * Also allows displaying of attachments when possible.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'src/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');

header('Pragma: ');
header('Cache-Control: cache');

function get_extract_to_target_list($imapConnection) {
    $boxes = sqimap_mailbox_list($imapConnection);
    for ($i = 0; $i < count($boxes); $i++) {  
        if (!in_array('noselect', $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            if ( $box2 == 'INBOX' ) {
                $box2 = _("INBOX");
            }
            echo "<option value=\"$box\">$box2</option>\n";
        }
    }
}
$mailbox = decodeHeader($mailbox);

global $messages, $uid_support;

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response =  sqimap_mailbox_select($imapConnection, $mailbox);
if (!isset($passed_ent_id)) {
   $passed_ent_id = '';
}

$message = &$messages[$mbx_response['UIDVALIDITY']]["$passed_id"];
if (!is_object($message)) {
    $message = sqimap_get_message($imapConnection,$passed_id, $mailbox);
}
$subject = $message->rfc822_header->subject;
$message = &$message->getEntity($ent_id);
$header = $message->header;
if ($message->rfc822_header) {
   $subject = $message->rfc822_header->subject;
   $charset = $header->content_type->properties['charset'];
} else {
   $header = $message->header;
   $charset = $header->getParameter('charset');
}
$type0 = $header->type0;
$type1 = $header->type1;
$encoding = strtolower($header->encoding);

/*
$extracted = false;
if (isset($extract_message) && $extract_message) {
  $cmd = "FETCH $passed_id BODY[$passed_ent_id]";
  $read = sqimap_run_command ($imapConnection, $cmd, true, $response, $message, $uid_support);
  $cnt = count($read);
  $body = '';
  $length = 0;
  for ($i=1;$i<$cnt;$i++) {
      $length = $length + strlen($read[$i]);
      $body .= $read[$i];
  }
  if (isset($targetMailbox) && $length>0) {
      sqimap_append ($imapConnection, $targetMailbox, $length);
      fputs($imapConnection,$body);
      sqimap_append_done ($imapConnection);
      $extracted = true;
  }
}   


*/
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
    $filename = decodeHeader($header->disposition->getProperty('filename'));
    if (!$filename) {
	$filename = decodeHeader($header->disposition->getProperty('name'));
    }
}
if (strlen($filename) < 1) {
    if ($type1 == 'plain' && $type0 == 'text') {
        $suffix = 'txt';
	$filename = $subject . '.txt';
    } else if ($type1 == 'richtext' && $type0 == 'text') {
        $suffix = 'rtf';
	$filename = $subject . '.rtf';
    } else if ($type1 == 'postscript' && $type0 == 'application') {
        $suffix = 'ps';
	$filename = $subject . '.ps';
    } else if ($type1 == 'rfc822' && $type0 == 'message') {
        $suffix = 'eml';
	$filename = $subject . '.msg';
    } else {
        $suffix = $type1;
    }

    if (strlen($filename) < 1) {
       $filename = "untitled$ent_id.$suffix";
    } else {
       $filename = "$filename.$suffix";
    }
}

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
 *    viewer (built in to squirrelmail).  Otherwise, it sets the
 *    content-type as application/octet-stream
 */
if (isset($absolute_dl) && $absolute_dl == 'true') {
    DumpHeaders($type0, $type1, $filename, 1);
} else {
    DumpHeaders($type0, $type1, $filename, 0);
}
/* be aware that any warning caused by download.php will corrupt the
 * attachment in case of ERROR reporting = E_ALL and the output is the screen */
mime_print_body_lines ($imapConnection, $passed_id, $ent_id, $encoding);

/*
 * This function is verified to work with Netscape and the *very latest*
 * version of IE.  I don't know if it works with Opera, but it should now.
 */
function DumpHeaders($type0, $type1, $filename, $force) {
    global $HTTP_USER_AGENT, $languages, $squirrelmail_language;
    $isIE = 0;

    if (strstr($HTTP_USER_AGENT, 'compatible; MSIE ') !== false &&
        strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE = 1;
    }

    if (strstr($HTTP_USER_AGENT, 'compatible; MSIE 6') !== false &&
        strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE6 = 1;
    }

    if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        $filename = 
            $languages[$squirrelmail_language]['XTRA_CODE']('downloadfilename', $filename, $HTTP_USER_AGENT);
    } else {
       $filename = ereg_replace('[^-a-zA-Z0-9\.]', '_', $filename);
    }

    // A Pox on Microsoft and it's Office!
    if (! $force) {
        // Try to show in browser window
        header("Content-Disposition: inline; filename=\"$filename\"");
        header("Content-Type: $type0/$type1; name=\"$filename\"");
    } else {
        // Try to pop up the "save as" box
        // IE makes this hard.  It pops up 2 save boxes, or none.
        // http://support.microsoft.com/support/kb/articles/Q238/5/88.ASP
        // But, accordint to Microsoft, it is "RFC compliant but doesn't
        // take into account some deviations that allowed within the
        // specification."  Doesn't that mean RFC non-compliant?
        // http://support.microsoft.com/support/kb/articles/Q258/4/52.ASP
        //
        // The best thing you can do for IE is to upgrade to the latest
        // version
        if ($isIE && !isset($isIE6)) {
            // http://support.microsoft.com/support/kb/articles/Q182/3/15.asp
            // Do not have quotes around filename, but that applied to
            // "attachment"... does it apply to inline too?
            //
            // This combination seems to work mostly.  IE 5.5 SP 1 has
            // known issues (see the Microsoft Knowledge Base)
            header("Content-Disposition: inline; filename=$filename");

            // This works for most types, but doesn't work with Word files
            header("Content-Type: application/download; name=\"$filename\"");

            // These are spares, just in case.  :-)
            //header("Content-Type: $type0/$type1; name=\"$filename\"");
            //header("Content-Type: application/x-msdownload; name=\"$filename\"");
            //header("Content-Type: application/octet-stream; name=\"$filename\"");
        } else {
            header("Content-Disposition: attachment; filename=\"$filename\"");
            // application/octet-stream forces download for Netscape
            header("Content-Type: application/octet-stream; name=\"$filename\"");
        }
    }
}
?>
