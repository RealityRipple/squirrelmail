<?php
   /** 
    **  setup.php -- SpamCop plugin           
    **
    **  Copyright (c) 1999-2002 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **  
    **  $Id$                                                         
    **/

/* Initialize the plugin */
function squirrelmail_plugin_init_spamcop() {
   global $squirrelmail_plugin_hooks, $data_dir, $username,
      $spamcop_is_composing;

   $squirrelmail_plugin_hooks['optpage_register_block']['spamcop'] =
      'spamcop_options';
   $squirrelmail_plugin_hooks['loading_prefs']['spamcop'] =
      'spamcop_load';
   $squirrelmail_plugin_hooks['read_body_header_right']['spamcop'] =
      'spamcop_show_link';

   sqextractGlobalVar('spamcop_is_composing');
      
   if (isset($spamcop_is_composing)) {
      $squirrelmail_plugin_hooks['compose_send']['spamcop'] =
         'spamcop_while_sending';
   }
}


// Load the settings
// Validate some of it (make '' into 'default', etc.)
function spamcop_load() {
   global $username, $data_dir, $spamcop_enabled, $spamcop_delete,
      $spamcop_method, $spamcop_id;

   $spamcop_enabled = getPref($data_dir, $username, 'spamcop_enabled');
   $spamcop_delete = getPref($data_dir, $username, 'spamcop_delete');
   $spamcop_method = getPref($data_dir, $username, 'spamcop_method');
   $spamcop_id = getPref($data_dir, $username, 'spamcop_id');
   if ($spamcop_method == '') {
      if (getPref($data_dir, $username, 'spamcop_form'))
         $spamcop_method = 'web_form';
      else
         $spamcop_method = 'thorough_email';
      setPref($data_dir, $username, 'spamcop_method', $spamcop_method);
   }
   if ($spamcop_id == '')
      $spamcop_enabled = 0;
}


// Show the link on the read-a-message screen
function spamcop_show_link() {
   global $spamcop_enabled, $spamcop_method;

   if (! $spamcop_enabled)
      return;

   /* GLOBALS */
   $passed_id = $_GET['passed_id'];
   $mailbox = $_GET['mailbox'];
   $startMessage = $_GET['startMessage'];
   /* END GLOBALS */

   echo "<br>\n";
   
   if ($spamcop_method == 'web_form') {
?><script language=javascript>
document.write('<a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP
echo urlencode($passed_id); ?>&js_web=1&mailbox=<?PHP
echo urlencode($mailbox); ?>" target="_blank">');
document.write("<?PHP echo _("Report as Spam"); ?>");
document.write("</a>");
</script><noscript>
<a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP
echo urlencode($passed_id); ?>&mailbox=<?PHP
echo urlencode($mailbox); ?>&startMessage=<?PHP
echo urlencode($startMessage); ?>"><?PHP
echo _("Report as Spam"); ?></a>
</noscript><?PHP
   } else {
?><a href="../plugins/spamcop/spamcop.php?passed_id=<?PHP
echo urlencode($passed_id); ?>&mailbox=<?PHP
echo urlencode($mailbox); ?>&startMessage=<?PHP
echo urlencode($startMessage); ?>"><?PHP
echo _("Report as Spam"); ?></a><?PHP
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
