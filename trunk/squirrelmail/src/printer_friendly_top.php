<?php

/**
 * printer_friendly_top.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * top frame of printer_friendly_main.php
 * displays some javascript buttons for printing & closing
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');

displayHtmlHeader( _("Printer Friendly"),
             "<script language=\"javascript\" type=\"text/javascript\">\n".
             "<!--\n".
             "function printPopup() {\n".
                "parent.frames[1].focus();\n".
                "parent.frames[1].print();\n".
             "}\n".
             "-->\n".
             "</script>\n", FALSE );


echo "<body text='$color[8]' bgcolor='$color[3]' link='$color[7]' vlink='$color[7]' alink='$color[7]'>\n" .
     html_tag( 'div',
         '<b>'.
         '<form>'.
         '<input type="button" value="' . _("Print") . '" onclick="printPopup()" /> '.
         '<input type="button" value="' . _("Close") . '" onclick="window.parent.close()" />'.
         '</form>'.
         '</b>',
     'right' );
     '</body></html>'. "\n";

?>
