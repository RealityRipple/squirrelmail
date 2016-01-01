<?php

/**
 * general_util.php
 *
 * This file is intended to contain helper functions for template sets
 * that would like to use them.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
  * Create stylesheet links that will work for multiple browsers
  *
  * @param string  $uri       The URI to the linked stylesheet.
  * @param string  $name      The title of the stylesheet (optional; default empty).
  * @param boolean $alt       Whether or not this is an alternate 
  *                           stylesheet (optional; default TRUE).
  * @param string  $mtype     The target media display type (optional; default "screen").
  *
  * @return string The full text of the stylesheet link.
  *
  */
function create_css_link($uri, $name='', $alt=TRUE, $mtype='screen') {
// FIXME: Add closing / to link and meta elements only after 
//        switching to xhtml 1.0 Transitional.
//        It is not compatible with html 4.01 Transitional
    if (empty($uri)) {
        return '';
    }

    // set to lower case to avoid errors
    //
    sqGetGlobalVar('HTTP_USER_AGENT', $browser_user_agent, SQ_SERVER);
    $browser_user_agent = strtolower($browser_user_agent);

    if (stristr($browser_user_agent, "msie 4")) {
        $browser = 'msie4';
        $dom_browser = false;
        $is_IE = true;
    } elseif (stristr($browser_user_agent, "msie") 
           && stristr($browser_user_agent, 'opera') === FALSE) {
        $browser = 'msie';
        $dom_browser = true;
        $is_IE = true;
    }

    if ((strpos($uri, '-ie')!== false) and !$is_IE) {
        //not IE, so don't render this sheet
        return;
    }

    if ( strpos($uri, 'print') !== false )
        $mtype = 'print';

    $href  = 'href="'.$uri.'" ';
    $media = 'media="'.$mtype.'" ';

    if ( empty($name) ) {
        $title = '';
        $rel   = 'rel="stylesheet" ';
    } else {
        $title = 'title="'.$name.'" ';
        $rel   = 'rel="'.( $alt ? 'alternate ' : '' ).'stylesheet" ';
    }

    return '<link '.$media.$title.$rel.'type="text/css" '.$href." />\n";
}


/**
 * Checks for an image icon and returns a complete image tag or a text
 * string with the text icon based on what is found and user prefs.
 *
 * @param string $icon_theme_path User's chosen icon set
 * @param string $icon_name File name of the desired icon
 * @param string $text_icon Text-based icon to display if desired
 * @param string $alt_text Text for alt/title attribute of image
 * @param integer $w Optional.  Width of requested image.
 * @param integer $h Optional.  Height of requested image.
 *
 * @return string $icon String containing icon that can be echo'ed
 *
 * @author Steve Brown
 * @since 1.5.2
 */
function getIcon($icon_theme_path, $icon_name, $text_icon, $alt_text, $w=NULL, $h=NULL) {
    $icon = '';
    if (is_null($icon_theme_path)) {
        $icon = $text_icon;
    } else {
        $icon_path = getIconPath($icon_theme_path, $icon_name);

        // If we found an icon, build an img tag to display it.  If we didn't
        // find an image, we will revert back to the text icon.
        if (!is_null($icon_path)) {
            $icon = create_image($icon_path, $alt_text, $w, $h, '', '', '', 
                                 '', $alt_text, '', '', '', $text_icon);
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
 *
 * @return string $icon String containing path to icon that can be used in
 *                      an IMG tag, or NULL if the image is not found.
 *
 * @author Steve Brown
 * @since 1.5.2
 *
 */
function getIconPath ($icon_theme_path, $icon_name) {
    global $fallback_icon_theme_path;

    if (is_null($icon_theme_path))
        return NULL;

    // Desired icon exists in the current theme?
//FIXME: this assumes a URI path will be valid when used as a filesystem path - this will fail for some systems -- OTOH, if $icon_theme_path is meant as an internal filesystem path to the user's chosen theme directory, then the assumption that is wrong here is that this internal filesystem path is always correct for use as part of a URI.  This really should be mapped to/from an internal path to a URI path -- or can we guarantee that the two are always the same?
    if (is_file($icon_theme_path . $icon_name)) {
        return $icon_theme_path . $icon_name;

    // Icon not found, check for the admin-specified fallback
//FIXME: same problem here as above
    } elseif (!is_null($fallback_icon_theme_path) && is_file($fallback_icon_theme_path . $icon_name)) {
        return $fallback_icon_theme_path . $icon_name;

    // Icon not found, return the SQM default icon
//FIXME: same problem here -- SM_PATH is *NOT* intended for use in URIs
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
//FIXME: I think this is needless since there is no reason templates cannot just call directly to show_readable_size
 *
 * @param int size to be converted to human-readable
 * @return string human-readable form
 * @since 1.5.2
 **/
function humanReadableSize ($size) {
    return show_readable_size($size);
}
