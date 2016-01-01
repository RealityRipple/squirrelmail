<?php

/**
  * form.tpl
  *
  * Template for constructing an opening form tag.
  *
  * The following variables are available in this template:
  *      + $name     - The name of the form (the caller should ideally 
  *                    use id (in $aAttribs) instead) (optional; may not be provided)
  *      + $method   - The HTTP method used to submit data (usually "get" or "post")
  *      + $action   - The form action URI
  *      + $enctype  - The content type that is used to submit data (this
  *                    is optional and might be empty, in which case you
  *                    should just let HTML default to "application/x-www-form-urlencoded" 
  *      + $charset  - The charset that is used for submitted data (optional; may 
  *                    not be provided)
  *      + $aAttribs - Any extra attributes: an associative array, where
  *                    keys are attribute names, and values (which are
  *                    optional and might be null) should be placed
  *                    in double quotes as attribute values (optional;
  *                    may not be provided)
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


if (!isset($aAttribs['id']) && !empty($name))
    $aAttribs['id'] = $name;


echo '<form';
if (!empty($action)) echo ' action="' . $action . '"';
if (!empty($name)) echo ' name="' . $name . '"';
if (!empty($method)) echo ' method="' . $method . '"';
if (!empty($charset)) echo ' accept-charset="' . $charset . '"';
if (!empty($enctype)) echo ' enctype="' . $enctype . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo ">\n";


