<?php
   /**
    **  options.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays the options page. Pulls from proper user preference files
    **  and config.php. Displays preferences as selected and other options.
    **
    **  $Id$
    **/

   session_start();

   if (!isset($strings_php))
      include('../functions/strings.php');
   if (!isset($config_php))
      include('../config/config.php');
   if (!isset($page_header_php))
      include('../functions/page_header.php');
   if (!isset($display_messages_php))
      include('../functions/display_messages.php');
   if (!isset($imap_php))
      include('../functions/imap.php');
   if (!isset($array_php))
      include('../functions/array.php');
   if (!isset($i18n_php))
      include('../functions/i18n.php');
   if (!isset($auth_php))
      include ('../functions/auth.php'); 

   if (isset($language)) {
      setcookie('squirrelmail_language', $language, time()+2592000);
      $squirrelmail_language = $language;
   }   

   include('../src/load_prefs.php');
   displayPageHeader($color, 'None');
   is_logged_in(); 
?>

<br>
<table width="95%" align="center" cellpadding="2" cellspacing="2" border="0">
<tr><td bgcolor="<?php echo $color[0] ?>">
   <center><b><?php echo _("Options") ?></b></center>
</td></tr></table>

<?php
   if (isset($submit_personal)) {
      # Save personal information
      if (isset($full_name)) setPref($data_dir, $username, 'full_name', $full_name);
      if (isset($email_address)) setPref($data_dir, $username, 'email_address', $email_address);
      if (isset($reply_to)) setPref($data_dir, $username, 'reply_to', $reply_to);  
      setPref($data_dir, $username, 'use_signature', $usesignature);  
      setPref($data_dir, $username, 'prefix_sig', $prefixsig);
      if (isset($signature_edit)) setSig($data_dir, $username, $signature_edit);
      
      do_hook('options_personal_save');
      
      echo '<br><center><b>'._("Successfully saved personal information!").'</b></center><br>';
   } else if (isset($submit_display)) {  
      # Save display preferences
      setPref($data_dir, $username, 'chosen_theme', $chosentheme);
      setPref($data_dir, $username, 'show_num', $shownum);
      setPref($data_dir, $username, 'wrap_at', $wrapat);
      setPref($data_dir, $username, 'editor_size', $editorsize);
      setPref($data_dir, $username, 'left_refresh', $leftrefresh);
      setPref($data_dir, $username, 'language', $language);
      setPref($data_dir, $username, 'location_of_bar', $folder_new_location);
      setPref($data_dir, $username, 'location_of_buttons', $button_new_location);
      setPref($data_dir, $username, 'left_size', $leftsize);
      setPref($data_dir, $username, 'use_javascript_addr_book', $javascript_abook);
    
      do_hook('options_display_save');

      echo '<br><center><b>'._("Successfully saved display preferences!").'</b><br>';
      echo '<a href="../src/webmail.php" target=_top>' . _("Refresh Page") . '</a></center><br>';
   } else if (isset($submit_folder)) { 
      # Save folder preferences
      if ($trash != 'none') {
         setPref($data_dir, $username, 'move_to_trash', true);
         setPref($data_dir, $username, 'trash_folder', $trash);
      } else {
         setPref($data_dir, $username, 'move_to_trash', '0');
         setPref($data_dir, $username, 'trash_folder', 'none');
      }
      if ($sent != 'none') {
         setPref($data_dir, $username, 'move_to_sent', true);
         setPref($data_dir, $username, 'sent_folder', $sent);
      } else {
         setPref($data_dir, $username, 'move_to_sent', '0');
         setPref($data_dir, $username, 'sent_folder', 'none');
      } 
      if (isset($folderprefix))
         setPref($data_dir, $username, 'folder_prefix', $folderprefix);
      setPref($data_dir, $username, 'unseen_notify', $unseennotify);
      setPref($data_dir, $username, 'unseen_type', $unseentype);
      if (isset($collapsefolders))
          setPref($data_dir, $username, 'collapse_folders', $collapsefolders);
      else
          removePref($data_dir, $username, 'collapse_folders');
      do_hook('options_folders_save');
      echo '<br><center><b>'._("Successfully saved folder preferences!").'</b><br>';
      echo '<a href="../src/left_main.php" target=left>' . _("Refresh Folder List") . '</a></center><br>';
   } else {
      do_hook('options_save');
   }
   
?>


<table width="90%" cellpadding="0" cellspacing="10" border="0" align="center">
<tr>
   <td width="50%" valign="top">
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td bgcolor="<?php echo $color[9] ?>">
               <a href="options_personal.php"><?php echo _("Personal Information"); ?></a>
            </td>
         </tr>
         <tr>
            <td bgcolor="<?php echo $color[0] ?>">
               <?php echo _("This contains personal information about yourself such as your name, your email address, etc.") ?>
            </td>
         </tr>   
      </table><br>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td bgcolor="<?php echo $color[9] ?>">
               <a href="options_highlight.php"><?php echo _("Message Highlighting"); ?></a>
            </td>
         </tr>
         <tr>
            <td bgcolor="<?php echo $color[0] ?>">
               <?php echo _("Based upon given criteria, incoming messages can have different background colors in the message list.  This helps to easily distinguish who the messages are from, especially for mailing lists.") ?>
            </td>
         </tr>   
      </table><br>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td bgcolor="<?php echo $color[9] ?>">
               <a href="options_order.php"><?php echo _("Index Order"); ?></a>
            </td>
         </tr>
         <tr>
            <td bgcolor="<?php echo $color[0] ?>">
               <?php echo _("The order of the message index can be rearanged and changed to contain the headers in any order you want.") ?>
            </td>
         </tr>   
      </table><br>
   </td>
   <td valign="top" width="50%">
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td bgcolor="<?php echo $color[9] ?>">
               <a href="options_display.php"><?php echo _("Display Preferences"); ?></a>
            </td>
         </tr>
         <tr>
            <td bgcolor="<?php echo $color[0] ?>">
               <?php echo _("You can change the way that SquirrelMail looks and displays information to you, such as the colors, the language, and other settings.") ?>
            </td>
         </tr>   
      </table><br>
      <table width="100%" cellpadding="3" cellspacing="0" border="0">
         <tr>
            <td bgcolor="<?php echo $color[9] ?>">
               <a href="options_folder.php"><?php echo _("Folder Preferences"); ?></a>
            </td>
         </tr>
         <tr>
            <td bgcolor="<?php echo $color[0] ?>">
               <?php echo _("These settings change the way your folders are displayed and manipulated.") ?>
            </td>
         </tr>   
      </table><br>
   </td>
</tr>
</table>
   <?php
      do_hook('options_link_and_description')
   ?>
</body></html>
