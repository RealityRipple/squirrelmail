<?php

/**
 * addrbook_search_html.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handle addressbook searching with pure html.
 *
 * This file is included from compose.php
 *
 * NOTE: A lot of this code is similar to the code in
 *       addrbook_search.html -- If you change one, change
 *       the other one too!
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/date.php');
require_once('../functions/smtp.php');
require_once('../functions/display_messages.php');
require_once('../functions/addressbook.php');
require_once('../functions/plugin.php');
require_once('../functions/strings.php');

/* Insert hidden data */
function addr_insert_hidden() {
    global $body, $subject, $send_to, $send_to_cc, $send_to_bcc, $mailbox,
           $identity;

   echo '<input type=hidden value="';
   if (substr($body, 0, 1) == "\r") {
       echo "\n";
   }
   echo htmlspecialchars($body) . '" name=body>' . "\n" .
        '<input type=hidden value="' . htmlspecialchars($subject) .
        '" name=subject>' . "\n" .
        '<input type=hidden value="' . htmlspecialchars($send_to) .
        '" name=send_to>' . "\n" .
        '<input type=hidden value="' . htmlspecialchars($send_to_cc) .
        '" name=send_to_cc>' . "\n" .
        '<input type=hidden value="' . htmlspecialchars($send_to_bcc) .
        '" name=send_to_bcc>' . "\n" .
        '<input type=hidden value="' . htmlspecialchars($identity) .
        '" name=identity>' . "\n" .
        '<input type=hidden name=mailbox value="' . htmlspecialchars($mailbox) .
        "\">\n" . '<input type=hidden value="true" name=from_htmladdr_search>' .
        "\n";
   }


/* List search results */
function addr_display_result($res, $includesource = true) {
    global $color, $javascript_on, $PHP_SELF;

    if (sizeof($res) <= 0) return;

    echo '<form method=post action="' . $PHP_SELF . '" name="addrbook">'."\n" .
         '<input type=hidden name="html_addr_search_done" value="true">' . "\n";
    addr_insert_hidden();
    $line = 0;

if ($javascript_on) {
    print
        '<script language="JavaScript" type="text/javascript">' .
        "\n<!-- \n" .
        "function CheckAll(ch) {\n" .
        "   for (var i = 0; i < document.addrbook.elements.length; i++) {\n" .
        "       if( document.addrbook.elements[i].type == 'checkbox' &&\n" .
        "           document.addrbook.elements[i].name.substr(0,16) == 'send_to_search['+ch ) {\n" .
        "           document.addrbook.elements[i].checked = !(document.addrbook.elements[i].checked);\n".
        "       }\n" .
        "   }\n" .
        "}\n" .
        "//-->\n" .
        "</script>\n";
    $chk_all = '<a href="#" onClick="CheckAll(\'T\');">' . _("All") . '</a>&nbsp;<font color="'.$color[9].'">To</font>'.
            '&nbsp;&nbsp;'.
            '<a href="#" onClick="CheckAll(\'C\');">' . _("All") . '</a>&nbsp;<font color="'.$color[9].'">Cc</font>'.
            '&nbsp;&nbsp;'.
            '<a href="#" onClick="CheckAll(\'B\');">' . _("All") . '</a>';
    }
    echo '<TABLE BORDER="0" WIDTH="98%" ALIGN="center">' .
           '<TR BGCOLOR="' . $color[9] . '"><TH ALIGN="left">&nbsp;' . $chk_all . '</TH>' .
          '<TH ALIGN="left">&nbsp;' . _("Name") . '</TH>' .
          '<TH ALIGN="left">&nbsp;' . _("E-mail") . '</TH>' .
          '<TH ALIGN="left">&nbsp;' . _("Info") . '</TH>';



    if ($includesource) {
        echo '<TH ALIGN=left WIDTH="10%">&nbsp;' . _("Source"). '</TH>';
    }

    echo "</TR>\n";

    foreach ($res as $row) {
        echo '<tr';
        if ($line % 2) { echo ' bgcolor="' . $color[0] . '"'; }
        echo ' nowrap><td nowrap align=center width="5%">' .
             '<input type=checkbox name="send_to_search[T' . $line . ']" value = "' .
             htmlspecialchars($row['email']) . '">&nbsp;' . _("To") . '&nbsp;' .
             '<input type=checkbox name="send_to_search[C' . $line . ']" value = "' .
             htmlspecialchars($row['email']) . '">&nbsp;' . _("Cc") . '&nbsp;' .
             '<input type=checkbox name="send_to_search[B' . $line . ']" value = "' .
             htmlspecialchars($row['email']) . '">&nbsp;' . _("Bcc") . '&nbsp;' . 
             '</td><td nowrap>&nbsp;' . $row['name'] . '&nbsp;</td>' .
             '<td nowrap>&nbsp;' . $row['email'] . '&nbsp;</td>' .
             '<td nowrap>&nbsp;' . $row['label'] . '&nbsp;</td>';
         if ($includesource) {
             echo '<td nowrap>&nbsp;' . $row['source'] . '&nbsp;</td>';
         }
         echo "</tr>\n";
         $line ++;
    }
    echo '<TR><TD ALIGN=center COLSPAN=';
    if ($includesource) { echo '5'; } else { echo '4'; }
    echo '><INPUT TYPE=submit NAME="addr_search_done" VALUE="' .
         _("Use Addresses") . '"></TD></TR>' .
         '</TABLE>' .
         '<INPUT TYPE=hidden VALUE=1 NAME="html_addr_search_done">' .
         '</FORM>';
}

/* --- End functions --- */

global $mailbox;
if ($compose_new_win == '1') {
    compose_Header($color, $mailbox);
}
else {
    displayPageHeader($color, $mailbox);
}
/* Initialize addressbook */
$abook = addressbook_init();

?>

<br>
<table width="95%" align=center cellpadding=2 cellspacing=2 border=0>
<tr><td bgcolor="<?php echo $color[0] ?>">
   <center><b><?php echo _("Address Book Search") ?></b></center>
</td></tr></table>

<?php

/* Search form */
echo "<CENTER>\n<TABLE BORDER=0><TR><TD NOWRAP VALIGN=middle>\n" .
     '<FORM METHOD=post NAME=f ACTION="' . $PHP_SELF .
     '?html_addr_search=true">' . "\n<CENTER>\n" .
     '  <nobr><STRONG>' . _("Search for") . "</STRONG>\n";
addr_insert_hidden();
if (! isset($addrquery))
    $addrquery = '';
echo '  <INPUT TYPE=text NAME=addrquery VALUE="' .
     htmlspecialchars($addrquery) . "\" SIZE=26>\n";

/* List all backends to allow the user to choose where to search */
if (!isset($backend)) { $backend = ''; }
if ($abook->numbackends > 1) {
    echo '<STRONG>' . _("in") . '</STRONG>&nbsp;<SELECT NAME=backend>' . "\n" .
         '<OPTION VALUE=-1';
    if ($backend == -1) { echo ' SELECTED'; }
    echo '>' . _("All address books") . "\n";
    $ret = $abook->get_backend_list();
    while (list($undef,$v) = each($ret)) {
        echo '<OPTION VALUE=' . $v->bnum;
        if ($backend == $v->bnum) { echo ' SELECTED'; }
        echo '>' . $v->sname . "\n";
    }
    echo "</SELECT>\n";
} else {
    echo '<INPUT TYPE=hidden NAME=backend VALUE=-1>' . "\n";
}
echo '<INPUT TYPE=submit VALUE="' . _("Search") . '">' .
     '&nbsp;|&nbsp;<INPUT TYPE=submit VALUE="' . _("List all") .
     '" NAME=listall>' . "\n" .
     '</FORM></center></TD></TR></TABLE>' . "\n";
addr_insert_hidden();
echo '</CENTER>';
do_hook('addrbook_html_search_below');
/* End search form */

/* Show personal addressbook */

if ( !empty( $listall ) ){
    $addrquery = '*';
}

if ($addrquery == '' && empty($listall)) {

    if (! isset($backend) || $backend != -1 || $addrquery == '') {
        if ($addrquery == '') {
            $backend = $abook->localbackend;
        }

        /* echo '<H3 ALIGN=center>' . $abook->backends[$backend]->sname) . "</H3>\n"; */

        $res = $abook->list_addr($backend);

        if (is_array($res)) {
            usort($res,'alistcmp');
            addr_display_result($res, false);
        } else {
            echo '<P ALIGN=center><STRONG>' .
                 sprintf(_("Unable to list addresses from %s"), 
                     $abook->backends[$backend]->sname) .
                 "</STRONG></P>\n";
        }

    } else {
        $res = $abook->list_addr();
        usort($res,'alistcmp');
        addr_display_result($res, true);
    }
    exit;
}
else {

    /* Do the search */
    if (!empty($addrquery)) {

        if ($backend == -1) {
            $res = $abook->s_search($addrquery);
        } else {
            $res = $abook->s_search($addrquery, $backend);
        }

        if (!is_array($res)) {
            echo '<P ALIGN=center><B><BR>' .
                 _("Your search failed with the following error(s)") . ':<br>' .
                  $abook->error . "</B></P>\n</BODY></HTML>\n";
        } else {
            if (sizeof($res) == 0) {
                echo '<P ALIGN=center><BR><B>' .
                     _("No persons matching your search was found") .
                     ".</B></P>\n</BODY></HTML>\n";
            } else {
                addr_display_result($res);
            }
        }
    }
}

if ($addrquery == '' || sizeof($res) == 0) {
    /* printf('<center><FORM METHOD=post NAME=k ACTION="compose.php">'."\n", $PHP_SELF); */
    echo '<center><FORM METHOD=post NAME=k ACTION="compose.php">' . "\n";
    addr_insert_hidden();
    echo '<INPUT TYPE=submit VALUE="' . _("Return") . '" NAME=return>' . "\n" .
         '</form></center></nobr>';
}

?>
</body></html>
