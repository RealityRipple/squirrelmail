<?php

/**
 * help.php
 *
 * Displays help for the user
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the help page */
define('PAGE_NAME', 'help');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

displayPageHeader($color);

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

do_hook('help_top', $null);

if (!isset($squirrelmail_language)) {
    $squirrelmail_language = 'en_US';
}

if (file_exists("../help/$squirrelmail_language")) {
    $user_language = $squirrelmail_language;
} else if (file_exists('../help/en_US')) {
    error_box(_("Help is not available in the selected language. It will be displayed in English instead."));
    echo '<br />';
    $user_language = 'en_US';
} else {
    error_box( _("Help is not available. Please contact your system administrator for assistance."));
    echo '</td></tr></table>';
    // Display footer (closes HTML tags) and stop script execution.
    $oTemplate->display('footer.tpl');
    exit;
}


/* take the chapternumber from the GET-vars,
 * else see if we can get a relevant chapter from the referer */
$chapter = 0;

if ( sqgetGlobalVar('chapter', $temp, SQ_GET) ) {
    $chapter = (int) $temp;
} elseif ( sqgetGlobalVar('HTTP_REFERER', $temp, SQ_SERVER) ) {
    $ref = strtolower($temp);

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
    // Initialise the needed variables.
    $toc = array();

    // Get the chapter numbers, title and decriptions.
    for ($i=0, $cnt = count($helpdir); $i < $cnt; $i++) {
        if (file_exists("../help/$user_language/$helpdir[$i]")) {
            // First try the selected language.
            $doc = file("../help/$user_language/$helpdir[$i]");
            $help_info = get_info($doc, 0);
            $toc[] = array($i+1, $help_info[0], $help_info[2]);
        } elseif (file_exists("../help/en_US/$helpdir[$i]")) {
            // If the selected language can't be found, try English.
            $doc = file("../help/en_US/$helpdir[$i]");
            $help_info = get_info($doc, 0);
            $toc[] = array($i+1, $help_info[0],
                    _("This chapter is not available in the selected language. It will be displayed in English instead.") .
                    '<br />' . $help_info[2]);
        } else {
            // If English can't be found, the chapter went MIA.
            $toc[] = array($i+1, _("This chapter is missing"),
                    sprintf(_("For some reason, chapter %s is not available."), $i+1));
        }
    }

    // Provide hook for external help scripts.
    do_hook('help_chapter', $null);
 
    $new_toc = array();
    foreach ($toc as $ch) {
        $a = array();
        $a['Chapter'] = $ch[0];
        $a['Title'] = $ch[1];
        $a['Summary'] = trim($ch[2]);
        $new_toc[] = $a;
    }
    
    $oTemplate->assign('toc', $new_toc);
    
    $oTemplate->display('help_toc.tpl');
} else {
    // Initialise the needed variables.
    $display_chapter = TRUE;

    // Get the chapter.
    if (file_exists("../help/$user_language/" . $helpdir[$chapter-1])) {
        // First try the selected language.
        $doc = file("../help/$user_language/" . $helpdir[$chapter-1]);
    } elseif (file_exists("../help/en_US/" . $helpdir[$chapter-1])) {
        // If the selected language can't be found, try English.
        $doc = file("../help/en_US/" . $helpdir[$chapter-1]);
        error_box(_("This chapter is not available in the selected language. It will be displayed in English instead."));
        echo '<br />';
    } else {
        // If English can't be found, the chapter went MIA.
        $display_chapter = FALSE;
    }

    // Write the chapter.
    if ($display_chapter) {
        // If there is a valid chapter, display it.
        $help_info = get_info($doc, 0);
        $ch = array();
        $ch['Chapter'] = $chapter;
        $ch['Title'] = $help_info[0];
        $ch['Summary'] = isset($help_info[1]) && $help_info[1] ? trim($help_info[1]) : $help_info[2];
        $ch['Sections'] = array();
        $section = 0;
        for ($n = $help_info[3], $cnt = count($doc); $n < $cnt; $n++) {
            $section++;
            $help_info = get_info($doc, $n);
            $n = $help_info[3];

            $a = array();
            $a['SectionNumber'] = $section;
            $a['SectionTitle'] = $help_info[0];
            $a['SectionText'] = isset($help_info[1]) ? trim($help_info[1]) : '';;
            
            $ch['Sections'][] = $a;
        }
        
        $oTemplate->assign('chapter_number', $chapter);
        $oTemplate->assign('chapter_count', count($helpdir));
        $oTemplate->assign('chapter_title', $ch['Title']);
        $oTemplate->assign('chapter_summary', $ch['Summary']);
        $oTemplate->assign('sections', $ch['Sections']);
        $oTemplate->assign('error_msg', NULL);
    } else {
        // If the help file went MIA, trigger an error message.
        $oTemplate->assign('chapter_number', $chapter);
        $oTemplate->assign('chapter_count', count($helpdir));
        $oTemplate->assign('chapter_title', '');
        $oTemplate->assign('chapter_summary', '');
        $oTemplate->assign('sections', array());
        $oTemplate->assign('error_msg', sprintf(_("For some reason, chapter %s is not available."), $chapter));
    }
    
    $oTemplate->display('help_chapter.tpl');
}

do_hook('help_bottom', $null);

$oTemplate->display('footer.tpl');
