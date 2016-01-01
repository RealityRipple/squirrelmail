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
 *      $org_logo_str       - translated string containing orginization's logo
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
  <td colspan="2">
   <?php echo getIcon($icon_theme_path, 'login1.png', '', _("The SquirrelMail logo")); ?>
  </td>
 </tr>
 <tr>
  <td class="orgName" colspan="2">
   <?php echo $org_name_str; ?>
  </td>
 </tr>
 <tr>
  <td class="attr" colspan="2">
   <?php echo nl2br($sm_attribute_str) . (empty($sm_attribute_str) ? '' : '<br /><br />'); ?>
  </td>
 </tr>
 <tr>
  <td class="orgLogo">
   <img src="<?php echo $logo_path; ?>" width="50" alt="<?php echo $org_logo_str; ?>" />
  </td>
  <td>
   <table cellspacing="0">
    <tr>
     <td class="fieldName">
      <?php echo _("Name:"); ?>
     </td>
     <td class="fieldInput">
      <input type="text" name="<?php global $username_form_name; echo $username_form_name; ?>" value="<?php echo $login_field_value; ?>" id="login_username" class="input" onfocus="alreadyFocused=true;" <?php global $username_form_extra; echo $username_form_extra; ?> />
     </td>
    </tr>
    <tr>
     <td class="fieldName">
      <?php echo _("Password:"); ?>
     </td>
     <td class="fieldInput">
      <input type="password" name="<?php global $password_form_name; echo $password_form_name; ?>" value="" id="secretkey" class="input" onfocus="alreadyFocused=true;" <?php global $password_form_extra; echo $password_form_extra; ?> />
      <?php echo $login_extra; ?>
     </td>
    </tr>
    <?php if (!empty($plugin_output['login_form'])) echo $plugin_output['login_form']; ?>
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
</form>
