<?php
/**  
 * Message Details plugin - main frame
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * @author Marc Groot Koerkamp
 * @copyright Copyright &copy; 2002 Marc Groot Koerkamp, The Netherlands
 * @copyright Copyright &copy; 2004 The SquirrelMail Project Team
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
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/strings.php');

displayHtmlHeader( _("Message Details"), '', FALSE );

sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET);

echo "<frameset rows=\"60, *\" noresize border=\"0\">\n";
echo '<frame src="message_details_top.php?mailbox=' . urlencode($mailbox) .'&passed_id=' . "$passed_id". '" name="top_frame" scrolling="off" />';
echo '<frame src="message_details_bottom.php?mailbox=' . urlencode($mailbox) .'&passed_id=' . "$passed_id" . '" name="bottom_frame" />';
echo  '</frameset>'."\n"."</html>\n";
?>