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

/**
 * Find out where squirrelmail lives and try to be smart about it.
 * The only problem would be when squirrelmail lives in directories
 * called "src", "functions", or "plugins", but people who do that need
 * to be beaten with a steel pipe anyway.
 *
 * @return  the base uri of squirrelmail installation.
 */
function sqm_baseuri(){
    global $base_uri, $PHP_SELF;
    /**
     * If it is in the session, just return it.
     */
    if (isset($base_uri)){
        return $base_uri;
    }
    $dirs = array('|src/.*|', '|plugins/.*|', '|functions/.*|');
    $repl = array('', '', '');
    $base_uri = preg_replace($dirs, $repl, $PHP_SELF);
    return $base_uri;
}

/**
 * Find out the top REAL path of the squirrelmail installation.
 *
 * @return  the real installation directory of squirrelmail.
 */

function sqm_topdir(){
    $topdir = '';
    /**
     * $levels is just to avoid a potential infinite loop in case
     * things are REALLY broken. Shouldn't really ever happen.
     */
    $levels = 0;
    while (!(is_dir("$topdir/functions") && is_dir("$topdir/src"))
           && $levels < 10){
        $topdir .= '../';
        $levels++;
    }
    return $topdir;
}

function error_username_password_incorrect() {
    global $frame_top, $color;
    /* XXX: Should really not start the HTML before this, or close off more
       cleanly. */

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }
    $string = '<TR><TD ALIGN="center">'.
                 _("Unknown user or password incorrect.") .
              '</TD></TR><TR><TD ALIGN="center">'.
	         '<A HREF="' . sqm_baseuri() . '"login.php" TARGET='.
                    $frame_top.'>' . _("Click here to try again") .
                    '</A>.'.
              '</TD></TR>';
   error_box($string,$color);
echo  '</BODY></HTML>';
}

function error_message($message, $mailbox, $sort, $startMessage, $color) {
    $urlMailbox = urlencode($mailbox);

    $string = '<tr><td ALIGN="center">' . $message . '</td></tr>'."\n".
               '<tr><td ALIGN="center">'.
                  '<A HREF="' . sqm_baseuri() 
                  . "src/right_main.php?sort=$sort&amp;startMessage=$startMessage"
                  . "&amp;mailbox=$urlMailbox\">" .
	    sprintf (_("Click here to return to %s"), $mailbox) .
	    '</A></td></tr>';
    error_box($string, $color);
}

function plain_error_message($message, $color) {
    error_box($message, $color);
}

function logout_error( $errString, $errTitle = '' ) {
    global $frame_top, $org_logo, $org_name, $org_logo_width, $org_logo_height,
           $hide_sm_attributions, $version, $squirrelmail_language;

    $base_uri = sqm_baseuri();
    $topdir = sqm_topdir();
    include_once( "$topdir/functions/page_header.php" );
    if ( !isset( $org_logo ) ) {
        // Don't know yet why, but in some accesses $org_logo is not set.
        include( "$topdir/config/config.php" );
    }
    /* Display width and height like good little people */
    $width_and_height = '';
    if (isset($org_logo_width) && is_numeric($org_logo_width) && $org_logo_width>0) {
        $width_and_height = " WIDTH=\"$org_logo_width\"";
    }
    if (isset($org_logo_height) && is_numeric($org_logo_height) && $org_logo_height>0) {
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

    displayHtmlHeader( $errTitle, '', false );

    echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n\n" .
         '<CENTER>'.
         "<IMG SRC=\"$org_logo\" ALT=\"" . sprintf(_("%s Logo"), $org_name) .
            "\"$width_and_height><BR>\n".
         ( $hide_sm_attributions ? '' :
           '<SMALL>' . sprintf (_("SquirrelMail version %s"), $version) . "<BR>\n".
           '  ' . _("By the SquirrelMail Development Team") . "<BR></SMALL>\n" ) .
         "<table cellspacing=1 cellpadding=0 bgcolor=\"$color[1]\" width=\"70%\"><tr><td>".
         "<TABLE WIDTH=\"100%\" BORDER=\"0\" BGCOLOR=\"$color[4]\" ALIGN=CENTER>".
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=\"center\">".
                  "<FONT COLOR=\"$color[2]\"><B>" . _("ERROR") .
                  '</B></FONT></TD></TR>'.
            '<TR><TD ALIGN="center">' . $errString . '</TD></TR>'.
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=\"center\">".
                  "<FONT COLOR=\"$color[2]\"><B>".
                  '<a href="' . $base_uri . 'src/login.php" target="' .
                  $frame_top . '">' .
                  _("Go to the login page") . "</a></B></FONT>".
            '</TD></TR>'.
            '</TABLE></td></tr></table></center></body></html>';
}

function error_box($string, $color) {
   echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center"'.' border="0" bgcolor="'.$color[9].'">';
   echo '     <tr><td>';
   echo '       <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0" bgcolor="'.$color[4].'">';
   echo '        <tr><td ALIGN="center" bgcolor="'.$color[0].'">';
   echo '           <font color="' . $color[2].'"><b>' . _("ERROR") . ':</b></font>';
   echo '        </td></tr>';
   echo '        <tr><td>';
   echo '            <table cellpadding="1" cellspacing="5" align="center" border="0">';
   echo '              <tr>' . html_tag( 'td', $string."\n", 'left')
                    . '</tr>';
   echo '            </table>';
   echo '       </table></td></tr>';
   echo '    </table>';
   echo '  </td></tr>';
}
?>
