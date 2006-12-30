<?php
/**
 * printer_friendly_bottom.tpl
 *
 * Display the printer friendly version of an email.  This is called "_bottom"
 * because when javaascript is enabled, the printer friendly view is a window
 * with two frames.
 * 
 * The following variables are available in this template:
 *      $headers      - array containing the headers to be displayed for this email.
 *                      Each element in the array represents a separate header.
 *                      The index of each element is the field name; the value is
 *                      the value of that field.
 *      $message_body - formatted, scrubbed body of the email.
 *      $attachments  - array containing info for all message attachments.  Each
 *                      element in the array represents a separate attachment and
 *                      contains the following elements:
 *         $el['Name']         - The name of the attachment
 *         $el['Description']  - Description of the attachment
 *         $el['DefaultHREF']  - URL to the action that should occur when the name is clicked
 *         $el['DownloadHREF'] - URL to download the attachment
 *         $el['ViewHREF']     - URL to view the attachment.  Empty if not available.
 *         $el['Size']         - Size of attachment in bytes.
 *         $el['ContentType']  - Content-Type of the attachment
 *         $el['OtherLinks']   - array containing links to any other actions
 *                               available for this attachment that might be
 *                               provided by plugins, for example.  Each element represents
 *                               a different action and contains the following elements:
 *              $link['HREF'] - URL to access the functionality
 *              $link['Text'] - Text representing the functionality.
 *      
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/** extract template variables **/
extract($t);

/** Begin template **/
?>
<div class="printerFriendly">
<table cellspacing="0" class="table_blank">
 <?php
    foreach ($headers as $field=>$value) {
        # If the value is empty, skip the entry.
        if (empty($value))
            continue;
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
<hr />
<?php echo $message_body; ?>
<?php
if (count($attachments) > 0) {
    ?>
<hr />
<b>Attachments:</b>
 <?php
    foreach ($attachments as $attachment) {
        ?>
<table cellspacing="0" border="1" class="attach">
 <tr>
  <td colspan="2" class="attachName">
   <?php echo $attachment['Name']; ?>
  </td>
 </tr>
 <tr>
  <td class="attachField">
   <?php echo _("Size"); ?>:
  </td>
  <td class="attachFieldValue">
   <?php echo humanReadableSize($attachment['Size']); ?>
  </td>
 </tr>
 <tr>
  <td class="attachField">
   <?php echo _("Type"); ?>:
  </td>
  <td class="attachFieldValue">
   <?php echo $attachment['ContentType']; ?>
  </td>
 </tr>
 <tr>
  <td class="attachField">
   <?php echo _("Info"); ?>:
  </td>
  <td class="attachFieldValue">
   <?php echo $attachment['Description']; ?>
  </td>
 </tr>
</table>
        <?php
    }
}
?>
</div>
