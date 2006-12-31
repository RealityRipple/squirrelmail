<?php

/**
 * html.php
 *
 * The idea is to inlcude here some functions to make easier
 * the right to left implementation by "functionize" some
 * html outputs.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @since 1.3.0
 */


/**
 * Generates a hyperlink
 *
 * @param string $uri     The target link location
 * @param string $text    The link text 
 * @param string $target  The location where the link should 
 *                        be opened (OPTIONAL; default not used)
 * @param string $onclick The onClick JavaScript handler (OPTIONAL; 
 *                        default not used)
 * @param string $class   The CSS class name (OPTIONAL; default
 *                        not used)
 * @param string $id      The ID name (OPTIONAL; default not used)
 *
 * @return string The desired hyperlink tag.
 *
 * @since 1.5.2
 *
 */
function create_hyperlink($uri, $text, $target='', $onclick='', $class='', $id='') {

    global $oTemplate;

    $oTemplate->assign('uri', $uri);
    $oTemplate->assign('text', $text);
    $oTemplate->assign('target', $target);
    $oTemplate->assign('onclick', $onclick);
    $oTemplate->assign('class', $class);
    $oTemplate->assign('id', $id);

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
 *
 * @return string The desired hyperlink tag.
 *
 * @since 1.5.2
 *
 */
function create_image($src, $alt='', $width='', $height='', 
                      $border='', $class='', $id='', $onclick='', 
                      $title='', $align='', $hspace='', $vspace='') {

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

    return $oTemplate->fetch('image.tpl');

}


/**
 * Generates html tags
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
 * handy function to set url vars
 *
 * especially useful when $url = $PHP_SELF
 * @param string $url url that must be modified
 * @param string $var variable name
 * @param string $val variable value
 * @param boolean $link controls sanitizing of ampersand in urls (since 1.3.2)
 * @return string $url modified url
 * @since 1.3.0
 */
function set_url_var($url, $var, $val=0, $link=true) {
    $k = '';
    $pat_a = array (
                    '/.+(\\&'.$var.')=(.*)\\&/AU',   /* in the middle */
                    '/.+\\?('.$var.')=(.*\\&).+/AU', /* at front, more follow */
                    '/.+(\\?'.$var.')=(.*)$/AU',     /* at front and only var */
                    '/.+(\\&'.$var.')=(.*)$/AU'      /* at the end */
                    );
    $url = str_replace('&amp;','&',$url);

    // FIXME: why switch is used instead of if () or one preg_match()
    switch (true) {
    case (preg_match($pat_a[0],$url,$regs)):
        $k = $regs[1];
        $v = $regs[2];
        break;
    case (preg_match($pat_a[1],$url,$regs)):
        $k = $regs[1];
        $v = $regs[2];
        break;
    case (preg_match($pat_a[2],$url,$regs)):
        $k = $regs[1];
        $v = $regs[2];
        break;
    case (preg_match($pat_a[3],$url,$regs)):
        $k = $regs[1];
        $v = $regs[2];
        break;
    default:
        if ($val) {
            if (strpos($url,'?')) {
                $url .= "&$var=$val";
            } else {
                $url .= "?$var=$val";
            }
        }
        break;
    }

    if ($k) {
        if ($val) {
            $rpl = "$k=$val";
        } else {
            $rpl = '';
        }
        if( substr($v,-1)=='&' ) {
            $rpl .= '&';
        }
        $pat = "/$k=$v/";
        $url = preg_replace($pat,$rpl,$url);
    }
    if ($link) {
        $url = str_replace('&','&amp;',$url);
    }
    return $url;
}


