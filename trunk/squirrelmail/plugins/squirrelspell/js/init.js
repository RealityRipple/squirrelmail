/**
   INIT.JS
   -------
   Grabs the text from the SquirrelMail field and submits it to
   the squirrelspell.
   								**/
function sqspell_init(flag){
  // flag tells the function whether to automatically submit the form, or
  // wait for user input. True submits the form, while False doesn't.
  textToSpell = opener.document.forms[0].subject.value + "\n" + opener.document.forms[0].body.value;
  document.forms[0].sqspell_text.value = textToSpell;
  if (flag) document.forms[0].submit();
}
