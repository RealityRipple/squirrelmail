<?php

/**
 * Message Details plugin - top frame with buttons
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * @author Marc Groot Koerkamp
 * @copyright 2002 Marc Groot Koerkamp, The Netherlands
 * @copyright 2002-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage message_details
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');
require(SM_PATH . 'functions/forms.php');

displayHtmlHeader( _("Message Details"),
             "<script type=\"text/javascript\">\n".
             "<!--\n".
             "function printPopup() {\n".
                "parent.frames[1].focus();\n".
                "parent.frames[1].print();\n".
             "}\n".
             "-->\n".
             "</script>\n", FALSE );

sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
if (!sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET))
    $passed_ent_id = 0;
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

echo "<body text=\"$color[8]\" bgcolor=\"$color[3]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
     '<div style="text-align: center;">' .
     addForm(SM_PATH . 'src/download.php', 'GET').
     addHidden('mailbox', $mailbox).
     addHidden('passed_id', $passed_id).
     addHidden('ent_id', $passed_ent_id).
     addHidden('absolute_dl', 'true').
     (checkForJavascript() ?
     '<input type="button" value="' . _("Print") . '" onclick="printPopup()" />&nbsp;&nbsp;'.
     '<input type="button" value="' . _("Close Window") . '" onclick="window.parent.close()" />&nbsp;&nbsp;' :'').
     addSubmit(_("Save Message")).
     '</form></div>'.
     '</body>'.
     "</html>\n";
