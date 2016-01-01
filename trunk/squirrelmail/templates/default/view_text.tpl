<?php
/**
 * view_text.tpl
 *
 * Tempalte for displaying a simple .txt or .html attachment
 * 
 * The following variables are available in this template:
 *      $view_message_href      - URL to navigate back to the main message
 *      $view_unsafe_image_href - URL to toggle viewing unsafe images
 *      $download_href          - URL to download the attachment as a file
 *      $body                   - Body of the attachment to be displayed
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
<div class="viewText">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header2">
   <?php echo _("Viewing a text attachment"); ?> -
   <a href="<?php echo $view_message_href; ?>"><?php echo _("View message"); ?></a>
  </td>
 </tr>
 <tr>
  <td class="actions">
   <?php
    if (!empty($view_unsafe_image_href)) {
        ?>
   <a href="<?php echo $view_unsafe_image_href; ?>"><?php echo _("View Unsafe Images"); ?></a> |
        <?php
    }
   ?>
   <a href="<?php echo $download_href; ?>"><?php echo _("Download this as a file"); ?></a>
  </td>
 </tr>
 <tr>
  <td class="spacer">
  </td>
 </tr>
 <tr>
  <td class="text">
   <?php echo $body; ?>
  </td>
 </tr>
</table>
</div>