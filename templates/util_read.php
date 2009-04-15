<?php
/**
 * util_read.php
 *
 * Utility file containing functions related to reading messages
 * 
 * @copyright &copy; 1999-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/**
 * Return a string representing the priority of a message
 */
function priorityStr($p) {
    return htmlspecialchars(getPriorityStr($p));
}

?>