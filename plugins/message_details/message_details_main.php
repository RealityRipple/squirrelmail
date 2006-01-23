<?php

/**
 * Message Details plugin - main frame
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

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');

displayHtmlHeader( _("Message Details"), '', FALSE );

sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET);

echo "<frameset rows=\"60, *\" >\n";
echo '<frame src="message_details_top.php?mailbox=' . urlencode($mailbox) .'&amp;passed_id=' . "$passed_id".
    '" name="top_frame" scrolling="no" noresize="noresize" frameborder="0" />';
echo '<frame src="message_details_bottom.php?mailbox=' . urlencode($mailbox) .
    '&amp;get_message_details=yes&amp;passed_id=' . "$passed_id" .
    '" name="bottom_frame" frameborder="0" />';
echo  '</frameset>'."\n"."</html>\n";
?>