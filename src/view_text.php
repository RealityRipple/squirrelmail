<?php

/**
 * view_text.php -- View a text attachment
 *
 * Used by attachment_common code.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** SquirrelMail required files. */
include('../include/init.php');
include(SM_PATH . 'functions/imap_general.php');
include(SM_PATH . 'functions/imap_messages.php');
include(SM_PATH . 'functions/mime.php');
include(SM_PATH . 'functions/date.php');
include(SM_PATH . 'functions/url_parser.php');

sqgetGlobalVar('messages',   $messages,     SQ_SESSION);
sqgetGlobalVar('mailbox',    $mailbox,      SQ_GET);
sqgetGlobalVar('ent_id',     $ent_id,       SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);
if (sqgetGlobalVar('passed_id', $temp, SQ_GET)) {
    $passed_id = (int) $temp;
}

$imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);
$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);

$message = &$messages[$mbx_response['UIDVALIDITY']][$passed_id];
if (!is_object($message)) {
    $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
}
$message_ent = $message->getEntity($ent_id);
if ($passed_ent_id) {
    $message = &$message->getEntity($passed_ent_id);
}
$header   = $message_ent->header;
$type0    = $header->type0;
$type1    = $header->type1;
$charset  = $header->getParameter('charset');
$encoding = strtolower($header->encoding);

$msg_url   = 'read_body.php?' . $QUERY_STRING;
$msg_url   = set_url_var($msg_url, 'ent_id', 0);
$dwnld_url = '../src/download.php?' . $QUERY_STRING . '&amp;absolute_dl=true';
$unsafe_url = 'view_text.php?' . $QUERY_STRING;
$unsafe_url = set_url_var($unsafe_url, 'view_unsafe_images', 1);


$body = mime_fetch_body($imapConnection, $passed_id, $ent_id);
$body = decodeBody($body, $encoding);
$hookResults = do_hook('message_body', $body);
$body = $hookResults[1];

if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
    function_exists($languages[$squirrelmail_language]['XTRA_CODE'].'_decode')) {
    if (mb_detect_encoding($body) != 'ASCII') {
        $body = call_user_func($languages[$squirrelmail_language]['XTRA_CODE'] . '_decode', $body);
    }
}

if ($type1 == 'html' || (isset($override_type1) &&  $override_type1 == 'html')) {
    $ishtml = TRUE;
    $body = MagicHTML( $body, $passed_id, $message, $mailbox);
    // html attachment with character set information
    if (! empty($charset)) {
        $body = charset_decode($charset,$body,false,true);
    }
} else {
    translateText($body, $wrap_at, $charset);
}

displayPageHeader($color, 'None');
?>
<br /><table width="100%" border="0" cellspacing="0" cellpadding="2" align="center"><tr><td bgcolor="<?php echo $color[0]; ?>">
<b><div style="text-align: center;">
<?php
echo _("Viewing a text attachment") . ' - ' .
    '<a href="'.$msg_url.'">' . _("View message") . '</a>';
?>
</b></td><tr><tr><td><div style="text-align: center;">
<?php
if ( $ishtml ) {
    echo '<a href="' . $unsafe_url . '">' . _("View Unsafe Images") . '</a> | ';
}
echo '<a href="' . $dwnld_url . '">' . _("Download this as a file") . '</a>';
?>
</div><br />
</div></b>
</td></tr></table>
<table width="98%" border="0" cellspacing="0" cellpadding="2" align="center"><tr><td bgcolor="<?php echo $color[0]; ?>">
<tr><td bgcolor="<?php echo $color[4]; ?>"><tt>
<?php echo $body; ?>
</tt></td></tr></table>
<?php
$oTemplate->display('footer.tpl');
?>
