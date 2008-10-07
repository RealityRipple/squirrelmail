<?php
/**
 * options.tpl
 *
 * Template for rendering the options page
 * 
 * The following variables are available to this template:
 *      $options - array of options as built by SquirrelMail.  Important fields
 *                 in this array include (but are not limited to):
 *          $el['name']     - The name of the option group
 *          $el['options']  - array of squirrelOption objects
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */

/** extract variables */
extract($t);


if ( !empty($topmessage) ) {
	echo "<div id=\"optionMessage\">\n$topmessage\n</div>\n\n";
}
?>

<div id="optionDisplay">
<?php
foreach ($options as $option) {
    echo "<table cellspacing=\"0\">\n";

    if (!empty($option['name'])) {
        echo " <tr>\n" .
             "  <td class=\"header1\" colspan=\"2\">\n" .
             "   ".$option['name']."\n" .
             "  </td>\n" .
             " </tr>\n";
    }

    $hidden_widgets = '';
    foreach ($option['options'] as $opt) {
        if ($opt->type != SMOPT_TYPE_HIDDEN) {
            echo   "<tr>\n" .
                   " <td class=\"optionName\"" . ($opt->caption_wrap ? '' : ' style="white-space:nowrap"') . ">\n  ";

            if ($opt->type == SMOPT_TYPE_BOOLEAN_CHECKBOX
             || $opt->type == SMOPT_TYPE_BOOLEAN) {
                echo '<label for="new_' . $opt->name . '">'
                   . $opt->caption . '</label>';
            } else {
                echo $opt->caption;
            }

            echo   "\n </td>\n" .
                   " <td class=\"optionValue\">\n" .
                   "  ".$opt->createWidget()."\n" .
                   " </td>\n" .
                   "</tr>\n";
        } else {
            $hidden_widgets .= $opt->createWidget();
        }
    }

    echo " <tr>\n  <td colspan=\"2\" align=\"right\">\n"
       . $hidden_widgets . "   <input type=\"submit\" value=\"" . _("Submit") 
       . "\" name=\"" . $submit_name . "\" />&nbsp;&nbsp;&nbsp;&nbsp;\n  </td>\n </tr>\n";

    echo "</table>\n";
}
?>
</div>
