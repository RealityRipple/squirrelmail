<?php
/**
 * printer_friendly_top.tpl
 *
 * Top frame of the printer-friendly window.  Bt default, this is only displayed
 * when javascript is enabled.
 * 
 * There are no additional variables given to this template.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<script type="text/javascript">
<!--
function printPopup() {
    parent.frames[1].focus();
    parent.frames[1].print();
}
-->
</script>
<div class="printerFriendlyTop">
<table class="table_blank" cellspacing="0">
 <tr>
  <td>
   <input type="button" value="<?php echo _("Print"); ?>" onclick="printPopup()" />
   <input type="button" value="<?php echo _("Close"); ?>" onclick="window.parent.close()" />
  </td>
 </tr>
</table>
</div>