<?php

/**
 * imap.php
 *
 * This just includes the different sections of the imap functions.
 * They have been organized into these sections for simplicity sake.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage imap
 */

/** Includes */
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/imap_messages.php');
require_once(SM_PATH . 'functions/imap_general.php');

/** This is here for bc */
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/mailbox_display.php');
require_once(SM_PATH . 'functions/mime.php');

?>