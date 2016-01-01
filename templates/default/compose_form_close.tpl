<?php
/**
 * compose_form_close.tpl
 *
 * This template is intended to push the closing <form> tag to the browser
 * along with any other elements to be added before the form is closed out.
 *
 * Plugins can add output before the form is closed by registering on the
 * "template_construct_compose_form_close.tpl" hook and return an array
 * with a single key/value pair where the key is "compose_bottom" and the
 * value is the desired output (typically some hidden form elements or some
 * JavaScript).
 * 
 * The following variables are available in this template:
 *    $plugin_output     array  An array of extra output that may be added by plugin(s).
 *    $username          string The current user's username
 *    $smaction          string The form action we have just finished processing
 *    $mailbox           string The mailbox currently being viewed or acted upon
 *    $querystring       string The current page view's query string arguments
 *    $composesession    string The current message compose session ID (internal to SM;
 *                              not the same as the PHP session ID)
 *    $send_button_count string The count of the number of send buttons on this screen
 *    $user_notices      array  A list of notices to be displayed to the user
 *                              (usually just a notice about PHP file uploads
 *                              being disabled causing the attachment form not
 *                              to be displayed)
 *    $attachments       string A serialized string containing information about
 *                              any attachments being sent with this message
 *                              (when no attachments have been added, this will
 *                              not be provided here)
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** extract template variables **/
extract($t);

/** Begin template **/
?>
<input type="hidden" name="username" value="<?php echo $username; ?>" />
<input type="hidden" name="smaction" value="<?php echo $smaction; ?>" />
<input type="hidden" name="mailbox" value="<?php echo $mailbox; ?>" />
<input type="hidden" name="querystring" value="<?php echo $querystring; ?>" />
<input type="hidden" name="composesession" value="<?php echo $composesession; ?>" />
<input type="hidden" name="send_button_count" value="<?php echo $send_button_count; ?>" />

<?php if (!empty($attachments)) { ?>
<input type="hidden" name="attachments" value="<?php echo $attachments; ?>" />
<?php } ?>

<?php if (!empty($plugin_output['compose_bottom'])) echo $plugin_output['compose_bottom']; ?>

</form>

<?php if (!empty($user_notices)) foreach ($user_notices as $notice) {
  echo '<p style="text-align:center">' . $notice . "</p>\n";
} ?>

