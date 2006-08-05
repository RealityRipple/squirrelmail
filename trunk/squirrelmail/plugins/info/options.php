<?php

/**
 * options page for IMAP info plugin
 *
 * This is where it all happens :)
 *
 * @author Jason Munro <jason at stdbev.com>
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage info
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
require('../../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/imap_general.php');
require_once(SM_PATH . 'functions/forms.php');
require_once(SM_PATH . 'plugins/info/functions.php');

global $username, $color, $folder_prefix, $default_charset;
$default_charset = strtoupper($default_charset);
displayPageHeader($color, 'None');
$mailbox = 'INBOX';

/**
 * testing installation
 *
 * prevent use of plugin if it is not enabled
 */
if (! is_plugin_enabled('info')) {
    error_box(_("Plugin is disabled."));
    // display footer (closes html) and stop script execution
    $oTemplate->display('footer.tpl');
    exit;
}

/* GLOBALS */
sqgetGlobalVar('submit', $submit, SQ_POST);

for($i = 0; $i <= 9; $i++){
    $varc = 'CHECK_TEST_'.$i;
    sqgetGlobalVar($varc, $$varc, SQ_POST);
    $vart  = 'TEST_'.$i;
    sqgetGlobalVar($vart, $$vart, SQ_POST);
}

/* END GLOBALS */

$imap_stream = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);
$caps_array = get_caps($imap_stream);
$list = array ('TEST_0',
               'TEST_1',
               'TEST_2',
               'TEST_3',
               'TEST_4',
               'TEST_5',
               'TEST_6',
               'TEST_7',
               'TEST_8',
               'TEST_9');

echo '<br /><div style="text-align: center;"><b>'._("IMAP server information")."</b></div><br />\n".
     '<table bgcolor="'.$color[3].'" width="100%" align="center" border="1" cellpadding="2">'.
     '<tr><td bgcolor="'.$color[3]."\"><br />\n".
     '<table width="95%" align="center" border="1" bgcolor="'.$color[3]."\">\n".
     '<tr><td bgcolor="'.$color[4].'"><b>'.
     _("Server Capability response:").
     "</b><br />\n";

foreach($caps_array[0] as $value) {
    echo htmlspecialchars($value);
}

echo "</td></tr><tr><td>\n";

if (!isset($submit) || $submit == 'default') {
    echo '<br /><p><small><font color="'.$color[6].'">'.
         _("Select the IMAP commands you would like to run. Most commands require a selected mailbox so the select command is already setup. You can clear all the commands and test your own IMAP command strings. The commands are executed in order. The default values are simple IMAP commands using your default_charset and folder_prefix from SquirrelMail when needed.").
         "</font></small></p>\n".
         '<p align="center"><small><b>'.
         _("NOTE: These commands are live, any changes made will effect your current email account.").
         "</b></small></p><br />\n";
    if (!isset($submit)) {
        $submit = '';
    }
}
else {
    echo 'folder_prefix = ' . htmlspecialchars($folder_prefix)."<br />\n" .
         'default_charset = '.htmlspecialchars($default_charset)."\n";
}

echo "<br /></td></tr></table><br />\n";


if ($submit == 'submit') {
    $type = array();
    for ($i=0;$i<count($list);$i++) {
        $type[$list[$i]] = $$list[$i];
    }
}

elseif ($submit == 'clear') {
    for ($i=0;$i<count($list);$i++) {
        $type[$list[$i]] = '';
    }
}

elseif (!$submit || $submit == 'default')  {
    $type = array (
        'TEST_0' => "SELECT $mailbox",
        'TEST_1' => "STATUS $mailbox (MESSAGES RECENT)",
        'TEST_2' => "EXAMINE $mailbox",
        'TEST_3' => "SEARCH CHARSET \"$default_charset\" ALL *",
        'TEST_4' => "THREAD REFERENCES $default_charset ALL",
        'TEST_5' => "SORT (DATE) $default_charset ALL",
        'TEST_6' => "FETCH 1:* (FLAGS BODY[HEADER.FIELDS (FROM DATE TO)])",
        'TEST_7' => "LSUB \"$folder_prefix\" \"*%\"",
        'TEST_8' => "LIST \"$folder_prefix\" \"*\"",
        'TEST_9' => "");
}

echo "<form action=\"options.php\" method=\"post\">\n".
     "<table border=\"1\" align=\"center\">\n".
     '<tr><th>'. _("Select").
     '</th><th>'._("Test Name").
     '</th><th>'._("IMAP command string")."</th></tr>\n".
     '<tr><td>';

foreach($type as $index=>$value) {
    echo "</td></tr>\n<tr><td width=\"10%\">\n<input type=\"checkbox\" value=\"1\" name=\"CHECK_$index\"";
    if ($index == 'TEST_0' && ($submit == 'default' || $submit == '')) {
        echo ' checked="checked"';
    }
    $check = "CHECK_".$index;
    if (isset($$check) && $submit != 'clear' && $submit != 'default') {
        echo ' checked="checked"';
    }
    echo " /></td><td width=\"30%\">$index</td><td width=\"60%\">\n".
         addInput($index, $value, 60);
}

echo "</td></tr></table><br />\n".
     '<div style="text-align: center;">'.
     addSubmit('submit','submit').
     addSubmit('clear','submit',array('id'=>'clear')).
     addSubmit('default','submit',array('id'=>'default')).
     "</div><br /></form>\n";

$tests = array();

if ($submit == 'submit') {
    foreach ($type as $index=>$value) {
        $check = "CHECK_".$index;
        if (isset($$check)) {
            $type[$index] = $$index;
            array_push($tests, $index);
        }
    }
    for ($i=0;$i<count($tests);$i++) {
        // make sure that microtime function is available before it is called
        if (function_exists('microtime')) {
            list($usec, $sec) = explode(" ", microtime());
            $starttime = (float)$sec + (float)$usec;
        }

        echo '<table width="95%" align="center" border="0" bgcolor="'.$color[4]."\">\n".
             '<tr><td><b>'.$tests[$i]."</b></td></tr>\n".
             '<tr><td><small><b><font color="'.$color[7].'">'.
            _("Request:")."</font></b></small></td></tr>\n";
        // imap_test function outputs imap command
        $response = imap_test($imap_stream, $type[$tests[$i]]);
        echo '<tr><td><small><b><font color="'.$color[7].'">'.
             _("Response:")."</font></b></small></td></tr>\n".
             '<tr><td>';
        print_response($response);
        echo "</td></tr>\n";

        if (function_exists('microtime')) {
            // get script execution time
            list($usec, $sec) = explode(" ", microtime());
            $endtime = (float)$sec + (float)$usec;
            // i18n: ms = short for miliseconds
            echo '<tr><td><small><b><font color="'.$color[7].'">'.
                _("Execution time:")."</font></b></small></td></tr>\n".
                '<tr><td>'.sprintf(_("%s ms"),round((($endtime - $starttime)*1000),3))."</td></tr>\n";
        }

        echo "</table><br />\n";
    }
}
echo '</td></tr></table>';
sqimap_logout($imap_stream);

/**
 * Optional hook in info plugin
 *
 * Hook allows attaching plugin to bottom of info plugin
 */
do_hook('info_bottom');
?>
</body></html>
