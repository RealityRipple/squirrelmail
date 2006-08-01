<?php
/**
 * folder_manip_dialog.tpl
 *
 * Template for folder management dialogs (rename, delete)
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);
?>

<form action="folders.php" method="post">

<?php if ( $dialog_type == 'rename' ) : ?>


<input type="hidden" name="smaction" value="rename">
<?php if ( $isfolder ) { ?>
<input type="hidden" name="isfolder" value="true" />
<?php } ?>
<input type="hidden" name="orig" value="<?php echo $old ?>" />
<input type="hidden" name="old_name" value="<?php echo $old_name ?>" />

<table align="center" width="95%" border="0">
<tr><td align="center" bgcolor="<?php echo $color[0] ?>"><b><?php echo _("Rename a folder") ?></b></td>

<tr><td align="center" bgcolor="<?php echo $color[4] ?>">

<label for="new_name"><?php echo _("New name:") ?></label><br />
<b><?php echo $old_parent ?></b><input type="text" name="new_name" id="new_name"
value="<?php echo $old_name ?>" size="25" /><br /><br />

<input type="submit" value="<?php echo _("Rename") ?>" />
<input type="submit" name="cancelbutton" value="<?php echo _("Cancel") ?>" />

<?php elseif ( $dialog_type == 'delete' ) : ?>

<input type="hidden" name="smaction" value="delete">
<input type="hidden" name="folder_name" value="<?php echo $folder_name ?>" />

<table align="center" width="95%" border="0">
<tr><td align="center" bgcolor="<?php echo $color[0] ?>"><b><?php echo _("Delete Folder") ?></b></td>

<tr><td align="center" bgcolor="<?php echo $color[4] ?>">

<?php echo sprintf(_("Are you sure you want to delete %s?"),
            str_replace(array(' ','<','>'),array('&nbsp;','&lt;','&gt;'),$visible_folder_name)); ?>
<br /><br />

<input type="submit" name="confirmed" value="<?php echo _("Yes") ?>" />
<input type="submit" name="cancelbutton" value="<?php echo _("No") ?>" />


<?php endif; ?>

</td></tr></table>

</form>


