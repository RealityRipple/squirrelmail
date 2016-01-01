<?php

/**
 * html.php
 *
 * The idea is to inlcude here some functions to make easier
 * the right to left implementation by "functionize" some
 * html outputs.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @since 1.3.0
 */


/**
 * Generates a hyperlink
 *
 * @param string $uri      The target link location
 * @param string $text     The link text 
 * @param string $target   The location where the link should 
 *                         be opened (OPTIONAL; default not used)
 * @param string $onclick  The onClick JavaScript handler (OPTIONAL; 
 *                         default not used)
 * @param string $class    The CSS class name (OPTIONAL; default
 *                         not used)
 * @param string $id       The ID name (OPTIONAL; default not used)
 * @param string $name     The anchor name (OPTIONAL; default not used)
 * @param array  $aAttribs Any extra attributes: this must be an 
 *                         associative array, where keys will be 
 *                         added as the attribute name, and values
 *                         (which are optional - should be null if
 *                         none should be used) will be placed in
 *                         double quotes (pending template implementation)
 *                         as the attribute value (OPTIONAL; default empty).
 *
 * @return string The desired hyperlink tag.
 *
 * @since 1.5.2
 *
 */
function create_hyperlink($uri, $text, $target='', $onclick='', 
                          $class='', $id='', $name='', $aAttribs=array()) {

    global $oTemplate;

    $oTemplate->assign('uri', $uri);
    $oTemplate->assign('text', $text);
    $oTemplate->assign('target', $target);
    $oTemplate->assign('onclick', $onclick);
    $oTemplate->assign('class', $class);
    $oTemplate->assign('id', $id);
    $oTemplate->assign('name', $name);

    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('hyperlink.tpl');

}


/**
 * Generates an image tag
 *
 * @param string $src     The image source path
 * @param string $alt     Alternate link text (OPTIONAL; default 
 *                        not used)
 * @param string $width   The width the image should be shown in 
 *                        (OPTIONAL; default not used)
 * @param string $height  The height the image should be shown in 
 *                        (OPTIONAL; default not used)
 * @param string $border  The image's border attribute value 
 *                        (OPTIONAL; default not used)
 * @param string $class   The CSS class name (OPTIONAL; default
 *                        not used)
 * @param string $id      The ID name (OPTIONAL; default not used)
 * @param string $onclick The onClick JavaScript handler (OPTIONAL;
 *                        default not used)
 * @param string $title   The image's title attribute value 
 *                        (OPTIONAL; default not used)
 * @param string $align   The image's alignment attribute value 
 *                        (OPTIONAL; default not used)
 * @param string $hspace  The image's hspace attribute value 
 *                        (OPTIONAL; default not used)
 * @param string $vspace  The image's vspace attribute value 
 *                        (OPTIONAL; default not used)
 * @param string $text_alternative A text replacement for the entire
 *                                 image tag, to be used at the 
 *                                 discretion of the template set,
 *                                 if for some reason the image tag
 *                                 cannot or should not be produced
 *                                 (OPTIONAL; default not used)
 * @param array  $aAttribs Any extra attributes: this must be an 
 *                         associative array, where keys will be 
 *                         added as the attribute name, and values
 *                         (which are optional - should be null if
 *                         none should be used) will be placed in
 *                         double quotes (pending template implementation)
 *                         as the attribute value (OPTIONAL; default empty).
 *
 * @return string The desired hyperlink tag.
 *
 * @since 1.5.2
 *
 */
function create_image($src, $alt='', $width='', $height='', 
                      $border='', $class='', $id='', $onclick='', 
                      $title='', $align='', $hspace='', $vspace='',
                      $text_alternative='', $aAttribs=array()) {

    global $oTemplate;

    $oTemplate->assign('src', $src);
    $oTemplate->assign('alt', $alt);
    $oTemplate->assign('width', $width);
    $oTemplate->assign('height', $height);
    $oTemplate->assign('border', $border);
    $oTemplate->assign('class', $class);
    $oTemplate->assign('id', $id);
    $oTemplate->assign('onclick', $onclick);
    $oTemplate->assign('title', $title);
    $oTemplate->assign('align', $align);
    $oTemplate->assign('hspace', $hspace);
    $oTemplate->assign('vspace', $vspace);
    $oTemplate->assign('text_alternative', $text_alternative);

    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('image.tpl');

}


/**
 * Generates a label tag
 *
 * @param string $value    The contents that belong inside the label
 * @param string $for      The ID to which the label applies (OPTIONAL; 
 *                         default not used)
 * @param array  $aAttribs Any extra attributes: this must be an
 *                         associative array, where keys will be
 *                         added as the attribute name, and values
 *                         (which are optional - should be null if
 *                         none should be used) will be placed in
 *                         double quotes (pending template implementation)
 *                         as the attribute value (OPTIONAL; default empty).
 *
 * @return string The desired label tag.
 *
 * @since 1.5.2
 *
 */
function create_label($value, $for='', $aAttribs=array()) {

    global $oTemplate;

    $oTemplate->assign('text', $value);
    $oTemplate->assign('for', $for);

    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('label.tpl');

}


/**
 * Generates a span tag
 *
 * @param string $value   The contents that belong inside the span
 * @param string $class   The CSS class name (OPTIONAL; default
 *                        not used)
 * @param string $id      The ID name (OPTIONAL; default not used)
 * @param array  $aAttribs Any extra attributes: this must be an 
 *                         associative array, where keys will be 
 *                         added as the attribute name, and values
 *                         (which are optional - should be null if
 *                         none should be used) will be placed in
 *                         double quotes (pending template implementation)
 *                         as the attribute value (OPTIONAL; default empty).
 *
 * @return string The desired span tag.
 *
 * @since 1.5.2
 *
 */
function create_span($value, $class='', $id='', $aAttribs=array()) {

    global $oTemplate;

    $oTemplate->assign('value', $value);
    $oTemplate->assign('class', $class);
    $oTemplate->assign('id', $id);

    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('span.tpl');

}


/**
 * Generates an opening body tag
 *
 * @param string $onload  Body onload JavaScript handler code  
 *                        (OPTIONAL; default not used)
 * @param string $class   The CSS class name (OPTIONAL; default
 *                        not used)
 * @param array  $aAttribs Any extra attributes: this must be an 
 *                         associative array, where keys will be 
 *                         added as the attribute name, and values
 *                         (which are optional - should be null if
 *                         none should be used) will be placed in
 *                         double quotes (pending template implementation)
 *                         as the attribute value (OPTIONAL; default empty).
 *
 * @return string The desired body tag.
 *
 * @since 1.5.2
 *
 */
function create_body($onload='', $class='', $aAttribs=array()) {

    global $oTemplate;

    $oTemplate->assign('onload', $onload);
    $oTemplate->assign('class', $class);

    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('body.tpl');

}


/**
 * Generates html tags
//FIXME: This should not be used anywhere in the core, or we should convert this to use templates.  We should not be assuming HTML output.
 *
 * @param string $tag Tag to output
 * @param string $val Value between tags
 * @param string $align Alignment (left, center, etc)
 * @param string $bgcolor Back color in hexadecimal
 * @param string $xtra Extra options
 * @return string HTML ready for output
 * @since 1.3.0
 */
function html_tag( $tag,                // Tag to output
                       $val = '',           // Value between tags
                       $align = '',         // Alignment
                       $bgcolor = '',       // Back color
                       $xtra = '' ) {       // Extra options

    GLOBAL $languages, $squirrelmail_language;

    $align = strtolower( $align );
    $bgc = '';
    $tag = strtolower( $tag );

    if ( isset( $languages[$squirrelmail_language]['DIR']) ) {
        $dir = $languages[$squirrelmail_language]['DIR'];
    } else {
        $dir = 'ltr';
    }

    if ( $dir == 'ltr' ) {
        $rgt = 'right';
        $lft = 'left';
    } else {
        $rgt = 'left';
        $lft = 'right';
    }

    if ( $bgcolor <> '' ) {
        $bgc = " bgcolor=\"$bgcolor\"";
    }

    switch ( $align ) {
    case '':
        $alg = '';
        break;
    case 'right':
        $alg = " align=\"$rgt\"";
        break;
    case 'left':
        $alg = " align=\"$lft\"";
        break;
    default:
        $alg = " align=\"$align\"";
        break;
    }

    $ret = "<$tag";

    if ( $dir <> 'ltr' ) {
        $ret .= " dir=\"$dir\"";
    }
    $ret .= $bgc . $alg;

    if ( $xtra <> '' ) {
        $ret .= " $xtra";
    }

    if ( $val <> '' ) {
        $ret .= ">$val</$tag>\n";
    } else {
        $ret .= '>'. "\n";
    }

    return( $ret );
}


/**
 * This function is used to add, modify or delete more than
 * one GET variable at a time in a URL.  This simply takes
 * an array of variables (key/value pairs) and passes them
 * one at a time to {@link set_url_var}.
 * 
 * Note that the value for any one of the variables may be
 * an array, and it will be handled properly.
 *
 * As with set_url_var, any of the variable values may be
 * set to NULL to remove it from the URI.
 *
 * Also, to ensure compatibility with older versions, use
 * $val='0' to set $var to 0. 
 *
 * @param string  $uri      URI that must be modified
 * @param array   $values   List of GET variable names and their values
 * @param boolean $sanitize Controls sanitizing of ampersand in URIs
 *
 * @return string The modified URI
 *
 * @since 1.5.2
 *
 */
function set_uri_vars($uri, $values, $sanitize=TRUE) {
    foreach ($values as $key => $value)
        if (is_array($value)) {
          $i = 0;
          foreach ($value as $val)
             $uri = set_url_var($uri, $key . '[' . $i++ . ']', $val, $sanitize);
        }
        else
          $uri = set_url_var($uri, $key, $value, $sanitize);
    return $uri;
}


/**
 * This function is used to add, modify or delete GET variables in a URL. 
 * It is especially useful when $url = $PHP_SELF
 *
 * Set $val to NULL to remove $var from $url.
 * To ensure compatibility with older versions, use $val='0' to set $var to 0. 
 *
 * @param string $url url that must be modified
 * @param string $var GET variable name
 * @param string $val variable value (CANNOT be an array)
 * @param boolean $link controls sanitizing of ampersand in urls (since 1.3.2)
 * @param boolean $treat_as_array When TRUE, if $var is an array (it occurs one
 *                                or more times with square brackets after it,
 *                                e.g. "var[1]"), the whole array will be removed
 *                                (when $val is NULL) or the given value will be
 *                                added to the next array slot (@since 1.4.23/1.5.2)
 *
 * @return string $url modified url
 *
 * @since 1.3.0
 *
 */
function set_url_var($url, $var, $val=null, $link=true, $treat_as_array=false) {
    $url = str_replace('&amp;','&',$url);

    if (strpos($url, '?') === false) {
        $url .= '?';
    }   

    list($uri, $params) = explode('?', $url, 2);
        
    $newpar = array(); 
    $params = explode('&', $params);
    $array_names = array();
   
    foreach ($params as $p) {
        if (trim($p)) {
            $p = explode('=', $p);
            $newpar[$p[0]] = (isset($p[1]) ? $p[1] : '');
            if ($treat_as_array && preg_match('/(.*)\[(\d+)]$/', $p[0], $matches)) {
               if (!isset($array_names[$matches[1]])) $array_names[$matches[1]] = array();
               $array_names[$matches[1]][$matches[2]] = $p[1];
            }
        }
    }

    if (is_null($val)) {
        if ($treat_as_array && !empty($array_names[$var])) {
            foreach ($array_names[$var] as $key => $ignore)
                unset($newpar[$var . '[' . $key . ']']);
        } else {
            unset($newpar[$var]);
        }
    } else {
        if ($treat_as_array && !empty($array_names[$var])) {
            $max_key = 0;
            foreach ($array_names[$var] as $key => $ignore)
                if ($key >= $max_key) $max_key = $key + 1;
            $newpar[$var . '[' . $max_key . ']'] = $val;
        } else {
            $newpar[$var] = $val;
        }
    }

    if (!count($newpar)) {
        return $uri;
    }
   
    $url = $uri . '?';
    foreach ($newpar as $name => $value) {
        $url .= "$name=$value&";
    }
     
    $url = substr($url, 0, -1);
    if ($link) {
        $url = str_replace('&','&amp;',$url);
    }
    
    return $url;
}

