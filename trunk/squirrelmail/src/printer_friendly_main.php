<?php

/**
 * printer_friendly_main.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/page_header.php');

displayHtmlHeader( _("Printer Friendly"), '', FALSE );

echo "<frameset rows=\"60, *\" noresize border=\"0\">\n".
     "<frame src=\"printer_friendly_top.php\" name=\"top_frame\" scrolling=\"no\">".
     '<frame src="printer_friendly_bottom.php?passed_ent_id='.
     $passed_ent_id . '&amp;mailbox=' . urlencode($mailbox) .
     '&amp;passed_id=' . $passed_id .
     "\" name=\"bottom_frame\">".
     "</frameset>\n".
     "</html>\n";

?>
