<?php
/**
   LANG_SETUP.MOD.PHP
   ------------------
   This module displays available dictionaries to the user and lets
   him/her choose which ones s/he wants to check messages with.
   								**/
 // Making sure Sqspell doesn't barf when working with E_ALL
 global $SQSPELL_APP;
 
 $msg = "<p>Please check any available international dictionaries which you would like 
  to use when spellchecking:</p>
  <form method=\"post\">
  <input type=\"hidden\" name=\"MOD\" value=\"lang_change\">
  <blockquote><p>
 ";
 $langs = sqspell_getSettings(null);
 $add = "<p>Make this dictionary my default selection: <select name=\"lang_default\">\n";
 while (list($avail_lang, $junk) = each($SQSPELL_APP)){
  $msg .= "<input type=\"checkbox\" name=\"use_langs[]\" value=\"$avail_lang\"";
  if (in_array($avail_lang, $langs)) $msg .= " checked";
  $msg .= ">$avail_lang<br>\n";
  $add .= "<option";
  if ($avail_lang==$langs[0]) $add .= " selected";
  $add .= ">$avail_lang</option>\n";
 }
 $msg .= "</p>\n" . $add . "</select>\n";
 $msg .= "</p></blockquote><p><input type=\"submit\" value=\" Make these changes \"></p>";
 sqspell_makePage("Add International Dictionaries", null, $msg); 
?>
