<?php
/**
 * compose_attachments.tpl
 *
 * Description
 * 
 * The following variables are available in this template:
 *    $accesskey_compose_attach_browse - The access key to be used for the Browse button
 *    $accesskey_compose_attach        - The access key to be used for the Attach button
 *    $accesskey_compose_delete_attach - The access key to be used for the Delete Attachments button
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div class="compose">
<table cellspacing="0" class="table1" id="attachment_table">
 <tr class="header">
  <td class="fieldName" style="width: 1%; white-space: nowrap;">
   <?php echo _("New attachment");?>:
  </td>
  <td class="fieldValue">
   <?php
    if($max_file_size != -1) {
        echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_file_size . '" />';
    }
   ?>
   <input type="file" name="attachfile" size="48" <?php if ($accesskey_compose_attach_browse != 'NONE') echo 'accesskey="' . $accesskey_compose_attach_browse . '" '; ?>/>
   &nbsp;
   <input type="submit" name="attach" <?php if ($accesskey_compose_attach != 'NONE') echo 'accesskey="' . $accesskey_compose_attach . '" '; ?>value="<?php echo _("Attach"); ?>" />
   &nbsp;
   <?php
    if($max_file_size != -1) {
       echo '(' . _("Max.") . ' ' . humanReadableSize($max_file_size) . ')';
    }
    if (!empty($plugin_output['add_attachment_notes'])) echo $plugin_output['add_attachment_notes'];
   ?>
  </td>
 </tr>
 <?php
    if (!empty($plugin_output['attachment_inputs'])) echo $plugin_output['attachment_inputs'];
    $attachment_count = 1;
    foreach ($attachments as $attach) {
        ?>
 <tr class="attachment">
  <td class="fieldName" style="width: 1%">
   <input type="checkbox" name="delete[]" id="delete<?php echo $attachment_count; ?>" accesskey="<?php echo ($attachment_count % 10); ?>" value="<?php echo $attach['Key']; ?>" />
  </td>
  <td class="fieldValue"><label for="delete<?php echo $attachment_count; ?>">
   <?php echo $attach['FileName']; ?> - <?php echo $attach['ContentType']; ?> (<?php echo humanReadableSize($attach['Size']); ?>)
  </label></td>
 </tr>
        <?php
        $attachment_count++;
    }
    
    if (count($attachments) > 0) {
        ?>
 <tr class="header">
  <td colspan="2">
   <input type="submit" name="do_delete" <?php if ($accesskey_compose_delete_attach != 'NONE') echo 'accesskey="' . $accesskey_compose_delete_attach . '" '; ?>value="<?php echo _("Delete Selected Attachments"); ?>" />
  </td>
 </tr>
        <?php
    }
 ?>
</table>
</div>
