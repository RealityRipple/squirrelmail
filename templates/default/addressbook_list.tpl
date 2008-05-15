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
 *      $addresses - array of backends in the address book.  Each element
 *                   is an array containing the following fields:
 *          ['BackendID']       - integer unique identifier for each source of 
 *                                addresses in the book.  this should also be
 *                                the same as the array key for this value
 *          ['BackendSource']   - description of each source of addresses
 *          ['BackendWritable'] - boolean TRUE if the address book can be
 *                                modified.  FALSE otherwise.
 *          ['Addresses']       - array containing address from this source.
 *                                Each array element contains the following:
 *                       ['FirstName']   - The entry's first name
 *                       ['LastName']    - The entry's last name (surname)
 *                       ['FullName']    - The entry's full name (first + last)
 *                       ['NickName']    - The entry's nickname
 *                       ['Email']       - duh
 *                       ['FullAddress'] - Email with full name or nick name
 *                                         optionally prepended.
 *                       ['Info']        - Additional info about this contact
 *                       ['Extra']       - Additional field, if provided.  NULL if
 *                                         this field is not provided by the book.
 *                       ['JSEmail']     - email address scrubbed for use with
 *                                         javascript functions.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/
include_once(SM_PATH . 'functions/template/abook_util.php');

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
   <input type="submit" value=<?php echo '"'._("Edit Selected").'"'; ?> name="editaddr" id="editaddr" />
   <input type="submit" value=<?php echo '"'._("Delete Selected").'"'; ?> name="deladdr" id="deladdr" />
   <?php if (!empty($plugin_output['address_book_navigation'])) echo $plugin_output['address_book_navigation']; ?>
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
  <td class="colHeader" style="width:1%"><input type="checkbox" name="toggleAll" id="toggleAll" title="<?php echo _("Toggle All"); ?>" onclick="toggle_all('address_book_form', 'sel', false); return false;" /></td>
  <td class="colHeader" style="width:15%"><?php echo addAbookSort('nickname', $current_backend); ?></td>
  <td class="colHeader"><?php echo addAbookSort('fullname', $current_backend); ?></td>
  <td class="colHeader"><?php echo addAbookSort('email', $current_backend); ?></td>
  <td class="colHeader"><?php echo addAbookSort('info', $current_backend); ?></td>
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
        $id = $contact['NickName'] .'_'. $current_backend;
        ?>
 <tr class=<?php echo '"'.($count%2 ? 'even' : 'odd').'"'; ?>>
  <td class="abookField" style="width:1%"><?php echo ($source['BackendWritable'] ? '<input type="checkbox" name="sel[' . $count . ']" value="'.$id.'" id="'.$id.'" ' . (!empty($plugin_output['address_book_checkbox_extra']) ? $plugin_output['address_book_checkbox_extra'] : '') . ' />' : ''); ?></td>
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
