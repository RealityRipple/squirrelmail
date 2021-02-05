<?php

/**
 * options_main.mod
 *
 * Squirrelspell module
 *
 * Default page called when accessing SquirrelSpell's options.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2021 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $SQSPELL_APP, $main_options_changed_message;

if (!empty($main_options_changed_message))
   $msg = $main_options_changed_message;
else
   $msg = '';

$msg .= '<p>'
  . _("Please choose which options you wish to set up:")
  . '</p>'
  . '<ul>'
  . '<li><a href="sqspell_options.php?MOD=edit_dic">'
  . _("Edit your personal dictionary") . '</a></li>';
/**
 * See if more than one dictionary is defined system-wide.
 * If so, let the user choose his preferred ones.
 */
if (sizeof($SQSPELL_APP)>1) {
  $msg .= '<li><a href="sqspell_options.php?MOD=lang_setup">'
    . _("Set up international dictionaries")
    . "</a></li>\n";
}
/**
 * See if MCRYPT is available.
 * If so, let the user choose whether s/he wants to encrypt the
 * personal dictionary file.
 */
if (function_exists("mcrypt_generic")) {
  $msg .= '<li><a href="sqspell_options.php?MOD=enc_setup">'
    . _("Encrypt or decrypt your personal dictionary")
    . "</a></li>\n";
} else {
  $msg .= '<li>'
    . _("Personal dictionary encryption options are not available") 
    . '</li>';
}
$msg .= "</ul>\n";



// add checkbox to enable/disable the spellcheck button on compose screen
//
$sqspell_show_button = getPref($data_dir, $username, 'sqspell_show_button', 1);
$msg .= '<form method="post">'
  . '<input type="hidden" name="MOD" value="change_main_options" />'
  . '<input type="hidden" name="smtoken" value="' . sm_generate_security_token() . '" />'
  . '<p>'
  . '<input type="checkbox" id="sqspell_show_button" name="sqspell_show_button" value="1"';
if ($sqspell_show_button) {
  $msg .= ' checked="checked"';
}
$msg .= ' /><label for="sqspell_show_button"> '
     . sprintf(_("Show \"%s\" button when composing"), _("Check Spelling"))
     . "</label>\n";
$msg .= " <input type=\"submit\" value=\" "
  . _("Make these changes") . " \" /></p></form>";


sqspell_makePage( _("SquirrelSpell Options Menu"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
