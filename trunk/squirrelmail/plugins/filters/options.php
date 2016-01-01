<?php

/**
 * Message and Spam Filter Plugin - Filtering Options
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage filters
 */

/**
 * Include the SquirrelMail initialization file.
 */
require('../../include/init.php');
include_once(SM_PATH . 'functions/imap_general.php');
include_once(SM_PATH . 'functions/forms.php');
include_once(SM_PATH . 'plugins/filters/filters.php');

displayPageHeader($color);

/* get globals */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);

sqgetGlobalVar('theid', $theid);
sqgetGlobalVar('action', $action, SQ_GET);
global $imap_stream_options; // in case not defined in config

if (sqgetGlobalVar('filter_submit',$filter_submit,SQ_POST)) {

    if(! isset($theid) ) $theid = 0;

    $complete_post=true;

    // FIXME: write human readable error messages
    sqgetGlobalVar('filter_what', $filter_what, SQ_POST);
    if (!sqgetGlobalVar('filter_what', $filter_what, SQ_POST)) {
        do_error("Post error");
        $complete_post=false;
    }

    sqgetGlobalVar('filter_where', $filter_where, SQ_POST);
    if (!sqgetGlobalVar('filter_where', $filter_where, SQ_POST)) {
        do_error("Post error");
        $complete_post=false;
    }

    sqgetGlobalVar('filter_folder', $filter_folder, SQ_POST);
    if (!sqgetGlobalVar('filter_folder', $filter_folder, SQ_POST)) {
        do_error("Post error");
        $complete_post=false;
    }

    if ($complete_post) {
        $filter_what = str_replace(',', '###COMMA###', $filter_what);
        $filter_what = str_replace("\\\\", "\\", $filter_what);
        $filter_what = str_replace("\\\"", '"', $filter_what);
        $filter_what = str_replace('"', '&quot;', $filter_what);

        if (empty($filter_what)) {
            do_error(_("WARNING! You must enter something to search for."));
            $action = 'edit';
        }

        if (($filter_where == 'Header') && (strchr($filter_what,':') == '')) {
            do_error(_("WARNING! Header filters should be of the format &quot;Header: value&quot;"));
            $action = 'edit';
        }
        if ($action != 'edit') {
        setPref($data_dir, $username, 'filter'.$theid, $filter_where.','.$filter_what.','.$filter_folder);
        }
        $filters[$theid]['where'] = $filter_where;
        $filters[$theid]['what'] = $filter_what;
        $filters[$theid]['folder'] = $filter_folder;
    }
} elseif (isset($action) && $action == 'delete') {
      remove_filter($theid);
} elseif (isset($action) && $action == 'move_up') {
      filter_swap($theid, $theid - 1);
} elseif (isset($action) && $action == 'move_down') {
      filter_swap($theid, $theid + 1);
} elseif (sqgetGlobalVar('user_submit',$user_submit,SQ_POST)) {
    sqgetGlobalVar('filters_user_scan_set',$filters_user_scan_set,SQ_POST);
    setPref($data_dir, $username, 'filters_user_scan', $filters_user_scan_set);
    echo '<br /><div style="text-align: center;"><b>'._("Saved Scan type")."</b></div>\n";
}

   $filters = load_filters();
   $filters_user_scan = getPref($data_dir, $username, 'filters_user_scan');

   echo html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td',
                    '<div style="text-align: center;"><b>' . _("Options") . ' - ' . _("Message Filtering") . '</b></div>' ,
                    'left', $color[0]
                )
            ),
            'center', '', 'width="95%" border="0" cellpadding="2" cellspacing="0"'
        ) .
        '<br /><form method="post" action="options.php">'.
        html_tag( 'table', '', 'center', '', 'border="0" cellpadding="2" cellspacing="0"' ) .
            html_tag( 'tr' ) .
                html_tag( 'th', _("What to Scan:"), 'right', '', 'style="white-space: nowrap;"' ) .
                html_tag( 'td', '', 'left' ) .
            '<select name="filters_user_scan_set">'.
            '<option value=""';
    if ($filters_user_scan == '') {
        echo ' selected="selected"';
    }
    echo '>' . _("All messages") . '</option>'.
            '<option value="new"';
    if ($filters_user_scan == 'new') {
        echo ' selected="selected"';
    }
    echo '>' . _("Only unread messages") . '</option>' .
            '</select>'.
        '</td>'.
        html_tag( 'td', '<input type="submit" name="user_submit" value="' . _("Save") . '" />', 'left' ) .
        '</table>'.
        '</form>'.

        html_tag( 'div', '[<a href="options.php?action=add">' . _("New") .
            '</a>] - [<a href="'.SM_PATH.'src/options.php">' . _("Done") . '</a>]' ,
        'center' ) . '<br />';

    if (isset($action) && ($action == 'add' || $action == 'edit')) {

        $imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
        $boxes = sqimap_mailbox_list($imapConnection);

        for ($a = 0, $cnt = count($boxes); $a < $cnt; $a++) {
            if (strtolower($boxes[$a]['formatted']) == 'inbox') {
                unset($boxes[$a]);
            }
        }

        sqimap_logout($imapConnection);
        if ( !isset($theid) ) {
            $theid = count($filters);
        }
        echo html_tag( 'div', '', 'center' ) .
             '<form action="options.php" method="post">'.
             html_tag( 'table', '', '', '', 'border="0" cellpadding="2" cellspacing="0"' ) .
             html_tag( 'tr' ) .
                html_tag( 'td', _("Match:"), 'left' ) .
                html_tag( 'td', '', 'left' ) .
                    '<select name="filter_where">';

        $L = isset($filters[$theid]['where']);

        $sel = (($L && $filters[$theid]['where'] == 'From')?' selected="selected"':'');
        echo "<option value=\"From\"$sel>" . _("From") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'To')?' selected="selected"':'');
        echo "<option value=\"To\"$sel>" . _("To") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Cc')?' selected="selected"':'');
        echo "<option value=\"Cc\"$sel>" . _("Cc") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'To or Cc')?' selected="selected"':'');
        echo "<option value=\"To or Cc\"$sel>" . _("To or Cc") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Subject')?' selected="selected"':'');
        echo "<option value=\"Subject\"$sel>" . _("Subject") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Message Body')?' selected="selected"':'');
        echo "<option value=\"Message Body\"$sel>" . _("Message Body") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Header and Body')?' selected="selected"':'');
        echo "<option value=\"Header and Body\"$sel>" . _("Header and Body") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Header')?' selected="selected"':'');
        echo "<option value=\"Header\"$sel>" . _("Header") . '</option>';

        echo         '</select>'.
                '</td>'.
            '</tr>'.
            html_tag( 'tr' ) .
                html_tag( 'td', _("Contains:"), 'right' ) .
                html_tag( 'td', '', 'left' ) .
                    '<input type="text" size="32" name="filter_what" value="';
        if (isset($filters[$theid]['what'])) {
            echo sm_encode_html_special_chars($filters[$theid]['what']);
        }
        echo '" />'.
                '</td>'.
            '</tr>'.
            html_tag( 'tr' ) .
                html_tag( 'td', _("Move to:"), 'left' ) .
                html_tag( 'td', '', 'left' ) .
                    '<tt>'.
                    '<select name="filter_folder">';
        $selected = 0;
        if ( isset($filters[$theid]['folder']) )
          $selected = array(strtolower($filters[$theid]['folder']));
        echo sqimap_mailbox_option_list(0, $selected, 0, $boxes);
        echo        '</select>'.
                    '</tt>'.
                '</td>'.
            '</tr>'.
            '</table>'.
            '<input type="submit" name="filter_submit" value="' . _("Submit") . "\" />\n".
            addHidden('theid', $theid).
            '</form>'.
            '</div>';

    }

if (count($filters)) {
    echo html_tag( 'table', '', 'center', '', 'border="0" cellpadding="3" cellspacing="0"' );

    for ($i=0, $num = count($filters); $i < $num; $i++) {

        $clr = (($i % 2)?$color[0]:$color[9]);
        $fdr = ($folder_prefix)?str_replace($folder_prefix, "", $filters[$i]["folder"]):$filters[$i]["folder"];
        echo html_tag( 'tr', '', '', $clr ) .
                   html_tag( 'td',
                       '<small>' .
                       "[<a href=\"options.php?theid=$i&amp;action=edit\">" . _("Edit") . '</a>]'.
                       '</small>' ,
                   'left' ) .
                   html_tag( 'td',
                       '<small>' .
                       "[<a href=\"options.php?theid=$i&amp;action=delete\">" . _("Delete") . '</a>]'.
                       '</small>' ,
                   'left' );

        if ($num > 1) {
            echo html_tag( 'td', '', 'center' ) . '<small>[';
            if (isset($filters[$i + 1])) {
                echo "<a href=\"options.php?theid=$i&amp;action=move_down\">" . _("Down") . '</a>';
                if ($i > 0) {
                    echo '&nbsp;|&nbsp;';
                }
            }
            if ($i > 0) {
                echo "<a href=\"options.php?theid=$i&amp;action=move_up\">" . _("Up") . '</a>';
            }
            echo ']</small></td>';
        }
        echo html_tag( 'td', '-', 'left' ) .
             html_tag( 'td', '', 'left' );
        printf( _("If %s contains %s then move to %s"),
            '<b>'.$filters[$i]['where'].'</b>',
            '<b>'.$filters[$i]['what'].'</b>',
            '<b>'.sm_encode_html_special_chars(imap_utf7_decode_local($fdr)).'</b>');
        echo '</td></tr>';

    }
    echo '</table>';
}
    echo html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '&nbsp;', 'left' )
            ) ,
        'center', '', 'width="80%" border="0" cellpadding="2" cellspacing="0"' );
    echo '</body></html>';
