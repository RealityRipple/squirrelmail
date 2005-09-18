<?php

/**
 * Deliver_SendMail.class.php
 *
 * Delivery backend for the Deliver class.
 *
 * @author Marc Groot Koerkamp
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/** This of course depends upon Deliver */
require_once(SM_PATH . 'class/deliver/Deliver.class.php');

/**
 * Delivers messages using the sendmail binary
 * @package squirrelmail
 */
class Deliver_SendMail extends Deliver {

   /**
    * function preWriteToStream
    *
    * Sendmail needs LF's as line endings instead of CRLF.
    * This function translates the line endings to LF and should be called
    * before each line is written to the stream.
    *
    * @param string $s Line to process
    * @return void
    * @access private
    */
    function preWriteToStream(&$s) {
       if ($s) {
           $s = str_replace("\r\n", "\n", $s);
       }
    }

   /**
    * function initStream
    *
    * Initialise the sendmail connection.
    *
    * @param Message $message Message object containing the from address
    * @param string $sendmail_path Location of sendmail binary
    * @return void
    * @access public
    */
    function initStream($message, $sendmail_path) {
        $rfc822_header = $message->rfc822_header;
        $from = $rfc822_header->from[0];
        $envelopefrom = trim($from->mailbox.'@'.$from->host);
        $envelopefrom = str_replace(array("\0","\n"),array('',''),$envelopefrom);
        if (strstr($sendmail_path, "qmail-inject")) {
            $stream = popen (escapeshellcmd("$sendmail_path -f$envelopefrom"), "w");
        } else {
            $stream = popen (escapeshellcmd("$sendmail_path -i -t -f$envelopefrom"), "w");
        }
        return $stream;
    }

   /**
    * function finalizeStream
    *
    * Close the stream.
    *
    * @param resource $stream
    * @return boolean
    * @access public
    */
    function finalizeStream($stream) {
        pclose($stream);
        return true;
    }

   /**
    * function getBcc
    *
    * In case of sendmail, the rfc822header must contain the bcc header.
    *
    * @return boolean true if rfc822header should include the bcc header.
    * @access private
    */
    function getBcc() {
       return true;
    }

   /**
    * function clean_crlf
    *
    * Cleans each line to only end in a LF
    * Returns the length of the line including a CR,
    * so that length is correct when the message is saved to imap
    * Implemented to fix sendmail->postfix rejection of messages with
    * attachments because of stray LF's
    *
    * @param string $s string to strip of CR's
    * @return integer length of string including a CR for each LF
    * @access private
    */
    function clean_crlf(&$s) {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        $s2 = str_replace("\n", "\r\n", $s);
        return strlen($s2);
    }


}
?>