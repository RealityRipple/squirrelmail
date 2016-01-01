<?php

/**
 * vcard.php
 *
 * This file shows an attched vcard
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the vcard page */
define('PAGE_NAME', 'vcard');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */

/** imap functions depend on date functions */
include_once(SM_PATH . 'functions/date.php');
/** form functions */
include_once(SM_PATH . 'functions/forms.php');
/** mime decoding */
include_once(SM_PATH . 'functions/mime.php');
/** url parser */
include_once(SM_PATH . 'functions/url_parser.php');
/** imap functions used to retrieve vcard */
include_once(SM_PATH . 'functions/imap_general.php');
include_once(SM_PATH . 'functions/imap_messages.php');

/* globals */

sqgetGlobalVar('passed_id', $passed_id, SQ_GET, NULL, SQ_TYPE_BIGINT);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('ent_id', $ent_id, SQ_GET);
sqgetGlobalVar('startMessage', $startMessage, SQ_GET);
/* end globals */

global $imap_stream_options; // in case not defined in config
$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
sqimap_mailbox_select($imapConnection, $mailbox);

displayPageHeader($color);

$msg_url = 'read_body.php?mailbox='.urlencode($mailbox).
    '&amp;startMessage='.urlencode($startMessage).
    '&amp;passed_id='.urlencode($passed_id);
$msg_url = set_url_var($msg_url, 'ent_id', 0);

$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

$entity_vcard = getEntity($message,$ent_id);

$vcard = mime_fetch_body($imapConnection, $passed_id, $ent_id);
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
            $k .= ';' . strtolower($attr);
    }

    $v = str_replace(';', "\n", $v);
    $vcard_nice[$k] = $v;
}

if ($vcard_nice['version'] == '2.1') {
    // get firstname and lastname for sm addressbook
    $vcard_nice['firstname'] = substr($vcard_nice['n'],
    strpos($vcard_nice['n'], "\n") + 1, strlen($vcard_nice['n']));
    $vcard_nice['lastname'] = substr($vcard_nice['n'], 0,
        strpos($vcard_nice['n'], "\n"));
    // workaround for Outlook, should be fixed in a better way,
    // maybe in new 'vCard' class.
    if (isset($vcard_nice['email;pref;internet'])) {
       $vcard_nice['email;internet'] = $vcard_nice['email;pref;internet'];
    }
} else {
    $oTemplate->assign('note', sprintf(_("vCard Version %s is not supported. Some information might not be converted correctly."), sm_encode_html_special_chars($vcard_nice['version'])));
    $oTemplate->display('note.tpl');

    $vcard_nice['firstname'] = '';
    $vcard_nice['lastname'] = '';
}

foreach ($vcard_nice as $k => $v) {
    $v = sm_encode_html_special_chars($v);
    $v = trim($v);
    $vcard_safe[$k] = trim(nl2br($v));
}

$ShowValues = array(
    'fn' =>             _("Name"),
    'title' =>          _("Title"),
    'email;internet' => _("E-mail"),
    'url' =>            _("Web Page"),
    'org' =>            _("Organization / Department"),
    'adr' =>            _("Address"),
    'tel;work' =>       _("Work Phone"),
    'tel;home' =>       _("Home Phone"),
    'tel;cell' =>       _("Cellular Phone"),
    'tel;fax' =>        _("Fax"),
    'note' =>           _("Note"));

if (isset($vcard_safe['email;internet'])) {
    $vcard_safe['email;internet'] = makeComposeLink('src/compose.php?send_to='.urlencode($vcard_safe['email;internet']),
        $vcard_safe['email;internet']);
}

if (isset($vcard_safe['url'])) {
    $vcard_safe['url'] = '<a href="' . $vcard_safe['url'] . '" target="_blank">' .
        $vcard_safe['url'] . '</a>';
}

$vcard = array();
foreach ($ShowValues as $k => $v) {
    if (isset($vcard_safe[$k]) && $vcard_safe[$k]) {
        $vcard[$v] = $vcard_safe[$k];
    }
}

$dl = '../src/download.php?absolute_dl=true&amp;passed_id=' .
     urlencode($passed_id) . '&amp;mailbox=' . urlencode($mailbox) .
     '&amp;ent_id=' . urlencode($ent_id);

if (isset($vcard_nice['email;internet'])) {
    $email = $vcard_nice['email;internet'];
} else {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
    $header = $message->rfc822_header;
    $from_name = $header->getAddr_s('from');

    $email = getEmail(decodeHeader($from_name));
}

$opts = array();
if (isset($vcard_nice['url'])) {
    $opts[$vcard_nice['url']] = _("Web Page");
}
if (isset($vcard_nice['adr'])) {
    $opts[$vcard_nice['adr']] = _("Address");
}
if (isset($vcard_nice['title'])) {
    $opts[$vcard_nice['title']] = _("Title");
}
if (isset($vcard_nice['org'])) {
    $opts[$vcard_nice['org']] = _("Organization / Department");
}
if (isset($vcard_nice['title'])) {
    $opts[$vcard_nice['title'].'; '.$vcard_nice['org']] = _("Title &amp; Org. / Dept.");
}
if (isset($vcard_nice['tel;work'])) {
    $opts[$vcard_nice['tel;work']] = _("Work Phone");
}
if (isset($vcard_nice['tel;home'])) {
    $opts[$vcard_nice['tel;home']] = _("Home Phone");
}
if (isset($vcard_nice['tel;cell'])) {
    $opts[$vcard_nice['tel;cell']] = _("Cellular Phone");
}
if (isset($vcard_nice['tel;fax'])) {
    $opts[$vcard_nice['tel;fax']] = _("Fax");
}
if (isset($vcard_nice['note'])) {
    $opts[$vcard_nice['note']] = _("Note");
}

$oTemplate->assign('view_message_link', $msg_url);
$oTemplate->assign('download_link', $dl);
$oTemplate->assign('vcard', $vcard);

$oTemplate->assign('nickname', $vcard_nice['firstname'].'-'.$vcard_safe['lastname']);
$oTemplate->assign('firstname', $vcard_safe['firstname']);
$oTemplate->assign('lastname', $vcard_safe['lastname']);
$oTemplate->assign('email', $email);
$oTemplate->assign('info', $opts);

$oTemplate->display('vcard.tpl');

$oTemplate->display('footer.tpl');
