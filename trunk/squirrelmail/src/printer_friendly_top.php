<?php

  /**
   **  printer_friendly_top.php
   **
   **  Copyright (c) 1999-2001 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  top frame of printer_friendly_main.php
   **  displays some javascript buttons for printing & closing
   **
   **  $Id$
   **/

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