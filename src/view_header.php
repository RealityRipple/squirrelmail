<?php

/**
 * view_header.php
 *
 * This is the code to view the message header.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the view_header page */
define('PAGE_NAME', 'view_header');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/url_parser.php');

function parse_viewheader($imapConnection,$id, $passed_ent_id) {

    $header_output = array();
    $second = array();
    $first = array();

    if (!$passed_ent_id) {
        $read=sqimap_run_command ($imapConnection, "FETCH $id BODY[HEADER]",
                              true, $a, $b, TRUE);
    } else {
        $query = "FETCH $id BODY[".$passed_ent_id.'.HEADER]';
        $read=sqimap_run_command ($imapConnection, $query,
                              true, $a, $b, TRUE);
    }
    $cnum = 0;
    for ($i=1; $i < count($read); $i++) {
        $line = sm_encode_html_special_chars($read[$i]);
        switch (true) {
            case (preg_match('/^&gt;/i', $line)):
                $second[$i] = $line;
                $first[$i] = '&nbsp;';
                $cnum++;
                break;
// FIXME: is the pipe character below a mistake?  I think the original author might have thought it carried special meaning in the character class, which it does not... but then again, I am not currently trying to understand what this code actually does
            case (preg_match('/^[ |\t]/', $line)):
                $second[$i] = $line;
                $first[$i] = '';
                break;
            case (preg_match('/^([^:]+):(.+)/', $line, $regs)):
                $first[$i] = $regs[1] . ':';
                $second[$i] = $regs[2];
                $cnum++;
                break;
            default:
                $second[$i] = trim($line);
                $first[$i] = '';
                break;
        }
    }
    for ($i=0; $i < count($second); $i = $j) {
        $f = (isset($first[$i]) ? $first[$i] : '');
        $s = (isset($second[$i]) ? nl2br($second[$i]) : '');
        $j = $i + 1;
        while (($first[$j] == '') && ($j < count($first))) {
            $s .= '&nbsp;&nbsp;&nbsp;&nbsp;' . nl2br($second[$j]);
            $j++;
        }
        $lowf=strtolower($f);
        /* do not mark these headers as emailaddresses */
        if($lowf != 'message-id:' && $lowf != 'in-reply-to:' && $lowf != 'references:') {
            parseEmail($s);
        }
        if ($f) {
            $header_output[] = array($f,$s);
        }
    }
    sqimap_logout($imapConnection);
    return $header_output;
}

/* get global vars */
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
if ( sqgetGlobalVar('mailbox', $temp, SQ_GET) ) {
  $mailbox = $temp;
}
if ( !sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET) ) {
  $passed_ent_id = '';
}
sqgetGlobalVar('delimiter',  $delimiter,    SQ_SESSION);

global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress,
                               $imapPort, 0, $imap_stream_options);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);
$header = parse_viewheader($imapConnection,$passed_id, $passed_ent_id);

$aTemplateHeaders = array();
foreach ($header as $h) {
    $aTemplateHeaders[] = array (
                                    'Header' => $h[0],
                                    'Value' => $h[1]
                                );
}

sqgetGlobalVar('QUERY_STRING', $queryStr, SQ_SERVER);
$ret_addr = SM_PATH . 'src/read_body.php?'.$queryStr;

displayPageHeader( $color, $mailbox );

$oTemplate->assign('view_message_href', $ret_addr);
$oTemplate->assign('headers', $aTemplateHeaders);

$oTemplate->display('view_header.tpl');

$oTemplate->display('footer.tpl');
