<?php

/**
 * addrbook_search_html.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handle addressbook searching with pure html.
 *
 * This file is included from compose.php
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
if (! defined('SM_PATH') ) {
    define('SM_PATH','../');
}

/** SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/addressbook.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');

sqgetGlobalVar('session',   $session,   SQ_POST);
sqgetGlobalVar('mailbox',   $mailbox,   SQ_POST);
sqgetGlobalVar('addrquery', $addrquery, SQ_POST);
sqgetGlobalVar('listall',   $listall,   SQ_POST);
sqgetGlobalVar('backend',   $backend,   SQ_POST);

/**
 * Insert hidden data
 */
function addr_insert_hidden() {
    global $body, $subject, $send_to, $send_to_cc, $send_to_bcc, $mailbox,
           $identity, $session;

   if (substr($body, 0, 1) == "\r") {
       echo addHidden('body', "\n".$body);
   } else {
       echo addHidden('body', $body);
   }

   echo addHidden('session', $session).
        addHidden('subject', $subject).
        addHidden('send_to', $send_to).
        addHidden('send_to_bcc', $send_to_bcc).
        addHidden('send_to_cc', $send_to_cc).
        addHidden('identity', $identity).
        addHidden('mailbox', $mailbox).
        addHidden('from_htmladdr_search', 'true');
}


/**
 * List search results
 * @param array $res Array containing results of search
 * @param bool $includesource UNDOCUMENTED [Default=true]
 */
function addr_display_result($res, $includesource = true) {
    global $color, $javascript_on, $PHP_SELF, $squirrelmail_language;

    if (sizeof($res) <= 0) return;

    echo addForm($PHP_SELF, 'post', 'addrbook').
         addHidden('html_addr_search_done', 'true');
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
    $chk_all = '<a href="#" onclick="CheckAll(\'T\');">' . _("All") . '</a>&nbsp;<font color="'.$color[9].'">'._("To").'</font>'.
            '&nbsp;&nbsp;'.
            '<a href="#" onclick="CheckAll(\'C\');">' . _("All") . '</a>&nbsp;<font color="'.$color[9].'">'._("Cc").'</font>'.
            '&nbsp;&nbsp;'.
            '<a href="#" onclick="CheckAll(\'B\');">' . _("All") . '</a>';
    }
    echo html_tag( 'table', '', 'center', '', 'border="0" width="98%"' ) .
    html_tag( 'tr', '', '', $color[9] ) .
    html_tag( 'th', '&nbsp;' . $chk_all, 'left' ) .
    html_tag( 'th', '&nbsp;' . _("Name"), 'left' ) .
    html_tag( 'th', '&nbsp;' . _("E-mail"), 'left' ) .
    html_tag( 'th', '&nbsp;' . _("Info"), 'left' );

    if ($includesource) {
        echo html_tag( 'th', '&nbsp;' . _("Source"), 'left', '', 'width="10%"' );
    }

    echo "</tr>\n";

    foreach ($res as $row) {
        $email = AddressBook::full_address($row);
        if ($line % 2) {
            $tr_bgcolor = $color[12];
        } else {
            $tr_bgcolor = $color[4];
        }
        if ($squirrelmail_language == 'ja_JP')
            {
        echo html_tag( 'tr', '', '', $tr_bgcolor, 'style="white-space: nowrap;"' ) .
        html_tag( 'td',
             '<input type="checkbox" name="send_to_search[T' . $line . ']" value = "' .
             htmlspecialchars($email) . '" />&nbsp;' . _("To") . '&nbsp;' .
             '<input type="checkbox" name="send_to_search[C' . $line . ']" value = "' .
             htmlspecialchars($email) . '" />&nbsp;' . _("Cc") . '&nbsp;' .
             '<input type="checkbox" name="send_to_search[B' . $line . ']" value = "' .
             htmlspecialchars($email) . '" />&nbsp;' . _("Bcc") . '&nbsp;' ,
        'center', '', 'width="5%" style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['lastname']) . ' ' . htmlspecialchars($row['firstname']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['email']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['label']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' );
            } else {
        echo html_tag( 'tr', '', '', $tr_bgcolor, 'style="white-space: nowrap;"' ) .
        html_tag( 'td',
            addCheckBox('send_to_search[T'.$line.']', FALSE, $email).
            '&nbsp;' . _("To") . '&nbsp;' .
            addCheckBox('send_to_search[C'.$line.']', FALSE, $email).
            '&nbsp;' . _("Cc") . '&nbsp;' .
            addCheckBox('send_to_search[B'.$line.']', FALSE, $email).
            '&nbsp;' . _("Bcc") . '&nbsp;' ,
        'center', '', 'width="5%" style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['name']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['email']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['label']) . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' );
            }

         if ($includesource) {
             echo html_tag( 'td', '&nbsp;' . $row['source'] . '&nbsp;', 'left', '', 'style="white-space: nowrap;"' );
         }
         echo "</tr>\n";
         $line ++;
    }
    if ($includesource) { $td_colspan = '5'; } else { $td_colspan = '4'; }
    echo html_tag( 'tr',
                html_tag( 'td',
                        '<input type="submit" name="addr_search_done" value="' .
                        _("Use Addresses") . '" />' ,
                'center', '', 'colspan="'. $td_colspan .'"' )
            ) .
         '</table>' .
         addHidden('html_addr_search_done', '1').
         '</form>';
}

/* --- End functions --- */

if ($compose_new_win == '1') {
    compose_Header($color, $mailbox);
}
else {
    displayPageHeader($color, $mailbox);
}
/* Initialize addressbook */
$abook = addressbook_init();


echo '<br />' .
html_tag( 'table',
    html_tag( 'tr',
        html_tag( 'td', '<b>' . _("Address Book Search") . '</b>', 'center', $color[0] )
    ) ,
'center', '', 'width="95%" cellpadding="2" cellspacing="2" border="0"' );


/* Search form */
echo '<center>' .
    html_tag( 'table', '', 'center', '', 'border="0"' ) .
    html_tag( 'tr' ) .
    html_tag( 'td', '', 'left', '', 'style="white-space: nowrap;" valign="middle"' ) . "\n" .
    addForm($PHP_SELF.'?html_addr_search=true', 'post', 'f').
    "\n<center>\n" .
    '  <nobr><strong>' . _("Search for") . "</strong>\n";
addr_insert_hidden();
if (! isset($addrquery))
    $addrquery = '';
echo addInput('addrquery', $addrquery, 26);

/* List all backends to allow the user to choose where to search */
if (!isset($backend)) { $backend = ''; }
if ($abook->numbackends > 1) {
    echo '<strong>' . _("in") . '</strong>&nbsp;';

    $selopts['-1'] = _("All address books");
    $ret = $abook->get_backend_list();

    while (list($undef,$v) = each($ret)) {
        $selopts[$v->bnum] = $v->sname;
    }
    echo addSelect('backend', $selopts, $backend, TRUE);
} else {
    echo addHidden('backend', '-1');
}
if (isset($session)) {
    echo addHidden('session', $session);
}

echo '<input type="submit" value="' . _("Search") . '" />' .
     '&nbsp;|&nbsp;<input type="submit" value="' . _("List all") .
     '" name="listall" />' . "\n" .
     '</form></center></td></tr></table>' . "\n";
addr_insert_hidden();
echo '</center>';
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

        /* echo '<h3 align="center">' . $abook->backends[$backend]->sname) . "</h3>\n"; */

        $res = $abook->list_addr($backend);

        if (is_array($res)) {
            usort($res,'alistcmp');
            addr_display_result($res, false);
        } else {
            echo html_tag( 'p', '<strong><br />' .
                 sprintf(_("Unable to list addresses from %s"),
                 $abook->backends[$backend]->sname) . "</strong>\n" ,
            'center' );
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
            echo html_tag( 'p', '<b><br />' .
                             _("Your search failed with the following error(s)") .
                            ':<br />' . $abook->error . "</b>\n" ,
                   'center' ) .
            "\n</body></html>\n";
        } else {
            if (sizeof($res) == 0) {
                echo html_tag( 'p', '<br /><b>' .
                                 _("No persons matching your search were found") . "</b>\n" ,
                       'center' ) .
                "\n</body></html>\n";
            } else {
                addr_display_result($res);
            }
        }
    }
}

if ($addrquery == '' || sizeof($res) == 0) {
    /* printf('<center><form method="post" name="k" action="compose.php">'."\n", $PHP_SELF); */
    echo '<center>'.
        addForm('compose.php','post','k');
    addr_insert_hidden();
    echo '<input type="submit" value="' . _("Return") . '" name="return" />' . "\n" .
         '</form></center></nobr>';
}

?>
</body></html>