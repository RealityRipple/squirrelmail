<?php

/**
   CRYPTO.MOD.PHP
   --------------
   This module handles the encryption/decryption of the user dictionary
   if the user so chooses from the options page.
   								**/
// Declaring globals for E_ALL
global $action, $SQSPELL_CRYPTO;
switch ($action){
 case "encrypt":
  // Let's encrypt the file.
  $words=sqspell_getWords();
  // flip the flag.
  $SQSPELL_CRYPTO=true;
  sqspell_writeWords($words);
  $msg="<p>Your personal dictionary has been <strong>encrypted</strong> and is now stored in an <strong>encrypted format</strong>.</p>";
 break;

 case "decrypt":
  // Decrypt the file and save plain text.
  $words=sqspell_getWords();
  // flip the flag.
  $SQSPELL_CRYPTO=false;
  sqspell_writeWords($words);
  $msg="<p>Your personal dictionary has been <strong>decrypted</strong> and is now stored as <strong>clear text</strong>.</p>";
 break;
 
 case "":
  // Wait, this shouldn't happen! :)
  $msg = "<p>No action requested.</p>";
 break;
}
 sqspell_makePage("Personal Dictionary Crypto Settings", null, $msg);
?>
