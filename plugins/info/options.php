<?PHP

/* options page for IMAP info plugin 
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *   
 * This is where it all happens :)
 *
 * Written by: Jason Munro 
 * jason@stdbev.com
 */

define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'plugins/info/functions.php');

global $username, $color, $folder_prefix, $default_charset;
$default_charset = strtoupper($default_charset);
displayPageHeader($color, 'None');
$mailbox = 'INBOX';
$imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$caps_array = get_caps($imap_stream);
$list = array (
               'TEST_0',
               'TEST_1',
               'TEST_2',
               'TEST_3',
               'TEST_4',
               'TEST_5',
               'TEST_6',
               'TEST_7',
               'TEST_8',
               'TEST_9');

print "<BR><CENTER><B>IMAP server information</B></CENTER><BR>\n";
print "<CENTER><TABLE BGCOLOR=".$color[3]." WIDTH=\"100%\" BORDER=1 CELLPADDING=2><TR><TD BGCOLOR=".$color[3]."><BR>\n";
print "<CENTER><TABLE WIDTH=\"95%\" BORDER=1 BGCOLOR=".$color[3].">\n";
print "<TR><TD BGCOLOR=".$color[4]."><B>Server Capability response:</B><BR>\n";

foreach($caps_array[0] as $value) {
    print $value;
}

print "</TD></TR><TR><TD>\n";

if (!isset($submit) || $submit == 'default') {
    print "<BR><SMALL><FONT COLOR=".$color[6].">Select the IMAP commands you would like to run. Most commands require a selected mailbox so the select command is already setup. You can clear all the commands and test your own IMAP command strings. The commands are executed in order. The default values are simple IMAP commands using your default_charset and folder_prefix from Squirrelmail when needed.<BR><BR><B><CENTER>NOTE: These commands are live, any changes made will effect your current email account.</B></CENTER></FONT></SMALL><BR>\n";
    if (!isset($submit)) {
        $submit = '';
    }
}
else {
    print "folder_prefix = $folder_prefix<BR>\n";
    print "default_charset = $default_charset\n";
}

print "<BR></TD></TR></TABLE></CENTER><BR>\n";


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
        'TEST_8' => "LIST \"$folder_prefix*\" \"*\"",
        'TEST_9' => "");
}

print "<FORM ACTION=\"options.php\" METHOD=POST>\n";
print "<CENTER><TABLE BORDER=1>\n";
print "<TR><TH>Select</TH><TH>Test Name</TH><TH>IMAP command string</TH>\n";
print "</TR><TR><TD>\n";

foreach($type as $index=>$value) {
    print "</TD></TR><TR><TD WIDTH=\"10%\"><INPUT TYPE=CHECKBOX VALUE=1 NAME=CHECK_$index";
    if ($index == 'TEST_0' && ($submit == 'default' || $submit == '')) {
        print " CHECKED";
    }
    $check = "CHECK_".$index;
    if (isset($$check) && $submit != 'clear' && $submit != 'default') {
        print " CHECKED";
    }
    print "></TD><TD WIDTH=\"30%\">$index</TD><TD WIDTH=\"60%\">\n";
    print "<INPUT TYPE=TEXT NAME=$index VALUE='$value' SIZE=60>\n"; 
}

print "</TD></TR></TABLE></CENTER><BR>\n";
print "<CENTER><INPUT TYPE=SUBMIT NAME=submit value=submit>\n";
print "<INPUT TYPE=SUBMIT NAME=submit value=clear>\n";
print "<INPUT TYPE=SUBMIT NAME=submit value=default></CENTER><BR>\n";

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
        print "<CENTER><TABLE WIDTH=\"95%\" BORDER=0 BGCOLOR=".$color[4].">\n";
        print "<TR><TD><B>".$tests[$i]."</B></TD><TR>";
        print "<TR><TD><SMALL><B><FONT COLOR=".$color[7].
              ">Request:</FONT></SMALL></B></TD></TR>\n";
        $response = imap_test($imap_stream, $type[$tests[$i]]);
        print "<TR><TD><SMALL><B><FONT COLOR=".$color[7].
              ">Response:</FONT></SMALL></B></TD></TR>\n";
        print "<TR><TD>";
        print_response($response);
        print "</TD><TR></TABLE></CENTER><BR>\n";
    }
}
    print "</TD></TR></TABLE></CENTER></BODY></HTML>";
    sqimap_logout($imap_stream);
    do_hook('info_bottom');
?>
