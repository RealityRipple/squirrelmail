<?php

/**
 * Message and Spam Filter Plugin
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This plugin filters your inbox into different folders based upon given
 * criteria. It is most useful for people who are subscibed to mailing lists
 * to help organize their messages.  The argument stands that filtering is
 * not the place of the client, which is why this has been made a plugin for
 * SquirrelMail.  You may be better off using products such as Sieve or
 * Procmail to do your filtering so it happens even when SquirrelMail isn't
 * running.
 *
 * If you need help with this, or see improvements that can be made, please
 * email me directly at the address above.  I definately welcome suggestions
 * and comments.  This plugin, as is the case with all SquirrelMail plugins,
 * is not directly supported by the developers.  Please come to me off the
 * mailing list if you have trouble with it.
 *
 * Also view plugins/README.plugins for more information.
 *
 * $Id$
 */

   chdir ('..');
   require_once('../src/validate.php');
   require_once('../functions/page_header.php');
   require_once('../functions/imap.php');
   require_once('../src/load_prefs.php');

   global $AllowSpamFilters;

   displayPageHeader($color, 'None');

   if (isset($filter_submit)) {
      if (!isset($theid)) $theid = 0;
      $filter_what = ereg_replace(",", " ", $filter_what);
      $filter_what = str_replace("\\\\", "\\", $filter_what);
      $filter_what = str_replace("\\\"", "\"", $filter_what);
      $filter_what = str_replace("\"", "&quot;", $filter_what);

      if (($filter_where == 'Header') && (strchr($filter_what,':') == '')) {
         print ('WARNING! Header filters should be of the format "Header: value"<BR>');
	 $action = 'edit';
      }
      setPref($data_dir, $username, "filter".$theid, $filter_where.",".$filter_what.",".$filter_folder);
      $filters[$theid]["where"] = $filter_where;
      $filters[$theid]["what"] = $filter_what;
      $filters[$theid]["folder"] = $filter_folder;
   } elseif (isset($action) && $action == 'delete') {
      remove_filter($theid);
   } elseif (isset($action) && $action == 'move_up') {
      filter_swap($theid, $theid - 1);
   } elseif (isset($action) && $action == 'move_down') {
      filter_swap($theid, $theid + 1);
   } elseif (isset($user_submit)) {
       echo "<br><center><b>"._("Saved Scan type")."</b></center>\n";
       setPref($data_dir, $username, 'filters_user_scan', $filters_user_scan_set);
   }

   $filters = load_filters();
   $filters_user_scan = getPref($data_dir, $username, 'filters_user_scan');

   echo '<br>' .
        '<table width=95% align=center border=0 cellpadding=2 cellspacing=0>'.
        "<tr><td bgcolor=\"$color[0]\">".
        '<center><b>' . _("Options") . ' -  ' . _("Message Filtering") . '</b></center>'.
        '</td></tr></table>'.

        '<br><form method=post action="options.php">'.
        '<center>'.
        '<table cellpadding=2 cellspacing=0 border=0>'.
        '<tr>'.
            '<th align=right nowrap>' . _("What to Scan:") . '</th>'.
            '<td><select name="filters_user_scan_set">'.
            '<option value=""';
    if ($filters_user_scan == '') {
        echo ' SELECTED';
    }
    echo '>' . _("All messages") . '</option>'.
            '<option value="new"';
    if ($filters_user_scan == 'new') {
        echo ' SELECTED';
    }
    echo '>' . _("Only unread messages") . '</option>' .
            '</select>'.
        '</td>'.
        '<td><input type=submit name="user_submit" value="' . _("Save") . '"></td></tr>'.
        '</table>'.
        '</center>'.
        '</form>'.

        '<center>[<a href="options.php?action=add">' . _("New") .
        '</a>] - [<a href="../../src/options.php">' . _("Done") . '</a>]</center><br>';

    if (isset($action) && ($action == 'add' || $action == 'edit')) {
        $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
        $boxes = sqimap_mailbox_list($imapConnection);
        sqimap_logout($imapConnection);
        if ( !isset($theid) ) {
            $theid = count($filters);
        }
        echo '<center>'.
             '<form action="options.php" method=post>'.
             '<table cellpadding=2 cellspacing=0 border=0>'.
             '<tr>'.
                '<td>' . _("Match:") . '</td>'.
                '<td>'.
                    '<select name=filter_where>';

        $L = isset($filters[$theid]['where']);

        $sel = (($L && $filters[$theid]['where'] == 'From')?'selected':'');
        echo "<option value=\"From\" $sel>" . _ ("From") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'To')?'selected':'');
        echo "<option value=\"To\" $sel>" . _ ("To") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Cc')?'selected':'');
        echo "<option value=\"Cc\" $sel>" . _ ("Cc") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'To or Cc')?'selected':'');
        echo "<option value=\"To or Cc\" $sel>" . _ ("To or Cc") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Subject')?'selected':'');
        echo "<option value=\"Subject\" $sel>" . _ ("Subject") . '</option>';

        $sel = (($L && $filters[$theid]['where'] == 'Header')?'selected':'');
        echo "<option value=\"Header\" $sel>" . _ ("Header") . '</option>';

        echo         '</select>'.
                '</td>'.
            '</tr>'.
            '<tr>'.
                '<td align=right>'.
                    _("Contains:").
                '</td>'.
                '<td>'.
                    '<input type=text size=32 name=filter_what value="';
        if (isset($filters[$theid]['what'])) {
            echo $filters[$theid]["what"];
        }
        echo '">'.
                '</td>'.
            '</tr>'.
            '<tr>'.
                '<td>'.
                    _("Move to:").
                '</td>'.
                '<td>'.
                    '<tt>'.
                    '<select name=filter_folder>';

        for ($i = 0; $i < count($boxes); $i++) {
            if (! in_array('noselect', $boxes[$i]['flags'])) {
                $box = $boxes[$i]['unformatted'];
                $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
                if (isset($filters[$theid]['folder']) &&
                    $filters[$theid]['folder'] == $box)
                echo "<OPTION VALUE=\"$box\" SELECTED>$box2</option>";
                else
                echo "<OPTION VALUE=\"$box\">$box2</option>";
            }
        }
        echo         '</tt>'.
                    '</select>'.
                '</td>'.
            '</tr>'.
            '</table>'.
            '<input type=submit name=filter_submit value=' . _("Submit") . '>'.
            "<input type=hidden name=theid value=$theid>".
            '</form>'.
            '</center>';

    }

	echo '<table border=0 cellpadding=3 cellspacing=0 align=center>';

    for ($i=0; $i < count($filters); $i++) {

        $clr = (($i % 2)?$color[0]:$color[9]);
        $fdr = ($folder_prefix)?str_replace($folder_prefix, "", $filters[$i]["folder"]):$filters[$i]["folder"];
        echo "<tr bgcolor=\"$clr\"><td><small>".
            "[<a href=\"options.php?theid=$i&action=edit\">" . _("Edit") . '</a>]'.
            '</small></td><td><small>'.
            "[<a href=\"options.php?theid=$i&action=delete\">" . _("Delete") . '</a>]'.
            '</small></td><td align=center><small>[';

        if (isset($filters[$i + 1])) {
            echo "<a href=\"options.php?theid=$i&action=move_down\">" . _("Down") . '</a>';
            if ($i > 0) {
                echo '&nbsp;|&nbsp;';
            }
        }
        if ($i > 0) {
            echo "<a href=\"options.php?theid=$i&action=move_up\">" . _("Up") . '</a>';
        }
        echo ']</small></td><td>-</td><td>';
        printf( _("If <b>%s</b> contains <b>%s</b> then move to <b>%s</b>"), _($filters[$i]['where']), $filters[$i]['what'], $fdr );
        echo '</td></tr>';

    }
    echo '</table>'.
        '<table width=80% align=center border=0 cellpadding=2 cellspacing=0">'.
            '<tr><td>&nbsp</td></tr>'.
        '</table>';

?>
