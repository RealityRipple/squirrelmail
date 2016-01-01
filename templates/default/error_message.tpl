<?php

/**
 * error_message.tpl
 *
 * Template for viewing error handler error messages
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */


/* retrieve the template vars */
extract($t);

if (isset($aErrors) && is_array($aErrors)) {
?>
<div id="error_list">
<table class="error_table">
  <tr class="error_thead">
    <td class="error_thead_caption" colspan="2">
       <div class="thead_caption"><?php echo _("SquirrelMail notice messages"); ?></div>
    </td>
  </tr>
<?php
    foreach($aErrors as $aError) {
?>
  <tr class="error_row">
    <td class="error_key">
       <?php echo _("Category:"); ?>
    </td>
    <td class="error_val">
       <?php echo join(', ', $aError['category']); ?>
    </td>
  </tr>
  <tr>
     <td class="error_key">
       <?php echo _("Message:"); ?>
     </td>
     <td class="error_val">
       <?php echo $aError['message']; ?>
     </td>
  </tr>
<?php if (isset($aError['extra']) && is_array($aError['extra'])) {
           foreach ($aError['extra'] as $sKey => $sValue) { ?>
  <tr class="error_row">
    <td class="error_key">
      <?php echo $sKey; ?>:
    </td>
    <td class="error_val">
      <?php echo $sValue; ?>
    </td>
  </tr>
<?php     } // foreach ($aError['extra'] as sKkey => $sValue)
       }   // isset($aError['extra']) && is_array($aError['extra']))
?>

<?php if (isset($aError['tip']) && ($aError['tip'])) { ?>
  <tr class="error_row">
    <td class="error_key">
      <?php echo _("Tip:"); ?>
    </td>
    <td class="error_val">
      <?php echo $aError['tip']; ?>
    </td>
  </tr>
<?php }   // (isset($aError['tip']) && ($aError['tip']))
?>

<?php if (isset($aError['link']) && ($aError['link'])) { ?>
  <tr class="error_row">
    <td class="error_key">
      <?php echo _("More info:"); ?>
    </td>
    <td class="error_val">
      <?php echo $aError['link']; ?>
    </td>
  </tr>
<?php }   // (isset($aError['link']) && ($aError['link']))
        ?>
 <tr class="error_thead">
   <td class="error_thead_caption" colspan="2">
   </td>
 </tr>
        <?php
    } // foreach($aErrors as $aError)
?>
</table>
</div>
<?php
} // isset($aErrors)
