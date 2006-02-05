<?php

/**
 * SpamCop plugin - functions
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage spamcop
 */

/* sqgetGlobalVar(), getPref(), setPref(), sqimap functions are used */

/**
 * Disable Quick Reporting by default
 * @global boolean $spamcop_quick_report
 * @since 1.4.3 and 1.5.0
 */
global $spamcop_quick_report;
$spamcop_quick_report = false;

/**
 * Loads spamcop settings and validates some of values (make '' into 'default', etc.)
 * 
 * Internal function used to reduce size of setup.php
 * @since 1.5.1
 * @access private
 */
function spamcop_load_function() {
    global $username, $data_dir, $spamcop_enabled, $spamcop_delete, $spamcop_save,
           $spamcop_method, $spamcop_id, $spamcop_quick_report, $spamcop_type;

    $spamcop_enabled = getPref($data_dir, $username, 'spamcop_enabled');
    $spamcop_delete = getPref($data_dir, $username, 'spamcop_delete');
    $spamcop_save = getPref($data_dir, $username, 'spamcop_save',true);
    $spamcop_method = getPref($data_dir, $username, 'spamcop_method');
    $spamcop_type = getPref($data_dir, $username, 'spamcop_type');
    $spamcop_id = getPref($data_dir, $username, 'spamcop_id');
    if ($spamcop_method == '') {
        // Default to web_form. It is faster.
        $spamcop_method = 'web_form';
        setPref($data_dir, $username, 'spamcop_method', $spamcop_method);
    }
    if (! $spamcop_quick_report && $spamcop_method=='quick_email') {
        $spamcop_method = 'web_form';
        setPref($data_dir, $username, 'spamcop_method', $spamcop_method);
    }
    if ($spamcop_type == '') {
        $spamcop_type = 'free';
        setPref($data_dir, $username, 'spamcop_type', $spamcop_type);
    }
    if ($spamcop_id == '')
        $spamcop_enabled = 0;
}

/**
 * Add spamcop link to read_body (internal function)
 * @since 1.5.1
 * @access private
 */
function spamcop_show_link_function() {
    global $spamcop_enabled, $spamcop_method, $spamcop_quick_report,$javascript_on;

    if (! $spamcop_enabled)
        return;

    /* GLOBALS */
    sqgetGlobalVar('passed_id',    $passed_id,    SQ_FORM);
    sqgetGlobalVar('passed_ent_id',$passed_ent_id,SQ_FORM);
    sqgetGlobalVar('mailbox',      $mailbox,      SQ_FORM);
    if ( sqgetGlobalVar('startMessage', $startMessage, SQ_FORM) ) {
        $startMessage = (int)$startMessage;
    }
    /* END GLOBALS */

    // catch unset passed_ent_id
    if (! sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_FORM) ) {
        $passed_ent_id = 0;
    }

    echo "<br />\n";

    /*
       Catch situation when user uses quick_email and does not update
       preferences. User gets web_form link. If prefs are set to
       quick_email format - they will be updated after clicking the link
     */
    if (! $spamcop_quick_report && $spamcop_method=='quick_email') {
        $spamcop_method = 'web_form';
    }

    // Javascript is used only in web based reporting
    // don't insert javascript if javascript is disabled
    if ($spamcop_method == 'web_form' && $javascript_on) {
?><script type="text/javascript">
document.write('<a href="../plugins/spamcop/spamcop.php?passed_id=<?php echo urlencode($passed_id); ?>&amp;js_web=1&amp;mailbox=<?php echo urlencode($mailbox); ?>&amp;passed_ent_id=<?php echo urlencode($passed_ent_id); ?>" target="_blank">');
document.write("<?php echo _("Report as Spam"); ?>");
document.write("</a>");
</script><?php
    } else {
?><a href="../plugins/spamcop/spamcop.php?passed_id=<?php echo urlencode($passed_id); ?>&amp;mailbox=<?php echo urlencode($mailbox); ?>&amp;startMessage=<?php echo urlencode($startMessage); ?>&amp;passed_ent_id=<?php echo urlencode($passed_ent_id); ?>">
<?php echo _("Report as Spam"); ?></a>
<?php
    }
}

/**
 * Add spamcop option block (internal function)
 * @since 1.5.1
 * @access private
 */
function spamcop_options_function() {
    global $optpage_blocks;

    $optpage_blocks[] = array(
            'name' => _("SpamCop - Spam Reporting"),
            'url' => '../plugins/spamcop/options.php',
            'desc' => _("Help fight the battle against unsolicited email. SpamCop reads the spam email and determines the correct addresses to send complaints to. Quite fast, really smart, and easy to use."),
            'js' => false
            );
}

/**
 * Process messages that are submitted by email.
 *
 * Delete spam if user wants to delete it. Don't save submitted emails.
 * Implement overrides that fix compose.php behavior.
 * @since 1.5.1
 * @access private
 */
function spamcop_while_sending_function() {
    global $mailbox, $spamcop_delete, $spamcop_save, $spamcop_is_composing, $auto_expunge,
           $username, $key, $imapServerAddress, $imapPort;

    if (sqgetGlobalVar('spamcop_is_composing' , $spamcop_is_composing)) {
        // delete spam message
        if ($spamcop_delete) {
            $imapConnection = sqimap_login($username, $key, $imapServerAddress,
                $imapPort, 0);
            sqimap_mailbox_select($imapConnection, $mailbox);
            sqimap_msgs_list_delete($imapConnection, $mailbox, array($spamcop_is_composing));
            if ($auto_expunge)
                sqimap_mailbox_expunge($imapConnection, $mailbox, true);
        }
        if (! $spamcop_save) {
            // disable use of send folder.
            // Temporally override in order to disable saving of 'reply anyway' messages.
            global $default_move_to_sent;
            $default_move_to_sent=false;
        }
        // change default email composition setting. Plugin always operates in right frame.
        // make sure that compose.php redirects to right page. Temporally override.
        global $compose_new_win;
        $compose_new_win = false;
    }
}

/**
 * Internal spamcop plugin function.
 *
 * It is used to display similar action links.
 * @access private
 */
function spamcop_enable_disable($option,$disable_action,$enable_action) {
    if ($option) {
        $ret= _("Enabled") . " (<a href=\"options.php?action=$disable_action\">" . _("Disable it") . "</a>)\n";
    } else {
        $ret = _("Disabled") . " (<a href=\"options.php?action=$enable_action\">" . _("Enable it") . "</a>)\n";
    }
    return $ret;
}

/**
 * Stores message in attachment directory, when email based reports are used
 * @access private
 * @todo Duplicate code in src/compose.php
 */
function spamcop_getMessage_RFC822_Attachment($message, $composeMessage, $passed_id,
                                      $passed_ent_id='', $imapConnection) {
    global $attachment_dir, $username;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    if (!$passed_ent_id) {
        $body_a = sqimap_run_command($imapConnection,
                                    'FETCH '.$passed_id.' RFC822',
                                    TRUE, $response, $readmessage,
                                    TRUE);
    } else {
        $body_a = sqimap_run_command($imapConnection,
                                     'FETCH '.$passed_id.' BODY['.$passed_ent_id.']',
                                     TRUE, $response, $readmessage,TRUE);
        $message = $message->parent;
    }
    if ($response == 'OK') {
        array_shift($body_a);
        $body = implode('', $body_a) . "\r\n";

        $localfilename = GenerateRandomString(32, 'FILE', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";
        $fp = fopen( $full_localfilename, 'w');
        fwrite ($fp, $body);
        fclose($fp);

        /* dirty relative dir fix */
        if (substr($attachment_dir,0,3) == '../') {
           $attachment_dir = substr($attachment_dir,3);
           $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
        }
        $full_localfilename = "$hashed_attachment_dir/$localfilename";

        $composeMessage->initAttachment('message/rfc822','email.txt',
                         $full_localfilename);
    }
    return $composeMessage;
}
?>