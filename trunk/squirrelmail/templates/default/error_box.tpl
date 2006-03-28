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
 * 
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
   </table>
  </td>
 </tr>
</table>
</div>
<br>