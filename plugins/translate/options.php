<?php

/**
 * options.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Pick your translator to translate the body of incoming mail messages
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');

    displayPageHeader($color, 'None');

    if (isset($_POST['submit_translate']) && $_POST['submit_translate'] ) {
        if (isset($_POST['translate_translate_server'])) {
            setPref($data_dir, $username, 'translate_server', $_POST['translate_translate_server']);
        } else {
            setPref($data_dir, $username, 'translate_server', 'babelfish');
        }

        if (isset($_POST['translate_translate_location'])) {
            setPref($data_dir, $username, 'translate_location', $_POST['translate_translate_location']);
        } else {
            setPref($data_dir, $username, 'translate_location', 'center');
        }

        if (isset($_POST['translate_translate_show_read'])) {
            setPref($data_dir, $username, 'translate_show_read', '1');
        } else {
            setPref($data_dir, $username, 'translate_show_read', '');
        }

        if (isset($_POST['translate_translate_show_send'])) {
            setPref($data_dir, $username, 'translate_show_send', '1');
        } else {
            setPref($data_dir, $username, 'translate_show_send', '');
        }

        if (isset($_POST['translate_translate_same_window'])) {
           setPref($data_dir, $username, 'translate_same_window', '1');
        } else {
            setPref($data_dir, $username, 'translate_same_window', '');
        }
    }

    $translate_server = getPref($data_dir, $username, 'translate_server');
    if ($translate_server == '') {
    $translate_server = 'babelfish';
    }
    $translate_location = getPref($data_dir, $username, 'translate_location');
    if ($translate_location == '') {
    $translate_location = 'center';
    }
    $translate_show_read = getPref($data_dir, $username, 'translate_show_read');
    $translate_show_send = getPref($data_dir, $username, 'translate_show_send');
    $translate_same_window = getPref($data_dir, $username, 'translate_same_window');
  

   function ShowOption($Var, $value, $Desc)
   {
       $Var = 'translate_' . $Var;

       global $$Var;

       echo '<option value="' . $value . '"';
       if ($$Var == $value)
       {
           echo ' SELECTED';
       }
       echo '>' . $Desc . "</option>\n";
   }

    function ShowTrad( $tit, $com, $url ) {

        echo "<li><b>$tit</b> - ".
             $com .
             "[ <a href=\"$url\" target=\"_blank\">$tit</a> ]</li>";

    }

?>
   <table width="95%" align=center border=0 cellpadding=1 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . ' - '. _("Translator"); ?></b></center>
   </td></tr></table>

    <?php if (isset($_POST['submit_translate']) && $_POST['submit_translate'] ) {
        print "<center><h4>"._("Saved Translation Options")."</h4></center>\n";
    }?>

   <p><?php echo _("Your server options are as follows:"); ?></p>

   <ul>
<?php
    ShowTrad( 'Babelfish',
              _("19 language pairs, maximum of 1000 characters translated, powered by Systran"),
              'http://babelfish.altavista.com/' );
//    ShowTrad( 'Translator.Go.com',
//              _("10 language pairs, maximum of 25 kilobytes translated, powered by Systran"),
//              'http://translator.go.com/' );
    ShowTrad( 'Dictionary.com',
              _("12 language pairs, no known limits, powered by Systran"),
              'http://www.dictionary.com/translate' );
    ShowTrad( 'InterTran',
              _("784 language pairs, no known limits, powered by Translation Experts's InterTran"),
              'http://www.tranexp.com/' );
    ShowTrad( 'GPLTrans',
              _("8 language pairs, no known limits, powered by GPLTrans (free, open source)"),
              'http://www.translator.cx/' );
    ShowTrad( 'OTEnet',
              _("4 language pairs for Hellenic, no known limits, powered by Systran"),
              'http://systran.otenet.gr/' );
?>
   </ul>
   <p>
<?php
   echo _("You also decide if you want the translation box displayed, and where it will be located.") .
        "<form action=\"$PHP_SELF\" method=post>".
        '<table border=0 cellpadding=0 cellspacing=2>'.
            '<tr><td align=right nowrap>' .
             _("Select your translator:") .
             '</td>'.
            '<td><select name="translate_translate_server">';

    ShowOption('server', 'babelfish', 'Babelfish');
//    ShowOption('server', 'go', 'Go.com');
    ShowOption('server', 'dictionary', 'Dictionary.com');
    ShowOption('server', 'intertran', 'Intertran');
    ShowOption('server', 'gpltrans', 'GPLTrans');
    ShowOption('server', 'otenet', 'OTEnet');
    echo '</select>' .
         '</td></tr>' .
         '<tr><td align=right nowrap>' .
         _("When reading:") .
         '</td>'.
         '<td><input type=checkbox name="translate_translate_show_read"';
    if ($translate_show_read)
        echo " CHECKED";
    echo '> - ' . _("Show translation box") .
         ' <select name="translate_translate_location">';
    ShowOption('location', 'left', _("to the left"));
    ShowOption('location', 'center', _("in the center"));
    ShowOption('location', 'right', _("to the right"));
    echo '</select><br>'.
         '<input type=checkbox name="translate_translate_same_window"';
    if ($translate_same_window)
        echo " CHECKED";
    echo '> - ' . _("Translate inside the SquirrelMail frames").
         '</td></tr>'.
         '<tr><td align=right nowrap>'.
         _("When composing:") . '</td>'.
         '<td><input type=checkbox name="translate_translate_show_send"';
   if ($translate_show_send)
     echo " CHECKED";
   echo '> - ' . _("Not yet functional, currently does nothing") .
        '</td></tr>'.
        '<tr><td></td><td>'.
        '<input type="submit" value="' . _("Submit") . '" name="submit_translate">'.
        '</td></tr>'.
   '</table>'.
   '</form>'.
"</body></html>\n";

?>
