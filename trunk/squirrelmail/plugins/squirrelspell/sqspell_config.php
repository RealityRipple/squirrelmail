<?php

   /**
    **  sqspell_config.php -- SquirrelSpell Configuration file.
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **
    **
    **  $Id$
    **/

    /* Just for poor wretched souls with E_ALL. :) */
    global $username, $data_dir;


    $SQSPELL_APP = array( 'English' => 'ispell -d spanish -a' );
    $SQSPELL_APP_DEFAULT = 'English';
    $SQSPELL_WORDS_FILE = "$data_dir/$username.words";
    $SQSPELL_EREG = 'ereg';
    $SQSPELL_SOUP_NAZI = 'Mozilla/3, Mozilla/2, Opera 4, Opera/4, Macintosh';

?>