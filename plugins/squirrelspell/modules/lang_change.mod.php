<?php

   /**
    **  LANG_CHANGE.MOD.PHP -- Squirrelspell module
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This module changes the international dictionaries selection
    **  for the user. Called after LANG_SETUP module.                
    **
    **  $Id$
    **/

    // For poor wretched souls with E_ALL.
    global $use_langs, $lang_default, $SQSPELL_APP_DEFAULT;
    
    $words = sqspell_getWords();
    if (!$words) {
        $words = sqspell_makeDummy();
    }
    $langs = sqspell_getSettings($words);
    if (sizeof($use_langs)){
        // See if the user clicked any options on the previous page.
        if (sizeof($use_langs)>1){
            // See if s/he wants more than one dictionary.
            if ($use_langs[0]!=$lang_default){
                // See if we need to juggle the order of the dictionaries
                // to make the default dictionary first in line.
                if (in_array($lang_default, $use_langs)){
                    // see if the user was dumb and chose a default dictionary
                    // to be something other than the ones he selected.
                    $hold = array_shift($use_langs);
                    $lang_string = join(", ", $use_langs);
                    $lang_string = str_replace("$lang_default", "$hold", $lang_string);
                    $lang_string = $lang_default . ", " . $lang_string;
                } else {
                    // Yes, he is dumb.
                    $lang_string = join(', ', $use_langs);
                }
            } else {
                // No need to juggle the order -- preferred is already first.
                $lang_string = join(', ', $use_langs);
            }
        } else {
            // Just one dictionary, please.
            $lang_string = $use_langs[0];
        }
        $lang_array = explode( ',', $lang_string );
        $dsp_string = '';
        foreach( $lang_array as $a) {
            $dsp_string .= _(trim($a)) . ', ';
        }
        $dsp_string = substr( $dsp_string, 0, -2 );
        $msg = '<p>'.
               sprintf( _("Settings adjusted to: <strong>%s</strong> with <strong>%s</strong> as default dictionary."), $dsp_string, _($lang_default) ) .
               '</p>';
    } else {
        // No dictionaries selected. Use system default.
        $msg = '<p>'.
               sprintf( _("Using <strong>%s</strong> dictionary (system default) for spellcheck." ), $SQSPELL_APP_DEFAULT ) .
               '</p>';
        $lang_string = $SQSPELL_APP_DEFAULT;
    }
    $old_lang_string = join(", ", $langs);
    $words = str_replace("# LANG: $old_lang_string", "# LANG: $lang_string", $words);
    // write it down where the sun don't shine.
    sqspell_writeWords($words);
    sqspell_makePage(_("International Dictionaries Preferences Updated"), null, $msg);
 
?>