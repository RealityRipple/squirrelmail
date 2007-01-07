<?php

/**
  * textarea.tpl
  *
  * Template for constructing a textarea input tag.
  *
  * The following variables are available in this template:
  *      + $name     - The name of the select input
  *      + $text     - The initial value inside the textarea
  *      + $cols     - The width of the textarea in characters
  *      + $rows     - The height of the textarea in rows
  *      + $aAttribs - Any extra attributes: an associative array, where
  *                    keys are attribute names, and values (which are
  *                    optional and might be null) should be placed
  *                    in double quotes as attribute values (optional;
  *                    may not be present)
  *
  * @copyright &copy; 1999-2006 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


if (isset($aAttribs['id'])) {
    $label_open = '<label for="' . $aAttribs['id'] . '">';
    $label_close = '</label>';
} else {
    $label_open = '';
    $label_close = '';
}


echo '<textarea name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo '>' . $label_open . $text . $label_close . "</textarea>\n";


