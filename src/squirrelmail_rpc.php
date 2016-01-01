<?php

/**
  * squirrelmail_rpc.php
  *
  * This file contains the entry point to the "SquirrelMail API" -- the 
  * remote procedure call request receiver.
  *
  * RPC requests are currently understood as simple HTTP GET or POST
  * requests.  The SquirrelMail default_rpc template set responds in a
  * SOAP (currently v1.2) compliant manner, but this interface does not
  * (yet?) understand SOAP requests.  The format of responses can be
  * changed by creating a different RPC template set and pointing to it
  * with $rpc_templateset in the main SquirrelMail configuration file.
  * 
  * @copyright 1999-2016 The SquirrelMail Project Team
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



//FIXME: do we need to put this list somewhere else?
//FIXME: do we want to use constants instead?  probably not a bad idea, although plugins probably won't, so we still want to try to keep track of the plugin error codes too if possible (new plugin website should help)
/**
  * Known core error codes:
  *
  * 1    - No RPC action was given in request (please use "rpc_action")
  * 2    - RPC action was not understood (perhaps a needed plugin is
  *        not installed and activated?)
  *
  * Known plugin error codes:
  *
  * 500  - Empty Folders plugin empty_folders_purge_trash action failed
  * 501  - Empty Folders plugin empty_folders_purge_all action failed
  * 502  - Empty Folders plugin empty_folders_delete_all action failed
  * 503  - Mark Read plugin mark_read_read_all action failed
  * 504  - Mark Read plugin mark_read_unread_all action failed
  *
  */



/**
  * Get RPC Action (can be in either GET or POST)
  *
  */
if (!sqGetGlobalVar('rpc_action', $rpc_action, SQ_FORM)) {
    sm_rpc_return_error('', 1, _("No RPC action given"), 'client', 400, 'Bad Request');
}



/**
  * No matter what our response is, the headers
  * will not change.
  *
  */
$oTemplate->header('Content-Type: text/xml');
$oTemplate->header('Content-Type: application/xml'); // required by IE
$oTemplate->header('Pragma: no-cache');
$oTemplate->header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
$oTemplate->header('Expires: Sat, 1 Jan 2000 00:00:00 GMT');
//TODO: is this needed? $oTemplate->header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');



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
  * Known users of this hook:
  *    empty_folders
  *    mark_read
  *
  */
$temp = array(&$rpc_action);
$handled_by_plugin = boolean_hook_function('squirrelmail_rpc', $temp, 1);



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
            sm_rpc_return_error($rpc_action, 99, _("No deletion ID given"), 'client', 400, 'Bad Request');
        }
        $delete_ids = explode(',', $delete_ids);
        if (!sqGetGlobalVar('mailbox', $mailbox, SQ_FORM)) {
            sm_rpc_return_error($rpc_action, 99, _("No mailbox given"), 'client', 400, 'Bad Request');
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
                sm_rpc_return_error($rpc_action, 99, $result, 'server', 500, 'Server Error');
            }
        }
--- */

        sm_rpc_return_success();
        //FIXME: Just for testing the line above can be changed to something like this:
        //sm_rpc_return_success($rpc_action, 0, 'Hooray!  Message(s) deleted.  Refresh your message list and make sure.');
        break;


    /**
      * Default: error out
      *
      */
    default:
        sm_rpc_return_error($rpc_action, 2, _("RPC action not understood"), 'client', 400, 'Bad Request');
        break;

}



/**
  * Returns an error message to the RPC caller and exits
  *
  * NOTE that this function exits and will never return
  *
  * @param string $rpc_action   The RPC action that is being handled
  *                             (OPTIONAL; default attempt to grab from GET/POST)
  * @param int    $error_code   The (application-level) error code for the current
  *                             error condition
  * @param string $error_text   Any error message associated with the error
  *                             condition (OPTIONAL; default empty string)
  * @param string $guilty_party A string indicating the party who caused the
  *                             error: either "client" or "server" (OPTIONAL;
  *                             default unspecified)
  * @param int    $http_status_code When non-zero, this value will be sent to
  *                                 the browser in the HTTP headers as the request
  *                                 status code (OPTIONAL; default not used)
  * @param string $http_status_text A string naming the HTTP status, usually the
  *                                 title of the corresponding status code as
  *                                 found on:
  *                                 http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
  *                                 (OPTIONAL; default not used; $http_status_code
  *                                 must also be provided).
  *
  */
function sm_rpc_return_error($rpc_action=NULL, $error_code,
                             $error_text='', $guilty_party='',
                             $http_status_code=0, $http_status_text='') {

    global $oTemplate;

    if (is_null($rpc_action)) sqGetGlobalVar('rpc_action', $rpc_action, SQ_FORM);

    if ($http_status_code) {
       $oTemplate->header('HTTP/1.1 ' . $http_status_code . ' ' . $http_status_text);
       $oTemplate->header('Status: ' . $http_status_code . ' ' . $http_status_text);
    }

    $oTemplate->assign('rpc_action',   $rpc_action);
    $oTemplate->assign('error_code',   $error_code);
    $oTemplate->assign('error_text',   $error_text);
    $oTemplate->assign('guilty_party', $guilty_party);

    $oTemplate->display('rpc_response_error.tpl');

    exit;

}



/**
  * Returns a standard success result to the RPC caller and exits
  *
  * NOTE that this function exits and will never return
  *
  * @param string $rpc_action  The RPC action that is being handled
  *                            (OPTIONAL; default attempt to grab from GET/POST)
  * @param int    $result_code The result code (OPTIONAL; default 0)
  * @param string $result_text Any result message (OPTIONAL; default 
  *                            empty string)
  *
  */
function sm_rpc_return_success($rpc_action=NULL, $result_code=0, $result_text='') {

    if (is_null($rpc_action)) sqGetGlobalVar('rpc_action', $rpc_action, SQ_FORM);

    global $oTemplate;
    $oTemplate->assign('rpc_action', $rpc_action);
    $oTemplate->assign('result_code', $result_code);
    $oTemplate->assign('result_text', $result_text);

    $oTemplate->display('rpc_response_success.tpl');

    exit;

}



