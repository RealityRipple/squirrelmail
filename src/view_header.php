<?php

/**
 * view_header.php
 *
 * This is the code to view the message header.
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
        $line = htmlspecialchars($read[$i]);
        switch (true) {
            case (eregi("^&gt;", $line)):
                $second[$i] = $line;
                $first[$i] = '&nbsp;';
                $cnum++;
                break;
            case (eregi("^[ |\t]", $line)):
                $second[$i] = $line;
                $first[$i] = '';
                break;
            case (eregi("^([^:]+):(.+)", $line, $regs)):
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

/**
 * Temporary test function to process template vars with formatting.
 * I use it for viewing the message_header (view_header.php) with
 * a sort of template.
 * @param mixed $var
 * @param mixed $format_ar
 * @since 1.3.0
 * @todo if function is temporary, then why it is used.
 * @deprecated
 */
function echo_template_var($var, $format_ar = array() ) {
    $frm_last = count($format_ar) -1;

    if (isset($format_ar[0])) echo $format_ar[0];
    $i = 1;

    switch (true) {
    case (is_string($var)):
        echo $var;
        break;
    case (is_array($var)):
        $frm_a = array_slice($format_ar,1,$frm_last-1);
        foreach ($var as $a_el) {
            if (is_array($a_el)) {
                echo_template_var($a_el,$frm_a);
            } else {
                echo $a_el;
                if (isset($format_ar[$i])) {
                    echo $format_ar[$i];
                }
                $i++;
            }
        }
        break;
    default:
        break;
    }
    if (isset($format_ar[$frm_last]) && $frm_last>$i ) {
        echo $format_ar[$frm_last];
    }
}

function view_header($header, $mailbox, $color) {
    sqgetGlobalVar('QUERY_STRING', $queryStr, SQ_SERVER);
    $ret_addr = SM_PATH . 'src/read_body.php?'.$queryStr;

    displayPageHeader($color, $mailbox);

    echo '<br />' .
         '<table width="100%" cellpadding="2" cellspacing="0" border="0" '.
            'align="center">' . "\n" .
         '<tr><td bgcolor="'.$color[9].'" width="100%" align="center"><b>'.
         _("Viewing Full Header") . '</b> - '.
         '<a href="';
    echo_template_var($ret_addr);
    echo '">' ._("View message") . "</a></td></tr></table>\n";

    echo_template_var($header,
        array(
            '<table width="99%" cellpadding="2" cellspacing="0" border="0" '.
                "align=center>\n".'<tr><td>',
            '<tt style="white-space: nowrap;"><b>',
            '</b>',
            '</tt>',
            '</td></tr></table>'."\n"
         )
    );
    echo '</body></html>';
}

/* get global vars */
if ( sqgetGlobalVar('passed_id', $temp, SQ_GET) ) {
  $passed_id = (int) $temp;
}
if ( sqgetGlobalVar('mailbox', $temp, SQ_GET) ) {
  $mailbox = $temp;
}
if ( !sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET) ) {
  $passed_ent_id = '';
}
sqgetGlobalVar('key',        $key,          SQ_COOKIE);
sqgetGlobalVar('username',   $username,     SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad,   SQ_SESSION);
sqgetGlobalVar('delimiter',  $delimiter,    SQ_SESSION);

$imapConnection = sqimap_login($username, $key, $imapServerAddress,
                               $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);

$header = parse_viewheader($imapConnection,$passed_id, $passed_ent_id);
view_header($header, $mailbox, $color);

?>