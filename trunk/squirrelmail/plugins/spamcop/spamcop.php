<?php

/**
 * spamcop.php -- SpamCop plugin -- main page
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage spamcop
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');
/* IMAP functions depend on date and mime */
include_once(SM_PATH . 'functions/date.php');
include_once(SM_PATH . 'functions/mime.php');
/* IMAP functions */
include_once(SM_PATH . 'functions/imap_general.php');
include_once(SM_PATH . 'functions/imap_messages.php');
/* plugin functions */
include_once(SM_PATH . 'plugins/spamcop/functions.php');

include_once(SM_PATH . 'functions/compose.php');

/* GLOBALS */

sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
sqgetGlobalVar('js_web', $js_web, SQ_GET);

if (! sqgetGlobalVar('startMessage', $startMessage, SQ_GET) ) {
    $startMessage = 1;
}
if (! sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET) ) {
    $passed_ent_id = 0;
}
if (! sqgetGlobalVar('js_web', $js_web, SQ_GET) ) {
    $js_web = 0;
}

sqgetGlobalVar('compose_messages', $compose_messages, SQ_SESSION);

if(! sqgetGlobalVar('composesession', $composesession, SQ_SESSION) ) {
    $composesession = 0;
    sqsession_register($composesession, 'composesession');
}
/* END GLOBALS */

// js_web variable is 1 only when link opens web based report page in new window
// and in new window menu line or extra javascript code is not needed.
if ($js_web) {
  displayHTMLHeader(_("SpamCop reporting"));
  echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n";
} else {
  displayPageHeader($color,$mailbox);
}

/** is spamcop plugin disabled */
if (! is_plugin_enabled('spamcop')) {
    error_box(_("Plugin is disabled."));
    // display footer (closes html tags) and stop script execution
    $oTemplate->display('footer.tpl');
    exit();
}

    global $imap_stream_options; // in case not defined in config
    $imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
    sqimap_mailbox_select($imap_stream, $mailbox);

    if ($spamcop_method == 'quick_email' ||
        $spamcop_method == 'thorough_email') {
       // Use email-based reporting -- save as an attachment
       $session = "$composesession"+1;
       $composesession = $session;
       sqsession_register($composesession,'composesession');
       if (!isset($compose_messages)) {
          $compose_messages = array();
       }
       if (!isset($compose_messages[$session]) || ($compose_messages[$session] == NULL)) {
          $composeMessage = new Message();
          $rfc822_header = new Rfc822Header();
          $composeMessage->rfc822_header = $rfc822_header;
          $composeMessage->reply_rfc822_header = '';
          $compose_messages[$session] = $composeMessage;
          sqsession_register($compose_messages,'compose_messages');
       } else {
          $composeMessage=$compose_messages[$session];
       }


        $message = sqimap_get_message($imap_stream, $passed_id, $mailbox);
        $composeMessage = spamcop_getMessage_RFC822_Attachment($message, $composeMessage, $passed_id,
                                      $passed_ent_id, $imap_stream);

            $compose_messages[$session] = $composeMessage;
        sqsession_register($compose_messages, 'compose_messages');

        $fn = getPref($data_dir, $username, 'full_name');
        $em = getPref($data_dir, $username, 'email_address');

        $HowItLooks = $fn . ' ';
        if ($em != '')
          $HowItLooks .= '<' . $em . '>';
     }


echo "<p>";
echo _("Sending this spam report will give you back a reply with URLs that you can click on to properly report this spam message to the proper authorities. This is a free service. By pressing the \"Send Spam Report\" button, you agree to follow SpamCop's rules/terms of service/etc.");
echo "</p>";

?>

<table align="center" width="75%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td align="left" valign="top">
<?php if (isset($js_web) && $js_web) {
  echo '<form method="post" action="javascript:return false">';
  echo '<input type="button" value="' . _("Close Window") . "\" onclick=\"window.close(); return true;\" />\n";
} else {
   ?><form method="post" action="<?php echo sqm_baseuri(); ?>src/right_main.php">
  <input type="hidden" name="mailbox" value="<?php echo sm_encode_html_special_chars($mailbox) ?>" />
  <input type="hidden" name="startMessage" value="<?php echo sm_encode_html_special_chars($startMessage) ?>" />
<?php
  echo '<input type="submit" value="' . _("Cancel / Done") . "\" />";
}
  ?></form>
</td>
<td align="right" valign="top">
<?php if ($spamcop_method == 'thorough_email' ||
          $spamcop_method == 'quick_email') {
   if ($spamcop_method == 'thorough_email')
      $report_email = 'submit.' . $spamcop_id . '@spam.spamcop.net';
   else
      $report_email = 'quick.' . $spamcop_id . '@spam.spamcop.net';
   $form_action = sqm_baseuri() . 'src/compose.php';
?>  <form method="post" action="<?php echo $form_action?>">
  <input type="hidden" name="smtoken" value="<?php echo sm_generate_security_token() ?>" />
  <input type="hidden" name="mailbox" value="<?php echo sm_encode_html_special_chars($mailbox) ?>" />
  <input type="hidden" name="spamcop_is_composing" value="<?php echo sm_encode_html_special_chars($passed_id) ?>" />
  <input type="hidden" name="send_to" value="<?php echo sm_encode_html_special_chars($report_email)?>" />
  <input type="hidden" name="subject" value="reply anyway" />
  <input type="hidden" name="identity" value="0" />
  <input type="hidden" name="session" value="<?php echo $session?>" />
<?php
  echo '<input type="submit" name="send1" value="' . _("Send Spam Report") . "\" />\n";
} else {
   $spam_message = mime_fetch_body ($imap_stream, $passed_id, $passed_ent_id, 50000);

   if (strlen($spam_message) == 50000) {
      $Warning = "\n[truncated by SpamCop]\n";
      $spam_message = substr($spam_message, 0, 50000 - strlen($Warning)) . $Warning;
   }
   $action_url="http://members.spamcop.net/sc";

   if (isset($js_web) && $js_web) {
     echo "<form method=\"post\" action=\"$action_url\" name=\"submitspam\"".
       " enctype=\"multipart/form-data\">\n";
   } else {
     echo "<form method=\"post\" action=\"$action_url\" name=\"submitspam\"".
       " enctype=\"multipart/form-data\" target=\"_blank\">\n";
   } ?>
  <input type="hidden" name="action" value="submit" />
  <input type="hidden" name="oldverbose" value="1" />
  <input type="hidden" name="spam" value="<?php echo sm_encode_html_special_chars($spam_message); ?>" />
    <?php
        echo '<input type="submit" name="x1" value="' . _("Send Spam Report") . "\" />\n";
    }
?>  </form>
</td>
</tr>
</table>
</body>
</html>
