<?php

   /**
    **  display_messages.php
    **
    **  Copyright (c) 1999-2001 The Squirrelmail Development Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This contains all messages, including information, error, and just
    **  about any other message you can think of.
    **
    ** $Id$
    **/

    function error_username_password_incorrect($color) {

        echo '<BR>'.
                "<TABLE COLS=1 WIDTH=75% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
                '<TR>'.
                    "<TD BGCOLOR=\"$color[0]\">".
                        '<B><CENTER>ERROR</CENTER></B>'.
                    '</TD></TR><TR><TD>'.
                        '<CENTER><BR>' . _("Unknown user or password incorrect.") .
                        '<BR><A HREF="login.php" TARGET=_top>' .
                        _("Click here to try again") .
                        '</A>.</CENTER>'.
                    '</TD></TR>'.
                '</TABLE>'.
            '</BODY></HTML>';

    }

    function general_info($motd, $org_logo, $version, $org_name, $color) {
      echo '<BR>';
      echo "<TABLE COLS=1 WIDTH=80% CELLSPACING=0 CELLPADDING=2 NOBORDER ALIGN=CENTER><TR><TD BGCOLOR=\"$color[9]\">";
      echo '<TABLE COLS=1 WIDTH=100% CELLSPACING=0 CELLPADDING=3 NOBORDER BGCOLOR="#FFFFFF" ALIGN=CENTER>';
      echo '   <TR>';
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo '         <B><CENTER>';
      printf (_("Welcome to %s's WebMail system"), $org_name);
      echo '         </CENTER></B>';
      echo '   <TR><TD BGCOLOR="#FFFFFF">';
      echo '      <TABLE COLS=2 WIDTH=90% CELLSPACING=0 CELLPADDING=3 NOBORDER align="center">';
      echo '         <TR>';
      echo '            <TD BGCOLOR="#FFFFFF"><CENTER>';
      if (strlen($org_logo) > 3)
         echo "               <IMG SRC=\"$org_logo\">";
      else
         echo "               <B>$org_name</B>";
      echo '         <BR><CENTER>';
      printf (_("Running SquirrelMail version %s (c) 1999-2000."), $version);
      echo '            </CENTER><BR>';
      echo '            </CENTER></TD></TR><TR>';
      echo '            <TD BGCOLOR="#FFFFFF">';
      echo "               $motd";
      echo '            </TD>';
      echo '         </TR>';
      echo '      </TABLE>';
      echo '   </TD></TR>';
      echo '</TABLE>';
      echo '</TD></TR></TABLE>';
   }

    function error_message($message, $mailbox, $sort, $startMessage, $color) {
      $urlMailbox = urlencode($mailbox);

      echo '<BR>';
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo '   <TR>';
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . '</CENTER></B></FONT>';
      echo '   </TD></TR><TR><TD>';
      echo "      <CENTER><BR>$message<BR>\n";
      echo '      <BR>';
      echo "         <A HREF=\"right_main.php?sort=$sort&startMessage=$startMessage&mailbox=$urlMailbox\">";
      printf (_("Click here to return to %s"), $mailbox);
      echo '</A>.';
      echo '   </TD></TR>';
      echo '</TABLE>';
    }

    function plain_error_message($message, $color) {
      echo '<BR>';
      echo "<TABLE COLS=1 WIDTH=70% NOBORDER BGCOLOR=\"$color[4]\" ALIGN=CENTER>";
      echo '   <TR>';
      echo "      <TD BGCOLOR=\"$color[0]\">";
      echo "         <FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . '</CENTER></B></FONT>';
      echo '   </TD></TR><TR><TD>';
      echo "      <CENTER><BR>$message";
      echo '      </CENTER>';
      echo '   </TD></TR>';
      echo '</TABLE>';
    }
?>