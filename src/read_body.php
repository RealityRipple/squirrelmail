<?
   include("../config/config.php");
   include("../functions/strings.php");
   include("../functions/page_header.php");
   include("../functions/imap.php");
   include("../functions/mime.php");
   include("../functions/mailbox.php");
   include("../functions/date.php");

   $imapConnection = loginToImapServer($username, $key, $imapServerAddress);
   selectMailbox($imapConnection, $mailbox, $numMessages);

   // $message contains all information about the message
   // including header and body
   $message = fetchMessage($imapConnection, $passed_id);

   echo "<HTML>";
   echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"" . $message["HEADER"]["TYPE"][0] . "/" . $message["HEADER"]["TYPE"][1] . "; charset=" . $message["HEADER"]["CHARSET"] . "\">";
   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   displayPageHeader($color, $mailbox);

   /** translate the subject and mailbox into url-able text **/
   $url_subj = urlencode(trim(stripslashes($message["HEADER"]["SUBJECT"])));
   $urlMailbox = urlencode($mailbox);
   $url_replyto = urlencode($message["HEADER"]["REPLYTO"]);

   $dateString = getLongDateString($message["HEADER"]["DATE"]);

   /** FORMAT THE TO STRING **/
   $i = 0;
   $to_string = "";
   $to_ary = $message["HEADER"]["TO"];
   while ($i < count($to_ary)) {
      $to_ary[$i] = htmlspecialchars($to_ary[$i]);
      if ($to_string)
         $to_string = "$to_string<BR>$to_ary[$i]";
      else
         $to_string = "$to_ary[$i]";

      $i++;
      if (count($to_ary) > 1) {
         if ($show_more == false) {
            if ($i == 1) {
               $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=1&show_more_cc=$show_more_cc\">more</A>)";
               $i = count($to_ary);
            }
         } else if ($i == 1) {
            $to_string = "$to_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more=0&show_more_cc=$show_more_cc\">less</A>)";
         }
      }
   }

   /** FORMAT THE CC STRING **/
   $i = 0;
   $cc_string = "";
   $cc_ary = $message["HEADER"]["CC"];
   while ($i < count($cc_ary)) {
      $cc_ary[$i] = htmlspecialchars($cc_ary[$i]);
      if ($cc_string)
         $cc_string = "$cc_string<BR>$cc_ary[$i]";
      else
         $cc_string = "$cc_ary[$i]";

      $i++;
      if (count($cc_ary) > 1) {
         if ($show_more_cc == false) {
            if ($i == 1) {
               $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=1&show_more=$show_more\">more</A>)";
               $i = count($cc_ary);
            }
         } else if ($i == 1) {
            $cc_string = "$cc_string&nbsp;(<A HREF=\"read_body.php?mailbox=$urlMailbox&passed_id=$passed_id&sort=$sort&startMessage=$startMessage&show_more_cc=0&show_more=$show_more\">less</A>)";
         }
      }
   }

   /** make sure everything will display in HTML format **/
   $from_name = htmlspecialchars($message["HEADER"]["FROM"]);
   $subject = htmlspecialchars(stripslashes($message["HEADER"]["SUBJECT"]));

   echo "<BR>";
   echo "<TABLE COLS=1 WIDTH=95% BORDER=0 ALIGN=CENTER CELLPADDING=2>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\" WIDTH=100%>";
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
   echo "               <A HREF=\"compose.php?send_to=$url_replyto&reply_subj=$url_subj&reply_id=$passed_id&mailbox=$urlMailbox\">Reply</A>&nbsp;&nbsp;";
   echo "               </FONT>";
   echo "            </TD>";
   echo "         </TR>";
   echo "      </TABLE>";
   echo "   </TD></TR>";
   echo "   <TR><TD BGCOLOR=\"$color[9]\" WIDTH=100%>";
   echo "   <TABLE COLS=2 WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=3>\n";
   echo "      <TR>\n";
   /** subject **/
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">Subject:</FONT>\n";
   echo "         </TD><TD BGCOLOR=\"$color[4]\" WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$subject</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** from **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">From:</FONT>\n";
   echo "         </TD><TD BGCOLOR=\"$color[4]\" WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$from_name</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** date **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">Date:</FONT>\n";
   echo "         </TD><TD BGCOLOR=\"$color[4]\" WIDTH=85%>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$dateString</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** to **/
   echo "      <TR>\n";
   echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT VALIGN=TOP>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\">To:</FONT>\n";
   echo "         </TD><TD BGCOLOR=\"$color[4]\" WIDTH=85% VALIGN=TOP>\n";
   echo "            <FONT FACE=\"Arial,Helvetica\"><B>$to_string</B></FONT>\n";
   echo "         </TD>\n";
   echo "      </TR>\n";
   /** cc **/
   if ($message["HEADER"]["CC"][0]) {
      echo "      <TR>\n";
      echo "         <TD BGCOLOR=\"$color[0]\" WIDTH=15% ALIGN=RIGHT VALIGN=TOP>\n";
      echo "            <FONT FACE=\"Arial,Helvetica\">Cc:</FONT>\n";
      echo "         </TD><TD BGCOLOR=\"$color[4]\" WIDTH=85% VALIGN=TOP>\n";
      echo "            <FONT FACE=\"Arial,Helvetica\"><B>$cc_string</B></FONT>\n";
      echo "         </TD>\n";
      echo "      </TR>\n";
   }
   echo "   </TABLE></TD></TR>\n";

   echo "   <TR><TD BGCOLOR=\"$color[4]\" WIDTH=100%>\n";
   $body = formatBody($message);

   for ($i = 0; $i < count($body); $i++) {
      echo "$body[$i]";
   }

   echo "   <BR></TD></TR>\n";
   echo "   <TR><TD BGCOLOR=\"$color[0]\">&nbsp;</TD></TR>";
   echo "</TABLE>\n";

?>