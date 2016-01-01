<?php
/**
 * options_ident_advanced.tpl
 *
 * Template to handle advanced identity management
 * 
 * The following variables are available in this template:
 *      $identities - array containing all identities.  Each element contains
 *                    the following fields:
 *          $el['Title']    - title to be displayed in each block
 *          $el['New']      - boolean TRUE if this element is for a new identity.
 *                            FALSE otherwise.
 *          $el['Default']  - boolean TRUE if this is the default identity.
 *          $el['FullName'] - value for the Full Name field
 *          $el['Email']    - value for the email field
 *          $el['ReplyTo']  - value for the Reply To field
 *          $el['Signature']- value for the Signature field
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
<div id="optionsIdentity">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header1">
   <?php echo _("Options"); ?> - <?php echo _("Advanced Identities"); ?>
  </td>
 </tr>
 <tr>
  <td>
   <?php
    foreach ($identities as $index=>$identity) {
        if ($identity['New']) {
            ?>
   <hr />
            <?php
        }
        ?>
   <table cellspacing="0" class="table2">
    <tr>
     <td colspan="2" class="header2">
      <?php echo $identity['Title']; ?>
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Full Name"); ?>
     </td>
     <td class="fieldValue">
      <input type="text" name="newidentities[<?php echo $index; ?>][full_name]" size="50" value="<?php echo $identity['FullName']; ?>" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("E-Mail Address"); ?>
     </td>
     <td class="fieldValue">
      <input type="text" name="newidentities[<?php echo $index; ?>][email_address]" size="50" value="<?php echo $identity['Email']; ?>" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Reply To"); ?>
     </td>
     <td class="fieldValue">
      <input type="text" name="newidentities[<?php echo $index; ?>][reply_to]" size="50" value="<?php echo $identity['ReplyTo']; ?>" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Signature"); ?>
     </td>
     <td class="fieldValue">
      <textarea name="newidentities[<?php echo $index; ?>][signature]" cols="50" rows="5"><?php echo $identity['Signature']; ?></textarea>
     </td>
    </tr>
    <?php /* FIXME: No hooks in templates! */ $temp = array('', &$identity['New'], &$index); echo concat_hook_function('options_identities_table', $temp); ?>
    <tr>
     <td colspan="2" class="actionButtons">
      <input type="submit" name="smaction[save][<?php echo $index; ?>]" value="<?php echo _("Save / Update"); ?>" />
      <?php
        if ($index > 0 && !$identity['New']) {
            ?>
      <input type="submit" name="smaction[makedefault][<?php echo $index; ?>]" value="<?php echo _("Make Default"); ?>" />
      <input type="submit" name="smaction[delete][<?php echo $index; ?>]" value="<?php echo _("Delete"); ?>" />
            <?php
        }
        if ($index > 1 && !$identity['New']) {
            ?>
      <input type="submit" name="smaction[move][<?php echo $index; ?>]" value="<?php echo _("Move Up"); ?>" />
            <?php
        }
        /* FIXME: No hooks in templates! */ $temp = array(&$identity['New'], &$index); echo concat_hook_function('options_identities_buttons', $temp);
      ?>
     </td>
    </tr>
   </table>
        <?php
    }
   ?>
  </td>
 </tr>
</table>
</div>
