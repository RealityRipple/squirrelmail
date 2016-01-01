<?php
/**
 * addrbook_search_list.tpl
 *
 * Display a list of addresses from the search forms
 * 
 * The following variables are available in this template:
 *      $use_js             - boolean TRUE if we should use Javascript in this book.
 *      $include_abook_name - boolean TRUE if the resuls should also display
 *                            the name of the address book the result is in.
 *      $addresses - array containing search results.  Each element contains the 
 *                 following fields:
 *              $el['FirstName'] - The entry's first name
 *              $el['LastName']  - The entry's last name (surname)
 *              $el['FullName']  - The entry's full name (first + last)
 *              $el['NickName']  - The entry's nickname
 *              $el['Email']     - duh
 *              $el['FullAddress'] - Email with full name or nick name
 *                                 optionally prepended.
 *              $el['Info']      - Additional info about this contact
 *              $el['Extra']     - Additional field, if provided.  NULL if this
 *                                 field is not provided by the book.
 *              $el['JSEmail']   - email address scrubbed for use with
 *                                 javascript functions.
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
$colspan = $include_abook_name ? 5 : 4;
?>
<?php
if ($javascript_on) {
    insert_javascript(); 
}
?>
<div id="addressList">
<table cellspacing="0">
 <tr>
  <td class="colHeader" style="width:1%; font-size: 8pt; white-space: nowrap;">
<?php
if ($javascript_on && !$compose_addr_pop) {
?>
    <input type="checkbox" id="checkAllTo" onClick="CheckAll('T');"><label for="checkAllTo"><?php echo _("All");?></label> &nbsp;
    <input type="checkbox" id="checkAllCc" onClick="CheckAll('C');"><label for="checkAllCc"><?php echo _("Cc");?></label> &nbsp;
    <input type="checkbox" id="checkAllBcc" onClick="CheckAll('B');"><label for="checkAllBcc"><?php echo _("Bcc");?></label>
<?php
}
?>
  </td>
  <td class="colHeader"><?php echo _("Name"); ?></td>
  <td class="colHeader"><?php echo _("E-mail"); ?></td>
  <td class="colHeader"><?php echo _("Info"); ?></td>
  <?php
   if ($include_abook_name) {
    echo '<td class="colHeader">'. _("Source") .'</td>';
   }
  ?>
 </tr>
 <?php
    if (count($addresses) == 0) {
        echo '<tr><td class="abookEmpty" colspan="'.$colspan.'">'._("Address book is empty").'</td></tr>'."\n";
    }

    if ($compose_addr_pop) {
      $addr_str = '<a href="javascript:to_and_close(\'%1$s\')">%1$s</a>';
    } else {
      $addr_str = '%1$s';
    }
    
    foreach ($addresses as $index=>$contact) {
        ?>
 <tr class=<?php echo '"'.(($index+1)%2 ? 'even' : 'odd').'"'; ?>>
  <td class="abookCompose" style="width:1%">
   <?php
    if ($compose_addr_pop) {
        ?>
   <a href="javascript:to_address('<?php echo $contact['JSEmail']; ?>')"><?php echo _("To"); ?></a> |
   <a href="javascript:cc_address('<?php echo $contact['JSEmail']; ?>')"><?php echo _("Cc"); ?></a> |
   <a href="javascript:bcc_address('<?php echo $contact['JSEmail']; ?>')"><?php echo _("Bcc"); ?></a>
        <?php
    } else {
        ?>
   <input type="checkbox" name=<?php echo '"send_to_search[T'.$index.']"'; ?> value=<?php echo '"'.$contact['FullAddress'].'"'; ?> id=<?php echo '"send_to_search_T'.$index.'_"'; ?> /><label for=<?php echo '"send_to_search_T'.$index.'_"'; ?>><?php echo _("To"); ?></label>
   <input type="checkbox" name=<?php echo '"send_to_search[C'.$index.']"'; ?> value=<?php echo '"'.$contact['FullAddress'].'"'; ?> id=<?php echo '"send_to_search_C'.$index.'_"'; ?> /><label for=<?php echo '"send_to_search_C'.$index.'_"'; ?>><?php echo _("Cc"); ?></label>
   <input type="checkbox" name=<?php echo '"send_to_search[B'.$index.']"'; ?> value=<?php echo '"'.$contact['FullAddress'].'"'; ?> id=<?php echo '"send_to_search_B'.$index.'_"'; ?> /><label for=<?php echo '"send_to_search_B'.$index.'_"'; ?>><?php echo _("Bcc"); ?></label>
        <?php
    }
   ?> 
  </td>
  <td class="abookField"><?php echo $contact['FullName']; ?></td>
  <td class="abookField"><?php echo sprintf($addr_str, $contact['Email']); ?></td>
  <td class="abookField"><?php echo $contact['Info']; ?></td>
        <?php 
        if ($include_abook_name) {
            echo '<td class="abookField">'.$contact['Source'].'</td>'."\n";
        }
        ?>
 </tr>
        <?php
    }
?>
</table>
<?php
if (!$compose_addr_pop) {
    echo '<input type="submit" name="addr_search_done" value="'. _("Use Addresses") .'" />'."\n";
    echo '<input type="submit" name="addr_search_cancel" value="'. _("Cancel") .'" />'."\n";
} else {
    echo '<input type="submit" onClick="javascript:parent.close();" name="close_window" value="' . _("Close Window") . '" />'. "\n";
}
?>
</div>
