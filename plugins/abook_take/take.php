<?php

/**
 * take.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Address Take -- steals addresses from incoming email messages. Searches
 * the To, Cc, From and Reply-To headers.
 *
 * $Id$
 * @package plugins
 * @subpackage abook_take
 */

/**
 * Path for SquirrelMail required files.
 * @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/addressbook.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/forms.php');

displayPageHeader($color, 'None');

/* input form data */
sqgetGlobalVar('email', $email, SQ_POST);

$abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');

$abook = addressbook_init(false, true);
$name = 'addaddr';

$addrs = array();
foreach ($email as $Val) {
    if (valid_email($Val, $abook_take_verify)) {
        $addrs[$Val] = $Val;
    } else {
        $addrs[$Val] = 'FAIL - ' . $Val;
    }
}

echo addForm(SM_PATH . 'src/addressbook.php', 'POST', 'f_add') . "\n" .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'th', sprintf(_("Add to %s"), $abook->localbackendname), 'center', $color[0] )
        ) ,
    'center', '', 'width="100%"' ) . "\n" .

    html_tag( 'table', '', 'center', '', 'border="0" cellpadding="1" cols="2" width="90%"' ) . "\n" .
            html_tag( 'tr', "\n" .
                html_tag( 'td', _("Nickname") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
                html_tag( 'td', addInput($name . '[nickname]', '', 15) .
                    '&nbsp;<small>' . _("Must be unique") . '</small>',
                'left', $color[4] )
            ) . "\n" .
            html_tag( 'tr' ) . "\n" .
            html_tag( 'td', _("E-mail address") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
            html_tag( 'td', '', 'left', $color[4] ) . "\n" .
            addSelect($name . '[email]', $addrs, null, true) .
            '</td></tr>' . "\n";

    if ($squirrelmail_language == 'ja_JP') {
        echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("Last name") . ':', 'right', $color[4], 'width="50"' ) .
                html_tag( 'td', addInput($name . '[lastname]', '', 45), 'left', $color[4] )
             ) . "\n" .
             html_tag( 'tr', "\n" .
                html_tag( 'td', _("First name") . ':', 'right', $color[4], 'width="50"' ) .
                html_tag( 'td', addInput($name . '[firstname]', '', 45), 'left', $color[4] )
             ) . "\n";
    } else {
        echo html_tag( 'tr', "\n" .
                html_tag( 'td', _("First name") . ':', 'right', $color[4], 'width="50"' ) .
                html_tag( 'td', addInput($name . '[firstname]', '', 45), 'left', $color[4] )
             ) . "\n" .
             html_tag( 'tr', "\n" .
                html_tag( 'td', _("Last name") . ':', 'right', $color[4], 'width="50"' ) .
                html_tag( 'td', addInput($name . '[lastname]', '', 45), 'left', $color[4] )
             ) . "\n";
    }
    echo html_tag( 'tr', "\n" .
            html_tag( 'td', _("Additional info") . ':', 'right', $color[4], 'width="50"' ) .
            html_tag( 'td', addInput($name . '[label]', '', 45), 'left', $color[4] )
         ) . "\n" .
         html_tag( 'tr', "\n" .
            html_tag( 'td',
                addSubmit(_("Add address"), $name . '[SUBMIT]'),
                'center', $color[4], 'colspan="2"' )
         ) . "\n";
?>
</table>
</form></body>
</html>
