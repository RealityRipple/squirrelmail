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
   getMessageHeadersTo($imapConnection, $passed_id, $passed_id, $t);

   $subject = $s[0];
   $url_subj = urlencode(trim($subject));

   $d[0] = ereg_replace("  ", " ", $d[0]);
   $date = $d[0];
   $from_name = getSenderName($f[0]);
   $urlMailbox = urlencode($mailbox);

   $url_from = trim(decodeEmailAddr($f[0]));
   $url_from = urlencode($url_from);

   $to_left = trim($t[0]);
   for ($i = 0; $to_left;$i++) {
      if (strpos($to_left, ",")) {
         $to_ary[$i] = trim(substr($to_left, 0, strpos($to_left, ",")));
         $to_left = substr($to_left, strpos($to_left, ",")+1, strlen($to_left));
      }
      else {
         $to_ary[$i] = trim($to_left);
         $to_left = "";
      }
   }

   $i = 0;
   $to_string = "";
   while ($i < count($to_ary)) {
      if ($to_string)
         $to_string = "$to_string<BR>$to_ary[$i]";
      else
         $to_string = "$to_ary[$i]";

      $i++;
      if (count($to_ary) > 1) {
         if ($show_more == false) {
            if ($i == 1) {
               $to_string = "$to_string&nbsp;&nbsp;&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=1\">more</A>)";
               $i = count($to_ary);
            }
         } else if ($i == 1) {
            $to_string = "$to_string&nbsp;&nbsp;&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=0\">less</A>)";
         }
      }
   }

   echo "<BR>";
   echo "<TABLE COLS=1 WIDTH=95% BORDER=0 ALIGN=CENTER CELLPADDING=2>\n";
   echo "   <TR><TD BGCOLOR=DCDCDC WIDTH=100%>";
   echo "      <TABLE WIDTH=100% BORDER=0 COLS=2>";
   echo "         <TR>";
   echo "            <TD ALIGN=LEFT WIDTH=50%>";
   echo "               <FONT FACE=\"Arial,Helvetica\" SIZE=2>";
   echo "               <A HREF=\"right_main.php?sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\">Message List</A>&nbsp;&nbsp;";
   echo "               <A HREF=\"delete_message.php?mailbox=$urlMailbox&message=$passed_id&sort=$sort&startMessage=1\">Delete</A>&nbsp;&nbsp;";
   echo "               </FONT>";
   echo "            </TD><TD WIDTH=50% ALIGN=RIGHT>";
   echo "               <FONT FACE=\"Arial,Helvetica\" SIZE=2>";
   echo "               <A HREF=\"compose.php?forward_id=$passed_id&forward_subj=$url_subj&mailbox=$urlMailbox\">Forward</A>&nbsp;&nbsp;";
   echo "               <A HREF=\"compose.php?send_to=$url_from&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox\">Reply</A>&nbsp;&nbsp;";
   echo "               </FONT>";
   echo "            </TD>";
   echo "         </TR>";
   echo "      </TABLE>";
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
   /** to **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=FFFFFF WIDTH=15% ALIGN=RIGHT VALIGN=TOP>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">To:</FONT>\n";
   echo "         </TD><TD BGCOLOR=FFFFFF WIDTH=85% VALIGN=TOP>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$to_string</B></FONT>\n";
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