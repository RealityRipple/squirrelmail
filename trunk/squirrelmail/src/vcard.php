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

/* Path for SquirrelMail required files. */
Define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/date.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'include/load_prefs.php');

/* globals */
$key  = $_COOKIE['key'];
$username = $_SESSION['username'];
$onetimepad = $_SESSION['onetimepad'];
$mailbox = decodeHeader($_GET['mailbox']);
$passed_id = (int) $_GET['passed_id'];
$ent_id = $_GET['ent_id'];
$passed_ent_id = $_GET['passed_ent_id'];
$QUERY_STRING = $_SERVER['QUERY_STRING'];
/* end globals */

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imapConnection, $mailbox);


displayPageHeader($color, 'None');

echo '<br><table width="100%" border="0" cellspacing="0" cellpadding="2" ' .
            'align="center">' . "\n" .
        '<tr><td bgcolor="' . $color[0] . '">' .
        '<b><center>' .
        _("Viewing a Business Card") . " - ";
$msg_url = 'read_body.php?' . urlencode(strip_tags(urldecode($QUERY_STRING)));
$msg_url = set_url_var($msg_url, 'ent_id', 0);
echo '<a href="'.$msg_url.'">'. _("View message") . '</a>';

echo '</center></b></td></tr>';

$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

$entity_vcard = getEntity($message,$ent_id);

$vcard = mime_fetch_body ($imapConnection, $passed_id, $ent_id);
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
    echo '<tr><td align=center>vCard Version ' . $vcard_nice['version'] .
        ' is not supported.  Some information might not be converted ' .
    "correctly.</td></tr>\n";
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

echo '<tr><td><br>' .
        '<TABLE border=0 cellpadding=2 cellspacing=0 align=center>' . "\n";

if (isset($vcard_safe['email;internet'])) {     $vcard_safe['email;internet'] = '<A HREF="../src/compose.php?send_to=' .
        $vcard_safe['email;internet'] . '">' . $vcard_safe['email;internet'] .
        '</A>';
}
if (isset($vcard_safe['url'])) {
    $vcard_safe['url'] = '<A HREF="' . $vcard_safe['url'] . '">' .
        $vcard_safe['url'] . '</A>';
}

foreach ($ShowValues as $k => $v) {
    if (isset($vcard_safe[$k]) && $vcard_safe[$k])     {
        echo "<tr><td align=right><b>$v:</b></td><td>" . $vcard_safe[$k] .
                "</td><tr>\n";
    }
}

echo '</table>' .
        '<br>' .
        '</td></tr></table>' .
        '<table width="100%" border="0" cellspacing="0" cellpadding="2" ' .
        'align="center">' .
        '<tr>' .
        '<td bgcolor="' . $color[0] . '">' .
        '<b><center>' .
        _("Add to Addressbook") .
        '</td></tr>' .
        '<tr><td align=center>' .
        '<FORM ACTION="../src/addressbook.php" METHOD="POST" NAME=f_add>' .
        '<table border=0 cellpadding=2 cellspacing=0 align=center>' .
        '<tr><td align=right><b>Nickname:</b></td>' .
        '<td><input type=text name="addaddr[nickname]" size=20 value="' .
        $vcard_safe['firstname'] . '-' . $vcard_safe['lastname'] .
        '"></td></tr>' .
        '<tr><td align=right><b>Note Field Contains:</b></td><td>' .
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
        '<tr><td colspan=2 align=center>' .
        '<INPUT NAME="addaddr[email]" type=hidden value="' .
        htmlspecialchars($vcard_nice['email;internet']) . '">' .
        '<INPUT NAME="addaddr[firstname]" type=hidden value="' .
        $vcard_safe['firstname'] . '">' .
        '<INPUT NAME="addaddr[lastname]" type=hidden value="' .
        $vcard_safe['lastname'] . '">' .
        '<INPUT TYPE=submit NAME="addaddr[SUBMIT]" ' .
        'VALUE="Add to Address Book">' .
        '</td></tr>' .
        '</table>' .
        '</FORM>' .
        '</td></tr>' .
        '<tr><td align=center>' .
        '<a href="../src/download.php?absolute_dl=true&amp;passed_id=' .
        $passed_id . '&amp;mailbox=' . urlencode($mailbox) .
        '&amp;passed_ent_id=' . urlencode($passed_ent_id) . '">' .
        _("Download this as a file") . '</A>' .
        '</TD></TR></TABLE>' .

        '<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>' .
        '<TR><TD BGCOLOR="' . $color[4] . '">' .
        '</TD></TR></TABLE>' .
        '</body></html>';

?>
