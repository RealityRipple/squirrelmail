<?php
/**
 * compose_buttons.tpl
 *
 * Description
 * 
 * The following variables are available in this template:
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
<div class="compose">
<table cellspacing="0" class="table1">
 <?php
    # Priority setting
    if ($allow_priority) {
        ?>
 <tr>
  <td class="fieldName">
   <label for="mailprio"><?php echo _("Priority"); ?>:</label>
  </td>
  <td class="fieldValue">
   <select name="mailprio" id="mailprio">
    <?php
        foreach ($priority_list as $value=>$name) {
            echo '<option value="'.$value.'"'. ($value==$current_priority ? ' selected="selected"' : '') .'>'.$name.'</option>';
        }
    ?>
   </select>
  </td>
 </tr>
        <?php
    }

    # Notifications
    if ($notifications_enabled) {
        ?>
 <tr>
  <td class="fieldName">
   <?php echo _("Receipts"); ?>:
  </td>
  <td class="fieldValue">
    <input type="checkbox" name="request_mdn" id="request_mdn" value="1" <?php if ($read_receipt) echo ' checked="checked"'; ?> /><label for="request_mdn"><?php echo _("On Read"); ?></label>
    <br />
    <input type="checkbox" name="request_dr" id="request_dr" value="1" <?php if ($delivery_receipt) echo ' checked="checked"'; ?> /><label for="request_dr"><?php echo _("On Delivery"); ?></label>
  </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td colspan="2" class="buttons">
   <input type="submit" name="sigappend" value="<?php echo _("Signature"); ?>" />&nbsp;
   <?php echo $address_book_button; ?>&nbsp;
   <?php
    if ($drafts_enabled) {
        ?>
   <input type="submit" name="draft" value="<?php echo _("Save Draft"); ?>" />&nbsp;
        <?php
    }
   ?>
   <input type="submit" name="send" value="<?php echo _("Send"); ?>" />&nbsp;
   <?php echo @$plugin_output['compose_button_row']; ?>
  </td>
 </tr>
</table>
</div>