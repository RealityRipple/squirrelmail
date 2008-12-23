<?php
/**
 * compose_body.tpl
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
 <tr>
  <td style="text-align: center">
   <textarea name="body" id="body" rows="<?php echo $editor_height; ?>" cols="<?php echo $editor_width; ?>" <?php echo $input_onfocus; ?>><?php echo $body; ?></textarea>
  </td>
 </tr>
 <?php
    if ($show_bottom_send) {
        ?>
 <tr>
  <td class="bottomSend">
   <input type="submit" name="<?php echo unique_widget_name('send'); ?>" value="<?php echo _("Send"); ?>" />
  </td>
 </tr>
        <?php
    }
 ?>
</table>
</div>
<input type="hidden" name="send_button_count" value="<?php echo unique_widget_name('send', TRUE); ?>" />
