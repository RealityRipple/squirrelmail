<?php

/**
  * input.tpl
  *
  * Template for constructing an input tag.
  *
  * The following variables are available in this template:
  *      + $type     - The type of input tag being created
  *      + $aAttribs - Any extra attributes: an associative array, where
  *                    keys are attribute names, and values (which are
  *                    optional and might be null) should be placed
  *                    in double quotes as attribute values (optional;
  *                    may not be present)
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


echo '<input type="' . $type . '"';
foreach ($aAttribs as $key => $value) {
//TODO: see the FIXME tag on line 68 of functions/forms.php - the sm_encode_html_special_chars() below should be migrated to the template class
    echo ' ' . $key . (is_null($value) ? '' : '="' . sm_encode_html_special_chars($value) . '"');
}
echo ' />';


