<?php

   /**
    **  testsound.php
    **  Copyright (c) 1999-2001 The Squirrelmail Development Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.        
    **
    **  Displays all options relating to new mail sounds
    **
    **  $Id$
    **    
    **/

   chdir ("../");
   require_once('../src/validate.php');
   require_once("../src/load_prefs.php");
   if (!isset($sound)) {
    $sound = "Click.wav";
   }
   $sound = str_replace('../plugins/newmail/', '', $sound);
   $sound = str_replace('../', '', $sound);
   $sound = str_replace("..\\", '', $sound);

   displayHtmlHeader( _("Test Sound"), '', FALSE );

   echo "<BODY bgcolor=\"$color[4]\" topmargin=0 leftmargin=0 rightmargin=0 marginwidth=0 marginheight=0>\n".
        '<CENTER>'.
        "<embed src=\"$sound\" hidden=true autostart=true>".
        '<br>'.
        '<b>' . _("Loading the sound...") . '</b><br><br>'.
        '<form>'.
        '<input type="button" name="close" value="  ' .
        _("Close") .
        '  " onClick="window.close()">'.
        '</form>'.
        '</CENTER>'.
        '</BODY></HTML>';

?>