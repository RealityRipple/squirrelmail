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
   require_once('../functions/options.php');   
   
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

   /* TRASH FOLDER */
   echo '<tr><td nowrap align="right">';
   echo _("Trash Folder:");
   echo '</td><td>';
      echo "<TT><SELECT NAME=trash>\n";
      if ($move_to_trash == true) {
         echo '<option value="none">' . _("Do not use Trash");
      } else {
         echo '<option value="none" selected>' . _("Do not use Trash");
      }
 
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


   /* SENT FOLDER */
   echo '<tr><td nowrap align="right">';
   echo _("Sent Folder:");
   echo '</td><td>';
      echo '<TT><SELECT NAME="sent">' . "\n";
      if ($move_to_sent == true)
         echo '<option value="none">' . _("Do not use Sent");
      else
         echo "<option value=\"none\" selected>" . _("Do not use Sent");
 
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

   /* Drafts Folder. */
   echo '<tr><td nowrap align="right">';
   echo _("Drafts Folder:");
   echo '</td><td>';
   echo '<TT><SELECT NAME="draft">';
   if ($save_as_draft == true)
      echo '<option value="none">' . _("Do not use Drafts");
   else
      echo '<option value="none" selected>' . _("Do not use Drafts");

   for ($i = 0; $i < count($boxes); $i++) {
      $use_folder = true;
      if (strtolower($boxes[$i]['unformatted']) == 'inbox') {
         $use_folder = false;
      }
      if ($use_folder == true) {
         $box = $boxes[$i]['unformatted-dm'];
         $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
         $select_draft_value = rtrim($boxes[$i]['unformatted']);
         if (($select_draft_value == $draft_folder) && ($save_as_draft == true)) {
            echo "         <OPTION SELECTED VALUE=\"$box\">$box2\n";
         } else {
            echo "         <OPTION VALUE=\"$box\">$box2\n";
         }
      }
   }
   echo "</SELECT></TT>\n";
   echo '</td></tr>';
   // if( $unseen_notify == '' )
   //   $unseen_notify = '2';
   OptionRadio( _("Unseen message notification"),
                'unseennotify',
                array( 1 => _("No notification"),
                       2 => _("Only INBOX"),
                       3 => _("All Folders") ),
                $unseen_notify, '', '',
                '<br>' );
    OptionRadio( _("Unseen message notification type"),
                 'unseentype',
                 array( 1 => _("Only unseen"),
                        2 => _("Unseen and Total") ),
                 $unseen_type, '', '',
                 '<br>' );
    OptionCheck( _("Collapseable folders"),
                 'collapsefolders',
                 $collapse_folders,
                 _("Enable Collapseable Folders") );
   OptionSelect( '<b>' . _("Show Clock on Folders Panel") . '</b> ' . _("Date format"),
                 'dateformat',
                 array( '1' => 'MM/DD/YY HH:MM',
                        '2' => 'DD/MM/YY HH:MM',
                        '3' => 'DDD, HH:MM',
                        '4' => 'HH:MM:SS',
                        '5' => 'HH:MM',
                        '6' => _("No Clock") ),
                 $date_format );
   OptionSelect( _("Hour format"),
                 'hourformat',
                 array( '1' => _("24-hour clock"),
                        '2' => _("12-hour clock") ),
                 $hour_format );     
                 
   echo '<tr><td colspan=2><hr noshade></td></tr>';
   do_hook("options_folders_inside");
   OptionSubmit( 'submit_folder' );
?>         

      </table>
   </form>

   <?php do_hook('options_folders_bottom'); ?>

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>