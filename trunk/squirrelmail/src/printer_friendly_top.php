<?php

/**
 * printer_friendly_top.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * top frame of printer_friendly_main.php
 * displays some javascript buttons for printing & closing
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
require_once('../functions/strings.php');
require_once('../config/config.php');
require_once('../src/load_prefs.php');
require_once('../functions/page_header.php');

    displayHtmlHeader( _("Printer Friendly"),
                 "<script language=\"javascript\">\n".
                 "<!--\n".
                 "function printPopup() {\n".
                    "parent.frames[1].focus();\n".
                    "parent.frames[1].print();\n".
                 "}\n".
                 "-->\n".
                 "</script>\n", FALSE );


    echo "<body text=\"$color[8]\" bgcolor=\"$color[3]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
         //'<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0"><tr><td valign="middle" align="center">'.
         '<center><b>'.
         '<form>'.
         '<input type="button" value="' . _("Print") . '" onClick="printPopup()"> '.
         '<input type="button" value="' . _("Close Window") . '" onClick="window.parent.close()">'.
         '</form>'.
         '</b>'.
         //'</td></tr></table>'.
         '</body>'.
         "</html>\n";

?>
