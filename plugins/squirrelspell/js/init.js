/**
 * init.js
 * -------
 * Grabs the text from the SquirrelMail field and submits it to
 * the squirrelspell.
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
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
  textToSpell = opener.document.forms[0].subject.value + "\n" + opener.document.forms[0].body.value;
  document.forms[0].sqspell_text.value = textToSpell;
  if (flag) document.forms[0].submit();
}
