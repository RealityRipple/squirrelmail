<?php
/**
   CRYPTO_BADKEY.MOD.PHP
   ---------------------
   This module tries to decrypt the user dictionary with a newly provided
   old password, or erases the file if everything else fails. :(
   								**/
 // Just for fidian! :)
 global $delete_words, $SCRIPT_NAME, $old_key;
 if ($delete_words=="ON"){
  // All attemts to decrypt the file were futile. Erase the bastard and
  // hope this never happens again.
  sqspell_deleteWords(); 
  // See where we were called from -- pop-up window or options page
  // and call whichever wrapper is appropriate.
  if (strstr($SCRIPT_NAME, "sqspell_options")){
   $msg="<p>Your personal dictionary was erased.</p>";
   sqspell_makePage("Dictionary Erased", null, $msg);
  } else {
   $msg = "<p>Your personal dictionary was erased. Please close this window and
   click \"Check Spelling\" button again to start your spellcheck over.</p>
   <p align=\"center\"><form>
   <input type=\"button\" value=\"  Close this Window \" onclick=\"self.close()\">
   </form></p>";
   sqspell_makeWindow(null, "Dictionary Erased", null, $msg);
  }
  exit;
 }

 if ($old_key){
  // User provided another key to try and decrypt the dictionary.
  // call sqspell_getWords. If this key fails, the function will
  // handle it.
  $words=sqspell_getWords();
  // It worked! Pinky, you're a genius!
  // Write it back this time encrypted with a new key.
  sqspell_writeWords($words);
  // See where we are and call a necessary GUI-wrapper.
  if (strstr($SCRIPT_NAME, "sqspell_options")){
   $msg="<p>Your personal dictionary was re-encrypted successfully. Now
   return to the &quot;SpellChecker options&quot; menu and make your selection
   again.</p>";
   sqspell_makePage("Successful Re-encryption", null, $msg);
  } else {
   $msg = "<p>Your personal dictionary was re-encrypted successfully. Please
   close this window and click \"Check Spelling\" button again to start your
   spellcheck over.</p>
   <form><p align=\"center\"><input type=\"button\" value=\"  Close this Window  \"
   onclick=\"self.close()\"></p></form>";
   sqspell_makeWindow(null, "Dictionary re-encrypted", null, $msg);
  }
  exit;
 }
?>
