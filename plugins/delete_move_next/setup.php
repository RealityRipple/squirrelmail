<?php

/**
 * setup.php
 *
 * delete_move_next
 *   deletes or moves currently displayed message and displays
 *   next or previous message.
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * $Id$
 */

function squirrelmail_plugin_init_delete_move_next() {
    global $squirrelmail_plugin_hooks;
    
    $squirrelmail_plugin_hooks['html_top']['delete_move_next'] = 'delete_move_next_action';
    $squirrelmail_plugin_hooks['right_main_after_header']['delete_move_next'] = 'delete_move_next_action';
    $squirrelmail_plugin_hooks['read_body_bottom']['delete_move_next'] = 'delete_move_next_read_b';
    $squirrelmail_plugin_hooks['read_body_top']['delete_move_next'] = 'delete_move_next_read_t';
    $squirrelmail_plugin_hooks['options_display_inside']['delete_move_next'] = 'delete_move_next_display_inside';
    $squirrelmail_plugin_hooks['options_display_save']['delete_move_next'] = 'delete_move_next_display_save';
    $squirrelmail_plugin_hooks['loading_prefs']['delete_move_next'] = 'delete_move_next_loading_prefs';
}


/* fixes the sort_array for the prev_del/next_del links when 
 * using server side sorting or thread sorting 
 */

function fix_sort_array () {
    global $username, $data_dir, $allow_server_sort, $allow_thread_sort,
    $mailbox, $imapConnection, $sort;
    if ($allow_server_sort == true) {
        $server_sort_array = sqimap_get_sort_order($imapConnection, $sort);
    }
    $thread_sort_messages = getPref($username, $data_dir, "thread_$mailbox");
    if ($allow_thread_sort == true && $thread_sort_messages == 1) {
        $server_sort_array = get_thread_sort($imapConnection);
    }
}

/*
 * Warning: this function relies on the internal representation of
 * of the message cache for the current mailbox. As such, it is fragile
 * because the underlying implementation can change. I will present it
 * to the squirrelmail maintainers as a proposed addition to the API,
 * perhaps even as inline code to sqimap_mailbox_expunge(). In the 
 * meantime, you have been warned. [alane@geeksrus.net 2001/05/06]
 */

function delete_move_del_arr_elem($arr, $index) {
    $tmp = array();
    $lim = count($arr);
    $j = 0;
    for ($i = 0; $i < $lim; $i++) {
        if ($i != $index) {
            $tmp[$j++] = $arr[$i];
        }
    }
    return $tmp;
}

function delete_move_show_msg_array() {
    global $msort, $msgs;
    
    $keys = array_keys($msort);
    for ($i = 0; $i < count($keys); $i++) {
        echo '<p>key ' . $keys[$i] . ' msgid ' . $msgs[$keys[$i]]['ID'] . '</p>';
    }
}


function delete_move_expunge_from_all($id) {
    global $msgs, $msort, $sort, $imapConnection, $mailbox;
    
    // delete_move_show_msg_array();
    
    $delAt = -1;
    for ($i = 0; $i < count($msort); $i++) {
        if ($msgs[$i]['ID'] == $id) {
            $delAt = $i;
        } elseif ($msgs[$i]['ID'] > $id) {
            $msgs[$i]['ID']--;
        }
    }
    
    $msgs = delete_move_del_arr_elem($msgs, $delAt);
    $msort = delete_move_del_arr_elem($msort, $delAt);
    
    if ($sort < 6) {
        if ($sort % 2) {
            asort($msort);
        } else {
            arsort($msort);
        }
    }
    
    sqimap_mailbox_expunge($imapConnection, $mailbox, true);
}

function delete_move_next_action() {

    global $PHP_SELF,
           $delete_id, $move_id;

    if ($delete_id) {
        delete_move_next_delete();
        fix_sort_array();
    } elseif ($move_id) {
        delete_move_next_move();
        fix_sort_array();
    }

}

function delete_move_next_read_t() {

    global $delete_move_next_t;

    if($delete_move_next_t == 'on') {
        delete_move_next_read('top');
    }
}

function delete_move_next_read_b() {

    global $delete_move_next_b;

    if ($delete_move_next_b != 'off') {
        delete_move_next_read('bottom');
    }
}


function delete_move_next_read($currloc) {
    global $delete_move_next_formATtop, $delete_move_next_formATbottom,
           $color, $where, $what, $currentArrayIndex, $passed_id,
           $urlMailbox, $sort, $startMessage, $delete_id, $move_id,
           $imapConnection, $auto_expunge, $move_to_trash;

    if (!(($where && $what) || ($currentArrayIndex == -1))) {
        $next = findNextMessage();
        $prev = findPreviousMessage();
        $prev_if_del = $prev;
        $next_if_del = $next;
        if ($auto_expunge || $move_to_trash) {
            if ($prev_if_del > $passed_id) {
                $prev_if_del--;
            }
            if ($next_if_del > $passed_id) {
                $next_if_del--;
            }
        }    
        
	/* Base is illegal within documents 
        * $location = get_location();
        * echo "<base href=\"$location/\">" . */
        echo '<table cellspacing=0 width="100%" border=0 cellpadding=2>'.
             '<tr>'.
                 "<td bgcolor=\"$color[9]\" width=\"100%\" align=center><small>";
    
        if ($prev > 0) {
            echo "<a href=\"read_body.php?passed_id=$prev&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Previous") . "</A>&nbsp;|&nbsp;\n";
        } else {
            echo _("Previous") . "&nbsp;|&nbsp;";
        }
        if ($next > 0) {
            echo "<a href=\"read_body.php?passed_id=$next&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0\">" . _("Next") . "</A>&nbsp;|&nbsp;\n";
        } else {
            echo _("Next") . "&nbsp;|&nbsp;";
        }
        if ($prev > 0){
            echo "<a href=\"read_body.php?passed_id=$prev_if_del&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0&amp;delete_id=$passed_id\">" . _("Delete & Prev") . "</a>" . "&nbsp;|&nbsp;\n";
        }
        else {
            echo _("Delete & Prev") . "&nbsp;|&nbsp;";
        }
        if ($next > 0){
            echo "<a href=\"read_body.php?passed_id=$next_if_del&amp;mailbox=$urlMailbox&amp;sort=$sort&amp;startMessage=$startMessage&amp;show_more=0&amp;delete_id=$passed_id\">" . _("Delete & Next") . "</a>\n";
        } else {
            echo _("Delete & Next");
        }
        echo '</small></td></tr>';
        
        if ($next_if_del < 0) {
            $next_if_del = $prev_if_del;
        }
        if (($delete_move_next_formATtop == 'on') && ($currloc == 'top')){
            if ($next_if_del > 0) {
                delete_move_next_moveNextForm($next_if_del);
            } else {
                delete_move_next_moveRightMainForm();
            }
        }
        if (($delete_move_next_formATbottom != 'off') && ($currloc == 'bottom')){
            if ($next_if_del > 0) {
                delete_move_next_moveNextForm($next_if_del);
            } else {
                delete_move_next_moveRightMainForm();
            }
        }
        echo '</table>';
    }
}

function get_move_target_list() {
    global $imapConnection;
    
    $boxes = sqimap_mailbox_list($imapConnection);
    for ($i = 0; $i < count($boxes); $i++) {  
        if (!in_array('noselect', $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
            if ( $box2 == 'INBOX' ) {
                $box2 = _("INBOX");
            }
            echo "<option value=\"$box\">$box2</option>\n";
        }
    }
}

function delete_move_next_moveNextForm($next) {

    global $color, $where, $what, $currentArrayIndex, $passed_id,
           $urlMailbox, $sort, $startMessage, $delete_id, $move_id,
           $imapConnection;

    echo '<tr>'.
         "<td bgcolor=\"$color[9]\" width=\"100%\" align=\"center\">".
           '<form action="read_body.php" method="post"><small>'.
            "<input type=\"hidden\" name=\"passed_id\" value=\"$next\">".
            "<input type=\"hidden\" name=\"mailbox\" value=\"".urldecode($urlMailbox)."\">".
            "<input type=\"hidden\" name=\"sort\" value=\"$sort\">".
            "<input type=\"hidden\" name=\"startMessage\" value=\"$startMessage\">".
            "<input type=\"hidden\" name=\"show_more\" value=\"0\">".
            "<input type=\"hidden\" name=\"move_id\" value=\"$passed_id\">".
            _("Move to:") .
            ' <select name="targetMailbox">';
    get_move_target_list(); 
    echo    '</select> '.
            '<input type="submit" value="' . _("Move") . '">'.
            '</small>'.
           '</form>'.
         '</td>'.
         '</tr>';

}
function delete_move_next_moveRightMainForm() {

    global $color, $where, $what, $currentArrayIndex, $passed_id,
           $urlMailbox, $sort, $startMessage, $delete_id, $move_id,
           $imapConnection;

    echo '<tr>' .      
            "<td bgcolor=\"$color[9]\" width=\"100%\" align=\"center\">".
            '<form action="right_main.php" method="post"><small>' .
            "<input type=\"hidden\" name=\"mailbox\" value=\"".urldecode($urlMailbox)."\">".
            "<input type=\"hidden\" name=\"sort\" value=\"$sort\">".
            "<input type=\"hidden\" name=\"startMessage\" value=\"$startMessage\">".
            "<input type=\"hidden\" name=\"move_id\" value=\"$passed_id\">".
            _("Move to:") .
            ' <select name="targetMailbox">';
    get_move_target_list(); 
    echo    ' </select>' .
            '<input type=submit value="' . _("Move") . '">'.
            '</small>'.
         '</form>' .
         '</td>'.
         '</tr>';

}


function delete_move_next_delete() {
    global $imapConnection, $delete_id, $mailbox, $auto_expunge;
    
    sqimap_messages_delete($imapConnection, $delete_id, $delete_id, $mailbox);
    if ($auto_expunge){
        delete_move_expunge_from_all($delete_id);
        // sqimap_mailbox_expunge($imapConnection, $mailbox, true);    
    }
}

function delete_move_next_move() {
    global $imapConnection, $move_id, $targetMailbox, $auto_expunge, $mailbox;
    
    // Move message
    sqimap_messages_copy($imapConnection, $move_id, $move_id, $targetMailbox);
    sqimap_messages_flag($imapConnection, $move_id, $move_id, 'Deleted');
    if ($auto_expunge) {
        delete_move_expunge_from_all($move_id);
        // sqimap_mailbox_expunge($imapConnection, $mailbox, true);
    }
}

function delete_move_next_display_inside() {
    global $username,$data_dir,
        $delete_move_next_t, $delete_move_next_formATtop,
        $delete_move_next_b, $delete_move_next_formATbottom;
    
    echo "<tr><td align=right valign=top>\n".
         _("Delete/Move/Next Buttons:") . "</td>\n".
         "<td><input type=checkbox name=delete_move_next_ti";
         
    if ($delete_move_next_t == 'on') {
        echo " checked";
    }
    echo '> ' . _("Display at top").
         " <input type=checkbox name=delete_move_next_formATtopi";
         
    if ($delete_move_next_formATtop == 'on') {
        echo ' checked';
    }
    echo '> ' . _("with move option") . '<br>';    
    
    echo '<input type=checkbox name=delete_move_next_bi';
    if($delete_move_next_b != 'off') {
        echo ' checked';
    }
    echo '> ' . _("Display at bottom") .
         '<input type=checkbox name=delete_move_next_formATbottomi';
         
    if ($delete_move_next_formATbottom != 'off') {
        echo ' checked';
    }
    echo '> ' . _("with move option") . '<br>'.
         "</td></tr>\n";
}

function delete_move_next_display_save() {

    global $username,$data_dir,
           $delete_move_next_ti, $delete_move_next_formATtopi,
           $delete_move_next_bi, $delete_move_next_formATbottomi;
    
    if (isset($delete_move_next_ti)) {
        setPref($data_dir, $username, 'delete_move_next_t', 'on');
    } else {
        setPref($data_dir, $username, 'delete_move_next_t', "off");
    }
    
    if (isset($delete_move_next_formATtopi)) {
        setPref($data_dir, $username, 'delete_move_next_formATtop', 'on');
    } else {
        setPref($data_dir, $username, 'delete_move_next_formATtop', "off");
    }
    
    
    if (isset($delete_move_next_bi)) {
        setPref($data_dir, $username, 'delete_move_next_b', 'on');
    } else {
        setPref($data_dir, $username, 'delete_move_next_b', "off");
    }
    
    if (isset($delete_move_next_formATbottomi)) {
        setPref($data_dir, $username, 'delete_move_next_formATbottom', 'on');
    } else {
        setPref($data_dir, $username, 'delete_move_next_formATbottom', "off");
    }

}


function delete_move_next_loading_prefs() {
    global $username,$data_dir,
           $delete_move_next_t, $delete_move_next_formATtop,
           $delete_move_next_b, $delete_move_next_formATbottom;
    
    $delete_move_next_t = getPref($data_dir, $username, 'delete_move_next_t');
    $delete_move_next_b = getPref($data_dir, $username, 'delete_move_next_b');
    $delete_move_next_formATtop = getPref($data_dir, $username, 'delete_move_next_formATtop');
    $delete_move_next_formATbottom = getPref($data_dir, $username, 'delete_move_next_formATbottom');

}

?>
