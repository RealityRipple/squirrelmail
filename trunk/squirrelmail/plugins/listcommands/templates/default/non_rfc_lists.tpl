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
  * @copyright &copy; 1999-2006 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage listcommands
  */


// retrieve the template vars
//
extract($t);


?><html><body><form method="post" action="">
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
  <tr>
    <td colspan="3" align="center" bgcolor="<?php echo  $color[0] ?>">
      <b><?php echo _("Options") . " - " . _("Mailing Lists"); ?></b>
    </td>
  </tr>
  <tr>
    <td colspan="3">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="3">Manage the (non-RFC-compliant) mailing lists that you are subscribed to for the purpose of providing one-click list replies when responding to list messages.  You only need to enter any lists you are subscribed to that do not already comply with RFC 2369.<br /><br />When entering a new list, input the full email address for the main list.</td>
  </tr>
  <tr>
    <td colspan="3">&nbsp;</td>
  </tr>
</table>
<table width="80%" align="center" border="0" cellpadding="2" cellspacing="0">
  <tr>
    <td>
      <?php echo _("Enter new mailing list"); ?>:
    </td>
    <td align="right">
      <input type="text" name="newlist" size="30" />
    </td>
    <td>
      <input type="submit" name="addlist" value="<? echo _("Add"); ?>" size="30" />
    </td>
  </tr>
  <tr>
    <td colspan="3">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><? echo _("Existing mailing lists"); ?>:</td>
    <td align="center" colspan="2">
      <table cellpadding="2">
<?php
    foreach($lists as $index => $list) {
        echo '<tr><td>' . $list . '</td><td><input type="submit" name="deletelist[' . $index . ']" value="' . _("Delete") . '" /></td></tr>';
    }
?>
      </table>
    </td>
  </tr>
</table>
</form>
</body>
</html>
