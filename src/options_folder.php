<?php
   /**
    **  options_folder.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all options relating to folders
    **
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($plugin_php))
      include("../functions/plugin.php");

   include("../src/load_prefs.php");
   displayPageHeader($color, "None");

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $boxes = sqimap_mailbox_list($imapConnection, $boxes);
   sqimap_logout($imapConnection);
?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . " - " . _("Folder Preferences"); ?></b></center>
   </td></tr></table>

   <form name=f action="options.php" method=post>
      <table width=100% cellpadding=0 cellspacing=2 border=0>

<?php if ($show_prefix_option == true) {   ?>   
         <tr>
            <td align=right nowrap><?php echo _("Folder Path"); ?>:
            </td><td>
<?php if (isset ($folder_prefix))
      echo "         <input type=text name=folderprefix value=\"$folder_prefix\" size=35><br>";
   else
      echo "         <input type=text name=folderprefix value=\"$default_folder_prefix\" size=35><br>";
?>
            </td>
         </tr>
<?php }          

   // TRASH FOLDER
   echo "<tr><td nowrap align=right>";
   echo _("Trash Folder:");
   echo "</td><td>";
      echo "<TT><SELECT NAME=trash>\n";
      if ($move_to_trash == true)
         echo "<option value=none>" . _("Don't use Trash");
      else
         echo "<option value=none selected>" . _("Do not use Trash");
 
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         if (strtolower($boxes[$i]["unformatted"]) == "inbox") {
            $use_folder = false;
         }
         if ($use_folder == true) {
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            if (($boxes[$i]["unformatted"] == $trash_folder) && ($move_to_trash == true))
               echo "         <OPTION SELECTED VALUE=\"$box\">$box2\n";
            else
               echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
   echo "</td></tr>";  


   // SENT FOLDER
   echo "<tr><td nowrap align=right>";
   echo _("Sent Folder:");
   echo "</td><td>";
      echo "<TT><SELECT NAME=sent>\n";
      if ($move_to_sent == true)
         echo "<option value=none>" . _("Don't use Sent");
      else
         echo "<option value=none selected>" . _("Do not use Sent");
 
      for ($i = 0; $i < count($boxes); $i++) {
         $use_folder = true;
         if (strtolower($boxes[$i]["unformatted"]) == "inbox") {
            $use_folder = false;
         }
         if ($use_folder == true) {
            $box = $boxes[$i]["unformatted-dm"];
            $box2 = replace_spaces($boxes[$i]["formatted"]);
            if (($boxes[$i]["unformatted"] == $sent_folder) && ($move_to_sent == true))
               echo "         <OPTION SELECTED VALUE=\"$box\">$box2\n";
            else
               echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
      echo "</SELECT></TT>\n";
   echo "</td></tr>";  
?>
         <tr>
            <td valign=top align=right>
               <br>
               <?php echo _("Unseen message notification"); ?>:
            </td>
            <td>
               <input type=radio name=unseennotify value=1<?php if ($unseen_notify == 1) echo " checked"; ?>> <?php echo _("No notification") ?><br>
               <input type=radio name=unseennotify value=2<?php if ($unseen_notify != 1 && $unseen_notify != 3) echo " checked"; ?>> <?php echo _("Only INBOX") ?><br>
               <input type=radio name=unseennotify value=3<?php if ($unseen_notify == 3) echo " checked"; ?>> <?php echo _("All Folders") ?><br>
               <br>
            </td>
         </tr>
         <tr>
            <td valign=top align=right>
               <br>
               <?php echo _("Unseen message notification type"); ?>:
            </td>
            <td>
               <input type=radio name=unseentype value=1<?php if ($unseen_type < 2 || $unseen_type > 2) echo " checked"; ?>> <?php echo _("Only unseen"); ?> - (4)<br> 
               <input type=radio name=unseentype value=2<?php if ($unseen_type == 2) echo " checked"; ?>> <?php echo _("Unseen and Total"); ?> - (4/27)
            </td>
         </tr>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="Submit" name="submit_folder">
            </td>
         </tr>
      </table>
   </form>
   <?php do_hook("options_folders_bottom"); ?>
</body></html>
