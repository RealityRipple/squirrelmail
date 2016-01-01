<?php
/**
 * read_handle_receipt.tpl
 *
 * Template to generate output for handling read receipts.
 * 
 * The following variables are available in this template:
 * 
 *     $read_receipt_sent - boolean TRUE if the read receipt has already been sent
 *     $first_time_reading - boolean TRUE if this is the first time this message
 *                           has been seen
 *     $send_receipt_href  - URL to send a read receipt now.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
if ($read_receipt_sent) {
    echo  _("Sent");
} else {
    ?>
<?php echo _("Requested"); ?>&nbsp;
<small>[ <a href="<?php echo $send_receipt_href; ?>"><?php echo _("Send Read Receipt Now"); ?></a> ]</small>
    <?php
    if ($first_time_reading && $javascript_on) {
        ?>
<script type="text/javascript">
<!--
if (confirm("<?php echo _("The message sender has requested a response to indicate that you have read this message. Would you like to send a receipt?"); ?>")) {
    sendMDN();
}
// -->
</script>
        <?php
    }
}
?>