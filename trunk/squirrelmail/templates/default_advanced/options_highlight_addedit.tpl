<?php
/**
 * options_highlight_addedit.tpl
 *
 * Template for adding new rules and editing existing rules
 * 
 * The following variables are available in this template:
 *      $rule_name      - The name of this rule.  Blank if not given.
 *      $rule_field     - The field being matched by the rule
 *      $rule_value     - The value being matched by the rule
 *      $rule_color     - The color to shade a match
 *      $color_radio    - integer Identifier as to which radio button should be
 *                        selected by default.  Will be 1 if the drop-down is
 *                        selected, 2 if "other" is selected, or 0 if one of the
 *                        other colors is selected
 *      $color_input    - default value for the "other" input field.  Will be
 *                        blank if not used.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<script type="text/javascript">
<!--
function setSampleColor(color) {
    s = document.getElementById('colorSample');
    i = document.getElementById('newcolor_input');
    if (color=='' || color=='#') {
        if (typeof(window.opera) == 'undefined' && typeof(s.setAttribute) != 'undefined' && s.getAttribute('bgColor') ) {
            s.setAttribute('bgcolor', '#ffffff');
        } else {
            s.style.backgroundColor = '#ffffff';
        }
        i.value = '';
    } else {
        if (typeof(window.opera) == 'undefined' && typeof(s.setAttribute) != 'undefined' && s.getAttribute('bgColor') ) {
            s.setAttribute('bgcolor', color);
        } else {
            s.style.backgroundColor = color;
        }

        str = color.toLowerCase();
        while (str.match(/[^0-9a-f]/)) {
            str = str.replace(/[^0-9]/, '');
        }
        i.value = str.toUpperCase();
    }
}

function sampleColor (thecell) {
    if (typeof(window.opera) == 'undefined' && typeof(thecell.getAttribute) != 'undefined' && thecell.getAttribute('bgColor') ) {
        color = thecell.getAttribute('bgColor');
    } else {
        color = thecell.style.backgroundColor;
    }
    document.getElementById('newcolor_choose').selectedIndex = 0;
    setSampleColor(color);
}

function selectColor () {
    el = document.getElementById('newcolor_choose');
    color = el.options[el.selectedIndex].value;
    setSampleColor('#'+color);
}

function inputColor () {
    val = document.getElementById('newcolor_input').value;
    
    str = val.toLowerCase();
    while (str.match(/[^0-9a-f]/)) {
        str = str.replace(/[^0-9a-f]/, '');
    }
    document.getElementById('newcolor_choose').selectedIndex = 0;
    setSampleColor('#'+str)
}
-->
</script>
<br />
<div id="optionHighlightAdd">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header1">
   <?php echo _("Add/Edit") .' '. _("Message Highlighting"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0" class="table1">
    <tr>
     <td class="ruleField">
      <?php echo _("Identifying Name"); ?>:
     </td>
     <td class="fieldValue" colspan="3">
      <input type="text" name="identname" value=<?php echo '"'.$rule_name.'"'; ?> />
     </td>
    </tr>
    <tr>
     <td class="ruleField">
      <select name="match_type">
       <option value="from" <?php if ($rule_field=='from') echo 'selected="selected"'; ?>> <?php echo _("From"); ?> </option>
       <option value="to" <?php if ($rule_field=='to') echo 'selected="selected"'; ?>> <?php echo _("To"); ?> </option>
       <option value="cc" <?php if ($rule_field=='cc') echo 'selected="selected"'; ?>> <?php echo _("Cc"); ?> </option>
       <option value="to_cc" <?php if ($rule_field=='to_cc') echo 'selected="selected"'; ?>> <?php echo _("To or cc"); ?> </option>
       <option value="subject" <?php if ($rule_field=='subject') echo 'selected="selected"'; ?>> <?php echo _("Subject"); ?> </option>
      </select>
      <?php echo _("Matches"); ?>:
     </td>
     <td class="fieldValue" colspan="3">
      <input type="text" name="value" value=<?php echo '"'.$rule_value.'"'; ?> size="40" />
     </td>
    </tr>
    <tr>
     <td class="ruleField">
      <?php echo _("Color"); ?>:
     </td>
     <td class="fieldValue" style="width:10%">
      <input type="hidden" name="color_type" value="2" />
      <input type="text" name="newcolor_input" value="<?php echo $rule_color; ?>" size="7" id="newcolor_input" onblur="inputColor()" />
     </td>
     <td id="colorSample" bgcolor="#<?php echo $rule_color; ?>">
         &nbsp;
     </td>
     <td>
     </td>
    </tr>
    <tr>
     <td>
      &nbsp;
     </td>
     <td colspan="3" class="fieldValue">
      - <?php echo _("OR"); ?> -
      <br />
      <select id="newcolor_choose"  onchange="selectColor()">
       <option value=""></option>
       <option value="4444aa">Dark Blue</option>
       <option value="44aa44">Dark Green</option>
       <option value="aaaa44">Dark Yellow</option>
       <option value="44aaaa">Dark Cyan</option>
       <option value="aa44aa">Dark Magenta</option>
       <option value="aaaaff">Light Blue</option>
       <option value="aaffaa">Light Green</option>
       <option value="ffffaa">Light Yellow</option>
       <option value="aaffff">Light Cyan</option>
       <option value="ffaaff">Light Magenta</option>
       <option value="aaaaaa">Dark Gray</option>
       <option value="bfbfbf">Medium Gray</option>
       <option value="dfdfdf">Light Gray</option>
       <option value="ffffff">White</option>
      </select>
      <br />
      - <?php echo _("OR"); ?> -
      <br />
      <table cellspacing="0" class="colorTable">
       <tr>
        <td bgcolor="#cccccc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff0000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffcccc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffcccc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00ff00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccffcc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccffcc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#0000ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccccff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccccff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffff00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffffcc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffffcc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00ffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ccffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff00ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffccff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffccff" onclick="javascript:sampleColor(this)">&nbsp;</td>
       </tr>
       <tr>
        <td bgcolor="#999999" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cc0000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cc9999" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff9999" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00cc00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#99cc99" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#99ff99" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#0000cc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#9999cc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#9999ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cccc00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cccc99" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffff99" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00cccc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#99cccc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#99ffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cc00cc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#cc99cc" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff99ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
       </tr>
       <tr>
        <td bgcolor="#666666" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#990000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#996666" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff6666" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#009900" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#669966" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#66ff66" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#000099" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#666699" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#6666ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#999900" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#999966" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffff66" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#009999" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#669999" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#66ffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#990099" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#996699" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff66ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
       </tr>
       <tr>
        <td bgcolor="#333333" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#660000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#663333" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff3333" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#006600" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#336633" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#33ff33" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#000066" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#333366" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#3333ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#666600" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#666633" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffff33" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#006666" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#336666" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#33ffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#660066" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#663366" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff33ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
       </tr>
       <tr>
        <td bgcolor="#000000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#330000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#330000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff0000" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#003300" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#003300" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00ff00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#000033" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#000033" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#0000ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#333300" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#333300" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ffff00" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#003333" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#003333" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#00ffff" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#330033" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#330033" onclick="javascript:sampleColor(this)">&nbsp;</td>
        <td bgcolor="#ff00ff" onclick="javascript:sampleColor(this)">&nbsp;</td>
       </tr>
      </table>
     </td>
    </tr>
    <tr>
     <td colspan="4">
      <input type="reset" value="<?php echo _("Reset"); ?>" onclick="setSampleColor('#'+document.getElementById('newcolor_input').defaultValue)" />
      <input type="submit" value="<?php echo _("Save Changes"); ?>" />
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</div>