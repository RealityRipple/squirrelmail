<?php
/**
 * signout.tpl
 *
 * Template to create the signout page
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);

$plugin_message = concat_hook_function('logout_above_text');
?>
<body>

<center>
<table width="50%" class="sqm_signout">
<tr width="100%"><th class="sqm_signoutBar">
  <?php echo _("Sign Out"); ?>
</th></tr>
<?php echo $plugin_message; ?>
<tr width="100%"><td>
  <?php echo _("You have been successfully signed out."); ?><br />
  <a href="<?php echo $login_uri; ?>" target="<?php echo $frame_top; ?>"><?php echo _("Click here to log back in."); ?></a><br />
</td></tr>
<tr width="100%"><td class="sqm_signoutBar"><br /></td></tr>
</table>
</center>

