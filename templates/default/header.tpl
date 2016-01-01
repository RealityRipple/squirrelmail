<?php

/**
  * header.tpl
  *
  * Template for constructing a header that is sent to the browser.
  *
  * The following variables are available in this template:
  *      + $header - The header string to be sent
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


echo $header;


