<?php
/**
 * read_headers.tpl
 *
 * Template to display the envelope headers when viewing a message.
 * 
 * The following variables are available in this template:
 * 
 *    $headers_to_display - Array containing the list of all elements that need
 *                          to be displayed.  The index of each element is the
 *                          translated name of the field to be displayed.  The
 *                          value of each element is the value to be displayed
 *                          for that field.  Many values can be controled through
 *                          additional templates.
 * 
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
<div class="readHeaders">
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
<table cellspacing="0" class="table2">
 <?php
    foreach ($headers_to_display as $field_name=>$value) {
        if (empty($value)) {
            # Skip enpty headers
            continue;
        }
        ?>
 <tr class="field_<?php echo $field_name; ?>">
  <td class="fieldName">
   <?php echo $field_name; ?>:
  </td>
  <td class="fieldValue">
   <?php echo $value; ?>
  </td>
 </tr>
<?php
    }
    if (!empty($plugin_output['read_body_header'])) echo $plugin_output['read_body_header'];
?>
</table>
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
</div>
