<?php

/**
  * line_break.tpl
  *
  * Template for constructing a line break.
  *
  * The following variables are available in this template:
  *
  * array $aAttribs Any extra attributes: an associative array, where
  *                 keys are attribute names, and values (which are
  *                 optional and might be null) should be placed
  *                 in double quotes as attribute values (optional;
  *                 may not be present)
  *
  * @copyright &copy; 1999-2008 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: line_break.tpl 12046 2007-01-02 21:03:07Z pdontthink $
  * @package squirrelmail
  * @subpackage templates
  */


echo '<br';
foreach ($aAttribs as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}
echo ' />';