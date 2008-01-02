<?php

/**
  * select.tpl
  *
  * Template for constructing a select input tag.
  *
  * The following variables are available in this template:
  * string  $name     The name of the select input
  * array   $aValues  An associative array corresponding to each 
  *                   select option where keys must be used as
  *                   the option value and the values must be used
  *                   as the option text
  * boolean $bUsekeys When FALSE, the value of each option should
  *                   be the same as the option text instead of
  *                   using the array key for the option value
  * boolean $multiple When TRUE, a multiple select list should be
  *                   shown.
  * array   $default  An array of option values that should be 
  *                   selected by default (only will contain one
  *                   array element unless this is a multiple select
  *                   list)
  * array   $aAttribs Any extra attributes: an associative array, where
  *                   keys are attribute names, and values (which are
  *                   optional and might be null) should be placed
  *                   in double quotes as attribute values (optional;
  *                   may not be present)
  *
  * @copyright &copy; 1999-2008 The SquirrelMail Project Team
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


echo '<select name="' . $name . ($multiple ? '[]" multiple="multiple" size="3"' : '"');
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo ">\n";


foreach ($aValues as $key => $value) {
    if (!$bUsekeys) $key = $value;
    echo '<option value="' .  $key . '"';

    // multiple select lists have possibly more than one default selection
    //
    if ($multiple) {
        foreach ($default as $def) {
            if ($def === $key) {
                echo ' selected="selected"';
                break;
            }
        }
    }

    // single select widget only needs to check for one default value
    // (we could use the same code above, but we do this here to increase
    // efficency and performance)
    //
    else if ($default[0] === $key)
        echo ' selected="selected"';

    echo '>' . $label_open . $value . $label_close  . "</option>\n";
}
echo "</select>\n";


