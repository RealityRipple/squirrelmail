<?php

/**
  * system_specs.tpl
  *
  * Template for the system specs display screen for the Bug Report plugin.
  *
  * The following variables are available in this template:
  *
  * string $body The page content (lightly formatted system specs text
  *              with newlines and spaces for indentation)
  *                       
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<body>
<pre>
<?php echo $body; ?>
</pre>


