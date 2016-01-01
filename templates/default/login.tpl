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
 *      $plugin_output      - An array of extra output that may be added by 
 *                            plugin(s).
 * 
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);

?><body onload="squirrelmail_loginpage_onload()">
<form name="login_form" id="login_form" action="redirect.php" method="post" onsubmit="document.login_form.js_autodetect_results.value=1">
<?php if (!empty($plugin_output['login_top'])) echo $plugin_output['login_top']; ?>
<div id="sqm_login">
<table cellspacing="0">
 <tr>
  <td class="sqm_loginTop" colspan="2">
   <?php 
       echo $logo_str; if (!empty($logo_str)) echo '<br />'; 
       echo nl2br($sm_attribute_str) . (empty($sm_attribute_str) ? '' : '<br /><br />'); 
   ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginOrgName" colspan="2">
   <?php echo $org_name_str; ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginFieldName"><label for="login_username">
   <?php echo _("Name:"); ?>
  </label></td>
  <td class="sqm_loginFieldInput">
   <input type="text" name="<?php global $username_form_name; echo $username_form_name; ?>" value="<?php echo $login_field_value; ?>" id="login_username" onfocus="alreadyFocused=true;" <?php global $username_form_extra; echo $username_form_extra; ?> />
  </td>
 </tr>
 <tr>
  <td class="sqm_loginFieldName"><label for="secretkey">
   <?php echo _("Password:"); ?>
  </label></td>
  <td class="sqm_loginFieldInput">
   <input type="password" name="<?php global $password_form_name; echo $password_form_name; ?>" value="" id="secretkey" onfocus="alreadyFocused=true;" <?php global $password_form_extra; echo $password_form_extra; ?> />
   <?php echo $login_extra; ?>
  </td>
 </tr>
 <?php if (!empty($plugin_output['login_form'])) echo $plugin_output['login_form']; ?>
 <tr>
  <td class="sqm_loginSubmit" colspan="2">
   <input type="submit" value="<?php echo _("Login"); ?>" />
  </td>
 </tr>
</table>
</div>
</form>
