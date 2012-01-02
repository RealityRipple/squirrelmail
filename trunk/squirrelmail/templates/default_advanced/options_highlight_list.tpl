<?php
/**
 * options_highlight.tpl
 *
 * Template for displaying option highlighting rules
 * 
 * The following variables are available in this template:
 *      $current_rules  - array containing the current rule set.  Each element
 *                        contains the following fields:
 *          $el['Name']         - The name of the rule.  Sanitized.  May be empty.
 *          $el['Color']        - The highlight color for the rule
 *          $el['MatchField']   - Translated name of the field the rule matches
 *          $el['MatchValue']   - The value being matched
 *      $add_rule       - URL to add a rule
 *      $edit_rule      - URL foundation to edit a rule
 *      $delete_rule    - URL foundation to delete a rule
 *      $move_up        - URL foundation to move a rule up
 *      $move_down      - URL foundation to move a rule down
 *
 * @copyright 1999-2012 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div id="optionHighlightList">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header1">
   <?php echo _("Options") .' - '. _("Message Highlighting"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0" class="table1">
   <?php
    if (count($current_rules) == 0) {
        ?>
    <tr>
     <td colspan="6" class="emptyList">
      <?php echo _("No highlighting is defined"); ?>
     </td>
    </tr>
         <?php
    }
    
    foreach ($current_rules as $index=>$rule) {
        ?>
    <tr>
     <td class="ruleButtons">
      <a href="<?php echo $edit_rule.$index ?>"><?php echo getIcon($icon_theme_path, 'edit.png', _("Edit"), _("Edit")); ?></a>
     </td>
     <td class="ruleButtons">
      <a href="<?php echo $delete_rule.$index; ?>"><?php echo getIcon($icon_theme_path, 'delete.png', _("Delete"), _("Delete")); ?></a>
     </td>
     <td class="ruleButtons">
      <?php 
        if ($index > 0) {
            ?>
            <a href="<?php echo $move_up.$index; ?>"><?php echo getIcon($icon_theme_path, 'up.png', _("Up"), _("Up")); ?></a>
            <?php
        } else {
            ?>
            &nbsp;
            <?php
        }
      ?>
     </td>
     <td class="ruleButtons">
      <?php 
        if ($index < count($current_rules)-1) {
            ?>
            <a href="<?php echo $move_down.$index; ?>"><?php echo getIcon($icon_theme_path, 'down.png', _("Down"), ("Down")); ?></a>
            <?php
        } else {
            ?>
            &nbsp;
            <?php
        }
      ?>
     </td>
     <td bgcolor="#<?php echo $rule['Color']; ?>" class="ruleName">
      <?php echo $rule['Name']; ?>
     </td>
     <td bgcolor="#<?php echo $rule['Color']; ?>" class="ruleDesc">
      <?php echo $rule['MatchField'].' = '.$rule['MatchValue']; ?>
     </td>
    </tr>
        <?php
    }
   ?>
   </table>
  </td>
 </tr>
 <tr>
  <td class="ruleButtons">
   <a href="<?php echo $add_rule; ?>"><?php echo getIcon($icon_theme_path, 'plus.png', _("Add"), _("Add")); ?></a> <?php echo _("Add Rule"); ?>
  </td>
 </tr>
</table>
</div>
