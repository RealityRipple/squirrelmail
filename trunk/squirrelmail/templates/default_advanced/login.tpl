<?php
/**
 * login.tpl
 *
 * Template to create the login page
 *
 * The following variables are available to this template:
 *      $logo_str           - string containing HTML to display the org logo
 *      $logo_path          - path to the org logo, in case you want to do
 *                            something else with it.
 *      $sm_attribute_str   - string containg SQM attributes.  Will be empty if
 *                            this has been disabled by the admin.
 *      $org_name_str       - translated string containing orginization's name
 *      $login_field_value  - default value for the user name field
 *      $login_extra        - Some extra form fields needed by SquirrelMail
 *                            for the login.  Template designers SHOULD ALWAYS
 *                            INCLUDE this value somewhere in the form.
 *      $plugin_extra       - Extra table row(s) that may be added by plugin(s).
 * 
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

// add includes
require(SM_PATH . 'templates/util_global.php');

/* retrieve the template vars */
extract($t);

?>
<div id="sqm_login">
<table cellspacing="0">
 <tr>
  <td colspan="2">
   <?php echo getIcon($icon_theme_path, 'login1.png', ''); ?>
  </td>
 </tr>
 <tr>
  <td class="orgName" colspan="2">
   <?php echo $org_name_str; ?>
  </td>
 </tr>
 <tr>
  <td class="attr" colspan="2">
   <?php echo $sm_attribute_str; ?>
  </td>
 </tr>
 <tr>
  <td class="orgLogo">
   <img src="<?php echo $logo_path; ?>" width="50px" />
  </td>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="fieldName">
      <?php echo _("Name:"); ?>
     </td>
     <td class="fieldInput">
      <input type="text" name="login_username" value="<?php echo $login_field_value; ?>" id="login_username" class="input" />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Password:"); ?>
     </td>
     <td class="fieldInput">
      <input type="password" name="secretkey" value="" id="secretkey" class="input" />
      <?php echo $login_extra; ?>
     </td>
    </tr>
    <?php echo $plugin_extra; ?>
    <tr>
     <td class="loginSubmit" colspan="2">
      <input type="image" src="<?php echo getIconPath($icon_theme_path, 'login_submit.png', _("Login"), _("Login")); ?>" alt="<?php echo _("Login"); ?>" />
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</div>
