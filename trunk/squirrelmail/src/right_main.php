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

   include("../config/config.php");
   include("../functions/imap.php");
   include("../functions/strings.php");
   include("../functions/date.php");
   include("../functions/page_header.php");
   include("../functions/array.php");
   include("../functions/mime.php");
   include("../functions/mailbox_display.php");
   include("../functions/display_messages.php");
?>
<HTML>
<FONT FACE="Arial,Helvetica">
<?
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
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, 0);

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

   sqimap_mailbox_select($imapConnection, $mailbox);
   $numMessages = sqimap_get_num_messages ($imapConnection, $mailbox);
   displayPageHeader($color, $mailbox);

   showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort, $color);

   // close the connection
   sqimap_logout ($imapConnection);
?>
</FONT>
</BODY>
</HTML>
