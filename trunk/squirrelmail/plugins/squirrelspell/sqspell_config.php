<?php
/**
 * sqspell_config.php -- SquirrelSpell Configuration file.
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *
 *
 * $Id$
 */

require_once(SM_PATH . 'functions/prefs.php');

/* Just for poor wretched souls with E_ALL. :) */
global $data_dir;

if ( !check_php_version(4,1) ) {
    global $_SESSION;
}

$username = $_SESSION['username'];

/**
 * Example:
 *
 * $SQSPELL_APP = array( 'English' => 'ispell -a',
 *                     'Spanish' => 'ispell -d spanish -a' );
 */
$SQSPELL_APP = array('English' => 'ispell -a',
			'Spanish' => 'ispell -d spanish -a');
$SQSPELL_APP_DEFAULT = 'English';
$SQSPELL_WORDS_FILE = 
   getHashedFile($username, $data_dir, "$username.words");

$SQSPELL_EREG = 'ereg';
#$SQSPELL_SOUP_NAZI = 'Mozilla/3, Mozilla/2, Opera 4, Opera/4, '
#   . 'Macintosh, OmniWeb';

?>
