<?php
   /**
    **  options_display.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all optinos about display preferences
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include('../functions/display_messages.php');
   include('../functions/imap.php');
   include('../functions/array.php');
   include('../functions/plugin.php');
   
   displayPageHeader($color, 'None');
   $chosen_language = getPref($data_dir, $username, 'language');  
?>
   <br>
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
<tr><td bgcolor="<?php echo $color[0] ?>" align="center">

   <b><?php echo _("Options") . ' - ' . _("Display Preferences"); ?></b><br>

   <table width="100%" border="0" cellpadding="1" cellspacing="1">
   <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name="f" action="options.php" method="post"><br>
      <table width="100%" cellpadding="2" cellspacing="0" border="0">
         <tr>
            <td align="right" nowrap><?php echo _("Theme"); ?>:
            </td><td>
<?php
   echo '         <tt><select name="chosentheme">' . "\n";
   for ($i = 0; $i < count($theme); $i++) {
      if ($theme[$i]['PATH'] == $chosen_theme)
         echo '         <option selected value="'.$theme[$i]['PATH'].'">'.$theme[$i]['NAME']."\n";
      else
         echo '         <option value="'.$theme[$i]['PATH'].'">'.$theme[$i]['NAME']."\n";
   }
   echo '         </select></tt>';  
?>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?php echo _("Language"); ?>:
            </td><td>
<?php
   echo '         <tt><select name="language">' . "\n";
   foreach ($languages as $code => $name) {
      if (! isset($name['ALIAS'])) {
         if ($code==$chosen_language)
            echo '         <OPTION SELECTED VALUE="'.$code.'">'.$name['NAME']."\n";
         else
            echo '         <OPTION VALUE="'.$code.'">'.$name['NAME']."\n";
      }
   }
   echo '         </select></tt>';  
   if (! $use_gettext)
      echo '<br><small>This system doesn\'t support multiple languages</small>';
      
?>
            </td>
         <tr>
            <td align=right nowrap>&nbsp;
               <?php echo _("Use Javascript or HTML addressbook?") . '</td><td>'; 
               if ($use_javascript_addr_book == true) {
                  echo '         <input type="radio" name="javascript_abook" value="1" checked> ' . _("JavaScript") . '&nbsp;&nbsp;&nbsp;&nbsp;';
                  echo '         <input type="radio" name="javascript_abook" value="0"> ' . _("HTML"); 
               } else {
                  echo '         <input type="radio" name="javascript_abook" value="1"> ' . _("JavaScript") . '&nbsp;&nbsp;&nbsp;&nbsp;';
                  echo '         <input type="radio" name="javascript_abook" value="0" checked> ' . _("HTML"); 
               }  
               ?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Number of Messages to Index"); ?>:
            </td><td>
<?php
   if (isset($show_num))
      echo '         <tt><input type="text" size="5" name="shownum" value="'.$show_num.'"></tt><br>';
   else
      echo '         <tt><input type="text" size="5" name="shownum" value="25"></tt><br>'; 
?>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?php echo _("Wrap incoming text at"); ?>:
            </td><td>
<?php
   if (isset($wrap_at))
      echo '         <tt><input type="text" size="5" name="wrapat" value="'.$wrap_at.'"></tt><br>';
   else
      echo '         <tt><input type="tex" size="5" name="wrapat" value="86"></tt><br>'; 
?>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?php echo _("Size of editor window"); ?>:
            </td><td>
<?php
   if ($editor_size >= 10 && $editor_size <= 255)
      echo '         <tt><input type="text" size="5" name="editorsize" value="'.$editor_size.'"></tt><br>';
   else
      echo '         <tt><input type="text" size="5" name="editorsize" value="76"></tt><br>'; 
?>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?PHP echo _("Location of buttons when composing") ?>:</td>
            <td><select name="button_new_location">
                <option value="top"<?PHP
                    if ($location_of_buttons == 'top') echo ' SELECTED';
                    ?>><?PHP echo _("Before headers"); ?></option>
                <option value="between"<?PHP
                    if ($location_of_buttons == 'between') echo ' SELECTED';
                    ?>><?PHP echo _("Between headers and message body"); ?></option>
                <option value="bottom"<?PHP
                    if ($location_of_buttons == 'bottom') echo ' SELECTED';
                    ?>><?PHP echo _("After message body"); ?></option>
                </select>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?PHP echo _("Location of folder list") ?>:</td>
            <td><select name="folder_new_location">
                <option value="left"<?PHP
                    if ($location_of_bar != 'right') echo ' SELECTED';
                    ?>><?PHP echo _("Left"); ?></option>
                <option value="right"<?PHP
                    if ($location_of_bar == 'right') echo ' SELECTED';
                    ?>><?PHP echo _("Right"); ?></option>
                </select>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Width of folder list"); ?>:
            </td><td>
<?php
   echo '         <select name="leftsize">' . "\n";
   for ($i = 100; $i <= 300; $i += 10)
   {
       if ($left_size >= $i && $left_size < $i + 10)
          echo "<option value=\"$i\" selected>$i pixels\n";
       else
          echo "<option value=\"$i\">$i pixels\n";
   }
   echo '         </select>';  
?>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?php echo _("Auto refresh folder list"); ?>:
            </td><td>
<?php
   $seconds_str = _("Seconds");
   $none_str = _("None");
   $minute_str = _("Minute");
   $minutes_str = _("Minutes");

   echo '               <SELECT name="leftrefresh">';
   
   if ($left_refresh == '')
      $left_refresh = 'None';
   if ($left_refresh > 600)
      $left_refresh = 600;
   RefreshOption($left_refresh, '', 'Never', _("Never"));
   RefreshOption($left_refresh, 30);
   RefreshOption($left_refresh, 60);
   RefreshOption($left_refresh, 120);
   RefreshOption($left_refresh, 180);
   RefreshOption($left_refresh, 300);
   RefreshOption($left_refresh, 600);
   // Refreshes after the session auto-timeout (default 15 min) is pointless

function RefreshOption(&$current, $val, $str = '') {
   static $lastVal = 0;
   
   if (is_int($val) && is_int($current)) {
      if ($current > $lastVal && $current <= $val)
         $current = $val;
   }
   
   if ($str == '') {
      if ($val > 60) {
         $str = ($val / 60) . ' ' . _("Minutes");
      } elseif ($val == 60) {
         $str = '1 ' . _("Minute");
      } else {
         $str = $val . ' ' . _("Seconds");
      }
   }
   
   echo '<option value="' . $val . '"';
   if ($val == $current)
      echo ' SELECTED';
   echo '>' . $str . "\n";
}
      echo '               </SELECT>'; 
?>
            </td>
         </tr>
         <tr>
            <td align="right">
                <?php echo _("Use alternating row colors?") ?>
            </td><td>
<?php
    if (isset($alt_index_colors) && $alt_index_colors == 1) {
        $a = " checked";
        $b = "";
    } else {
        $a = "";
        $b = " checked";
    }
?>
                <input type="radio" name="altIndexColors" value="1"<?php echo $a ?>> <?php echo _("Yes") ?> &nbsp;&nbsp; 
                <input type="radio" name="altIndexColors" value="0"<?php echo $b ?>> <?php echo _("No") ?><br>
            </td>
         </tr>
         <tr>
            <td align=right>
               <?php echo _("Show HTML version by default"); ?>:
            </td>
            <td>
               <input type=checkbox name=showhtmldefault <?php 
	       if (isset($show_html_default) && $show_html_default) 
	       echo " checked"; ?>>
	         <?php 
echo _("Yes, show me the HTML version of a mail message, if it is available."); 
                 ?>
            </td>
         </tr>
         <tr>
            <td align=right>
               <?php echo _("Include Self"); ?>:
            </td>
            <td>
               <input type=checkbox name=includeselfreplyall <?php 
	       if (getPref($data_dir, $username, 'include_self_reply_all')
	           != '')
	       echo " checked"; ?>>
	         <?php 
echo _("Don't remove me from the CC addresses when I use \"Reply All\"");
                 ?>
            </td>
         </tr>
         <?php do_hook('options_display_inside'); ?>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="<?php echo _("Submit"); ?>"name="submit_display">
            </td>
         </tr>
      </table>
   </form>

   <?php do_hook('options_display_bottom'); ?>

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>
