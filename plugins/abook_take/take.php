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
require_once(SM_PATH . 'functions/addressbook.php');

displayPageHeader($color, 'None');

/* input form data */
sqgetGlobalVar('email', $email, SQ_POST);

$abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');

$abook = addressbook_init(false, true);

$addrs = array();
foreach ($email as $Val) {
    if (valid_email($Val, $abook_take_verify)) {
        $addrs[$Val] = $Val;
    } else {
        $addrs[$Val] = 'FAIL - ' . $Val;
    }
}

$formdata=array('email'=>$addrs);

abook_create_form(SM_PATH . 'src/addressbook.php','addaddr',_("Add to address book"),_("Add address"),$formdata);
echo '</form>';
?>
</body></html>