<?php

/**
  * span.tpl
  *
  * Template for constructing a span tag.
  *
  * The following variables are available in this template:
  *      + $value    - The contents that belong inside the span
  *      + $class    - CSS class name (optional; may not be present)
  *      + $id       - ID name (optional; may not be present)
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


echo '<span';
if (!empty($class)) echo ' class="' . $class . '"';
if (!empty($id)) echo ' id="' . $id . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo '>' . $value . '</span>';


