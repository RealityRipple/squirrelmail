<?php
/**
 * options_order.tpl
 *
 * Template for changing message list field ordering.
 * 
 * The following variables are available in this template:
 *      $fields         - array containing all translated field names available
 *                        for ordering.  Array key is an integer, value is the field name
 *      $current_order  - array containing the current ordering of fields
 *      $not_used       - array containing fields not currently used.  Array
 *                        key is the same as in $fields, value is the translated
 *                        field name.
 *      $always_show    - array containing field indexes that should always be shown
 *                        to maintain SquirrelMail functionality, e.g. Subject
 *      $move_up        - URL foundation to move a field up in the ordering
 *      $move_down      - URL foundation to move a field down in the ordering
 *      $remove         - URL foundation to remove a field from the ordering.
 *      $add            - URL foundation to add a field to the ordering.
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
<div id="optionHighlight">
<table cellspacing="0">
 <tr>
  <td class="header1">
   <?php echo _("Options") .' - '. _("Index Order"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <?php echo _("The index order is the order that the columns are arranged in the message index. You can add, remove, and move columns around to customize them to fit your needs.");?>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0" class="moveFields">
    <tr>
     <td colspan="4" class="divider">
      <?php echo _("Reorder indexes"); ?>
     </td>
    </tr>
    <?php
        foreach ($current_order as $order=>$index) {
            echo "   <tr>\n";
            
            echo "    <td class=\"moveLink\">\n" .($order > 0 ? '<a href="'. $move_up.$index .'">'.getIcon($icon_theme_path, 'up.png', _("up"), _("up")).'</a>' : '&nbsp;'). "\n    </td>\n";
            echo "    <td class=\"moveLink\">\n" .($order < count($current_order)-1 ? '<a href="'. $move_down.$index .'">'.getIcon($icon_theme_path, 'down.png', _("down"), _("down")).'</a>' : '&nbsp;'). "\n    </td>\n";
            echo "    <td class=\"moveLink\">\n" .(!in_array($index, $always_show) ? '<a href="'. $remove.$index .'">'.getIcon($icon_theme_path, 'delete.png', _("remove"), _("remove")).'</a>' : '&nbsp;'). "\n    </td>\n";
            echo "    <td class=\"fieldName\">\n" .$fields[$index]. "\n    </td>\n";
            
            echo "   </tr>\n";
        }

    if (count($not_used) > 0) {
        ?>
    <tr>
     <td colspan="4" class="divider">
      <?php echo _("Add an index"); ?>
     </td>
    </tr>
    <?php
        foreach ($not_used as $field_id=>$name) {
            echo "<tr>\n" .
                 "<td colspan=\"3\" class=\"moveLink\"><a href=\"". $add.$field_id."\">".getIcon($icon_theme_path, 'plus.png', _("Add"), _("Add"))."</a></td>\n" .
                 "<td class=\"fieldName\">".sm_encode_html_special_chars($name)."</td>\n" .
                 "</tr>\n";
    }
   ?>
   </table>
  </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td>
   <a href="../src/options.php"><?php echo _("Return to options page");?></a>   
  </td>
 </tr>
</table>
</div>
