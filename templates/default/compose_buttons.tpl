<?php
/**
 * compose_buttons.tpl
 *
 * Description
 * 
 * The following variables are available in this template:
 *    $accesskey_compose_priority    - The access key to be use for the Priority list
 *    $accesskey_compose_on_read     - The access key to be use for the On Read checkbox
 *    $accesskey_compose_on_delivery - The access key to be use for the On Delivery checkbox
 *    $accesskey_compose_signature   - The access key to be use for the Signature button
 *    $accesskey_compose_save_draft  - The access key to be use for the Save Draft button
 *    $accesskey_compose_send        - The access key to be use for the Send button
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
   <select name="mailprio" id="mailprio"<?php if ($accesskey_compose_priority != 'NONE') echo ' accesskey="' . $accesskey_compose_priority . '"'; ?>>
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
    <input type="checkbox" name="request_mdn" id="request_mdn" value="1" <?php if ($read_receipt) echo ' checked="checked"'; ?> <?php if ($accesskey_compose_on_read != 'NONE') echo 'accesskey="' . $accesskey_compose_on_read . '" '; ?>/><label for="request_mdn"><?php echo _("On Read"); ?></label>
    &nbsp;
    <input type="checkbox" name="request_dr" id="request_dr" value="1" <?php if ($delivery_receipt) echo ' checked="checked"'; ?> <?php if ($accesskey_compose_on_delivery != 'NONE') echo 'accesskey="' . $accesskey_compose_on_delivery . '" '; ?>/><label for="request_dr"><?php echo _("On Delivery"); ?></label>
  </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td colspan="2" class="buttons">
   <input type="submit" name="sigappend" <?php if ($accesskey_compose_signature != 'NONE') echo 'accesskey="' . $accesskey_compose_signature . '" '; ?>value="<?php echo _("Signature"); ?>" />&nbsp;
   <?php echo $address_book_button; ?>&nbsp;
   <?php
    if ($drafts_enabled) {
        ?>
   <input type="submit" name="draft" <?php if ($accesskey_compose_save_draft != 'NONE') echo 'accesskey="' . $accesskey_compose_save_draft . '" '; ?>value="<?php echo _("Save Draft"); ?>" />&nbsp;
        <?php
    }
   ?>
   <input type="submit" <?php if (!unique_widget_name('send', TRUE) && $accesskey_compose_send != 'NONE') echo 'accesskey="' . $accesskey_compose_send . '" '; ?>name="<?php echo unique_widget_name('send'); ?>" value="<?php echo _("Send"); ?>" />&nbsp;
   <?php if (!empty($plugin_output['compose_button_row'])) echo $plugin_output['compose_button_row']; ?>
  </td>
 </tr>
</table>
</div>
