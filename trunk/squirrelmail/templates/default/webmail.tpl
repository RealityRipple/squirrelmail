<?php
/**
 * webmail.tpl
 *
 * Template for rendering the main squirrelmail window
 * 
 * The following variables are available in this template:
 *      $nav_size - integer width of the navigation frame
 *      $nav_on_left - boolean TRUE if the mavigation from should be on the
 *                      left side of the page.  FALSE if it is on the right.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */


/** extract variables */
extract($t);

$output = '';
if ($nav_on_left) {
    $output .= "<frameset cols=\"$nav_size, *\" id=\"fs1\">\n";
}
else {
    $output .= "<frameset cols=\"*, $nav_size\" id=\"fs1\">\n";
}

$left_frame  = '<frame src="left_main.php" name="left" frameborder="1" title="'. _("Folder List") .'" />'."\n";
$right_frame = '<frame src="'.$right_frame_url.'" name="right" frameborder="1" title="'. _("Message List") .'" />'."\n";

if ($nav_on_left) {
    $output .= $left_frame . $right_frame;
} else {
    $output .= $right_frame . $left_frame;
}

echo $output ."\n</frameset>\n</html>";
