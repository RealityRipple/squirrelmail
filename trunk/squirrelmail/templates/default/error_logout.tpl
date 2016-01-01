<?php
/**
 * error_logout.tpl
 *
 * Displays error messages when user not logged in.
 * 
 * Variables available in this template:
 *      $logo_str     - String containing full HTML tag to display the
 *                      org logo.
 *      $sm_attribute_str - String containing SQM attributes to be displayed,
 *                          if any
 *      $login_link   - Array containing details needed to generate link to login
 *                      page.  Elements are:
 *                          $login_link['URI']   - URI target for link
 *                          $login_link['FRAME'] - Frame target for link
 *      $errorMessage - Translated string containing error message to be
 *                      displayed.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */
 
/** Extract template vars **/
extract ($t);
?>
<body>
<div id="sqm_errorLogout">
<table class="sqm_errorLogoutTop" cellspacing="0">
 <tr>
  <td colspan="2">
   <?php 
       echo $logo_str; if (!empty($logo_str)) echo '<br />'; 
       echo nl2br($sm_attribute_str) . (empty($sm_attribute_str) ? '' : '<br /><br />'); 
   ?>
  </td>
 </tr>
</table>
</div>
<br />

<table class="table_errorBoxWrapper" cellspacing="0">
 <tr>
  <td>
   <table class="table_errorBox" cellspacing="0">
    <tr>
     <td class="error_header">
      <?php echo _("ERROR"); ?>
     </td>
    </tr>
    <tr>
     <td class="error_message">
      <?php echo $errorMessage."\n"; ?>
     </td>
    </tr>
    <tr>
     <td class="error_header">
      <?php echo '<a href="'.$login_link['URI'].'" target="'.$login_link['FRAME'].'">'. _("Go to the login page") .'</a>'; ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
