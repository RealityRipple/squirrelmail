<?
   /**
    **  right_main.php3
    **
    **  This is where the mailboxes are listed.  This controls most of what
    **  goes on in SquirrelMail.
    **
    **/

   if(!isset($logged_in)) {
      echo "You must <a href=\"login.php3\">login</a> first.";
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }
?>
<HTML>
<BODY TEXT="#000000" BGCOLOR="#FFFFFF" LINK="#0000EE" VLINK="#0000EE" ALINK="#0000EE">
<FONT FACE="Arial,Helvetica">
<?
   include("../config/config.php3");
   include("../functions/imap.php3");
   include("../functions/strings.php3");
   include("../functions/date.php3");
   include("../functions/page_header.php3");
   include("../functions/array.php3");
   include("../functions/mailbox.php3");
   include("../functions/mailbox_display.php3");
   include("../functions/display_messages.php3");

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
   $imapConnection = fsockopen($imapServerAddress, 143, &$errorNumber, &$errorString);
   if (!$imapConnection) {
      echo "Error connecting to IMAP Server.<br>";
      echo "$errorNumber : $errorString<br>";
      exit;
   }
   $serverInfo = fgets($imapConnection, 256);

   // login
   fputs($imapConnection, "1 login $username $key\n");
   $read = fgets($imapConnection, 1024);
   if (strpos($read, "NO")) {
      error_username_password_incorrect();
      exit;
   }

   // If the page has been loaded without a specific mailbox,
   //    just show a page of general info.
   if (!isset($mailbox)) {
      displayPageHeader("None");
      general_info($motd, $org_logo, $version, $org_name);
      exit;
   }


   // switch to the mailbox, and get the number of messages in it.
   selectMailbox($imapConnection, $mailbox, $numMessages);
//   $numMessages = $numMessages - 1;  // I did this so it's 0 based like the message array

   // make a URL safe $mailbox for use in the links
   $urlMailbox = urlencode($mailbox);

   // Display the header at the top of the page
   displayPageHeader($mailbox);

   // Get the list of messages for this mailbox
   echo "$numMessages : $startMessage : $sort<BR><BR>";
   showMessagesForMailbox($imapConnection, $mailbox, $numMessages, $startMessage, $sort);

   // close the connection
   fclose($imapConnection);
?>
</FONT>
</BODY>
</HTML>
