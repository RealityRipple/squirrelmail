<?php

/**
 * testsound.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.        
 *
 * Displays all options relating to new mail sounds
 *
 * $Id$
 */

   chdir('../');
   define('SM_PATH','../');

   /* SquirrelMail required files. */
   require_once(SM_PATH . 'include/validate.php');
   require_once(SM_PATH . 'functions/html.php');

   if (!isset($sound)) {
    $sound = "Click.wav";
   }
   $sound = str_replace('../plugins/newmail/', '', $sound);
   $sound = str_replace('../', '', $sound);
   $sound = str_replace("..\\", '', $sound);

   displayHtmlHeader( _("Test Sound"), '', FALSE );

   echo "<body bgcolor=\"$color[4]\" topmargin=0 leftmargin=0 rightmargin=0 marginwidth=0 marginheight=0>\n".
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td',
                    "<embed src=\"$sound\" hidden=true autostart=true>".
                    '<br>'.
                    '<b>' . _("Loading the sound...") . '</b><br><br>'.
                    '<form>'.
                    '<input type="button" name="close" value="  ' .
                    _("Close") .
                    '  " onClick="window.close()">'.
                    '</form>' ,
                'center' )
            ) ,
        'center' ) .
        '</body></html>';

?>
