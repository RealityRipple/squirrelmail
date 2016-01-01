<?php
/**
 * vcard.tpl
 *
 * Template to display a vCard
 * 
 * The following variables are available in this template:
 *      $view_message_link  - URL to go back to the message
 *      $download_link      - URL to download the vCard
 *      $nickname           - Default nickname for the address book add form
 *      $firstname          - First name for the address book add from
 *      $last name          - Last name for the add form
 *      $email              - Email for the add form
 *      $info               - array of Additional info for the add form.  May be
 *                            empty if no additional info is provided by the
 *                            card.  Index of each element is the value for the
 *                            option, value of each element is the name.
 *      $vcard              - array containing vCard data, scrubbed and i-18-n'ed.
 *                            Index of each element is the field name, value of
 *                            each element is the field value.
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
<div id="vCard">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header1">
   <?php echo _("Viewing a Business Card") ; ?> - <a href="<?php echo $view_message_link; ?>"><?php echo _("View message"); ?></a>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0">
    <?php
        foreach ($vcard as $field=>$value) {
            ?>
    <tr>
     <td class="fieldName">
      <?php echo $field; ?>:
     </td>
     <td class="fieldValue">
      <?php echo $value; ?>
     </td>
    </tr>
            <?php
        }
    ?>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   <a href="<?php echo $download_link; ?>"><?php echo _("Download this as a file"); ?></a>
  </td>
 </tr>
</table>
<form action="../src/addressbook.php" method="post" name="f_add">
<input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
<input type="hidden" name="addaddr[firstname]" value="<?php echo $firstname; ?>" />
<input type="hidden" name="addaddr[lastname]" value="<?php echo $lastname; ?>" />
<table cellspacing="0" class="table1">
 <tr> 
  <td class="header1">
   <?php echo _("Add to address book"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="fieldName">
      <?php echo _("Nickname"); ?>
     </td>
     <td class="fieldValue">
      <input type="text" name="addaddr[nickname]" value="<?php echo $nickname; ?>" size="20" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Email"); ?>
     </td>
     <td class="fieldValue">
      <input type="text" name="addaddr[email]" value="<?php echo $email; ?>" size="20" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Additional Info"); ?>
     </td>
     <td class="fieldValue">
      <?php
        if (count($info) == 0) {
            ?>
      <input type="text" name="addaddr[label]" value="" size="20" />
            <?php
        } else {
            ?>
      <select name="addaddr[label]">
            <?php
            foreach ($info as $value=>$field) {
                ?>
        <option value="<?php echo $value; ?>"><?php echo $field; ?></option>
                <?php
            }
            ?>
      </select>
            <?php
        }
      ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   <input type="submit" value="<?php echo _("Add to address book"); ?>" name="addaddr[SUBMIT]" id="addaddr_SUBMIT_" />
  </td>
 </tr>
</table>
</form>
</div>
