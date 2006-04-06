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
 *                          $login_link['URL']   - URL target for link
 *                          $login_link['FRAME'] - Frame target for link
 *      $errorMessage - Translated string containing error message to be
 *                      displayed.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
<table cellspacing="0">
 <tr>
  <td class="sqm_errorLogoutTop" colspan="2">
   <?php 
       echo $logo_str;
       echo $sm_attribute_str; 
   ?>
  </td>
 </tr>
</table>
<br>
<div style="width:70%; text-align:center; margin-left:auto; margin-right:auto">
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
      <?php echo '<a href="'.$login_link['URL'].'" target="'.$login_link['FRAME'].'">'. _("Go to the login page") .'</a>'; ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</div>
</div>