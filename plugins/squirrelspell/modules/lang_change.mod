<?php

/**
 * lang_change.mod
 *
 * Squirrelspell module
 *
 * This module changes the international dictionaries selection
 * for the user. Called after LANG_SETUP module.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $SQSPELL_APP_DEFAULT;

if (! sqgetGlobalVar('use_langs',$use_langs,SQ_POST)) {
    $use_langs = array($SQSPELL_APP_DEFAULT);
}

if (! sqgetGlobalVar('lang_default',$lang_default,SQ_POST)) {
    $lang_default = $SQSPELL_APP_DEFAULT;
}

/**
 * Rebuild languages. Default language is first one.
 */
$new_langs = array($lang_default);
foreach ($use_langs as $lang) {
    if (! in_array($lang,$new_langs)) {
        $new_langs[]=$lang;
    }
}

if (sizeof($new_langs)>1) {
  $dsp_string = '';
  foreach( $new_langs as $a) {
    $dsp_string .= _(htmlspecialchars(trim($a))) . _(", ");
  }
  // remove last comma and space
  $dsp_string = substr( $dsp_string, 0, -2 );

  /**
   * i18n: first %s is comma separated list of languages, second %s - default language.
   * Language names are translated, if they are present in squirrelmail.po file.
   * make sure that you don't use html codes in language name translations
   */
  $msg = '<p>'
    . sprintf(_("Settings adjusted to: %s with %s as default dictionary."),
             '<strong>'.htmlspecialchars($dsp_string).'</strong>',
             '<strong>'.htmlspecialchars(_($lang_default)).'</strong>')
    . '</p>';
} else {
  /**
   * Only one dictionary is selected.
   */
  $msg = '<p>'
    . sprintf(_("Using %s dictionary for spellcheck." ), '<strong>'.htmlspecialchars(_($new_langs[0])).'</strong>')
    . '</p>';
}

/** save settings */
sqspell_saveSettings($new_langs);

sqspell_makePage(_("International Dictionaries Preferences Updated"),
        null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
