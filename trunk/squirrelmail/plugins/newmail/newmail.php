<?php

/**
 * newmail.php - popup page
 *
 * Displays all options relating to new mail sounds
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
require('../../include/init.php');

/**
 * Make sure plugin is activated!
 */
global $plugins;
if (!in_array('newmail', $plugins))
   exit;

/** load default config */
if (file_exists(SM_PATH . 'plugins/newmail/config_default.php')) {
   include_once(SM_PATH . 'plugins/newmail/config_default.php');
}

/** load config */
if (file_exists(SM_PATH . 'config/newmail_config.php')) {
   include_once(SM_PATH . 'config/newmail_config.php');
} elseif (file_exists(SM_PATH . 'plugins/newmail/config.php')) {
   include_once(SM_PATH . 'plugins/newmail/config.php');
}

   sqGetGlobalVar('numnew', $numnew, SQ_GET);
   $numnew = (int)$numnew;

   global $username, $org_title,
          $newmail_popup_title_bar_singular, $newmail_popup_title_bar_plural;

   // make sure default strings are in pot file
   $ignore = _("New Mail");

   $singular_title = "New Mail";
   $plural_title = "New Mail";
   if (!empty($newmail_popup_title_bar_singular))
      $singular_title = $newmail_popup_title_bar_singular;
   if (!empty($newmail_popup_title_bar_plural))
      $plural_title = $newmail_popup_title_bar_plural;
   list($singular_title, $plural_title) = str_replace(array('###USERNAME###', '###ORG_TITLE###'), array($username, $org_title), array($singular_title, $plural_title));
   $title = sprintf(ngettext($singular_title, $plural_title, $numnew), $numnew);


   displayHtmlHeader( $title, '', FALSE );

   echo '<body bgcolor="'.$color[4].'" topmargin="0" leftmargin="0" rightmargin="0" marginwidth="0" marginheight="0">'."\n".
        '<div style="text-align: center;">'. "\n" .
        html_tag( 'table', "\n" .
            html_tag( 'tr', "\n" .
                // i18n: %s inserts the organisation name (typically SquirrelMail)
                html_tag( 'td', '<b>' . sprintf(_("%s notice:"), $org_name) . '</b>', 'center', $color[0] )
            ) .
            html_tag( 'tr', "\n" .
                html_tag( 'td',
                          '<br /><big><font color="' . $color[2] . '">'.
                          sprintf(ngettext("You have %s new message","You have %s new messages",$numnew), $numnew ) .
                          '</font><br /></big><br />' . "\n" .
                          '<form name="nm">' . "\n".
                          '<input type="button" name="bt" value="' . _("Close Window") .
                          '" onclick="javascript:window.close();" />'."\n".
                          '</form>',
                          'center' )
                      ) ,
                  '', '', 'width="100%" cellpadding="2" cellspacing="2" border="0"' ) .
   '</div>' .
   "<script type=\"text/javascript\">\n".
   "<!--\n".
   "document.nm.bt.focus();\n".
   "-->\n".
   "</script>\n".
   "</body></html>\n";
