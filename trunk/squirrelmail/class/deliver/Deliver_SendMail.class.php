<?php

require_once('Deliver.class.php');

class Deliver_SendMail extends Deliver {

    function preWriteToStream(&$s) {
       if ($s) {
          if ($s{0} == '.')   $s = '.' . $s;
    	  $s = str_replace("\n.","\n..",$s);
          $s = str_replace("\r\n", "\n", $s);
       }
    }
    
    function initStream($message, $sendmail_path) {
        $rfc822_header = $message->rfc822_header;
	$from = $rfc822_header->from[0];
	$envelopefrom = $from->mailbox.'@'.$from->host;
	if (strstr($sendmail_path, "qmail-inject")) {
    	    $stream = popen (escapeshellcmd("$sendmail_path -f$envelopefrom"), "w");
	} else {
    	    $stream = popen (escapeshellcmd("$sendmail_path -t -f$envelopefrom"), "w");
	}
    }
    
    function finalizeStream($stream) {
	pclose($stream);
    }
}
?>
