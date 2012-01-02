<?php

/**
  * table_row.tpl
  *
  * Template for constructing an opening table row tag.
  *
  * The following variables are available in this template:
  * array  $attributes  The table attributes
  *
  * @copyright 1999-2012 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: table_row.tpl 12078 2007-01-07 07:28:11Z pdontthink $
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


echo '<tr';

foreach ($attributes as $key => $value) {
    echo ' ' . $key . (is_null($value) ? '' : '="' . $value . '"');
}

echo ">\n";

