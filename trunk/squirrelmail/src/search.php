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
        array_splice($attributes[$key], $forget_index, 1);
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

displayPageHeader($color, $mailbox);

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

echo "<BR>\n".
     "<table width=\"100%\">\n".
        "<TR><td bgcolor=\"$color[0]\">\n".
            "<CENTER><B>" . _("Search") . "</B></CENTER>\n".
        "</TD></TR>\n".
     "</TABLE>\n";

/*  update the recent and saved searches from the pref files  */
$attributes = get_recent($username, $data_dir);
$saved_attributes = get_saved($username, $data_dir);
$saved_count = count($saved_attributes['saved_what']);
$count_all = 0;

/* Saved Search Table */
if ($saved_count > 0) {
    echo "<BR>\n"
    . "<TABLE WIDTH=\"95%\" BGCOLOR=\"$color[9]\" ALIGN=\"CENTER\" CELLPADDING=1 CELLSPACING=1>"
    . '<TR><TD align=center><B>Saved Searches</B></TD></TR><TR><TD>'
    . '<TABLE WIDTH="100%" ALIGN="CENTER" CELLPADDING=0 CELLSPACING=0>';
    for ($i=0; $i < $saved_count; ++$i) {
        if ($i % 2) {
            echo "<TR BGCOLOR=\"$color[0]\">";
        } else {
            echo "<TR BGCOLOR=\"$color[4]\">";
        }
        echo "<TD WIDTH=\"35%\">".$saved_attributes['saved_folder'][$i]."</TD>"
        . "<TD ALIGN=LEFT>".$saved_attributes['saved_what'][$i]."</TD>"
        . "<TD ALIGN=CENTER>".$saved_attributes['saved_where'][$i]."</TD>"
        . '<TD ALIGN=RIGHT>'
        .   '<A HREF=search.php'
        .     '?mailbox=' . urlencode($saved_attributes['saved_folder'][$i])
        .     '&amp;what=' . urlencode($saved_attributes['saved_what'][$i])
        .     '&amp;where=' . urlencode($saved_attributes['saved_where'][$i])
        .   '>' . _("edit") . '</A>'
        .   '&nbsp;|&nbsp;'
        .   '<A HREF=search.php'
        .     '?mailbox=' . urlencode($saved_attributes['saved_folder'][$i])
        .     '&amp;what=' . urlencode($saved_attributes['saved_what'][$i])
        .     '&amp;where=' . urlencode($saved_attributes['saved_where'][$i])
        .     '&amp;submit=Search_no_update'
        .   '>' . _("search") . '</A>'
        .   '&nbsp;|&nbsp;'
        .   "<A HREF=search.php?count=$i&amp;submit=delete>"
        .     _("delete")
        .   '</A>'
        . '</TD></TR>';
    }
    echo "</TABLE></TD></TR></TABLE>\n";
}

/* Recent Search Table */
if ($recent_count > 0) {
    echo "<BR>\n"
       . "<TABLE WIDTH=\"95%\" BGCOLOR=\"$color[9]\" ALIGN=\"CENTER\" CELLPADDING=1 CELLSPACING=1>\n"
       . '<TR><TD ALIGN=CENTER><B>' . _("Recent Searches") . '</B></TD></TR><TR><TD>'
       . '<TABLE WIDTH="100%" ALIGN="CENTER" CELLPADDING=0 CELLSPACING=0>';
    for ($i=1; $i <= $recent_count; ++$i) {
            if (isset($attributes['search_folder'][$i])) { 
            if ($attributes['search_folder'][$i] == "") {
                $attributes['search_folder'][$i] = "INBOX";
            }
            }
            if ($i % 2) {
                echo "<TR BGCOLOR=\"$color[0]\">";
            } else {
                echo "<TR BGCOLOR=\"$color[4]\">";
            }
            if (isset($attributes['search_what'][$i]) &&
                !empty($attributes['search_what'][$i])) {
            echo "<TD WIDTH=35%>".$attributes['search_folder'][$i]."</TD>"
               . "<TD ALIGN=LEFT>".$attributes['search_what'][$i]."</TD>"
               . "<TD ALIGN=CENTER>".$attributes['search_where'][$i]."</TD>"
               . '<TD ALIGN=RIGHT>'
               .   "<A HREF=search.php?count=$i&amp;submit=save>"
               .     _("save")
               .   '</A>'
               .   '&nbsp;|&nbsp;'
               .   '<A HREF=search.php'
               .     '?mailbox=' . urlencode($attributes['search_folder'][$i])
               .     '&amp;what=' . urlencode($attributes['search_what'][$i])
               .     '&amp;where=' . urlencode($attributes['search_where'][$i])
               .     '&amp;submit=Search_no_update'
               .   '>' . _("search") . '</A>'
               .   '&nbsp;|&nbsp;'
               .   "<A HREF=search.php?count=$i&amp;submit=forget>"
               .     _("forget")
               .   '</A>'
               . '</TD></TR>';
        }
        }
    echo '</TABLE></TD></TR></TABLE><BR>';
}

/* Search Form */
echo '<B>' . _("Current Search") . '</B>'
   . '<FORM ACTION="search.php" NAME=s>'
   . '   <TABLE WIDTH="95%" CELLPADDING=0 CELLSPACING=0>'
   . '     <TR>'
   . '       <TD><SELECT NAME="mailbox">';
for ($i = 0; $i < count($boxes); $i++) {
    if (!in_array('noselect', $boxes[$i]['flags'])) {
        $box = $boxes[$i]['unformatted'];
        $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
        if( $box2 == 'INBOX' ) {
            $box2 = _("INBOX");
        }
        if ($mailbox == $box) {
            echo "         <OPTION VALUE=\"$box\" SELECTED>$box2</OPTION>\n";
        }
        else {
            echo "         <OPTION VALUE=\"$box\">$box2</OPTION>\n";
        }
    }
}
        echo "<OPTION VALUE=\"All Folders\"";
        if ($mailbox == "All Folders") {
            echo "SELECTED";
        }
        echo ">All folders</OPTION>\n";
echo '         </SELECT>'.
     "       </TD>\n".
     "        <TD ALIGN=\"CENTER\">\n";
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
echo "          <INPUT TYPE=\"TEXT\" SIZE=\"35\" NAME=\"what\" VALUE=\"$what_disp\">\n".
     "        </TD>\n".
     "<TD ALIGN=\"RIGHT\">\n".
     "<SELECT NAME=\"where\">";
s_opt( 'BODY', $where, _("Body") );
s_opt( 'TEXT', $where, _("Everywhere") );
s_opt( 'SUBJECT', $where, _("Subject") );
s_opt( 'FROM', $where, _("From") );
s_opt( 'CC', $where, _("Cc") );
s_opt( 'TO', $where, _("To") );
echo "         </SELECT>\n" .
     "        </TD>\n".
     "       <TD COLSPAN=\"3\" ALIGN=\"CENTER\">\n".
     "         <INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"" . _("Search") . "\">\n".
     "       </TD>\n".
     "     </TR>\n".
     "</FORM>\n".
     "   </TABLE>\n".
     "</TD></TR></TABLE>\n";


do_hook('search_after_form');

/*
    search all folders option still in the works. returns a table for each
    folder it finds a match in.
*/

$old_value = 0;
if ($allow_thread_sort == true) {
    $old_value = $allow_thread_sort;
    $allow_thread_sort = false;
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
            $count_all = sqimap_search($imapConnection, $where, $what, $mailbox, $color, 0, $search_all, $count_all);
        array_push($perbox_count, $count_all);
        }
    }
    for ($i=0;$i<count($perbox_count);$i++) {
        if ($perbox_count[$i] != "") {
           break;
        }
        $count_all = "none";
    }
    if ($count_all == "none") {
        echo '<br><b>' .
             _("No Messages found") .
             '</b><br>';
    }
}

/*  search one folder option  */
else {
    if (($submit == _("Search") || $submit == 'Search_no_update') && !empty($what)) {
        echo '<BR><CENTER><B>' .
             _("Search Results") .
             "</B></CENTER>\n";
        sqimap_mailbox_select($imapConnection, $mailbox);
        sqimap_search($imapConnection, $where, $what, $mailbox, $color, 0, $search_all, $count_all);
    }
}

/*  must have search terms to search  */
if ($submit == _("Search") && empty($what)) {
    echo "<BR><CENTER><B>Please enter something to search for</B></CENTER>\n";
}

$allow_thread_sort = $old_value;

do_hook('search_bottom');
sqimap_logout ($imapConnection);
echo '</body></html>';

?>
