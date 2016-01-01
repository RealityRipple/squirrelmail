/**
 * init.js
 *
 * Grabs the text from the SquirrelMail field and submits it to
 * the squirrelspell.
 *
 * @copyright 2001-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 */

/**
 * This is the work function.
 *
 * @param  flag tells the function whether to automatically submit the
 *              form, or wait for user input. True submits the form, while
 *              false doesn't.
 * @return      void 
 */
function sqspell_init(flag){
  textToSpell = opener.document.compose.subject.value + "\n" + opener.document.compose.body.value;
  document.forms[0].sqspell_text.value = textToSpell;
  if (flag) document.forms[0].submit();
}