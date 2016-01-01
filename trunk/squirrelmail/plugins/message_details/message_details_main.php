<?php

/**
 * Message Details plugin - main frame
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

displayHtmlHeader( _("Message Details"), '', FALSE );

sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
if (!sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET))
    $passed_ent_id = 0;

//FIXME: Don't echo HTML directly - need to "templatize" this
echo "<frameset rows=\"60, *\" >\n";
echo '<frame src="message_details_top.php?mailbox=' . urlencode($mailbox) .'&amp;passed_id=' . $passed_id 
    . '&amp;passed_ent_id=' . $passed_ent_id
    . '" name="top_frame" scrolling="no" noresize="noresize" frameborder="0" />';
echo '<frame src="message_details_bottom.php?mailbox=' . urlencode($mailbox) 
    . '&amp;get_message_details=yes&amp;passed_id=' . $passed_id 
    . '&amp;passed_ent_id=' . $passed_ent_id
    . '" name="bottom_frame" frameborder="0" />';
echo  '</frameset>'."\n"."</html>\n";
