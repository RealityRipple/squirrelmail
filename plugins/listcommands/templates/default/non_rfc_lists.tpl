<?php

/**
 * non_rfc_lists.tpl
 *
 * Template for listcommands non-RFC-compliant list subscriptions 
 * management screen
 *
 * The following variables are available in this template:
 *      + $lists - The lists that the user currently has 
 *                 configured (an array of list addresses, 
 *                 keyed by an ID number)
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */


// retrieve the template vars
//
extract($t);


?><form method="post" action="">
<div id="optionGroups">
<table cellspacing="0">
  <tr>
    <td class="header1" colspan="2">
      <?php echo _("Options") . " - " . _("Mailing Lists"); ?>
    </td>
  </tr>
  <tr>
    <td colspan="2"><?php echo _("Manage the (non-RFC-compliant) mailing lists that you are subscribed to for the purpose of providing one-click list replies when responding to list messages. You only need to enter any lists you are subscribed to that do not already comply with RFC 2369.") . '<br /><br />' . _("When entering a new list, input the full email address for the address from which list postings are delivered."); ?><br /><br /></td>
  </tr>
  <tr>
    <td align="right">
      <?php echo _("Enter new mailing list"); ?>:
    </td>
    <td align="left">
      <input type="text" name="newlist" size="30" />
      <input type="submit" name="addlist" value="<?php echo _("Add"); ?>" size="30" />
    </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td align="right" valign="top"><?php echo _("Existing mailing lists"); ?>:</td>
    <td align="center">
      <table border="0" cellpadding="0" cellspacing="0">
<?php
    foreach($lists as $index => $list) {
        echo '<tr><td>' . $list . '</td><td><input type="submit" name="deletelist[' . $index . ']" value="' . _("Delete") . '" /></td></tr>';
    }
?>
      </table>
    </td>
  </tr>
</table>
</div>
</form>
