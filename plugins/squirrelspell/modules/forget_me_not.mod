<?php

/**
 * forget_me_not.mod
 *
 * Squirrelspell module
 *
 * This module saves the added words into the user dictionary. Called
 * after CHECK_ME module.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $SQSPELL_VERSION, $SQSPELL_APP_DEFAULT;

if (! sqgetGlobalVar('words',$words,SQ_POST)) {
    $words='';
}
if (! sqgetGlobalVar('sqspell_use_app',$sqspell_use_app,SQ_POST)) {
    $sqspell_use_app = $SQSPELL_APP_DEFAYLT;
}

/**
 * Because of the nature of Javascript, there is no way to efficiently
 * pass an array. Hence, the words will arrive as a string separated by
 * "%". To get the array, we explode the "%"'s.
 * Dirty: yes. Is there a better solution? Let me know. ;)
 */
$new_words = explode("%",$words);
/**
 * Load the user dictionary and see if there is anything in it.
 */
$old_words=sqspell_getLang($sqspell_use_app);
if (empty($old_words)){
    $word_dic = $new_words;
} else {
    foreach($new_words as $new_word) {
        $old_words[]=$new_word;
    }
    // make sure that dictionary contains only unique values
    $word_dic = array_unique($old_words);
}

/**
 * Write out the file
 */
sqspell_writeWords($word_dic,$sqspell_use_app);
/**
 * display the splash screen, then close it automatically after 2 sec.
 */
$onload = "setTimeout('self.close()', 2000)";
$msg = '<form onsubmit="return false"><div style="text-align: center;">'
   . '<input type="submit" value="  '
   . _("Close") . '  " onclick="self.close()" /></div></form>';
sqspell_makeWindow($onload, _("Personal Dictionary Updated"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
