<?php
/**
 * addressbook_list.tpl
 *
 * Template for the basic address book list
 * 
 * The following variables are available in this template:
 *      $current_backend - integer containing backend currently displayed.
 *      $abook_select    - string containing HTML to display the address book
 *                         selection drop down
 *      $abook_has_extra_field - boolean TRUE if the address book contains an
 *                         additional field.  FALSE otherwise.
 *      $backends        - array containing all available backends for selection.
 *                         This will be empty if only 1 backend is available! 
 *      $addresses - array of addresses in the address book.  Each element
 *                   is an array containing the following fields:
 *          $el['BackendID']     - integer unique identifier for each source of 
 *                                 addresses in the book
 *          $el['BackendSource'] - description of each source of addresses
 *          $el['BackendWritable'] - boolean TRUE if the address book can be
 *                                 modified.  FALSE otherwise.
 *          $el['Addresses']     - array containing address from this source.
 *                                 Each array element contains the following:
 *              $el['FirstName'] - The entry's first name
 *              $el['LastName']  - The entry's last name (surname)
 *              $el['FullName']  - The entry's full name (first + last)
 *              $el['NickName']  - The entry's nickname
 *              $el['Email']     - duh
 *              $el['FullAddress'] - Email with full name or nick name
 *                                 optionally prepended.
 *              $el['Info']      - Additional info about this contact
 *              $el['Extra']     - Additional field, if provided.  NULL if this
 *                                 field is not provided by the book.
 *              $el['JSEmail']   - email address scrubbed for use with
 *                                 javascript functions.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/
include_once(SM_PATH . 'templates/util_addressbook.php');

/** extract template variables **/
extract($t);

/** Begin template **/
$source = $addresses[$current_backend];
$colspan = $abook_has_extra_field ? 6 : 5;
?>
<div id="addressList">
<table cellspacing="0">
 <tr>
  <td colspan=<?php echo '"'.$colspan.'"'; ?> class="header1">
   <?php echo $source['BackendSource']; ?>
  </td>
 </tr>
 <tr>
  <td colspan="3" class="abookButtons">
   <input type="submit" value=<?php echo '"'._("Edit selected").'"'; ?> name="editaddr" id="editaddr" />
   <input type="submit" value=<?php echo '"'._("Delete selected").'"'; ?> name="deladdr" id="deladdr" />
  </td>
  <td colspan=<?php echo '"'.($colspan - 3).'"'; ?> class="abookSwitch">
   <?php
    if (count($backends) > 0) {
        ?>
   <select name="new_bnum">
    <?php
        foreach ($backends as $id=>$name) {
            echo '<option value="'.$id.'"'.($id==$current_backend ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";
        }
    ?>
   </select>
   <input type="submit" value=<?php echo '"'._("Change").'"'; ?> name="change_abook" id="change_abook" />
        <?php
    } else {
        echo '&nbsp;';
    }
   ?>
  </td>
 </tr>
 <tr>
  <td class="colHeader" style="width:1%"></td>
  <td class="colHeader" style="width:15%"><?php echo addAbookSort('nickname'); ?></td>
  <td class="colHeader"><?php echo addAbookSort('fullname'); ?></td>
  <td class="colHeader"><?php echo addAbookSort('email'); ?></td>
  <td class="colHeader"><?php echo addAbookSort('info'); ?></td>
  <?php
   if ($abook_has_extra_field) {
    echo '<td class="colHeader"></td>';
   }
  ?>
 </tr>
 <?php
    $count = 1;
    if (count($source['Addresses']) == 0) {
        echo '<tr><td class="abookEmpty" colspan="'.$colspan.'">'._("Address book is empty").'</td></tr>'."\n";
    }
    foreach ($source['Addresses'] as $contact) {
        $id = $contact['NickName'] . $current_backend;
        ?>
 <tr class=<?php echo '"'.($count%2 ? 'even' : 'odd').'"'; ?>>
  <td class="abookField" style="width:1%"><?php echo ($source['BackendWritable'] ? '<input type="checkbox" name="sel[]" value="'.$id.'" id="'.$id.'" />' : ''); ?></td>
  <td class="abookField" style="width:15%"><label for=<?php echo '"'.$id.'"'; ?>><?php echo $contact['NickName']; ?></label></td>
  <td class="abookField"><?php echo $contact['FullName']; ?></td>
  <td class="abookField"><?php echo composeLink($contact); ?></td>
  <td class="abookField"><?php echo $contact['Info']; ?></td>
        <?php 
        if ($abook_has_extra_field) {
            echo '<td class="abookField">'.$contact['Extra'].'</td>'."\n";
        }
        ?>
 </tr>
        <?php
        $count++;
    }
?>
</table>
</div>