<?php
/**
 * search_advanced.tpl
 *
 * Template to display the advanced (multi-rule) search fields
 * 
 * The following variables are available in this template:
 *      $allow_advanced_search - boolean TRUE if the advacned search feature is available
 *      $adv_toggle_link    - URL to toggle between basic and advanced searches.
 *                            NULL if advanced search has been disabled.
 *      $adv_toggle_text    - Text to toggle between basic and advanced searches.
 *                            NULL if advanced search has been disabled.
 *      $mailbox_options    - array containing sanitized list of mailboxes to
 *                            sort.  Index of each element is the value that
 *                            should be assigned to the HTML input element.
 *      $logical_options    - array containing sanitized list of logical
 *                            operators available, e.g. AND, OR.  Index of each
 *                            element is the value that should be assigned
 *                            to the HTML input element.
 *      $unary_options      - array containing sanitized list of unary options,
 *                            e.g. NOT.  Index of each element is the value that
 *                            should be assigned to the HTML input element.
 *      $where_options      - array containing sanitized list of fields availble
 *                            to search on.  Index of each element is the value
 *                            that should be assigned to the HTML input element.
 *      $criteria           - array containing the current list of search criteria.
 *                            Each element in the array represents a set of criteria
 *                            and contains the following elements:
 *          $el['MailboxSel']   - the selected mailbox for this rule
 *          $el['LogicSel']     - the selected logical operator for this rule
 *          $el['UnarySel']     - the selected unary operator for this rule
 *          $el['WhereSel']     - the selected field to search in for this rule
 *          $el['Exclude']      - boolean TRUE if this rule is to be excluded
 *          $el['IncludeSubfolders'] - boolean TRUE if this rule is to include
 *                                the subfolders of the selected mailbox
 *          $el['What']         - the value that is to be searched for.
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
<div class="search">
<table cellspacing="0" class="table2">
 <tr>
  <td class="header1" colspan="5">
   <?php echo _("Search"); ?>
   <?php
    if ($allow_advanced_search) {
        ?>
   - <small>[<a href="<?php echo $adv_toggle_link; ?>"><?php echo $adv_toggle_text; ?></a>]</small>
        <?php
    }
   ?>
  </td>
 </tr>
 <?php
    foreach ($criteria as $row=>$rule) {
        $mailbox_sel = strtolower($rule['MailboxSel']);
        $logical_sel = $rule['LogicSel'];
        $unary_sel = $rule['UnarySel'];
        $subfolders = $rule['IncludeSubfolders'];
        $where_sel = $rule['WhereSel'];
        $what_val = $rule['What'];
        $exclude = $rule['Exclude'];
        ?>
 <tr>
  <td class="searchForm">
   <?php
    if($row == 0) {
        echo _("In");
    } else {
        ?>
   <select name="biop[<?php echo $row; ?>]">
    <?php
        foreach ($logical_options as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$logical_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
        <?php
    }
   ?>
  </td>
  <td class="searchForm">
   <select name="mailbox[<?php echo $row; ?>]">
    <?php
        foreach ($mailbox_options as $value=>$option) {
            echo '<option value="'. $value .'"' . (strtolower($value)==$mailbox_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
   <label for="sub_<?php echo $row; ?>"><?php echo _("and subfolders"); ?>:</label>
   <input type="checkbox" name="sub[<?php echo $row; ?>]" id="sub_<?php echo $row; ?>" <?php if ($subfolders) echo ' checked="checked"'; ?> />
  </td>
  <td class="searchForm">
   <select name="unop[<?php echo $row; ?>]">
    <?php
        foreach ($unary_options as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$unary_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
   &nbsp;
   <select name="where[<?php echo $row; ?>]">
    <?php
        foreach ($where_options as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$where_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
  </td>
  <td class="searchForm">
   <input type="text" name="what[<?php echo $row; ?>]" value="<?php echo $what_val; ?>" />
  </td>
  <td class="searchForm">
  <label for="exclude_<?php echo $row; ?>"><?php echo _("Exclude"); ?>:</label>
   <input type="checkbox" name="exclude[<?php echo $row; ?>]" id="exclude_<?php echo $row; ?>" <?php if ($exclude) echo ' checked="checked"'; ?> />
  </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td colspan="5" class="header1">
   <input type="submit" name="submit" value="<?php echo _("Search"); ?>" />&nbsp;&nbsp;
   <input type="submit" name="submit" value="<?php echo _("Add New Criteria"); ?>" />&nbsp;&nbsp;
   <input type="submit" name="submit" value="<?php echo _("Remove All Criteria"); ?>" />&nbsp;&nbsp;
   <input type="submit" name="submit" value="<?php echo _("Remove Excluded Criteria"); ?>" />&nbsp;&nbsp;
  </td>
 </tr>
</table>
</div>