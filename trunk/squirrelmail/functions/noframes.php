<?php
/**
 * noframes.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code makes SM No Frames compatible
 *
 * $Id$
 * @package squirrelmail
 */

require_once(SM_PATH . 'functions/imap.php');

global $use_frames, $allow_frames;
sqgetGlobalVar('use_frames', $use_frames, SQ_COOKIE);

switch ($allow_frames) {
   case 4:    // if $use_frames unset, fall through to case 2
      if (isset($use_frames))
         break;
   case 2:    // Do not use frames
      $use_frames = 0;
      break;
   case 3:    // if $use_frames unset, fall through to case 1
      if (isset($use_frames))
         break;
   default:   // default is also to use frames
   case 1:    // use frames
      $use_frames = 1;
      break;
}


/**
 * Displays the top html header or the left folder list
 * if not using frames
 *
 * @return void
 */
function noframes_top() {
    global $onetimepad, $password, $username, $domain, $trash_folder, $imapConnection,
        $sent_folder, $draft_folder, $imapServerAddress, $imapPort, $left_size, 
        $key, $delimiter, $color, $use_frames, $location_of_bar,
        $auto_create_special, $date_format, $hour_format, $collapse_folders, $boxes;
    if ($use_frames) return;
    $size = $left_size - 20;
    if ($location_of_bar == 'left' || $location_of_bar != 'right') {
        $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output
        echo "<table width='100%' cellpadding=3 cellspacing=5 border=0>\n";
        echo "<tr><td width='$size'><img src='" . SM_PATH . "images/blank.gif' width=$size height=1 border=0></td>";
	echo "<td width='15'><img src='" . SM_PATH . "images/blank.gif' width=15 height=1 border=0></td>";
	echo "<td width='100%'><img src='" . SM_PATH . "images/blank.gif' width=1 height=1 border=0></td></tr>";
        echo "<tr><td valign=top>\n";
        do_hook('left_main_before');
        echo "<table cellpadding=0 width='100%'><tr><td><table cellpadding=1 cellspacing=0 width='100%'><tr bgcolor='$color[0]'>";
	echo "<td><table width='100%' border=0 cellpadding=3 cellspacing=0>\n";
        echo "<tr><td BGCOLOR='".$color[9]."' align=center>\n";
        echo "<B>" . _("Folders") . "</B></td></tr><tr bgcolor='$color[4]'><td>\n";
        require_once(SM_PATH . 'src/left_main.php');
        echo "<br>\n</td></tr></table></td></tr></table></td></tr></table><br>\n";
        do_hook('left_main_after');
        echo "</td><td><img src='" . SM_PATH . "images/blank.gif' width=15 height=1 border=0></td></td><td valign=top>\n\n";
    } else {
        echo "<table width='100%' cellpadding=3 cellspacing=5 border=0>\n";
        echo "<tr><td width='100%'></td><td width='15'><img src='" . SM_PATH . "images/blank.gif' width=15 height=1 border=0></td>";
	echo "<td width='$size'><img src='" . SM_PATH . "images/blank.gif' width=$size height=1 border=0></td></tr>";
        echo "<tr><td valign=top>\n\n";
    }
}

/**
 * Displays the bottom html header or the right folder list
 * if not using frames
 *
 * @return void
 */

function noframes_bottom() {
    global $onetimepad, $password, $username, $domain, $trash_folder, $imapConnection,
        $sent_folder, $draft_folder, $imapServerAddress, $imapPort, $left_size, 
        $key, $delimiter, $color, $use_frames, $location_of_bar,
        $auto_create_special, $date_format, $hour_format, $collapse_folders, $boxes;
    if ($use_frames) return;
    if ($location_of_bar == 'left' || $location_of_bar != 'right') {
        echo "</td></tr></table>\n";
	echo "</body></html>";
    } else {
        $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10); // the 10 is to hide the output
        echo "</td><td><img src='" . SM_PATH . "images/blank.gif' width=15 height=1 border=0></td></td><td valign=top>\n\n";
        do_hook('left_main_before');
        echo "<table cellpadding=0 width='100%'><tr><td><table cellpadding=1 cellspacing=0 width='100%'><tr bgcolor='$color[0]'>";
	echo "<td><table width='100%' border=0 cellpadding=3 cellspacing=0>\n";
        echo "<tr><td BGCOLOR='".$color[9]."' align=center>\n";
        echo "<B>" . _("Folders") . "</B></td></tr><tr bgcolor='$color[4]'><td>\n";
        require_once(SM_PATH . 'src/left_main.php');
        echo "<br>\n</td></tr></table></td></tr></table></td></tr></table><br>\n";
        do_hook('left_main_after');
        echo "</td></tr></table>\n";
	echo "</body></html>\n";
    }
}

?>