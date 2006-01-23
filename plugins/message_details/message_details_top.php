<?php

/**
 * Message Details plugin - top frame with buttons
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * @author Marc Groot Koerkamp
 * @copyright &copy; 2002 Marc Groot Koerkamp, The Netherlands
 * @copyright &copy; 2002-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage message_details
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/forms.php');

displayHtmlHeader( _("Message Details"),
             "<script language=\"javascript\" type=\"text/javascript\">\n".
             "<!--\n".
             "function printPopup() {\n".
                "parent.frames[1].focus();\n".
                "parent.frames[1].print();\n".
             "}\n".
             "-->\n".
             "</script>\n", FALSE );

sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

echo "<body text=\"$color[8]\" bgcolor=\"$color[3]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
     '<center>' .
     addForm(SM_PATH . 'src/download.php', 'GET').
     addHidden('mailbox', $mailbox).
     addHidden('passed_id', $passed_id).
     addHidden('ent_id', '0').
     addHidden('absolute_dl', 'true').
     '<input type="button" value="' . _("Print") . '" onclick="printPopup()" />&nbsp;&nbsp;'.
     '<input type="button" value="' . _("Close Window") . '" onclick="window.parent.close()" />&nbsp;&nbsp;'.
     addSubmit(_("Save Message")).
     '</form></center>'.
     '</body>'.
     "</html>\n";
?>