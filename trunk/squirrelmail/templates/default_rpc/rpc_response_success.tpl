<?php

/**
  * rpc_response_success.tpl
  *
  * Template for constructing a standard (SOAP-compliant)
  * response to a remote procedure call.
  *
  * The following variables are available in this template:
  *
  * string $rpc_action  The RPC action being handled
  * int    $result_code The result code
  * string $result_text Any result message (optional; may not be present)
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


echo '<?xml version="1.0" ?>'; ?>
<soap:envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:sm="http://squirrelmail.org/rpc" xmlns:xsd="http://www.w3.org/1999/XMLSchema" xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance" xmlns:soap-enc="http://www.w3.org/2003/05/soap-encoding" soap:encodingstyle="http://www.w3.org/2003/05/soap-encoding">
  <soap:header>
    <sm:result_code><?php echo $result_code; ?></sm:result_code>
    <sm:result_text><?php echo $result_text; ?></sm:result_text>
  </soap:header>
  <soap:body>
    <sm:<?php echo $rpc_action; ?>Response><?php
/* TODO/FIXME: when data is returned to the client, it goes here.... */
  ?></sm:<?php echo $rpc_action; ?>Response>
  </soap:body>
</soap:envelope>
