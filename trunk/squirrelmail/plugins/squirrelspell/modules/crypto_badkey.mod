<?php

/**
 * crypto_badkey.mod
 *
 * Squirrelspell module
 *
 * This module tries to decrypt the user dictionary with a newly provided
 * old password, or erases the file if everything else fails. :(
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2013 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/** get script name*/
sqgetGlobalVar('SCRIPT_NAME',$SCRIPT_NAME,SQ_SERVER);

if (! sqgetGlobalVar('delete_words',$delete_words,SQ_POST)){ 
  $delete_words = 'OFF';
}
if (! sqgetGlobalVar('old_key',$old_key,SQ_POST)) {
  $old_key=null;
}

if (! sqgetGlobalVar('old_setup',$temp,SQ_POST)) {
  $old_setup = false;
} else {
  $old_setup = true;
}

/**
 * Displays information about deleted dictionary
 * @since 1.5.1 (sqspell 0.5)
 */
function sqspell_dict_deleted() {
  global $SCRIPT_NAME;
  /**
   * See where we were called from -- pop-up window or options page
   * and call whichever wrapper is appropriate.
   * I agree, this is dirty.
   * TODO: make it so it's not dirty.
   * TODO: add upgrade handing
   */
  if (strstr($SCRIPT_NAME, 'sqspell_options')){
    $msg='<p>' . _("Your personal dictionary was erased.") . '</p>';
    sqspell_makePage(_("Dictionary Erased"), null, $msg);
  } else {
    /**
     * The _("Your....") has to be on one line. Otherwise xgettext borks
     * on getting the strings.
     */
    $msg = '<p>'
      . _("Your personal dictionary was erased. Please close this window and click \"Check Spelling\" button again to start your spellcheck over.")
      . '</p> '
      . '<p align="center"><form>'
      . '<input type="button" value=" '
      . _("Close this Window") . ' " onclick="self.close()" />'
      . '</form></p>';
    sqspell_makeWindow(null, _("Dictionary Erased"), null, $msg);
  }
  exit;
}

/**
 * Displays information about reencrypted dictionary
 * @since 1.5.1 (sqspell 0.5)
 */
function sqspell_dict_reencrypted() {
  global $SCRIPT_NAME;
  /**
   * See where we are and call a necessary GUI-wrapper.
   * Also dirty.
   * TODO: Make this not dirty.
   * TODO: add upgrade handing
   */
  if (strstr($SCRIPT_NAME, 'sqspell_options')){
    $msg = '<p>'
      . _("Your personal dictionary was re-encrypted successfully. Now return to the &quot;SpellChecker options&quot; menu and make your selection again." )
      . '</p>';
    sqspell_makePage(_("Successful re-encryption"), null, $msg);
  } else {
    $msg = '<p>'
        . _("Your personal dictionary was re-encrypted successfully. Please close this window and click \"Check Spelling\" button again to start your spellcheck over.")
        . '</p><form><p align="center"><input type="button" value=" '
        . _("Close this Window") . ' "'
        . 'onclick="self.close()" /></p></form>';
    sqspell_makeWindow(null, _("Dictionary re-encrypted"), null, $msg);
  }
  exit;
}

// main code 
if (! $old_setup && $delete_words=='ON') {
  if (sqgetGlobalVar('dict_lang',$dict_lang,SQ_POST)) {
    sqspell_deleteWords($dict_lang);
    sqspell_dict_deleted();
  }
} elseif ($delete_words=='ON'){
  /**
   * $delete_words is passed via the query_string. If it's set, then
   * the user asked to delete the file. Erase the bastard and hope
   * this never happens again.
   */
  sqspell_deleteWords_old();
  sqspell_dict_deleted();
}

if (! $old_setup && $old_key) {
  if (sqgetGlobalVar('dict_lang',$dict_lang,SQ_POST)) {
    $words=sqspell_getLang($dict_lang);
    sqspell_writeWords($words,$dict_lang);
    sqspell_dict_reencrypted();
  }
} elseif ($old_key){
  /**
   * User provided another key to try and decrypt the dictionary.
   * Call sqspell_getWords. If this key fails, the function will
   * handle it.
   */
  $words=sqspell_getWords_old();
  /**
   * It worked! Pinky, you're a genius!
   * Write it back this time encrypted with a new key.
   */
  sqspell_writeWords_old($words);
  sqspell_dict_reencrypted();
}

// TODO: handle broken calls

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
