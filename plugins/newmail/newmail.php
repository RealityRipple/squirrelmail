<?php

/**
 * newmail.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.        
 *
 * Displays all options relating to new mail sounds
 *
 * $Id$
 * @package plugins
 * @subpackage newmail
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/page_header.php');

sqGetGlobalVar('numnew', $numnew, SQ_GET);

   displayHtmlHeader( _("New Mail"), '', FALSE );

   echo '<body bgcolor="'.$color[4].'" topmargin="0" leftmargin="0" rightmargin="0" marginwidth="0" marginheight="0">'."\n".
        '<center>'. "\n" .
        html_tag( 'table', "\n" .
            html_tag( 'tr', "\n" .
                html_tag( 'td', '<b>' . _("SquirrelMail Notice:") . '</b>', 'center', $color[0] )
            ) .
            html_tag( 'tr', "\n" .
                html_tag( 'td',
                    '<br /><big><font color="' . $color[2] . '">'.
        	    sprintf( ( $numnew == 1 ?
        	        _("You have %s new message") :
        		_("You have %s new messages") ), $numnew ) .
                    '</font><br /></big><br />' . "\n" .
                    '<form name="nm">' . "\n".
                    '<input type="button" name="bt" value="' . _("Close Window") .
                    '" onClick="javascript:window.close();" />'."\n".
                    '</form>',
                'center' )
            ) ,
        '', '', 'width="100%" cellpadding="2" cellspacing="2" border="0"' ) .
        '</center>' .
        "<script language=javascript>\n".
        "<!--\n".
            "document.nm.bt.focus();\n".
        "-->\n".
        "</script>\n".
        "</body></html>\n";

?>