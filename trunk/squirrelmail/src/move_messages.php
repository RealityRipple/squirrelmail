<?php

/**
 * move_messages.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Enables message moving between folders on the IMAP server.
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/html.php');

global $compose_new_win;

if ( !sqgetGlobalVar('composesession', $composesession, SQ_SESSION) ) {
    $composesession = 0;
}

function attachSelectedMessages($msg, $imapConnection) {
    global $username, $attachment_dir, $startMessage,
           $data_dir, $composesession,
           $msgs, $show_num, $compose_messages;

    if (!isset($compose_messages)) {
        $compose_messages = array();
        sqsession_register($compose_messages,'compose_messages');
    }

    if (!$composesession) {
        $composesession = 1;
        sqsession_register($composesession,'composesession');
    } else {
        $composesession++;
        sqsession_register($composesession,'composesession');
    }

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $composeMessage = new Message();
    $rfc822_header = new Rfc822Header();
    $composeMessage->rfc822_header = $rfc822_header;
    $composeMessage->reply_rfc822_header = '';

    foreach($msg as $id) {
        $body_a = sqimap_run_command($imapConnection, "FETCH $id RFC822", true, $response, $readmessage, TRUE);

        if ($response == 'OK') {
            // fetch the subject for the message with $id from msgs.
            // is there a more efficient way to do this?
            foreach($msgs as $k => $vals) {
                if($vals['ID'] == $id) {
                    $subject = $msgs[$k]['SUBJECT'];
                    break;
                }
            }

            array_shift($body_a);
            array_pop($body_a);
            $body = implode('', $body_a);
            $body .= "\r\n";

            $localfilename = GenerateRandomString(32, 'FILE', 7);
            $full_localfilename = "$hashed_attachment_dir/$localfilename";

            $fp = fopen( $full_localfilename, 'wb');
            fwrite ($fp, $body);
            fclose($fp);
            $composeMessage->initAttachment('message/rfc822',$subject.'.msg',
                 $full_localfilename);
        }
    }

    $compose_messages[$composesession] = $composeMessage;
    sqsession_register($compose_messages,'compose_messages');
    session_write_close();
    return $composesession;
}

/* get globals */
sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('base_uri',  $base_uri,      SQ_SESSION);

sqgetGlobalVar('mailbox', $mailbox);
sqgetGlobalVar('startMessage', $startMessage);
sqgetGlobalVar('msg', $msg);

sqgetGlobalVar('msgs',              $msgs,              SQ_SESSION);
sqgetGlobalVar('composesession',    $composesession,    SQ_SESSION);
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);

sqgetGlobalVar('moveButton',      $moveButton,      SQ_POST);
sqgetGlobalVar('expungeButton',   $expungeButton,   SQ_POST);
sqgetGlobalVar('targetMailbox',   $targetMailbox,   SQ_POST);
sqgetGlobalVar('expungeButton',   $expungeButton,   SQ_POST);
sqgetGlobalVar('undeleteButton',  $undeleteButton,  SQ_POST);
sqgetGlobalVar('markRead',        $markRead,        SQ_POST);
sqgetGlobalVar('markUnread',      $markUnread,      SQ_POST);
sqgetGlobalVar('markFlagged',     $markFlagged,     SQ_POST);
sqgetGlobalVar('markUnflagged',   $markUnflagged,   SQ_POST);
sqgetGlobalVar('attache',         $attache,         SQ_POST);
sqgetGlobalVar('location',        $location,        SQ_POST);
sqgetGlobalVar('bypass_trash',    $bypass_trash,    SQ_POST);
sqgetGlobalVar('dmn',             $is_dmn,          SQ_POST);

/* end of get globals */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response=sqimap_mailbox_select($imapConnection, $mailbox);

$location = set_url_var($location,'composenew',0,false);
$location = set_url_var($location,'composesession',0,false);
$location = set_url_var($location,'session',0,false);

/* remember changes to mailbox setting */
if (!isset($lastTargetMailbox)) {
    $lastTargetMailbox = 'INBOX';
}
if ($targetMailbox != $lastTargetMailbox) {
    $lastTargetMailbox = $targetMailbox;
    sqsession_register($lastTargetMailbox, 'lastTargetMailbox');
}
$exception = false;
$change    = false;

do_hook('move_before_move');

/*
    Move msg list sorting up here, as it is used several times,
    makes it more efficient to do it in one place for the code
*/
$id = array();
if (isset($msg) && is_array($msg)) {
    foreach( $msg as $key=>$uid ) {
        // using foreach removes the risk of infinite loops that was there //
        $id[] = $uid;
    }
}
$num_ids = count($id);

// expunge-on-demand if user isn't using move_to_trash or auto_expunge
if(isset($expungeButton)) {
    $num_ids = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
    $change = true;
} elseif(isset($undeleteButton)) {
    // undelete messages if user isn't using move_to_trash or auto_expunge
    // Removes \Deleted flag from selected messages
    if ($num_ids) {
        sqimap_toggle_flag($imapConnection, $id, '\\Deleted',false,true);
    } else {
        $exception = true;
    }
} elseif (!isset($moveButton)) {
    if ($num_ids) {
        if (!isset($attache)) {
            if (isset($markRead)) {
                sqimap_toggle_flag($imapConnection, $id, '\\Seen',true,true);
            } else if (isset($markUnread)) {
                sqimap_toggle_flag($imapConnection, $id, '\\Seen',false,true);
            } else if (isset($markFlagged)) {
                sqimap_toggle_flag($imapConnection, $id, '\\Flagged', true, true);
            } else if (isset($markUnflagged)) {
                sqimap_toggle_flag($imapConnection, $id, '\\Flagged', false, true);
            } else  { // Delete messages
                if (!boolean_hook_function('move_messages_button_action', NULL, 1)) {
                    sqimap_msgs_list_delete($imapConnection, $mailbox, $id,$bypass_trash);
                    if ($auto_expunge) {
                        $num_ids = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
                    }
                    $change = true;
                }
            }
        } else {
            $composesession = attachSelectedMessages($id, $imapConnection);
            $location = set_url_var($location, 'session', $composesession, false);
            if ($compose_new_win) {
                $location = set_url_var($location, 'composenew', 1, false);
            } else {
                $location = str_replace('search.php','compose.php',$location);
                $location = str_replace('right_main.php','compose.php',$location);
            }
        }
    } else {
        $exception = true;
    }
} else {    // Move messages
    if ( $num_ids > 0 ) {
        if ( $is_dmn && $num_ids == 1 ) {
            sqimap_msgs_list_move($imapConnection,$id[0],$targetMailbox);
            $num_ids = sqimap_mailbox_expunge_dmn($id[0]);
        } else {
            sqimap_msgs_list_move($imapConnection,$id,$targetMailbox);
            if ($auto_expunge) {
                $num_ids = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
            }
        }
        $change = true;
    } else {
        $exception = true;
    }
}
if($change) { // Change the startMessage number if the mailbox was changed
    if (($startMessage+$num_ids-1) >= $mbx_response['EXISTS']) {
        if ($startMessage > $show_num) {
            $location = set_url_var($location,'startMessage',$startMessage-$show_num,false);
        } else {
            $location = set_url_var($location,'startMessage',1,false);
        }
    }
}
// Log out this session
sqimap_logout($imapConnection);
if ($exception) {
    displayPageHeader($color, $mailbox);
    error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
} else {
    header("Location: $location");
    exit;
}
?>
</BODY></HTML>
