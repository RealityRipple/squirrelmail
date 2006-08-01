<?php
/**
 * folder_manip.tpl
 *
 * Template for folder management (create, rename, delete, (un)subscribe)
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

<br />
<table bgcolor="<?php echo $color[0]; ?>" align="center" width="95%" cellpadding="1" cellspacing="0" border="0">
<tr>
<td align="center"><b><?php echo _("Folders"); ?></b>

<table align="center" width="100%" cellpadding="5" cellspacing="0" border="0">
<tr>
<td bgcolor="<?php echo $color[4] ?>" align="center">

<?php
// if there are any messages, output them.
if ( !empty($td_str) ) :
?>
<table align="center" width="100%" cellpadding="4" cellspacing="0" border="0">
<tr><td align="center"><b><?php echo $td_str ?></b><br />
<a href="left_main.php" target="left"><?php echo _("refresh folder list") ?></a>
</td></tr>
</table>

<?php
endif;
?>

<br />

<table align="center" width="70%" cellpadding="4" cellspacing="0" border="0">
<tr><td bgcolor="<?php echo $color[9]?>" align="center"><b><?php echo _("Create Folder") ?></b></td></tr>
<tr><td bgcolor="<?php echo $color[0]?>" align="center">

<form method="post" action="folders.php" name="cf" id="cf">
<input type="hidden" name="smaction" value="create">
<input type="text" name="folder_name" size="25" value=""><br />
<?php echo _("as a subfolder of") ?><br />
<select name="subfolder">
<?php echo $mbx_option_list; ?>
</select>
<?php if ($show_contain_subfolders_option): ?>
<br />
<input type="checkbox" name="contain_subs" id="contain_subs" value="1">&nbsp;<label
  for="contain_subs"><?php echo _("Let this folder contain subfolders") ?></label><br />
<?php endif; ?>
<input type="submit" value="<?php echo _("Create") ?>" />
</form>
</td></tr>

<tr><td bgcolor="<?php echo $color[4] ?>">&nbsp;</td></tr>


<table align="center" width="70%" cellpadding="4" cellspacing="0" border="0">
<tr><td bgcolor="<?php echo $color[9]?>" align="center"><b><?php echo _("Rename a Folder") ?></b></td></tr>
<tr><td bgcolor="<?php echo $color[0]?>" align="center">

<?php if ( !empty($rendel_folder_list) ) : ?>

<form method="post" action="folders.php" name="rf" id="rf">
<input type="hidden" name="smaction" value="rename">
<select name="old_name">
<option value="">[ <?php echo _("Select a folder") ?> ]</option>
<?php echo $rendel_folder_list ?>
</select>
<input type="submit" value="<?php echo _("Rename") ?>" />
</form>

<?php else: ?>

<?php echo _("No folders found") ?><br /><br />

<?php endif; ?>
</td></tr>


<tr><td bgcolor="<?php echo $color[4] ?>">&nbsp;</td></tr>


<table align="center" width="70%" cellpadding="4" cellspacing="0" border="0">
<tr><td bgcolor="<?php echo $color[9]?>" align="center"><b><?php echo _("Delete Folder") ?></b></td></tr>
<tr><td bgcolor="<?php echo $color[0]?>" align="center">

<?php if ( !empty($rendel_folder_list) ) : ?>

<form method="post" action="folders.php" name="df" id="df">
<input type="hidden" name="smaction" value="delete">
<select name="folder_name">
<option value="">[ <?php echo _("Select a folder") ?> ]</option>
<?php echo $rendel_folder_list ?>
</select>
<input type="submit" value="<?php echo _("Delete") ?>" />
</form>

<?php else: ?>

<?php echo _("No folders found") ?><br /><br />

<?php endif; ?>
</td></tr>


<tr><td bgcolor="<?php echo $color[4] ?>">&nbsp;</td></tr>

<?php if ( $show_only_subscribed_folders ): ?>

<table align="center" width="70%" cellpadding="4" cellspacing="0" border="0">
<tr><td colspan="2" bgcolor="<?php echo $color[9]?>" align="center"><b><?php echo  _("Unsubscribe") . '/' . _("Subscribe") ?></b></td></tr>
<tr><td bgcolor="<?php echo $color[0]?>" align="center" width="50%">

<?php if ( !empty($rendel_folder_list) ) { ?>

<form method="post" action="folders.php" name="uf" id="uf">
<input type="hidden" name="smaction" value="unsubscribe">
<select name="folder_names[]" multiple="multiple" size="8">
<?php echo $rendel_folder_list ?>
</select><br /><br />
<input type="submit" value="<?php echo _("Unsubscribe") ?>" />
</form>

<?php } else {
    echo _("No folders were found to unsubscribe from.");
  }
?>
</td>

<td align="center" bgcolor="<?php echo $color[0]?>" width="50%">
<?php
if ( $no_list_for_subscribe ) {
?>
<form method="post" action="folders.php" name="sf" id="sf">
<input type="hidden" name="smaction" value="subscribe">
<input type="text" name="folder_names[]" size="25" />
<input type="submit" value="<?php echo _("Subscribe") ?>" />
</form>
<?php
} elseif ( !empty($subbox_option_list) ) {
?>
<form method="post" action="folders.php" name="sf" id="sf">
<input type="hidden" name="smaction" value="subscribe">
<select name="folder_names[]" multiple="multiple" size="8">
<?php echo $subbox_option_list ?>
</select><br /><br />
<input type="submit" value="<?php echo _("Subscribe") ?>" />
</form>

<?php } else {
    echo _("No folders were found to subscribe to.");
  }
?>



<?php endif; ?>

<?php do_hook('folders_bottom');  ?>

</td></tr>
</table>
</td></tr>
</table>
    
