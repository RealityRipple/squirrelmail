<?php

/**
 * view_header.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is the code to view the message header.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/url_parser.php');

function parse_viewheader($imapConnection,$id, $passed_ent_id) {
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
        if(strtolower($f) != 'message-id:') {
		parseEmail($s);
	}
        if ($f) {
            $header_output[] = array($f,$s);
        }
    }
    sqimap_logout($imapConnection);
    return $header_output;
}

function view_header($header, $mailbox, $color) {
    $ret_addr = SM_PATH . 'src/read_body.php?'.$_SERVER['QUERY_STRING'];

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

/* get global vars */
$passed_id = $_GET['passed_id'];
$username = $_SESSION['username'];
$key = $_COOKIE['key'];
$delimiter = $_SESSION['delimiter'];
$onetimepad = $_SESSION['onetimepad'];

if (!isset($_GET['passed_ent_id'])) {
  $passed_ent_id = '';
} else {
    $passed_ent_id = $_GET['passed_ent_id'];
}

$mailbox = urldecode($_GET['mailbox']);

$imapConnection = sqimap_login($username, $key, $imapServerAddress, 
                               $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);

$header = parse_viewheader($imapConnection,$passed_id, $passed_ent_id); 
view_header($header, $mailbox, $color);

?>
