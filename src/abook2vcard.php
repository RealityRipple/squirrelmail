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
$vcard_base=$data_dir.$username;
$i=0;

$fp = fopen ($abook_file,"r");
while (!feof ($fp)) {
    $buffer = fgets($fp, 8096);
   $line=explode("|",$buffer);
   if (count($line)>1) {
    write_vcard($line);
   }
}

fclose ($fp);


function write_vcard($abook) {
global $vcard_base,$i;


// FIXME check if filename is ok
$vcard_fn = $vcard_base.".".$abook[0].".vcard";

$fp0 = fopen ($vcard_fn,"w");

fputs($fp0, "BEGIN:VCARD
VERSION:3.0
N:$abook[1];$abook[2];
NICKNAME:$abook[0]
EMAIL;INTERNET:$abook[3];
END:VCARD<P>
");
$i++;
fclose($fp0);

}

echo "done ;) seem to have written $i files.";
?>
