<?php
/**
 * util_global.php
 *
 * Utility functions for use with all templates.  Do not echo output here!
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */
 
/**
 * Checks for an image icon and returns a complete HTML img tag or a text
 * string with the text icon based on what is found and user prefs.
 * 
 * @param string $icon_theme_path User's chosen icon set
 * @param string $icon_name File name of the desired icon
 * @param string $text_icon Text-based icon to display if desired
 * @param string $alt_text Optional.  Text for alt/title attribute of image
 * @param integer $w Optional.  Width of requested image.
 * @param integer $h Optional.  Height of requested image.
 * @return string $icon String containing icon that can be echo'ed
 * @author Steve Brown
 * @since 1.5.2
 */
function getIcon($icon_theme_path, $icon_name, $text_icon, $alt_text='', $w=NULL, $h=NULL) {
    $icon = '';
    if (is_null($icon_theme_path)) {
        $icon = $text_icon;
    } else {
        // Desired icon exists in the current theme?
        if (is_file($icon_theme_path . $icon_name)) {
            $icon_path = $icon_theme_path . $icon_name;

        // Icon not found, return the SQM default icon
        } elseif (is_file(SM_PATH . 'images/themes/default/'.$icon_name)) {
            $icon_path = SM_PATH . 'images/themes/default/'.$icon_name;
        } 
        
        // If we found an icon, build an img tag to display it.  If we didn't
        // find an image, we will revert back to the text icon.
        if (isset($icon_path) && !is_null($icon_path)) {
            $icon = '<img src="'.$icon_path.'" ' .
                    (!empty($alt_text) ? 'alt="'.$alt_text.'" title="'.$alt_text.'" ' : '') .
                    (!is_null($w) ? 'width="'.$w.'" ' : '') .
                    (!is_null($h) ? 'height="'.$h.'" ' : '') .
                    ' />';
        } else {
            $icon = $text_icon;
        }
    }
    return $icon;    
}
?>