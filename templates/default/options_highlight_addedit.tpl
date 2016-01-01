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
<style type="text/css">
<!--
td.color_cccccc   { background: #cccccc; }
td.color_999999   { background: #999999; }
td.color_666666   { background: #666666; }
td.color_333333   { background: #333333; }
td.color_000000   { background: #000000; }
td.color_ff0000   { background: #ff0000; }
td.color_cc0000   { background: #cc0000; }
td.color_990000   { background: #990000; }
td.color_660000   { background: #660000; }
td.color_330000   { background: #330000; }
td.color_ffcccc   { background: #ffcccc; }
td.color_cc9999   { background: #cc9999; }
td.color_996666   { background: #996666; }
td.color_663333   { background: #663333; }
td.color_330000   { background: #330000; }
td.color_ffcccc   { background: #ffcccc; }
td.color_ff9999   { background: #ff9999; }
td.color_ff6666   { background: #ff6666; }
td.color_ff3333   { background: #ff3333; }
td.color_ff0000   { background: #ff0000; }
td.color_00ff00   { background: #00ff00; }
td.color_00cc00   { background: #00cc00; }
td.color_009900   { background: #009900; }
td.color_006600   { background: #006600; }
td.color_003300   { background: #003300; }
td.color_ccffcc   { background: #ccffcc; }
td.color_99cc99   { background: #99cc99; }
td.color_669966   { background: #669966; }
td.color_336633   { background: #336633; }
td.color_003300   { background: #003300; }
td.color_ccffcc   { background: #ccffcc; }
td.color_99ff99   { background: #99ff99; }
td.color_66ff66   { background: #66ff66; }
td.color_33ff33   { background: #33ff33; }
td.color_00ff00   { background: #00ff00; }
td.color_0000ff   { background: #0000ff; }
td.color_0000cc   { background: #0000cc; }
td.color_000099   { background: #000099; }
td.color_000066   { background: #000066; }
td.color_000033   { background: #000033; }
td.color_ccccff   { background: #ccccff; }
td.color_9999cc   { background: #9999cc; }
td.color_666699   { background: #666699; }
td.color_333366   { background: #333366; }
td.color_000033   { background: #000033; }
td.color_ccccff   { background: #ccccff; }
td.color_9999ff   { background: #9999ff; }
td.color_6666ff   { background: #6666ff; }
td.color_3333ff   { background: #3333ff; }
td.color_0000ff   { background: #0000ff; }
td.color_ffff00   { background: #ffff00; }
td.color_cccc00   { background: #cccc00; }
td.color_999900   { background: #999900; }
td.color_666600   { background: #666600; }
td.color_333300   { background: #333300; }
td.color_ffffcc   { background: #ffffcc; }
td.color_cccc99   { background: #cccc99; }
td.color_999966   { background: #999966; }
td.color_666633   { background: #666633; }
td.color_333300   { background: #333300; }
td.color_ffffcc   { background: #ffffcc; }
td.color_ffff99   { background: #ffff99; }
td.color_ffff66   { background: #ffff66; }
td.color_ffff33   { background: #ffff33; }
td.color_ffff00   { background: #ffff00; }
td.color_00ffff   { background: #00ffff; }
td.color_00cccc   { background: #00cccc; }
td.color_009999   { background: #009999; }
td.color_006666   { background: #006666; }
td.color_003333   { background: #003333; }
td.color_ccffff   { background: #ccffff; }
td.color_99cccc   { background: #99cccc; }
td.color_669999   { background: #669999; }
td.color_336666   { background: #336666; }
td.color_003333   { background: #003333; }
td.color_ccffff   { background: #ccffff; }
td.color_99ffff   { background: #99ffff; }
td.color_66ffff   { background: #66ffff; }
td.color_33ffff   { background: #33ffff; }
td.color_00ffff   { background: #00ffff; }
td.color_ff00ff   { background: #ff00ff; }
td.color_cc00cc   { background: #cc00cc; }
td.color_990099   { background: #990099; }
td.color_660066   { background: #660066; }
td.color_330033   { background: #330033; }
td.color_ffccff   { background: #ffccff; }
td.color_cc99cc   { background: #cc99cc; }
td.color_996699   { background: #996699; }
td.color_663366   { background: #663366; }
td.color_330033   { background: #330033; }
td.color_ffccff   { background: #ffccff; }
td.color_ff99ff   { background: #ff99ff; }
td.color_ff66ff   { background: #ff66ff; }
td.color_ff33ff   { background: #ff33ff; }
td.color_ff00ff   { background: #ff00ff; }
-->
</style>
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
     <td colspan="2" class="header2">
      <?php echo _("Rule Conditions"); ?>
     </td>
    </tr>
    <tr>
     <td class="ruleField">
      <?php echo _("Identifying Name"); ?>:
     </td>
     <td class="fieldValue">
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
     <td class="fieldValue">
      <input type="text" name="value" value=<?php echo '"'.$rule_value.'"'; ?> size="40" />
     </td>
    </tr>
   </table>
   <table cellspacing="0" class="table1">
    <tr>
     <td colspan="2" class="header2">
      <?php echo _("Color"); ?>
     </td>
    </tr>
    <tr>
     <td class="ruleField">
      <input type="radio" name="color_type" value="1" id="color_type1" <?php if ($color_radio==1) echo 'checked="checked"'; ?> />
     </td>
     <td class="fieldValue">
      <select name="newcolor_choose">
       <option value="4444aa" <?php if ($rule_color=='4444aa') echo 'selected="selected"'; ?>>Dark Blue</option>
       <option value="44aa44" <?php if ($rule_color=='44aa44') echo 'selected="selected"'; ?>>Dark Green</option>
       <option value="aaaa44" <?php if ($rule_color=='aaaa44') echo 'selected="selected"'; ?>>Dark Yellow</option>
       <option value="44aaaa" <?php if ($rule_color=='44aaaa') echo 'selected="selected"'; ?>>Dark Cyan</option>
       <option value="aa44aa" <?php if ($rule_color=='aa44aa') echo 'selected="selected"'; ?>>Dark Magenta</option>
       <option value="aaaaff" <?php if ($rule_color=='aaaaff') echo 'selected="selected"'; ?>>Light Blue</option>
       <option value="aaffaa" <?php if ($rule_color=='aaffaa') echo 'selected="selected"'; ?>>Light Green</option>
       <option value="ffffaa" <?php if ($rule_color=='ffffaa') echo 'selected="selected"'; ?>>Light Yellow</option>
       <option value="aaffff" <?php if ($rule_color=='aaffff') echo 'selected="selected"'; ?>>Light Cyan</option>
       <option value="ffaaff" <?php if ($rule_color=='ffaaff') echo 'selected="selected"'; ?>>Light Magenta</option>
       <option value="aaaaaa" <?php if ($rule_color=='aaaaaa') echo 'selected="selected"'; ?>>Dark Gray</option>
       <option value="bfbfbf" <?php if ($rule_color=='bfbfbf') echo 'selected="selected"'; ?>>Medium Gray</option>
       <option value="dfdfdf" <?php if ($rule_color=='dfdfdf') echo 'selected="selected"'; ?>>Light Gray</option>
       <option value="ffffff" <?php if ($rule_color=='ffffff') echo 'selected="selected"'; ?>>White</option>
      </select>
     </td>
    </tr>
    <tr>
     <td class="ruleField">
      <input type="radio" name="color_type" value="2" id="color_type2"  <?php if ($color_radio==2) echo 'checked="checked"'; ?> />
     </td>
     <td class="fieldValue">
      <?php echo _("Other"); ?>:
      <input type="text" name="newcolor_input" value="<?php echo $color_input; ?>" size="7" id="newcolor_input" />
      <?php // i18n: This is an example on how to write a color in RGB, literally meaning "For example: 63aa7f".
      echo _("Ex: 63aa7f"); ?>
     </td>
    </tr>
    <tr>
     <td colspan="2">
      <table cellspacing="0" class="colorTable">
       <tr>
        <td class="color_cccccc"><input type="radio" name="color_type" value="cccccc" <?php if ($rule_color=="cccccc") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff0000"><input type="radio" name="color_type" value="ff0000" <?php if ($rule_color=="ff0000") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffcccc"><input type="radio" name="color_type" value="ffcccc" <?php if ($rule_color=="ffcccc") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffcccc"><input type="radio" name="color_type" value="ffcccc" <?php if ($rule_color=="ffcccc") echo 'checked="checked"'; ?> /></td>
        <td class="color_00ff00"><input type="radio" name="color_type" value="00ff00" <?php if ($rule_color=="00ff00") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccffcc"><input type="radio" name="color_type" value="ccffcc" <?php if ($rule_color=="ccffcc") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccffcc"><input type="radio" name="color_type" value="ccffcc" <?php if ($rule_color=="ccffcc") echo 'checked="checked"'; ?> /></td>
        <td class="color_0000ff"><input type="radio" name="color_type" value="0000ff" <?php if ($rule_color=="0000ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccccff"><input type="radio" name="color_type" value="ccccff" <?php if ($rule_color=="ccccff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccccff"><input type="radio" name="color_type" value="ccccff" <?php if ($rule_color=="ccccff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffff00"><input type="radio" name="color_type" value="ffff00" <?php if ($rule_color=="ffff00") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffffcc"><input type="radio" name="color_type" value="ffffcc" <?php if ($rule_color=="ffffcc") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffffcc"><input type="radio" name="color_type" value="ffffcc" <?php if ($rule_color=="ffffcc") echo 'checked="checked"'; ?> /></td>
        <td class="color_00ffff"><input type="radio" name="color_type" value="00ffff" <?php if ($rule_color=="00ffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccffff"><input type="radio" name="color_type" value="ccffff" <?php if ($rule_color=="ccffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ccffff"><input type="radio" name="color_type" value="ccffff" <?php if ($rule_color=="ccffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff00ff"><input type="radio" name="color_type" value="ff00ff" <?php if ($rule_color=="ff00ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffccff"><input type="radio" name="color_type" value="ffccff" <?php if ($rule_color=="ffccff") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffccff"><input type="radio" name="color_type" value="ffccff" <?php if ($rule_color=="ffccff") echo 'checked="checked"'; ?> /></td>
       </tr>
       <tr>
        <td class="color_999999"><input type="radio" name="color_type" value="999999" <?php if ($rule_color=="999999") echo 'checked="checked"'; ?> /></td>
        <td class="color_cc0000"><input type="radio" name="color_type" value="cc0000" <?php if ($rule_color=="cc0000") echo 'checked="checked"'; ?> /></td>
        <td class="color_cc9999"><input type="radio" name="color_type" value="cc9999" <?php if ($rule_color=="cc9999") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff9999"><input type="radio" name="color_type" value="ff9999" <?php if ($rule_color=="ff9999") echo 'checked="checked"'; ?> /></td>
        <td class="color_00cc00"><input type="radio" name="color_type" value="00cc00" <?php if ($rule_color=="00cc00") echo 'checked="checked"'; ?> /></td>
        <td class="color_99cc99"><input type="radio" name="color_type" value="99cc99" <?php if ($rule_color=="99cc99") echo 'checked="checked"'; ?> /></td>
        <td class="color_99ff99"><input type="radio" name="color_type" value="99ff99" <?php if ($rule_color=="99ff99") echo 'checked="checked"'; ?> /></td>
        <td class="color_0000cc"><input type="radio" name="color_type" value="0000cc" <?php if ($rule_color=="0000cc") echo 'checked="checked"'; ?> /></td>
        <td class="color_9999cc"><input type="radio" name="color_type" value="9999cc" <?php if ($rule_color=="9999cc") echo 'checked="checked"'; ?> /></td>
        <td class="color_9999ff"><input type="radio" name="color_type" value="9999ff" <?php if ($rule_color=="9999ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_cccc00"><input type="radio" name="color_type" value="cccc00" <?php if ($rule_color=="cccc00") echo 'checked="checked"'; ?> /></td>
        <td class="color_cccc99"><input type="radio" name="color_type" value="cccc99" <?php if ($rule_color=="cccc99") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffff99"><input type="radio" name="color_type" value="ffff99" <?php if ($rule_color=="ffff99") echo 'checked="checked"'; ?> /></td>
        <td class="color_00cccc"><input type="radio" name="color_type" value="00cccc" <?php if ($rule_color=="00cccc") echo 'checked="checked"'; ?> /></td>
        <td class="color_99cccc"><input type="radio" name="color_type" value="99cccc" <?php if ($rule_color=="99cccc") echo 'checked="checked"'; ?> /></td>
        <td class="color_99ffff"><input type="radio" name="color_type" value="99ffff" <?php if ($rule_color=="99ffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_cc00cc"><input type="radio" name="color_type" value="cc00cc" <?php if ($rule_color=="cc00cc") echo 'checked="checked"'; ?> /></td>
        <td class="color_cc99cc"><input type="radio" name="color_type" value="cc99cc" <?php if ($rule_color=="cc99cc") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff99ff"><input type="radio" name="color_type" value="ff99ff" <?php if ($rule_color=="ff99ff") echo 'checked="checked"'; ?> /></td>
       </tr>
       <tr>
        <td class="color_666666"><input type="radio" name="color_type" value="666666" <?php if ($rule_color=="666666") echo 'checked="checked"'; ?> /></td>
        <td class="color_990000"><input type="radio" name="color_type" value="990000" <?php if ($rule_color=="990000") echo 'checked="checked"'; ?> /></td>
        <td class="color_996666"><input type="radio" name="color_type" value="996666" <?php if ($rule_color=="996666") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff6666"><input type="radio" name="color_type" value="ff6666" <?php if ($rule_color=="ff6666") echo 'checked="checked"'; ?> /></td>
        <td class="color_009900"><input type="radio" name="color_type" value="009900" <?php if ($rule_color=="009900") echo 'checked="checked"'; ?> /></td>
        <td class="color_669966"><input type="radio" name="color_type" value="669966" <?php if ($rule_color=="669966") echo 'checked="checked"'; ?> /></td>
        <td class="color_66ff66"><input type="radio" name="color_type" value="66ff66" <?php if ($rule_color=="66ff66") echo 'checked="checked"'; ?> /></td>
        <td class="color_000099"><input type="radio" name="color_type" value="000099" <?php if ($rule_color=="000099") echo 'checked="checked"'; ?> /></td>
        <td class="color_666699"><input type="radio" name="color_type" value="666699" <?php if ($rule_color=="666699") echo 'checked="checked"'; ?> /></td>
        <td class="color_6666ff"><input type="radio" name="color_type" value="6666ff" <?php if ($rule_color=="6666ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_999900"><input type="radio" name="color_type" value="999900" <?php if ($rule_color=="999900") echo 'checked="checked"'; ?> /></td>
        <td class="color_999966"><input type="radio" name="color_type" value="999966" <?php if ($rule_color=="999966") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffff66"><input type="radio" name="color_type" value="ffff66" <?php if ($rule_color=="ffff66") echo 'checked="checked"'; ?> /></td>
        <td class="color_009999"><input type="radio" name="color_type" value="009999" <?php if ($rule_color=="009999") echo 'checked="checked"'; ?> /></td>
        <td class="color_669999"><input type="radio" name="color_type" value="669999" <?php if ($rule_color=="669999") echo 'checked="checked"'; ?> /></td>
        <td class="color_66ffff"><input type="radio" name="color_type" value="66ffff" <?php if ($rule_color=="66ffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_990099"><input type="radio" name="color_type" value="990099" <?php if ($rule_color=="990099") echo 'checked="checked"'; ?> /></td>
        <td class="color_996699"><input type="radio" name="color_type" value="996699" <?php if ($rule_color=="996699") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff66ff"><input type="radio" name="color_type" value="ff66ff" <?php if ($rule_color=="ff66ff") echo 'checked="checked"'; ?> /></td>
       </tr>
       <tr>
        <td class="color_333333"><input type="radio" name="color_type" value="333333" <?php if ($rule_color=="333333") echo 'checked="checked"'; ?> /></td>
        <td class="color_660000"><input type="radio" name="color_type" value="660000" <?php if ($rule_color=="660000") echo 'checked="checked"'; ?> /></td>
        <td class="color_663333"><input type="radio" name="color_type" value="663333" <?php if ($rule_color=="663333") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff3333"><input type="radio" name="color_type" value="ff3333" <?php if ($rule_color=="ff3333") echo 'checked="checked"'; ?> /></td>
        <td class="color_006600"><input type="radio" name="color_type" value="006600" <?php if ($rule_color=="006600") echo 'checked="checked"'; ?> /></td>
        <td class="color_336633"><input type="radio" name="color_type" value="336633" <?php if ($rule_color=="336633") echo 'checked="checked"'; ?> /></td>
        <td class="color_33ff33"><input type="radio" name="color_type" value="33ff33" <?php if ($rule_color=="33ff33") echo 'checked="checked"'; ?> /></td>
        <td class="color_000066"><input type="radio" name="color_type" value="000066" <?php if ($rule_color=="000066") echo 'checked="checked"'; ?> /></td>
        <td class="color_333366"><input type="radio" name="color_type" value="333366" <?php if ($rule_color=="333366") echo 'checked="checked"'; ?> /></td>
        <td class="color_3333ff"><input type="radio" name="color_type" value="3333ff" <?php if ($rule_color=="3333ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_666600"><input type="radio" name="color_type" value="666600" <?php if ($rule_color=="666600") echo 'checked="checked"'; ?> /></td>
        <td class="color_666633"><input type="radio" name="color_type" value="666633" <?php if ($rule_color=="666633") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffff33"><input type="radio" name="color_type" value="ffff33" <?php if ($rule_color=="ffff33") echo 'checked="checked"'; ?> /></td>
        <td class="color_006666"><input type="radio" name="color_type" value="006666" <?php if ($rule_color=="006666") echo 'checked="checked"'; ?> /></td>
        <td class="color_336666"><input type="radio" name="color_type" value="336666" <?php if ($rule_color=="336666") echo 'checked="checked"'; ?> /></td>
        <td class="color_33ffff"><input type="radio" name="color_type" value="33ffff" <?php if ($rule_color=="33ffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_660066"><input type="radio" name="color_type" value="660066" <?php if ($rule_color=="660066") echo 'checked="checked"'; ?> /></td>
        <td class="color_663366"><input type="radio" name="color_type" value="663366" <?php if ($rule_color=="663366") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff33ff"><input type="radio" name="color_type" value="ff33ff" <?php if ($rule_color=="ff33ff") echo 'checked="checked"'; ?> /></td>
       </tr>
       <tr>
        <td class="color_000000"><input type="radio" name="color_type" value="000000" <?php if ($rule_color=="000000") echo 'checked="checked"'; ?> /></td>
        <td class="color_330000"><input type="radio" name="color_type" value="330000" <?php if ($rule_color=="330000") echo 'checked="checked"'; ?> /></td>
        <td class="color_330000"><input type="radio" name="color_type" value="330000" <?php if ($rule_color=="330000") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff0000"><input type="radio" name="color_type" value="ff0000" <?php if ($rule_color=="ff0000") echo 'checked="checked"'; ?> /></td>
        <td class="color_003300"><input type="radio" name="color_type" value="003300" <?php if ($rule_color=="003300") echo 'checked="checked"'; ?> /></td>
        <td class="color_003300"><input type="radio" name="color_type" value="003300" <?php if ($rule_color=="003300") echo 'checked="checked"'; ?> /></td>
        <td class="color_00ff00"><input type="radio" name="color_type" value="00ff00" <?php if ($rule_color=="00ff00") echo 'checked="checked"'; ?> /></td>
        <td class="color_000033"><input type="radio" name="color_type" value="000033" <?php if ($rule_color=="000033") echo 'checked="checked"'; ?> /></td>
        <td class="color_000033"><input type="radio" name="color_type" value="000033" <?php if ($rule_color=="000033") echo 'checked="checked"'; ?> /></td>
        <td class="color_0000ff"><input type="radio" name="color_type" value="0000ff" <?php if ($rule_color=="0000ff") echo 'checked="checked"'; ?> /></td>
        <td class="color_333300"><input type="radio" name="color_type" value="333300" <?php if ($rule_color=="333300") echo 'checked="checked"'; ?> /></td>
        <td class="color_333300"><input type="radio" name="color_type" value="333300" <?php if ($rule_color=="333300") echo 'checked="checked"'; ?> /></td>
        <td class="color_ffff00"><input type="radio" name="color_type" value="ffff00" <?php if ($rule_color=="ffff00") echo 'checked="checked"'; ?> /></td>
        <td class="color_003333"><input type="radio" name="color_type" value="003333" <?php if ($rule_color=="003333") echo 'checked="checked"'; ?> /></td>
        <td class="color_003333"><input type="radio" name="color_type" value="003333" <?php if ($rule_color=="003333") echo 'checked="checked"'; ?> /></td>
        <td class="color_00ffff"><input type="radio" name="color_type" value="00ffff" <?php if ($rule_color=="00ffff") echo 'checked="checked"'; ?> /></td>
        <td class="color_330033"><input type="radio" name="color_type" value="330033" <?php if ($rule_color=="330033") echo 'checked="checked"'; ?> /></td>
        <td class="color_330033"><input type="radio" name="color_type" value="330033" <?php if ($rule_color=="330033") echo 'checked="checked"'; ?> /></td>
        <td class="color_ff00ff"><input type="radio" name="color_type" value="ff00ff" <?php if ($rule_color=="ff00ff") echo 'checked="checked"'; ?> /></td>
       </tr>
      </table>
     </td>
    </tr>
    <tr>
     <td colspan="2">
      <input type="submit" value="<?php echo _("Save Changes"); ?>" />
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</div>