<?php

/**
 * search.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/imap_search.php');
require_once('../functions/imap_mailbox.php');
require_once('../functions/array.php');
require_once('../functions/strings.php');

global $allow_thread_sort;

/*  here are some functions, could go in imap_search.php

    this was here, pretty handy  */
function s_opt( $val, $sel, $tit ) {
    echo "            <option value=\"$val\"";
    if ( $sel == $val ) {
        echo ' selected';
    }
    echo  ">$tit</option>\n";
}

/*  function to get the recent searches and put them in the attributes array  */
function get_recent($username, $data_dir) {
    $attributes = array();
    $types = array('search_what', 'search_where', 'search_folder');
    $recent_count = getPref($data_dir, $username, 'search_memory', 0);
    for ($x=1;$x<=$recent_count;$x++) {
        reset($types);
        foreach ($types as $key) {
            $attributes[$key][$x] = getPref($data_dir, $username, $key.$x, "");
        }
    }
    return $attributes;
}

/*  function to get the saved searches and put them in the saved_attributes array  */
function get_saved($username, $data_dir) {
    $saved_attributes = array();
    $types = array('saved_what', 'saved_where', 'saved_folder');
    foreach ($types as $key) {
        for ($x=1;;$x++) {
            $saved_attributes[$key][$x] = getPref($data_dir, $username, $key."$x", "");
            if ($saved_attributes[$key][$x] == "") {
                array_pop($saved_attributes[$key]);
               break;
            }
        }
    }
    return $saved_attributes;
}

/*  function to update recent pref arrays  */
function update_recent($what, $where, $mailbox, $username, $data_dir) {
    $attributes = array();
    $types = array('search_what', 'search_where', 'search_folder');
    $input = array($what, $where, $mailbox);
    $attributes = get_recent( $username, $data_dir);
    reset($types);
    $dupe = 'no';
    for ($i=1;$i<=count($attributes['search_what']);$i++) {
        if (isset($attributes['search_what'][$i])) {
            if ($what == $attributes['search_what'][$i] &&
                $where == $attributes['search_where'][$i] &&
                $mailbox == $attributes['search_folder'][$i]) {
                    $dupe = 'yes';
            }
        }
    }
    if ($dupe == 'no') {
        $i = 0;
        foreach ($types as $key) {
            array_push ($attributes[$key], $input[$i]);
            array_shift ($attributes[$key]);
            $i++;
        }
        $recent_count = getPref($data_dir, $username, 'search_memory', 0);
        $n=0;
        for ($i=1;$i<=$recent_count;$i++) {
            reset($types);
            foreach ($types as $key) {
                setPref($data_dir, $username, $key.$i, $attributes[$key][$n]);
            }
            $n++;
        }
    }
}

/*  function to forget a recent search  */
function forget_recent($forget_index, $username, $data_dir) {
    $attributes = array();
    $types = array('search_what', 'search_where', 'search_folder');
    $attributes = get_recent( $username, $data_dir);
    reset($types);
    foreach ($types as $key) {
        array_splice($attributes[$key], $forget_index - 1, 1);
        array_unshift($attributes[$key], '');
    }
    reset($types);
    $recent_count = getPref($data_dir, $username, 'search_memory', 0);
    $n=0;
    for ($i=1;$i<=$recent_count;$i++) {
        reset($types);
        foreach ($types as $key) {
            setPref($data_dir, $username, $key.$i, $attributes[$key][$n]);
        }
        $n++;
    }
}

/*  function to delete a saved search  */
function delete_saved($delete_index, $username, $data_dir) {
    $types = array('saved_what', 'saved_where', 'saved_folder');
    $attributes = get_saved($username, $data_dir);
    foreach ($types as $key) {
        array_splice($attributes[$key], $delete_index, 1);
    }
    reset($types);
    $n=0;
    $saved_count = count($attributes['saved_what']);
    $last_element = $saved_count + 1;
        for ($i=1;$i<=$saved_count;$i++) {
            reset($types);
            foreach ($types as $key) {
                setPref($data_dir, $username, $key.$i, $attributes[$key][$n]);
            }
        $n++;
        }
    reset($types);
    foreach($types as $key) {
    removePref($data_dir, $username, $key.$last_element);
    }
}

/*  function to save a search from recent to saved  */
function save_recent($save_index, $username, $data_dir) {
    $attributes = array();
    $types = array('search_what', 'search_where', 'search_folder');
    $saved_types = array(0 => 'saved_what', 1 => 'saved_where', 2 => 'saved_folder');
    $saved_array = get_saved($username, $data_dir);
    $save_index = $save_index -1;
    $saved_count = (count($saved_array['saved_what']) + 1);
    $attributes = get_recent ($username, $data_dir);
    $n = 0;
    foreach ($types as $key) {
        $slice = array_slice($attributes[$key], $save_index, 1);
        $name = $saved_types[$n];
        setPref($data_dir, $username, $name.$saved_count, $slice[0]);
        $n++;
    }
}

function printSearchMessages($msgs,$mailbox, $cnt, $imapConnection, $where, $what, $usecache = false, $newsort = false) {
    global $sort, $color;
    
    $msort = calc_msort($msgs, $sort);
    if ($cnt > 0) {
       if ( $mailbox == 'INBOX' ) {
           $showbox = _("INBOX");
       } else {
            $showbox = imap_utf7_decode_local($mailbox);
       }
       echo html_tag( 'div', '<b><big>' . _("Folder:") . ' '. $showbox.'</big></b>','center') . "\n";


       $msg_cnt_str = get_msgcnt_str(1, $cnt, $cnt);
       $toggle_all = get_selectall_link(1, $sort);

       echo '<table bgcolor="' . $color[0] . '" border="0" width="100%" cellpadding="1" cellspacing="0"><tr><td>';
       mail_message_listing_beginning($imapConnection, $mailbox, $sort, 
                                       $msg_cnt_str, $toggle_all, 1);


       printHeader($mailbox, 6, $color, false);

       displayMessageArray($imapConnection, $cnt, 1, 
		          $msort, $mailbox, $sort, $color, $cnt, $where, $what);

       mail_message_listing_end($cnt, '', $msg_cnt_str, $color); 
       echo '</td></tr></table>';
       
    }
}			      

/* ------------------------ main ------------------------ */

/*  reset these arrays on each page load just in case  */
$attributes = array ();
$saved_attributes = array ();
$search_all = 'none';
$perbox_count = array ();
$recent_count = getPref($data_dir, $username, 'search_memory', 0);


/*  get mailbox names  */
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$boxes = sqimap_mailbox_list($imapConnection);

/*  set current mailbox to INBOX if none was selected or if page
    was called to search all folders.  */
if ( !isset($mailbox) || $mailbox == 'None' || $mailbox == '' ) {
    $mailbox = $boxes[0]['unformatted'];
}
if ($mailbox == 'All Folders') {
    $search_all = 'all';
}

if (isset($composenew) && $composenew) {
    $comp_uri = "../src/compose.php?mailbox=". urlencode($mailbox).
		"&amp;session=$composesession&amp;attachedmessages=true&amp";
    displayPageHeader($color, $mailbox, "comp_in_new(false,'$comp_uri');", false);
} else {
    displayPageHeader($color, $mailbox);
}
/*  See how the page was called and fire off correct function  */
if ((!isset($submit) || empty($submit)) && !empty($what)) {
    $submit = _("Search");
}
if ( !isset( $submit ) ) {
    $submit = '';
} else if ($submit == _("Search") && !empty($what)) {
    if ($recent_count > 0) {
        update_recent($what, $where, $mailbox, $username, $data_dir);
    }
}
elseif ($submit == 'forget') {
    forget_recent($count, $username, $data_dir);
}
elseif ($submit == 'save') {
    save_recent($count, $username, $data_dir);
}
elseif ($submit == 'delete') {
    delete_saved($count, $username, $data_dir);
}

do_hook('search_before_form');

echo html_tag( 'table',
         html_tag( 'tr', "\n" .
             html_tag( 'td', '<b>' . _("Search") . '</b>', 'center', $color[0] )
         ) ,
     '', '', 'width="100%"') . "\n";

/*  update the recent and saved searches from the pref files  */
$attributes = get_recent($username, $data_dir);
$saved_attributes = get_saved($username, $data_dir);
$saved_count = count($saved_attributes['saved_what']);
$count_all = 0;

/* Saved Search Table */
if ($saved_count > 0) {
    echo "<br>\n"
    . html_tag( 'table', '', 'center', $color[9], 'width="95%" cellpadding="1" cellspacing="1" border="0"' )
    . html_tag( 'tr',
          html_tag( 'td', '<b>Saved Searches</b>', 'center' )
      )
    . html_tag( 'tr' )
    . html_tag( 'td' )
    . html_tag( 'table', '', 'center', '', 'width="100%" cellpadding="2" cellspacing="2" border="0"' );
    for ($i=0; $i < $saved_count; ++$i) {
        if ($i % 2) {
            echo html_tag( 'tr', '', '', $color[0] );
        } else {
            echo html_tag( 'tr', '', '', $color[4] );
        }
        echo html_tag( 'td', $saved_attributes['saved_folder'][$i], 'left', '', 'width="35%"' )
        . html_tag( 'td', $saved_attributes['saved_what'][$i], 'left' )
        . html_tag( 'td', $saved_attributes['saved_where'][$i], 'center' )
        . html_tag( 'td', '', 'right' )
        .   '<a href=search.php'
        .     '?mailbox=' . urlencode($saved_attributes['saved_folder'][$i])
        .     '&amp;what=' . urlencode($saved_attributes['saved_what'][$i])
        .     '&amp;where=' . urlencode($saved_attributes['saved_where'][$i])
        .   '>' . _("edit") . '</a>'
        .   '&nbsp;|&nbsp;'
        .   '<a href=search.php'
        .     '?mailbox=' . urlencode($saved_attributes['saved_folder'][$i])
        .     '&amp;what=' . urlencode($saved_attributes['saved_what'][$i])
        .     '&amp;where=' . urlencode($saved_attributes['saved_where'][$i])
        .     '&amp;submit=Search_no_update'
        .   '>' . _("search") . '</a>'
        .   '&nbsp;|&nbsp;'
        .   "<a href=search.php?count=$i&amp;submit=delete>"
        .     _("delete")
        .   '</a>'
        . '</td></tr>';
    }
    echo "</table></td></tr></table>\n";
}

if ($recent_count > 0) {
    echo "<br>\n"
       . html_tag( 'table', '', 'center', $color[9], 'width="95%" cellpadding="1" cellspacing="1" border="0"' )
       . html_tag( 'tr',
             html_tag( 'td', '<b>' . _("Recent Searches") . '</b>', 'center' )
         )
       . html_tag( 'tr' )
       . html_tag( 'td' )
       . html_tag( 'table', '', 'center', '', 'width="100%" cellpadding="0" cellspacing="0" border="0"' );
    for ($i=1; $i <= $recent_count; ++$i) {
            if (isset($attributes['search_folder'][$i])) { 
            if ($attributes['search_folder'][$i] == "") {
                $attributes['search_folder'][$i] = "INBOX";
            }
            }
            if ($i % 2) {
                echo html_tag( 'tr', '', '', $color[0] );
            } else {
                echo html_tag( 'tr', '', '', $color[0] );
            }
            if (isset($attributes['search_what'][$i]) &&
                !empty($attributes['search_what'][$i])) {
            echo html_tag( 'td', $attributes['search_folder'][$i], 'left', '', 'width="35%"' )
               . html_tag( 'td', $attributes['search_what'][$i], 'left' )
               . html_tag( 'td', $attributes['search_where'][$i], 'center' )
               . html_tag( 'td', '', 'right' )
               .   "<a href=search.php?count=$i&amp;submit=save>"
               .     _("save")
               .   '</a>'
               .   '&nbsp;|&nbsp;'
               .   '<a href=search.php'
               .     '?mailbox=' . urlencode($attributes['search_folder'][$i])
               .     '&amp;what=' . urlencode($attributes['search_what'][$i])
               .     '&amp;where=' . urlencode($attributes['search_where'][$i])
               .     '&amp;submit=Search_no_update'
               .   '>' . _("search") . '</a>'
               .   '&nbsp;|&nbsp;'
               .   "<a href=search.php?count=$i&amp;submit=forget>"
               .     _("forget")
               .   '</a>'
               . '</td></tr>';
        }
        }
    echo '</table></td></tr></table><br>';
}


if (isset($newsort)) {
    $sort = $newsort;
    session_register('sort');
}

/*********************************************************************
 * Check to see if we can use cache or not. Currently the only time  *
 * when you will not use it is when a link on the left hand frame is *
 * used. Also check to make sure we actually have the array in the   *
 * registered session data.  :)                                      *
 *********************************************************************/
if (! isset($use_mailbox_cache)) {
    $use_mailbox_cache = 0;
}

/* There is a problem with registered vars in 4.1 */
/*
if( substr( phpversion(), 0, 3 ) == '4.1'  ) {
    $use_mailbox_cache = FALSE;
}
*/

/* Search Form */
echo html_tag( 'div', '<b>' . _("Current Search") . '</b>', 'left' ) . "\n"
   . '<form action="search.php" name="s">'
   . html_tag( 'table', '', '', '', 'width="95%" cellpadding="0" cellspacing="0" border="0"' )
   . html_tag( 'tr' )
   . html_tag( 'td', '', 'left' )
   . '<select name="mailbox">';
for ($i = 0; $i < count($boxes); $i++) {
    if (!in_array('noselect', $boxes[$i]['flags'])) {
        $box = $boxes[$i]['unformatted'];
        $box2 = str_replace(' ', '&nbsp;', 
                         imap_utf7_decode_local($boxes[$i]['unformatted-disp']));
        if( $box2 == 'INBOX' ) {
            $box2 = _("INBOX");
        }
        echo '         <option value="' . $box . '"';
        if ($mailbox == $box) { echo ' selected'; }
        echo '>' . $box2 . '</option>' . "\n";
    }
}
        echo '<option value="All Folders"';
        if ($mailbox == 'All Folders') {
            echo ' selected';
        }
        echo ">All folders</option>\n";
echo '         </select>'.
     "       </td>\n";
if ( !isset( $what ) ) {
    $what = '';
}
if ( !isset( $where ) ) {
    $where = '';
}


$what_disp = str_replace(',', ' ', $what);
$what_disp = str_replace('\\\\', '\\', $what_disp);
$what_disp = str_replace('\\"', '"', $what_disp);
$what_disp = str_replace('"', '&quot;', $what_disp);
echo html_tag( 'td', '<input type="text" size="35" name="what" value="' . $what_disp . '">' . "\n", 'center' )
     . html_tag( 'td', '', 'right' )
     . "<select name=\"where\">";
s_opt( 'BODY', $where, _("Body") );
s_opt( 'TEXT', $where, _("Everywhere") );
s_opt( 'SUBJECT', $where, _("Subject") );
s_opt( 'FROM', $where, _("From") );
s_opt( 'CC', $where, _("Cc") );
s_opt( 'TO', $where, _("To") );
echo "         </select>\n" .
     "        </td>\n".
     html_tag( 'td', '<input type="submit" name="submit" value="' . _("Search") . '">' . "\n", 'center', '', 'colspan="3"' ) .
     "     </tr>\n".
     "</form>\n".
     "   </table>\n".
     "</td></tr></table>\n";


do_hook('search_after_form');

/*
    search all folders option still in the works. returns a table for each
    folder it finds a match in.
*/

$old_value = 0;
if ($allow_thread_sort == TRUE) {
    $old_value = $allow_thread_sort;
    $allow_thread_sort = FALSE;
}

if ($search_all == 'all') {
    $mailbox == '';
    $boxcount = count($boxes);
    echo '<BR><CENTER><B>' .
         _("Search Results") .
         "</B><CENTER><BR>\n";
    for ($x=0;$x<$boxcount;$x++) {
        if (!in_array('noselect', $boxes[$x]['flags'])) {
            $mailbox = $boxes[$x]['unformatted'];
        }
        if (($submit == _("Search") || $submit == 'Search_no_update') && !empty($what)) {
            sqimap_mailbox_select($imapConnection, $mailbox);
            $msgs = sqimap_search($imapConnection, $where, $what, $mailbox, $color, 0, $search_all, $count_all);
	    $count_all = count($msgs);
            printSearchMessages($msgs, $mailbox, $count_all, $imapConnection, 
	                        $where, $what, false, false);
            array_push($perbox_count, $count_all);
        }
    }
    for ($i=0;$i<count($perbox_count);$i++) {
        if ($perbox_count[$i]) {
            $count_all = true;
            break;
        }
    }
    if (!$count_all) {
       echo '<br><center>' . _("No Messages Found") . '</center>';
    }
}

/*  search one folder option  */
else {
    if (($submit == _("Search") || $submit == 'Search_no_update') && !empty($what)) {
        echo '<br>'
        . html_tag( 'div', '<b>' . _("Search Results") . '</b>', 'center' ) . "\n";
        sqimap_mailbox_select($imapConnection, $mailbox);
        $msgs = sqimap_search($imapConnection, $where, $what, $mailbox, $color, 0, $search_all, $count_all);
	if (count($msgs)) {
           printSearchMessages($msgs, $mailbox, count($msgs), $imapConnection,
	                       $where, $what, false, false);
	} else {
           echo '<br><center>' . _("No Messages Found") . '</center>';
	}
    }
}

/*  must have search terms to search  */
if ($submit == _("Search") && empty($what)) {
        echo '<br>'
        . html_tag( 'div', '<b>Please enter something to search for</b>', 'center' ) . "\n";
}

$allow_thread_sort = $old_value;


do_hook('search_bottom');
sqimap_logout ($imapConnection);
echo '</body></html>';

?>
