<?php

/**
 * options_order.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays message highlighting options
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/display_messages.php');
require_once('../functions/imap.php');
require_once('../functions/array.php');
require_once('../functions/plugin.php');

if (! isset($action)) { $action = ''; }
if ($action == 'delete' && isset($theid)) {
    removePref($data_dir, $username, "highlight$theid");
} elseif ($action == 'save') {
} 
displayPageHeader($color, 'None');
?>
   <br>
<table width="95%" align=center border=0 cellpadding=2 cellspacing=0>
<tr><td align="center" bgcolor="<?php echo $color[0] ?>">

      <b><?php echo _("Options") . " - " . _("Index Order"); ?></b>

    <table width="100%" border="0" cellpadding="1" cellspacing="1">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center"><br>
<?php
 
    $available[1] = _("Checkbox");
    $available[2] = _("From");
    $available[3] = _("Date");
    $available[4] = _("Subject");
    $available[5] = _("Flags");
    $available[6] = _("Size");
    
    if (! isset($method)) { $method = ''; }
 
    if ($method == 'up' && $num > 1) {
        $prev = $num-1;
        $tmp = $index_order[$prev];
        $index_order[$prev] = $index_order[$num];
        $index_order[$num] = $tmp;
    } else if ($method == 'down' && $num < count($index_order)) {
        $next = $num++;
        $tmp = $index_order[$next];
        $index_order[$next] = $index_order[$num];
        $index_order[$num] = $tmp;
    } else if ($method == 'remove' && $num) {
        for ($i=1; $i < 8; $i++) {
            removePref($data_dir, $username, "order$i"); 
        }
        for ($j=1,$i=1; $i <= count($index_order); $i++) {
           if ($i != $num) {
               $new_ary[$j] = $index_order[$i];
               $j++;
           }
        }
        $index_order = array();
        $index_order = $new_ary;
        if (count($index_order) < 1) {
           include_once('../src/load_prefs.php');
        }
    } else if ($method == 'add' && $add) {
        /* User should not be able to insert PHP-code here */
        $add = str_replace ('<?', '..', $add);
        $add = ereg_replace ('<.*script.*language.*php.*>', '..', $add);
        $add = str_replace ('<%', '..', $add);
        $index_order[count($index_order)+1] = $add;
    }
 
    if ($method) {
        for ($i=1; $i <= count($index_order); $i++) {
           setPref($data_dir, $username, "order$i", $index_order[$i]);
        }
    }
    echo '<table cellspacing="0" cellpadding="0" border="0" width="65%"><tr><td>' . "\n";
    echo _("The index order is the order that the columns are arranged in the message index.  You can add, remove, and move columns around to customize them to fit your needs.");
    echo '</td></tr></table><br>';
 
    if (count($index_order))
    {
        echo '<table cellspacing="0" cellpadding="0" border="0">' . "\n";
        for ($i=1; $i <= count($index_order); $i++) {
            $tmp = $index_order[$i];
            echo '<tr>';
            echo "<td><small><a href=\"options_order.php?method=up&amp;num=$i\">". _("up") ."</a></small></td>\n";
            echo '<td><small>&nbsp;|&nbsp;</small></td>' . "\n";
            echo "<td><small><a href=\"options_order.php?method=down&amp;num=$i\">". _("down") . "</a></small></td>\n";
            echo '<td><small>&nbsp;|&nbsp;</small></td>' . "\n";
            echo '<td>';
            /* Always show the subject */
            if ($tmp != 4)
               echo "<small><a href=\"options_order.php?method=remove&amp;num=$i\">" . _("remove") . '</a></small>';
            echo "</td>\n";
            echo '<td><small>&nbsp;-&nbsp;</small></td>' . "\n";
            echo '<td>' . $available[$tmp] . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    if (count($index_order) != count($available)) {
        echo '<form name="f" method="post" action="options_order.php">';
        echo '<select name="add">';
        for ($i=1; $i <= count($available); $i++) {
            $found = false;
            for ($j=1; $j <= count($index_order); $j++) {
                if ($index_order[$j] == $i) {
                    $found = true;
                }
            }
            if (!$found) {
                echo "<option value=\"$i\">$available[$i]</option>";
            }
        }
        echo '</select>';
        echo '<input type="hidden" value="add" name="method">';
        echo '<input type="submit" value="'._("Add").'" name="submit">';
        echo '</form>';
    }
 
    echo '<p><a href="../src/options.php">' . _("Return to options page") . '</a></p><br>';

?>
    </td></tr>
    </table>

</td></tr>
</table>
</body></html>
