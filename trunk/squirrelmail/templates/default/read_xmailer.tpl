<?php
/**
 * read_xmailer.tpl
 *
 * Template for setting the Mailer option
 * 
 * The following variables are available in this template:
 *      $xmailer - Mailer as set on the message
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
echo $xmailer;
?>