<?php
/**
 * search_result_empty.tpl
 *
 * Template displayed when no search results are found.
 * 
 * There are no variables given to this template.
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
?>
<div class="search">
<h2><?php echo _("No Messages Found"); ?></h2>
</div>