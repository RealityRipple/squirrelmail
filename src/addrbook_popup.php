<?php

/**
 * addrbook_popup.php
 *
 * Frameset for the JavaScript version of the address book.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

displayHtmlHeader($org_title .': '. _("Addresses"), '', false, true);

$oTemplate->display('addressbook_popup.tpl');


