<?
   /**
    **  display_messages.php
    **
    **  This contains all messages, including information, error, and just
    **  about any other message you can think of.
    **
    **/

    function error_username_password_incorrect() {
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><B><CENTER>ERROR</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><FONT FACE=\"Arial,Helvetica\"><BR>Unknown user or password incorrect.<BR><A HREF=\"login.php\" TARGET=_top>Click here to try again</A>.</FONT></CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
      echo "</BODY></HTML>";
    }

    function general_info($motd, $org_logo, $version, $org_name) {
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><B><CENTER>Welcome to $org_name's WebMail system</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "   <TR><TD BGCOLOR=FFFFFF>";
      echo "         <FONT FACE=\"Arial,Helvetica\" SIZE=-1><CENTER>Running SquirrelMail version $version (c) 1999 by Nathan and Luke Ehresman.</CENTER></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <TABLE COLS=2 WIDTH=75% NOBORDER align=\"center\">";
      echo "         <TR>";
      echo "            <TD BGCOLOR=FFFFFF><CENTER>";
      if (strlen($org_logo) > 3)
         echo "               <IMG SRC=\"$org_logo\">";
      else
         echo "               <B><FONT FACE=\"Arial,Helvetica\">$org_name</FONT></B>";
      echo "            </CENTER></TD></TR><TR>";
      echo "            <TD BGCOLOR=FFFFFF>";
      echo "               <FONT FACE=\"Arial,Helvetica\">$motd</FONT>";
      echo "            </TD>";
      echo "         </TR>";
      echo "      </TABLE>";
      echo "   </TD></TR>";
      echo "</TABLE>";
   }

    function messages_deleted_message($mailbox, $sort, $startMessage) {
      $urlMailbox = urlencode($mailbox);
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><B><CENTER>Messages Deleted</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><FONT FACE=\"Arial,Helvetica\"><BR>The selected messages were deleted successfully.<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              Click here to return to $mailbox</A>.";
      echo "      </FONT></CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }

    function messages_moved_message($mailbox, $sort, $startMessage) {
      $urlMailbox = urlencode($mailbox);
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\"><B><CENTER>Messages Moved</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><FONT FACE=\"Arial,Helvetica\"><BR>The selected messages were moved successfully.<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              Click here to return to $mailbox</A>.";
      echo "      </FONT></CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }

    function error_message($message, $mailbox, $sort, $startMessage) {
      $urlMailbox = urlencode($mailbox);
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=FFFFFF ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=DCDCDC>";
      echo "         <FONT FACE=\"Arial,Helvetica\" COLOR=CC0000><B><CENTER>ERROR</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><FONT FACE=\"Arial,Helvetica\"><BR>$message<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              Click here to return to $mailbox</A>.";
      echo "      </FONT></CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }
?>
