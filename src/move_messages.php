<?php

/**
 * move_messages.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Enables message moving between folders on the IMAP server.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
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

/* obsolete ?? */
function putSelectedMessagesIntoString($msg) {
    $j = 0;
    $i = 0;
    $firstLoop = true;
    // If they have selected nothing msg is size one still, but will
    // be an infinite loop because we never increment j. so check to
    // see if msg[0] is set or not to fix this.
    while (($j < count($msg)) && ($msg[0])) {
        if ($msg[$i]) {
            if ($firstLoop != true) {
                $selectedMessages .= "&amp;";
            } else {
                $firstLoop = false;
            }
            $selectedMessages .= "selMsg[$j]=$msg[$i]";
            $j++;
        }
        $i++;
    }
}

function attachSelectedMessages($msg, $imapConnection) {
    global $username, $attachment_dir,  
           $data_dir, $composesession, $uid_support,
	   $msgs, $thread_sort_messages, $allow_server_sort, $show_num,
	   $compose_messages;

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

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir, $composesession);

    if ($thread_sort_messages || $allow_server_sort) {
       $start_index=0;
    } else {
       $start_index = ($startMessage-1) * $show_num;
    }

    $i = 0;
    $j = 0;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    $composeMessage = new Message();
    $rfc822_header = new Rfc822Header();
    $composeMessage->rfc822_header = $rfc822_header;
    $composeMessage->reply_rfc822_header = '';

    while ($j < count($msg)) {
        if (isset($msg[$i])) {
    	    $id = $msg[$i];
    	    $body_a = sqimap_run_command($imapConnection, "FETCH $id RFC822",true, $response, $readmessage, $uid_support);
    	    if ($response = 'OK') {
	        $k = $i + $start_index;
		$subject = $msgs[$k]['SUBJECT'];
    
        	array_shift($body_a);
        	$body = implode('', $body_a);
        	$body .= "\r\n";
        		
        	$localfilename = GenerateRandomString(32, 'FILE', 7);
        	$full_localfilename = "$hashed_attachment_dir/$localfilename";
        	
        	$fp = fopen( $full_localfilename, 'wb');
        	fwrite ($fp, $body);
        	fclose($fp);
	        $composeMessage->initAttachment('message/rfc822',$subject.'.eml', 
	                 $full_localfilename);
    	    }
	    $j++;
	}
	$i++;	
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
sqgetGlobalVar('attache',         $attache,         SQ_POST);
sqgetGlobalVar('location',        $location,        SQ_POST);

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
// expunge-on-demand if user isn't using move_to_trash or auto_expunge
if(isset($expungeButton)) {
    $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
    if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
        if ($startMessage > $show_num) {
	    $location = set_url_var($location,'startMessage',$startMessage-$show_num,false);
	} else {
	    $location = set_url_var($location,'startMessage',1,false);
	}
    }
} elseif(isset($undeleteButton)) {
    // undelete messages if user isn't using move_to_trash or auto_expunge
    if (is_array($msg) == 1) {
        // Removes \Deleted flag from selected messages
        $j = $i = 0;
        $id = array();
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
        while ($j < count($msg)) {
            if ($msg[$i]) {
	        $id[] = $msg[$i];
                $j++;
            }
            $i++;
        }
	if (count($id)) {
            sqimap_toggle_flag($imapConnection, $id, '\\Deleted',false,true);
        }
    } else {
	$exception = true;
    }
} elseif (!isset($moveButton)) {
    // If the delete button was pressed, the moveButton variable will not be set.
    if (is_array($msg)) {
        // Marks the selected messages as 'Deleted'
        $j = $i = $cnt = 0;
	$id = array();
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
        while ($j < count($msg)) {
            if (isset($msg[$i])) {
	        $id[] = $msg[$i];
                $j++;
            }
            $i++;
        }
	if (count($id) && !isset($attache)) {
           if (isset($markRead)) {
	      sqimap_toggle_flag($imapConnection, $id, '\\Seen',true,true);
           } else if (isset($markUnread)) {
	      sqimap_toggle_flag($imapConnection, $id, '\\Seen',false,true);
           } else  {
	      sqimap_msgs_list_delete($imapConnection, $mailbox, $id);
              if ($auto_expunge) {
                 $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
	      }
           }
        }
        if (isset($attache)) {
	    $composesession = attachSelectedMessages($msg, $imapConnection);
	    $location = set_url_var($location, 'session', $composesession, false);
	    if ($compose_new_win) {
	        $location = set_url_var($location, 'composenew', 1, false);
	    } else {
		$location = str_replace('search.php','compose.php',$location);
		$location = str_replace('right_main.php','compose.php',$location);
	    }
	} else {		
	    if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
    	       if ($startMessage > $show_num) {
	           $location = set_url_var($location,'startMessage',$startMessage-$show_num, false);
	       } else {
	  	   $location = set_url_var($location,'startMessage',1, false);
	       }
	    }
        } 
    } else {
    	$exception = true;
    }
} else {    // Move messages
    // lets check to see if they selected any messages
    if (is_array($msg)) {
        $j = $i = 0;
	$id = array();
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
	$cnt = count($msg);
        while ($j < $cnt) {
            if (isset($msg[$i])) {
	        $id[] = $msg[$i];
                $j++;
            }
            $i++;
        }
	sqimap_msgs_list_copy($imapConnection,$id,$targetMailbox);
        if ($auto_expunge) {
            $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
	} else {
	    $cnt = 0;
	}
	
	if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
    	    if ($startMessage > $show_num) {
		$location = set_url_var($location,'startMessage',$startMessage-$show_num, false);
	    } else {
		$location = set_url_var($location,'startMessage',1, false);
	    }
	}
    } else {
	$exception = true;
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
