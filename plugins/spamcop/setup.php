<?php
   /** 
    **  setup.php -- SpamCop plugin           
    **
    **  Copyright (c) 1999-2003 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **  
    **  $Id$
    * @package plugins
    * @subpackage spamcop
    **/

/** @ignore */
require_once(SM_PATH . 'functions/global.php');

/** Disable Quick Reporting by default */
$spamcop_quick_report = false;

/** Initialize the plugin */
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


// Load the settings
// Validate some of it (make '' into 'default', etc.)
function spamcop_load() {
   global $username, $data_dir, $spamcop_enabled, $spamcop_delete,
      $spamcop_method, $spamcop_id, $spamcop_quick_report;

   $spamcop_enabled = getPref($data_dir, $username, 'spamcop_enabled');
   $spamcop_delete = getPref($data_dir, $username, 'spamcop_delete');
   $spamcop_method = getPref($data_dir, $username, 'spamcop_method');
   $spamcop_id = getPref($data_dir, $username, 'spamcop_id');
    if ($spamcop_method == '') {
// This variable is not used
//      if (getPref($data_dir, $username, 'spamcop_form'))
//         $spamcop_method = 'web_form';
//      else

// Default to web_form. It is faster.
	$spamcop_method = 'web_form';
	setPref($data_dir, $username, 'spamcop_method', $spamcop_method);
    }
   if (! $spamcop_quick_report && $spamcop_method=='quick_email') {
	$spamcop_method = 'web_form';
	setPref($data_dir, $username, 'spamcop_method', $spamcop_method);
   }
   if ($spamcop_id == '')
      $spamcop_enabled = 0;
}


// Show the link on the read-a-message screen
function spamcop_show_link() {
   global $spamcop_enabled, $spamcop_method, $spamcop_quick_report;

   if (! $spamcop_enabled)
      return;

   /* GLOBALS */
   sqgetGlobalVar('passed_id',    $passed_id,    SQ_FORM);
   sqgetGlobalVar('mailbox',      $mailbox,      SQ_FORM);
   sqgetGlobalVar('startMessage', $startMessage, SQ_FORM);
   /* END GLOBALS */

   echo "<br>\n";

    /* 
       Catch situation when user use quick_email and does not update 
       preferences. User gets web_form link. If prefs are set to 
       quick_email format - they will be updated after clicking the link
     */
    if (! $spamcop_quick_report && $spamcop_method=='quick_email') {
	$spamcop_method = 'web_form';
    }
   
   if ($spamcop_method == 'web_form') {
?><script language="javascript" type="text/javascript">
document.write('<a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP echo urlencode($passed_id); ?>&amp;js_web=1&amp;mailbox=<?PHP echo urlencode($mailbox); ?>" target="_blank">');
document.write("<?PHP echo _("Report as Spam"); ?>");
document.write("</a>");
</script><noscript>
<a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP echo urlencode($passed_id); ?>&amp;mailbox=<?PHP echo urlencode($mailbox); ?>&amp;startMessage=<?PHP echo urlencode($startMessage); ?>">
<?PHP echo _("Report as Spam"); ?></a>
</noscript><?PHP
   } else {
?><a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP echo urlencode($passed_id); ?>&amp;mailbox=<?PHP echo urlencode($mailbox); ?>&amp;startMessage=<?PHP echo urlencode($startMessage); ?>">
<?PHP echo _("Report as Spam"); ?></a>
<?PHP
   }
}


// Show the link to our own custom options page
function spamcop_options()
{
   global $optpage_blocks;
   
   $optpage_blocks[] = array(
      'name' => _("SpamCop - Spam Reporting"),
      'url' => '../plugins/spamcop/options.php',
      'desc' => _("Help fight the battle against unsolicited email.  SpamCop reads the spam email and determines the correct addresses to send complaints to.  Quite fast, really smart, and easy to use."),
      'js' => false
   );
}


// When we send the email, we optionally trash it then too
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