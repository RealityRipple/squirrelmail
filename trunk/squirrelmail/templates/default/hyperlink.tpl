<?php

/**
  * hyperlink.tpl
  *
  * Template for constructing a hyperlink.
  *
  * The following variables are available in this template:
  *      + $uri      - the target link location
  *      + $text     - link text
  *      + $target   - the location where the link should be opened 
  *                    (optional; may not be present)
  *      + $onclick  - onClick JavaScript handler (optional; may not be present)
  *      + $class    - CSS class name (optional; may not be present)
  *      + $id       - ID name (optional; may not be present)
  *      + $name     - Anchor name (optional; may not be present)
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


echo '<a href="' . $uri . '"';
if (!empty($target)) echo ' target="' . $target . '"';
if (!empty($onclick)) echo ' onclick="' . $onclick . '"';
if (!empty($name)) echo ' name="' . $name . '"';
if (!empty($class)) echo ' class="' . $class . '"';
if (!empty($id)) echo ' id="' . $id . '"';
else if (!empty($name)) echo ' id="' . $name . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo '>' . $text . '</a>';


