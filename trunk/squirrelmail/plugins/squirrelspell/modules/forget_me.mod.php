<?php

   /**
    **  FORGET_ME.MOD.PHP -- Squirrelspell module
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This module deletes the words from the user dictionary. Called
    **  after EDIT_DIC module.                                        
    **
    **  $Id$
    **/

    // Make it two packs of Guinness and a bag of pistachios, fidian. :)
    global $words_ary, $sqspell_use_app, $SQSPELL_VERSION;
    if (sizeof($words_ary)){
        // something needs to be deleted.
        $words=sqspell_getWords();
        $lang_words = sqspell_getLang($words, $sqspell_use_app);
        $msg = '<p>'.
               sprintf( _("Deleting the following entries from <strong>%s</strong> dictionary:", $sqspell_use_app ) .
               '</p>'.
               "<ul>\n";
        for ($i=0; $i<sizeof($words_ary); $i++){
            // remove word by word...
            $lang_words=str_replace("$words_ary[$i]\n", "", $lang_words);
            $msg .= "<li>$words_ary[$i]</li>\n";
        }
        $new_words_ary=split("\n", $lang_words);
        // Wipe this lang, if only 2 members in array (no words left).
        if (sizeof($new_words_ary)<=2) {
            $lang_words='';
        }
        $new_lang_words = $lang_words;
        // process the stuff and write the dic back.
        $langs=sqspell_getSettings($words);
        $words_dic = "# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# Last Revision: " . date("Y-m-d") . "\n# LANG: " . join(", ", $langs) . "\n";
        for ($i=0; $i<sizeof($langs); $i++){
            if ($langs[$i]==$sqspell_use_app) {
              $lang_words = $new_lang_words;
            } else {
                $lang_words = sqspell_getLang($words, $langs[$i]);
            }
            if ($lang_words) {
                $words_dic .= $lang_words;
            }
        }
        $words_dic .= "# End\n";
        sqspell_writeWords($words_dic);
        $msg .= '</ul>' .
                '<p>' _("All done!") . "</p>\n";
        sqspell_makePage(_("Personal Dictionary Updated"), null, $msg);
    } else {
        // Click on some words first, Einstein!
        sqspell_makePage(_("Personal Dictionary"), null, '<p>' . _("No changes requested.") . '</p>');
    }
    
?>