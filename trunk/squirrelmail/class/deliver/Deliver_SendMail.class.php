<?php
/**
 * Deliver_SendMail.class.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Delivery backend for the Deliver class.
 *
 * $Id$
 */

require_once(SM_PATH . 'class/deliver/Deliver.class.php');

class Deliver_SendMail extends Deliver {

    function preWriteToStream(&$s) {
       if ($s) {
    	  $s = str_replace("\r\n", "\n", $s);
       }
    }
    
    function initStream($message, $sendmail_path) {
        $rfc822_header = $message->rfc822_header;
	$from = $rfc822_header->from[0];
	$envelopefrom = $from->mailbox.'@'.$from->host;
	if (strstr($sendmail_path, "qmail-inject")) {
    	    $stream = popen (escapeshellcmd("$sendmail_path -i -f$envelopefrom"), "w");
	} else {
    	    $stream = popen (escapeshellcmd("$sendmail_path -i -t -f$envelopefrom"), "w");
	}
	return $stream;
    }
    
    function finalizeStream($stream) {
	pclose($stream);
	return true;
    }
    
    function getBcc() {
       return true;
    }
    
}
?>
