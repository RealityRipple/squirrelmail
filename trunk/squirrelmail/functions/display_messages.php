<?php

/**
 * display_messages.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains all messages, including information, error, and just
 * about any other message you can think of.
 *
 * $Id$
 */

function error_username_password_incorrect() {
    global $frame_top, $color;
    /* XXX: Should really not start the HTML before this, or close off more
       cleanly. */

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    echo '<BR>'.
                '<TABLE COLS=1 WIDTH="75%" BORDER="0" BGCOLOR="' . $color[4] . '" ALIGN=CENTER>'.
                '<TR BGCOLOR="' . $color[0] . '">'.
                    '<TH>' . _("ERROR") . '</TH>'.
                '</TR>' .
                '<TR><TD>'.
                    '<CENTER><BR>' . _("Unknown user or password incorrect.") .
                    '<BR><A HREF="login.php" TARGET='.$frame_top.'>' .
                    _("Click here to try again") .
                    '</A>.</CENTER>'.
                '</TD></TR>'.
                '</TABLE>'.
            '</BODY></HTML>';
}

function general_info($motd, $org_logo, $version, $org_name, $color) {

    echo '<BR>'.
         "<TABLE COLS=1 WIDTH=\"80%\" CELLSPACING=0 CELLPADDING=2 BORDER=\"0\" ALIGN=CENTER><TR><TD BGCOLOR=\"$color[9]\">".
         '<TABLE COLS=1 WIDTH="100%" CELLSPACING=0 CELLPADDING=3 BORDER="0" BGCOLOR="' .  $color[4] . '" ALIGN=CENTER>'.
            '<TR>' .
               "<TD BGCOLOR=\"$color[0]\">" .
                  '<B><CENTER>';
    printf (_("Welcome to %s's WebMail system"), $org_name);
    echo          '</CENTER></B>'.
            '<TR><TD BGCOLOR="' . $color[4] .  '">'.
               '<TABLE COLS=2 WIDTH="90%" CELLSPACING=0 CELLPADDING=3 BORDER="0" align="center">'.
                  '<TR>'.
                     '<TD BGCOLOR="' . $color[4] .  '"><CENTER>';
    if ( strlen($org_logo) > 3 ) {
        echo                "<IMG SRC=\"$org_logo\">";
    } else {
        echo                "<B>$org_name</B>";
    }
    echo          '<BR><CENTER>';
    printf (_("Running SquirrelMail version %s (c) 1999-2001."), $version);
    echo             '</CENTER><BR>'.
                     '</CENTER></TD></TR><TR>' .
                     '<TD BGCOLOR="' . $color[4] .  '">' .
                         $motd.
                     '</TD>'.
                  '</TR>'.
               '</TABLE>'.
            '</TD></TR>'.
         '</TABLE>'.
         '</TD></TR></TABLE>';
}

function error_message($message, $mailbox, $sort, $startMessage, $color) {
    $urlMailbox = urlencode($mailbox);

    echo '<BR>'.
         "<TABLE COLS=1 WIDTH=\"70%\" BORDER=\"0\" BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
            '<TR>'.
               "<TD BGCOLOR=\"$color[0]\">".
                  "<FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . '</CENTER></B></FONT>'.
            '</TD></TR><TR><TD>'.
               "<CENTER><BR>$message<BR>\n".
               '<BR>'.
                  "<A HREF=\"right_main.php?sort=$sort&amp;startMessage=$startMessage&amp;mailbox=$urlMailbox\">";
    printf (_("Click here to return to %s"), $mailbox);
    echo '</A>.'.
            '</TD></TR>'.
         '</TABLE>';
}

function plain_error_message($message, $color) {
    echo '<BR>'.
         "<TABLE COLS=1 WIDTH=\"70%\" BORDER=\"0\" BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
            '<TR>'.
               "<TD BGCOLOR=\"$color[0]\">".
                  "<FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . '</CENTER></B></FONT>'.
            '</TD></TR><TR><TD>'.
               "<CENTER><BR>$message".
               '</CENTER>'.
            '</TD></TR>'.
         '</TABLE>';
}

?>
