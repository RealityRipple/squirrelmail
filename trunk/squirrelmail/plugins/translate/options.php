<?php
/**
 * options.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Pick your translator to translate the body of incoming mail messages
 *
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../../');

/* SquirrelMail required files. */
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/display_messages.php');
include_once(SM_PATH . 'functions/imap.php');
include_once(SM_PATH . 'plugins/translate/functions.php');

displayPageHeader($color, 'None');

// Save preferences
if (sqgetGlobalVar('submit_translate',$tmp,SQ_POST)) {
    if (sqgetGlobalVar('translate_translate_server',$translate_translate_server,SQ_POST)) {
        setPref($data_dir, $username, 'translate_server', $translate_translate_server);
    } else {
        setPref($data_dir, $username, 'translate_server', $translate_default_engine);
    }

    if (sqgetGlobalVar('translate_translate_location',$translate_translate_location,SQ_POST)) {
        setPref($data_dir, $username, 'translate_location', $translate_translate_location);
    } else {
        setPref($data_dir, $username, 'translate_location', 'center');
    }

    if (sqgetGlobalVar('translate_translate_show_read',$tmp,SQ_POST)) {
        setPref($data_dir, $username, 'translate_show_read', '1');
    } else {
        setPref($data_dir, $username, 'translate_show_read', '');
    }

    if (sqgetGlobalVar('translate_translate_show_send',$tmp,SQ_POST)) {
        setPref($data_dir, $username, 'translate_show_send', '1');
    } else {
        setPref($data_dir, $username, 'translate_show_send', '');
    }

    if (sqgetGlobalVar('translate_translate_same_window',$tmp,SQ_POST)) {
       setPref($data_dir, $username, 'translate_same_window', '1');
    } else {
        setPref($data_dir, $username, 'translate_same_window', '');
    }
}

// Move these calls to separate function
$translate_server = getPref($data_dir, $username, 'translate_server',$translate_default_engine);

$translate_location = getPref($data_dir, $username, 'translate_location');
if ($translate_location == '') {
    $translate_location = 'center';
}
$translate_show_read = getPref($data_dir, $username, 'translate_show_read');
$translate_show_send = getPref($data_dir, $username, 'translate_show_send');
$translate_same_window = getPref($data_dir, $username, 'translate_same_window');

?>
   <table width="95%" align="center" border="0" cellpadding="1" cellspacing="0"><tr><td bgcolor="<?php echo $color[0]; ?>">
      <center><b><?php echo _("Options") . ' - '. _("Translator"); ?></b></center>
   </td></tr></table>

    <?php if (sqgetGlobalVar('submit_translate',$tmp,SQ_POST)) {
        print "<center><h4>"._("Saved Translation Options")."</h4></center>\n";
    }?>

   <p><?php echo _("Your server options are as follows:"); ?></p>

   <ul>
<?php
   translate_showtrad();
?>
   </ul>
   <p>
<?php
   echo _("You also decide if you want the translation box displayed, and where it will be located.") .
        '<form action="'.$PHP_SELF.'" method="post">'.
        '<table border="0" cellpadding="0" cellspacing="2">'.
            '<tr><td align="right" nowrap>' .
             _("Select your translator:") .
             '</td>'.
            '<td><select name="translate_translate_server">';
   translate_showoption();
   echo '</select>' .
       '</td></tr>' .
       '<tr>'.html_tag('td',_("When reading:"),'right','','nowrap').
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
   echo '<tr>'.html_tag('td',_("When composing:"),'right','','nowrap').
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
