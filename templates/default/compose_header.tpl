<?php
/**
 * compose_header.tpl
 *
 * Description
 * 
 * The following variables are available in this template:
 *    $accesskey_compose_identity - The access key to be used for the From field
 *    $accesskey_compose_to       - The access key to be used for the To field
 *    $accesskey_compose_cc       - The access key to be used for the Cc field
 *    $accesskey_compose_bcc      - The access key to be used for the Bcc field
 *    $accesskey_compose_subject  - The access key to be used for the Subject field
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
<div class="compose">
<table cellspacing="0" class="table1">
 <?php
    # Display the identity list if there is more than 1 identity
    if (count($identities) > 1) {
        ?>
 <tr>
  <td class="fieldName">
   <label for="identity"><?php echo _("From"); ?>:</label>
  </td>
  <td class="fieldValue">
   <select name="identity" <?php if ($accesskey_compose_identity != 'NONE') echo 'accesskey="' . $accesskey_compose_identity . '" '; ?>id="identity">
        <?php
        foreach ($identities as $id=>$ident) {
            echo '<option value="'.$id.'"'. ($identity_def==$id ? ' selected="selected"' : '') .'>'. $ident .'</option>';
        }
        ?>
   </select>
   </td>
 </tr>
        <?php
    }
 ?>
 <tr>
  <td class="fieldName">
   <label for="to"><?php echo _("To"); ?>:</label>
  </td>
  <td class="fieldValue">
   <input type="text" name="send_to" id="to" value="<?php echo $to; ?>" size="50" <?php if ($accesskey_compose_to != 'NONE') echo 'accesskey="' . $accesskey_compose_to . '" '; echo $input_onfocus; ?> />
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="send_to_cc"><?php echo _("Cc"); ?>:</label>
  </td>
  <td class="fieldValue">
   <input type="text" name="send_to_cc" id="send_to_cc" value="<?php echo $cc; ?>" size="50" <?php if ($accesskey_compose_cc != 'NONE') echo 'accesskey="' . $accesskey_compose_cc . '" '; echo $input_onfocus; ?> />
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="send_to_bcc"><?php echo _("Bcc"); ?>:</label>
  </td>
  <td class="fieldValue">
   <input type="text" name="send_to_bcc" id="send_to_bcc" value="<?php echo $bcc; ?>" size="50" <?php if ($accesskey_compose_bcc != 'NONE') echo 'accesskey="' . $accesskey_compose_bcc . '" '; echo $input_onfocus; ?> />
  </td>
 </tr>
 <tr>
  <td class="fieldName">
   <label for="subject"><?php echo _("Subject"); ?>:</label>
  </td>
  <td class="fieldValue">
   <input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" size="50" <?php if ($accesskey_compose_subject != 'NONE') echo 'accesskey="' . $accesskey_compose_subject . '" '; echo $input_onfocus; ?> />
  </td>
 </tr>
</table>
</div>
