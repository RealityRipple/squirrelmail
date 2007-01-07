<?php

/**
  * select.tpl
  *
  * Template for constructing a select input tag.
  *
  * The following variables are available in this template:
  *      + $name     - The name of the select input
  *      + $aValues  - An associative array corresponding to each 
  *                    select option where keys must be used as
  *                    the option value and the values must be used
  *                    as the option text
  *      + $bUsekeys - When FALSE, the value of each option should
  *                    be the same as the option text instead of
  *                    using the array key for the option value
  *      + $default  - The option value that should be selected by default
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


echo '<select name="' . $name . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo ">\n";


foreach ($aValues as $key => $value) {
    if (!$bUsekeys) $key = $value;
    echo '<option value="' .  $key . '"'
       . (($default == $key) ? ' selected="selected"' : '')
       . '>' . $label_open . $value . $label_close  . "</option>\n";
}
echo "</select>\n";


