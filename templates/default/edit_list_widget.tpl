<?php

/**
  * edit_list_widget.tpl
  *
  * Template for constructing an edit list.
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
  * mixed    $current_value     The currently selected value(s)
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


if ($use_input_widget) 
    echo _("Add") . '&nbsp;' . $input_widget . '<br />';

echo $select_widget;

if (!empty($possible_values) && $use_delete_widget)
   echo '<br />' . $checkbox_widget . '&nbsp;<label for="delete_' . $name . '">' 
      . _("Delete Selected") . '</label>';
