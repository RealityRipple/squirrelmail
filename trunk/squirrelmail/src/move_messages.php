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

require_once('../src/validate.php');
require_once('../functions/display_messages.php');
require_once('../functions/imap.php');
require_once('../functions/html.php');
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
    global $mailbox, $username, $attachment_dir, $attachments, $identity, 
           $data_dir, $composesession, $lastTargetMailbox, $uid_support,
	   $msgs, $startMessage, $show_num, $thread_sort_messages,
	   $allow_server_sort;

    if (!isset($attachments)) {
	    $attachments = array();
	    session_register('attachments');
    }

    if (!isset($composesession) ) {
	    $composesession = 1;
	    session_register('composesession');
    } else {
	    $composesession++;
    }

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir, $composesession);

    $rem_attachments = array();
    foreach ($attachments as $info) {
    	if ($info['session'] == $composesession) {
    	    $attached_file = "$hashed_attachment_dir/$info[localfilename]";
    	    if (file_exists($attached_file)) {
        	    unlink($attached_file);
    	    }
    	} else {
    	    $rem_attachments[] = $info;
    	}
    }

    $attachments = $rem_attachments;

    if ($thread_sort_messages || $allow_server_sort) {
       $start_index=0;
    } else {
       $start_index = ($startMessage-1) * $show_num;
    }

    $i = 0;
    $j = 0;
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
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
        	
        	$fp = fopen( $full_localfilename, 'w');
        	fwrite ($fp, $body);
        	fclose($fp);
    		$newAttachment = array();
        	$newAttachment['localfilename'] = $localfilename;
        	$newAttachment['type'] = "message/rfc822";
            	$newAttachment['remotefilename'] = $subject.'.eml';
            	$newAttachment['session'] = $composesession;
        	$attachments[] = $newAttachment;
        	flush();
    	    }
	    $j++;
	}
	$i++;	
    }
    setPref($data_dir, $username, 'attachments', serialize($attachments));
    return $composesession;
}

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imapConnection, $mailbox);

$location = set_url_var($location,'composenew');
$location = set_url_var($location,'composesession');
$location = set_url_var($location,'session');


/* remember changes to mailbox setting */
if (!isset($lastTargetMailbox)) {
    $lastTargetMailbox = 'INBOX';
}
if ($targetMailbox != $lastTargetMailbox) {
    $lastTargetMailbox = $targetMailbox;
    session_register('lastTargetMailbox');
}

// expunge-on-demand if user isn't using move_to_trash or auto_expunge
if(isset($expungeButton)) {
    sqimap_mailbox_expunge($imapConnection, $mailbox, true);
    header("Location: $location");
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
                sqimap_messages_remove_flag ($imapConnection, $msg[$i], $msg[$i], "Deleted");
                $j++;
            }
            $i++;
        }
	header ("Location: $location"); 
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
                    sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Seen");
                } else if (isset($markUnread)) {
                    sqimap_messages_remove_flag($imapConnection, $msg[$i], $msg[$i], "Seen");
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
            sqimap_mailbox_expunge($imapConnection, $mailbox, true);
        }
        if (isset($attache)) {
	    $composesession = attachSelectedMessages($msg, $imapConnection);
	    if ($compose_new_win) {
        	header ("Location: $location&composenew=1&session=$composesession");
	    } else {
		$location = str_replace('search.php','compose.php',$location);
		$location = str_replace('right_main.php','compose.php',$location);
		header ("Location: $location&session=$composesession");
	    }
	} else {		
            header ("Location: $location");
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
        while ($j < count($msg)) {
            if (isset($msg[$i])) {
                /** check if they would like to move it to the trash folder or not */
                sqimap_messages_copy($imapConnection, $msg[$i], $msg[$i], $targetMailbox);
                sqimap_messages_flag($imapConnection, $msg[$i], $msg[$i], "Deleted");
                $j++;
            }
            $i++;
        }
        if ($auto_expunge) {
            sqimap_mailbox_expunge($imapConnection, $mailbox, true);
	}
	header ("Location: $location"); 
    } else {
        displayPageHeader($color, $mailbox);
        error_message(_("No messages were selected."), $mailbox, $sort, $startMessage, $color);
    }
}
// Log out this session
sqimap_logout($imapConnection);

?>
</BODY></HTML>
