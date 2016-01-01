<?php

/**
 * Message and Spam Filter Plugin - Spam Options
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
include_once(SM_PATH . 'functions/imap_messages.php');
include_once(SM_PATH . 'plugins/filters/filters.php');

/* get globals */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);

sqgetGlobalVar('action', $action, SQ_GET);
global $imap_stream_options; // in case not defined in config
/* end globals */

displayPageHeader($color);

if (sqgetGlobalVar('spam_submit',$spam_submit,SQ_POST)) {
    $spam_filters = load_spam_filters();

    // setting spam folder
    sqgetGlobalVar('filters_spam_folder_set',$filters_spam_folder_set,SQ_POST);
    if (isset($filters_spam_folder_set)) {
        setPref($data_dir, $username, 'filters_spam_folder', $filters_spam_folder_set);
    } else {
        echo _("You must select a spam folder.");
    }

    // setting scan type
    sqgetGlobalVar('filters_spam_scan_set',$filters_spam_scan_set,SQ_POST);
    if (isset($filters_spam_scan_set)) {
        setPref($data_dir, $username, 'filters_spam_scan', $filters_spam_scan_set);
    } else {
        echo _("You must select a scan type.");
    }

    foreach ($spam_filters as $Key => $Value) {
        $input = $spam_filters[$Key]['prefname'] . '_set';
        if ( sqgetGlobalVar($input,$input_key,SQ_POST) ) {
            setPref( $data_dir, $username, $spam_filters[$Key]['prefname'],$input_key);
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
    echo '<br />' .
        html_tag( 'div', '<b>' .
            sprintf(_("WARNING! Tell the administrator to set the %s variable."), '&quot;SpamFilters_YourHop&quot;') .
            '</b>' ,
        'center' ) .
        '<br />';
}


if (isset($action) && $action == 'spam') {
    $imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0, $imap_stream_options);
    $boxes = sqimap_mailbox_list($imapConnection);
    sqimap_logout($imapConnection);
    $numboxes = count($boxes);

    for ($i = 0; $i < $numboxes && $filters_spam_folder == ''; $i++) {
        if ((isset($boxes[$i]['flags'][0]) && $boxes[$i]['flags'][0] != 'noselect') &&
            (isset($boxes[$i]['flags'][1]) && $boxes[$i]['flags'][1] != 'noselect') &&
            (isset($boxes[$i]['flags'][2]) && $boxes[$i]['flags'][2] != 'noselect')) {
            $filters_spam_folder = $boxes[$i]['unformatted'];
        }
    }

    echo '<form method="post" action="spamoptions.php">'.
        '<div style="text-align: center;">'.
        html_tag( 'table', '', '', '', 'width="85%" border="0" cellpadding="2" cellspacing="0"' ) .
            html_tag( 'tr' ) .
                html_tag( 'th', _("Move spam to:"), 'right', '', 'style="white-space: nowrap;"' ) .
                html_tag( 'td', '', 'left' ) .
                    '<select name="filters_spam_folder_set">';

        $selected = 0;
        if ( isset($filters_spam_folder) )
          $selected = array(strtolower($filters_spam_folder));
        echo sqimap_mailbox_option_list(0, $selected, 0, $boxes);
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
            html_tag( 'th', _("What to Scan:"), 'right', '', 'style="white-space: nowrap;"' ) .
            html_tag( 'td' ) .
            '<select name="filters_spam_scan_set">'.
            '<option value=""';
    if ($filters_spam_scan == '') {
        echo ' selected="selected"';
    }
    echo '>' . _("All messages") . '</option>'.
            '<option value="new"';
    if ($filters_spam_scan == 'new') {
        echo ' selected="selected"';
    }
    echo '>' . _("Unread messages only") . '</option>' .
            '</select>'.
        '</td>'.
    '</tr>'.
    html_tag( 'tr',
          html_tag( 'td', '&nbsp;' ) .
          html_tag( 'td',
              _("The more messages scanned, the longer it takes. It's recommended to scan unread messages only. If a change to the filters is made, it's recommended to set it to scan all messages, then go view the INBOX, then come back and set it to scan unread messages only. That way, the new spam filters will be applied and even the spam you didn't catch with the old filters will be scanned.") ,
          'left' )
      );

    $spam_filters = load_spam_filters();

    foreach ($spam_filters as $Key => $Value) {
        echo html_tag( 'tr' ) .
                   html_tag( 'th', $Key, 'right', '', 'style="white-space: nowrap;"' ) ."\n" .
                   html_tag( 'td' ) .
            '<input type="checkbox" name="' .
            $spam_filters[$Key]['prefname'] .
            '_set"';
        if ($spam_filters[$Key]['enabled']) {
            echo ' checked="checked"';
        }
        echo ' /> - ';
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
        html_tag( 'td', '<input type="submit" name="spam_submit" value="' . _("Save") . '" />', 'center', '', 'colspan="2"' )
    ) . "\n" .
        '</table>'.
        '</div>'.
        '</form>';
} else {
    // action is not set or action is not spam
    echo html_tag( 'p', '', 'center' ) .
         '[<a href="spamoptions.php?action=spam">' . _("Edit") . '</a>]' .
         ' - [<a href="../../src/options.php">' . _("Done") . '</a>]</div><br /><br />';
    printf( _("Spam is sent to %s."), ($filters_spam_folder?'<b>'.sm_encode_html_special_chars(imap_utf7_decode_local($filters_spam_folder)).'</b>':'[<i>'._("not set yet").'</i>]' ) );
    echo '<br />';
    printf( _("Spam scan is limited to %s."), '<b>' . ( ($filters_spam_scan == 'new')?_("Unread messages only"):_("All messages") ) . '</b>' );
    echo '</p>'.
        '<table border="0" cellpadding="3" cellspacing="0" align="center" bgcolor="' . $color[0] . "\">\n";

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
</body></html>
