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
  *      + $extra   - any extra text to be directly inserted into the hyperlink
  *                   (note to core developers - PLEASE AVOID using this, as it
  *                   usually means you are adding template-engine specific output
  *                   to the core)
  *
  * @copyright &copy; 1999-2006 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


?><a href="<?php echo $uri ?>"<?php if (!empty($target)) echo ' target="' . $target . '"'; ?><?php if (!empty($onclick)) echo ' onclick="' . $onclick . '"'; ?>><?php echo $text; ?></a>
