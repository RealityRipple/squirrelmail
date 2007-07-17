<?php

/**
  * squirrelmail_rpc.php
  *
  * This file contains the entry point to the "SquirrelMail API" -- the 
  * remote procedure call request receiver.
  * 
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @since 1.5.2
  *
  */

/** This is the squirrelmail_rpc page */
define('PAGE_NAME', 'squirrelmail_rpc');

//FIXME: If we decide to route ALL requests, even normal page
//       requests through this file, need to change page requests
//       to something like this
//http://example.org/squirrelmail/src/squirrelmail_rpc.php?page=read_body&passed_id=47633...
//       This file would then add ".php" to the "page" variable
//       and pass the request on to that page by simply require()ing
//       that page and exiting.
//       Does this present problems, security or otherwise?  What
//       problems are created by the fact that the page request
//       is always the same thing (some parts of the code and some
//       plugins switch functionality based on $PHP_SELF and other
//       $_SERVER variables that look for specific page names -- those
//       can be fixed by looking at the "page" GET argument, but what
//       other issues are created)?  What about plugins?  How would
//       they work in this scheme?  Would they be a lot more difficult 
//       to develop?
//NOTE:  It is not entirely clear if doing the above is even desirable.
//       Initial conversations on the squirrelmail-devel list were 
//       inconclusive.  On one hand, doing so would give us one master
//       file that handles any and all incoming requests, no matter 
//       where they came from or what format/type they are.  On the
//       other, keeping page requests out of this file keeps this file
//       lean and specific to one technology: our RPC interface.


/**
 * Include the SquirrelMail initialization file.
 */
//FIXME: init.php assumes it is being called by a browser, so some error 
//       conditions are handled by immediately calling error_box() or 
//       otherwise trying to push something to the browser, which should 
//       be avoided at all costs.  This is also pervasive in the whole 
//       core and must be cleaned up entirely before this can be a very
//       functional RPC interface
require('../include/init.php');



/**
  * Get RPC Action (can be in either GET or POST)
  *
  */
if (!sqGetGlobalVar('rpc_action', $rpc_action, SQ_FORM)) {
//FIXME: establish error codes (using 99 in the interim)
    sm_rpc_return_error(99, _("No RPC action given"));
}



/**
  * No matter what our response is, the headers
  * will not change.
  *
  */
$oTemplate->header('Content-Type: text/xml');
$oTemplate->header('Content-Type: application/xml'); // required by IE
//FIXME: which anti-cache headers do we want to use?
$oTemplate->header('Cache-Control: no-cache');
// $oTemplate->header("Expires: Sat, 1 Jan 2000 00:00:00 GMT");
// $oTemplate->header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
// $oTemplate->header("Cache-Control: no-cache, must-revalidate");
// $oTemplate->header("Pragma: no-cache");



/**
  * Allow plugins to add their own RPC action
  * or modify behavior of SM core RPC actions...
  *
  * A plugin that handles a custom RPC action must
  * return TRUE to the hook so that it knows that
  * the action was handled and was not an unknown
  * action.  If the action was not handled, the plugin
  * should return FALSE to the hook.
  *
  * Developer note: the $rpc_action parameter is passed 
  * in an array in case we can think of more parameters 
  * to add in the future.
  *
  */
$handled_by_plugin = boolean_hook_function('squirrelmail_rpc',
                                           $temp=array(&$rpc_action),
                                           1);



/**
  * Go take care of each RPC action (unless plugin already did)
  *
  */
if (!$handled_by_plugin) switch (strtolower($rpc_action)) {

    /**
      * Delete Messages
      *
      */
    case 'delete_messages':

        require_once(SM_PATH . 'functions/mailbox_display.php');
        require_once(SM_PATH . 'functions/imap.php');

        if (!sqGetGlobalVar('delete_ids', $delete_ids, SQ_FORM)) {
            sm_rpc_return_error(99, _("No deletion ID given"));
        }
        $delete_ids = explode(',', $delete_ids);
        if (!sqGetGlobalVar('mailbox', $mailbox, SQ_FORM)) {
            sm_rpc_return_error(99, _("No mailbox given"));
        }
        if (sqGetGlobalVar('startMessage', $startMessage, SQ_INORDER, 1)) {
            $startMessage = (int) $startMessage;
        }
        sqGetGlobalVar('what', $what, SQ_FORM, 0);
        if (sqGetGlobalVar('account', $iAccount,  SQ_GET, 0)) {
            $iAccount = (int) $iAccount;
        }
//FIXME: need to grab the bypass trash variable here too!  probably other vars...

/* FIXME: --- The following code was just experimental/proof-of-concept; the rest 
              of the implementation of this functionality still needs to be done "for real"
        $oImapMessage = new IMAP_Message(0, $mailbox, $startMessage, $what, $iAccount);
        foreach ($delete_ids as $id) {
            $oImapMessage->setUid($id);
            //FIXME: establish constants for $hide values (the 3 below indicates not to show errors, but to return any error string)
            $result = $oImapMessage->deleteMessage(3);
            if ($result !== TRUE) {
                sm_rpc_return_error(99, $result);
            }
        }
--- */

        sm_rpc_return_success();
        //FIXME: Just for testing the line above can be changed to something like this:
        //sm_rpc_return_success(0, 'Hooray!  Message(s) deleted.  Refresh your message list and make sure.');
        break;


    /**
      * Default: error out
      *
      */
    default:
        sm_rpc_return_error(99, _("RPC action not understood"));
        break;

}



/**
  * Returns an error message to the RPC caller and exits
  *
  * NOTE that this function exits and will never return
  *
  * @param int    $error_code The error code for the current error condition
  * @param string $error_text Any error message associated with the error
  *                           condition (OPTIONAL; default empty string)
  *
  */
function sm_rpc_return_error($error_code, $error_text='') {

    global $oTemplate;
    $oTemplate->assign('error_code', $error_code);
    $oTemplate->assign('error_text', $error_text);

    $oTemplate->display('rpc_response_error.tpl');

    exit;

}



/**
  * Returns a standard success result to the RPC caller and exits
  *
  * NOTE that this function exits and will never return
  *
  * @param int    $result_code The result code (OPTIONAL; default 0)
  * @param string $result_text Any result message (OPTIONAL; default 
  *                            empty string)
  *
  */
function sm_rpc_return_success($result_code=0, $result_text='') {

    global $oTemplate;
    $oTemplate->assign('result_code', $result_code);
    $oTemplate->assign('result_text', $result_text);

    $oTemplate->display('rpc_response_success.tpl');

    exit;

}



