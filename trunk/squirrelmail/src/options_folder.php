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

    $optvals[] = array(
        'name'    => 'location_of_bar',
        'caption' => _("Location of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_LOC_LEFT  => _("Left"),
                           SMPREF_LOC_RIGHT => _("Right"))
    );

    $left_size_values = array();
    for ($lsv = 100; $lsv <= 300; $lsv += 10) {
        $left_size_values[$lsv] = "$lsv " . _("pixels");
    }
    $optvals[] = array(
        'name'    => 'left_size',
        'caption' => _("Width of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => $left_size_values
    );

    $minute_str = _("Minutes");
    $left_refresh_values = array(SMPREF_NONE => _("Never"));
    foreach (array(30,60,120,180,300,600) as $lr_val) {
        if ($lr_val < 60) {
            $left_refresh_values[$lr_val] = "$lr_val " . _("Seconds");
        } else if ($lr_val == 60) {
            $left_refresh_values[$lr_val] = "1 " . _("Minute");
        } else {
            $left_refresh_values[$lr_val] = ($lr_val/60) . " $minute_str";
        }
    }
    $optvals[] = array(
        'name'    => 'left_refresh',
        'caption' => _("Auto Refresh Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => $left_refresh_values
    );

    $optvals[] = array(
        'name'    => 'unseen_notify',
        'caption' => _("Enable Unseen Message Notification"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_UNSEEN_NONE  => _("No Notification"),
                           SMPREF_UNSEEN_INBOX => _("Only INBOX"),
                           SMPREF_UNSEEN_ALL   => _("All Folders"))
    );

    $optvals[] = array(
        'name'    => 'unseen_type',
        'caption' => _("Unseen Message Notification Type"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_UNSEEN_ONLY  => _("Only Unseen"),
                           SMPREF_UNSEEN_TOTAL => _("Unseen and Total")) 
    );

    $optvals[] = array(
        'name'    => 'collapse_folders',
        'caption' => _("Enable Collapsable Folders"),
        'type'    => SMOPT_TYPE_BOOLEAN,
        'refresh' => SMOPT_REFRESH_FOLDERLIST
    );

    $optvals[] = array(
        'name'    => 'date_format',
        'caption' => _("Show Clock on Folders Panel"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array( '1' => 'MM/DD/YY HH:MM',
                            '2' => 'DD/MM/YY HH:MM',
                            '3' => 'DDD, HH:MM',
                            '4' => 'HH:MM:SS',
                            '5' => 'HH:MM',
                            '6' => _("No Clock")),
    );

    $optvals[] = array(
        'name'    => 'hour_format',
        'caption' => _("Hour Format"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_FOLDERLIST,
        'posvals' => array(SMPREF_TIME_12HR => _("12-hour clock"),
                           SMPREF_TIME_24HR => _("24-hour clock")) 
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
