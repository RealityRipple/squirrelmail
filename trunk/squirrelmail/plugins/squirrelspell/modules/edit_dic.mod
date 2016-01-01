<?php

/**
 * edit_dic.mod
 *
 * Squirrelspell module
 *
 * This module lets the user edit his/her personal dictionary.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

global $color;

$pre_msg = '<p>'
  . _("Please check any words you wish to delete from your dictionary.")
  . "</p>\n";
$pre_msg .= "<table border=\"0\" width=\"95%\" align=\"center\">\n";

/**
 * Get how many dictionaries this user has defined.
 */
$langs=sqspell_getSettings();

foreach ($langs as $lang) {
  /**
   * Get all words from this language dictionary.
   */
  $lang_words = sqspell_getLang($lang);
  if (! empty($lang_words)){
    /**
     * There are words in this dictionary. If this is the first
     * language we're processing, prepend the output with the
     * "header" message.
     */
    if (!isset($msg) || !$msg) {
      $msg = $pre_msg;
    }
    $msg .= "<tr bgcolor=\"$color[0]\" align=\"center\"><th>"
      . sprintf( _("%s dictionary"), $lang ) . '</th></tr>'
      . '<tr><td align="center">'
      . '<form method="post">'
      . '<input type="hidden" name="MOD" value="forget_me" />'
      . '<input type="hidden" name="sqspell_use_app" value="'
      . $lang . '" />'
      . '<table border="0" width="95%" align="center">'
      . '<tr>'
      . "<td valign=\"top\">\n";
    /**
     * Do some fancy stuff to separate the words into three
     * columns.
     */
    for ($j=0; $j<sizeof($lang_words); $j++){
      if ($j==intval(sizeof($lang_words)/3)
          || $j==intval(sizeof($lang_words)/3*2)){
        $msg .= "</td><td valign=\"top\">\n";
      }
      $msg .= "<input type=\"checkbox\" name=\"words_ary[]\" "
        . 'value="'.sm_encode_html_special_chars($lang_words[$j]). '" id="words_ary_'
        . $j . '" /> <label for="words_ary_' . $j .'">'
        . sm_encode_html_special_chars($lang_words[$j]) . "</label><br />\n";
    }
    $msg .= '</td></tr></table></td></tr>'
      . "<tr bgcolor=\"$color[0]\" align=\"center\"><td>"
      . '<input type="submit" value="' . _("Delete checked words")
      . '" /></form>'
      . '</td></tr><tr><td><hr />'
      . "</td></tr>\n";
  }
}
/**
 * Check if all dictionaries were empty.
 */
if (! isset($msg)) {
  $msg = '<p>' . _("No words in your personal dictionary.") . '</p>';
} else {
  $msg .= '</table>';
}
sqspell_makePage(_("Edit your Personal Dictionary"), null, $msg);

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */
