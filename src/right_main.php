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
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

   /** If it was a successful login, lets load their preferences **/
   include("../src/load_prefs.php");
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";

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
