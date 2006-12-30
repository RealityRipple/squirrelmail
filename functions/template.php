<?php

/**
 * template.php
 *
 * This file is intended to contain helper functions for template sets
 * that would like to use them.
FIXME: potentially create a separate directory and separate functions into different files?
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
  * @param string  $xhtml_end The XHTML-compliant close tag syntax to 
  *                           use (optional; default "/")
  *
  * @return string The full text of the stylesheet link.
  *
  */
function create_css_link($uri, $name='', $alt=TRUE, $mtype='screen', $xhtml_end='/') {
// FIXME: Add closing / to link and meta elements only after 
//        switching to xhtml 1.0 Transitional.
//        It is not compatible with html 4.01 Transitional
$xhtml_end='';

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
//FIXME: this fails for Opera because its user agent also contains MSIE
    } elseif (stristr($browser_user_agent, "msie")) {
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

    return '<link '.$media.$title.$rel.'type="text/css" '.$href." $xhtml_end>\n";
}


