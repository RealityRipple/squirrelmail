<?php

/** Message Source  
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * Copyright (c) 2002 Marc Groot Koerkamp, The Netherlands
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 * 
 * $Id$
 * @package plugins
 * @subpackage message_details
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/forms.php');

displayHtmlHeader( _("Message Details"),
             "<script language=\"javascript\">\n".
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
     '<center><b>' .
     addForm(SM_PATH . 'src/download.php', 'GET').
     addHidden('mailbox', $mailbox).
     addHidden('passed_id', $passed_id).
     addHidden('ent_id', '0').
     addHidden('absolute_dl', 'true').
     '<input type="button" value="' . _("Print") . '" onClick="printPopup()" />&nbsp;&nbsp;'.
     '<input type="button" value="' . _("Close Window") . '" onClick="window.parent.close()" />&nbsp;&nbsp;'.
     addSubmit(_("Save Message")).
     '</form>'.
     '</b>'.
     '</body>'.
     "</html>\n";
?>
