/**
   CRYPTO_SETTINGS.JS
   -------------------
   Some client-side checks. Nothing fancy.
   								**/

function checkMe(){
 if (!document.forms[0].action.checked){
	alert ("Please make a selection first.");
	return false;
 }
 if (document.forms[0].action.value=="encrypt")
 	cmsg="This will encrypt your personal dictionary and store it in an encrypted format. Proceed?";
 if (document.forms[0].action.value=="decrypt")
 	cmsg="This will decrypt your personal dictionary and store it in a clear-text format. Proceed?";
 return confirm(cmsg);
}
