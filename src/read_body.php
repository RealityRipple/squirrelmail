<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages);

   echo "<HTML><BODY TEXT=\"#000000\" BGCOLOR=\"#FFFFFF\" LINK=\"#0000EE\" VLINK=\"#0000EE\" ALINK=\"#0000EE\">\n";
   displayPageHeader($mailbox);
   $body = fetchBody($imapConnection, $passed_id);
   getMessageHeaders($imapConnection, $passed_id, $passed_id, $f, $s, $d);
//   setMessageFlag($imapConnection, $passed_id, $passed_id, "Seen");

   $subject = $s[0];
   $d[0] = ereg_replace("  ", " ", $d[0]);
//   $date = explode(" ", trim($d[0]));
//   $date = getDateString($date);
   $date = $d[0];
   $from_name = getSenderName($f[0]);
   $urlMailbox = urlencode($mailbox);

   echo "<BR>";
   echo "<TABLE COLS=1 WIDTH=95% BORDER=0 ALIGN=CENTER CELLPADDING=2>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC WIDTH=100%>";
   echo "      <A HREF=\"right_main.php?sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\">Message List</A>&nbsp;&nbsp;";
   echo "   </TD></TR>";
   echo "   <TR><TD BGCOLOR=FFFFFF WIDTH=100%>";
   echo "   <TABLE COLS=2 WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=2>\n";
   echo "      <TR>\n";
   /** subject **/
   echo "         <TD BGCOLOR=FFFFFF WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">Subject:</FONT>\n";
   echo "         </TD><TD BGCOLOR=FFFFFF WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$subject</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** from **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=FFFFFF WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">From:</FONT>\n";
   echo "         </TD><TD BGCOLOR=FFFFFF WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$from_name</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** date **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=FFFFFF WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">Date:</FONT>\n";
   echo "         </TD><TD BGCOLOR=FFFFFF WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$date</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";

   echo "   </TABLE></TD></TR>\n";

   echo "   <TR><TD BGCOLOR=FFFFFF WIDTH=100%><BR>\n";
   $i = 0;
   while ($i < count($body)) {
      echo "$body[$i]";
      $i++;
   }
   echo "   <BR></TD></TR>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC>&nbsp;</TD></TR>";
   echo "</TABLE>\n";

?>