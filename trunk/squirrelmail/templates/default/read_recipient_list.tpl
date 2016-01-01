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
$count = 0;
echo "<span class=\"recpt_head\">";

foreach ($recipients as $r) {
    $count++;

    echo $r['Full'];
    if ($count != count($recipients)) {
        echo ", \n   ";
    }
    if (!$show_more && $count == 3) {
        echo "</span><span id=\"recpt_tail_" . $which_field . "\">";
    }
}
echo "</span>\n";


if (count($recipients) > 3) {
    if ( checkForJavascript() ) {
        $url = "javascript:void(0)";
        $onclick = ' onclick="showhide(\'' . $which_field . "','" . _("more") . "','" . _("less") . "')\"";
    } else {
        $url = $more_less_toggle_href;
        $onlclick = '';
    }

    echo "&nbsp;(<a href=\"" . $url . "\"" . $onclick . " id=\"toggle_" . $which_field . "\">" .
        ($show_more ? _("less") : _("more") ) .
        "</a>)";
}

