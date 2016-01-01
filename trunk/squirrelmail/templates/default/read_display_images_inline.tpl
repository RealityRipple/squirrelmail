<?php
/**
 * read_display_images_inline.tpl
 *
 * Template for displaying attached images inline, when desired by the user.
 * 
 * The following variables are available in this template:
 *      $images - array containing all the images to be displayed.  Each element
 *                is an array representing an image and contains the following elements:
 *          $im['Name']        - The name of the attached image
 *          $im['DisplayURL']  - URL for use with src attribute of img tag to display the image
 *          $im['DownloadURL'] - URL to download the image. 
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
if (count($images) == 0) {
    # If we don't have any images, don't do anything
    return '';
}

?>
<div class="readInlineImages">
<table cellspacing="0" class="table_blank">
 <?php
  foreach ($images as $img) {
    ?>
 <tr>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="header5">
      <small><?php echo $img['Name']; ?></small>
      <?php
        if (!empty($img['DownloadURL'])) {
            ?>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <a href="<?php echo $img['DownloadURL']; ?>"><?php echo _("Download"); ?></a>
            <?php
        }
      ?>
     </td>
     <td>&nbsp;</td>
    </tr>
    <tr>
     <td colspan="2" class="image">
      <img src="<?php echo $img['DisplayURL']; ?>" />
     </td>
    </tr>
   </table>
  </td>
 </tr>
    <?php
  }
 ?>
</table>
</div>