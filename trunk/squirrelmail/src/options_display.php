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
   if (!isset($plugin_php))
      include('../functins/plugin.php');

   include('../src/load_prefs.php');
   displayPageHeader($color, 'None');
   $chosen_language = getPref($data_dir, $username, 'language');  
?>
   <br>
   <table width="95%" align="center" border="0" cellpadding="2" cellspacing="0"><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . ' - ' . _("Display Preferences"); ?></b></center>
   </td></tr></table>

   <form name="f" action="options.php" method="post">
      <table width="100%" cellpadding="0" cellspacing="2" border="0">
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
            <td valign="top" align="right" nowrap><?php echo _("Language"); ?>:
            </td><td>
<?php
   echo '         <tt><select name="language">' . "\n";
   foreach ($languages as $code => $name) {
      if ($code==$chosen_language)
         echo '         <OPTION SELECTED VALUE="'.$code.'">'.$languages[$code]['NAME']."\n";
      else
         echo '         <OPTION VALUE=\"".$code.'">'.$languages[$code]['NAME']."\n";
   }
   echo '         </select></tt>';  
   if (! $use_gettext)
      echo '<br><small>This system doesn't support multiple languages</small>';
      
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
            <td align=right nowrap><?php echo _("Width of folder list"); ?>:
            </td><td>
<?php
   echo '         <select name="leftsize">' . "\n";
   if ($left_size == 100)
      echo "<option value=100 selected>100 pixels\n";
   else
      echo "<option value=100>100 pixels\n";
 
   if ($left_size == 125)
      echo "<option value=125 selected>125 pixels\n";
   else
      echo "<option value=125>125 pixels\n";
 
   if ($left_size == 150)
      echo "<option value=150 selected>150 pixels\n";
   else
      echo "<option value=150>150 pixels\n";
 
   if ($left_size == 175)
      echo "<option value=175 selected>175 pixels\n";
   else
      echo "<option value=175>175 pixels\n";
 
   if (($left_size == 200) || ($left_size == ""))
      echo "<option value=200 selected>200 pixels\n";
   else
      echo "<option value=200>200 pixels\n";
 
   if (($left_size == 225))
      echo "<option value=225 selected>225 pixels\n";
   else
      echo "<option value=225>225 pixels\n";
 
   if (($left_size == 250))
      echo "<option value=250 selected>250 pixels\n";
   else
      echo "<option value=250>250 pixels\n";
 
   if ($left_size == 275)
      echo "<option value=275 selected>275 pixels\n";
   else
      echo "<option value=275>275 pixels\n";
 
   if (($left_size == 300))
      echo "<option value=300 selected>300 pixels\n";
   else
      echo "<option value=300>300 pixels\n";
 
   echo "         </select>";  
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Auto refresh folder list"); ?>:
            </td><td>
<?php
   $seconds_str = _("Seconds");
   $none_str = _("None");
   $minute_str = _("Minute");
   $minutes_str = _("Minutes");

   echo "               <SELECT name=leftrefresh>";
   if (($left_refresh == "None") || ($left_refresh == ""))
      echo "                  <OPTION VALUE=None SELECTED>$none_str";
   else
      echo "                  <OPTION VALUE=None>$none_str";
 
   if (($left_refresh == "10"))
      echo "                  <OPTION VALUE=10 SELECTED>10 $seconds_str";
   else
      echo "                  <OPTION VALUE=10>10 $seconds_str";
 
   if (($left_refresh == "20"))
      echo "                  <OPTION VALUE=20 SELECTED>20 $seconds_str";
   else
      echo "                  <OPTION VALUE=20>20 $seconds_str";
 
   if (($left_refresh == "30"))
      echo "                  <OPTION VALUE=30 SELECTED>30 $seconds_str";
   else
      echo "                  <OPTION VALUE=30>30 $seconds_str";
 
   if (($left_refresh == "60"))
      echo "                  <OPTION VALUE=60 SELECTED>1 $minute_str";
   else
      echo "                  <OPTION VALUE=60>1 $minute_str";
 
   if (($left_refresh == "120"))
      echo "                  <OPTION VALUE=120 SELECTED>2 $minutes_str";
   else
      echo "                  <OPTION VALUE=120>2 $minutes_str";
 
   if (($left_refresh == "180"))
      echo "                  <OPTION VALUE=180 SELECTED>3 $minutes_str";
   else
      echo "                  <OPTION VALUE=180>3 $minutes_str";
 
   if (($left_refresh == "240"))
      echo "                  <OPTION VALUE=240 SELECTED>4 $minutes_str";
   else
      echo "                  <OPTION VALUE=240>4 $minutes_str";
 
   if (($left_refresh == "300"))
      echo "                  <OPTION VALUE=300 SELECTED>5 $minutes_str";
   else
      echo "                  <OPTION VALUE=300>5 $minutes_str";
 
   if (($left_refresh == "420"))
      echo "                  <OPTION VALUE=420 SELECTED>7 $minutes_str";
   else
      echo "                  <OPTION VALUE=420>7 $minutes_str";

   if (($left_refresh == "600"))
      echo "                  <OPTION VALUE=600 SELECTED>10 $minutes_str";
   else
      echo "                  <OPTION VALUE=600>10 $minutes_str";
 
   if (($left_refresh == "720"))
      echo "                  <OPTION VALUE=720 SELECTED>12 $minutes_str";
   else
      echo "                  <OPTION VALUE=720>12 $minutes_str";
 
   if (($left_refresh == "900"))
      echo "                  <OPTION VALUE=900 SELECTED>15 $minutes_str";
   else
      echo "                  <OPTION VALUE=900>15 $minutes_str";
 
   if (($left_refresh == "1200"))
      echo "                  <OPTION VALUE=1200 SELECTED>20 $minutes_str";
   else
      echo "                  <OPTION VALUE=1200>20 $minutes_str";
 
   if (($left_refresh == "1500"))
      echo "                  <OPTION VALUE=1500 SELECTED>25 $minutes_str";
   else
      echo "                  <OPTION VALUE=1500>25 $minutes_str";
 
   if (($left_refresh == "1800"))
      echo "                  <OPTION VALUE=1800 SELECTED>30 $minutes_str";
   else
      echo "                  <OPTION VALUE=1800>30 $minutes_str";
 
      echo '               </SELECT>'; 
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
</body></html>
