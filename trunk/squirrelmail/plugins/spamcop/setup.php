<?php
/** 
 * setup.php -- SpamCop plugin - setup script
 *
 * @copyright (c) 1999-2004 The SquirrelMail development team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage spamcop
 */

/** @ignore */
require_once(SM_PATH . 'functions/global.php');

/** Disable Quick Reporting by default */
$spamcop_quick_report = false;

/**
 * Initialize the plugin 
 * @access private
 */
function squirrelmail_plugin_init_spamcop() {
   global $squirrelmail_plugin_hooks, $data_dir, $username,
      $spamcop_is_composing;

   $squirrelmail_plugin_hooks['optpage_register_block']['spamcop'] =
      'spamcop_options';
   $squirrelmail_plugin_hooks['loading_prefs']['spamcop'] =
      'spamcop_load';
   $squirrelmail_plugin_hooks['read_body_header_right']['spamcop'] =
      'spamcop_show_link';

    sqgetGlobalVar('spamcop_is_composing' , $spamcop_is_composing);
      
   if (isset($spamcop_is_composing)) {
      $squirrelmail_plugin_hooks['compose_send']['spamcop'] =
         'spamcop_while_sending';
   }
}


/**
 * Loads spamcop settings and validates some of values (make '' into 'default', etc.)
 * @access private
 */
function spamcop_load() {
   global $username, $data_dir, $spamcop_enabled, $spamcop_delete,
      $spamcop_method, $spamcop_id, $spamcop_quick_report, $spamcop_type;

   $spamcop_enabled = getPref($data_dir, $username, 'spamcop_enabled');
   $spamcop_delete = getPref($data_dir, $username, 'spamcop_delete');
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
 * Shows spamcop link on the read-a-message screen
 * @access private
 */
function spamcop_show_link() {
   global $spamcop_enabled, $spamcop_method, $spamcop_quick_report,$javascript_on;

   if (! $spamcop_enabled)
      return;

   /* GLOBALS */
   sqgetGlobalVar('passed_id',    $passed_id,    SQ_FORM);
   sqgetGlobalVar('passed_ent_id',$passed_ent_id,SQ_FORM);
   sqgetGlobalVar('mailbox',      $mailbox,      SQ_FORM);
   sqgetGlobalVar('startMessage', $startMessage, SQ_FORM);
   /* END GLOBALS */

   // catch unset passed_ent_id
   if (! sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_FORM) ) {
    $passed_ent_id = 0;
   }

   echo "<br />\n";

    /* 
       Catch situation when user use quick_email and does not update 
       preferences. User gets web_form link. If prefs are set to 
       quick_email format - they will be updated after clicking the link
     */
    if (! $spamcop_quick_report && $spamcop_method=='quick_email') {
	$spamcop_method = 'web_form';
    }
   
    // Javascript is used only in web based reporting
    // don't insert javascript if javascript is disabled
   if ($spamcop_method == 'web_form' && $javascript_on) {
?><script language="javascript" type="text/javascript">
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
 * Show spamcop options block
 * @access private
 */
function spamcop_options()
{
   global $optpage_blocks;
   
   $optpage_blocks[] = array(
      'name' => _("SpamCop - Spam Reporting"),
      'url' => '../plugins/spamcop/options.php',
      'desc' => _("Help fight the battle against unsolicited email. SpamCop reads the spam email and determines the correct addresses to send complaints to. Quite fast, really smart, and easy to use."),
      'js' => false
   );
}


/**
 * When we send the email, we optionally trash it then too
 * @access private
 */
function spamcop_while_sending()
{
   global $mailbox, $spamcop_delete, $spamcop_is_composing, $auto_expunge, 
      $username, $key, $imapServerAddress, $imapPort;

   if ($spamcop_delete) {
      $imapConnection = sqimap_login($username, $key, $imapServerAddress, 
         $imapPort, 0);
      sqimap_mailbox_select($imapConnection, $mailbox);
      sqimap_messages_delete($imapConnection, $spamcop_is_composing, 
         $spamcop_is_composing, $mailbox);
      if ($auto_expunge)
         sqimap_mailbox_expunge($imapConnection, $mailbox, true);
   }
}

?>