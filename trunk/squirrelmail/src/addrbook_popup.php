<?php

/**
 * addrbook_popup.php
 *
 * Frameset for the JavaScript version of the address book.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/** This is the addrbook_popup page */
define('PAGE_NAME', 'addrbook_popup');

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

displayHtmlHeader($org_title .': '. _("Addresses"), '', false, true);

$oTemplate->display('addressbook_popup.tpl');


