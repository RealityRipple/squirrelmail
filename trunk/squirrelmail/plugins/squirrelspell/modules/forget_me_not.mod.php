<?php 

   /**
    **  FORGET_ME_NOT.MOD.PHP -- Squirrelspell module
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This module saves the added words into the user dictionary. Called
    **  after CHECK_ME module.                                            
    **
    **  $Id$
    **/

    // For our friends with E_ALL.
    global $words, $SQSPELL_VERSION, $SQSPELL_APP_DEFFAULT, $sqspell_use_app;
    
    $new_words = ereg_replace("%", "\n", $words);
    
    // Load the user dictionary.
    $words=sqspell_getWords();
    
    if (!$words){
        // First time.
        $words_dic="# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# Last Revision: " . date("Y-m-d") . "\n# LANG: $SQSPELL_APP_DEFAULT\n# $SQSPELL_APP_DEFAULT\n";
        $words_dic .= $new_words . "# End\n";
    } else {
        // Do some fancy stuff in order to save the dictionary and not mangle the
        // rest.
        $langs=sqspell_getSettings($words);
        $words_dic = "# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# Last Revision: " . date("Y-m-d") . "\n# LANG: " . join(", ", $langs) . "\n";
        for ($i=0; $i<sizeof($langs); $i++){
            $lang_words=sqspell_getLang($words, $langs[$i]);
            if ($langs[$i]==$sqspell_use_app){
               if (!$lang_words) {
                   $lang_words="# $langs[$i]\n";
               }
               $lang_words .= $new_words;
            }
            $words_dic .= $lang_words;
        }
        $words_dic .= "# End\n";
    }
    
    // Write out the file
    sqspell_writeWords($words_dic);
    // display the splash screen, then close it automatically after 2 sec.
    $onload = "setTimeout('self.close()', 2000)";
    $msg = '<form onsubmit="return false"><div align="center"><input type="submit" value="  '.
           _("Close") . '  " onclick="self.close()"></div></form>';
    sqspell_makeWindow($onload, _("Personal Dictionary Updated"), null, $msg);
    
?>