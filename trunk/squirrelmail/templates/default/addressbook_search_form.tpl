<?php
/**
 * addressbook_search_form.tpl
 *
 * Display the form for searching the address book.  Called from addrbook_search.php
 * 
 * The following variables are available in this template:
 *      $use_js   - boolean TRUE if we should use Javascript in the address book
 *      $backends - array containing list of all available backends.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
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
<div id="addrBookSearch">
<table cellspacing="0" class="wrapper">
 <tr>
  <td class="header2">
   <?php echo _("Address book search"); ?>
  </td>
 </tr>
 <tr>
  <td>
<table cellspacing="0">
 <tr>
  <td>
   <label for="query"><?php echo _("Search for"); ?>:</label>
  </td>
  <td>
   <input type="text" id="query" name="query" />
  </td>
  <td>
   <?php
    if (count($backends) > 1) {
        ?>
   <label for="backend"><?php echo _("in"); ?></label>
   <select name="backend" id="backend">
        <?php
        foreach ($backends as $id=>$name) {
            echo '<option value="'.$id.'">'.sm_encode_html_special_chars($name).'</option>'."\n";
        }
        ?>
   </select>
        <?php
    } else {
        ?>
   <input type="hidden" name="backend" value="-1" />
        <?php
    }
   ?>
  </td>
 </tr>
 <tr>
  <td colspan="3" class="buttons">
   <input type="submit" name="show" value=<?php echo '"'._("Search").'"'; ?> />
   &nbsp;&nbsp;
   <input type="submit" name="listall" value=<?php echo '"'._("List all").'"'; ?> />
   <?php
    if ($javascript_on && $compose_addr_pop) {
        ?>
   &nbsp;&nbsp;
   <input type="submit" onclick="parent.close()" value=<?php echo '"'._("Close").'"'; ?> />
        <?php
    }
   ?>
  </td>
 </tr>
</table>
  </td>
 </tr>
</table>
</div>
