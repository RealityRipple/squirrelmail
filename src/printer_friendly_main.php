<?php

  /**
   **  printer_friendly_main.php
   **
   **  Copyright (c) 1999-2001 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  $Id$
   **/

    require_once('../src/validate.php');
    require_once('../functions/page_header.php');

    displayHtmlHeader( _("Printer Friendly"), '', FALSE );

    echo "<frameset rows=\"50, *\" noresize border=\"0\">\n".
         "<frame src=\"printer_friendly_top.php\" name=\"top_frame\" scrolling=\"off\">".
         '<frame src="printer_friendly_bottom.php?passed_ent_id=';
    echo $passed_ent_id . '&mailbox=' . urlencode($mailbox) .
         '&passed_id=' . $passed_id;
    echo "\" name=\"bottom_frame\">".
         "</frameset>\n".
         "</html>\n";

?>
