<?php

/**
 * printer_friendly_main.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');

displayHtmlHeader( _("Printer Friendly"), '', FALSE );

/* get those globals into gear */
$passed_ent_id = $_GET['passed_ent_id'];
$passed_id = $_GET['passed_id'];
$mailbox = $_GET['mailbox'];
/* end globals */

echo "<frameset rows=\"60, *\" noresize border=\"0\">\n".
     "<frame src=\"printer_friendly_top.php\" name=\"top_frame\" scrolling=\"no\">".
     '<frame src="printer_friendly_bottom.php?passed_ent_id='.
     $passed_ent_id . '&amp;mailbox=' . urlencode($mailbox) .
     '&amp;passed_id=' . $passed_id .
     "\" name=\"bottom_frame\">".
     "</frameset>\n".
     "</html>\n";

?>
