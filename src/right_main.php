<?php
   /**
    **  right_main.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This is where the mailboxes are listed.  This controls most of what
    **  goes on in SquirrelMail.
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include('../functions/imap.php');
   include('../functions/date.php');
   include('../functions/array.php');
   include('../functions/mime.php');
   include('../functions/mailbox_display.php');
   include('../functions/display_messages.php');

   /////////////////////////////////////////////////////////////////////////////////
   //
   // incoming variables from URL:
   //    $sort             Direction to sort by date
   //                         values:  0  -  descending order
   //                         values:  1  -  ascending order
   //    $startMessage     Message to start at
   //    $mailbox          Full Mailbox name
   //
   // incoming from cookie:
   //    $username         duh
   //    $key              pass
   //
   /////////////////////////////////////////////////////////////////////////////////

   // open a connection on the imap port (143)
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   if (isset($newsort) && $newsort != $sort) {
      setPref($data_dir, $username, 'sort', $newsort);
   }

   // If the page has been loaded without a specific mailbox,
   //   send them to the inbox
   if (!isset($mailbox)) {
      $mailbox = 'INBOX';
      $startMessage = 1;
   }

   // compensate for the UW vulnerability
   if ($imap_server_type == 'uw' && (strstr($mailbox, '../') !== false ||
                                     substr($mailbox, 0, 1) == '/')) {
      $mailbox = 'INBOX';
   }

   sqimap_mailbox_select($imapConnection, $mailbox);
   displayPageHeader($color, $mailbox);

   do_hook('right_main_after_header');
   
   if ($just_logged_in == true) {
      $just_logged_in = false;
      
      if (strlen(trim($motd)) > 0) {
?><br>
<table align=center width=70% cellpadding=0 cellspacing=3 border=0
bgcolor="<?PHP echo $color[9] ?>">
<tr><td>
  <table width=100% cellpadding=5 cellspacing=1 border=0 bgcolor="<?PHP
    echo $color[4] ?>">
    <tr><td align=center><?PHP 
       echo $motd;
       do_hook('motd');
    ?></td></tr>
  </table>
</td></tr></table>
<?PHP
      }
   }

	if (isset($newsort)) {
		$sort = $newsort;
		session_register('sort');
	}	

   // Check to see if we can use cache or not.  Currently the only time when you wont use it is
   //    when a link on the left hand frame is used.  Also check to make sure we actually have the
   //    array in the registered session data.  :)
   if (! isset($use_mailbox_cache))
       $use_mailbox_cache = 0;
   if ($use_mailbox_cache && session_is_registered('msgs')) {
      showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color, $show_num, $use_mailbox_cache);
   } else {
      if (session_is_registered('msgs'))
         unset($msgs);
      if (session_is_registered('msort'))
         unset($msort);
		if (session_is_registered('numMessages'))
			unset($numMessages);

   	$numMessages = sqimap_get_num_messages ($imapConnection, $mailbox);

      showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color, $show_num, $use_mailbox_cache);
      
      if (session_is_registered('msgs') && isset($msgs))
         session_register('msgs');
      if (session_is_registered('msort') && isset($msort))
         session_register('msort');
      session_register('numMessages');
   }

   do_hook('right_main_bottom');
   sqimap_logout ($imapConnection);
?>
</FONT>
</BODY>
</HTML>
