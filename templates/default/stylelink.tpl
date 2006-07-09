<?php
/**
 * stylelink.tpl
 *
 * Template for rendering the links to css sheets for the page
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

//echo SM_PATH;
$base_css="css";

$template_css_file="templates/$templateid/$templateid.css";

/** Add URLs to the $css_files array to have them added as links before any theme css or style.php output **/
$css_files=array();

/** If in existance, add link to template css file named for template **/
if (is_file(SM_PATH.$template_css_file)) $css_files[]=$base_uri.$template_css_file;

$base_css_files=list_css_files(SM_PATH.$base_css,$base_uri.$base_css);

if (!$base_css_files) $base_css_files=array();

/** Add link to default.css in the css directory **/
$css_link_html='';
$css_url=$base_uri.$base_css."/default.css";
$css_link_html=css_link($css_url, null, false, 'all','');

/** Add links to all css files listed in the css_files collection **/
foreach ($css_files as $css_url) {
  $css_link_html.=css_link($css_url, null, false, 'screen', '');
}

/** Add links to each of the .css files in the /css/ directory, making them as alternate if they are not named for the current theme **/
foreach ($base_css_files as $css_file=>$css_url) {
  $css_file_theme=substr($css_file,0,-4);
//    echo $css_file_theme;
   $css_link_html.=css_link($css_url, $css_file_theme, ($css_file_theme!=$themeid), 'screen', '');
}

/** output CSS links **/
echo $css_link_html;

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

/**
 * $Log$
 * Revision 1.2  2006/07/09 22:37:35  vanmer
 * - added variable initalization and check on variable
 *
 * Revision 1.1  2006/07/09 22:23:03  vanmer
 * - intial revision of a template to display CSS links at the top of the page
 *
 *
**/
?>