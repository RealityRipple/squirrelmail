<?php

/**
 * printer_friendly_main.php
 *
 * Copyright (c) 1999-2001 The SquirrelMail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below looks.                        ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

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
