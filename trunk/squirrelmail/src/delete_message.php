<?php

/**
 * delete_message.php
 *
 * Deletes a meesage from the IMAP server
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/display_messages.php');

global $color;
error_box('delete_message.php script is obsolete since 1.5.1.',$color);

?>
</body></html>