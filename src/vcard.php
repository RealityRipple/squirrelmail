<?php

/**
 * vcard.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file shows an attched vcard
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/date.php');
require_once('../functions/page_header.php');
require_once('../functions/mime.php');
require_once('../src/load_prefs.php');

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imapConnection, $mailbox);


displayPageHeader($color, 'None');

echo '<br>' .
    html_tag( 'table', '', 'center', '', 'width="100%" border="0" cellspacing="0" cellpadding="2"' ) ."\n" .
    html_tag( 'tr' ) . "\n" .
    html_tag( 'td', '', 'left', $color[0] ) .
        '<b><center>' .
        _("Viewing a Business Card") . ' - ';
if (isset($where) && isset($what)) {
    // from a search
    echo '<a href="../src/read_body.php?mailbox=' . urlencode($mailbox) .
            '&amp;passed_id=' . $passed_id . '&amp;where=' . urlencode($where) .
            '&amp;what=' . urlencode($what). '">' . _("View message") . '</a>';
} else {
    echo '<a href="../src/read_body.php?mailbox=' . urlencode($mailbox) .
        '&amp;passed_id=' . $passed_id . '&amp;startMessage=' . $startMessage .
        '&amp;show_more=0">' . _("View message") . '</a>';
}
echo '</center></b></td></tr>';

$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

$entity_vcard = getEntity($message,$passed_ent_id);

$vcard = mime_fetch_body ($imapConnection, $passed_id, $passed_ent_id);
$vcard = decodeBody($vcard, $entity_vcard->header->encoding);
$vcard = explode ("\n",$vcard);
foreach ($vcard as $l) {
    $k = substr($l, 0, strpos($l, ':'));
    $v = substr($l, strpos($l, ':') + 1);
    $attributes = explode(';', $k);
    $k = strtolower(array_shift($attributes));
    foreach ($attributes as $attr)     {
        if ($attr == 'quoted-printable')
        $v = quoted_printable_decode($v);
        else
            $k .= ';' . $attr;
    }

    $v = str_replace(';', "\n", $v);
    $vcard_nice[$k] = $v;
}

if ($vcard_nice['version'] == '2.1') {
    // get firstname and lastname for sm addressbook
    $vcard_nice["firstname"] = substr($vcard_nice["n"],
    strpos($vcard_nice["n"], "\n") + 1, strlen($vcard_nice["n"]));
    $vcard_nice["lastname"] = substr($vcard_nice["n"], 0,
        strpos($vcard_nice["n"], "\n"));
} else {
    echo html_tag( 'tr',
               html_tag( 'td', 'vCard Version ' . $vcard_nice['version'] .
                    ' is not supported.  Some information might not be converted ' .
                    'correctly.' ,
               'center' )
           ) . "\n";
}

foreach ($vcard_nice as $k => $v) {
    $v = htmlspecialchars($v);
    $v = trim($v);
    $vcard_safe[$k] = trim(nl2br($v));
}

$ShowValues = array(
    'fn' =>             _("Name"),
    'title' =>          _("Title"),
    'email;internet' => _("Email"),
    'url' =>            _("Web Page"),
    'org' =>            _("Organization / Department"),
    'adr' =>            _("Address"),
    'tel;work' =>       _("Work Phone"),
    'tel;home' =>       _("Home Phone"),
    'tel;cell' =>       _("Cellular Phone"),
    'tel;fax' =>        _("Fax"),
    'note' =>           _("Note"));

echo html_tag( 'tr' ) . html_tag( 'td', '', 'left' ) . '<br>' .
        html_tag( 'table', '', 'center', '', 'border="0" cellpadding="2" cellspacing="0"' ) . "\n";

if (isset($vcard_safe['email;internet'])) {     $vcard_safe['email;internet'] = '<a href="../src/compose.php?send_to=' .
        $vcard_safe['email;internet'] . '">' . $vcard_safe['email;internet'] .
        '</a>';
}
if (isset($vcard_safe['url'])) {
    $vcard_safe['url'] = '<a href="' . $vcard_safe['url'] . '">' .
        $vcard_safe['url'] . '</a>';
}

foreach ($ShowValues as $k => $v) {
    if (isset($vcard_safe[$k]) && $vcard_safe[$k])     {
        echo html_tag( 'tr',
                   html_tag( 'td', '<b>' . $v . ':</b>', 'right' ) .
                   html_tag( 'td', $vcard_safe[$k], 'left' )
               ) . "\n";
    }
}

echo '</table>' .
        '<br>' .
        '</td></tr></table>' .
        html_tag( 'table', '', 'center', '', 'border="0" cellpadding="2" cellspacing="0" width="100%"' ) . "\n";
        html_tag( 'tr',
            html_tag( 'td',
                '<b><center>' .
                _("Add to Addressbook") . '</b></center>' ,
            'left', $color[0] )
        ) .
        html_tag( 'tr' ) .
        html_tag( 'td', '', 'center' ) .
        '<form action="../src/addressbook.php" method="post" name="f_add">' .
        html_tag( 'table', '', 'center', '', 'border="0" cellpadding="2" cellspacing="0" width="100%"' ) .
        html_tag( 'tr',
            html_tag( 'td',
                '<b>Nickname:</b>' ,
            'right' ) .
            html_tag( 'td',
                '<input type=text name="addaddr[nickname]" size=20 value="' .
                $vcard_safe['firstname'] . '-' . $vcard_safe['lastname'] . '">' ,
            'left' )
        ) .
        html_tag( 'tr' ) .
        html_tag( 'td', '<b>Note Field Contains:</b>', 'right' ) .
        html_tag( 'td', '', 'left' ) .
        '<select name="addaddr[label]">';

if (isset($vcard_nice['url'])) {
    echo '<option value="' . htmlspecialchars($vcard_nice['url']) .
        '">' . _("Web Page") . "</option>\n";
}
if (isset($vcard_nice['adr'])) {
    echo '<option value="' . $vcard_nice['adr'] .
        '">' . _("Address") . "</option>\n";
}
if (isset($vcard_nice['title'])) {
    echo '<option value="' . $vcard_nice['title'] .
        '">' . _("Title") . "</option>\n";
}
if (isset($vcard_nice['org'])) {
    echo '<option value="' . $vcard_nice['org'] .
        '">' . _("Organization / Department") . "</option>\n";
}
if (isset($vcard_nice['title'])) {
    echo '<option value="' . $vcard_nice['title'] .
        '; ' . $vcard_nice['org'] .
        '">' . _("Title & Org. / Dept.") . "</option>\n";
}
if (isset($vcard_nice['tel;work'])) {
    echo '<option value="' . $vcard_nice['tel;work'] .
        '">' . _("Work Phone") . "</option>\n";
}
if (isset($vcard_nice['tel;home'])) {
    echo '<option value="' . $vcard_nice['tel;home'] .
        '">' . _("Home Phone") . "</option>\n";
}
if (isset($vcard_nice['tel;cell'])) {
    echo '<option value="' . $vcard_nice['tel;cell'] .
        '">' . _("Cellular Phone") . "</option>\n";
}
if (isset($vcard_nice['tel;fax'])) {
    echo '<option value="' . $vcard_nice['tel;fax'] .
        '">' . _("Fax") . "</option>\n";
}
if (isset($vcard_nice['note'])) {
    echo '<option value="' . $vcard_nice['note'] .
        '">' . _("Note") . "</option>\n";
}
echo '</select>' .
        '</td></tr>' .
        html_tag( 'tr',
            html_tag( 'td',
                '<input name="addaddr[email]" type=hidden value="' .
                htmlspecialchars($vcard_nice['email;internet']) . '">' .
                '<input name="addaddr[firstname]" type=hidden value="' .
                $vcard_safe['firstname'] . '">' .
                '<input name="addaddr[lastname]" type=hidden value="' .
                $vcard_safe['lastname'] . '">' .
                '<input type="submit" name="addaddr[SUBMIT]" ' .
                'value="Add to Address Book">' ,
            'center', '', 'colspan="2"' )
        ) .
        '</table>' .
        '</form>' .
        '</td></tr>' .
        html_tag( 'tr',
            html_tag( 'td',
                '<a href="../src/download.php?absolute_dl=true&amp;passed_id=' .
                $passed_id . '&amp;mailbox=' . urlencode($mailbox) .
                '&amp;passed_ent_id=' . $passed_ent_id . '">' .
                _("Download this as a file") . '</a>' ,
            'center' )
        ) .
        '</table>' .

        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '&nbsp;', 'left', $color[4] )
            ) ,
        'center', '', 'border="0" cellspacing="0" cellpadding="2"' ) .
        '</body></html>';

?>
