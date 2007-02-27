<?php
/**
 * folder_manip.tpl
 *
 * Template for folder management (create, rename, delete, (un)subscribe)
 *
 * The following variables are available in this template:
 *      + $mbx_option_list - string containing all mailboxes as <option>'s for
 *                           use in <select>'s on this page.
 *      + $rendel_folder_list - string containing all mailboxes available for
 *                           delete/rename as <option>'s for use in <select>'s
 *                           on this page. 
 *      + $show_subfolders_option - boolean TRUE if the a folder can contain
 *                           subfolders in conf.pl > Folder Options
 *      + $show_only_subscribed_folders - boolean TRUE if the user only wants
 *                           to see subscribed folders.
 *      + $no_list_for_subscribe = boolean TRUE if the subscribe list should NOT
 *                           be displayed in conf.pl
 *      + $subbox_option_list - array containing a list of folders that can be
 *                           subscribed to.  Each array element contains an
 *                           array with the following elements:
 *                              $el['Value'] - encoded string for the VALUE
 *                                             field of an input element
 *                              $el['Display'] - string containing the display
 *                                             name for the element
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
<div class="dialogbox">
<table cellspacing="0" class="wrapper">
 <tr>
  <td class="header1">
   <?php echo _("Folders"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="header2">
      <?php echo _("Create Folder") ?>
     </td>
    </tr>
    <tr>
     <td>
      <form method="post" action="folders.php" name="cf" id="cf">
      <input type="hidden" name="smaction" value="create" />
      <input type="text" name="folder_name" size="25" value="" />
      <br />
      <?php echo _("as a subfolder of") ?>
      <br />
      <select name="subfolder">
       <?php echo $mbx_option_list; ?>
      </select>
      <?php 
        if ($show_subfolders_option) {
            ?>
      <br />
      <input type="checkbox" name="contain_subs" id="contain_subs" value="1" />
      &nbsp;
      <label for="contain_subs"><?php echo _("Let this folder contain subfolders") ?></label>
      <br />
            <?php
        }
      ?>
      <input type="submit" value="<?php echo _("Create") ?>" />
      </form>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="header2">
      <?php echo _("Rename a Folder") ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php
        if ( !empty($rendel_folder_list) ) {
            ?>
     <form method="post" action="folders.php" name="rf" id="rf">
     <input type="hidden" name="smaction" value="rename">
     <select name="old_name">
      <option value="">[ <?php echo _("Select a folder") ?> ]</option>
      <?php echo $rendel_folder_list ?>
     </select>
     <input type="submit" value="<?php echo _("Rename") ?>" />
     </form>
            <?php
        } else {
            echo _("No folders found");
        }
      ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="header2">
      <?php echo _("Delete Folder") ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php
        if ( !empty($rendel_folder_list) ) { 
            ?>
      <form method="post" action="folders.php" name="df" id="df">
      <input type="hidden" name="smaction" value="delete">
      <select name="folder_name">
       <option value="">[ <?php echo _("Select a folder") ?> ]</option>
       <?php echo $rendel_folder_list ?>
      </select>
      <input type="submit" value="<?php echo _("Delete") ?>" />
      </form>
            <?php
        } else {
            echo _("No folders found");
        }
      ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   <?php
    if ($show_only_subscribed_folders) {
        ?>
   <table cellspacing="0">
    <tr>
     <td class="header2" colspan="2">
      <?php echo _("Unsubscribe") .'/'. _("Subscribe"); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php
        if (!empty($rendel_folder_list)) {
            ?>
      <form method="post" action="folders.php" name="uf" id="uf">
      <input type="hidden" name="smaction" value="unsubscribe" />
      <select name="folder_names[]" multiple="multiple" size="8">
       <?php echo $rendel_folder_list ?>
      </select>
      <br /><br />
      <input type="submit" value="<?php echo _("Unsubscribe") ?>" />
      </form>
            <?php
        } else {
            echo _("No folders were found to unsubscribe from.");
        }
      ?>
     </td>
     <td>
     <?php
        if ($no_list_for_subscribe) {
            ?>
      <form method="post" action="folders.php" name="sf" id="sf">
      <input type="hidden" name="smaction" value="subscribe">
      <input type="text" name="folder_names[]" size="25" />
      <input type="submit" value="<?php echo _("Subscribe") ?>" />
      </form>
            <?php
        } elseif (!empty($subbox_option_list)) {
            ?>
      <form method="post" action="folders.php" name="sf" id="sf">
      <input type="hidden" name="smaction" value="subscribe" />
      <div>
            <?php
/*
      <select name="folder_names[]" multiple="multiple" size="8">
       <?php echo $subbox_option_list ?>
      </select>
*/
            foreach ($subbox_option_list as $folder) {
                echo '<input type="checkbox" name="folder_names[]" id="sub_'.$folder['Value'].'" value="'.$folder['Value'].'" /> '.
                    '<label for="sub_'.$folder['Value'].'">'.$folder['Display'].'</label><br />';
            }
            ?>
      </div>
      <br />
      <input type="submit" value="<?php echo _("Subscribe") ?>" />
      </form>
            <?php
        } else {
            echo _("No folders were found to subscribe to.");
        }
     ?>
     </td>
    </tr>
   </table>
        <?php
    }
   ?>
  </td>
 </tr>
</table>
<?php /* FIXME: no hooks in templates!! */ global $null; do_hook('folders_bottom', $null);  ?>
</div>
