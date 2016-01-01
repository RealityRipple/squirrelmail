<?php

/**
  * addressbook_list.tpl
  *
  * Template for the basic address book list
  * 
  * The following variables are available in this template:
  *
  * string  $form_action       The action for the main form tag
  * array   $current_page_args All known query string arguments for the
  *                            current page request, for use when constructing
  *                            links pointing back to same page (possibly
  *                            changing one of them); structured as an
  *                            associative array of key/value pairs
  * boolean $compose_new_win   Whether or not the user prefs are set to compose
  *                            messages in a popup window
  * int     $compose_width     Width of popup compose window if needed
  * int     $compose_height    Height of popup compose window if needed
  * int     $current_backend   Number of backend currently displayed.
  * string  $abook_select      Code for widget to display the address book
  *                            backend selection drop down
  * boolean $abook_has_extra_field TRUE if the address book contains an
  *                                additional field.  FALSE otherwise.
  * array   $backends          Contains all available abook backends for selection.
  *                            This will be empty if only 1 backend is available! 
  * array   $addresses         Contains backends in the address book.  Each element
  *                            is an array containing the following fields:
  *          ['BackendID']       - integer unique identifier for each source of 
  *                                addresses in the book.  this should also be
  *                                the same as the array key for this value
  *          ['BackendSource']   - description of each source of addresses
  *          ['BackendWritable'] - boolean TRUE if the address book can be
  *                                modified.  FALSE otherwise.
  *          ['Addresses']       - array containing address from this source.
  *                                Each array element contains the following:
  *                       ['FirstName']   - The entry's first name
  *                       ['LastName']    - The entry's last name (surname)
  *                       ['FullName']    - The entry's full name (first + last)
  *                       ['NickName']    - The entry's nickname
  *                       ['Email']       - duh
  *                       ['FullAddress'] - Email with full name or nick name
  *                                         optionally prepended.
  *                       ['Info']        - Additional info about this contact
  *                       ['Extra']       - Additional field, if provided.  NULL if
  *                                         this field is not provided by the book.
  *                       ['JSEmail']     - email address scrubbed for use with
  *                                         javascript functions.
  *
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage templates
  */

/** add required includes **/
include_once(SM_PATH . 'functions/template/abook_util.php');

/** extract template variables **/
extract($t);

/** Begin template **/
$source = $addresses[$current_backend];
$colspan = $abook_has_extra_field ? 6 : 5;
?>
<form action="<?php echo $form_action; ?>" method="post" id="address_book_form" name="address_book_form">
<input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token(); ?>" />
<div id="addressList">
<table cellspacing="0">
 <tr>
  <td colspan="<?php echo $colspan; ?>" class="header1">
   <?php echo $source['BackendSource']; ?>
  </td>
 </tr>
 <tr>
  <td colspan="3" class="abookPaginationAndButtons">
    <div class="abookPagination">
      <?php
          $this->display('addressbook_paginator.tpl');
          if (!empty($plugin_output['address_book_navigation'])) echo $plugin_output['address_book_navigation'];
      ?>
    </div><div class="abookButtons">
      <input type="submit" value="<?php echo _("Edit"); ?>" name="editaddr" id="editaddr" />
      <input type="submit" value="<?php echo _("Delete"); ?>" name="deladdr" id="deladdr" />
      <input type="submit" value="<?php echo _("Compose To") . ($javascript_on && $compose_new_win ? '" onclick="var send_to = \'\'; var f = document.forms.length; var i = 0; var grab_next_hidden = \'\'; while (i < f) { var e = document.forms[i].elements.length; var j = 0; while (j < e) { if (document.forms[i].elements[j].type == \'checkbox\' && document.forms[i].elements[j].checked) { var pos = document.forms[i].elements[j].value.indexOf(\'_\'); if (pos >= 1) { grab_next_hidden = document.forms[i].elements[j].value; } } else if (document.forms[i].elements[j].type == \'hidden\' && grab_next_hidden == document.forms[i].elements[j].name) { if (send_to != \'\') { send_to += \', \'; } send_to += document.forms[i].elements[j].value; } j++; } i++; } if (send_to != \'\') { comp_in_new(\''. $base_uri . 'src/compose.php?send_to=\' + send_to, ' . $compose_width . ', ' . $compose_height . '); } return false;"' : '"'); ?> name="compose_to" id="compose_to" />
      <?php if (!empty($plugin_output['address_book_buttons'])) echo $plugin_output['address_book_buttons']; ?>
    </div>
  </td>
  <td colspan="<?php echo ($colspan - 3); ?>" class="abookSwitch">
   <?php if (!empty($plugin_output['address_book_filter'])) echo $plugin_output['address_book_filter']; ?>
   <?php
    if (count($backends) > 0) {
        ?>
   <select name="new_bnum">
    <?php
        foreach ($backends as $id=>$name) {
            echo '<option value="' . $id . '"' . ($id == $current_backend ? ' selected="selected"' : '') . '>' . $name . '</option>' . "\n";
        }
    ?>
   </select>
   <input type="submit" value="<?php echo _("Change"); ?>" name="change_abook" id="change_abook" />
        <?php
    } else {
        echo '&nbsp;';
    }
   ?>
  </td>
 </tr>
 <tr>
  <td class="colHeader" style="width:1%"><input type="checkbox" name="toggleAll" id="toggleAll" title="<?php echo _("Toggle All"); ?>" onclick="toggle_all('address_book_form', 'sel', false); return false;" /></td>
  <td class="colHeader" style="width:15%"><?php echo addAbookSort('nickname', $current_page_args); ?></td>
  <td class="colHeader"><?php echo addAbookSort('fullname', $current_page_args); ?></td>
  <td class="colHeader"><?php echo addAbookSort('email', $current_page_args); ?></td>
  <td class="colHeader"><?php echo addAbookSort('info', $current_page_args); ?></td>
  <?php
   if ($abook_has_extra_field) {
    echo '<td class="colHeader"></td>';
   }
  ?>
 </tr>
 <?php
    $count = 1;
    if (count($source['Addresses']) == 0) {
        echo '<tr><td class="abookEmpty" colspan="'.$colspan.'">'._("Address book is empty").'</td></tr>'."\n";
    }
    foreach ($source['Addresses'] as $contact) {
        echo '<tr class="' . ($count%2 ? 'even' : 'odd') . '">';
        if (!empty($contact['special_message'])) {
            echo '<td class="abookEmpty" colspan="' . $colspan . '">' . $contact['special_message'] . '</td>';
        } else {
            $id = $current_backend . '_' . $contact['NickName'];
            ?>
  <td class="abookField" style="width:1%"><?php echo ($source['BackendWritable'] ? '<input type="checkbox" name="sel[' . $count . ']" value="'.$id.'" id="'.$id.'" ' . (!empty($plugin_output['address_book_checkbox_extra']) ? $plugin_output['address_book_checkbox_extra'] : '') . ' />' : ''); ?></td>
  <td class="abookField" style="width:15%"><label for="<?php echo $id . '">' . $contact['NickName']; ?></label></td>
  <td class="abookField"><label for="<?php echo $id . '">' . $contact['FullName']; ?></label></td>
  <td class="abookField"><input type="hidden" name="<?php echo $id; ?>" value="<?php echo rawurlencode($contact['FullAddress']); ?>" /><?php echo composeLink($contact); ?></td>
  <td class="abookField"><label for="<?php echo $id . '">' . $contact['Info']; ?></label></td>
        <?php 
            if ($abook_has_extra_field) {
                echo '<td class="abookField">'.$contact['Extra'].'</td>'."\n";
            }
        }
        ?>
 </tr>
        <?php
        $count++;
    }
?>
</table>
</div>
</form>
