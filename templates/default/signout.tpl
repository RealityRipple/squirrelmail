<?php
/**
 * signout.tpl
 *
 * Template to create the signout page
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);

?>
<body>

<center>
<table width="50%" class="sqm_signout">
<tr width="100%"><th class="sqm_signoutBar">
  <?php echo _("Sign Out"); ?>
</th></tr>
<?php if (!empty($plugin_output['signout_message'])) echo $plugin_output['signout_message']; ?>
<tr width="100%"><td>
  <?php echo _("You have been successfully signed out."); ?><br />
  <a href="<?php echo $login_uri; ?>" target="<?php echo $frame_top; ?>"><?php echo _("Click here to log back in."); ?></a><br />
</td></tr>
<tr width="100%"><td class="sqm_signoutBar"><br /></td></tr>
</table>
</center>

