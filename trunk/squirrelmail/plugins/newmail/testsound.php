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
   $sound = str_replace("../plugins/newmail/", "", $sound);
   $sound = str_replace("../", "", $sound);
   $sound = str_replace("..\\", "", $sound);
?>
<HTML>
<TITLE>Test Sound</TITLE>
<BODY bgcolor=<?php echo $color[4] ?> topmargin=0 leftmargin=0
rightmargin=0 marginwidth=0 marginheight=0>
<CENTER>
<embed src="<?php echo $sound ?>" hidden=true autostart=true>
<br>
<font face="Veranda, Arial Helvetica, sans-serif" size="2" </font>
<b>Loading the sound...</b><br><br>
<form>
<input type="button" name="close" value="  Close  " onClick="window.close()">
</form>
</CENTER>
</BODY></HTML>
