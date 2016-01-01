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

/** set up for adding preview pane if turned on */
global $data_dir, $username;
$use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
$show_preview_pane = checkForJavascript() && $use_previewPane;
if ($show_preview_pane) {
    $previewPane_size = getPref($data_dir, $username, 'previewPane_size', 300);
    $previewPane_vertical_split = getPref($data_dir, $username, 'previewPane_vertical_split', 0);
    if ($previewPane_vertical_split)
        $split = 'cols';
    else
        $split = 'rows';
}
 

$output = '';
if ($nav_on_left) {
    $output .= "<frameset cols=\"$nav_size, *\" id=\"fs1\">\n";
}
else {
    $output .= "<frameset cols=\"*, $nav_size\" id=\"fs1\">\n";
}


$left_frame  = '<frame src="left_main.php" name="left" frameborder="1" title="'. _("Folder List") .'" />'."\n";


/** use preview pane? */
if ($show_preview_pane) {
    $right_frame = "<frameset $split=\"*, $previewPane_size\" id=\"fs2\">\n"
                 . "<frame src=\"$right_frame_url\" name=\"right\" title=\"" . _("Message List") . "\" frameborder=\"1\" />\n"
                 . "<frame src=\"" . SM_PATH . "plugins/preview_pane/empty_frame.php\" name=\"bottom\" title=\"" . _("Message Preview") . "\" frameborder=\"1\" />\n"
                 . "</frameset>\n";

/** no preview pane */
} else {
    $right_frame = '<frame src="'.$right_frame_url.'" name="right" frameborder="1" title="'. _("Message List") .'" />'."\n";
}


if ($nav_on_left) {
    $output .= $left_frame . $right_frame;
} else {
    $output .= $right_frame . $left_frame;
}

echo $output ."\n</frameset>\n</html>";
