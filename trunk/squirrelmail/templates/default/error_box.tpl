<?php
/**
 * error_box.tpl
 *
 * Displays the simple error box.  This is different than the error list 
 * template that is displayed in footer.tpl.
 *
 * Variables available to this template:
 *      $errorMessage - Translated string containing error message to be
 *                      displayed.
 *      $error        - Translation of string "ERROR".  This string is
 *                      translated in functions that call this template to
 *                      avoid making multiple translations on this string
 *      $link         - Array containing link to display to go back, if desired.
 *                      If no link is dsired, this will be NULL.  The array
 *                      will contain the following elements:
 *              $link['URL']    - URL target for link
 *              $link['FRAME']  - Frame target for link
 *              $link['TEXT']   - Text to display for link
 * 
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

// Extract template variables
extract($t);
?>
<div style="text-align:center; width:100%">
<table class="table_errorBoxWrapper" cellspacing="0">
 <tr>
  <td>
   <table class="table_errorBox" cellspacing="0">
    <tr>
     <td class="error_header">
      <?php echo $error; ?>
     </td>
    </tr>
    <tr>
     <td class="error_message">
      <?php echo $errorMessage."\n"; ?>
     </td>
    </tr>
    <?php
        if (!is_null($link)) {
            ?>
    <tr>
     <td class="error_header">
      <a href="<?php echo $link['URL']; ?>" target="<?php echo $link['FRAME']; ?>"><?php echo $link['TEXT']; ?></a>
     </td>
    </tr>
            
            <?php
        }
    ?>
   </table>
  </td>
 </tr>
</table>
</div>
<br />