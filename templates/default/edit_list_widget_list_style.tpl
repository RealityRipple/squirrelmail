<?php

/**
  * edit_list_widget_list_style.tpl
  *
  * Template for constructing an edit list using a list-format layout.
  *
  * The following variables are available in this template:
  *
  * string   $name              The name of the edit list
  * string   $input_widget      A preconstructed input text box used
  *                             for adding new elements to the edit list
  * boolean  $use_input_widget  Whether or not to present the $input_widget
  * boolean  $use_delete_widget Whether or not to present the $checkbox_widget
  * string   $select_widget     A preconstructed select widget containing
  *                             all the elements in the list
  * string   $checkbox_widget   A preconstructed checkbox used for deleting
  *                             elements from the edit list
  * string   $trailing_text     Any text given by the caller to be displayed
  *                             after the edit list input
  * array    $possible_values   The original list of options in the edit list,
  *                             for use constructing layouts alternative to
  *                             the select widget
  *
  * @copyright 1999-2010 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: select.tpl 12961 2008-02-24 22:35:08Z pdontthink $
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


echo '<table class="table2" cellspacing="0"><tr><td>';

if ($use_input_widget)
    echo _("Add") . '&nbsp;' . $input_widget . '<br />';

echo '<table class="table_messageList" cellspacing="0">';

$class = 'even';
$index = 0;

foreach ($possible_values as $key => $value) {

    if ($class == 'even') $class = 'odd';
    else $class = 'even';

    echo '<tr class="' . $class . '">'
       . '<td class="col_check" style="width:1%"><input type="checkbox" name="new_' . $name . '[' . ($index++) . ']" id="' . $name . '_list_item_' . $key . '" value="' . $value . '"></td>'
       . '<td><label for="' . $name . '_list_item_' . $key . '">' . $value . '</label></td>'
       . "</tr>\n";
    
}

echo '</table>';

if (!empty($possible_values) && $use_delete_widget)
    echo $checkbox_widget . '&nbsp;<label for="delete_' . $name . '">' 
       . _("Delete Selected") . '</label>';

echo '</td></tr></table>';
