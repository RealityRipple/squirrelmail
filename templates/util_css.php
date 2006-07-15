<?php
/**
 * util_css.php
 *
 * CSS Utility functions for use with all templates.  Do not echo output here!
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */
 
/**
 * Function to list files within a css directory, used when querying for theme directories
 *
**/
function list_css_files($cssdir,$cssroot) {
    if (!$cssroot OR !$cssdir) return false;
    $files=array();
   if (is_dir($cssdir)) {
        if ($dh = opendir($cssdir)) {
            while (($file = readdir($dh)) !== false) {
                if ((strlen($file)>3) AND strtolower(substr($file,strlen($file)-3,3))=='css') {
                    $files[$file]="$cssroot/$file";
                }
            }
        }
        closedir($dh);
    }
    if (count($files)>0) {
//        sort($files);
        return $files;
    }
    return false;
}

/**
 * Function to create stylesheet links that will work for multiple browsers
 *
**/
function css_link($url, $name = null, $alt = true, $mtype = 'screen', $xhtml_end='/') {
    global $http_site_root;

    if ( empty($url) )
        return '';
    // set to lower case to avoid errors
    $browser_user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

    if (stristr($browser_user_agent, "msie 4"))
    {
        $browser = 'msie4';
        $dom_browser = false;
        $is_IE = true;
    }
    elseif (stristr($browser_user_agent, "msie"))
    {
        $browser = 'msie';
        $dom_browser = true;
        $is_IE = true;
    }

    if ((strpos($url, '-ie')!== false) and !$is_IE) {
        //not IE, so don't render this sheet
        return;
    }

    if ( strpos($url, 'print') !== false )
        $mtype = 'print';

    $href  = 'href="'.$url.'" ';
    $media = 'media="'.$mtype.'" ';

    if ( empty($name) ) {
        $title = '';
        $rel   = 'rel="stylesheet" ';
    } else {
        $title =  empty($name) ? '' : 'title="'.$name.'" ';
        $rel   = 'rel="'.( $alt ? 'alternate ' : '' ).'stylesheet" ';
    }

    return '    <link '.$media.$title.$rel.'type="text/css" '.$href." $xhtml_end>\n";
}


