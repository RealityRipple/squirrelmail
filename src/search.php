<?php

/**
 * right_main.php
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

function s_opt( $val, $sel, $tit ) {
    echo "            <option value=\"$val\"";
    if ( $sel ) {
        echo ' selected';
    }
    echo  ">$tit</option>\n";
}

displayPageHeader($color, $mailbox);
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

do_hook('search_before_form');
echo "<br>\n".
    "      <table width=\"95%\" align=center cellpadding=2 cellspacing=0 border=0>\n".
    "      <tr><td bgcolor=\"$color[0]\">\n".
    "          <center><b>"._("Search")."</b></center>\n".
    "      </td></tr>\n".
    '      <tr><td align=center>'.

    "<FORM ACTION=\"$PHP_SELF\" NAME=s>\n".
    "   <TABLE WIDTH=\"75%\">\n".
    "     <TR>\n".
    "       <TD WIDTH=\"33%\">\n".
    '         <TT><SMALL><SELECT NAME="mailbox">';

$boxes = sqimap_mailbox_list($imapConnection);
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
echo '         </SELECT></SMALL></TT>'.
     "       </TD>\n".
     "        <TD ALIGN=\"CENTER\" WIDTH=\"33%\">\n";
if (!isset($what)) {
   $what = '';
}
$what_disp = ereg_replace(',', ' ', $what);
$what_disp = str_replace('\\\\', '\\', $what_disp);
$what_disp = str_replace('\\"', '"', $what_disp);
$what_disp = str_replace('"', '&quot;', $what_disp);
echo "          <INPUT TYPE=\"TEXT\" SIZE=\"20\" NAME=\"what\" VALUE=\"$what_disp\">\n".
            '</TD>'.
           "<TD ALIGN=\"RIGHT\" WIDTH=\"33%\">\n".
             '<SELECT NAME="where">';

s_opt( 'BODY', ($where == 'BODY'), _("Body") );
s_opt( 'TEXT', ($where == 'TEXT'), _("Everywhere") );
s_opt( 'SUBJECT', ($where == 'SUBJECT'), _("Subject") );
s_opt( 'FROM', ($where == 'FROM'), _("From") );
s_opt( 'CC', ($where == 'CC'), _("Cc") );
s_opt( 'TO', ($where == 'TO'), _("To") );

echo "         </SELECT>\n" .
     "        </TD>\n".
     "       <TD COLSPAN=\"3\" ALIGN=\"CENTER\">\n".
     "         <INPUT TYPE=\"submit\" VALUE=\""._("Search")."\">\n".
     "       </TD>\n".
     "     </TR>\n".
     "   </TABLE>\n".
     "</FORM>".
     "</td></tr></table>";
do_hook("search_after_form");
if (isset($where) && $where && isset($what) && $what) {
    sqimap_mailbox_select($imapConnection, $mailbox);
    sqimap_search($imapConnection, $where, $what, $mailbox, $color);
}
do_hook("search_bottom");
sqimap_logout ($imapConnection);

echo '</body></html>';

?>
