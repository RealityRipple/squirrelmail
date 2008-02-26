<?php

/**
  * edit_list_widget.tpl
  *
  * Template for constructing an edit list.
  *
  * The following variables are available in this template:
  *
  * string  $name            The name of the edit list
  * string  $input_widget    A preconstructed input text box used
  *                          for adding new elements to the edit list
  * string  $select_widget   A preconstructed input text box used
  * string  $checkbox_widget A preconstructed input text box used
  * string  $trailing_text   Any text given by the caller to be displayed
  *                          after the edit list input
  *
  * @copyright &copy; 1999-2008 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: select.tpl 12961 2008-02-24 22:35:08Z pdontthink $
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


echo _("Add") . '&nbsp;' . $input_widget . '<br />' . $select_widget 
   . '<br />' . $checkbox_widget . '&nbsp;<label for="delete_' . $name . '">' 
   . _("Delete Selected") . '</label>';
