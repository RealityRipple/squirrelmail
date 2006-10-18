<?php

/**
 * printer_friendly top frame
 *
 * top frame of printer_friendly_main.php
 * displays some javascript buttons for printing & closing
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

displayHtmlHeader( _("Printer Friendly"));
$oErrorHandler->setDelayedErrors(true);

$oTemplate->display('printer_friendly_top.tpl');

$oTemplate->display('footer.tpl');
?>