<?
   /**
    **  display_messages.php
    **
    **  This contains all messages, including information, error, and just
    **  about any other message you can think of.
    **
    **/

    $display_messages_php = true;

    function error_username_password_incorrect($color) {
      global $PHPSESSID;

      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=75% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <B><CENTER>ERROR</CENTER></B>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><BR>". _("Unknown user or password incorrect.") ."<BR><A HREF=\"login.php?PHPSESSID=$PHPSESSID\" TARGET=_top>". _("Click here to try again") ."</A>.</CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
      echo "</BODY></HTML>";
    }

    function general_info($motd, $org_logo, $version, $org_name, $color) {
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=80% CELLSPACING=0 CELLPADDING=2 NOBORDER ALIGN=CENTER><TR><TD BGCOLOR=\"$color[9]\">";
      echo "<TABLE COLS=1 WIDTH=100% CELLSPACING=0 CELLPADDING=3 NOBORDER BGCOLOR=\"#FFFFFF\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <B><CENTER>". _("Welcome to $org_name's WebMail system") ."</CENTER></B>";
      echo "   <TR><TD BGCOLOR=\"#FFFFFF\">";
      echo "      <TABLE COLS=2 WIDTH=90% CELLSPACING=0 CELLPADDING=3 NOBORDER align=\"center\">";
      echo "         <TR>";
      echo "            <TD BGCOLOR=\"#FFFFFF\"><CENTER>";
      if (strlen($org_logo) > 3)
         echo "               <IMG SRC=\"$org_logo\">";
      else
         echo "               <B>$org_name</B>";
      echo "         <BR><CENTER>". _("Running SquirrelMail version $version (c) 1999-2000.") ."</CENTER><BR>";
      echo "            </CENTER></TD></TR><TR>";
      echo "            <TD BGCOLOR=\"#FFFFFF\">";
      echo "               $motd";
      echo "            </TD>";
      echo "         </TR>";
      echo "      </TABLE>";
      echo "   </TD></TR>";
      echo "</TABLE>";
      echo "</TD></TR></TABLE>";
   }

    function messages_deleted_message($mailbox, $sort, $startMessage, $color) {
      global $PHPSESSID;
      $urlMailbox = urlencode($mailbox);

      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <B><CENTER>". _("Messages Deleted") ."</CENTER></B>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><BR>". _("The selected messages were deleted successfully.") ."<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?PHPSESSID=$PHPSESSID&right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              ". _("Click here to return to ") ."$mailbox</A>.";
      echo "      </CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }

    function messages_moved_message($mailbox, $sort, $startMessage, $color) {
      global $PHPSESSID;
      $urlMailbox = urlencode($mailbox);

      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <B><CENTER>". _("Messages Moved") ."</CENTER></B>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><BR>". _("The selected messages were moved successfully.") ."<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?PHPSESSID=$PHPSESSID&right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              ". _("Click here to return to ") ."$mailbox</A>.";
      echo "      </CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }

    function error_message($message, $mailbox, $sort, $startMessage, $color) {
      global $PHPSESSID;
      $urlMailbox = urlencode($mailbox);

      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <FONT COLOR=\"$color[2]\"><B><CENTER>". _("ERROR") ."</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><BR>$message<BR>\n";
      echo "      <BR>";
      echo "              <A HREF=\"webmail.php?PHPSESSID=$PHPSESSID&right_frame=right_main.php&sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\" TARGET=_top>";
      echo "              ". _("Click here to return to ") ."$mailbox</A>.";
      echo "      </CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }

    function plain_error_message($message, $color) {
      echo "<BR>";
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo "   <TR>";
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . "</CENTER></B></FONT>";
      echo "   </TD></TR><TR><TD>";
      echo "      <CENTER><BR>$message";
      echo "      </CENTER>";
      echo "   </TD></TR>";
      echo "</TABLE>";
    }
?>
