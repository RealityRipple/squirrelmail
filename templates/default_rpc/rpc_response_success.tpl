<?php

/**
  * rpc_response_success.tpl
  *
  * Template for constructing a standard response to a remote 
  * procedure call.
  *
  * The following variables are available in this template:
  *      + $result_code - The result code (optional; if not given 
  *                       must default to 0 (zero))
  *      + $result_text - Any result message (optional; may not be 
  *                       present)
  *
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: rpc_response_success.tpl 12111 2007-01-11 08:05:51Z pdontthink $
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
    <status>OK</status>
    <result_code><?php echo $result_code; ?></result_code>
    <result_text><?php echo $result_text; ?></result_text>
</response>
