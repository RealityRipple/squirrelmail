/**
   DECRYPT_ERROR.JS
   -----------------
   Some client-side form-checks. Trivial stuff.
   								**/

function AYS(){
  if (document.forms[0].delete_words.checked && document.forms[0].old_key.value){
    alert ("You can either delete your dictionary or type in the old password. Not both.");
    return false;
  }

  if (!document.forms[0].delete_words.checked && !document.forms[0].old_key.value){
    alert("First make a choice.");
    return false;
  }
  if (document.forms[0].delete_words.checked)
    return confirm("This will delete your personal dictionary file. Proceed?");
  return true;
}

