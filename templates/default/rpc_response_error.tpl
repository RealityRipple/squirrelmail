<?php

/**
  * rpc_response_error.tpl
  *
  * Template for constructing an error response to a remote 
  * procedure call.
  *
  * The following variables are available in this template:
  *      + $error_code - The numeric error code associated with the 
  *                      current error condition
  *      + $error_text - Any error message associated with the current
  *                      error condition (optional; may not be present)
  *
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage templates
  */


// retrieve the template vars
//
extract($t);


/*echo '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>';*/
echo '<?xml version="1.0" ?>';
?>
<response>
    <status>ERROR</status>
    <result_code><?php echo $error_code; ?></result_code>
    <result_text><?php echo $error_text; ?></result_text>
</response>
