<?php

/**
 * search.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/imap_search.php');
require_once('../functions/array.php');
require_once('../functions/strings.php');

function s_opt( $val, $sel, $tit ) {
    echo "            <option value=\"$val\"";
    if ( $sel == $val ) {
        echo ' selected';
    }
    echo  ">$tit</option>\n";
}

/* ------------------------ main ------------------------ */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$boxes = sqimap_mailbox_list($imapConnection);

if( $mailbox == 'None' ) {
    $mailbox = $boxes[0]['unformatted'];
}

displayPageHeader($color, $mailbox);

if( !isset( $search_memory ) ) {
    $search_memory = 0;
}

do_hook('search_before_form');
echo "<br>\n".
     "      <table width=\"100%\" align=center cellpadding=2 cellspacing=0 border=0>\n".
     "      <tr><td bgcolor=\"$color[0]\">\n".
     "          <center><b>"._("Search")."</b></center>\n".
     "      </td></tr>\n".
     '      <tr><td align=center>';

if( $search_memory > 0 ) {

    if( isset( $pos) ) {
        setpref( $data_dir, $username, 'search_pos', $pos );
    } else {
        $pos = getPref($data_dir, $username, 'search_pos', 0 );
    }

}

for ( $form = 0; $form <= $search_memory; $form++ ) {

    $frm = "$form";
    $what = "what$frm";
    $where = "where$frm";
    
    if( $search_memory > 0 ) {
        if ( $$what == '' ) {
            $$what = getPref($data_dir, $username, "search_what$frm", '' );
            $$where = getPref($data_dir, $username, "search_where$frm", '' );
        } else {
            setpref( $data_dir, $username, "search_what$frm", $$what );
            setpref( $data_dir, $username, "search_where$frm", $$where );
        }
    }
    echo "   <TABLE WIDTH=\"75%\" cellpadding=0 cellspacing=0>\n";
    if( !($form == 0 && $search_memory > 0) ) {

        if( $form == $pos ) {
            $act = $color[5];
        } else {
            $act = $color[4];
        }

        echo "<FORM ACTION=\"$PHP_SELF\" NAME=s>\n".
             "<input type=hidden name=pos value=\"$frm\">".
             "     <TR bgcolor=$act><td width=10></td>\n".
             '       <TD><SELECT NAME="mailbox">';
        
        for ($i = 0; $i < count($boxes); $i++) {
            if (!in_array('noselect', $boxes[$i]['flags'])) {
                $box = $boxes[$i]['unformatted'];
                $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
                if ($mailbox == $box) {
                    echo "         <OPTION VALUE=\"$box\" SELECTED>$box2</OPTION>\n";
                } else {
                    echo "         <OPTION VALUE=\"$box\">$box2</OPTION>\n";
                }   
            }
        }
        echo '         </SELECT>'.
             "       </TD>\n".
             "        <TD ALIGN=\"CENTER\">\n";
        if (!isset($$what)) {
           $$what = '';
        }
        $what_disp = str_replace(',', ' ', $$what);
        $what_disp = str_replace('\\\\', '\\', $what_disp);
        $what_disp = str_replace('\\"', '"', $what_disp);
        $what_disp = str_replace('"', '&quot;', $what_disp);
        echo "          <INPUT TYPE=\"TEXT\" SIZE=\"20\" NAME=\"what$frm\" VALUE=\"$what_disp\">\n".
                    '</TD>'.
                   "<TD ALIGN=\"RIGHT\">\n".
                     "<SELECT NAME=\"where$frm\">";
        
        s_opt( 'BODY', $$where, _("Body") );
        s_opt( 'TEXT', $$where, _("Everywhere") );
        s_opt( 'SUBJECT', $$where, _("Subject") );
        s_opt( 'FROM', $$where, _("From") );
        s_opt( 'CC', $$where, _("Cc") );
        s_opt( 'TO', $$where, _("To") );
        
        echo "         </SELECT>\n" .
             "        </TD>\n".
             "       <TD COLSPAN=\"3\" ALIGN=\"CENTER\">\n".
             "         <INPUT TYPE=\"submit\" VALUE=\""._("Search")."\">\n".
             "       </TD>\n".
             "     </TR>\n".
             '</FORM>';
    }         
    echo "   </TABLE>\n";
}     

echo "</td></tr></table>";
do_hook("search_after_form");
if( !isset( $pos ) ) {
    $pos = $frm;
}
$what = "what$pos";
$where = "where$pos";

if (isset($$where) && $$where && isset($$what) && $$what) {
    sqimap_mailbox_select($imapConnection, $mailbox);
    sqimap_search($imapConnection, $$where, $$what, $mailbox, $color, $pos);
}
do_hook("search_bottom");
sqimap_logout ($imapConnection);

echo '</body></html>';

?>
