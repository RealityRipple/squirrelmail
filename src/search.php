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


//	here are some functions, could go in imap_search.php
//	this was here, pretty handy

function s_opt( $val, $sel, $tit ) {
    echo "            <option value=\"$val\"";
    if ( $sel == $val ) {
        echo ' selected';
    }
    echo  ">$tit</option>\n";
}

//	function to get the recent searches and put them in arrays

function get_recent($pref_name, $username, $data_dir) {
    $array = array ();
    $recent_count = getPref($data_dir, $username, 'search_memory', 0);
    $n = 0;
    for ($x=1;$x<=$recent_count;$x++) {
	$array[$n] = getPref($data_dir, $username, "$pref_name" . "$x", "");
	$n++;
    }
    return $array;
}

//	function to get the saved searches and put them in arrays

function get_saved($pref_name, $username, $data_dir) {
    $array = array ();
    $n = 0;
    for ($x=1;;$x++) {
        $array[$n] = getPref($data_dir, $username, "$pref_name" . "$x", "");
	if ($array[$n] == "") {
	    array_pop($array);
	    return $array;
	}
	$n++;
    }
    return $array;
}

//	function to update pref file with recent searches

function update_recent($array, $recent_value, $pref_name, $username, $data_dir) {
    $array = get_recent($pref_name, $username, $data_dir);
    array_push ($array, $recent_value);
    array_shift ($array);
    $recent_count = getPref($data_dir, $username, 'search_memory', 0);
    $n=0;
    for ($i=1;$i<=$recent_count;$i++) {
       setPref($data_dir, $username, "$pref_name" . "$i", $array[$n]);
       $n++;
    }
}

//	function to "forget" a recent search

function forget_recent($forget_index, $username, $data_dir) {
    $what_array = get_recent("search_what", $username, $data_dir);
    $where_array = get_recent("search_where", $username, $data_dir);
    $folder_array = get_recent("search_folder", $username, $data_dir);
    array_splice($what_array, $forget_index, 1);
    array_splice($where_array, $forget_index, 1);
    array_splice($folder_array, $forget_index, 1);
	array_unshift($what_array, "");
	array_unshift($where_array, "");
	array_unshift($folder_array, "");
    $recent_count = getPref($data_dir, $username, 'search_memory', 0);
    $n=0;
    for ($i=1;$i<=$recent_count;$i++) {
        setPref($data_dir, $username, "search_what" . "$i", $what_array[$n]);
	setPref($data_dir, $username, "search_where" . "$i", $where_array[$n]);
	setPref($data_dir, $username, "search_folder" . "$i", $folder_array[$n]);
	$n++;
    }

//		function to delete a saved search
}
function delete_saved($delete_index, $username, $data_dir) {
    $saved_what_array = get_saved("saved_what", $username, $data_dir); 
    $saved_where_array = get_saved("saved_where", $username, $data_dir);
    $saved_folder_array = get_saved("saved_folder", $username, $data_dir);
    array_splice($saved_what_array, $delete_index, 1);
    array_splice($saved_where_array, $delete_index, 1);
    array_splice($saved_folder_array, $delete_index, 1);
    $n=0;
    $saved_count = count($saved_what_array);
    $last_element = $saved_count + 1;
    if ($last_element < 1) {
        for ($i=1;$i<=$saved_count;$i++) {
            setPref($data_dir, $username, "saved_what" . "$i", $saved_what_array[$n]);
	    setPref($data_dir, $username, "saved_where" . "$i", $saved_where_array[$n]);
	    setPref($data_dir, $username, "saved_folder" . "$i", $saved_folder_array[$n]);
	    $n++;
        }
    }
    removePref($data_dir, $username, "saved_what" . "$last_element");
    removePref($data_dir, $username, "saved_where" . "$last_element");
    removePref($data_dir, $username, "saved_folder" . "$last_element");
}    

//		function to save a search from recent to saved

function save_recent($save_index, $username, $data_dir) {
    $what_array = get_recent("search_what", $username, $data_dir);
    $where_array = get_recent("search_where", $username, $data_dir);
    $folder_array = get_recent("search_folder", $username, $data_dir);
    $saved_what_once = array_slice($what_array, $save_index, 1);
    $saved_where_once = array_slice($where_array, $save_index, 1);
    $saved_folder_once = array_slice($folder_array, $save_index, 1);
    $saved_array = get_saved("saved_what", $username, $data_dir);
    $saved_count = (count($saved_array) + 1);
    setPref($data_dir, $username, "saved_what" . "$saved_count", $saved_what_once[0]);
    setPref($data_dir, $username, "saved_where" . "$saved_count", $saved_where_once[0]);
    setPref($data_dir, $username, "saved_folder" . "$saved_count", $saved_folder_once[0]);
}



/* ------------------------ main ------------------------ */

//	reset these arrays on each page load just in case

$what_array = array ();
$where_array = array ();
$folder_array = array ();
$saved_what_array = array ();
$saved_where_array = array ();
$saved_folder_array = array ();
$search_all = "none";

//	get mailbox names

$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
$boxes = sqimap_mailbox_list($imapConnection);


//	set current mailbox to INBOX if none was selected or if page
//	was called to search all folders.

if ($mailbox == 'None' || $mailbox == "" ) {
    $mailbox = $boxes[0]['unformatted'];
}
if ($mailbox == "All Folders") {
    $search_all = "all";
}

//	page headers

displayPageHeader($color, $mailbox);

//	if the page is called from a search link or button  update recent values
//	in pref files here

if ($submit == "Search" && !empty($what)) {
    update_recent($what_array, $what, "search_what", $username, $data_dir);
    update_recent($where_array, $where, "search_where", $username, $data_dir);
    update_recent($folder_array, $mailbox, "search_folder", $username, $data_dir);
}
//	if the page is called from a "forget recent" link remove search from pref file
elseif ($submit == "forget") {
    forget_recent($count, $username, $data_dir);
}
//	if the page is called from a "save recent" link add search to saved searches
elseif ($submit == "save") {
    save_recent($count, $username, $data_dir);
}
elseif ($submit == "delete") {
    delete_saved($count, $username, $data_dir);
}
//	if the page is called from a "delete saved" link delete saved search
do_hook('search_before_form');

echo "<BR>\n".
     "      <table width=\"100%\">\n".
     "      <TR><td bgcolor=\"$color[0]\">\n".
     "          <CENTER><B>"._("Search")."</B></CENTER>\n".
     "      </TD></TR>\n".
	 "		</TABLE>\n";
#     '      <TR><td align=center>';

//	update the recent and saved searches from the pref files

$what_array = get_recent("search_what", $username, $data_dir);
$where_array = get_recent("search_where", $username, $data_dir);
$folder_array = get_recent("search_folder", $username, $data_dir);
$recent_count = getPref($data_dir, $username, 'search_memory', 0);
$saved_what_array = get_saved("saved_what", $username, $data_dir); 
$saved_where_array = get_saved("saved_where", $username, $data_dir);
$saved_folder_array = get_saved("saved_folder", $username, $data_dir);
$saved_count = count($saved_what_array);
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
        echo "<TD WIDTH=\"35%\">$saved_folder_array[$i]</TD>"
           . "<TD ALIGN=LEFT>$saved_what_array[$i]</TD>"
           . "<TD ALIGN=CENTER>$saved_where_array[$i]</TD>"
           . '<TD ALIGN=RIGHT>'
           .   '<A HREF=search.php'
	       .     '?mailbox=' . urlencode($saved_folder_array[$i])
	       .     '&what=' . urlencode($saved_what_array[$i])
	       .     '&where=' . urlencode($saved_where_array[$i])
	       .   '>' . _("edit") . '</A>'
	       .   '&nbsp;|&nbsp;'
           .   '<A HREF=search.php'
	       .     '?mailbox=' . urlencode($saved_folder_array[$i])
	       .     '&what=' . urlencode($saved_what_array[$i])
	       .     '&where=' . urlencode($saved_where_array[$i])
	       .     '&submit=Search_no_update'
           .   '>' . _("search") . '</A>'
	       .   '&nbsp;|&nbsp;'
	       .   "<A HREF=search.php?count=$i&submit=delete>"
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
       . '<TR><TD ALIGN=CENTER><B>Recent Searches</B></TD></TR><TR><TD>'
       . '<TABLE WIDTH="100%" ALIGN="CENTER" CELLPADDING=0 CELLSPACING=0>';
    for ($i=0; $i < $recent_count; ++$i) {
        if (!empty($what_array[$i])) {
            if ($folder_array[$i] == "") {
                $folder_array[$i] = "INBOX";
            }
            if ($i % 2) {
                echo "<TR BGCOLOR=\"$color[0]\">";
            } else {
                echo "<TR BGCOLOR=\"$color[4]\">";
            }
            echo "<TD WIDTH=35%>$folder_array[$i]</TD>"
               . "<TD ALIGN=LEFT>$what_array[$i]</TD>"
               . "<TD ALIGN=CENTER>$where_array[$i]</TD>"
               . '<TD ALIGN=RIGHT>'
               .   "<A HREF=search.php?count=$i&submit=save>"
               .     _("save")
               .   '</A>'
               .   '&nbsp;|&nbsp;'
               .   '<A HREF=search.php'
               .     '?mailbox=' . urlencode($folder_array[$i])
               .     '&what=' . urlencode($what_array[$i])
               .     '&where=' . urlencode($where_array[$i])
               .     '&submit=Search_no_update'
               .   '>' . _("search") . '</A>'
               .   '&nbsp;|&nbsp;'
               .   "<A HREF=search.php?count=$i&submit=forget>"
               .     _("forget")
               .   '</A>'
               . '</TD></TR>';
        }
    }
    echo '</TABLE></TD></TR></TABLE><BR>';
}

/* Search Form */
echo '<B>Current Search</B>'
   . '<FORM ACTION="search.php" NAME=s>'
   . '   <TABLE WIDTH="95%" CELLPADDING=0 CELLSPACING=0>'
   . '     <TR>'
   . '       <TD><SELECT NAME="mailbox">';
for ($i = 0; $i < count($boxes); $i++) {
    if (!in_array('noselect', $boxes[$i]['flags'])) {
        $box = $boxes[$i]['unformatted'];
        $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
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
     "         <INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"Search\">\n".
     "       </TD>\n".
     "     </TR>\n".
     "</FORM>\n".
     "   </TABLE>\n".
     "</TD></TR></TABLE>\n";


do_hook("search_after_form");

//	search all folders option still in the works. returns a table for each 
//	folder it finds a match in. The toggle all link does not work


if ($search_all == "all") {
    $mailbox == "";
    $boxcount = count($boxes);
    echo "<BR><CENTER><B>Search Results</B><CENTER><BR>\n";
    for ($x=0;$x<$boxcount;$x++) {
        if (!in_array('noselect', $boxes[$x]['flags'])) {
	    $mailbox = $boxes[$x]['unformatted'];
	}
        if (($submit == "Search" || $submit == "Search_no_update") && !empty($what)) {
            sqimap_mailbox_select($imapConnection, $mailbox);
           $count_all = sqimap_search($imapConnection, $where, $what, $mailbox, $color, $pos, $search_all, $count_all);
       }
    }
	if ($count_all == 0) {
	echo "<br><b>No Messages found</b><br>";
	}
}

//	search one folder option

else {
    if (($submit == "Search" || $submit == "Search_no_update") && !empty($what)) {
        echo "<BR><CENTER><B>Search Results</B></CENTER>\n";
        sqimap_mailbox_select($imapConnection, $mailbox);
        sqimap_search($imapConnection, $where, $what, $mailbox, $color, $pos, $search_all, $count_all);
    }
}

//	must have search terms to search

if ($submit == "Search" && empty($what)) {
    echo "<BR><CENTER><B>Please enter something to search for</B></CENTER>\n";
}

do_hook("search_bottom");

//	all done

sqimap_logout ($imapConnection);
echo '</body></html>';

?>
