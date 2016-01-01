<?php

/**
  * rpc_response_error.tpl
  *
  * Template for constructing a standard (SOAP-compliant)
  * response to an errant remote procedure call.
  *
  * The following variables are available in this template:
  *
  * string $rpc_action   The RPC action being handled
  * int    $error_code   The numeric error code associated with the
  *                      current error condition
  * string $error_text   Any error message associated with the
  *                      current error condition (optional; may not be
  *                      present)
  * string $guilty_party A string indicating the party who caused the
  *                      error: either "client" or "server" (optional;
  *                      may not be present)
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
    <sm:result_code><?php echo $error_code; ?></sm:result_code>
    <sm:result_text><?php echo $error_text; ?></sm:result_text>
  </soap:header>
  <soap:body>
    <soap:fault>
      <faultcode>soap:<?php echo ucfirst(strtolower($guilty_party)); ?></faultcode>
      <faultstring><?php echo $error_text; ?></faultstring>
      <detail>
        <sm:result_code><?php echo $error_code; ?></sm:result_code>
        <sm:result_text><?php echo $error_text; ?></sm:result_text>
      </detail>
    </soap:fault>
  </soap:body>
</soap:envelope>
