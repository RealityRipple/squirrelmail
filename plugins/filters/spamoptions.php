<?php
/**
 * Message and Spam Filter Plugin
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This plugin filters your inbox into different folders based upon given
 * criteria.  It is most useful for people who are subscibed to mailing lists
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

/* Path for SquirrelMail required files. */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'plugins/filters/filters.php');
global $AllowSpamFilters;

displayPageHeader($color, 'None');

if (isset($spam_submit)) {
    $spam_filters = load_spam_filters();
    setPref($data_dir, $username, 'filters_spam_folder', $filters_spam_folder_set);
    setPref($data_dir, $username, 'filters_spam_scan', $filters_spam_scan_set);
    foreach ($spam_filters as $Key => $Value) {
        $input = $spam_filters[$Key]['prefname'] . '_set';
        if ( isset( $$input ) ) {
            setPref( $data_dir, $username, $spam_filters[$Key]['prefname'],
                     $$input);
        } else {
            removePref($data_dir, $username, $spam_filters[$Key]['prefname']);
        }
    }
}

$filters_spam_folder = getPref($data_dir, $username, 'filters_spam_folder');
$filters_spam_scan = getPref($data_dir, $username, 'filters_spam_scan');
$filters = load_filters();

echo html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'th', _("Spam Filtering"), 'center' )
            ) ,
        'center', $color[0], 'width="95%" border="0" cellpadding="2" cellspacing="0"' );

if ($SpamFilters_YourHop == ' ') {
    echo '<br>' .
        html_tag( 'div', '<b>' .
            _("WARNING! Tell your admin to set the SpamFilters_YourHop variable") .
            '</b>' ,
        'center' ) .
        '<br>';
}


if (isset($action) && $action == 'spam') {
    $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
    $boxes = sqimap_mailbox_list($imapConnection);
    sqimap_logout($imapConnection);
    for ($i = 0; $i < count($boxes) && $filters_spam_folder == ''; $i++) {

        if ($boxes[$i]['flags'][0] != 'noselect' &&
            $boxes[$i]['flags'][1] != 'noselect' &&
            $boxes[$i]['flags'][2] != 'noselect') {
            $filters_spam_folder = $boxes[$i]['unformatted'];
        }
    }

    echo '<form method=post action="spamoptions.php">'.
        '<center>'.
        html_tag( 'table', '', '', '', 'width="85%" border="0" cellpadding="2" cellspacing="0"' ) .
            html_tag( 'tr' ) .
                html_tag( 'th', _("Move spam to:"), 'right', '', 'nowrap' ) .
                html_tag( 'td', '', 'left' ) .
                    '<select name="filters_spam_folder_set">';

    for ($i = 0; $i < count($boxes); $i++) {
        if (! in_array('noselect', $boxes[$i]['flags'])) {
            $box = $boxes[$i]['unformatted'];
            $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
            if ($filters_spam_folder == $box) {
                echo "<OPTION VALUE=\"$box\" SELECTED>$box2</OPTION>\n";
            } else {
                echo "<OPTION VALUE=\"$box\">$box2</OPTION>\n";
            }
        }
    }
    echo    '</select>'.
        '</td>'.
        '</tr>'.
        html_tag( 'tr',
            html_tag( 'td', '&nbsp;' ) .
            html_tag( 'td',
                _("Moving spam directly to the trash may not be a good idea at first, since messages from friends and mailing lists might accidentally be marked as spam. Whatever folder you set this to, make sure that it gets cleaned out periodically, so that you don't have an excessively large mailbox hanging around.") ,
            'left' )
        ) .
        html_tag( 'tr' ) .
            html_tag( 'th', _("What to Scan:"), 'right', '', 'nowrap' ) .
            html_tag( 'td' ) .
            '<select name="filters_spam_scan_set">'.
            '<option value=""';
    if ($filters_spam_scan == '') {
        echo ' SELECTED';
    }
    echo '>' . _("All messages") . '</option>'.
            '<option value="new"';
    if ($filters_spam_scan == 'new') {
        echo ' SELECTED';
    }
    echo '>' . _("Only unread messages") . '</option>' .
            '</select>'.
        '</td>'.
    '</tr>'.
    html_tag( 'tr',
          html_tag( 'td', '&nbsp;' ) .
          html_tag( 'td',
              _("The more messages you scan, the longer it takes.  I would suggest that you scan only new messages.  If you make a change to your filters, I would set it to scan all messages, then go view my INBOX, then come back and set it to scan only new messages.  That way, your new spam filters will be applied and you'll scan even the spam you read with the new filters.") ,
          'left' )
      );

    $spam_filters = load_spam_filters();

    foreach ($spam_filters as $Key => $Value) {
        echo html_tag( 'tr' ) .
                   html_tag( 'th', $Key, 'right', '', 'nowrap' ) ."\n" .
                   html_tag( 'td' ) .
            '<input type=checkbox name="' .
            $spam_filters[$Key]['prefname'] .
            '_set"';
        if ($spam_filters[$Key]['enabled']) {
            echo ' CHECKED';
        }
        echo '> - ';
        if ($spam_filters[$Key]['link']) {
            echo '<a href="' .
                 $spam_filters[$Key]['link'] .
                 '" target="_blank">';
        }
        echo $spam_filters[$Key]['name'];
        if ($spam_filters[$Key]['link']) {
            echo '</a>';
        }
        echo '</td></tr>' .
        html_tag( 'tr',
            html_tag( 'td', '&nbsp;' ) .
            html_tag( 'td', $spam_filters[$Key]['comment'], 'left' )
        ) . "\n";

    }
    echo html_tag( 'tr',
        html_tag( 'td', '<input type=submit name="spam_submit" value="' . _("Save") . '">', 'center', '', 'colspan="2"' )
    ) . "\n" .
        '</table>'.
        '</center>'.
        '</form>';

}

if (! isset($action) || $action != 'spam') {

    echo html_tag( 'p', '', 'center' ) .
         '[<a href="spamoptions.php?action=spam">' . _("Edit") . '</a>]' .
         ' - [<a href="../../src/options.php">' . _("Done") . '</a>]</center><br><br>';
    printf( _("Spam is sent to <b>%s</b>"), ($filters_spam_folder?$filters_spam_folder:_("[<i>not set yet</i>]") ) );
    echo '<br>';
    printf( _("Spam scan is limited to <b>%s</b>"), (($filters_spam_scan == 'new')?_("New Messages Only"):_("All Messages") ) );
    echo '</p>'.
        "<table border=0 cellpadding=3 cellspacing=0 align=center bgcolor=\"$color[0]\">";

    $spam_filters = load_spam_filters();

    foreach ($spam_filters as $Key => $Value) {
        echo html_tag( 'tr' ) .
                    html_tag( 'th', '', 'center' );

        if ($spam_filters[$Key]['enabled']) {
            echo _("ON");
        } else {
            echo _("OFF");
        }

        echo '</th>' . 
               html_tag( 'td', '&nbsp;-&nbsp;', 'left' ) .
               html_tag( 'td', '', 'left' );

        if ($spam_filters[$Key]['link']) {
        echo '<a href="' .
            $spam_filters[$Key]['link'] .
            '" target="_blank">';
        }

        echo $spam_filters[$Key]['name'];
        if ($spam_filters[$Key]['link']) {
        echo '</a>';
        }
        echo "</td></tr>\n";
    }
    echo '</table>';
}

?>
