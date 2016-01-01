<?php

/**
 * forget_me.mod
 *
 * Squirrelspell module
 *
 * This module deletes the words from the user dictionary. Called
 * after EDIT_DIC module.
 *
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $SQSPELL_VERSION, $SQSPELL_APP_DEFAULT;

if (! sqgetGlobalVar('words_ary',$words_ary,SQ_POST) || ! is_array($words_ary)) {
    $words_ary = array();
}

if (! sqgetGlobalVar('sqspell_use_app',$sqspell_use_app,SQ_POST)){
    $sqspell_use_app = $SQSPELL_APP_DEFAULT;
}

/**
 * If something needs to be deleted, then $words_ary will be
 * non-zero length.
 */
if (! empty($words_ary)){
  $lang_words = sqspell_getLang($sqspell_use_app);
  $msg = '<p>'
     . sprintf(_("Deleting the following entries from %s dictionary:"), '<strong>'.$sqspell_use_app.'</strong>')
     . '</p>'
     . "<ul>\n";

  // print list of deleted words
  foreach ($words_ary as $deleted_word) {
    $msg.= '<li>'.sm_encode_html_special_chars($deleted_word)."</li>\n";
  }

  // rebuild dictionary
  $new_words_ary = array();
  foreach ($lang_words as $word){
      if (! in_array($word,$words_ary)) {
          $new_words_ary[]=$word;
      }
  }
  // save it
  sqspell_writeWords($new_words_ary,$sqspell_use_app);
  $msg .= '</ul><p>' . _("All done!") . "</p>\n";
  sqspell_makePage(_("Personal Dictionary Updated"), null, $msg);
} else {
  /**
   * Click on some words first, Einstein!
   */
  sqspell_makePage(_("Personal Dictionary"), null,
          '<p>' . _("No changes requested.") . '</p>');
}

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
