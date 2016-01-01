<?php

/**
 * compose.php
 *
 * Functions for message compositon: writing a message, attaching files etc.
 *
 * @author Thijs Kinkhorst <kink at squirrelmail.org>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Get a new file to write an attachment to.
 * This function makes sure it doesn't overwrite other attachments,
 * preventing collisions and race conditions.
 *
 * @return filename of the tempfile only (not full path)
 * @since 1.5.2
 */
function sq_get_attach_tempfile()
{
    global $username, $attachment_dir;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    // using PHP >= 4.3.2 we can be truly atomic here
    $filemods = check_php_version ( 4,3,2 ) ? 'x' : 'w';

    // give up after 1000 tries
    $TMP_MAX = 1000;
    for ($try=0; $try<$TMP_MAX; ++$try) {

        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";

        // filename collision. try again
        if ( file_exists($full_localfilename) ) {
            continue;
        }

        // try to open for (binary) writing
        $fp = @fopen( $full_localfilename, $filemods);

        if ( $fp !== FALSE ) {
            // success! make sure it's not readable, close and return filename
            chmod($full_localfilename, 0600);
            fclose($fp);
            return $localfilename;
        }
    }

    // we tried 1000 times but didn't succeed.
    error_box( _("Could not open temporary file to store attachment. Contact your system administrator to resolve this issue.") );
    return FALSE;
}


/**
  * Send a simple mail message using SquirrelMail's API.
  *
  * Until SquirrelMail is sufficiently redesigned, this
  * function is a stand-in for a simple mail delivery
  * call.  Currently, it only sends plaintext messages
  * (unless the caller uses the $message parameter).
  *
  * @param string $to      The destination recipient.
  * @param string $subject The message subject.
  * @param string $body    The message body.
  * @param string $from    The sender.
  * @param string $cc      The destination carbon-copy recipient.
  *                        (OPTIONAL; default no Cc:)
  * @param string $bcc     The destination blind carbon-copy recipient.
  *                        (OPTIONAL; default no Bcc:)
  * @param object $message If the caller wants to construct a more
  *                        complicated message themselves and pass
  *                        it here, this function will take care
  *                        of the rest - handing it over to SMTP
  *                        or Sendmail.  If this parameter is non-
  *                        empty, all other parameters are ignored.
  *                        (OPTIONAL: default is empty)
  *
  * @return array A two-element array, the first element being a
  *               boolean value indicating if the message was successfully
  *               sent or not, and the second element being the message's
  *               assigned Message-ID, if available (only available as of
  *               SquirrelMail 1.4.14 and 1.5.2)
  *
  */
function sq_send_mail($to, $subject, $body, $from, $cc='', $bcc='', $message='')
{

   require_once(SM_PATH . 'functions/mime.php');
   require_once(SM_PATH . 'class/mime.class.php');

   if (empty($message))
   {
      $message = new Message();
      $header  = new Rfc822Header();

      $message->setBody($body);
      $content_type = new ContentType('text/plain');
      global $special_encoding, $default_charset;
      if ($special_encoding)
         $header->encoding = $special_encoding;
      else
         $header->encoding = '8bit';
      if ($default_charset)
         $content_type->properties['charset']=$default_charset;
      $header->content_type = $content_type;

      $header->parseField('To', $to);
      $header->parseField('Cc', $cc);
      $header->parseField('Bcc', $bcc);
      $header->parseField('From', $from);
      $header->parseField('Subject', $subject);
      $message->rfc822_header = $header;
   }
//sm_print_r($message);exit;


   global $useSendmail;


   // ripped from src/compose.php - based on both 1.5.2 and 1.4.14
   //
   if (!$useSendmail) {
      require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
      $deliver = new Deliver_SMTP();
      global $smtpServerAddress, $smtpPort, $pop_before_smtp,
             $domain, $pop_before_smtp_host;

      $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;
      if (empty($pop_before_smtp_host)) $pop_before_smtp_host = $smtpServerAddress;
      $user = '';
      $pass = '';
      get_smtp_user($user, $pass);
      $stream = $deliver->initStream($message,$domain,0,
                $smtpServerAddress, $smtpPort, $user, $pass, $authPop, $pop_before_smtp_host);
   } else {
      require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
      global $sendmail_path, $sendmail_args;
      // Check for outdated configuration
      if (!isset($sendmail_args)) {
         if ($sendmail_path=='/var/qmail/bin/qmail-inject') {
            $sendmail_args = '';
         } else {
            $sendmail_args = '-i -t';
         }
      }
      $deliver = new Deliver_SendMail(array('sendmail_args'=>$sendmail_args));
      $stream = $deliver->initStream($message,$sendmail_path);
   }


   $success = false;
   $message_id = '';
   if ($stream) {
      $deliver->mail($message, $stream);
      if (!empty($message->rfc822_header->message_id)) {
         $message_id = $message->rfc822_header->message_id;
      }

      $success = $deliver->finalizeStream($stream);
   }

   return array($success, $message_id);

}


