<?php
/**
 * read_message_body.tpl
 *
 * Template for displaying the message body.
 * 
 * The following variables are available in this template:
 *      $message_body - Entire message body, scrubbed, formatted, etc.
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
if (!empty($plugin_output['read_body_top'])) echo $plugin_output['read_body_top']; 
?>
<div class="readBody">
<table cellspacing="0" class="table2">
 <tr>
  <td> 
   <?php echo $message_body; ?>
  </td>
 </tr>
</table>
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
</div>
