<?php

/**
 * image.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file shows an attached image
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'src/validate.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'src/load_prefs.php');

displayPageHeader($color, 'None');

echo '<BR>' . 
    '<TABLE WIDTH="100%" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>' .
    "\n" .
    '<TR><TD BGCOLOR="' . $color[0] . '">' .
    '<B><CENTER>' .
    _("Viewing an image attachment") . " - ";

$msg_url = 'read_body.php?' . $QUERY_STRING;
$msg_url = set_url_var($msg_url, 'ent_id', 0);
echo '<a href="'.$msg_url.'">'. _("View message") . '</a>';


$DownloadLink = '../src/download.php?passed_id=' . $passed_id .
               '&amp;mailbox=' . urlencode($mailbox) . 
               '&amp;ent_id=' . $ent_id . '&amp;absolute_dl=true';

echo '</b></td></tr>' . "\n" .
    '<tr><td align=center><A HREF="' . $DownloadLink . '">' .
    _("Download this as a file") .
    '</A></B><BR>&nbsp;' . "\n" .
    '</TD></TR></TABLE>' . "\n" .

    '<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>' . "\n" .
    '<TR><TD BGCOLOR="' . $color[4] . '">' .
    '<img src="' . $DownloadLink . '">' .

    '</TD></TR></TABLE>' . "\n";
    '</body></html>' . "\n";

?>
