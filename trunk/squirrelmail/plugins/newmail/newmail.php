<?php

   /**
    **  newmail.php
    **
    **  Copyright (c) 1999-2002 The SquirrelMail Project Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.        
    **
    **  Displays all options relating to new mail sounds
    **
    **  $Id$
    **    
    **/
    
   chdir ('../');
   require_once('../src/validate.php');
   require_once('../src/load_prefs.php');
   require_once('../functions/page_header.php');

   displayHtmlHeader( _("New Mail"), '', FALSE );

   echo "<BODY bgcolor=\"$color[4]\" topmargin=0 leftmargin=0 rightmargin=0 marginwidth=0 marginheight=0>\n".
        '<CENTER>'.
        "<table width=\"100%\" cellpadding=2 cellspacing=2 border=0>\n".
        "<tr>\n".
        "<td bgcolor=\"$color[0]\">\n".
        '<b><center>' . _("SquirrelMail Notice:") . "</center></b>\n".
        "</td>\n".
        "</tr><tr>\n".
        "<td><center><br><big><font color=\"$color[2]\">" .
        _("You have new mail!") . "</font><br></big><br>\n".
        "<form name=nm>\n".
            '<input type=button name=bt value="Close Window" onClick="javascript:window.close();">'.
        "</form></center></td></tr></table></CENTER>\n".
        "<script language=javascript>\n".
        "<!--\n".
            "document.nm.bt.focus();\n".
        "-->\n".
        "</script>\n".
        "</BODY></HTML>\n";

?>
