<?php

/**
  * hyperlink.tpl
  *
  * Template for constructing a hyperlink.
  *
  * The following variables are available in this template:
  *      + $uri     - the target link location
  *      + $text    - link text
  *      + $target  - the location where the link should be opened 
  *                   (optional; may not be present)
  *      + $onclick - onClick JavaScript handler (optional; may not be present)
  *      + $class   - CSS class name (optional; may not be present)
  *      + $id      - ID name (optional; may not be present)
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


?><a href="<?php echo $uri ?>"<?php if (!empty($target)) echo ' target="' . $target . '"'; ?><?php if (!empty($onclick)) echo ' onclick="' . $onclick . '"'; ?><?php if (!empty($class)) echo ' class="' . $class . '"'; ?><?php if (!empty($id)) echo ' id="' . $id . '"'; ?>><?php echo $text; ?></a>
