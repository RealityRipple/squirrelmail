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

    /* Build a simple array into which we will build options. */
    $optvals = array();

    $special_folder_values = array();
    foreach ($boxes as $folder) {
        if (strtolower($folder['unformatted']) != 'inbox') {
            $real_value = $folder['unformatted-dm'];
            $disp_value = str_replace(' ', '&nbsp;', $folder['formatted']);
            $special_folder_values[$real_value] = $disp_value;
        }
    }

    $trash_none = array(SMPREF_NONE => _("Do not use Trash"));
    $trash_folder_values = array_merge($trash_none, $special_folder_values);
    $optvals[] = array(
        'name'    => 'trash_folder',
        'caption' => _("Trash Folder"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $trash_folder_values
    );
    
    $sent_none = array(SMPREF_NONE => _("Do not use Sent"));
    $sent_folder_values = array_merge($sent_none, $special_folder_values);
    $optvals[] = array(
        'name'    => 'sent_folder',
        'caption' => _("Sent Folder"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $sent_folder_values
    );
    
    $drafts_none = array(SMPREF_NONE => _("Do not use Drafts"));
    $draft_folder_values = array_merge($draft_none, $special_folder_values);
    $optvals[] = array(
        'name'    => 'draft_folder',
        'caption' => _("Draft Folder"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $draft_folder_values
    );

    /* Build all these values into an array of SquirrelOptions objects. */
    $options = createOptionArray($optvals);

    /* Print the row for each option. */
    foreach ($options as $option) {
        if ($option->type != SMOPT_TYPE_HIDDEN) {
            echo "<TR>\n";
            echo '  <TD ALIGN="RIGHT" VALIGN="MIDDLE" NOWRAP>'
               . $option->caption . ":</TD>\n";
            echo '  <TD>' . $option->createHTMLWidget() . "</TD>\n";
            echo "</TR>\n";
        } else {
            echo $option->createHTMLWidget();
        }
    }

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
