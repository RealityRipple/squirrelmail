<?php

/**
 * move_messages.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
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
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/html.php');

global $compose_new_win;

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

    if (!isset($composesession) ) {
	    $composesession = 1;
            sqsession_register($composesession,'composesession');  	    
    } else {
	    $composesession++;
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
    return $composesession;
}



/* get globals */

$username = $_SESSION['username'];
$key  = $_COOKIE['key'];
$onetimepad = $_SESSION['onetimepad'];
$base_uri = $_SESSION['base_uri'];
$delimiter = $_SESSION['delimiter'];
if (isset($_GET['mailbox'])) {
    $mailbox = $_GET['mailbox'];
}
if (isset($_GET['startMessage'])) {
    $startMessage = $_GET['startMessage'];
}
if (isset($_POST['moveButton'])) {
    $moveButton = $_POST['moveButton'];
}
if (isset($_POST['msg'])) {
    $msg = $_POST['msg'];
}
elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}
if (isset($_POST['expungeButton'])) {
    $expungeButton = $_POST['expungeButton'];
}
if (isset($_POST['targetMailbox'])) {
    $targetMailbox = $_POST['targetMailbox'];
}
if (isset($_SESSION['lastTargetMailbox'])) {
    $lastTargetMailbox = $_SESSION['lastTargetMailbox'];
}
if (isset($_POST['expungeButton'])) {
    $expungeButton = $_POST['expungeButton'];
}
if (isset($_POST['undeleteButton'])) {
    $undeleteButton = $_POST['undeleteButton'];
}
if (isset($_POST['markRead'])) {
    $markRead = $_POST['markRead'];
}
if (isset($_POST['markUnread'])) {
    $markUnread = $_POST['markUnread'];
}
if (isset($_POST['attache'])) {
    $attache = $_POST['attache'];
}

if (isset($_POST['location'])) {
    $location = $_POST['location'];
}

/* end of get globals */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$mbx_response=sqimap_mailbox_select($imapConnection, $mailbox);

$location = set_url_var($location,'composenew');
$location = set_url_var($location,'composesession');
$location = set_url_var($location,'session');

/* remember changes to mailbox setting */
if (!isset($lastTargetMailbox)) {
    $lastTargetMailbox = 'INBOX';
}
if ($targetMailbox != $lastTargetMailbox) {
    $lastTargetMailbox = $targetMailbox;
    sqsession_register($lastTargetMailbox, 'lastTargetMailbox');
}

// expunge-on-demand if user isn't using move_to_trash or auto_expunge
if(isset($expungeButton)) {
    $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
    if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
        if ($startMessage > $show_num) {
	    $location = set_url_var($location,'startMessage',$startMessage-$show_num);
	} else {
	    $location = set_url_var($location,'startMessage',1);
	}
    }
    header("Location: $location");
    exit;
} elseif(isset($undeleteButton)) {
    // undelete messages if user isn't using move_to_trash or auto_expunge
    if (is_array($msg) == 1) {
        // Removes \Deleted flag from selected messages
        $j = 0;
        $i = 0;
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
        while ($j < count($msg)) {
            if ($msg[$i]) {
                sqimap_messages_remove_flag ($imapConnection, $msg[$i], $msg[$i], "Deleted", true);
                $j++;
            }
            $i++;
        }
	header ("Location: $location"); 
	exit;
    } else {
        displayPageHeader($color, $mailbox);
        error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
    }
} elseif (!isset($moveButton)) {
    // If the delete button was pressed, the moveButton variable will not be set.
    if (is_array($msg) == 1) {
        // Marks the selected messages as 'Deleted'
        $j = 0;
        $i = 0;
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
        while ($j < count($msg)) {
            if (isset($msg[$i])) {
                if (isset($markRead)) {
                    sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Seen", true);
                } else if (isset($markUnread)) {
                    sqimap_messages_remove_flag($imapConnection, $msg[$i], $msg[$i], "Seen", true);
                } else if (isset($attache)) {
		    break;
                } else  {
                    sqimap_messages_delete($imapConnection, $msg[$i], $msg[$i], $mailbox);
                }
                $j++;
            }
            $i++;
        }
        if ($auto_expunge) {
            $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
        } else {
	    $cnt = 0;
	}
        if (isset($attache)) {
	    $composesession = attachSelectedMessages($msg, $imapConnection);
	    if ($compose_new_win) {
        	header ("Location: $location&composenew=1&session=$composesession");
		exit;
	    } else {
		$location = str_replace('search.php','compose.php',$location);
		$location = str_replace('right_main.php','compose.php',$location);
		header ("Location: $location&session=$composesession");
		exit;
	    }
	} else {		
	    if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
    	       if ($startMessage > $show_num) {
	           $location = set_url_var($location,'startMessage',$startMessage-$show_num);
	       } else {
	  	   $location = set_url_var($location,'startMessage',1);
	       }
	    }
            header ("Location: $location");
	    exit;
        } 
    } else {
        displayPageHeader($color, $mailbox);
        error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
    }
} else {    // Move messages
    // lets check to see if they selected any messages
    if (is_array($msg) == 1) {
        $j = 0;
        $i = 0;
        // If they have selected nothing msg is size one still, but will be an infinite
        //    loop because we never increment j.  so check to see if msg[0] is set or not to fix this.
	$cnt = count($msg);
        while ($j < $cnt) {
            if (isset($msg[$i])) {
                /** check if they would like to move it to the trash folder or not */
                sqimap_messages_copy($imapConnection, $msg[$i], $msg[$i], $targetMailbox);
                sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Deleted", true);
                $j++;
            }
            $i++;
        }
        if ($auto_expunge) {
            $cnt = sqimap_mailbox_expunge($imapConnection, $mailbox, true);
	} else {
	    $cnt = 0;
	}
	
	if (($startMessage+$cnt-1) >= $mbx_response['EXISTS']) {
    	    if ($startMessage > $show_num) {
		$location = set_url_var($location,'startMessage',$startMessage-$show_num);
	    } else {
		$location = set_url_var($location,'startMessage',1);
	    }
	}
	header ("Location: $location");
	exit;
    } else {
        displayPageHeader($color, $mailbox);
        error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
    }
}
// Log out this session
sqimap_logout($imapConnection);
?>
</BODY></HTML>
