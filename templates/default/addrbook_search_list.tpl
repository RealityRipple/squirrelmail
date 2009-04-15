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
 * @copyright &copy; 1999-2009 The SquirrelMail Project Team
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
if ($use_js) {
    insert_javascript(); 
}
?>
<div id="addressList">
<table cellspacing="0">
 <tr>
  <td class="colHeader" style="width:1%"></td>
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
    foreach ($addresses as $index=>$contact) {
        ?>
 <tr class=<?php echo '"'.(($index+1)%2 ? 'even' : 'odd').'"'; ?>>
  <td class="abookCompose" style="width:1%">
   <?php
    if ($use_js) {
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
  <td class="abookField"><a href="javascript:to_and_close('<?php echo $contact['JSEmail']; ?>')"><?php echo $contact['Email']; ?></a></td>
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
if (!$use_js) {
    echo '<input type="submit" name="addr_search_done" value="'. _("Use Addresses") .'" />'."\n";
    echo '<input type="submit" name="addr_search_cancel" value="'. _("Cancel") .'" />'."\n";
}
?>
</div>
