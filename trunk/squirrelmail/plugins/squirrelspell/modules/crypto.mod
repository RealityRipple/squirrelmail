<?php
/**
 * crypto.mod.php 
 * --------------- 
 * Squirrelspell module
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This module handles the encryption/decryption of the user dictionary
 * if the user so chooses from the options page.
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */

/**
 * Declaring globals for E_ALL
 */
global $SQSPELL_CRYPTO;

switch ($_POST['action']){
  case 'encrypt':
    /**
     * Let's encrypt the file and save it in an encrypted format.
     */
    $words=sqspell_getWords();
    /** 
     * Flip the flag so the sqspell_writeWords function knows to encrypt
     * the message before writing it to the disk.
     */
    $SQSPELL_CRYPTO=true;
    /**
     * Call the function that does the actual encryption_decryption.
     */
    sqspell_writeWords($words);
    $msg='<p>'
       .  _("Your personal dictionary has been <strong>encrypted</strong> and is now stored in an <strong>encrypted format</strong>.")
       . '</p>';
  break;
  case 'decrypt':
    /**
     * Let's decrypt the file and save it as plain text.
     */
    $words=sqspell_getWords();
    /** 
     * Flip the flag and tell the sqspell_writeWords() function that we
     * want to save it plaintext.
     */
    $SQSPELL_CRYPTO=false;
    sqspell_writeWords($words);
    $msg='<p>'
       . _("Your personal dictionary has been <strong>decrypted</strong> and is now stored as <strong>clear text</strong>.") 
       . '</p>';
  break;
  
  case "":
    /**
     * Wait, this shouldn't happen! :)
     */
    $msg = "<p>No action requested.</p>";
  break;
}
sqspell_makePage( _("Personal Dictionary Crypto Settings"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 */

?>
