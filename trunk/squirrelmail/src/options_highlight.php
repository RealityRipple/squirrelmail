<?php

/**
 * options_highlight.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays message highlighting options
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/display_messages.php');
require_once('../functions/imap.php');
require_once('../functions/array.php');
require_once('../functions/plugin.php');
require_once('../functions/strings.php');

function oh_opt( $val, $sel, $tit ) {
    echo "<option value=\"$val\"";
    if ( $sel )
        echo ' selected';
    echo  ">$tit</option>\n";
}

if (! isset($action)) {
    $action = '';
}
if (! isset($message_highlight_list)) {
    $message_highlight_list = array();
}

if ($action == 'delete' && isset($theid)) {
    removePref($data_dir, $username, "highlight$theid");
    header( "Location: $SCRIPT_NAME" );
    exit;
} else if ($action == 'save') {
    if (!$theid) $theid = 0;
    $identname = str_replace(',', ' ', $identname);
    if ($color_type == 1) $newcolor = $newcolor_choose;
    elseif ($color_type == 2) $newcolor = $newcolor_input;
    else $newcolor = $color_type;

    $newcolor = str_replace(',', '', $newcolor);
    $newcolor = str_replace('#', '', $newcolor);
    $newcolor = str_replace('"', '', $newcolor);
    $newcolor = str_replace('\'', '', $newcolor);
    $value = str_replace(',', ' ', $value);

    setPref($data_dir, $username, "highlight$theid", $identname.','.$newcolor.','.$value.','.$match_type);
    $message_highlight_list[$theid]['name'] = $identname;
    $message_highlight_list[$theid]['color'] = $newcolor;
    $message_highlight_list[$theid]['value'] = $value;
    $message_highlight_list[$theid]['match_type'] = $match_type;
}
displayPageHeader($color, 'None');
?>
<br>
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0"><tr><td bgcolor="<?php echo $color[0] ?>">
    <center><b><?php echo _("Options") . ' - ' . _("Message Highlighting"); ?></b></center>
</td></tr></table>

<?php
echo '<br><center>[<a href="options_highlight.php?action=add">' . _("New") . '</a>]'.
        ' - [<a href="options.php">'._("Done").'</a>]</center><br>'."\n";
if (count($message_highlight_list) >= 1) {
    echo '<table border="0" cellpadding="3" cellspacing="0" align="center" width="80%">'."\n";
    for ($i=0; $i < count($message_highlight_list); $i++) {
        echo '<tr bgcolor="' . $message_highlight_list[$i]['color'] . '">'.
                "<td width=\"20%\" bgcolor=\"$color[4]\" nowrap>".
                "<small>[<a href=\"options_highlight.php?action=edit&amp;theid=$i\">".
            _("Edit") .
            '</a>]&nbsp;[<a href="options_highlight.php?action=delete&amp;theid='.
            $i . '">' . _("Delete") . '</a>]</small>'.
            '</td><td>'.
            htmlspecialchars($message_highlight_list[$i]['name']) .
            '</td><td>'.
            $message_highlight_list[$i]['match_type'] . ' = ' .
            htmlspecialchars($message_highlight_list[$i]['value']).
            '</td></tr>';
    }
    echo "</table>\n".
        "<br>\n";
} else {
    echo '<center>' . _("No highlighting is defined") . "</center><br>\n".
        "<br>\n";
}
if ($action == 'edit' || $action == 'add') {
    if (!isset($theid))
    {
        $theid = count($message_highlight_list);
        $message_highlight_list[$theid] = array();
    }

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

    # helpful color chart from http://www.visibone.com/colorlab/big.html
    $new_color_list["0,0"] = 'cccccc';
    $new_color_list["0,1"] = '999999';
    $new_color_list["0,2"] = '666666';
    $new_color_list["0,3"] = '333333';
    $new_color_list["0,4"] = '000000';

    # red
    $new_color_list["1,0"] = 'ff0000';
    $new_color_list["1,1"] = 'cc0000';
    $new_color_list["1,2"] = '990000';
    $new_color_list["1,3"] = '660000';
    $new_color_list["1,4"] = '330000';

    $new_color_list["2,0"] = 'ffcccc';
    $new_color_list["2,1"] = 'cc9999';
    $new_color_list["2,2"] = '996666';
    $new_color_list["2,3"] = '663333';
    $new_color_list["2,4"] = '330000';

    $new_color_list["3,0"] = 'ffcccc';
    $new_color_list["3,1"] = 'ff9999';
    $new_color_list["3,2"] = 'ff6666';
    $new_color_list["3,3"] = 'ff3333';
    $new_color_list["3,4"] = 'ff0000';

    # green
    $new_color_list["4,0"] = '00ff00';
    $new_color_list["4,1"] = '00cc00';
    $new_color_list["4,2"] = '009900';
    $new_color_list["4,3"] = '006600';
    $new_color_list["4,4"] = '003300';

    $new_color_list["5,0"] = 'ccffcc';
    $new_color_list["5,1"] = '99cc99';
    $new_color_list["5,2"] = '669966';
    $new_color_list["5,3"] = '336633';
    $new_color_list["5,4"] = '003300';

    $new_color_list["6,0"] = 'ccffcc';
    $new_color_list["6,1"] = '99ff99';
    $new_color_list["6,2"] = '66ff66';
    $new_color_list["6,3"] = '33ff33';
    $new_color_list["6,4"] = '00ff00';

    # blue
    $new_color_list["7,0"] = '0000ff';
    $new_color_list["7,1"] = '0000cc';
    $new_color_list["7,2"] = '000099';
    $new_color_list["7,3"] = '000066';
    $new_color_list["7,4"] = '000033';

    $new_color_list["8,0"] = 'ccccff';
    $new_color_list["8,1"] = '9999cc';
    $new_color_list["8,2"] = '666699';
    $new_color_list["8,3"] = '333366';
    $new_color_list["8,4"] = '000033';

    $new_color_list["9,0"] = 'ccccff';
    $new_color_list["9,1"] = '9999ff';
    $new_color_list["9,2"] = '6666ff';
    $new_color_list["9,3"] = '3333ff';
    $new_color_list["9,4"] = '0000ff';

    # yellow
    $new_color_list["10,0"] = 'ffff00';
    $new_color_list["10,1"] = 'cccc00';
    $new_color_list["10,2"] = '999900';
    $new_color_list["10,3"] = '666600';
    $new_color_list["10,4"] = '333300';

    $new_color_list["11,0"] = 'ffffcc';
    $new_color_list["11,1"] = 'cccc99';
    $new_color_list["11,2"] = '999966';
    $new_color_list["11,3"] = '666633';
    $new_color_list["11,4"] = '333300';

    $new_color_list["12,0"] = 'ffffcc';
    $new_color_list["12,1"] = 'ffff99';
    $new_color_list["12,2"] = 'ffff66';
    $new_color_list["12,3"] = 'ffff33';
    $new_color_list["12,4"] = 'ffff00';

    # cyan
    $new_color_list["13,0"] = '00ffff';
    $new_color_list["13,1"] = '00cccc';
    $new_color_list["13,2"] = '009999';
    $new_color_list["13,3"] = '006666';
    $new_color_list["13,4"] = '003333';

    $new_color_list["14,0"] = 'ccffff';
    $new_color_list["14,1"] = '99cccc';
    $new_color_list["14,2"] = '669999';
    $new_color_list["14,3"] = '336666';
    $new_color_list["14,4"] = '003333';

    $new_color_list["15,0"] = 'ccffff';
    $new_color_list["15,1"] = '99ffff';
    $new_color_list["15,2"] = '66ffff';
    $new_color_list["15,3"] = '33ffff';
    $new_color_list["15,4"] = '00ffff';

    # magenta
    $new_color_list["16,0"] = 'ff00ff';
    $new_color_list["16,1"] = 'cc00cc';
    $new_color_list["16,2"] = '990099';
    $new_color_list["16,3"] = '660066';
    $new_color_list["16,4"] = '330033';

    $new_color_list["17,0"] = 'ffccff';
    $new_color_list["17,1"] = 'cc99cc';
    $new_color_list["17,2"] = '996699';
    $new_color_list["17,3"] = '663366';
    $new_color_list["17,4"] = '330033';

    $new_color_list["18,0"] = 'ffccff';
    $new_color_list["18,1"] = 'ff99ff';
    $new_color_list["18,2"] = 'ff66ff';
    $new_color_list["18,3"] = 'ff33ff';
    $new_color_list["18,4"] = 'ff00ff';

    $selected_input = '';
    $selected_choose = '';

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
    else if ($selected_choose == '')
        $selected_input = ' checked';

    echo '<form name="f" action="options_highlight.php">' . "\n";
    echo '<input type="hidden" value="save" name="action">' . "\n";
    echo '<input type="hidden" value="'.$theid.'" name="theid">' . "\n";
    echo '<table width="80%" align="center" cellpadding="3" cellspacing="0" border="0">' . "\n";
    echo "   <tr bgcolor=\"$color[0]\">\n";
    echo "      <td align=\"right\" nowrap><b>\n";
    echo _("Identifying name") . ":";
    echo '      </b></td>' . "\n";
    echo '      <td>' . "\n";
    if (isset($message_highlight_list[$theid]['name']))
        $disp = $message_highlight_list[$theid]['name'];
    else
        $disp = '';
    $disp = htmlspecialchars($disp);
    echo "         <input type=\"text\" value=\"".$disp."\" name=\"identname\">";
    echo "      </td>\n";
    echo "   </tr>\n";
    echo '   <tr><td><small><small>&nbsp;</small></small></td></tr>' . "\n";
    echo "   <tr bgcolor=\"$color[0]\">\n";
    echo '      <td align="right"><b>' . "\n";
    echo _("Color") . ':';
    echo "      </b></td>\n";
    echo '      <td>' . "\n";
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

    # Show grid of color choices
    echo "<tr bgcolor=\"$color[0]\"><td colspan='2'>\n";
    echo "<table border=0 cellpadding=\"2\" align=\"center\" cellspacing=\"1\">\n";
    $current_color = $message_highlight_list[$theid]['color'];
    for($x = 0; $x < 5; $x++) {
        echo "<tr>\n";
        for($y = 0; $y < 19; $y++) {
        $gridindex = "$y,$x";
        $gridcolor = $new_color_list[$gridindex];
        $selected = ($gridcolor == $current_color)?'CHECKED':'' ;
        echo "<td bgcolor=\"#$gridcolor\"><input type='radio' name=color_type value='#$gridcolor' $selected>\n";
        echo "</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</td></tr>\n";

    echo '   <tr><td><small><small>&nbsp;</small></small></td></tr>' . "\n";
    echo "   <tr bgcolor=\"$color[0]\">\n";
    echo '      <td align="center" colspan=2>' . "\n";
    echo "         <select name=match_type>\n";
    oh_opt( 'from',
            ($message_highlight_list[$theid]['match_type'] == 'from'),
            _("From") );
    oh_opt( 'to',
            ($message_highlight_list[$theid]['match_type'] == 'to'),
            _("To") );
    oh_opt( 'cc',
            ($message_highlight_list[$theid]['match_type'] == 'cc'),
            _("Cc") );
    oh_opt( 'to_cc',
            ($message_highlight_list[$theid]['match_type'] == 'to_cc'),
            _("To or Cc") );
    oh_opt( 'subject',
            ($message_highlight_list[$theid]['match_type'] == 'subject'),
            _("Subject") );
    echo "         </select>\n";
    echo '<b>' . _("Matches") . ':</b> ';
    if (isset($message_highlight_list[$theid]['value']))
        $disp = $message_highlight_list[$theid]['value'];
    else
        $disp = '';
    $disp = htmlspecialchars($disp);
    echo '         <input type="text" value="' . $disp .
        '" name="value" size=40>';
    echo "        </td>\n";
    echo "   </tr>\n";
    echo "</table>\n";
    echo '<center><input type="submit" value="' . _("Submit") . "\"></center>\n";
    echo "</form>\n";
}
do_hook('options_highlight_bottom');
?>
</body></html>
