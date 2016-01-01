<?php

/**
  * image.tpl
  *
  * Template for constructing an image.
  *
  * The following variables are available in this template:
  *      + $src     - the image source path
  *      + $id      - ID name (optional; may not be present)
  *      + $class   - CSS class name (optional; may not be present)
  *      + $alt     - alternative link text
  *                   (optional; may not be present)
  *      + $title   - the image's title attribute value
  *                   (optional; may not be present)
  *      + $width   - the width the image should be shown in
  *                   (optional; may not be present)
  *      + $height  - the height the image should be shown in
  *                   (optional; may not be present)
  *      + $align   - the image's alignment attribute value
  *                   (optional; may not be present)
  *      + $border  - the image's border attribute value
  *                   (optional; may not be present)
  *      + $hspace  - the image's hspace attribute value
  *                   (optional; may not be present)
  *      + $vspace  - the image's vspace attribute value
  *                   (optional; may not be present)
  *      + $onclick - onClick JavaScript handler (optional; may not be present)
  *      + $text_alternative - A text replacement for the entire
  *                            image tag, if for some reason the 
  *                            image tag cannot or should not be 
  *                            produced (optional; may not be present)
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


echo '<img src="' . $src . '"';
if (!empty($class)) echo ' class="' . $class . '"';
if (!empty($id)) echo ' id="' . $id . '"';
if (!empty($alt)) echo ' alt="' . $alt . '"';
if (!empty($title)) echo ' title="' . $title . '"';
if (!empty($onclick)) echo ' onclick="' . $onclick . '"';
if (!empty($width) || $width === '0') echo ' width="' . $width . '"';
if (!empty($height) || $height === '0') echo ' height="' . $height . '"';
if (!empty($align)) echo ' align="' . $align . '"';
if (!empty($border) || $border === '0') echo ' border="' . $border . '"';
if (!empty($hspace) || $hspace === '0') echo ' hspace="' . $hspace . '"';
if (!empty($vspace) || $vspace === '0') echo ' vspace="' . $vspace . '"';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo ' />';


