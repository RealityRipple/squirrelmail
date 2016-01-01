<?php

/**
 * lang_setup.mod
 *
 * Squirrelspell module
 *
 * This module displays available dictionaries to the user and lets
 * him/her choose which ones s/he wants to check messages with.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $SQSPELL_APP;

$msg = '<p>'
  . _("Please check any available international dictionaries which you would like to use when spellchecking:")
  . '</p>'
  . '<form method="post">'
  . '<input type="hidden" name="MOD" value="lang_change" />'
  . '<input type="hidden" name="smtoken" value="' . sm_generate_security_token() . '" />'
  . '<blockquote><p>';
/**
 * Present a nice listing.
 */
$langs = sqspell_getSettings();
$add = '<p><label for="lang_default">'
  . _("Make this dictionary my default selection:")
  . "</label> <select name=\"lang_default\" id=\"lang_default\">\n";
while (list($avail_lang, $junk) = each($SQSPELL_APP)){
  $msg .= "<input type=\"checkbox\" name=\"use_langs[]\" "
    . "value=\"$avail_lang\" id=\"use_langs_$avail_lang\"";
  if (in_array($avail_lang, $langs)) {
    $msg .= ' checked="checked"';
  }
  $msg .= ' /> <label for="use_langs_' . $avail_lang . '">'
    . _($avail_lang) . "</label><br />\n";
  $add .= "<option";
  if ($avail_lang==$langs[0]) {
    $add .= ' selected="selected"';
  }
  $add .= " value=\"$avail_lang\" >" . _($avail_lang) . "</option>\n";
}
$msg .= "</p>\n" . $add . "</select>\n";
$msg .= "</p></blockquote><p><input type=\"submit\" value=\" "
  . _("Make these changes") . " \" /></p>";
sqspell_makePage(_("Add International Dictionaries"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
