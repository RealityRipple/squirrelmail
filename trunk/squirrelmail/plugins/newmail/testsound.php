<?php

/**
 * testsound.php
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');

displayHtmlHeader( _("Test Sound"), '', FALSE );

echo '<body bgcolor="'.$color[4].'" topmargin="0" leftmargin="0" rightmargin="0" marginwidth="0" marginheight="0">'."\n";

if ( ! sqgetGlobalVar('sound', $sound, SQ_GET) ) {
    $sound = 'Click.wav';
} elseif ( $sound == '(none)' ) {
    echo '<center><form><br /><br />'.
         '<b>' . _("No sound specified") . '</b><br /><br />'.
         '<input type="button" name="close" value="' . _("Close") . '" onclick="window.close()" />'.
         '</form></center>'.
         '</body></html>';
    return;
}

echo html_tag( 'table',
         html_tag( 'tr',
             html_tag( 'td',
                    newmail_create_media_tags($sound)."\n".
                    '<br />'.
                    '<b>' . _("Loading the sound...") . '</b><br />'.
                    '<form>'.
                    '<input type="button" name="close" value="  ' .
                    _("Close") .
                    '  " onclick="window.close()" />'.
                    '</form>' ,
                'center' )
            ) ,
        'center' ) .
        '</body></html>';
?>