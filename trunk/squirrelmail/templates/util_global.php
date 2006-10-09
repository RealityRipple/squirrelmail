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
        $icon_path = getIconPath($icon_theme_path, $icon_name);

        // If we found an icon, build an img tag to display it.  If we didn't
        // find an image, we will revert back to the text icon.
        if (!is_null($icon_path)) {
            $icon = '<img src="'.$icon_path.'" ' .
                    'alt="'.$alt_text.'" '.
                    (!empty($alt_text) ? 'title="'.$alt_text.'" ' : '') .
                    (!is_null($w) ? 'width="'.$w.'" ' : '') .
                    (!is_null($h) ? 'height="'.$h.'" ' : '') .
                    ' />';
        } else {
            $icon = $text_icon;
        }
    }
    return $icon;    
}

/**
 * Gets the path to the specified icon or returns NULL if the image is not
 * found.  This has been separated from getIcon to allow the path to be fetched
 * for use w/ third party packages, e.g. dTree.
 * 
 * @param string $icon_theme_path User's chosen icon set
 * @param string $icon_name File name of the desired icon
 * @return string $icon String containing path to icon that can be used in
 *                      an IMG tag, or NULL if the image is not found.
 * @author Steve Brown
 * @since 1.5.2
 */
function getIconPath ($icon_theme_path, $icon_name) {
    global $fallback_icon_theme_path;

    if (is_null($icon_theme_path))
        return NULL;
    
    // Desired icon exists in the current theme?
    if (is_file($icon_theme_path . $icon_name)) {
        return $icon_theme_path . $icon_name;
        
    // Icon not found, check for the admin-specified fallback
    } elseif (!is_null($fallback_icon_theme_path) && is_file($fallback_icon_theme_path . $icon_name)) {
        return $fallback_icon_theme_path . $icon_name;
        
    // Icon not found, return the SQM default icon
    } elseif (is_file(SM_PATH . 'images/themes/default/'.$icon_name)) {
        return SM_PATH . 'images/themes/default/'.$icon_name;
    } 
    
    return NULL;
}

/**
 * Display error messages for use in footer.tpl
 * 
 * @author Steve Brown
 * @since 1.5.2
 **/
function displayErrors () {
    global $oErrorHandler;
    
    if ($oErrorHandler) {
        $oErrorHandler->displayErrors();
    }
}

/**
 * Make the internal show_readable_size() function available to templates.
 * 
 * @param int size to be converted to human-readable
 * @return string human-readable form
 * @since 1.5.2
 **/
function humanReadableSize ($size) {
    return show_readable_size($size);
}

