<?php

/**
 * options_order.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Displays messagelist column order options
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/forms.php');

/* get globals */
sqgetGlobalVar('num',       $num,       SQ_GET);  
sqgetGlobalVar('add',       $add,       SQ_POST);

sqgetGlobalVar('submit',    $submit);
sqgetGlobalVar('method',    $method);
/* end of get globals */

displayPageHeader($color, 'None');

   echo
   html_tag( 'table', '', 'center', '', 'width="95%" border="0" cellpadding="1" cellspacing="0"' ) . 
   html_tag( 'tr' ) . 
   html_tag( 'td', '', 'center', $color[0] ) .
   '<b>' . _("Options") . ' - ' . _("Index Order") . '</b>' .
   html_tag( 'table', '', '', '', 'width="100%" border="0" cellpadding="8" cellspacing="0"' ) . 
   html_tag( 'tr' ) . 
   html_tag( 'td', '', 'center', $color[4] );
 
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
    echo html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td',
                        _("The index order is the order that the columns are arranged in the message index. You can add, remove, and move columns around to customize them to fit your needs.")
                    )
                ) ,
            '', '', '', 'width="65%" border="0" cellpadding="0" cellspacing="0"' ) . "<br />\n";
 
    if (count($index_order))
    {
        echo html_tag( 'table', '', '', '', ' cellspacing="0" cellpadding="0" border="0"' ) . "\n";
        for ($i=1; $i <= count($index_order); $i++) {
            $tmp = $index_order[$i];
            echo html_tag( 'tr' );
            echo html_tag( 'td', '<small><a href="options_order.php?method=up&amp;num=' . $i . '">'. _("up") .'</a></small>' );
            echo html_tag( 'td', '<small>&nbsp;|&nbsp;</small>' );
            echo html_tag( 'td', '<small><a href="options_order.php?method=down&amp;num=' . $i . '">'. _("down") .'</a></small>' );
            echo html_tag( 'td', '<small>&nbsp;|&nbsp;</small>' );
            echo html_tag( 'td' );
            /* Always show the subject */
            if ($tmp != 4)
               echo '<small><a href="options_order.php?method=remove&amp;num=' . $i . '">' . _("remove") . '</a></small>';
            else
               echo '&nbsp;'; 
            echo '</td>';
            echo html_tag( 'td', '<small>&nbsp;-&nbsp;</small>' );
            echo html_tag( 'td', $available[$tmp] );
            echo '</tr>' . "\n";
        }
        echo '</table>' . "\n";
    }
    
    if (count($index_order) != count($available)) {

        $opts = array();
        for ($i=1; $i <= count($available); $i++) {
            $found = false;
            for ($j=1; $j <= count($index_order); $j++) {
                if ($index_order[$j] == $i) {
                    $found = true;
                }
            }
            if (!$found) {
                $opts[$i] = $available[$i];
            }
        }

        echo addForm('options_order.php', 'post', 'f');
        echo addSelect('add', $opts, '', TRUE);
        echo addHidden('method', 'add');
        echo addSubmit(_("Add"), 'submit');
        echo '</form>';
    }
 
    echo html_tag( 'p', '<a href="../src/options.php">' . _("Return to options page") . '</a></p><br />' );

?>
    </td></tr>
    </table>

</td></tr>
</table>
</body></html>
