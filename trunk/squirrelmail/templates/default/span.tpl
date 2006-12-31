<?php

/**
  * span.tpl
  *
  * Template for constructing a span tag.
  *
  * The following variables are available in this template:
  *      + $value   - The contents that belong inside the span
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


?><span<?php if (!empty($class)) echo ' class="' . $class . '"'; ?><?php if (!empty($id)) echo ' id="' . $id . '"'; ?>><?php echo $value; ?></span>
