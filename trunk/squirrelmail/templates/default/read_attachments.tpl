<?php
/**
 * read_attachments.tpl
 *
 * Template used to generate the attachment list while reading a message.  This
 * template is called from the function formatAttachments() in functions/mime.php.
 *
 * The following variables are available in this template:
 *    $plugin_output  array  An array of extra output that may be added by plugin(s).
 *    $attachments - array containing info for all message attachments.  Each
 *                   element in the array represents a separate attachment and
 *                   contains the following elements:
 *       $el['Name']         - The name of the attachment
 *       $el['Description']  - Description of the attachment
 *       $el['DefaultHREF']  - URL to the action that should occur when the name is clicked
 *       $el['DownloadHREF'] - URL to download the attachment
 *       $el['ViewHREF']     - URL to view the attachment.  Empty if not available.
 *       $el['Size']         - Size of attachment in bytes.
 *       $el['ContentType']  - Content-Type of the attachment
 *       $el['OtherLinks']   - array containing links to any other actions
 *                             available for this attachment that might be
 *                             provided by plugins, for example.  Each element represents
 *                             a different action and contains the following elements:
 *            $link['HREF'] - URL to access the functionality
 *            $link['Text'] - Text representing the functionality.
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
if (count($attachments)==0) {
    # If there are no attachments, display nothing.
    return '';
}

?>
<div class="readAttachments">
<table cellspacing="0" class="table2">
 <tr>
  <td class="header5" colspan="5">
   <?php
      echo _("Attachments");
      if (!empty($plugin_output['attachments_top'])) echo $plugin_output['attachments_top'];
   ?>
  </td>
 </tr>
 <?php
    foreach ($attachments as $count=>$attachment) {
        ?>
 <tr class="<?php echo ($count%2 ? 'odd' : 'even'); ?>">
  <td class="attachName">
   <a href="<?php echo $attachment['DefaultHREF']; ?>"><?php echo $attachment['Name']; ?></a>
  </td>
  <td class="attachType">
   <small><?php echo $attachment['ContentType']; ?></small>
  </td>
  <td class="attachSize">
   <small><?php echo humanReadableSize($attachment['Size']); ?></small>
  </td>
  <td class="attachDesc">
   <small><?php echo $attachment['Description']; ?></small>
  </td>
  <td class="attachActions">
   <small>
   <a href="<?php echo $attachment['DownloadHREF']; ?>"><?php echo _("Download"); ?></a>
   <?php
    if (!empty($attachment['ViewHREF'])) {
        ?>
   &nbsp;|&nbsp;
   <a href="<?php echo $attachment['ViewHREF']; ?>"><?php echo _("View"); ?></a>
        <?php
    }

    foreach ($attachment['OtherLinks'] as $link) {
        ?>
   &nbsp;|&nbsp;
   <a href="<?php echo $link['HREF']; ?>"><?php echo $link['Text']; ?></a>
        <?php
    }
   ?>
   </small>
  </td>
 </tr>
        <?php
    }

    if (!empty($plugin_output['attachments_bottom'])) echo $plugin_output['attachments_bottom'];
 ?>
</table>
<table cellspacing="0" class="spacer">
 <tr>
  <td>
  </td>
 </tr>
</table>
</div>
