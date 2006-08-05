<?php

/**
 * webmail.php -- Displays the main frameset
 *
 * This file generates the main frameset. The files that are
 * shown can be given as parameters. If the user is not logged in
 * this file will verify username and password.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

if (sqgetGlobalVar('sort', $sort)) {
    $sort = (int) $sort;
}

if (sqgetGlobalVar('startMessage', $startMessage)) {
    $startMessage = (int) $startMessage;
}

if (!sqgetGlobalVar('mailbox', $mailbox)) {
    $mailbox = 'INBOX';
}

sqgetGlobalVar('right_frame', $right_frame, SQ_GET);

if(!sqgetGlobalVar('mailto', $mailto)) {
    $mailto = '';
}

do_hook('webmail_top');

$oTemplate->assign('org_title',$org_title);
$oTemplate->assign('mailto',$mailto);
$oTemplate->assign('startMessage',$startMessage);
$oTemplate->assign('mailbox',$mailbox);
$oTemplate->assign('sort',$sort);
$oTemplate->assign('username',$username);
$oTemplate->assign('delimiter',$delimiter);
$oTemplate->assign('onetimepad',$onetimepad);
$oTemplate->assign('languages',$languages);
$oTemplate->assign('default_left_size',$default_left_size);
$oTemplate->assign('right_frame',$right_frame);

$oTemplate->display('webmail.tpl');

$oTemplate->display('footer.tpl');
