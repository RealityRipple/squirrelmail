<?php
/**
 * Deliver_SendMail.class.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Delivery backend for the Deliver class.
 *
 * $Id$
 * @package squirrelmail
 */

/** This of course depends upon Deliver */
require_once(SM_PATH . 'class/deliver/Deliver.class.php');

/**
 * Delivers messages using the sendmail binary
 * @package squirrelmail
 */
class Deliver_SendMail extends Deliver {

    function preWriteToStream(&$s) {
       if ($s) {
    	  $s = str_replace("\r\n", "\n", $s);
       }
    }
    
    function initStream($message, $sendmail_path) {
        $rfc822_header = $message->rfc822_header;
	$from = $rfc822_header->from[0];
	$envelopefrom = trim($from->mailbox.'@'.$from->host);
	$envelopefrom = str_replace(array("\0","\n"),array('',''),$envelopefrom);
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
