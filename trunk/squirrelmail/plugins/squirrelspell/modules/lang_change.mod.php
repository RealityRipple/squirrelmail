<?php
/**
   LANG_CHANGE.MOD.PHP
   --------------------
   This module changes the international dictionaries selection
   for the user. Called after LANG_SETUP module.
   								**/
 // For poor wretched souls with E_ALL.
 global $use_langs, $lang_default, $SQSPELL_APP_DEFAULT;
 
 $words = sqspell_getWords();
 if (!$words) $words = sqspell_makeDummy();
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
     $lang_string = join(", ", $use_langs);
    }
   } else {
    // No need to juggle the order -- preferred is already first.
    $lang_string = join(", ", $use_langs);
   }
  } else {
   // Just one dictionary, please.
   $lang_string = $use_langs[0];
  }
  $msg = "<p>Settings adjusted to: <strong>$lang_string</strong> with 
  <strong>$lang_default</strong> as default dictionary.</p>";
 } else {
  // No dictionaries selected. Use system default.
  $msg = "<p>Using <strong>$SQSPELL_APP_DEFAULT</strong> dictionary (system default)
  for spellcheck.</p>";
  $lang_string = $SQSPELL_APP_DEFAULT;
 }
 $old_lang_string = join(", ", $langs);
 $words = str_replace("# LANG: $old_lang_string", "# LANG: $lang_string", $words);
 // write it down where the sun don't shine.
 sqspell_writeWords($words);
 sqspell_makePage("International Dictionaries Preferences Updated", null, $msg);
?>
