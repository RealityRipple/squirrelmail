<?php
   /**
    **  right_main.php
    **
    **  This is where the mailboxes are listed.  This controls most of what
    **  goes on in SquirrelMail.
    **
    **/


   session_start();

   if(!isset($logged_in)) {
      echo _("You must login first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($date_php))
      include("../functions/date.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($mime_php))
      include("../functions/mime.php");
   if (!isset($mailbox_display_php))
      include("../functions/mailbox_display.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
?>
<?php
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

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");

   // If the page has been loaded without a specific mailbox,
   //    just show a page of general info.
   if (!isset($mailbox)) {
      displayPageHeader($color, "None");
      general_info($motd, $org_logo, $version, $org_name, $color);
      echo "</BODY></HTML>";
      exit;
   }

   sqimap_mailbox_select($imapConnection, $mailbox);
   displayPageHeader($color, $mailbox);

	if (isset($newsort)) {
		$sort = $newsort;
		session_register("sort");
	}	

   // Check to see if we can use cache or not.  Currently the only time when you wont use it is
   //    when a link on the left hand frame is used.  Also check to make sure we actually have the
   //    array in the registered session data.  :)
   if ($use_mailbox_cache && session_is_registered("msgs")) {
      showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color, $show_num, $use_mailbox_cache);
   } else {
      if (session_is_registered("msgs"))
         unset($msgs);
      if (session_is_registered("msort"))
         unset($msort);
		if (session_is_registered("numMessages"))
			unset($numMessages);

   	$numMessages = sqimap_get_num_messages ($imapConnection, $mailbox);

      showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color, $show_num, $use_mailbox_cache);
      
      if (session_is_registered("msgs") && isset($msgs))
         session_register("msgs");
      if (session_is_registered("msort") && isset($msort))
         session_register("msort");
      session_register("numMessages");
   }

   // close the connection
   sqimap_logout ($imapConnection);
?>
</FONT>
</BODY>
</HTML>
