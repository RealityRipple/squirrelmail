<?php

/**
 * set_language_align.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * which return direction and alignments for the table (stuff like the headers
 * input tags etc. need to be aligned differently in arabic and hebrew).
 *
 * $Id$
 */

    function set_language_align(){
        GLOBAL $languages, $squirrelmail_language;
        $text_align = array();
        if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
           $text_align['dir']  = $languages[$squirrelmail_language]['DIR'];
        } else {
            $text_align['dir'] = 'ltr';
        }
        $text_align['left'] = $text_align['dir'] == 'ltr' ? 'left' : 'right';
        $text_align['right'] = $text_align['dir'] == 'ltr' ? 'right' : 'left';
        return($text_align);
    }
?>