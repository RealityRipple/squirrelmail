<?php
/**
   ENC_SETUP.MOD.PHP
   -----------------
   This module shows the user a nice invitation to encrypt or decypt
   his/her personal dictionary and explains the caveats of such a decision.
   								**/
// Something for our friends with E_ALL for error_reporting:
global $SQSPELL_CRYPTO; 

$words=sqspell_getWords();
if ($SQSPELL_CRYPTO){
 // Current format is encrypted.
 $msg = "<p>Your personal dictionary is <strong>currently encrypted</strong>. This 
 helps protect your privacy in case the web-mail system gets compromized and your 
 personal dictionary ends up stolen. It is currently encrypted with the password
 you use to access your mailbox, making it hard for anyone to see what is stored
 in your personal dictionary.</p>
 <p><strong>ATTENTION:</strong> If you forget your password, your personal dictionary
 will become unaccessible, since it can no longer be decrypted.
 If you change your mailbox password, SquirrelSpell will recognize it and prompt you for
 your old password in order to re-encrypt the dictionary with a new key.</p>
 <form method=\"post\" onsubmit=\"return checkMe()\">
  <input type=\"hidden\" name=\"MOD\" value=\"crypto\">
  <p align=\"center\"><input type=\"checkbox\" name=\"action\" value=\"decrypt\"> Please decrypt my personal
  dictionary and store it in a clear-text format.</p>
  <p align=\"center\"><input type=\"submit\" value=\" Change crypto settings \"></p>
 </form>
 ";
} else {
 // current format is clear text.
 $msg = "<p>Your personal dictionary is <strong>currently not encrypted</strong>.
 You may wish to encrypt your personal dictionary to protect your privacy in case
 the webmail system gets compromized and your personal dictionary file gets stolen.
 When encrypted, the file's contents look garbled and are hard to decrypt without
 knowing the correct key (which is your mailbox password).</p>
 <strong>ATTENTION:</strong> If you decide to encrypt your personal dictionary,
 you must remember that it gets &quot;hashed&quot; with your mailbox password. If
 you forget your mailbox password and the administrator changes it to a new value,
 your personal dictionary will become useless and will have to be created anew.
 However, if you or your system administrator change your mailbox password but you
 still have the old password at hand, you will be able to enter the old key to
 re-encrypt the dictionary with the new value.</p>
 <form method=\"post\" onsubmit=\"return checkMe()\">
  <input type=\"hidden\" name=\"MOD\" value=\"crypto\">
  <p align=\"center\"><input type=\"checkbox\" name=\"action\" value=\"encrypt\"> Please encrypt my personal
  dictionary and store it in an encrypted format.</p>
  <p align=\"center\"><input type=\"submit\" value=\" Change crypto settings \"></p>
 </form>
 ";
}
 sqspell_makePage("Personal Dictionary Crypto Settings", "crypto_settings.js", $msg);
?>
