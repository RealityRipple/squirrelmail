<?php

/**
 * view_header.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is the code to view the message header.
 *
 * $Id$
 */
require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/html.php');
require_once('../functions/url_parser.php');

function  parse_viewheader($imapConnection,$id, $passed_ent_id) {
   global $uid_support;

   $header_full = array();
   if (!$passed_ent_id) {
       $read=sqimap_run_command ($imapConnection, "FETCH $id BODY[HEADER]", 
                              true, $a, $b, $uid_support);
   } else {
       $query = "FETCH $id BODY[".$passed_ent_id.'.HEADER]';
       $read=sqimap_run_command ($imapConnection, $query, 
                              true, $a, $b, $uid_support);
   }    
    $cnum = 0;
    for ($i=1; $i < count($read); $i++) {
        $line = htmlspecialchars($read[$i]);
        if (eregi("^&gt;", $line)) {
            $second[$i] = $line;
            $first[$i] = '&nbsp;';
            $cnum++;
        } else if (eregi("^[ |\t]", $line)) {
            $second[$i] = $line;
            $first[$i] = '';
        } else if (eregi("^([^:]+):(.+)", $line, $regs)) {
            $first[$i] = $regs[1] . ':';
            $second[$i] = $regs[2];
            $cnum++;
        } else {
            $second[$i] = trim($line);
            $first[$i] = '';
        }
    }
    for ($i=0; $i < count($second); $i = $j) {
        if (isset($first[$i])) {
            $f = $first[$i];
        }
        if (isset($second[$i])) {
            $s = nl2br($second[$i]);
        }
        $j = $i + 1;
        while (($first[$j] == '') && ($j < count($first))) {
            $s .= '&nbsp;&nbsp;&nbsp;&nbsp;' . nl2br($second[$j]);
            $j++;
        }
        parseEmail($s);
        if (isset($f)) {
            $header_output[] = array($f,$s);
        }
    }
    sqimap_logout($imapConnection);
    return $header_output;
}

function view_header($header, $mailbox, $color) {
    global $QUERY_STRING, $base_uri;

    $ret_addr = $base_uri . 'src/read_body.php?'.$QUERY_STRING;

    displayPageHeader($color, $mailbox);

    echo '<BR>' .
         '<TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="0" BORDER="0"'.
         ' ALIGN="CENTER">' . "\n" .
         "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=\"100%\" ALIGN=\"CENTER\"><B>".
         _("Viewing Full Header") . '</B> - '.
         '<a href="'; 
    echo_template_var($ret_addr);
    echo '">' ._("View message") . "</a></b></td></tr></table>\n";

    echo_template_var($header, 
         array(
           "<table width='99%' cellpadding='2' cellspacing='0' border='0'".
             "align=center>\n".'<tr><td>',
           '<nobr><tt><b>',
           '</b>',
           '</tt></nobr>',
           '</td></tr></table>'."\n" 
         ) );
    echo '</body></html>';
}

if (!isset($passed_ent_id)) {
  $passed_ent_id = '';
}
$mailbox = decodeHeader($mailbox);

$imapConnection = sqimap_login($username, $key, $imapServerAddress, 
                               $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);

$header = parse_viewheader($imapConnection,$passed_id, $passed_ent_id); 
view_header($header, $mailbox, $color);

?>
