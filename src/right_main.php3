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
   include("functions/imap.php3");
   include("functions/strings.php3");
   include("functions/date.php3");
   include("functions/page_header.php3");
   include("functions/array.php3");
   include("functions/mailbox.php3");
   include("functions/mailbox_display.php3");
   include("functions/display_messages.php3");

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

   // make a URL safe $mailbox for use in the links
   $urlMailbox = urlencode($mailbox);
  
   displayPageHeader($mailbox);
   $i = 1;
   while ($i <= $numMessages) {
      getMessageHeaders($imapConnection, $i, $from, $subject, $date);

      $messages[$i]["DATE"] = getTimeStamp(explode(" ", trim($date)));
      $messages[$i]["ID"] = $i;
      $messages[$i]["FROM"] = $from;
      $messages[$i]["SUBJECT"] = $subject;
      $i++;
   }

   if ($sort == 0)
      $msgs = ary_sort($messages, "DATE", -1);
   else
      $msgs = ary_sort($messages, "DATE", 1);

   if ($endMessage > 24) {
      echo "<A HREF=\"right_main.php3?sort=1&startMessage=$nextGroup&mailbox=$urlMailbox\" TARGET=\"right\"><FONT FACE=\"Arial,Helvetica\">Next</FONT></A>&nbsp&nbsp&nbsp";
      $endMessage = 24;
   }

   /** Display "Next, Previous" on top */

   /** This is the beginning of the message list table.  It wraps around all messages */
   echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1>";
   echo "<TR><TD BGCOLOR=DCDCDC>";
   echo "<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=1 BGCOLOR=FFFFFF>";
   echo "<TR BGCOLOR=FFFFCC ALIGN=\"center\">";
   echo "   <TD WIDTH=5%><FONT FACE=\"Arial,Helvetica\"><B>Num</B></FONT></TD>";
   echo "   <TD WIDTH=25%><FONT FACE=\"Arial,Helvetica\"><B>From</B></FONT></TD>";
   echo "   <TD WIDTH=15%><FONT FACE=\"Arial,Helvetica\"><B>Date</B></FONT>";
   if ($sort == 0)
      echo "   <A HREF=\"right_main.php3?sort=1&startMessage=1&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/up_pointer.gif\" BORDER=0></A></TD>\n";
   else
      echo "   <A HREF=\"right_main.php3?sort=0&startMessage=0&mailbox=$urlMailbox\" TARGET=\"right\"><IMG SRC=\"../images/down_pointer.gif\" BORDER=0></A></TD>\n";
   echo "   <TD WIDTH=*><FONT FACE=\"Arial,Helvetica\"><B>Subject</B></FONT></TD>\n";
   echo "</TR>";

   // loop through and display the info for each message.
   for ($i = $startMessage;$i <= $endMessage; $i++) {
      printMessageInfo($imapConnection, $msgs[$i]["ID"], $msgs[$i]["FROM"], $msgs[$i]["SUBJECT"], $msgs[$i]["DATE"]);
   }

   echo "</TABLE>\n";
   echo "</TD></TR></TABLE>"; /** End of message-list table */

   /** Display "Next, Previous" on bottom */
   fclose($imapConnection);
?>
</FONT>
</BODY>
</HTML>
