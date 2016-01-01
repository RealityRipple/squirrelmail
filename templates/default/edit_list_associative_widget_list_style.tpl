<?php

/**
  * edit_list_associative_widget_list_style.tpl
  *
  * Template for constructing an associative edit list using a list-format layout.
  *
  * The following variables are available in this template:
  *
  * string   $name               The name of the edit list
  * mixed    $current_value      The currently selected value(s)
  * array    $possible_values    The original list of options in the edit list
  * array    $poss_value_folders When not empty, contains a list of names
  *                              to be used to populate a drop-down selection
  *                              list for the value input (instead of the
  *                              $input_value_widget textual input). If any
  *                              of the values is an array, it is assumed to
  *                              be an IMAP folder list in the format given
  *                              by sqimap_mailbox_list()
  * string   $folder_filter      Controls the folders listed by
  *                              $poss_value_folders. See $flag argument in
  *                              the sqimap_mailbox_option_list() function
  * boolean  $use_input_widget   Whether or not to present the key/value inputs
  * boolean  $use_delete_widget  Whether or not to present the $checkbox_widget
  * string   $checkbox_widget    A preconstructed checkbox used for deleting
  *                              elements from the edit list
  * string   $input_key_widget   A preconstructed input text box used
  *                              for adding new element keys to the edit list
  * string   $input_value_widget A preconstructed input text box used
  *                              for adding new element values to the edit list
  * int      $select_height      The size of the edit list select widget
FIXME: which inputs to use $aAttribs for? currently only the <select> tag
  * array    $aAttribs           Any extra attributes: an associative array,
  *                              where keys are attribute names, and values
  *                              (which are optional and might be null)
  *                              should be placed in double quotes as attribute
  *                              values (optional; may not be present)
  * string   $trailing_text      Any text given by the caller to be displayed
  *                              after the edit list input
  *
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


// Construct the add key/value inputs
//
echo '<table class="table2" cellspacing="0"><tr><td>';
if ($use_input_widget) {
//FIXME implement poss_key_folders here? probably not worth the trouble, is there a use case?
    echo _("Add") . '&nbsp;' . $input_key_widget . ' ';

// FIXME: shall we allow these "poss value folders" (folder list selection for edit list values) for NON-Associative EDIT_LIST widgets?
    if ($poss_value_folders) {
        echo '<select name="add_' . $name . '_value">';

        // Add each possible value to the select list
        foreach ($poss_value_folders as $real_value => $disp_value) {

            if ( is_array($disp_value) ) {
                // For folder list, we passed in the array of boxes
                $new_option = sqimap_mailbox_option_list(0, 0, 0, $disp_value, $folder_filter);

            } else {
                // Start the next new option string
                $new_option = '<option value="' . sm_encode_html_special_chars($real_value) . '"';

                // Add the display value to our option string
                $new_option .= '>' . sm_encode_html_special_chars($disp_value) . "</option>\n";
            }
            // And add the new option string to our select tag
            echo $new_option;
        }
        // Close the select tag and return our happy result
        echo '</select>';
    }
    else
        echo $input_value_widget . '<br />';
}


// Construct the select input showing all current values in the list
//
echo '<table class="table_messageList" cellspacing="0">';

$class = 'even';
$index = 0;

if (is_array($current_value))
    $selected = $current_value;
else
    $selected = array($current_value);

foreach ($possible_values as $key => $value) {

    if ($class == 'even') $class = 'odd';
    else $class = 'even';

    echo '<tr class="' . $class . '">'
       . '<td class="col_check" style="width:1%"><input type="checkbox" name="new_' . $name . '[' . urlencode($key) . ']" id="' . $name . '_list_item_' . urlencode($key) . '" value="' . sm_encode_html_special_chars($value);

    // having a selected item in the edit list doesn't have
    // any meaning, but maybe someone will think of a way to
    // use it, so we might as well put the code in
    //
    foreach ($selected as $default) {
        if ((string)$default == (string)$key) {
            echo '" checked="checked';
            break;
        }
    }

    echo '"></td>'
       . '<td><label for="' . $name . '_list_item_' . urlencode($key) . '">' . sm_encode_html_special_chars($key) . ' = ';

    if ($poss_value_folders) {
        foreach ($poss_value_folders as $real_value => $disp_value) {
            if ( is_array($disp_value) ) {
                foreach ($disp_value as $folder_info) {
                    if ($value == $folder_info['unformatted']) {
                        echo sm_encode_html_special_chars(str_replace('&nbsp;', '', $folder_info['formatted']));
                        break 2;
                    }
                }
            }
            else
                if ($value == $disp_value) {
                    echo sm_encode_html_special_chars($disp_value);
                    break;
                }
        }
    }
    else
        echo sm_encode_html_special_chars($value);

    echo '</label></td>'
             . "</tr>\n";

}

echo '</table>';


// Construct the delete input
//
if (!empty($possible_values) && $use_delete_widget)
    echo $checkbox_widget . '&nbsp;<label for="delete_' . $name . '">'
       . _("Delete Selected") . '</label>';


echo '</td></tr></table>';
