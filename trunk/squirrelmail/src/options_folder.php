<?php
   /**
    **  options_folder.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all options relating to folders
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');
   require_once('../functions/array.php');
   require_once('../functions/plugin.php');
   
   displayPageHeader($color, 'None');

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $boxes = sqimap_mailbox_list($imapConnection);
   sqimap_logout($imapConnection);
?>
   <br>
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
<tr><td bgcolor="<?php echo $color[0] ?>" align="center">

      <b><?php echo _("Options") . " - " . _("Folder Preferences"); ?></b>

    <table width="100%" border="0" cellpadding="1" cellspacing="1">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name="f" action="options.php" method="post"><br>

      <table width="100%" cellpadding="2" cellspacing="0" border="0">

<?php if ($show_prefix_option == true) {   ?>   
         <tr>
            <td align=right nowrap><?php echo _("Folder Path"); ?>:
            </td><td>
<?php if (isset ($folder_prefix))
      echo '         <input type="text" name="folderprefix" value="'.$folder_prefix.'" size="35"><br>';
   else
      echo '         <input type="text" name="folderprefix" value="'.$default_folder_prefix.'" size="35"><br>';
?>
            </td>
         </tr>
<?php }          

   // TRASH FOLDER
   echo '<tr><td nowrap align="right">';
   echo _("Trash Folder:");
   echo '</td><td>';
      echo "<TT><SELECT NAME=trash>\n";
      if ($move_to_trash == true)
         echo '<option value="none">' . _("Don't use Trash");
      else
         echo '<option value="none" selected>' . _("Do not use Trash");
 
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         if (strtolower($boxes[$i]['unformatted']) == 'inbox') {
            $use_folder = false;
         }
         if ($use_folder == true) {
            $box = $boxes[$i]['unformatted-dm'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
            if (($boxes[$i]['unformatted'] == $trash_folder) && ($move_to_trash == true))
               echo "         <OPTION SELECTED VALUE=\"$box\">$box2\n";
            else
               echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
   echo '</td></tr>';  


   // SENT FOLDER
   echo '<tr><td nowrap align="right">';
   echo _("Sent Folder:");
   echo '</td><td>';
      echo '<TT><SELECT NAME="sent">' . "\n";
      if ($move_to_sent == true)
         echo '<option value="none">' . _("Don't use Sent");
      else
         echo "<option value=none selected>" . _("Do not use Sent");
 
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         if (strtolower($boxes[$i]['unformatted']) == 'inbox') {
            $use_folder = false;
         }
         if ($use_folder == true) {
            $box = $boxes[$i]['unformatted-dm'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
            if (($boxes[$i]['unformatted'] == $sent_folder) && ($move_to_sent == true))
               echo "         <OPTION SELECTED VALUE=\"$box\">$box2\n";
            else
               echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
   echo '</td></tr>';  
?>
         <tr>
            <td valign=top align=right>
               <?php echo _("Unseen message notification"); ?>:
            </td>
            <td>
               <input type=radio name=unseennotify value=1<?php if ($unseen_notify == 1) echo " checked"; ?>> <?php echo _("No notification") ?><br>
               <input type=radio name=unseennotify value=2<?php if ($unseen_notify != 1 && $unseen_notify != 3) echo " checked"; ?>> <?php echo _("Only INBOX") ?><br>
               <input type=radio name=unseennotify value=3<?php if ($unseen_notify == 3) echo " checked"; ?>> <?php echo _("All Folders") ?><br>
            </td>
         </tr>
         <tr>
            <td valign=top align=right>
               <?php echo _("Unseen message notification type"); ?>:
            </td>
	    <td>
               <input type=radio name=unseentype value=1<?php if ($unseen_type < 2 || $unseen_type > 2) echo " checked"; ?>> <?php echo _("Only unseen"); ?> - (4)<br>
               <input type=radio name=unseentype value=2<?php if ($unseen_type == 2) echo " checked"; ?>> <?php echo _("Unseen and Total"); ?> - (4/27)
            </td>
         </tr>
         <tr>
            <td valign=top align=right>
               <?php echo _("Collapseable folders"); ?>:
            </td>
            <td>
               <input type=checkbox name=collapsefolders <?php if (isset($collapse_folders) && $collapse_folders) echo " checked"; ?>>
	         <?php echo _("Enable Collapseable Folders"); ?>
            </td>
         </tr>
         <?php do_hook("options_folders_inside"); ?>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="<?php echo _("Submit"); ?>" name="submit_folder">
            </td>
         </tr>
      </table>
   </form>

   <?php do_hook('options_folders_bottom'); ?>

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>