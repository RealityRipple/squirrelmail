<?
   /**
    **  right_main.php
    **
    **  This is where the mailboxes are listed.  This controls most of what
    **  goes on in SquirrelMail.
    **
    **/

   if(!isset($logged_in)) {
      echo _("You must ");
      echo "<a href=\"login.php\">";
      echo _("login");
      echo "</a>";
      echo _(" first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }
?>
<HTML>
<FONT FACE="Arial,Helvetica">
<?
   include("../config/config.php");
   include("../functions/imap.php");
   include("../functions/strings.php");
   include("../functions/date.php");
   include("../functions/page_header.php");
   include("../functions/array.php");
   include("../functions/mailbox.php");
   include("../functions/mailbox_display.php");
   include("../functions/display_messages.php");

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
   $imapConnection = loginToImapServer($username, $key, $imapServerAddress, 0);

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   echo "<FONT FACE=\"Arial,Helvetica\">";

   // If the page has been loaded without a specific mailbox,
   //    just show a page of general info.
   if (!isset($mailbox)) {
      displayPageHeader($color, "None");
      general_info($motd, $org_logo, $version, $org_name, $color);
      echo "</BODY></HTML>";
      exit;
   }

   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // Display the header at the top of the page
   displayPageHeader($color, $mailbox);

   // Get the list of messages for this mailbox
   showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color);

   // close the connection
   fputs($imapConnection, "1 logout\n");
   fclose($imapConnection);
?>
</FONT>
</BODY>
</HTML>
