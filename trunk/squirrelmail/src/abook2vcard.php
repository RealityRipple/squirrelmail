<?php

/**
 * read_body.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file is used for reading the msgs array and displaying
 * the resulting emails in the right frame.
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/prefs.php');
require_once(SM_PATH . 'config/config.php');

$abook_file=$data_dir.$username.".abook";
$vcard_dir=$data_dir.$username."/";

$fp = fopen ($abook_file,"r");
while (!feof ($fp)) {
    $buffer .= fgets($fp, 4096);
}

fclose ($fp);

$abook=explode("|",$buffer);
print_r($abook);
echo "<p>".$buffer;

while ( list($nick,$email,$fn,$ln,$email)=each($abook) ) {
echo "
BEGIN:VCARD
VERSION:3.0
N:$ln;$fn;
NICKNAME:$nick
EMAIL;INTERNET:$email
END:VCARD<P>
";
}

?>
