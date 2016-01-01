<?php
/**
 * help_chapter.tpl
 *
 * Template to display help chapter
 * 
 * The following variables are available in this template:
 *      $chapter_number     - current chapter number.  Empty on error.
 *      $chapter_count      - total number of chapters in the help.  Empty on error.
 *      $chapter_title      - title of this chapter.  Empty on error.
 *      $chapter_summary    - summary of this chapter.  Empty on error.
 *      $error_msg          - Error message if an error is generated.  NULL if
 *                            no error is thrown.
 *      $sections           - array containing all secionts of this chapter.  Each
 *                            element contains the following fields:
 *          $el['SectionNumber']    - the number of each section
 *          $el['SectionTitle']     - the title of this section
 *          $el['SectionText']      - the text for this section
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
<div id="help">
<table cellspacing="0" class="table1">
 <tr>
  <td class="header1">
   <?php echo _("Help"); ?>
  </td>
 </tr>
 <tr>
  <td class="nav">
   <small>
   <?php
    if ($chapter_number == 1) {
        echo _("Previous");
    } else {
        ?>
   <a href="../src/help.php?chapter=<?php echo $chapter_number - 1; ?>"><?php echo _("Previous"); ?></a>
        <?php
    }
   ?>
   |
   <a href="../src/help.php"><?php echo _("Table of Contents"); ?></a>
   |
   <?php
    if ($chapter_number < $chapter_count) {
        ?>
   <a href="../src/help.php?chapter=<?php echo $chapter_number + 1; ?>"><?php echo _("Next"); ?></a>
        <?php
    } else {
        echo _("Next");
    }
   ?>
   </small>
  </td>
 </tr>
 <tr>
  <td class="help">
   <?php
    if (!is_null($error_msg)) {
        error_box($error_msg);
    } else {
        ?>
   <h1>
    <?php echo $chapter_number; ?> - <?php echo $chapter_title; ?>
   </h1>
   <h2>
    <?php echo $chapter_summary; ?>
   </h2>
   <?php
        foreach ($sections as $section) {
            ?>
   <h3>
    <?php echo $chapter_number; ?>.<?php echo $section['SectionNumber']; ?> - <?php echo $section['SectionTitle']; ?>
   </h3>
   <?php echo $section['SectionText']; ?>
            <?php
        }
    }
   ?>
  </td>
 </tr>
 <tr>
  <td class="nav">
   <small><a href="#pagetop"><?php echo _("Top"); ?></a></small>
  </td>
 </tr>
</table>
</div>