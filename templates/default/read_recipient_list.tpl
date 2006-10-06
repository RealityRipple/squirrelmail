<?php
/**
 * read_recipient_list.tpl
 *
 * Template to generate the listing of recipeients for the To, CC and BCC fields.
 * 
 * The following variables are available in this template:
 *      $which_field - The field that is currently being displayed.  Will be 'to',
 *                     'cc' or 'bcc'
 *      $more_less_toggle_href - URL to toggle more/less addresses for this field
 *      $show_more   - boolean TRUE if we want to show all addresses for this field. 
 *      $recipients  - array containing all receipients for this field.  Each element
 *                     is an array representing a recipient and contains the following
 *                     elements:
 *         $r['Name']  - The name attached to the receipient.  Will contain the email
 *                       if no name is provided.
 *         $r['Email'] - Email address of the recipient
 *         $a['Full']  - Full name + email
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes **/

/** extract template variables **/
extract($t);

/** Begin template **/
$count = 0;
foreach ($recipients as $r) {
    $count++;
    if ($count > 1 && !$show_more)
        continue;
    echo $r['Full'];
    if ($show_more && $count != count($recipients)) {
        echo '<br />';
    }
}

if (count($recipients) > 1) {
    if ($show_more) {
        ?>
&nbsp;<small>(<a href="<?php echo $more_less_toggle_href; ?>"><?php echo _("less"); ?></a>)</small>
        <?php
    } else {
        ?>
&nbsp;<small>(<a href="<?php echo $more_less_toggle_href; ?>"><?php echo _("more"); ?></a>)</small>
        <?php
    }
}