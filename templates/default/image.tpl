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


?><img src="<?php echo $src ?>"<?php if (!empty($class)) echo ' class="' . $class . '"'; ?><?php if (!empty($id)) echo ' id="' . $id . '"'; ?><?php if (!empty($alt)) echo ' alt="' . $alt . '"'; ?><?php if (!empty($title)) echo ' title="' . $title . '"'; ?><?php if (!empty($onclick)) echo ' onclick="' . $onclick . '"'; ?><?php if (!empty($width)) echo ' width="' . $width . '"'; ?><?php if (!empty($height)) echo ' height="' . $height . '"'; ?><?php if (!empty($align)) echo ' align="' . $align . '"'; ?><?php if (!empty($border)) echo ' border="' . $border . '"'; ?><?php if (!empty($hspace)) echo ' hspace="' . $hspace . '"'; ?><?php if (!empty($vspace)) echo ' vspace="' . $vspace . '"'; ?> />
