<?php
/**
 * printer_friendly_main.tpl
 *
 * Display the entire printer friendly window.  By default, this uses frames when
 * javascript is available.
 * 
 * The following variables are available in this template:
 *      $printer_friendly_url - URL to display the printer-frinedly version of a message
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<frameset rows="60, *">
 <frame src="printer_friendly_top.php" name="top_frame" scrolling="no" noresize="noresize" frameborder="0" />
 <frame src="<?php echo $printer_friendly_url; ?>" name="bottom_frame" frameborder="0" />
</frameset>
</html>
