<?php

/**
 * mailout.php
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */
/**
 * Path for SquirrelMail required files.
 * @ignore
 */
require('../../include/init.php');

/* SquirrelMail required files. */
require(SM_PATH . 'functions/identity.php');
require(SM_PATH . 'functions/forms.php');
require(SM_PATH . 'plugins/listcommands/functions.php');

/* get globals */
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('send_to', $send_to, SQ_GET);
sqgetGlobalVar('subject', $subject, SQ_GET);
sqgetGlobalVar('body',    $body,    SQ_GET);
sqgetGlobalVar('action',  $action,  SQ_GET);
sqgetGlobalVar('identity',  $identity,  SQ_GET);

displayPageHeader($color, $mailbox);

switch ( $action ) {
    case 'help':
        $out_string = _("This will send a message to %s requesting help for this list. You will receive an emailed response at the address below.");
        break;
    case 'subscribe':
        $out_string = _("This will send a message to %s requesting that you will be subscribed to this list. You will be subscribed with the address below.");
        break;
    case 'unsubscribe':
        $out_string = _("This will send a message to %s requesting that you will be unsubscribed from this list. It will try to unsubscribe the adress below.");
        break;
    default:
        error_box(sprintf(_("Unknown action: %s"),sm_encode_html_special_chars($action)));
        // display footer (closes html tags) and stop script execution
        $oTemplate->display('footer.tpl');
        exit;
}

$out_string = sprintf($out_string, '&quot;' . sm_encode_html_special_chars($send_to) . '&quot;');
$idents = get_identities();
$fieldsdescr = listcommands_fieldsdescr();
$fielddescr = $fieldsdescr[$action];

$oTemplate->assign('out_string', $out_string);
$oTemplate->assign('fielddescr', $fielddescr);
$oTemplate->assign('send_to', $send_to);
$oTemplate->assign('subject', $subject);
$oTemplate->assign('body', $body);
$oTemplate->assign('mailbox', $mailbox);
$oTemplate->assign('idents', $idents);
$oTemplate->assign('identity', $identity);

$oTemplate->display('plugins/listcommands/mailout.tpl');
$oTemplate->display('footer.tpl');

