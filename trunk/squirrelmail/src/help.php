<?php

/**
 * help.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays help for the user
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/display_messages.php');

displayPageHeader($color, 'None' );

$helpdir[0] = 'basic.hlp';
$helpdir[1] = 'main_folder.hlp';
$helpdir[2] = 'read_mail.hlp';
$helpdir[3] = 'compose.hlp';
$helpdir[4] = 'addresses.hlp';
$helpdir[5] = 'folders.hlp';
$helpdir[6] = 'options.hlp';
$helpdir[7] = 'search.hlp';
$helpdir[8] = 'FAQ.hlp';

/****************[ HELP FUNCTIONS ]********************/

/**
 * parses through and gets the information from the different documents.
 * this returns one section at a time.  You must keep track of the position
 * so that it knows where to start to look for the next section.
 */

function get_info($doc, $pos) {
    $ary = array(0,0,0);

    $cntdoc = count($doc);

    for ($n=$pos; $n < $cntdoc; $n++) {
        if (trim(strtolower($doc[$n])) == '<chapter>'
            || trim(strtolower($doc[$n])) == '<section>') {
            for ($n++; $n < $cntdoc 
                 && (trim(strtolower($doc[$n])) != '</section>') 
                 && (trim(strtolower($doc[$n])) != '</chapter>'); $n++) {
                if (trim(strtolower($doc[$n])) == '<title>') {
                    $n++;
                    $ary[0] = trim($doc[$n]);
                }
                if (trim(strtolower($doc[$n])) == '<description>') {
                    $ary[1] = '';
                    for ($n++;$n < $cntdoc 
                         && (trim(strtolower($doc[$n])) != '</description>');
                         $n++) {
                        $ary[1] .= $doc[$n];
                    }
                }
                if (trim(strtolower($doc[$n])) == '<summary>') {
                    $ary[2] = '';
                    for ($n++; $n < $cntdoc 
                         && (trim(strtolower($doc[$n])) != '</summary>'); 
                         $n++) {
                        $ary[2] .= $doc[$n];
                    }
                }
            }
            if (isset($ary)) {
                $ary[3] = $n;
            } else {
                $ary[0] = _("ERROR: Help files are not in the right format!");
                $ary[1] = $ary[0];
                $ary[2] = $ary[0];
            }
	    return( $ary );
        } else if (!trim(strtolower($doc[$n]))) {
	     $ary[0] = '';
	     $ary[1] = '';
	     $ary[2] = '';
	     $ary[3] = $n;
	}
    }
    $ary[0] = _("ERROR: Help files are not in the right format!");
    $ary[1] = $ary[0];
    $ary[2] = $ary[0];
    $ary[3] = $n;
    return( $ary );
}

/**************[ END HELP FUNCTIONS ]******************/


echo html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'td','<center><b>' . _("Help") .'</b></center>', 'center', $color[0] )
        ) ,
    'center', '', 'width="95%" cellpadding="1" cellspacing="2" border="0"' );

do_hook('help_top');

echo html_tag( 'table', '', 'center', '', 'width="90%" cellpadding="0" cellspacing="10" border="0"' ) .
        html_tag( 'tr' ) .
            html_tag( 'td' );

if (!isset($squirrelmail_language)) {
    $squirrelmail_language = 'en_US';
}

if (file_exists("../help/$squirrelmail_language")) {
    $user_language = $squirrelmail_language;
} else if (file_exists('../help/en_US')) {
    echo "<center><font color=\"$color[2]\">";
    printf (_("The help has not been translated to %s.  It will be displayed in English instead."), $languages[$squirrelmail_language]['NAME']);
    echo '</font></center><br>';
    $user_language = 'en_US';
} else {
    error_box( _("Some or all of the help documents are not present!"), $color );
    exit;
}


/* take the chapternumber from the GET-vars,
 * else see if we can get a relevant chapter from the referer */
$chapter = 0;

if ( isset( $_GET['chapter'] ) )
{
    $chapter = intval( $_GET['chapter']);
}
elseif (isset($_SERVER['HTTP_REFERER']))
{
    $ref = strtolower($_SERVER['HTTP_REFERER']);

    $contexts = array ( 'src/compose' => 4, 'src/addr' => 5,
        'src/folders' => 6, 'src/options' => 7, 'src/right_main' => 2,
        'src/read_body' => 3, 'src/search' => 8 );

    foreach($contexts as $path => $chap) {
        if(strpos($ref, $path)) {
            $chapter = $chap;
            break;
        }
    }
}

if ( $chapter == 0 || !isset( $helpdir[$chapter-1] ) ) {
    echo html_tag( 'table', '', 'center', '', 'cellpadding="0" cellspacing="0" border="0"' );
	        html_tag( 'tr' ) .
                    html_tag( 'td' ) .
                         '<b><center>' . _("Table of Contents") . '</center></b><br>';
    do_hook('help_chapter');
    echo html_tag( 'ol' );
    for ($i=0, $cnt = count($helpdir); $i < $cnt; $i++) {
        $doc = file("../help/$user_language/$helpdir[$i]");
        $help_info = get_info($doc, 0);
        echo '<li><a href="../src/help.php?chapter=' . ($i+1)
             . '">' . $help_info[0] . '</a>' .
             html_tag( 'ul', $help_info[2] );
    }
    echo '</ol></td></tr></table>';
} else {
    $doc = file("../help/$user_language/" . $helpdir[$chapter-1]);
    $help_info = get_info($doc, 0);
    echo '<small><center>';
    if ($chapter <= 1){
        echo '<font color="' . $color[9] . '">' . _("Previous")
             . '</font> | ';
    } else {
        echo '<a href="../src/help.php?chapter=' . ($chapter-1)
             . '">' . _("Previous") . '</a> | ';
    }
    echo '<a href="../src/help.php">' . _("Table of Contents") . '</a>';
    if ($chapter >= count($helpdir)){
        echo ' | <font color="' . $color[9] . '">' . _("Next") . '</font>';
    } else {
        echo ' | <a href="../src/help.php?chapter=' . ($chapter+1)
             . '">' . _("Next") . '</a>';
    }
    echo '</center></small><br>';

    echo '<font size="5"><b>' . $chapter . ' - ' . $help_info[0]
         . '</b></font><br><br>';

    if (isset($help_info[1]) && $help_info[1]) {
        echo $help_info[1];
    } else {
        echo html_tag( 'p', $help_info[2], 'left' );
    }
             
    $section = 0;
    for ($n = $help_info[3], $cnt = count($doc); $n < $cnt; $n++) {
        $section++;
        $help_info = get_info($doc, $n);
        echo "<b>$chapter.$section - $help_info[0]</b>" .
            html_tag( 'ul', $help_info[1] );
    	$n = $help_info[3];
    }

    echo '<br><center><a href="#pagetop">' . _("Top") . '</a></center>';
}

do_hook('help_bottom');

echo html_tag( 'tr',
            html_tag( 'td', '&nbsp;', 'left', $color[0] )
        ).
       '</table></body></html>';
?>
