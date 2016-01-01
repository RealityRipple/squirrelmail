<?php

/**
 * crypto.mod
 *
 * Squirrelspell module
 *
 * This module handles the encryption/decryption of the user dictionary
 * if the user so chooses from the options page.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/**
 * Declaring globals for E_ALL
 */
global $SQSPELL_CRYPTO;

$langs=sqspell_getSettings();

if (! sqgetGlobalVar('encaction', $crypt_action, SQ_POST)) {
    $crypt_action = 'noaction';
}

switch ($crypt_action){
    case 'encrypt':
        $SQSPELL_CRYPTO_ORIG=$SQSPELL_CRYPTO;

        foreach ($langs as $lang) {
            $SQSPELL_CRYPTO = $SQSPELL_CRYPTO_ORIG;
            /**
             * Let's encrypt the file and save it in an encrypted format.
             */
            $words=sqspell_getLang($lang);
            /**
             * Flip the flag so the sqspell_writeWords function knows to encrypt
             * the message before writing it to the disk.
             */
            $SQSPELL_CRYPTO=true;
            /**
             * Call the function that does the actual encryption_decryption.
             */
            sqspell_writeWords($words,$lang);
        }
        $msg='<p>'
            . _("Your personal dictionary has been encrypted and is now stored in an encrypted format.")
            . '</p>';
    break;
    case 'decrypt':
        $SQSPELL_CRYPTO_ORIG=$SQSPELL_CRYPTO;

        foreach ($langs as $lang) {
            $SQSPELL_CRYPTO = $SQSPELL_CRYPTO_ORIG;
            /**
             * Let's encrypt the file and save it in an encrypted format.
             */
            $words=sqspell_getLang($lang);
            /**
             * Flip the flag so the sqspell_writeWords function knows to decrypt
             * the message before writing it to the disk.
             */
            $SQSPELL_CRYPTO=false;
            /**
             * Call the function that does the actual encryption_decryption.
             */
            sqspell_writeWords($words,$lang);
        }
        $msg='<p>'
            . _("Your personal dictionary has been decrypted and is now stored as plain text.")
            . '</p>';
    break;
    default:
        /**
         * Wait, this shouldn't happen! :)
         */
        $msg = '<p>'._("No action requested.").'</p>';
    break;
}
sqspell_makePage( _("Personal Dictionary Crypto Settings"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
