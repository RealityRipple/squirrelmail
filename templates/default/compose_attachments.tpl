<?php
/**
 * compose_attachments.tpl
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

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div class="compose">
<table cellspacing="0" class="table1">
 <tr class="header">
  <td class="fieldName" style="width: 1%; white-space: nowrap;">
   <?php echo _("New") .' '. _("Attachment");?>:
  </td>
  <td class="fieldValue">
   <input type="file" name="attachfile" size="48" />
   &nbsp;
   <input type="submit" name="attach" value="<?php echo _("Attach"); ?>" />
   &nbsp;
   <?php
    if($max_file_size != -1) {
        ?>
   <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_file_size; ?>" />
   (<?php echo _("Max."); ?> <?php echo humanReadableSize($max_file_size); ?>)
        <?php
    }
   ?>
  </td>
 </tr>
 <?php
    foreach ($attachments as $attach) {
        ?>
 <tr class="attachment">
  <td class="fieldName" style="width: 1%">
   <input type="checkbox" name="delete[]" value="<?php echo $attach['Key']; ?>" />
  </td>
  <td class="fieldValue">
   <?php echo $attach['FileName']; ?> - <?php echo $attach['ContentType']; ?> (<?php echo humanReadableSize($attach['Size']); ?>)
  </td>
 </tr>
        <?php
    }
    
    if (count($attachments) > 0) {
        ?>
 <tr class="header">
  <td colspan="2">
   <input type="submit" name="do_delete" value="<?php echo _("Delete selected attachments"); ?>" />
  </td>
 </tr>
        <?php
    }
 ?>
</table>
</div>
