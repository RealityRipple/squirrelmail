<?php
/**
 * search_list.tpl
 *
 * Template for displaying recent/saved searches
 * 
 * The following variables are available in this template:
 *      $list_title     - Translated title for this list.
 *      $show_list      - boolean TRUE if this list is to be shown, i.e. it is unfolded
 *      $is_recent_list - boolean TRUE if this is the list of recent searches.
 *                        Different query options are displayed for each list
 *      $expand_collapse_toggle - URL to fold/unfold this list
 *      $save_recent    - base URL to save a recent query
 *      $do_recent      - base URL to repeat a recent query
 *      $forget_recent  - base URL to forget a recent query
 *      $edit_saved     - base URL to edit a saved query
 *      $do_saved       - base URL to repeat a saved query
 *      $delete_saved   - base URL to delete a saved query
 *      $query_list     - array containing the list of queries to be displayed.
 *                        Index of each element is the query number.  Each of the
 *                        base URLs above MUST be followed by this index in order
 *                        to work correctly!  
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div class="search">
<table cellspacing="0" class="table2">
 <tr>
  <td style="width:1%" class="header4">
   <a href="<?php echo $expand_collapse_toggle; ?>">
   <?php
    if ($show_list) {
        echo getIcon($icon_theme_path, 'minus.png', '-', _("Fold"));
    } else {
        echo getIcon($icon_theme_path, 'plus.png', '+', _("Unfold"));
    }
   ?>
   </a>
  </td>
  <td class="header4" colspan="4">
   <?php echo $list_title; ?>
  </td>
 </tr>
 <?php
    if ($show_list) {
        $count = 1;
        foreach ($query_list as $id=>$desc) {
            if ($count%2 == 0)
                $class = 'even';
            else $class = 'odd';
            ?>
 <tr class="<?php echo $class; ?>">
  <td class="queryDesc">
   &nbsp;
  </td>
  <td class="queryDesc">
   <?php echo $desc; ?>
  </td>
            <?php
            if ($is_recent_list) {
                ?>
  <td class="queryAction">
   <a href="<?php echo $save_recent.$id; ?>"><?php echo _("Save"); ?></a>
  </td>
  <td class="queryAction">
   <a href="<?php echo $do_recent.$id; ?>"><?php echo _("Search"); ?></a>
  </td>
  <td class="queryAction">
   <a href="<?php echo $forget_recent.$id; ?>"><?php echo _("Forget"); ?></a>
  </td>
                <?php
            } else {
                ?>
  <td class="queryAction">
   <a href="<?php echo $edit_saved.$id; ?>"><?php echo _("Edit"); ?></a>
  </td>
  <td class="queryAction">
   <a href="<?php echo $do_saved.$id; ?>"><?php echo _("Search"); ?></a>
  </td>
  <td class="queryAction">
   <a href="<?php echo $delete_saved.$id; ?>"><?php echo _("Delete"); ?></a>
  </td>
                <?php
            }
            ?>
 </tr>
            <?php
            $count++;
        }
    }
 ?>
</table>
</div>
