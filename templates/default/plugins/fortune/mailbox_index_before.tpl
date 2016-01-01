<?php

/**
  * mailbox_index_before.tpl
  *
  * Template for outputting a fortune above the message 
  * list for the fortune plugin.
  *
  * The following variables are available in this template:
  *      + $color   - SquirrelMail colors array
  *      + $fortune - The fortune string to be displayed
  *
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


?>
<table cellpadding="0" cellspacing="0" border="0" bgcolor="<?php echo $color[10]; ?>" align="center">
  <tr>
    <td>
      <table width="100%" cellpadding="2" cellspacing="1" border="0" bgcolor="<?php echo $color[5]; ?>">
        <tr>
          <td align="center">
            <table>
              <tr>
                <td>
                  <div style="text-align: center;"><em><?php echo _("Today's Fortune"); ?></em></div>
                  <pre>
<?php echo $fortune; ?></pre>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
