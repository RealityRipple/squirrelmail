<?php

/**
  * edit_list_associative_widget.tpl
  *
  * Template for constructing an associative edit list.
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
//FIXME implement poss_key_folders here? probably not worth the trouble, is there a use case?
if ($use_input_widget) {
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
echo '<select name="new_' . $name . '[]" multiple="multiple" size="' . $select_height . '"';

$onchange = '';
foreach ($aAttribs as $key => $value) {
    if (strtolower($key) == 'onchange' && $javascript_on) {
        $onchange = $value;
        continue;
    }
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}

// FIXME: this can be fooled by having the delimiter " = " in a key value - in general, we may want to use a different delimiter other than " = "
if ($javascript_on) {
    echo ' onchange="';
    if (!empty($onchange)) echo $onchange;
    echo ' if (typeof(window.addinput_key_' . $name . ') == \'undefined\') { var f = document.forms.length; var i = 0; var pos = -1; while( pos == -1 && i < f ) { var e = document.forms[i].elements.length; var j = 0; while( pos == -1 && j < e ) { if ( document.forms[i].elements[j].type == \'text\' && document.forms[i].elements[j].name == \'add_' . $name . '_key\' ) { pos = j; j=e-1; i=f-1; } j++; } i++; } if( pos >= 0 ) { window.addinput_key_' . $name . ' = document.forms[i-1].elements[pos]; } } if (typeof(window.addinput_value_' . $name . ') == \'undefined\') { var f = document.forms.length; var i = 0; var pos = -1; while( pos == -1 && i < f ) { var e = document.forms[i].elements.length; var j = 0; while( pos == -1 && j < e ) { if ( document.forms[i].elements[j].type == \'text\' && document.forms[i].elements[j].name == \'add_' . $name . '_value\' ) { pos = j; j=e-1; i=f-1; } j++; } i++; } if( pos >= 0 ) { window.addinput_value_' . $name . ' = document.forms[i-1].elements[pos]; } } for (x = 0; x < this.length; x++) { if (this.options[x].selected) { pos = this.options[x].text.indexOf(\' = \'); if (pos > -1) { window.addinput_key_' . $name . '.value = this.options[x].text.substr(0, pos); if (typeof(window.addinput_value_' . $name . ') != \'undefined\') window.addinput_value_' . $name . '.value = this.options[x].text.substr(pos + 3); } break; } }"';
// NOTE: i=f-1; j=e-1 is in lieu of break 2
}

echo ">\n";


if (is_array($current_value))
    $selected = $current_value;
else
    $selected = array($current_value);


// Add each possible value to the select list.
//
foreach ($possible_values as $key => $value) {

    // Start the next new option string.
    //
    echo '<option value="' . urlencode($key) . '"';

    // having a selected item in the edit list doesn't have
    // any meaning, but maybe someone will think of a way to
    // use it, so we might as well put the code in
    //
    foreach ($selected as $default) {
        if ((string)$default == (string)$key) {
            echo ' selected="selected"';
            break;
        }
    }

    // Add the display value to our option string.
    //
    echo '>' . sm_encode_html_special_chars($key) . ' = ';

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

    echo "</option>\n";

}

echo '</select>';


// Construct the delete input
// 
if (!empty($possible_values) && $use_delete_widget)
   echo '<br />' . $checkbox_widget . '&nbsp;<label for="delete_' . $name . '">' 
      . _("Delete Selected") . '</label>';
