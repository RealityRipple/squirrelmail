<?php

/**
  * mailout.tpl
  *
  * Template for listcommands (un)subscribe/help mail sending interface
  *
  * The following variables are available in this template:
  *      + $ - The lists that the user currently has 
  *                 configured (an array of list addresses, 
  *                 keyed by an ID number)
  *
  * @copyright 1999-2009 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage listcommands
  */


// retrieve the template vars
//
extract($t);


?>

<div class="dialogbox">
<table class="wrapper">
<tr><td class="header1"><?php echo _("Mailinglist") . ': ' . $fielddescr; ?></td></tr>

<tr><td>
<?php echo $out_string; ?>

<br /><br />

<form action="../../src/compose.php" method="post">

<?php if (count($idents) > 1) {
    echo '<label for="identity">' . _("From:") .'</label> ';
    echo '<select name="identity" id="identity">';
    
    foreach($idents as $nr=>$data) {
        echo '<option value="' . $nr . '">' .
            htmlspecialchars(
                    $data['full_name'].' <'.
                    $data['email_address'] . '>') .
            "</option>\n";		    
    }

    echo "</select>\n";

} else {

    echo _("From:");
    echo htmlspecialchars($idents[0]['full_name'].' <'.$idents[0]['email_address'].'>');
}
?>
<br /><br />
<input type="hidden" name="send_to" value="<?php echo htmlspecialchars($send_to); ?>" />
<input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>" />
<input type="hidden" name="body" value="<?php echo htmlspecialchars($body); ?>" />
<input type="hidden" name="mailbox" value="<?php echo htmlspecialchars($mailbox); ?>" />
<input type="submit" name="send1" value="<?php echo _("Send Mail"); ?>" />
<br />
</form>

</td></tr></table>
</div>

