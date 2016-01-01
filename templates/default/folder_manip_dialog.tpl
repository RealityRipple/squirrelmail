<?php
/**
 * folder_manip_dialog.tpl
 *
 * Template for folder management dialogs (rename, delete)
 *
 * The following variables are available in this template:
 *      + $dialog_type - string containing 'rename' or 'delete' to determine
 *                       the desired action
 *
 * Depending on $dialog_type, other variables will be available.  If 
 * $dialog_type is 'rename', the following variables will be available:
 *      + $current_folder_name - the current name of the element being renamed
 *      + $parent_folder - the name of the parent of the element being renamed
 *      + $current_full_name - the current full mailbox name
 *      + $is_folder - boolean TRUE if the element being renamed is a folder
 *
 * If $dialog_type is 'delete', the following variables will be available:
 *      + $folder_name - the name of the element being deleted
 *      + $visible_folder_name - scrubbed string of the element begin deleted
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);
?>
<div class="dialogbox">
<form action="folders.php" method="post">
<input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
<table cellspacing="0" class="wrapper">
<?php
if ( $dialog_type == 'rename' ) {
    ?>
 <tr>
  <td class="header1">
   <?php echo _("Rename a folder") ?>
  </td>
 </tr>
 <tr>
  <td>
   <label for="new_name"><?php echo _("New name:") ?></label>
   <br />
   <b><?php echo $parent_folder ?></b>
   <input type="text" name="new_name" id="new_name" value="<?php echo $current_folder_name ?>" size="25" />
   <br /><br />
   <?php
    if ( $is_folder ) {
        echo '<input type="hidden" name="isfolder" value="true" />';
    }
   ?>
   <input type="hidden" name="smaction" value="rename" />
   <input type="hidden" name="orig" value="<?php echo $current_full_name ?>" />
   <input type="hidden" name="old_name" value="<?php echo $current_folder_name ?>" />
   <input type="submit" value="<?php echo _("Rename") ?>" />
   <input type="submit" name="cancelbutton" value="<?php echo _("Cancel") ?>" />
    <?php
} elseif ( $dialog_type == 'delete' ) {
    ?>
 <tr>
  <td class="header1">
   <?php echo _("Delete Folder") ?>
  </td>
 </tr>
 <tr>
  <td>
   <?php echo sprintf(_("Are you sure you want to delete %s?"), $visible_folder_name); ?>
   <br /><br />
   <input type="hidden" name="smaction" value="delete" />
   <input type="hidden" name="folder_name" value="<?php echo $folder_name ?>" />
   <input type="submit" name="confirmed" value="<?php echo _("Yes") ?>" />
   <input type="submit" name="cancelbutton" value="<?php echo _("No") ?>" />
    <?php
}
?>
  </td>
 </tr>
</table>
</form>
</div>
