<?php
   /**
    **  options_highlight.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays message highlighting options
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
      include('../functions/plugin.php');

   if (! isset($action))
       $action = '';
   if (! isset($message_highlight_list))
       $message_highlight_list = array();

   if ($action == 'delete' && isset($theid)) {
      removePref($data_dir, $username, "highlight$theid");
   } else if ($action == 'save') {
      if (!$theid) $theid = 0;
      $identname = ereg_replace(',', ' ', $identname);
      $identname = str_replace('\\\\', '\\', $identname);
      $identname = str_replace('\\"', '"', $identname);
      $identname = str_replace('"', '&quot;', $identname);
      if ($color_type == 1) $newcolor = $newcolor_choose;
      else $newcolor = $newcolor_input;
 
      $newcolor = ereg_replace(',', '', $newcolor);
      $newcolor = ereg_replace('#', '', $newcolor);
      $newcolor = "$newcolor";
      $value = ereg_replace(',', ' ', $value);
      $value = str_replace('\\\\', '\\', $value);
      $value = str_replace('\\"', '"', $value);
      $value = str_replace('"', '&quot;', $value);

      setPref($data_dir, $username, "highlight$theid", $identname.','.$newcolor.','.$value.','.$match_type);
      $message_highlight_list[$theid]['name'] = $identname;
      $message_highlight_list[$theid]['color'] = $newcolor;
      $message_highlight_list[$theid]['value'] = $value;
      $message_highlight_list[$theid]['match_type'] = $match_type;
   } 
   include('../src/load_prefs.php');
   displayPageHeader($color, 'None');
?>
   <br>
   <table width="95%" align="center" border="0" cellpadding="2" cellspacing="0"><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . ' - ' . _("Message Highlighting"); ?></b></center>
   </td></tr></table>

<?php
   echo '<br><center>[<a href="options_highlight.php?action=add">' . _("New") . '</a>]';
   echo ' - [<a href="options.php">'._("Done").'</a>]</center><br>'."\n";
   if (count($message_highlight_list) >= 1) {
      echo '<table border="0" cellpadding="3" cellspacing="0" align="center" width="80%">'."\n";
      for ($i=0; $i < count($message_highlight_list); $i++) {
         echo "<tr>\n";
         echo '   <td width="1%" bgcolor="' . $color[4] . "\">\n";
         echo "<nobr><small>[<a href=\"options_highlight.php?action=edit&theid=$i\">" . _("Edit") . "</a>]&nbsp;[<a href=\"options_highlight.php?action=delete&theid=$i\">"._("Delete")."</a>]</small></nobr>\n";
         echo '   </td>';
         echo '   <td bgcolor="' . $message_highlight_list[$i]['color'] . "\">\n";
         echo '      ' . $message_highlight_list[$i]['name'];
         echo "   </td>\n";
         echo '   <td bgcolor="' . $message_highlight_list[$i]['color'] . "\">\n";
         echo '      '.$message_highlight_list[$i]['match_type'].' = ' . $message_highlight_list[$i]['value'];
         echo "   </td>\n";
         echo "</tr>\n";
      }
      echo "</table>\n";
      echo "<br>\n";
   } else {
      echo '<center>' . _("No highlighting is defined") . "</center><br>\n";
      echo "<br>\n";
   }
   if ($action == 'edit' || $action == 'add') {
      if (!isset($theid)) $theid = count($message_highlight_list);
          $message_highlight_list[$theid] = array();
 
      $color_list[0] = '4444aa';
      $color_list[1] = '44aa44';
      $color_list[2] = 'aaaa44';
      $color_list[3] = '44aaaa';
      $color_list[4] = 'aa44aa';
      $color_list[5] = 'aaaaff';
      $color_list[6] = 'aaffaa';
      $color_list[7] = 'ffffaa';
      $color_list[8] = 'aaffff';
      $color_list[9] = 'ffaaff';
      $color_list[10] = 'aaaaaa';
      $color_list[11] = 'bfbfbf';
      $color_list[12] = 'dfdfdf';
      $color_list[13] = 'ffffff';               
      
      $selected_input = '';
      
      for ($i=0; $i < 14; $i++) {
         ${"selected".$i} = '';
      }
      if (isset($message_highlight_list[$theid]['color'])) {
         for ($i=0; $i < 14; $i++) {
            if ($color_list[$i] == $message_highlight_list[$theid]['color']) {
               $selected_choose = ' checked';
               ${"selected".$i} = ' selected';
               continue;
            }
	     }
      }
      if (!isset($message_highlight_list[$theid]['color']))
         $selected_choose = ' checked';
      else if (!isset($selected_choose))
         $selected_input = ' checked';
 
      echo '<form name="f" action="options_highlight.php">' . "\n";
      echo '<input type="hidden" value="save" name="action">' . "\n";
      echo '<input type="hidden" value="'.$theid.'" name="theid">' . "\n";
      echo '<table width="80%" align="center" cellpadding="3" cellspacing="0" border="0">' . "\n";
      echo "   <tr bgcolor=\"$color[0]\">\n";
      echo "      <td align=\"right\" width=\"25%\"><b>\n";
      echo _("Identifying name") . ":";
      echo '      </b></td>' . "\n";
      echo '      <td width="75%">' . "\n";
      if (isset($message_highlight_list[$theid]['name']))
          $disp = $message_highlight_list[$theid]['name'];
      else
          $disp = '';
      $disp = str_replace('\\\\', '\\', $disp);
      $disp = str_replace('\\"', '"', $disp);
      $disp = str_replace('"', '&quot;', $disp);
      echo "         <input type=\"text\" value=\"".$disp."\" name=\"identname\">";
      echo "      </td>\n";
      echo "   </tr>\n";
      echo '   <tr><td><small><small>&nbsp;</small></small></td></tr>' . "\n";
      echo "   <tr bgcolor=\"$color[0]\">\n";
      echo '      <td align="right" width="25%"><b>' . "\n";
      echo _("Color") . ':';
      echo "      </b></td>\n";
      echo '      <td width="75%">' . "\n";
      echo "         <input type=\"radio\" name=color_type value=1$selected_choose> &nbsp;<select name=newcolor_choose>\n";
      echo "            <option value=\"$color_list[0]\"$selected0>" . _("Dark Blue") . "\n";
      echo "            <option value=\"$color_list[1]\"$selected1>" . _("Dark Green") . "\n";
      echo "            <option value=\"$color_list[2]\"$selected2>" . _("Dark Yellow") . "\n";
      echo "            <option value=\"$color_list[3]\"$selected3>" . _("Dark Cyan") . "\n";
      echo "            <option value=\"$color_list[4]\"$selected4>" . _("Dark Magenta") . "\n";
      echo "            <option value=\"$color_list[5]\"$selected5>" . _("Light Blue") . "\n";
      echo "            <option value=\"$color_list[6]\"$selected6>" . _("Light Green") . "\n";
      echo "            <option value=\"$color_list[7]\"$selected7>" . _("Light Yellow") . "\n";
      echo "            <option value=\"$color_list[8]\"$selected8>" . _("Light Cyan") . "\n";
      echo "            <option value=\"$color_list[9]\"$selected9>" . _("Light Magenta") . "\n";
      echo "            <option value=\"$color_list[10]\"$selected10>" . _("Dark Gray") . "\n";
      echo "            <option value=\"$color_list[11]\"$selected11>" . _("Medium Gray") . "\n";
      echo "            <option value=\"$color_list[12]\"$selected12>" . _("Light Gray") . "\n";
      echo "            <option value=\"$color_list[13]\"$selected13>" . _("White") . "\n";
      echo "         </select><br>\n";
      echo "         <input type=\"radio\" name=color_type value=2$selected_input> &nbsp;". _("Other:") ."<input type=\"text\" value=\"";
      if ($selected_input) echo $message_highlight_list[$theid]["color"];
      echo '" name="newcolor_input" size="7"> '._("Ex: 63aa7f")."<br>\n";
      echo "      </td>\n";
      echo "   </tr>\n";
      echo '   <tr><td><small><small>&nbsp;</small></small></td></tr>' . "\n";
      echo "   <tr bgcolor=\"$color[0]\">\n";
      echo '      <td align="right" width="25%"><b>' . "\n";
      echo _("Match") . ':';
      echo "      </b></td>\n";
      echo '      <td width="75%">' . "\n";
      echo "         <select name=match_type>\n";
      if (isset($message_highlight_list[$theid]['match_type']) && $message_highlight_list[$theid]['match_type'] == 'from')    echo "            <option value=\"from\" selected>From\n";
      else                                                         echo "            <option value=\"from\">From\n";
      if (isset($message_highlight_list[$theid]['match_type']) && $message_highlight_list[$theid]['match_type'] == 'to')      echo "            <option value=\"to\" selected>To\n";
      else                                                         echo "            <option value=\"to\">To\n";
      if (isset($message_highlight_list[$theid]['match_type']) && $message_highlight_list[$theid]['match_type'] == 'cc')      echo "            <option value=\"cc\" selected>Cc\n";
      else                                                         echo "            <option value=\"cc\">Cc\n";
      if (isset($message_highlight_list[$theid]['match_type']) && $message_highlight_list[$theid]['match_type'] == 'to_cc')   echo "            <option value=\"to_cc\" selected>To or Cc\n";
      else                                                         echo "            <option value=\"to_cc\">To or Cc\n";
      if (isset($message_highlight_list[$theid]['match_type']) && $message_highlight_list[$theid]['match_type'] == 'subject') echo "            <option value=\"subject\" selected>Subject\n";
      else                                                         echo "            <option value=\"subject\">Subject\n";
      echo "         </select>\n";
      if (isset($message_highlight_list[$theid]['value']))
          $disp = $message_highlight_list[$theid]['value'];
      else
          $disp = '';
      $disp = str_replace('\\\\', '\\', $disp);
      $disp = str_replace('\\"', '"', $disp);
      $disp = str_replace('"', '&quot;', $disp);
      echo '         <nobr><input type="text" value="'.$disp.'" name="value">';
      echo "        <nobr></td>\n";
      echo "   </tr>\n";
      echo "</table>\n";
      echo '<center><input type="submit" value="' . _("Submit") . "\"></center>\n";
      echo "</form>\n";
      do_hook('options_highlight_bottom');
   } 
?>
</body></html>
