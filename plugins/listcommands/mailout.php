<?php
/*
 * mailout.php part of listcommands plugin
 * last modified: 2002/01/12 by Thijs Kinkhorst
 *
 */
 
chdir('..');
include_once ('../src/validate.php');
include_once ('../functions/page_header.php');
include_once ('../src/load_prefs.php');

displayPageHeader($color, $mailbox);

?>
<P>
<TABLE align="center" width="75%" BGCOLOR="<?php echo $color[0]; ?>">
<TR><TH BGCOLOR="<?php echo $color[9] . '">' . _("Mailinglist") . ' ' . _($action); ?></TH></TR>
<TR><TD>
<?php

switch ( $action ) {
case 'Help':
    $out_string .= _("This will send a message to %s requesting help for this list. You will receive an emailed response at the address below.");
    break;
case 'Subscribe':
    $out_string .= _("This will send a message to %s requesting that you will be subscribed to this list. You will be subscribed with the address below.");
    break;
case 'Unsubscribe':
    $out_string = _("This will send a message to %s requesting that you will be subscribed to this list. It will try to unsubscribe the adress below.");
}

printf( $out_string, htmlspecialchars($send_to) );

echo '<form method="POST" action="../../src/compose.php">';

/*
 * Identity support (RFC 2369 sect. B.1.)
 *
 * I had to copy this from compose.php because there doesn't
 * seem to exist a function to get the identities.
 */

$defaultmail = htmlspecialchars(getPref($data_dir, $username, 'full_name'));
$em = getPref($data_dir, $username, 'email_address');
if ($em != '') {
    $defaultmail .= htmlspecialchars(' <' . $em . '>') . "\n";
}
echo '<P><CENTER>' . _("From:");

$idents = getPref($data_dir, $username, 'identities'); 
if ($idents != '' && $idents > 1)
{
    echo ' <select name=identity>' . "\n" .
         '<option value=default>' . $defaultmail;
    for ($i = 1; $i < $idents; $i ++) {
        echo '<option value="' . $i . '"';
        if (isset($identity) && $identity == $i) {
            echo ' SELECTED';
        }
        echo '>' . htmlspecialchars(getPref($data_dir, $username,
                                                'full_name' . $i));
        $em = getPref($data_dir, $username, 'email_address' . $i);
        if ($em != '') {
            echo htmlspecialchars(' <' . $em . '>') . "\n";
        }
    } 
    echo '</select>' . "\n" ;

}
else
{
    echo $defaultmail;
} 
?>
<BR>
<input type=hidden name="send_to" value="<?php echo htmlspecialchars($send_to); ?>">
<input type=hidden name="subject" value="<?php echo htmlspecialchars($subject); ?>">
<input type=hidden name="body" value="<?php echo htmlspecialchars($body); ?>">
<input type=hidden name="mailbox" value="<?php echo htmlspecialchars($mailbox); ?>">
<input type=submit name="send" value="<?php echo _("Send Mail"); ?>"><BR><BR></CENTER>
</form>
</TD></TR></TABLE>
</P>
</BODY>
</HTML>