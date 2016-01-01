<?php
/**
 * addrbook_addedit.tpl
 *
 * Display the form elements to add/edit an entry in the address book
 * 
 * The following variables are available in this template:
 *      $current_backend - integer containing backend currently displayed.
 *      $edit       - boolean TRUE if we are editing an existing address.
 *                    FALSE if the form is blank for adding a new address.
 *      $writable_backends - array of address book backends that can be written
 *                    to.  This will be NULL if $edit is TRUE.
 *      $values     - array containing values for each field.  If $edit is TRUE,
 *                    elements will contains the current values for each field
 *                    of the entry.  If $edit is FALSE, each element will be
 *                    empty.  The following elements will be present:
 *              $el['FirstName'] - The entry's first name
 *              $el['LastName']  - The entry's last name (surname)
 *              $el['NickName']  - The entry's nickname
 *              $el['Email']     - The entry's email.  Note that this field
 *                                 could be an array!
 *              $el['Info']      - Additional info about this contact
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
$formname = $edit ? 'editaddr' : 'addaddr';
?>
<div id="addrAddEdit">
<table cellspacing="0">
 <tr>
  <td class="header" colspan="2">
   <?php echo $edit ? _("Update Address") : _("Add to Address Book"); ?>
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="nickname"><?php echo _("Nickname"); ?>:</label>
  </td>
  <td>
   <input type="text" name="<?php echo $formname; ?>[nickname]" id="nickname" value=<?php echo '"'.$values['NickName'].'"'; ?> size="15" />
   <small><?php echo _("Must be unique"); ?></small>
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="email"><?php echo _("E-mail"); ?>:</label>
  </td>
  <td>
   <?php
    if (is_array($values['Email'])) {
        echo '<select name="'.$formname.'[email]" id="email">'."\n";
        foreach ($values['Email'] as $email) {
//FIXME: sm_encode_html_special_chars should not be needed inside any template files - I think values are already sanitized by the time they get here
            echo '<option value="'.sm_encode_html_special_chars($email).'">'.sm_encode_html_special_chars($email).'</option>'."\n";
        }
        echo '</select>'."\n";
    } else {
        echo '<input type="text" name="'.$formname.'[email]" id="email" value="'.$values['Email'].'" size="45" />'."\n";
    }
   ?>
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="firstname"><?php echo _("First name"); ?>:</label>
  </td>
  <td>
   <input type="text" name="<?php echo $formname; ?>[firstname]" id="firstname" value=<?php echo '"'.$values['FirstName'].'"'; ?> size="45" />
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="lastname"><?php echo _("Last name"); ?>:</label>
  </td>
  <td>
   <input type="text" name="<?php echo $formname; ?>[lastname]" id="lastname" value=<?php echo '"'.$values['LastName'].'"'; ?> size="45" />
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="info"><?php echo _("Additional info"); ?>:</label>
  </td>
  <td>
   <input type="text" name="<?php echo $formname; ?>[label]" id="info" value=<?php echo '"'.$values['Info'].'"'; ?> size="45" />
  </td>
 </tr>
 <?php
    if (!$edit) {
        if (count($writable_backends) > 1) {
            ?>
 <tr>
  <td class="fieldName">
   <label for="backend"><?php echo _("Add to:"); ?></label>
  </td>
  <td>
   <select name="backend" id="backend">
    <?php
        foreach ($writable_backends as $id=>$name) {
//FIXME: sm_encode_html_special_chars should not be needed inside any template files - I think values are already sanitized by the time they get here
            echo '<option value="' . $id
               . ($current_backend == $id ? '" selected="selected"' : '"')
               . '>' . sm_encode_html_special_chars($name) . '</option>' . "\n";
        }
    ?>
   </select>
  </td>
 </tr>
            <?php
        } else {
            echo '<input type="hidden" name="backend" value="1" />'."\n";
        }
    }
 ?>
 <tr>
  <td colspan="2" class="addButton">
   <input type="hidden" name="new_bnum" value="<?php echo $current_backend; ?>" />
   <input type="submit" value=<?php echo '"'.($edit ? _("Update Address") : _("Add Address")).'"'; ?> name="<?php echo $formname; ?>[SUBMIT]" />
  </td>
 </tr>
</table>
</div>
