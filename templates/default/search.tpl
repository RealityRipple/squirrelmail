<?php
/**
 * search.tpl
 *
 * Display the simple (single field) search fields
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
 *      $unary_options      - array containing sanitized list of unary options,
 *                            e.g. NOT.  Index of each element is the value that
 *                            should be assigned to the HTML input element.
 *      $where_options      - array containing sanitized list of fields availble
 *                            to search on.  Index of each element is the value
 *                            that should be assigned to the HTML input element.
 *      $mailbox_sel        - the selected mailbox for the search
 *      $unary_sel          - the selected unary operator for the search
 *      $where_sel          - the selected field to search in for the search
 *      $what_val           - the value that is to be searched for.
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
 <tr>
  <td class="searchForm">
   <?php echo _("In"); ?>
   <select name="mailbox[0]">
    <?php
        foreach ($mailbox_options as $value=>$option) {
            echo '<option value="'. $value .'"' . (strtolower($value)==$mailbox_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
  </td>
  <td class="searchForm">
   <select name="unop[0]">
    <?php
        foreach ($unary_options as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$unary_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
   &nbsp;
   <select name="where[0]">
    <?php
        foreach ($where_options as $value=>$option) {
            echo '<option value="'. $value .'"' . ($value==$where_sel ? ' selected="selected"' : '').'>' . $option .'</option>'."\n";
        }
    ?>
   </select>
  </td>
  <td class="searchForm">
   <input type="text" name="what[0]" value="<?php echo $what_val; ?>" size="35" />
  </td>
  <td class="searchForm">
   <input type="submit" name="submit" value="<?php echo _("Search"); ?>" />
  </td>
 </tr>
</table>
</div>