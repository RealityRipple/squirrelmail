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
    **/

   if (!isset($i18n_php))
      include("../functions/i18n.php");

   session_start();

   if(!isset($logged_in) || !isset($username) || !isset($key)) {
      include ("../themes/default_theme.php");
      include ("../functions/display_messages.php");
      printf('<html><BODY TEXT="%s" BGCOLOR="%s" LINK="%s" VLINK="%s" ALINK="%s">',
              $color[8], $color[4], $color[7], $color[7], $color[7]);
      plain_error_message(_("You need a valid user and password to access this page!")
                          . "<br><a href=\"../src/login.php\">"
                          . _("Click here to log back in.") . "</a>.", $color);
      echo "</body></html>";
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

   if (isset($newsort) && $newsort != $sort) {
      setPref($data_dir, $username, "sort", $newsort);
   }

   // If the page has been loaded without a specific mailbox,
   //   send them to the inbox
   if (!isset($mailbox)) {
      $mailbox = "INBOX";
      $startMessage = 1;
   }

   sqimap_mailbox_select($imapConnection, $mailbox);
   displayPageHeader($color, $mailbox);

   do_hook("right_main_after_header");
   
   if ($just_logged_in == 1 && strlen(trim($motd)) > 0) {
      echo "<center><br>\n";
      echo "<table width=70% cellpadding=0 cellspacing=0 border=0><tr><td bgcolor=\"$color[9]\">\n";
      echo "<table width=100% cellpadding=5 cellspacing=1 border=0><tr><td bgcolor=\"$color[4]\">\n";
      echo "$motd\n";
      echo "</td></tr></table>\n";
      echo "</td></tr></table>\n";
      echo "</center><br>\n";
   }

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

   do_hook("right_main_bottom");
   sqimap_logout ($imapConnection);
?>
</FONT>
</BODY>
</HTML>
