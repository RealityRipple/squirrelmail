<?php
/**
 * login.tpl
 *
 * Template to create the login page
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);

?>
<body onLoad="squirrelmail_loginpage_onload()" style="text-align:center">
<div id="sqm_login">
<form action="redirect.php" method="post" onSubmit="document.forms[0].js_autodetect_results.value=<?php echo SMPREF_JS_ON; ?>;">
<?php do_hook('login_top'); ?>
<table cellspacing="0">
 <tr>
  <td class="sqm_loginTop" colspan="2">
   <?php 
       echo $logo_str;
       echo $sm_attribute_str; 
   ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginOrgName" colspan="2">
   <?php echo $org_name_str; ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginFieldName">
   <?php echo _("Name:"); ?>
  </td>
  <td class="sqm_loginFieldInput">
   <?php echo $login_field; ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginFieldName">
   <?php echo _("Password:"); ?>
  </td>
  <td class="sqm_loginFieldInput">
   <?php 
       echo $password_field;
       echo concat_hook_function('login_form')
   ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_loginSubmit" colspan="2">
   <?php echo $submit_field; ?>
  </td>
 </tr>
</table>
</form>
<?php do_hook('login_bottom'); ?>
</div>
</body>
</html>
