<?php

/**
 * mailout.php
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/page_header.php');
include_once(SM_PATH . 'include/load_prefs.php');
include_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/identity.php');
require_once(SM_PATH . 'functions/forms.php');
include_once(SM_PATH . 'plugins/listcommands/functions.php');

/* get globals */
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('send_to', $send_to, SQ_GET);
sqgetGlobalVar('subject', $subject, SQ_GET);
sqgetGlobalVar('body',    $body,    SQ_GET);
sqgetGlobalVar('action',  $action,  SQ_GET);

displayPageHeader($color, $mailbox);
$fieldsdescr = listcommands_fieldsdescr();

switch ( $action ) {
    case 'help':
        $out_string = _("This will send a message to %s requesting help for this list. You will receive an emailed response at the address below.");
        break;
    case 'subscribe':
        $out_string = _("This will send a message to %s requesting that you will be subscribed to this list. You will be subscribed with the address below.");
        break;
    case 'unsubscribe':
        $out_string = _("This will send a message to %s requesting that you will be unsubscribed from this list. It will try to unsubscribe the adress below.");
        break;
    default:
        error_box(sprintf(_("Unknown action: %s"),htmlspecialchars($action)), $color);
        exit;
}

echo html_tag('p', '', 'left' ) .
    html_tag( 'table', '', 'center', $color[0], 'border="0" width="75%"' ) . "\n" .
    html_tag( 'tr',
            html_tag( 'th', _("Mailinglist") . ': ' . $fieldsdescr[$action], '', $color[9] )
            ) .
    html_tag( 'tr' ) .
    html_tag( 'td', '', 'left' );

printf($out_string, '&quot;' . htmlspecialchars($send_to) . '&quot;');

echo addForm(SM_PATH . 'src/compose.php', 'post');

$idents = get_identities();

echo html_tag('p', '', 'center' ) . _("From:") . ' ';

if (count($idents) > 1) {
    echo '<select name="identity">';
    foreach($idents as $nr=>$data) {
        echo '<option value="' . $nr . '">' .
            htmlspecialchars(
                    $data['full_name'].' <'.
                    $data['email_address'] . ">\n");
    }
    echo '</select>' . "\n" ;
} else {
    echo htmlspecialchars('"'.$idents[0]['full_name'].'" <'.$idents[0]['email_address'].'>');
}

echo '<br />' .
    addHidden('send_to', $send_to) .
    addHidden('subject', $subject) .
    addHidden('body', $body) .
    addHidden('mailbox', $mailbox) .
    addSubmit(_("Send Mail"), 'send');
?>
<br /><br /></center>
</form></td></tr></table></p></body></html>