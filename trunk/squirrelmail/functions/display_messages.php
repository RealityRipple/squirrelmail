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

function sqm_baseuri(){
    global $base_uri, $PHP_SELF;
    if (isset($base_uri)){
        return $base_uri;
    }
    $dirs = array("|src/.*|", "|plugins/.*|", "|functions/.*|");
    $repl = array("", "", "");
    $base_uri = preg_replace($dirs, $repl, $PHP_SELF);
    return $base_uri;
}

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
                    '<BR><A HREF="' . sqm_baseuri() . '"login.php" TARGET='.
                    $frame_top.'>' . _("Click here to try again") .
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
                  "<A HREF=\"" . sqm_baseuri() 
                  . "right_main.php?sort=$sort&amp;startMessage=$startMessage"
                  . "&amp;mailbox=$urlMailbox\">";
    printf (_("Click here to return to %s"), $mailbox);
    echo '</A>.'.
            '</TD></TR>'.
         '</TABLE>';
}

function plain_error_message($message, $color) {
    echo "<br><TABLE COLS=1 WIDTH=\"70%\" BORDER=\"0\" BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
            '<TR>'.
               "<TD BGCOLOR=\"$color[0]\">".
                  "<FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") . '</CENTER></B></FONT>'.
            '</TD></TR><TR><TD>'.
               "<CENTER><BR>$message".
               '</CENTER>'.
            '</TD></TR>'.
         '</TABLE>';
}

function logout_error( $errString, $errTitle = '' ) {

    GLOBAL $frame_top, $org_logo, $org_name, $org_logo_width, $org_logo_height,
           $hide_sm_attributions, $version;
    $base_uri = sqm_baseuri();
    include_once($base_uri . 'functions/page_header.php' );
    if ( !isset( $org_logo ) ) {
        // Don't know yet why, but in some accesses $org_logo is not set.
        include( $base_uri . '../config/config.php' );
    }
    /* Display width and height like good little people */
    $width_and_height = '';
    if (isset($org_logo_width) && is_int($org_logo_width) && $org_logo_width>0) {
        $width_and_height = " WIDTH=\"$org_logo_width\"";
    }
    if (isset($org_logo_height) && is_int($org_logo_height) && $org_logo_height>0) {
        $width_and_height .= " HEIGHT=\"$org_logo_height\"";
    }

    if (!isset($frame_top) || $frame_top == '' ) {
        $frame_top = '_top';
    }

    if ( !isset( $color ) ) {
        $color = array();
        $color[0]  = '#DCDCDC';  /* light gray    TitleBar               */
        $color[1]  = '#800000';  /* red                                  */
        $color[2]  = '#CC0000';  /* light red     Warning/Error Messages */
        $color[3]  = '#A0B8C8';  /* green-blue    Left Bar Background    */
        $color[4]  = '#FFFFFF';  /* white         Normal Background      */
        $color[5]  = '#FFFFCC';  /* light yellow  Table Headers          */
        $color[6]  = '#000000';  /* black         Text on left bar       */
        $color[7]  = '#0000CC';  /* blue          Links                  */
        $color[8]  = '#000000';  /* black         Normal text            */
        $color[9]  = '#ABABAB';  /* mid-gray      Darker version of #0   */
        $color[10] = '#666666';  /* dark gray     Darker version of #9   */
        $color[11] = '#770000';  /* dark red      Special Folders color  */
        $color[12] = '#EDEDED';
        $color[15] = '#002266';  /* (dark blue)      Unselectable folders */
    }

    if ( $errTitle == '' ) {
        $errTitle = $errString;
    }
    set_up_language($squirrelmail_language, true);
    displayHtmlHeader( $errTitle );
    
    echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n\n" .
         '<CENTER>'.
         "<IMG SRC=\"$org_logo\" ALT=\"" . sprintf(_("%s Logo"), $org_name) .
            "\"$width_and_height><BR>\n".
         ( $hide_sm_attributions ? '' :
           '<SMALL>' . sprintf (_("SquirrelMail version %s"), $version) . "<BR>\n".
           '  ' . _("By the SquirrelMail Development Team") . "<BR></SMALL>\n" ) .
         "<table cellspacing=1 cellpadding=0 bgcolor=\"$color[1]\" width=\"70%\"><tr><td>".
         "<TABLE COLS=1 WIDTH=\"100%\" BORDER=\"0\" BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
            "<TR><TD BGCOLOR=\"$color[0]\">".
                  "<FONT COLOR=\"$color[2]\"><B><CENTER>" . _("ERROR") .
                  '</CENTER></B></FONT></TD></TR>'.
            '<TR><TD><CENTER>' . $errString . '</CENTER></TD></TR>'.
            "<TR><TD BGCOLOR=\"$color[0]\">".
                  "<FONT COLOR=\"$color[2]\"><B><CENTER>".
                  '<a href="' . $base_uri . '" target="' . $frame_top . '">' .
                  _("Go to the login page") . "</a></CENTER></B></FONT>".
            '</TD></TR>'.
         '</TABLE></td></tr></table></body></html>';
}

?>