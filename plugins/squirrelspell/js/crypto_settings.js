/**
 * crypto_settings.js
 * -------------------
 * Some client-side checks. Nothing fancy.
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */

/**
 * This function is the only thing. It is called on form submit and
 * asks the user some questions.
 */
function checkMe(){
  if (!document.forms[0].action.checked){
    alert (ui_makesel);
    return false;
  }
  if (document.forms[0].action.value=="encrypt")
    cmsg=ui_encrypt;
  if (document.forms[0].action.value=="decrypt")
    cmsg=ui_decrypt;
  return confirm(cmsg);
}
