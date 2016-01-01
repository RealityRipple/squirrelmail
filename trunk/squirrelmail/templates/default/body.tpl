<?php

/**
  * body.tpl
  *
  * Template for constructing an opening body tag.
  *
  * The following variables are available in this template:
  *      + $class         - CSS class name (optional; may not be present)
  *      + $onload        - Body onload JavaScript handler code (optional; 
  *                         may not be present)
  *      + $aAttribs      - Any extra attributes: an associative array, where 
  *                         keys are attribute names, and values (which are 
  *                         optional and might be null) should be placed
  *                         in double quotes as attribute values (optional; 
  *                         may not be present)
  *      + $plugin_output - An array of extra output that may be added by
  *                         plugin(s).
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


echo '<body';
if (!empty($class)) echo ' class="' . $class . '"';
if (!empty($onload)) echo ' onload="' . $onload . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo '>';

if (!empty($plugin_output['body_after'])) echo $plugin_output['body_after'];

