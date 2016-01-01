<?php

/**
 * options.php
 *
 * Pick your translator to translate the body of incoming mail messages
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');

/** Plugin functions */
include_once(SM_PATH . 'plugins/translate/functions.php');

displayPageHeader($color);

?>
   <table width="95%" align="center" border="0" cellpadding="1" cellspacing="0"><tr><td bgcolor="<?php echo $color[0]; ?>">
      <div style="text-align: center;"><b><?php echo _("Options") . ' - '. _("Translator"); ?></b></div>
   </td></tr></table>

   <p><?php echo _("Your server options are as follows:"); ?></p>

   <ul>
<?php
   translate_showtrad();
?>
   </ul>
   <p>
<?php
   echo _("You also decide if you want the translation box displayed, and where it will be located.") .
        '<form action="'.sqm_baseuri().'src/options.php" method="post">'.
        '<input type="hidden" name="optmode" value="submit" />' .
        '<input type="hidden" name="optpage" value="translate" />' .
        '<table border="0" cellpadding="0" cellspacing="2">'.
            '<tr><td align="right" style="white-space: nowrap;">' .
             _("Select your translator:") .
             '</td>'.
            '<td><select name="translate_translate_server">';
   translate_showoption();
   echo '</select>' .
       '</td></tr>' .
       '<tr>'.html_tag('td',_("When reading:"),'right','','style="white-space: nowrap;"').
       '<td><input type="checkbox" name="translate_translate_show_read"';
   if ($translate_show_read)
       echo ' checked="checked"';
   echo ' /> - ' . _("Show translation box") .
       ' <select name="translate_translate_location">';
   translate_showoption_internal('location', 'left', _("to the left"));
   translate_showoption_internal('location', 'center', _("in the center"));
   translate_showoption_internal('location', 'right', _("to the right"));
   echo '</select><br />'.
       '<input type="checkbox" name="translate_translate_same_window"';
   if ($translate_same_window)
       echo ' checked="checked"';
   echo ' /> - ' . _("Translate inside the SquirrelMail frames").
       "</td></tr>\n";

if (!$disable_compose_translate) {
   echo '<tr>'.html_tag('td',_("When composing:"),'right','','style="white-space: nowrap;"').
         '<td><input type="checkbox" name="translate_translate_show_send"';
   if ($translate_show_send)
      echo ' checked="checked"';
   echo ' /> - ' . _("Not yet functional, currently does nothing") .
      "</td></tr>\n";
}
?>
<tr><td></td><td>
<input type="submit" value="<?php echo _("Submit"); ?>" name="submit_translate" />
</td></tr>
</table>
</form>
</body></html>
