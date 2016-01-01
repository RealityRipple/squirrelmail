<?php
/**
 * compose_body.tpl
 *
 * Description
 * 
 * The following variables are available in this template:
 *    $accesskey_compose_body - The access key to use for the message body textarea
 *    $accesskey_compose_send - The access key to be use for the Send button
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
 <tr>
  <td style="text-align: center">
   <textarea name="body" id="body" rows="<?php echo $editor_height; ?>" cols="<?php echo $editor_width; ?>" <?php if ($accesskey_compose_body != 'NONE') echo 'accesskey="' . $accesskey_compose_body . '" '; echo $input_onfocus; ?>><?php echo $body; ?></textarea>

<?php if (checkForJavascript()) // Display text area resizing handle

    // FIXME: There should be a better way (probably best to be javascript only, although trying to avoid unnecessary js) to calculate resize_handle width to match $editor_width 
    //$_total_width_ = 700;
    echo '<div class="resize_handle" ' /* style="width: ' . ($editor_width * 8.3) . 'px; margin-left: ' . (($_total_width_ - ($editor_width * 8.3)) / 2) . 'px;" */ . 'onmousedown="startVertResizeDrag(event, document.compose.body, 40, 9000, this);"></div>';

?>

  </td>
 </tr>
 <?php
    if ($show_bottom_send) {
        ?>
 <tr>
  <td class="bottomSend">
   <input type="submit" <?php if (!unique_widget_name('send', TRUE) && $accesskey_compose_send != 'NONE') echo 'accesskey="' . $accesskey_compose_send . '" '; ?>name="<?php echo unique_widget_name('send'); ?>" value="<?php echo _("Send"); ?>" />
  </td>
 </tr>
        <?php
    }
 ?>
</table>
</div>
<input type="hidden" name="send_button_count" value="<?php echo unique_widget_name('send', TRUE); ?>" />
