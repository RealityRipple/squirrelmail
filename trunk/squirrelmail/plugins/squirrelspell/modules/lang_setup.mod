<?php
/**
 * lang_setup.mod
 * ---------------
 * Squirrelspell module
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This module displays available dictionaries to the user and lets 
 * him/her choose which ones s/he wants to check messages with.     
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */
    
global $SQSPELL_APP;
    
$msg = '<p>'
  . _("Please check any available international dictionaries which you would like to use when spellchecking:")
  . '</p>'
  . '<form method="post">'
  . '<input type="hidden" name="MOD" value="lang_change" />'
  . '<blockquote><p>';
/**
 * Present a nice listing.
 */
$langs = sqspell_getSettings(null);
$add = '<p>'
  . _("Make this dictionary my default selection:")
  . " <select name=\"lang_default\">\n";
while (list($avail_lang, $junk) = each($SQSPELL_APP)){
  $msg .= "<input type=\"checkbox\" name=\"use_langs[]\" "
    . "value=\"$avail_lang\"";
  if (in_array($avail_lang, $langs)) {
    $msg .= ' checked="checked"';
  }
  $msg .= ' /> ' . _($avail_lang) . "<br />\n";
  $add .= "<option";
  if ($avail_lang==$langs[0]) {
    $add .= ' selected="selected"';
  }
  $add .= " value=\"$avail_lang\" >" . _($avail_lang) . "</option>\n";
}
$msg .= "</p>\n" . $add . "</select>\n";
$msg .= "</p></blockquote><p><input type=\"submit\" value=\" " 
  . _("Make these changes") . " \" /></p>";
sqspell_makePage(_("Add International Dictionaries"), null, $msg); 

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
 
?>